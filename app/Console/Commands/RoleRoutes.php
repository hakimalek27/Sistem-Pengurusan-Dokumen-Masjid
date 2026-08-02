<?php

namespace App\Console\Commands;

use App\Filament\Admin\Pages\ProfilSaya;
use App\Filament\Admin\Pages\StatusSambungan;
use App\Filament\Admin\Pages\TetapanPlatform;
use App\Filament\Admin\Pages\WhatsAppPlatform;
use App\Filament\Admin\Resources\HelpAnnouncements\HelpAnnouncementResource;
use App\Filament\Admin\Resources\Mosques\MosqueResource;
use App\Filament\Admin\Resources\StorageOrders\StorageOrderResource;
use App\Filament\Admin\Resources\Users\UserResource;
use App\Filament\App\Pages\AhliPeranan;
use App\Filament\App\Pages\AnalitikBantuan;
use App\Filament\App\Pages\Bantuan;
use App\Filament\App\Pages\CariRekod;
use App\Filament\App\Pages\Kegemaran;
use App\Filament\App\Pages\Laporan;
use App\Filament\App\Pages\OnboardingWizard;
use App\Filament\App\Pages\PelupusanManual;
use App\Filament\App\Pages\PenggunaanStoran;
use App\Filament\App\Pages\Profil;
use App\Filament\App\Pages\RetensiPegangan;
use App\Filament\App\Pages\TetapanMasjid;
use App\Filament\App\Resources\Approvals\ApprovalResource;
use App\Filament\App\Resources\ClassificationNodes\ClassificationNodeResource;
use App\Filament\App\Resources\Delegations\DelegationResource;
use App\Filament\App\Resources\Inbox\InboxResource;
use App\Filament\App\Resources\Minits\MinitResource;
use App\Filament\App\Resources\MosqueActivityLogs\MosqueActivityLogResource;
use App\Filament\App\Resources\RecordCorrections\RecordCorrectionResource;
use App\Filament\App\Resources\Records\RecordResource;
use App\Filament\App\Resources\RegistryFiles\RegistryFileResource;
use App\Filament\App\Resources\RetentionRules\RetentionRuleResource;
use App\Filament\App\Resources\SensitiveAccessLogs\SensitiveAccessLogResource;
use App\Filament\App\Resources\SupportRequests\SupportRequestResource;
use App\Models\Mosque;
use App\Models\User;
use App\Support\Roles;
use Filament\Facades\Filament;
use Filament\Pages\Dashboard;
use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;

/**
 * F0(ii-b) pelan pembaikan — jana manifest `role_routes` TIGA LAPIS (P14-03/P16-07):
 *   A `expected_access`  : SPEC — peta kebenaran statik di bawah (kunci permission per kelas,
 *                          dinilai terhadap matriks config/roles.php melalui Roles::can()) +
 *                          peraturan panel. TIDAK PERNAH ditulis semula daripada B atau C.
 *   B `declared_access`  : KOD — penilaian authorizer sebenar (Page::canAccess()/Resource::canAccess())
 *                          sebagai setiap identiti, tanpa HTTP.
 *   C `actual_status`    : RUNTIME — probe HTTP dalaman (kernel handle) sebagai identiti itu.
 * Mismatch mana-mana pasangan = dilaporkan; command READ-ONLY (tiada mutasi data).
 * Semesta route dibina TANPA tapisan identiti: getPages() + getResources() kedua-dua panel + route awam.
 *
 * Persekitaran: local/testing dengan DemoSeeder ({role}@demo.test / superadmin@diwan.test,
 * tenant mam+man). DILARANG pada produksi — manifest ialah artifak repo, bukan alat produksi.
 */
class RoleRoutes extends Command
{
    protected $signature = 'diwan:role-routes
        {--json= : Laluan fail output JSON}
        {--tenant=mam : Slug tenant seeded untuk konteks panel app}
        {--cross-tenant=man : Slug tenant kedua untuk probe silang-tenant (S1)}
        {--probe : Probe HTTP dalaman lapisan C (best-effort; C autoritatif = PlanManifestTest + runner F8)}';

    protected $description = 'Jana manifest role_routes 3 lapis (expected/declared/actual) — read-only';

