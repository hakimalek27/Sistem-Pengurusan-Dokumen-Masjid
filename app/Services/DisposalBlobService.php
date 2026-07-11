<?php

namespace App\Services;

use App\Models\Record;

class DisposalBlobService
{
    /**
     * Idempotent: media yang sudah dipadam tidak muncul semula; retry meneruskan baki.
     */
    public function deleteRecordMedia(Record $record): void
    {
        foreach (['original', 'derived', 'attachments'] as $collection) {
            foreach ($record->getMedia($collection) as $media) {
                $media->delete();
            }
        }
    }
}
