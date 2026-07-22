<?php

namespace App\Services;

use App\Enums\RecordStatus;
use App\Jobs\DeleteDriveFileJob;
use App\Models\DisposalBatch;
use App\Models\DisposalItem;
use App\Models\Mosque;
use App\Models\Record;
use App\Models\User;
use App\Services\GoogleDrive\DriveConfig;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * §16 / Aliran G & L — Pelupusan (manual & automatik). Metadata snapshot KEKAL selamanya.
 */
class DisposalService
{
    public function __construct(
        protected RetentionEngine $engine,
        protected DisposalBlobService $blobs,
    ) {}

    /** §16.3 / Aliran L — Pelupusan AUTOMATIK untuk masjid (hanya rekod layak §16.3). */
    public function executeAuto(Mosque $mosque): ?DisposalBatch
    {
        $recoverable = DisposalBatch::query()->withoutGlobalScope('mosque')
            ->where('mosque_id', $mosque->id)
            ->where('kind', 'auto')
            ->whereIn('status', ['memproses', 'gagal'])
            ->latest('id')
            ->first();

        if ($recoverable) {
            $records = Record::query()->withoutGlobalScope('mosque')
                ->where('mosque_id', $mosque->id)
                ->whereIn('id', $recoverable->items()->pluck('record_id'))
                ->get();

            return $this->executeBatch($mosque, $records, 'auto', null, $recoverable);
        }

        $records = new Collection;

        Record::query()->withoutGlobalScope('mosque')
            ->where('mosque_id', $mosque->id)
            ->where('status', 'difailkan')
            ->whereNotNull('retention_due_at')
            ->cursor()
            ->each(function (Record $record) use ($mosque, $records) {
                if ($this->engine->isEligibleForAutoDisposal($record, $mosque)) {
                    $records->push($record);
                }
            });

        if ($records->isEmpty()) {
            return null;
        }

        return $this->executeBatch($mosque, $records, 'auto', null);
    }

    /** §10.G — Sedia batch manual (kerani/admin): draf menunggu kelulusan. */
    public function prepareManual(Mosque $mosque, array $recordIds, User $creator): DisposalBatch
    {
        if (! $creator->canIn($mosque, 'disposal.prepare')) {
            throw new AuthorizationException('Tiada kebenaran menyediakan pelupusan.');
        }

        $ids = collect($recordIds)->map(fn ($id) => (int) $id)->unique()->values();
        $records = Record::query()->withoutGlobalScope('mosque')
            ->where('mosque_id', $mosque->id)
            ->whereIn('id', $ids)
            ->where('status', 'difailkan')
            ->where('legal_hold', false)
            ->get();

        if ($ids->isEmpty() || $records->count() !== $ids->count()) {
            throw ValidationException::withMessages(['records' => 'Senarai mengandungi rekod tidak sah, tenant lain, berpegangan atau bukan berstatus difailkan.']);
        }

        foreach ($records as $record) {
            if (! $creator->can('view', $record)) {
                throw new AuthorizationException('Rekod pelupusan mengandungi dokumen yang tidak boleh dilihat.');
            }
        }

        return DB::transaction(function () use ($mosque, $creator, $records) {
            $batch = DisposalBatch::query()->create([
                'mosque_id' => $mosque->id,
                'kind' => 'manual',
                'created_by' => $creator->id,
                'status' => 'menunggu_kelulusan',
            ]);

            foreach ($records as $record) {
                DisposalItem::query()->create([
                    'batch_id' => $batch->id,
                    'record_id' => $record->id,
                    'metadata_snapshot' => $this->snapshot($record),
                    'state' => 'snapshotted',
                ]);
            }

            foreach ($records as $record) {
                app(MosqueActivityLogger::class)->log(
                    $mosque,
                    'disposal_requested',
                    $creator->name.' menyediakan rekod "'.$record->title.'" untuk pelupusan; menunggu kelulusan Pengerusi.',
                    $creator,
                    $batch,
                    $record,
                    metadata: ['batch_id' => $batch->id, 'status' => 'menunggu_kelulusan'],
                );
            }

            return $batch;
        });
    }

