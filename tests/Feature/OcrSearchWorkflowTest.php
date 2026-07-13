<?php

use App\Enums\OcrStatus;
use App\Services\SearchService;

beforeEach(function () {
    config()->set('scout.driver', 'collection');
    $this->mam = makeMosque('MAM', 'mam');
    $this->man = makeMosque('MAN', 'man');
    $this->admin = makeMember($this->mam, 'admin_masjid', 'admin-ocr@mam.test');
    $this->record = makeRecord($this->mam, null, attrs: [
        'title' => 'Minit Mesyuarat PERKIB',
        'ocr_status' => OcrStatus::Siap,
        'ocr_text' => 'SENARAI KLUSTER PENDIDIKAN DAN PENCERAHAN HUKUM',
    ]);
});

it('item peti masuk mempunyai halaman semakan OCR dan deep-link yang hidup', function () {
    $this->actingAs($this->admin)
        ->get('/app/mam/peti-masuk/'.$this->record->id)
        ->assertOk()
        ->assertSee('Teks OCR')
        ->assertSee('SENARAI KLUSTER PENDIDIKAN');

    $this->actingAs($this->admin)
        ->get('/r/'.$this->record->ulid)
        ->assertRedirect('/app/mam/peti-masuk/'.$this->record->id);
});

it('teks OCR peti masuk boleh dicari tetapi tidak bocor ke tenant lain', function () {
    $results = app(SearchService::class)->for($this->admin, $this->mam, 'PENCERAHAN HUKUM');
    $otherTenantAdmin = makeMember($this->man, 'admin_masjid', 'admin-ocr@man.test');
    $foreignResults = app(SearchService::class)->for($otherTenantAdmin, $this->man, 'PENCERAHAN HUKUM');

    expect($results->pluck('id')->all())->toBe([$this->record->id])
        ->and($foreignResults)->toBeEmpty();
});