    /**
     * LAPISAN A — peta kebenaran SPEC per kelas (kunci permission daripada §6.2/§9.C, dinilai
     * terhadap config/roles.php). `rule`:
     *   permission     => allow jika Roles::can($role, permission)
     *   permission_any => allow jika mana-mana permission dalam senarai
     *   membership     => semua ahli tenant (8 role)
     *   config         => semua ahli, bergantung suis config (dinyatakan)
     *   superadmin     => superadmin sahaja (panel admin)
     */
    protected const APP_EXPECTED = [
        Dashboard::class => ['rule' => 'membership', 'permission' => null],
        AhliPeranan::class => ['rule' => 'permission', 'permission' => 'users.manage'],
        AnalitikBantuan::class => ['rule' => 'permission', 'permission' => 'help.analytics', 'config' => 'diwan.guidance.enabled'],
        Bantuan::class => ['rule' => 'config', 'permission' => null, 'config' => 'diwan.guidance.enabled'],
        CariRekod::class => ['rule' => 'permission', 'permission' => 'records.view'],
        Kegemaran::class => ['rule' => 'permission', 'permission' => 'records.view'],
        Laporan::class => ['rule' => 'permission', 'permission' => 'records.view'],
        OnboardingWizard::class => ['rule' => 'permission', 'permission' => 'mosque.settings'],
        PelupusanManual::class => ['rule' => 'permission_any', 'permission' => ['disposal.prepare', 'disposal.approve', 'disposal.execute']],
        PenggunaanStoran::class => ['rule' => 'permission', 'permission' => 'usage.view'],
        Profil::class => ['rule' => 'membership', 'permission' => null],
        RetensiPegangan::class => ['rule' => 'permission_any', 'permission' => ['retention.manage', 'retention.hold']],
        TetapanMasjid::class => ['rule' => 'permission', 'permission' => 'mosque.settings'],
        ApprovalResource::class => ['rule' => 'permission', 'permission' => 'records.view'],
        ClassificationNodeResource::class => ['rule' => 'permission', 'permission' => 'files.view'],
        DelegationResource::class => ['rule' => 'permission', 'permission' => 'records.view'],
        InboxResource::class => ['rule' => 'permission', 'permission' => 'inbox.view'],
        MinitResource::class => ['rule' => 'permission', 'permission' => 'records.view'],
        MosqueActivityLogResource::class => ['rule' => 'permission', 'permission' => 'activity.view'],
        RecordCorrectionResource::class => ['rule' => 'permission', 'permission' => 'records.view'],
        RecordResource::class => ['rule' => 'permission', 'permission' => 'records.view'],
        RegistryFileResource::class => ['rule' => 'permission', 'permission' => 'files.view'],
        RetentionRuleResource::class => ['rule' => 'permission', 'permission' => 'retention.manage'],
        SensitiveAccessLogResource::class => ['rule' => 'permission', 'permission' => 'audit.view'],
        SupportRequestResource::class => ['rule' => 'permission', 'permission' => 'support.manage', 'config' => 'diwan.guidance.support_enabled'],
    ];

    protected const ADMIN_EXPECTED = [
        Dashboard::class => ['rule' => 'superadmin', 'permission' => null],
        \App\Filament\Admin\Pages\AnalitikBantuan::class => ['rule' => 'superadmin', 'permission' => null, 'config' => 'diwan.guidance.enabled'],
        \App\Filament\Admin\Pages\Bantuan::class => ['rule' => 'superadmin', 'permission' => null, 'config' => 'diwan.guidance.enabled'],
        ProfilSaya::class => ['rule' => 'superadmin', 'permission' => null],
        StatusSambungan::class => ['rule' => 'superadmin', 'permission' => null],
        TetapanPlatform::class => ['rule' => 'superadmin', 'permission' => null],
        WhatsAppPlatform::class => ['rule' => 'superadmin', 'permission' => null],
        HelpAnnouncementResource::class => ['rule' => 'superadmin', 'permission' => null],
        MosqueResource::class => ['rule' => 'superadmin', 'permission' => null],
        StorageOrderResource::class => ['rule' => 'superadmin', 'permission' => null],
        \App\Filament\Admin\Resources\SupportRequests\SupportRequestResource::class => ['rule' => 'superadmin', 'permission' => null, 'config' => 'diwan.guidance.support_enabled'],
        UserResource::class => ['rule' => 'superadmin', 'permission' => null],
    ];

    /** Route awam (identiti `public` positif; identiti lain turut boleh capai halaman awam). */
    protected const PUBLIC_ROUTES = ['/', '/log-masuk', '/daftar', '/bantuan'];

