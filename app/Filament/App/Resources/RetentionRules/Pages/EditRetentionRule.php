<?php

namespace App\Filament\App\Resources\RetentionRules\Pages;

use App\Concerns\ConfirmsAutoPadamRetention;
use App\Filament\App\Resources\RetentionRules\RetentionRuleResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;

class EditRetentionRule extends EditRecord
{
    use ConfirmsAutoPadamRetention;

    protected static string $resource = RetentionRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * §15.2 — Kunci tenant secara eksplisit semasa simpan. getEloquentQuery() sudah
     * menghadkan rekod boleh-edit kepada masjid semasa, tetapi paksa mosque_id di sini
     * supaya peraturan retensi tidak boleh dialih ke tenant lain walau payload diusik.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['mosque_id'] = Filament::getTenant()->id;

        return $data;
    }

    /**
     * F4 §5.2 — menukar peraturan sedia ada KEPADA `auto_padam` sama bahayanya seperti
     * menciptanya begitu, jadi laluan simpan ini mendapat brek yang sama.
     */
    protected function getSaveFormAction(): Action
    {
        return $this->confirmAutoPadam(parent::getSaveFormAction());
    }
}
