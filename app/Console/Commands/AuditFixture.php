<?php

namespace App\Console\Commands;

use App\Enums\MosqueStatus;
use App\Models\Mosque;
use App\Models\User;
use App\Services\MosqueProvisioningService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * D11 #2 (PELAN-PEMBAIKAN.md §9.1a — P16-04/P18-03): kitaran hayat fixture larian produksi.
 *
 * PERATURAN KERAS:
 * - Slug larian = `smoke-<run_uuid>` SAHAJA. Slug `smoke` (tenant gate deploy diwan:smoke)
 *   dan mana-mana slug tanpa awalan `smoke-` TIDAK PERNAH disentuh.
 * - `cleanup` memadam HANYA ID yang disenaraikan fail inventori larian itu — bukan corak
 *   slug/e-mel umum; idempotent (larian kedua = 0 perubahan, exit 0).
 * - Superadmin DI LUAR SKOP sepenuhnya (P18-03): tidak dicipta, tidak diubah, tidak ditulis.
 *   `inventory` hanya mengesahkan TEPAT SATU superadmin wujud dan melaporkan e-melnya sahaja.
 * - Kata laluan akaun dijana rawak per larian, ditulis HANYA ke fail --json (ACL oleh wrapper);
 *   stdout tidak pernah memaparkan kata laluan.
 */
class AuditFixture extends Command
{
    protected $signature = 'diwan:audit-fixture
        {action : prepare|cleanup|inventory}
        {--run= : UUID larian (WAJIB, format UUIDv4)}
        {--json= : Laluan fail output (prepare: kredensial+inventori; cleanup/inventory: laporan)}
        {--force : Teruskan cleanup walaupun fail inventori tiada (padan ikut run uuid sahaja)}';

    protected $description = 'Setup/cleanup/inventory fixture larian audit produksi (run-scoped, idempotent)';

    protected const INVENTORY_TABLES = ['mosques', 'users', 'login_tokens', 'help_events', 'guidance_progress'];

    public function handle(): int
    {
        $run = (string) $this->option('run');
        if (! preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $run)) {
            $this->error('--run wajib UUIDv4 sah (tiada tekaan, tiada pembetulan senyap).');

            return self::FAILURE;
        }
        $slug = 'smoke-'.$run;

