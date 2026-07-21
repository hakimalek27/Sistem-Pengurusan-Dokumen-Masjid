<?php

namespace App\Filament\App\Resources\RegistryFiles\Pages;

use App\Filament\App\Resources\RegistryFiles\RegistryFileResource;
use App\Models\ClassificationNode;
use App\Services\RecordNumberingService;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreateRegistryFile extends CreateRecord
{
    protected static string $resource = RegistryFileResource::class;

    /** Buka fail menggunakan RecordNumberingService (§5.15) — jana file_no & transaction_no. */
    protected function handleRecordCreation(array $data): Model
    {
        $mosque = Filament::getTenant();

        $node = ClassificationNode::query()
            ->where('mosque_id', $mosque->id)
            ->findOrFail($data['classification_node_id']);

        $file = app(RecordNumberingService::class)->openFile($mosque, $node, $data['title'], Auth::id());
        $file->update(collect($data)->only(['medium', 'physical_reference', 'physical_location'])->all());

        return $file;
    }
}
