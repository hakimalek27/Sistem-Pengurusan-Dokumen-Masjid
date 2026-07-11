<?php

namespace App\Services;

use App\Enums\OcrStatus;
use App\Enums\RecordStatus;
use App\Enums\Sensitivity;
use App\Enums\SourceChannel;
use App\Jobs\ProcessOcrJob;
use App\Models\Mosque;
use App\Models\Record;
use App\Models\RegistryFile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * §9.C.3 / §10 / §11 — Kemasukan dokumen ke Peti Masuk + klasifikasi.
 * Dedup sha256 BERSKOP masjid (§5.14): muat naik ditanda, e-mel/webhook diskip.
 */
class InboxIngestService
{
    public function __construct(protected RecordNumberingService $numbering) {}

    /** Adakah sha256 ini sudah wujud dalam masjid (§5.14)? */
    public function hasDuplicate(Mosque $mosque, string $sha256, ?int $exceptRecordId = null): bool
    {
        return Record::query()
            ->withoutGlobalScope('mosque')
            ->where('mosque_id', $mosque->id)
            ->where('sha256', $sha256)
            ->when($exceptRecordId, fn ($q) => $q->where('id', '!=', $exceptRecordId))
            ->exists();
    }

    /**
     * Terima satu dokumen ke Peti Masuk.
     * $skipIfDuplicate = true untuk e-mel/webhook (skip senyap); false untuk muat naik (cipta + tanda).
     * Pulangkan Record, atau null jika diskip kerana duplikat.
     */
    public function ingest(
        Mosque $mosque,
        string $contents,
        string $filename,
        string $mime,
        ?User $creator,
        SourceChannel $source,
        array $sourceMeta = [],
        bool $skipIfDuplicate = false,
    ): ?Record {
        $sha256 = hash('sha256', $contents);

        if ($skipIfDuplicate && $this->hasDuplicate($mosque, $sha256)) {
            return null; // e-mel/webhook: skip senyap (log duplikat oleh pemanggil)
        }

        $record = DB::transaction(function () use ($mosque, $contents, $filename, $mime, $creator, $source, $sourceMeta, $sha256) {
            $record = Record::query()->create([
                'mosque_id' => $mosque->id,
                'record_type' => $this->guessType($source, $filename),
                'title' => $sourceMeta['subject'] ?? pathinfo($filename, PATHINFO_FILENAME),
                'record_date' => now()->toDateString(),
                'sensitivity' => Sensitivity::Dalaman,
                'status' => RecordStatus::PetiMasuk,
                'ocr_status' => OcrStatus::Belum,
                'sha256' => $sha256,
                'source_channel' => $source,
                'source_meta' => $sourceMeta,
                'created_by' => $creator?->id,
            ]);

            $safeName = Str::slug(pathinfo($filename, PATHINFO_FILENAME)).'.'.pathinfo($filename, PATHINFO_EXTENSION);

            $record->addMediaFromString($contents)
                ->usingFileName($safeName)
                ->withCustomProperties(['sha256' => $sha256, 'mime' => $mime])
                ->toMediaCollection('original');

            return $record;
        });

        // §12 — Hantar OCR ke queue 'ocr' (no-op jika tooling tiada; imej Docker sahaja).
        ProcessOcrJob::dispatch($record->id, $mosque->id)->onQueue('ocr');

        return $record;
    }

    /** Penanda duplikat untuk paparan Peti Masuk (§9.C.3). */
    public function isFlaggedDuplicate(Record $record): bool
    {
        return $record->sha256
            && $this->hasDuplicate($record->mosque, $record->sha256, $record->id);
    }

    /**
     * §9.C.3 langkah 3 — Failkan rekod ke dalam fail registri.
     * Peruntuk enclosure_no, status difailkan, waris sensitiviti max(pilihan, fail), filed_by/at.
     */
    public function fileRecord(Record $record, RegistryFile $file, array $attributes = [], ?User $filer = null, ?Sensitivity $chosen = null): Record
    {
        return DB::transaction(function () use ($record, $file, $attributes, $filer, $chosen) {
            $enclosureNo = $this->numbering->allocateEnclosureNo($file);

            $chosen ??= $record->sensitivity ?? Sensitivity::Dalaman;
            $fileSensitivity = $file->sensitivity ?? Sensitivity::Dalaman;
            $effective = Sensitivity::max($chosen, $fileSensitivity);

            $record->fill(array_merge($attributes, [
                'registry_file_id' => $file->id,
                'enclosure_no' => $enclosureNo,
                'sensitivity' => $effective,
                'status' => RecordStatus::Difailkan,
                'filed_by' => $filer?->id,
                'filed_at' => now(),
            ]));
            $record->save();

            // §16.3 — kira tarikh cukup tempoh apabila difailkan.
            app(RetentionEngine::class)->refreshForRecord($record);

            $record->searchable();

            return $record;
        });
    }

    /** §10.C — Pindah rekod ke fail lain (kerani/admin; sebab wajib). */
    public function moveToFile(Record $record, RegistryFile $target, string $reason, ?User $mover = null): Record
    {
        return DB::transaction(function () use ($record, $target, $reason, $mover) {
            $enclosureNo = $this->numbering->allocateEnclosureNo($target);

            activity()
                ->performedOn($record)
                ->causedBy($mover)
                ->withProperties(['reason' => $reason, 'from_file' => $record->registry_file_id, 'to_file' => $target->id, 'ip' => request()->ip()])
                ->log('pindah_fail');

            $record->update([
                'registry_file_id' => $target->id,
                'enclosure_no' => $enclosureNo,
            ]);
            $record->searchable();

            return $record;
        });
    }

    /** §9.C.4 / §10.D — Ganti Versi: rekod baharu, lama status=diganti, pautan dua hala. */
    public function supersede(Record $old, string $contents, string $filename, string $mime, ?User $user = null): Record
    {
        return DB::transaction(function () use ($old, $contents, $filename, $mime, $user) {
            $sha256 = hash('sha256', $contents);

            $new = $old->replicate(['ulid', 'superseded_by_record_id', 'created_at', 'updated_at']);
            $new->status = RecordStatus::Difailkan;
            $new->sha256 = $sha256;
            $new->created_by = $user?->id;
            $new->filed_by = $user?->id;
            $new->filed_at = now();
            $new->save(); // ulid dijana automatik

            $safeName = Str::slug(pathinfo($filename, PATHINFO_FILENAME)).'.'.pathinfo($filename, PATHINFO_EXTENSION);
            $new->addMediaFromString($contents)
                ->usingFileName($safeName)
                ->withCustomProperties(['sha256' => $sha256, 'mime' => $mime])
                ->toMediaCollection('original');

            $old->update([
                'status' => RecordStatus::Diganti,
                'superseded_by_record_id' => $new->id,
            ]);

            $new->searchable();
            $old->searchable();

            return $new;
        });
    }

    protected function guessType(SourceChannel $source, string $filename): string
    {
        return match ($source) {
            SourceChannel::Emel => 'emel_muatnaik',
            default => 'surat_menyurat',
        };
    }
}
