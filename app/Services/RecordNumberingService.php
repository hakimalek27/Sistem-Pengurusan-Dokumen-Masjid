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
            // Kunci satu baris nod supaya pembukaan fail bagi nod sama disiri pada
            // PostgreSQL. Aggregate MAX() sendiri tidak boleh menggunakan FOR UPDATE.
            $lockedNode = ClassificationNode::query()
                ->withoutGlobalScope('mosque')
                ->whereKey($node->id)
                ->lockForUpdate()
                ->firstOrFail();

            $txn = ((int) RegistryFile::query()
                ->withoutGlobalScope('mosque')
                ->where('mosque_id', $mosque->id)
                ->where('classification_node_id', $node->id)
                ->max('transaction_no')) + 1;

            $file = RegistryFile::query()->create([
                'mosque_id' => $mosque->id,
                'classification_node_id' => $node->id,
                'transaction_no' => $txn,
                'volume' => 1,
                'file_no' => $this->formatFileNo($mosque->code, $node->code, $txn, 1),
                'title' => $title,
                'sensitivity' => $lockedNode->default_sensitivity,
                'status' => 'terbuka',
                'enclosure_count' => 0,
                'opened_at' => now(),
                'created_by' => $userId,
            ]);

            $actor = $userId ? User::query()->find($userId) : null;
            app(MosqueActivityLogger::class)->log(
                $mosque,
                'file_opened',
                ($actor?->name ?? 'Sistem').' membuka fail '.$file->file_no.' ('.$file->title.').',
                $actor,
                $file,
                file: $file,
                metadata: ['classification_code' => $lockedNode->code, 'volume' => 1],
            );

            return $file;
        });
    }

    /** Buka jilid baharu (Aliran F) — fail lama ditutup, jilid+1 dengan transaksi sama. */
    public function openNextVolume(RegistryFile $file, ?int $userId = null): RegistryFile
    {
        if ($userId && ! User::query()->findOrFail($userId)->canIn($file->mosque, 'files.open')) {
            throw new AuthorizationException('Tiada kebenaran membuka jilid baharu.');
        }

        return DB::transaction(function () use ($file, $userId) {
            $locked = RegistryFile::query()
                ->withoutGlobalScope('mosque')
                ->whereKey($file->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $locked->isOpen()) {
                throw ValidationException::withMessages(['file' => 'Hanya fail terbuka boleh dibuka jilid baharu.']);
            }

            $locked->loadMissing(['classificationNode', 'mosque']);
            $newVolume = $locked->volume + 1;

            $locked->update([
                'status' => 'tutup',
                'closed_at' => now(),
                'closed_reason' => 'Jilid penuh',
            ]);

            $newFile = RegistryFile::query()->create([
                'mosque_id' => $locked->mosque_id,
                'classification_node_id' => $locked->classification_node_id,
                'transaction_no' => $locked->transaction_no,
                'volume' => $newVolume,
                'file_no' => $this->formatFileNo($locked->mosque->code, $locked->classificationNode->code, $locked->transaction_no, $newVolume),
                'title' => $locked->title,
                'sensitivity' => $locked->sensitivity,
                'status' => 'terbuka',
                'enclosure_count' => 0,
                'opened_at' => now(),
                'created_by' => $userId,
            ]);

            $actor = $userId ? User::query()->find($userId) : null;
            $logger = app(MosqueActivityLogger::class);
            $logger->log(
                $locked->mosque,
                'file_closed',
                ($actor?->name ?? 'Sistem').' menutup fail '.$locked->file_no.' kerana jilid penuh.',
                $actor,
                $locked,
                file: $locked,
                metadata: ['reason' => 'Jilid penuh', 'next_file_id' => $newFile->id],
            );
            $logger->log(
                $locked->mosque,
                'file_volume_opened',
                ($actor?->name ?? 'Sistem').' membuka jilid baharu '.$newFile->file_no.' untuk fail '.$newFile->title.'.',
                $actor,
                $newFile,
                file: $newFile,
                metadata: ['previous_file_id' => $locked->id, 'volume' => $newVolume],
            );

            return $newFile;
        });
    }

    /** Tutup fail terbuka dengan sebab yang direkodkan. */
    public function closeFile(RegistryFile $file, string $reason, User $actor): RegistryFile
    {
        if (! $actor->can('close', $file) || trim($reason) === '') {
            throw new AuthorizationException('Tiada kebenaran menutup fail atau sebab tidak lengkap.');
        }

        return DB::transaction(function () use ($file, $reason, $actor): RegistryFile {
            $locked = RegistryFile::query()->withoutGlobalScope('mosque')->lockForUpdate()->findOrFail($file->id);
            if (! $locked->isOpen()) {
                throw ValidationException::withMessages(['file' => 'Fail ini telah ditutup.']);
            }

            $locked->update([
                'status' => 'tutup',
                'closed_at' => now(),
                'closed_reason' => trim($reason),
            ]);

            app(MosqueActivityLogger::class)->log(
                $locked->mosque,
                'file_closed',
                $actor->name.' menutup fail '.$locked->file_no.' ('.$locked->title.').',
                $actor,
                $locked,
                file: $locked,
                metadata: ['reason' => trim($reason)],
            );

            return $locked;
        });
    }

    /** Peruntuk enclosure_no berikutnya (§5.15) dengan lockForUpdate baris fail. */
    public function allocateEnclosureNo(RegistryFile $file): int
    {
        return DB::transaction(function () use ($file) {
            $locked = RegistryFile::query()
                ->withoutGlobalScope('mosque')
                ->whereKey($file->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $locked->isOpen()) {
                throw ValidationException::withMessages(['file' => 'Fail telah ditutup dan tidak menerima kandungan baharu.']);
            }

            $next = (int) $locked->enclosure_count + 1;
            $locked->update(['enclosure_count' => $next]);

            return $next;
        });
    }
}
