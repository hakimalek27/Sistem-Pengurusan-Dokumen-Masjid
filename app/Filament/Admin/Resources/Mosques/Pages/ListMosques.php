<?php

namespace App\Filament\Admin\Resources\Mosques\Pages;

use App\Filament\Admin\Resources\Mosques\MosqueResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListMosques extends ListRecords
{
    protected static string $resource = MosqueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('pendaftaranBaharu')
                ->label('Buka Borang Pendaftaran')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->url(url('/daftar'))
                ->openUrlInNewTab(),
        ];
    }
}