    /** §10.G — Kelulusan batch manual (pengerusi). */
    public function approveManual(DisposalBatch $batch, User $approver): void
    {
        DB::transaction(function () use ($batch, $approver): void {
            $locked = DisposalBatch::query()->withoutGlobalScope('mosque')->lockForUpdate()->findOrFail($batch->id);

            if (! $approver->canIn($locked->mosque, 'disposal.approve')) {
                throw new AuthorizationException('Tiada kebenaran meluluskan pelupusan.');
            }

            if ($locked->status !== 'menunggu_kelulusan' || $locked->created_by === $approver->id) {
                throw ValidationException::withMessages(['batch' => 'Batch tidak boleh diluluskan atau pemohon tidak boleh meluluskan batch sendiri.']);
            }

            $locked->update(['status' => 'lulus', 'approved_by' => $approver->id]);

            foreach ($locked->items()->get() as $item) {
                $record = Record::query()->withoutGlobalScope('mosque')
                    ->where('mosque_id', $locked->mosque_id)
                    ->find($item->record_id);
                app(MosqueActivityLogger::class)->log(
                    $locked->mosque,
                    'disposal_approved',
                    $approver->name.' meluluskan pelupusan rekod "'.($item->metadata_snapshot['title'] ?? $record?->title ?? 'Rekod').'".',
                    $approver,
                    $locked,
                    $record,
                    metadata: ['batch_id' => $locked->id, 'snapshot' => $item->metadata_snapshot],
                );
            }
        });
    }

    /** §10.G — Laksana batch manual yang diluluskan (admin_masjid). */
    public function executeManual(DisposalBatch $batch, User $executor): DisposalBatch
    {
        $batch = DisposalBatch::query()->withoutGlobalScope('mosque')->findOrFail($batch->id);

        if (! $executor->canIn($batch->mosque, 'disposal.execute')) {
            throw new AuthorizationException('Tiada kebenaran melaksanakan pelupusan.');
        }

        if (! in_array($batch->status, ['lulus', 'memproses', 'gagal'], true)) {
            throw ValidationException::withMessages(['batch' => 'Batch belum diluluskan atau telah selesai.']);
        }

        $records = Record::query()->withoutGlobalScope('mosque')
            ->where('mosque_id', $batch->mosque_id)
            ->whereIn('id', $batch->items()->pluck('record_id'))
            ->whereIn('status', ['difailkan', 'dilupus'])
            ->get();

        return $this->executeBatch($batch->mosque, $records, 'manual', $executor, $batch);
    }

    /** Laksanakan pelupusan sekumpulan rekod → snapshot → padam blob → batu nisan → sijil. */
    public function executeBatch(Mosque $mosque, Collection $records, string $kind, ?User $executor, ?DisposalBatch $existing = null): DisposalBatch
    {
        $batch = DB::transaction(function () use ($mosque, $records, $kind, $executor, $existing) {
            $batch = $existing
                ? DisposalBatch::query()->withoutGlobalScope('mosque')->lockForUpdate()->findOrFail($existing->id)
                : DisposalBatch::query()->create([
                    'mosque_id' => $mosque->id,
                    'kind' => $kind,
                    'created_by' => $executor?->id,
                ]);

            if ($batch->mosque_id !== $mosque->id || $batch->kind !== $kind) {
                throw ValidationException::withMessages(['batch' => 'Batch tidak sepadan dengan tenant atau jenis pelupusan.']);
            }

            if ($batch->status === 'selesai') {
                return $batch;
            }

            $batch->update([
                'status' => 'memproses',
                'execution_started_at' => $batch->execution_started_at ?? now(),
                'failure_reason' => null,
            ]);

            foreach ($records as $record) {
                if ($record->mosque_id !== $mosque->id) {
                    throw ValidationException::withMessages(['records' => 'Rekod tenant lain dikesan dalam batch pelupusan.']);
                }

                $item = DisposalItem::query()->firstOrCreate(
                    ['batch_id' => $batch->id, 'record_id' => $record->id],
                    ['metadata_snapshot' => $this->snapshot($record), 'state' => 'snapshotted', 'error' => null],
                );

                if ($item->state === 'pending') {
                    $item->update(['metadata_snapshot' => $this->snapshot($record), 'state' => 'snapshotted', 'error' => null]);
                }
            }

            return $batch;
        });

        if ($batch->status === 'selesai') {
            return $batch;
        }

        try {
            $driveIds = [];

            foreach ($batch->items()->orderBy('id')->get() as $item) {
                if ($item->state === 'finalized') {
                    continue;
                }

                $record = Record::query()->withoutGlobalScope('mosque')
                    ->where('mosque_id', $mosque->id)
                    ->findOrFail($item->record_id);

                // §4.6′ — kumpul id Drive SEBELUM padam (untuk hapus salinan mirror).
                if ($record->gdrive_file_id) {
                    $driveIds[] = $record->gdrive_file_id;
                }
                foreach ((array) data_get($record->gdrive_meta, 'attachments', []) as $attId) {
                    $driveIds[] = $attId;
                }

                $this->blobs->deleteRecordMedia($record);
                $item->update(['state' => 'blobs_deleted', 'error' => null]);

                DB::transaction(function () use ($record, $item): void {
                    $locked = Record::query()->withoutGlobalScope('mosque')->lockForUpdate()->findOrFail($record->id);
                    $locked->update([
                        'status' => RecordStatus::Dilupus, 'ocr_text' => null,
                        'gdrive_file_id' => null, 'gdrive_meta' => null, 'gdrive_synced_at' => null,
                    ]);
                    $item->update(['state' => 'finalized', 'finalized_at' => now(), 'error' => null]);
                });

                $record->unsearchable();
            }

            $certificatePath = $this->certificate($batch->fresh(), $kind);
            $batch->update([
                'status' => 'selesai',
                'executed_at' => now(),
                'approved_by' => $batch->approved_by ?? $executor?->id,
                'certificate_path' => $certificatePath,
                'failure_reason' => null,
            ]);

            foreach ($batch->items()->get() as $item) {
                $record = Record::query()->withoutGlobalScope('mosque')
                    ->where('mosque_id', $mosque->id)
                    ->find($item->record_id);
                $snapshot = $item->metadata_snapshot ?? [];
                app(MosqueActivityLogger::class)->log(
                    $mosque,
                    'disposal_executed',
                    ($executor?->name ?? 'Sistem').' melaksanakan pelupusan rekod "'.($snapshot['title'] ?? $record?->title ?? 'Rekod').'"; blob dipadam dan metadata batu nisan dikekalkan.',
                    $executor,
                    $batch,
                    $record,
                    metadata: ['batch_id' => $batch->id, 'kind' => $kind, 'certificate_path' => $certificatePath, 'snapshot' => $snapshot],
                );
            }

            // §4.6′ — padam salinan Google Drive rekod dilupus (selaras sijil pelupusan).
            if (! empty($driveIds) && DriveConfig::enabled()) {
                DeleteDriveFileJob::dispatch($mosque->id, array_values(array_unique($driveIds)))->onQueue('backup')->afterCommit();
            }

            return $batch->fresh();
        } catch (Throwable $e) {
            $batch->update(['status' => 'gagal', 'failure_reason' => mb_substr($e->getMessage(), 0, 4000)]);

            throw $e;
        }
    }

