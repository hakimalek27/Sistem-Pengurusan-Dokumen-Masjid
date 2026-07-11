<?php

use App\Enums\OcrStatus;
use App\Jobs\BuildExportZipJob;
use App\Jobs\ProcessOcrJob;
use App\Models\SensitiveAccessLog;
use App\Services\ExportService;
use Illuminate\Support\Facades\Storage;

it('menjadikan log akses sulit append-only pada lapisan model', function () {
    $mosque = makeMosque('MAM', 'mam');
    $user = makeMember($mosque, 'admin_masjid');
    $record = makeRecord($mosque, makeFile($mosque, makeNode($mosque, '200-2', 'sulit'), 'sulit'), 'sulit');
    $log = SensitiveAccessLog::query()->create([
        'mosque_id' => $mosque->id,
        'user_id' => $user->id,
        'record_id' => $record->id,
        'action' => 'view',
    ]);

    expect(fn () => $log->update(['action' => 'download']))
        ->toThrow(LogicException::class, 'tidak boleh diubah');
    expect(fn () => $log->delete())
        ->toThrow(LogicException::class, 'tidak boleh dipadam');
});

it('job eksport menolak id rekod tenant lain', function () {
    $mam = makeMosque('MAM', 'mam');
    $man = makeMosque('MAN', 'man');
    $mamRecord = makeRecord($mam, makeFile($mam, makeNode($mam, '100-4')));
    $manRecord = makeRecord($man, makeFile($man, makeNode($man, '100-4')));

    $export = Mockery::mock(ExportService::class);
    $export->shouldReceive('build')->once()->withArgs(function ($mosque, $records, $label) use ($mam, $mamRecord) {
        return $mosque->is($mam)
            && $records->pluck('id')->all() === [$mamRecord->id]
            && $label === 'ujian';
    })->andReturn('tenants/'.$mam->id.'/exports/ujian.zip');

    (new BuildExportZipJob($mam->id, [$mamRecord->id, $manRecord->id], null, 'ujian'))->handle($export);
});

it('job OCR mengesahkan mosque_id dalam payload', function () {
    Storage::fake(config('diwan.storage_disk'));
    $mam = makeMosque('MAM', 'mam');
    $man = makeMosque('MAN', 'man');
    $record = makeRecord($mam, null, 'dalaman', 'surat_menyurat', ['ocr_status' => OcrStatus::Belum]);
    $record->addMediaFromString('dokumen office')->usingFileName('ujian.docx')->toMediaCollection('original');

    (new ProcessOcrJob($record->id, $man->id))->handle();

    expect($record->fresh()->ocr_status)->toBe(OcrStatus::Belum);
});
