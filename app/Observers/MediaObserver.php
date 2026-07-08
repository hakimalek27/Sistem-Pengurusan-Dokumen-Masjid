<?php

namespace App\Observers;

use App\Models\Mosque;
use App\Models\Record;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * §5.14 — Kira storage_used_bytes secara atomik apabila media dicipta/dipadam.
 * SEMUA media dikira (ambang & penguatkuasaan penuh = Fasa 5).
 */
class MediaObserver
{
    public function created(Media $media): void
    {
        $this->adjust($media, +1);
    }

    public function deleted(Media $media): void
    {
        $this->adjust($media, -1);
    }

    protected function adjust(Media $media, int $sign): void
    {
        $model = $media->model;

        if (! $model instanceof Record || empty($model->mosque_id)) {
            return;
        }

        $size = (int) $media->size;
        $op = $sign > 0 ? '+' : '-';

        // Atomik + portable (SQLite tiada GREATEST). Drift/negatif dibetulkan ReconcileStorageJob (Fasa 5).
        Mosque::query()
            ->whereKey($model->mosque_id)
            ->update([
                'storage_used_bytes' => DB::raw("storage_used_bytes {$op} {$size}"),
            ]);
    }
}
