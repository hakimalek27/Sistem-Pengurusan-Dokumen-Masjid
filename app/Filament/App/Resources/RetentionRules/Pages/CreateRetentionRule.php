<?php

namespace App\Filament\App\Resources\RetentionRules\Pages;

use App\Concerns\ConfirmsAutoPadamRetention;
use App\Filament\App\Resources\RetentionRules\RetentionRuleResource;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateRetentionRule extends CreateRecord
{
    use ConfirmsAutoPadamRetention;

    protected static string $resource = RetentionRuleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['mosque_id'] = Filament::getTenant()->id;

        return $data;
    }

    /**
     * F4 §5.2 — pengesahan sedar apabila `auto_padam` dipilih. `parent::` dahulu supaya
     * callback simpan vendor kekal terpasang (menggantikannya memutuskan fungsi simpan).
     */
    protected function getCreateFormAction(): Action
    {
        // F6-W1 — sasaran langkah akhir `screen.cipta-peraturan-retensi`. Dihias SELEPAS
        // confirmAutoPadam supaya brek F4 kekal utuh.
        return $this->confirmAutoPadam(parent::getCreateFormAction())
            ->extraAttributes(['data-help-target' => 'retention-submit']);
    }

    /** "Cipta & tambah lagi" ialah laluan simpan KEDUA — brek yang sama diperlukan. */
    protected function getCreateAnotherFormAction(): Action
    {
        return $this->confirmAutoPadam(parent::getCreateAnotherFormAction());
    }
}
