<?php

namespace App\Filament\App\Resources\Records\Pages;

use App\Concerns\ChecksSensitivity;
use App\Enums\Sensitivity;
use App\Filament\App\Resources\Records\RecordResource;
use App\Models\SensitiveAccessLog;
use Filament\Resources\Pages\ViewRecord as BaseViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewRecord extends BaseViewRecord
{
    use ChecksSensitivity;

    protected static string $resource = RecordResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        // §15.4 — Log akses rekod sulit ditulis pada MOUNT (bukan dalam policy).
        $rec = $this->getRecord();

        if ($this->effectiveSensitivity($rec) === Sensitivity::Sulit) {
            SensitiveAccessLog::query()->create([
                'mosque_id' => $rec->mosque_id,
                'is_superadmin' => (bool) Auth::user()?->is_superadmin,
                'user_id' => Auth::id(),
                'record_id' => $rec->id,
                'action' => 'view',
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        }
    }
}