    public function handle(): int
    {
        if (app()->isProduction()) {
            $this->error('diwan:role-routes DILARANG pada produksi — manifest dijana pada persekitaran seeded (local/CI).');

            return self::FAILURE;
        }

        $tenant = Mosque::query()->where('slug', $this->option('tenant'))->first();
        $cross = Mosque::query()->where('slug', $this->option('cross-tenant'))->first();
        if (! $tenant || ! $cross) {
            $this->error('Tenant seeded tidak ditemui — jalankan `php artisan migrate:fresh --seed` (DemoSeeder) dahulu.');

            return self::FAILURE;
        }

        $identities = $this->identities($tenant);
        if ($identities === null) {
            return self::FAILURE;
        }

        // Semesta TANPA tapisan identiti (P16-07).
        $universe = $this->universe($tenant);
        $unmapped = array_diff(array_keys($universe['app']), array_keys(self::APP_EXPECTED));
        $unmappedAdmin = array_diff(array_keys($universe['admin']), array_keys(self::ADMIN_EXPECTED));
        if ($unmapped !== [] || $unmappedAdmin !== []) {
            // Penjaga drift: kelas baharu WAJIB didaftar dalam peta SPEC di atas (bukan diteka).
            $this->error('Kelas panel TIADA dalam peta expected (kemas kini RoleRoutes): '.implode(', ', [...$unmapped, ...$unmappedAdmin]));

            return self::FAILURE;
        }

        $entries = [];
        foreach (['app' => $universe['app'], 'admin' => $universe['admin']] as $panelId => $components) {
            foreach ($components as $class => $meta) {
                foreach ($identities as $identity => $user) {
                    $expected = $this->expectedAccess($panelId, $class, $identity);
                    [$declared, $inNav] = $this->declaredAccess($panelId, $class, $identity, $user, $tenant);
                    $actual = $this->option('probe') ? $this->probe($meta['url'], $user) : null;
                    $entries[] = [
                        'identity' => $identity,
                        'route_template' => $meta['template'],
                        'url' => $meta['url'],
                        'panel' => $panelId,
                        'kind' => $meta['kind'],
                        'class' => $class,
                        'authorizer' => $meta['authorizer'],
                        'permission' => $this->permissionLabel($panelId, $class),
                        'expected_access' => $expected['access'],
                        'expected_status' => $expected['status'],
                        'expected_rule' => $expected['rule'],
                        'declared_access' => $declared,
                        'actual_status' => $actual,
                        'requires_tenant' => $panelId === 'app',
                        'category' => 'read-only',
                        'viewport' => 'both',
                        'in_navigation' => $inNav,
                    ];
                }
            }
        }

        foreach (self::PUBLIC_ROUTES as $path) {
            foreach ($identities as $identity => $user) {
                $actual = $this->option('probe') ? $this->probe($path, $user) : null;
                $entries[] = [
                    'identity' => $identity, 'route_template' => $path, 'url' => $path,
                    'panel' => 'public', 'kind' => 'route', 'class' => null,
                    'authorizer' => 'routes/web.php (awam)', 'permission' => null,
                    'expected_access' => 'allow', 'expected_status' => 200,
                    'expected_rule' => 'awam — terbuka kepada semua identiti',
                    'declared_access' => 'allow', 'actual_status' => $actual,
                    'requires_tenant' => false, 'category' => 'read-only',
                    'viewport' => 'both', 'in_navigation' => false,
                ];
            }
        }

        // Probe silang-tenant (S1): setiap role tenant + superadmin? — superadmin sah akses semua
        // tenant (User::canAccessTenant), jadi silang-tenant 404 dijangka untuk 8 role SAHAJA.
        $crossProbes = [];
        foreach ($identities as $identity => $user) {
            if (! in_array($identity, config('roles.list', []), true)) {
                continue;
            }
            $crossProbes[] = [
                'identity' => $identity,
                'url' => "/app/{$cross->slug}/records",
                'expected_status' => 404,
                'actual_status' => $this->option('probe') ? $this->probe("/app/{$cross->slug}/records", $user) : null,
                'rule' => '§0.6 S1 — silang-tenant mesti 404 (tidak membocorkan kewujudan)',
            ];
        }

        $counts = [];
        foreach (array_keys($identities) as $identity) {
            $counts[$identity] = count(array_filter(
                $entries,
                fn (array $e): bool => $e['identity'] === $identity && $e['panel'] === 'app'
                    && $e['expected_access'] === 'allow' && $e['in_navigation'],
            ));
        }

        $mismatches = array_values(array_filter($entries, fn (array $e): bool => $e['expected_access'] !== $e['declared_access']
            || ($e['actual_status'] !== null && $e['actual_status'] !== $e['expected_status'])));

        $payload = [
            'schema_version' => 1,
            'generated_by' => 'php artisan diwan:role-routes',
            'identities' => array_keys($identities),
            'tenant' => $tenant->slug,
            'cross_tenant' => $cross->slug,
            'expected_page_counts' => $counts,
            'entries' => $entries,
            'cross_tenant_probes' => $crossProbes,
            'mismatches' => $mismatches,
        ];

        if ($path = $this->option('json')) {
            @mkdir(dirname($path), 0775, true);
            file_put_contents($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
            $this->info('role_routes ditulis: '.$path.' ('.count($entries).' entri).');
        } else {
            $this->line(json_encode($payload, JSON_UNESCAPED_SLASHES));
        }

        $this->table(['Identiti', 'Halaman nav (expected)'], collect($counts)->map(fn ($c, $i) => [$i, $c])->values()->all());

        if ($mismatches !== []) {
            $this->error(count($mismatches).' MISMATCH antara lapisan (expected vs declared/actual) — lihat medan `mismatches`.');

            return self::FAILURE;
        }
        $this->info('Tiada mismatch expected↔declared'.($this->option('probe') ? '↔actual' : ' (lapisan C dikuatkuasakan oleh PlanManifestTest)').'.');

        return self::SUCCESS;
    }

    /** @return array<string, ?User>|null identiti => user (public => null) */
    protected function identities(Mosque $tenant): ?array
    {
        $identities = ['public' => null];
        $superadmin = User::query()->where('email', 'superadmin@diwan.test')->first();
        if (! $superadmin?->is_superadmin) {
            $this->error('superadmin@diwan.test tiada — jalankan DemoSeeder.');

            return null;
        }
        $identities['superadmin'] = $superadmin;
        foreach (config('roles.list', []) as $role) {
            $user = User::query()->where('email', "{$role}@demo.test")->first();
            if (! $user || $user->roleIn($tenant) !== $role) {
                $this->error("Akaun {$role}@demo.test tiada / role tidak sepadan — jalankan DemoSeeder.");

                return null;
            }
            $identities[$role] = $user;
        }

        return $identities;
    }

    /** @return array{app: array<class-string, array>, admin: array<class-string, array>} */
    protected function universe(Mosque $tenant): array
    {
        $result = ['app' => [], 'admin' => []];
        foreach (['app', 'admin'] as $panelId) {
            $panel = Filament::getPanel($panelId);
            foreach ($panel->getPages() as $page) {
                $url = $panelId === 'app' ? $page::getUrl(panel: $panelId, tenant: $tenant) : $page::getUrl(panel: $panelId);
                $result[$panelId][$page] = [
                    'kind' => 'page',
                    'url' => $this->relative($url),
                    'template' => str_replace('/'.$tenant->slug, '/{tenant}', $this->relative($url)),
                    'authorizer' => $page.'::canAccess()',
                ];
            }
            foreach ($panel->getResources() as $resource) {
                $url = $panelId === 'app' ? $resource::getUrl('index', panel: $panelId, tenant: $tenant) : $resource::getUrl('index', panel: $panelId);
                $result[$panelId][$resource] = [
                    'kind' => 'resource-index',
                    'url' => $this->relative($url),
                    'template' => str_replace('/'.$tenant->slug, '/{tenant}', $this->relative($url)),
                    'authorizer' => $resource.'::canAccess() → canViewAny()/policy',
                ];
            }
        }

        return $result;
    }

    /** LAPISAN A — daripada peta SPEC + config/roles.php SAHAJA (tiada panggilan canAccess). */
    protected function expectedAccess(string $panelId, string $class, string $identity): array
    {
        if ($identity === 'public') {
            return ['access' => 'deny', 'status' => 302, 'rule' => '§0.6 S6 — tetamu diubah hala ke log masuk, tiada naik taraf'];
        }
        if ($panelId === 'admin') {
            return $identity === 'superadmin'
                ? ['access' => 'allow', 'status' => 200, 'rule' => 'panel admin = superadmin sahaja (§6.0)']
                : ['access' => 'deny', 'status' => 403, 'rule' => '§0.6 S2 — role tenant ditolak dari /admin'];
        }
        // Panel app.
        if ($identity === 'superadmin') {
            return ['access' => 'allow', 'status' => 200, 'rule' => 'superadmin akses penuh semua tenant (§6.0 Gate::before + §9.C.2 "Masuk Panel Masjid")'];
        }
        $map = self::APP_EXPECTED[$class];
        $allow = match ($map['rule']) {
            'membership', 'config' => true,
            'permission' => Roles::can($identity, $map['permission']),
            'permission_any' => collect($map['permission'])->contains(fn (string $p): bool => Roles::can($identity, $p)),
            default => false,
        };

        return $allow
            ? ['access' => 'allow', 'status' => 200, 'rule' => $this->permissionLabel($panelId, $class) ?? $map['rule']]
            : ['access' => 'deny', 'status' => 403, 'rule' => 'tiada kebenaran dalam matriks §6.2 ('.($this->permissionLabel($panelId, $class) ?? $map['rule']).')'];
    }

    /**
     * LAPISAN B — penilaian authorizer kod sebagai identiti (tanpa HTTP).
     * Authorizer efektif = gate panel (User::canAccessPanel) + gate tenant (User::canAccessTenant)
     * + authorizer kelas (canAccess) — kerana halaman tanpa canAccess() sendiri (cth. ProfilSaya,
     * TetapanPlatform, Dashboard) bergantung SEPENUHNYA pada gate panel; menilai kelas sahaja
     * memberi "allow" palsu untuk role tenant di /admin.
     *
     * @return array{0:string,1:bool} [declared_access, in_navigation]
     */
    protected function declaredAccess(string $panelId, string $class, string $identity, ?User $user, Mosque $tenant): array
    {
        if ($identity === 'public') {
            return ['deny', false]; // panel memerlukan auth — guest tidak sampai ke canAccess()
        }

        try {
            Auth::login($user);
            $panel = Filament::getPanel($panelId);
            Filament::setCurrentPanel($panel);
            if (! $user->canAccessPanel($panel)) {
                return ['deny', false];
            }
            if ($panelId === 'app') {
                if (! $user->canAccessTenant($tenant)) {
                    return ['deny', false];
                }
                Filament::setTenant($tenant);
            }
            $can = (bool) $class::canAccess();

            return [$can ? 'allow' : 'deny', $can && $class::shouldRegisterNavigation()];
        } finally {
            if ($panelId === 'app') {
                Filament::setTenant(null);
            }
            Auth::logout();
            $this->resetRequestState();
        }
    }

    /** LAPISAN C — probe HTTP dalaman (kernel handle penuh middleware; read-only GET). */
    protected function probe(string $path, ?User $user): int
    {
        $kernel = app(HttpKernel::class);
        $request = Request::create($path, 'GET');

        try {
            $this->resetRequestState();
            if ($user) {
                Auth::guard('web')->setUser($user);
            }
            $response = $kernel->handle($request);
            if ($response->getStatusCode() >= 500 && $this->output->isVerbose()) {
                $this->warn('PROBE 5xx '.$path.': '.substr(preg_replace('/\s+/', ' ', strip_tags((string) $response->getContent())) ?? '', 0, 300));
            }

            return $response->getStatusCode();
        } catch (\Throwable $exception) {
            if ($this->output->isVerbose()) {
                $this->warn('PROBE THROW '.$path.': '.$exception->getMessage());
            }

            return 500;
        } finally {
            $this->resetRequestState();
        }
    }

    /**
     * Sesi konsol DIKONGSI antara panggilan kernel->handle() — tanpa flush, AuthenticateSession
     * membandingkan `password_hash_web` pengguna probe terdahulu dengan pengguna baharu →
     * logout/redirect berselang-seli (404/302/404…). Flush penuh antara probe menjadikan setiap
     * probe permintaan "pelayar baharu" yang tulen.
     */
    protected function resetRequestState(): void
    {
        Auth::guard('web')->forgetUser();
        if (app()->bound('session.store')) {
            session()->flush();
        }
        // Render Livewire penuh (halaman Filament) meninggalkan konteks komponen dalam
        // proses konsol; tanpa flush, `redirect()` permintaan BERIKUTNYA memulangkan
        // Livewire Redirector (bukan Symfony Response) → TypeError pada hook respons
        // bootstrap/app.php:34. flushState() ialah reset rasmi Livewire (corak Octane).
        Livewire::flushState();
    }

    protected function permissionLabel(string $panelId, string $class): ?string
    {
        $map = ($panelId === 'app' ? self::APP_EXPECTED : self::ADMIN_EXPECTED)[$class] ?? null;
        if (! $map || $map['permission'] === null) {
            return null;
        }

        return is_array($map['permission']) ? implode('|', $map['permission']) : $map['permission'];
    }

    protected function relative(string $url): string
    {
        $parsed = parse_url($url, PHP_URL_PATH);

        return $parsed === null || $parsed === '' ? '/' : $parsed;
    }
}
