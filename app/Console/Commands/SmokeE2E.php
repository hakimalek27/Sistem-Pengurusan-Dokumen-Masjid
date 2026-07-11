<?php

namespace App\Console\Commands;

use App\Enums\ApprovalStatus;
use App\Enums\MinitPriority;
use App\Enums\MosqueStatus;
use App\Enums\RecordStatus;
use App\Enums\SourceChannel;
use App\Models\Mosque;
use App\Models\Record;
use App\Models\RetentionRule;
use App\Models\User;
use App\Services\ApprovalService;
use App\Services\DisposalService;
use App\Services\ExportService;
use App\Services\InboxIngestService;
use App\Services\MembershipService;
use App\Services\MinitService;
use App\Services\MosqueProvisioningService;
use App\Services\RecordNumberingService;
use App\Services\RetentionEngine;
use App\Services\SearchService;
use Database\Seeders\RetentionRuleSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

// Fasa 9 — Smoke E2E berskrip: daftar → lulus → ahli → ingest → klasifikasi → minit →
// kelulusan → carian → eksport → backdate → notis → auto-padam → sijil.
class SmokeE2E extends Command
{
    protected $signature = 'diwan:smoke {--slug=smoke}';

    protected $description = 'Jalankan aliran E2E penuh untuk pengesahan kesediaan';

    protected int $pass = 0;

    protected int $fail = 0;

    public function handle(): int
    {
        $this->pass = 0;
        $this->fail = 0;
        config()->set('scout.driver', 'collection');
        $slug = $this->option('slug');
        $smokeCode = strtoupper(substr(hash('sha256', $slug), 0, 8));

        // 1. Daftar masjid (menunggu).
        $mosque = Mosque::query()->updateOrCreate(['slug' => $slug], [
            'name' => 'Masjid Smoke E2E', 'code' => $smokeCode,
            'status' => MosqueStatus::Menunggu, 'storage_quota_bytes' => 20 * (1024 ** 3),
            'auto_disposal_enabled' => true, 'wa_session_id' => $slug,
            'settings' => ['wa_intake_enabled' => true, 'wa_intake_keyword' => 'spdm'],
        ]);
        $admin = User::query()->updateOrCreate(['email' => "admin-{$slug}@smoke.test"],
            ['name' => 'Admin Smoke', 'is_active' => true, 'password' => Hash::make('password')]);
        $mosque->users()->syncWithoutDetaching([$admin->id => ['role' => 'admin_masjid', 'joined_at' => now()]]);
        $this->check('Daftar masjid (menunggu)', $mosque->status === MosqueStatus::Menunggu);

        // 2. Lulus & provision.
        app(MosqueProvisioningService::class)->approve($mosque->fresh());
        $mosque->refresh();
        $this->check('Lulus + KF disalin (40 nod)', $mosque->status === MosqueStatus::Aktif && $mosque->classificationNodes()->count() === 40);

        // 3. Jemput ahli.
        $kerani = app(MembershipService::class)->invite($mosque, "kerani-{$slug}@smoke.test", 'Kerani', 'kerani');
        $pengerusi = app(MembershipService::class)->invite($mosque, "pengerusi-{$slug}@smoke.test", 'Pengerusi', 'pengerusi');
        $this->check('Jemput ahli (kerani + pengerusi)', $kerani->roleIn($mosque) === 'kerani' && $pengerusi->roleIn($mosque) === 'pengerusi');

        // 4-5. Ingest + klasifikasi.
        $ingest = app(InboxIngestService::class);
        $node = $mosque->classificationNodes()->where('code', '100-4')->first();
        $file = app(RecordNumberingService::class)->openFile($mosque, $node, 'Surat Am Smoke', $kerani->id);
        $record = $ingest->ingest($mosque, 'dokumen smoke DEWAN', 'surat.pdf', 'application/pdf', $kerani, SourceChannel::MuatNaik);
        $record = $ingest->fileRecord($record, $file, ['title' => 'Surat DEWAN Smoke', 'record_type' => 'surat_menyurat'], $kerani);
        $this->check('Klasifikasi → difailkan + rujukan '.$file->file_no.'('.$record->enclosure_no.')', $record->status === RecordStatus::Difailkan && $record->enclosure_no === 1);

        // 6. Minit.
        $minit = app(MinitService::class)->create($record, $kerani, [$pengerusi->id], [], 'Untuk tindakan', MinitPriority::Biasa);
        $this->check('Edarkan minit', $minit->recipients()->count() === 1);

        // 7. Kelulusan.
        $approval = app(ApprovalService::class)->request($record, $kerani, $pengerusi, 'Sila lulus');
        app(ApprovalService::class)->decide($approval, $pengerusi, ApprovalStatus::Lulus, 'OK', '127.0.0.1');
        $this->check('Kelulusan (lulus + IP)', $approval->fresh()->status === ApprovalStatus::Lulus && $approval->fresh()->decision_ip === '127.0.0.1');

        // 8. Carian (collection driver).
        $results = app(SearchService::class)->for($admin, $mosque, 'DEWAN');
        $this->check('Carian jumpa rekod', $results->contains('id', $record->id));

        // 9. Eksport ZIP.
        $zip = app(ExportService::class)->build($mosque, Record::query()->whereKey($record->id)->get(), 'smoke');
        $this->check('Eksport ZIP dijana', Storage::disk(config('diwan.storage_disk'))->exists($zip));

        // 10. Backdate + retensi + auto-padam.
        $this->seedRetentionRules();
        $record->update(['record_date' => now()->subYears(8)]);
        app(RetentionEngine::class)->refreshForRecord($record->fresh());
        $record->update(['retention_notified' => ['t90' => 'x', 't30' => 'x', 't7' => 'x']]);
        $batch = app(DisposalService::class)->executeAuto($mosque->fresh());
        $this->check('Auto-padam + sijil + batu nisan', $batch !== null && $record->fresh()->status === RecordStatus::Dilupus && $batch->certificate_path !== null);

        $this->newLine();
        $this->info("SMOKE E2E: {$this->pass} lulus, {$this->fail} gagal.");

        return $this->fail === 0 ? self::SUCCESS : self::FAILURE;
    }

    protected function check(string $label, bool $ok): void
    {
        $ok ? $this->pass++ : $this->fail++;
        $this->line(($ok ? '  <fg=green>✓</>' : '  <fg=red>✘</>').' '.$label);
    }

    protected function seedRetentionRules(): void
    {
        if (RetentionRule::query()->whereNull('mosque_id')->exists()) {
            return;
        }
        (new RetentionRuleSeeder)->run();
    }
}
