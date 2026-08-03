<?php

namespace App\Concerns;

use App\Enums\RetentionAction;
use App\Models\Record;
use Filament\Actions\Action;
use Filament\Facades\Filament;

/**
 * F4 §5.2 (L1) — brek sedar sebelum menyimpan peraturan retensi `auto_padam`.
 *
 * Audit (RR-08-01/RR-09-01) mendapati auto-padam ialah tingkah laku LALAI melalui tiga
 * lapisan bertindan. Enjin retensi sendiri betul; yang bermasalah ialah betapa mudahnya
 * seseorang boleh menghidupkan pemadaman kekal tanpa menyedarinya. Trait ini menambah
 * pengesahan kedua yang BERMAKLUMAT — ia memberitahu berapa banyak rekod tenant yang
 * berada dalam skop peraturan itu pada masa ini.
 *
 * Had skop yang diisytihar (§5.2): ini brek UI pada laluan borang panel SAHAJA. Laluan
 * bukan-UI (seeder platform, console, ujian) sengaja tidak melaluinya — penguatkuasaan
 * domain kekal pada gate `auto_disposal_enabled` + peraturan retensi itu sendiri.
 */
trait ConfirmsAutoPadamRetention
{
    protected function isAutoPadamSelected(): bool
    {
        return ($this->data['action'] ?? null) === RetentionAction::AutoPadam->value;
    }

    /**
     * Bungkus aksi simpan vendor dengan pengesahan bersyarat.
     *
     * PENTING: pemanggil mesti menghantar `parent::get*FormAction()` dan JANGAN memanggil
     * `->action()`/`->submit()` semula — callback simpan bawaan vendor sudah terpasang di
     * situ, dan menggantikannya akan memutuskan fungsi simpan sepenuhnya.
     */
    protected function confirmAutoPadam(Action $action): Action
    {
        return $action
            ->requiresConfirmation(fn (): bool => $this->isAutoPadamSelected())
            ->modalHeading('Sahkan peraturan pemadaman automatik')
            ->modalDescription(fn (): string => $this->autoPadamImpactSummary())
            ->modalSubmitActionLabel('Saya faham, simpan peraturan');
    }

    /**
     * Ayat impak untuk dialog pengesahan.
     *
     * Kiraan adalah BERMAKLUMAT, bukan penguatkuasaan — nilai boleh berubah antara dialog
     * dan commit, dan itu diterima (§5.2). Ia dibaca daripada `$this->data`, iaitu keadaan
     * borang yang BELUM disimpan, kerana peraturan itu sendiri belum ada dalam DB.
     */
    protected function autoPadamImpactSummary(): string
    {
        $tenant = Filament::getTenant();
        $type = $this->data['record_type'] ?? null;
        $prefix = $this->data['classification_prefix'] ?? null;
        $years = $this->data['retain_years'] ?? null;

        $tempoh = filled($years)
            ? "selepas {$years} tahun"
            : 'tanpa had tempoh yang ditetapkan';

        [$skop, $kiraan] = $this->autoPadamScope($tenant?->getKey(), $type, $prefix);

        return "Peraturan ini membenarkan PEMADAMAN KEKAL automatik {$tempoh} untuk {$skop}. "
            ."Anggaran rekod dalam skop ini pada masa ini: {$kiraan}. "
            .'Pemadaman hanya berlaku jika masjid menghidupkan "Pelupusan automatik" dalam '
            .'Tetapan Masjid, dan selepas notifikasi 90/30/7 hari. Metadata rekod kekal.';
    }

    /**
     * Padanan skop mengikut semantik `RetentionEngine::scoreRule()`: `record_type` diutamakan,
     * kemudian awalan kod klasifikasi, jika tidak peraturan itu menangkap semua rekod.
     *
     * Setiap hop diskop tenant SECARA EKSPLISIT (§0.6 S1) — bukan bergantung pada global
     * scope, supaya kiraan tidak boleh bocor merentas masjid walaupun dipanggil di luar
     * konteks panel.
     *
     * @return array{0: string, 1: int}
     */
    protected function autoPadamScope(?int $mosqueId, ?string $type, ?string $prefix): array
    {
        if ($mosqueId === null) {
            return ['skop yang tidak dapat ditentukan', 0];
        }

        $query = Record::query()->forMosque($mosqueId);

        if (filled($type)) {
            $label = config("record_types.{$type}.label", $type);

            return ["jenis rekod \"{$label}\"", (int) $query->where('record_type', $type)->count()];
        }

        if (filled($prefix)) {
            $query->whereHas('registryFile', function ($q) use ($mosqueId, $prefix) {
                $q->withoutGlobalScope('mosque')
                    ->where('registry_files.mosque_id', $mosqueId)
                    ->whereHas('classificationNode', function ($q2) use ($mosqueId, $prefix) {
                        $q2->withoutGlobalScope('mosque')
                            ->where('classification_nodes.mosque_id', $mosqueId)
                            ->where('code', 'like', $prefix.'%');
                    });
            });

            return ["klasifikasi bermula \"{$prefix}\"", (int) $query->count()];
        }

        return ['SEMUA rekod masjid ini yang tiada peraturan lebih spesifik', (int) $query->count()];
    }
}