        return match ($this->argument('action')) {
            'prepare' => $this->prepare($run, $slug),
            'cleanup' => $this->cleanup($run, $slug),
            'inventory' => $this->inventory($run, $slug),
            default => $this->invalidAction(),
        };
    }

    protected function invalidAction(): int
    {
        $this->error('Tindakan mesti prepare|cleanup|inventory.');

        return self::FAILURE;
    }

    protected function prepare(string $run, string $slug): int
    {
        if (Mosque::query()->withTrashed()->where('slug', $slug)->exists()) {
            $this->error("Tenant {$slug} sudah wujud — guna run uuid baharu atau cleanup dahulu.");

            return self::FAILURE;
        }

        $before = $this->tableCounts();

        $mosque = Mosque::query()->create([
            'name' => 'Masjid Audit '.substr($run, 0, 8),
            'slug' => $slug,
            'code' => strtoupper(substr(hash('sha256', $slug), 0, 6)),
            'status' => MosqueStatus::Menunggu,
            'storage_quota_bytes' => 20 * (1024 ** 3),
            'storage_used_bytes' => 0,
            'auto_disposal_enabled' => false,
            'wa_session_id' => $slug,
            'settings' => ['wa_intake_enabled' => false],
        ]);
        app(MosqueProvisioningService::class)->approve($mosque->fresh());

        $accounts = [];
        $credentials = [];
        foreach (config('roles.list', []) as $role) {
            $email = "{$role}-{$run}@smoke.test";
            $password = Str::random(24);
            $user = User::query()->create([
                'name' => ucwords(str_replace('_', ' ', $role)).' Audit',
                'email' => $email,
                'password' => Hash::make($password),
                'is_active' => true,
            ]);
            $mosque->users()->attach($user->id, ['role' => $role, 'joined_at' => now()]);
            $accounts[] = ['id' => $user->id, 'email' => $email, 'role' => $role];
            $credentials[] = ['role' => $role, 'email' => $email, 'password' => $password, 'pages' => null];
        }

        $inventory = [
            'run_uuid' => $run,
            'slug' => $slug,
            'created' => [
                'mosque_id' => $mosque->id,
                'user_ids' => array_column($accounts, 'id'),
                'accounts' => $accounts,
            ],
            'before' => $before,
            // Kredensial role SAHAJA masuk fail ini (wrapper menetap ACL + memadamnya dlm
            // finally). Superadmin TIDAK PERNAH ditulis di sini (P18-03).
            'role_credentials' => $credentials,
        ];

        $path = $this->option('json');
        if (! $path) {
            $this->error('prepare memerlukan --json=<fail output> (kredensial tidak dicetak ke stdout).');

            return self::FAILURE;
        }
        @mkdir(dirname($path), 0700, true);
        file_put_contents($path, json_encode($inventory, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);

        $this->info("prepare selesai: tenant {$slug} (id {$mosque->id}) + ".count($accounts).' akaun role.');
        foreach ($accounts as $account) {
            $this->line("  - {$account['role']}: {$account['email']} (id {$account['id']})");
        }
        $this->line('Kredensial ditulis ke fail --json sahaja.');

        return self::SUCCESS;
    }

    protected function cleanup(string $run, string $slug): int
    {
        $path = $this->option('json');
        $inventory = null;
        if ($path && is_file($path)) {
            $inventory = json_decode((string) file_get_contents($path), true);
            if (($inventory['run_uuid'] ?? null) !== $run) {
                $this->error('Fail inventori bukan milik run uuid ini — enggan meneruskan.');

                return self::FAILURE;
            }
        } elseif (! $this->option('force')) {
            $this->error('Fail inventori tiada. Beri --json=<fail inventori> atau --force (padan run uuid sahaja).');

            return self::FAILURE;
        }

        $deleted = ['users' => 0, 'mosques' => 0, 'login_tokens' => 0];

        $userIds = $inventory['created']['user_ids'] ?? User::query()
            ->where('email', 'like', "%-{$run}@smoke.test")->pluck('id')->all();
        $mosque = Mosque::query()->withTrashed()->where('slug', $slug)->first();

        DB::transaction(function () use ($userIds, $mosque, &$deleted): void {
            if ($userIds !== []) {
                $users = User::query()->whereIn('id', $userIds)->get();
                foreach ($users as $user) {
                    // Penjaga keras: hanya akaun larian ini; superadmin & akaun lain TIDAK disentuh.
                    if (! str_ends_with((string) $user->email, '@smoke.test') || $user->is_superadmin) {
                        continue;
                    }
                    $deleted['login_tokens'] += DB::table('login_tokens')->where('user_id', $user->id)->delete();
                    $user->mosques()->detach();
                    $user->forceDelete();
                    $deleted['users']++;
                }
            }

            if ($mosque) {
                if ($mosque->slug === 'smoke' || ! str_starts_with($mosque->slug, 'smoke-')) {
                    throw new \RuntimeException("Penjaga slug: enggan memadam {$mosque->slug}.");
                }
                foreach (['help_events', 'guidance_progress', 'mosque_activity_logs', 'records', 'registry_files', 'classification_nodes', 'retention_rules', 'mosque_user'] as $table) {
                    if (DB::getSchemaBuilder()->hasTable($table)) {
                        DB::table($table)->where('mosque_id', $mosque->id)->delete();
                    }
                }
                $mosque->forceDelete();
                $deleted['mosques'] = 1;
            }
        });

        $this->info('cleanup selesai (idempotent): '.json_encode($deleted).' — 0 perubahan jika larian kedua.');
        if ($path && is_file($path) && ($deleted['users'] > 0 || $deleted['mosques'] > 0)) {
            $this->line('Nota: fail inventori dikekalkan sebagai rekod; wrapper memadam fail rahsia.');
        }

        return self::SUCCESS;
    }

    protected function inventory(string $run, string $slug): int
    {
        $counts = $this->tableCounts();
        $superadmins = User::query()->where('is_superadmin', true)->get();
        $report = [
            'run_uuid' => $run,
            'slug' => $slug,
            'counts' => $counts,
            'run_scoped' => [
                'mosque_exists' => Mosque::query()->where('slug', $slug)->exists(),
                'run_users' => User::query()->where('email', 'like', "%-{$run}@smoke.test")->count(),
            ],
            'superadmin' => [
                'count' => $superadmins->count(),
                'emails' => $superadmins->pluck('email')->all(), // e-mel SAHAJA — tiada kredensial
            ],
        ];

        if ($superadmins->count() !== 1) {
            $this->warn('Amaran: bilangan superadmin ≠ 1 ('.$superadmins->count().').');
        }
        $json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($path = $this->option('json')) {
            @mkdir(dirname($path), 0700, true);
            file_put_contents($path, $json.PHP_EOL);
        }
        $this->line($json);

        return self::SUCCESS;
    }

    /** @return array<string,int> */
    protected function tableCounts(): array
    {
        $counts = [];
        foreach (self::INVENTORY_TABLES as $table) {
            $counts[$table] = DB::getSchemaBuilder()->hasTable($table) ? (int) DB::table($table)->count() : 0;
        }

        return $counts;
    }
}
