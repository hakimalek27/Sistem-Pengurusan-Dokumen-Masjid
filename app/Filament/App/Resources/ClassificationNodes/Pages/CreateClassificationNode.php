<?php

namespace App\Filament\App\Resources\ClassificationNodes\Pages;

use App\Filament\App\Resources\ClassificationNodes\ClassificationNodeResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateClassificationNode extends CreateRecord
{
    protected static string $resource = ClassificationNodeResource::class;

    /**
     * F6-W1 (§7.2) — sasaran tour untuk langkah akhir "Cipta dan semak nod pada senarai".
     *
     * `parent::getCreateFormAction()` dikekalkan dan hanya dihias: memanggil `->action()`
     * atau `->submit()` semula akan memutuskan callback simpan vendor (pelajaran F4).
     */
    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->extraAttributes(['data-help-target' => 'classnode-submit']);
    }
}
