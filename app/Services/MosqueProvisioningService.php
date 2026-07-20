<?php

namespace App\Services;

use App\Enums\MosqueStatus;
use App\Jobs\CreateMosqueDriveFolderJob;
use App\Models\ClassificationNode;
use App\Models\Mosque;
use App\Models\User;
use App\Services\GoogleDrive\DriveConfig;
use Illuminate\Support\Facades\DB;

/**
 * §10.I / Aliran I — Pendaftaran & onboarding masjid.
 */
class MosqueProvisioningService
{
    public function __construct(protected MagicLinkService $magic) {}

    /** Luluskan masjid: status aktif, salin KF, ack retensi, aktifkan admin, hantar magic link. */
    public function approve(Mosque $mosque, ?User $approver = null): void
    {
        DB::transaction(function () use ($mosque, $approver) {
            if ($mosque->classificationNodes()->count() === 0) {
                $this->copyClassificationTemplate($mosque);
            }

            $mosque->update([
                'status' => MosqueStatus::Aktif,
                'approved_at' => now(),
                'approved_by' => $approver?->id,
                'retention_ack_at' => $mosque->retention_ack_at ?? now(),
                'retention_ack_by' => $mosque->retention_ack_by ?? $approver?->id,
            ]);

            // Aktifkan admin_masjid masjid ini.
            $mosque->users()->wherePivot('role', 'admin_masjid')->get()
                ->each(fn (User $u) => $u->is_active ?: $u->update(['is_active' => true]));
        });

        // Hantar magic link kepada setiap admin_masjid (luar transaksi).
        $mosque->users()->wherePivot('role', 'admin_masjid')->get()
            ->each(fn (User $u) => $this->magic->sendToUser($u));

        // §4.6′ — Sedia folder mirror Google Drive untuk masjid ini (jika aktif).
        if (DriveConfig::enabled()) {
            CreateMosqueDriveFolderJob::dispatch($mosque->id)->onQueue('backup');
        }
    }

    /** Tolak permohonan pendaftaran (sebab wajib). */
    public function reject(Mosque $mosque, string $reason): void
    {
        $mosque->update([
            'status' => MosqueStatus::Ditutup,
            'settings' => array_merge($mosque->settings ?? [], ['rejection_reason' => $reason]),
        ]);
    }

    /** §7 — Salin templat KF platform ke classification_nodes masjid. */
    public function copyClassificationTemplate(Mosque $mosque): void
    {
        $template = require database_path('seeders/data/kf_template.php');

        foreach ($template as $code => [$title, $sensitivity, $activities]) {
            $fungsi = ClassificationNode::query()->create([
                'mosque_id' => $mosque->id,
                'parent_id' => null,
                'level' => 'fungsi',
                'code' => $code,
                'title' => $title,
                'default_sensitivity' => $sensitivity,
                'is_active' => true,
                'sort' => 0,
            ]);

            $sort = 0;
            foreach ($activities as $actCode => $actTitle) {
                ClassificationNode::query()->create([
                    'mosque_id' => $mosque->id,
                    'parent_id' => $fungsi->id,
                    'level' => 'aktiviti',
                    'code' => $actCode,
                    'title' => $actTitle,
                    'default_sensitivity' => $sensitivity,
                    'is_active' => true,
                    'sort' => $sort++,
                ]);
            }
        }
    }
}
