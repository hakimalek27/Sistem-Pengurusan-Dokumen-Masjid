<?php

namespace App\Services;

use App\Enums\RecordStatus;
use App\Models\DisposalBatch;
use App\Models\DisposalItem;
use App\Models\Mosque;
use App\Models\Record;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * §16 / Aliran G & L — Pelupusan (manual & automatik). Metadata snapshot KEKAL selamanya.
 */
class DisposalService
{
    public function __construct(protected RetentionEngine $engine) {}

    /** §16.3 / Aliran L — Pelupusan AUTOMATIK untuk masjid (hanya rekod layak §16.3). */
    public function executeAuto(Mosque $mosque): ?DisposalBatch
    {
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

    /** Laksanakan pelupusan sekumpulan rekod → snapshot → padam blob → batu nisan → sijil. */
    public function executeBatch(Mosque $mosque, Collection $records, string $kind, ?User $executor, ?DisposalBatch $existing = null): DisposalBatch
    {
        return DB::transaction(function () use ($mosque, $records, $kind, $executor, $existing) {
            $batch = $existing ?? DisposalBatch::query()->create([
                'mosque_id' => $mosque->id,
                'kind' => $kind,
                'created_by' => $executor?->id,
            ]);

            foreach ($records as $record) {
                // 1) Snapshot PENUH sebelum apa-apa dipadam (§5.12).
                DisposalItem::query()->create([
                    'batch_id' => $batch->id,
                    'record_id' => $record->id,
                    'metadata_snapshot' => $this->snapshot($record),
                ]);

                // 2) Padam SEMUA blob (semua koleksi).
                $record->clearMediaCollection('original');
                $record->clearMediaCollection('derived');
                $record->clearMediaCollection('attachments');

                // 3) Batu nisan: metadata kekal, status dilupus, ocr_text NULL.
                $record->update(['status' => RecordStatus::Dilupus, 'ocr_text' => null]);
                $record->unsearchable();
            }

            $batch->update([
                'status' => 'selesai',
                'executed_at' => now(),
                'approved_by' => $batch->approved_by ?? $executor?->id,
                'certificate_path' => $this->certificate($batch, $kind),
            ]);

            return $batch;
        });
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
