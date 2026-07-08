<?php

namespace App\Services;

use App\Models\Mosque;
use App\Models\Record;
use App\Models\User;
use App\Notifications\QuotaThresholdNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * §5.14 — Perakaunan & penguatkuasaan storan. Lebih kuota = sekat TULIS sahaja;
 * baca/muat turun sentiasa OK. JANGAN padam data kerana kuota.
 */
class QuotaService
{
    public function effectiveQuota(Mosque $mosque): int
    {
        return $mosque->effectiveQuotaBytes();
    }

    public function usagePercent(Mosque $mosque): float
    {
        $quota = $this->effectiveQuota($mosque);

        return $quota > 0 ? min(100, ((int) $mosque->storage_used_bytes / $quota) * 100) : 100.0;
    }

    public function isFull(Mosque $mosque): bool
    {
        return (int) $mosque->storage_used_bytes >= $this->effectiveQuota($mosque);
    }

    /** Semak pintu tulis (§5.14). Pulangkan false jika sudah melebihi kuota. */
    public function canStore(Mosque $mosque): bool
    {
        return ! $this->isFull($mosque);
    }

    /** §5.14 — Notifikasi ambang 80/90/100% (maks sekali per ambang per bulan). */
    public function checkThresholds(Mosque $mosque): void
    {
        $percent = $this->usagePercent($mosque->fresh());

        foreach ([100, 90, 80] as $threshold) {
            if ($percent >= $threshold) {
                $key = "quota_notified:{$mosque->id}:{$threshold}:".now()->format('Y-m');

                if (! Cache::has($key)) {
                    Cache::put($key, true, now()->addMonth());
                    $this->notifyThreshold($mosque, $threshold);
                }

                break; // hanya ambang tertinggi yang dicapai
            }
        }
    }

    protected function notifyThreshold(Mosque $mosque, int $threshold): void
    {
        $usedGb = round((int) $mosque->storage_used_bytes / (1024 ** 3), 2);
        $quotaGb = round($this->effectiveQuota($mosque) / (1024 ** 3), 2);

        $admins = $mosque->users()->get()->filter(fn (User $u) => $u->canIn($mosque, 'usage.view') || $u->canIn($mosque, 'mosque.settings'));
        $superadmins = User::query()->where('is_superadmin', true)->where('is_active', true)->get();

        $notification = new QuotaThresholdNotification($mosque, $threshold, $usedGb, $quotaGb);
        Notification::send($admins->merge($superadmins)->unique('id'), $notification);
    }

    /** §5.14 — ReconcileStorage: Σ media sebenar vs kaunter; betulkan drift >1MB. */
    public function reconcile(Mosque $mosque): bool
    {
        $recordIds = $mosque->records()->withoutGlobalScope('mosque')->where('mosque_id', $mosque->id)->pluck('id');

        $actual = (int) Media::query()
            ->where('model_type', Record::class)
            ->whereIn('model_id', $recordIds)
            ->sum('size');

        if (abs($actual - (int) $mosque->storage_used_bytes) > 1024 * 1024) {
            DB::table('mosques')->where('id', $mosque->id)->update(['storage_used_bytes' => $actual]);

            return true; // dibetulkan
        }

        return false;
    }
}
