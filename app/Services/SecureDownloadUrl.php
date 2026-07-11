<?php

namespace App\Services;

use App\Models\DisposalBatch;
use App\Models\StorageOrder;
use App\Models\StoredExport;
use Illuminate\Support\Facades\URL;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class SecureDownloadUrl
{
    public function media(Media $media, string $disposition = 'inline'): string
    {
        return URL::temporarySignedRoute('secure-file.show', now()->addMinutes(5), [
            'media' => $media->getKey(),
            'disposition' => $disposition,
        ]);
    }

    public function invoice(StorageOrder $order): string
    {
        return URL::temporarySignedRoute('secure-artifact.invoice', now()->addMinutes(5), ['order' => $order->getKey()]);
    }

    public function certificate(DisposalBatch $batch): string
    {
        return URL::temporarySignedRoute('secure-artifact.certificate', now()->addMinutes(5), ['batch' => $batch->getKey()]);
    }

    public function export(StoredExport $export): string
    {
        return URL::temporarySignedRoute('secure-artifact.export', $export->expires_at, ['export' => $export->getKey()]);
    }
}