    protected function snapshot(Record $record): array
    {
        $record->loadMissing('registryFile');

        return [
            'ulid' => $record->ulid,
            'title' => $record->title,
            'record_type' => $record->record_type,
            'file_no' => $record->registryFile?->file_no,
            'enclosure_no' => $record->enclosure_no,
            'record_date' => optional($record->record_date)->toDateString(),
            'sensitivity' => $record->sensitivity?->value,
            'our_ref' => $record->our_ref,
            'their_ref' => $record->their_ref,
            'metadata' => $record->metadata,
            'attachments' => $record->getMedia('original')->merge($record->getMedia('attachments'))->map->file_name->values()->all(),
            'disposed_at' => now()->toIso8601String(),
        ];
    }

    protected function certificate(DisposalBatch $batch, string $kind): string
    {
        $batch->loadMissing(['mosque', 'items']);
        $title = $kind === 'auto' ? 'SIJIL PELUPUSAN AUTOMATIK' : 'SIJIL PELUPUSAN';
        $count = $batch->items()->count();

        $rows = '';
        foreach ($batch->items()->get() as $item) {
            $s = $item->metadata_snapshot;
            $rows .= '<tr><td>'.e($s['file_no'] ?? '—').'('.($s['enclosure_no'] ?? '').')</td>'
                .'<td>'.e($s['title'] ?? '—').'</td>'
                .'<td>'.e($s['record_date'] ?? '—').'</td></tr>';
        }

        $html = '<html><body style="font-family:sans-serif;">'
            ."<h2>{$title}</h2>"
            ."<p><strong>Masjid:</strong> {$batch->mosque->name} ({$batch->mosque->code})<br>"
            ."<strong>Batch:</strong> #{$batch->id}<br>"
            .'<strong>Tarikh:</strong> '.now()->format('d/m/Y H:i').'<br>'
            ."<strong>Bilangan rekod:</strong> {$count}</p>"
            .'<table border="1" cellpadding="5" cellspacing="0" width="100%">'
            .'<tr><th align="left">Rujukan</th><th align="left">Tajuk</th><th align="left">Tarikh</th></tr>'
            .$rows
            .'</table>'
            .'<p style="margin-top:16px;font-size:11px;">Blob dokumen telah dipadam kekal. Metadata rekod (batu nisan) '
            .'kekal tersimpan dalam sistem. Dokumen dijana secara automatik oleh Diwan (SPDM).</p>'
            .'</body></html>';

        $path = "tenants/{$batch->mosque_id}/disposal-certs/{$batch->id}.pdf";
        Storage::disk(config('diwan.storage_disk'))->put($path, Pdf::loadHTML($html)->output());

        return $path;
    }
}
