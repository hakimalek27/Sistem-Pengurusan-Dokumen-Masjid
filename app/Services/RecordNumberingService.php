<?php

namespace App\Services;

use App\Models\ClassificationNode;
use App\Models\Mosque;
use App\Models\RegistryFile;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * §5.15 — Penomboran registri berskop masjid. Dilindungi RecordNumberingTest.
 *  file_no    = "{mosques.code}.{kodNod}/{transaction_no}" (+ " Jld.{n}" jika volume>1)
 *  enclosure  = kaunter per fail; rujukan penuh = "{file_no}({enclosure_no})"
 */
class RecordNumberingService
{
    public function formatFileNo(string $mosqueCode, string $nodeCode, int $transactionNo, int $volume = 1): string
    {
        $base = "{$mosqueCode}.{$nodeCode}/{$transactionNo}";

        return $volume > 1 ? $base." Jld.{$volume}" : $base;
    }

    public function fullReference(RegistryFile $file, int $enclosureNo): string
    {
        return "{$file->file_no}({$enclosureNo})";
    }

    /** Buka fail baharu dengan transaction_no berikutnya (semua jilid = 1 transaksi) berskop masjid. */
    public function openFile(Mosque $mosque, ClassificationNode $node, string $title, ?int $userId = null): RegistryFile
    {
        if ($node->mosque_id !== $mosque->id || ! $node->is_active || $node->isFungsi() || trim($title) === '') {
            throw ValidationException::withMessages(['classification' => 'Nod mesti aktif, bukan peringkat fungsi dan berada dalam tenant sama.']);
        }

        if ($userId && ! User::query()->findOrFail($userId)->canIn($mosque, 'files.open')) {
            throw new AuthorizationException('Tiada kebenaran membuka fail registri.');
        }

        return DB::transaction(function () use ($mosque, $node, $title, $userId) {
            $txn = ((int) RegistryFile::query()
                ->withoutGlobalScope('mosque')
                ->where('mosque_id', $mosque->id)
                ->where('classification_node_id', $node->id)
                ->lockForUpdate()
                ->max('transaction_no')) + 1;

            return RegistryFile::query()->create([
                'mosque_id' => $mosque->id,
                'classification_node_id' => $node->id,
                'transaction_no' => $txn,
                'volume' => 1,
                'file_no' => $this->formatFileNo($mosque->code, $node->code, $txn, 1),
                'title' => $title,
                'sensitivity' => $node->default_sensitivity,
                'status' => 'terbuka',
                'enclosure_count' => 0,
                'opened_at' => now(),
                'created_by' => $userId,
            ]);
        });
    }

    /** Buka jilid baharu (Aliran F) — fail lama ditutup, jilid+1 dengan transaksi sama. */
    public function openNextVolume(RegistryFile $file, ?int $userId = null): RegistryFile
    {
        if (! $file->isOpen()) {
            throw ValidationException::withMessages(['file' => 'Hanya fail terbuka boleh dibuka jilid baharu.']);
        }

        if ($userId && ! User::query()->findOrFail($userId)->canIn($file->mosque, 'files.open')) {
            throw new AuthorizationException('Tiada kebenaran membuka jilid baharu.');
        }

        return DB::transaction(function () use ($file, $userId) {
            $file->loadMissing(['classificationNode', 'mosque']);
            $newVolume = $file->volume + 1;

            $file->update([
                'status' => 'tutup',
                'closed_at' => now(),
                'closed_reason' => 'Jilid penuh',
            ]);

            return RegistryFile::query()->create([
                'mosque_id' => $file->mosque_id,
                'classification_node_id' => $file->classification_node_id,
                'transaction_no' => $file->transaction_no,
                'volume' => $newVolume,
                'file_no' => $this->formatFileNo($file->mosque->code, $file->classificationNode->code, $file->transaction_no, $newVolume),
                'title' => $file->title,
                'sensitivity' => $file->sensitivity,
                'status' => 'terbuka',
                'enclosure_count' => 0,
                'opened_at' => now(),
                'created_by' => $userId,
            ]);
        });
    }

    /** Peruntuk enclosure_no berikutnya (§5.15) dengan lockForUpdate baris fail. */
    public function allocateEnclosureNo(RegistryFile $file): int
    {
        if (! $file->isOpen()) {
            throw ValidationException::withMessages(['file' => 'Fail telah ditutup dan tidak menerima kandungan baharu.']);
        }

        return DB::transaction(function () use ($file) {
            $locked = RegistryFile::query()
                ->withoutGlobalScope('mosque')
                ->whereKey($file->id)
                ->lockForUpdate()
                ->first();

            $next = (int) $locked->enclosure_count + 1;
            $locked->update(['enclosure_count' => $next]);

            return $next;
        });
    }
}
