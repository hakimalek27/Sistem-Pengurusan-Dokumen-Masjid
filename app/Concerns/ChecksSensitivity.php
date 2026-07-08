<?php

namespace App\Concerns;

use App\Enums\Sensitivity;
use App\Models\Record;
use App\Models\RegistryFile;
use App\Models\User;

/**
 * §6.3 — Dasar sensitiviti (SATU sumber kebenaran; dipanggil Policies & SearchService).
 * Rekod mewarisi max(sensitiviti rekod, sensitiviti fail).
 */
trait ChecksSensitivity
{
    /** Sensitiviti efektif rekod = max(rekod, fail). */
    public function effectiveSensitivity(Record $record): Sensitivity
    {
        $recordSensitivity = $record->sensitivity ?? Sensitivity::Dalaman;
        $fileSensitivity = $record->registryFile?->sensitivity;

        return $fileSensitivity
            ? Sensitivity::max($recordSensitivity, $fileSensitivity)
            : $recordSensitivity;
    }

    /** Bolehkah $user melihat/muat turun rekod ini mengikut sensitiviti (§6.3)? */
    public function canSeeSensitivity(User $user, Record $record): bool
    {
        if ($user->is_superadmin) {
            return true;
        }

        $mosque = $record->mosque;
        $role = $user->roleIn($mosque);

        if ($role === null) {
            return false; // bukan ahli masjid
        }

        $effective = $this->effectiveSensitivity($record);

        // umum & dalaman → semua ahli.
        if ($effective !== Sensitivity::Sulit) {
            return true;
        }

        // sulit → senarai peranan istimewa.
        if (in_array($role, ['admin_masjid', 'kerani', 'pengerusi', 'setiausaha', 'nazir'], true)) {
            return true;
        }

        // bendahari → hanya fail klasifikasi 200/300.
        if ($role === 'bendahari') {
            return $this->fileHasPrefix($record->registryFile, ['200', '300']);
        }

        // individu dalam file_access_grants fail berkenaan.
        if ($record->registryFile) {
            return $record->registryFile->accessGrants()->where('user_id', $user->id)->exists();
        }

        return false;
    }

    /** Adakah fail berada di bawah salah satu prefix klasifikasi? */
    protected function fileHasPrefix(?RegistryFile $file, array $prefixes): bool
    {
        if (! $file) {
            return false;
        }

        $code = $file->classificationNode?->code;
        $prefix = $code ? substr($code, 0, 3) : null;

        return in_array($prefix, $prefixes, true);
    }
}
