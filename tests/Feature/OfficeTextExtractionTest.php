<?php

use App\Enums\OcrStatus;
use App\Enums\SourceChannel;
use App\Services\InboxIngestService;
use App\Services\SearchService;
use App\Support\OfficeTextExtractor;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;

/*
 * §20 Fasa 2 — ekstrak teks kandungan Office (docx/xlsx/pptx) supaya boleh dicari.
 * Fail Office dijana sebenar (PhpWord/PhpSpreadsheet/ZipArchive) — tiada fixture binari.
 */

function officeWriteTemp(string $bytes, string $ext): string
{
    $path = tempnam(sys_get_temp_dir(), 'office').'.'.$ext;
    file_put_contents($path, $bytes);

    return $path;
}

function officeMakeDocx(string ...$paragraphs): string
{
    $phpWord = new PhpWord;
    $section = $phpWord->addSection();
    foreach ($paragraphs as $paragraph) {
        $section->addText($paragraph);
    }
    $tmp = tempnam(sys_get_temp_dir(), 'mkdocx').'.docx';
    IOFactory::createWriter($phpWord, 'Word2007')->save($tmp);
    $bytes = (string) file_get_contents($tmp);
    @unlink($tmp);

    return $bytes;
}

function officeMakeXlsx(string $title, array $rows): string
{
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle($title);
    foreach ($rows as $r => $cols) {
        foreach (array_values($cols) as $c => $value) {
            $sheet->setCellValueExplicit([$c + 1, $r + 1], (string) $value, DataType::TYPE_STRING);
        }
    }
    $tmp = tempnam(sys_get_temp_dir(), 'mkxlsx').'.xlsx';
    PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx')->save($tmp);
    $bytes = (string) file_get_contents($tmp);
    @unlink($tmp);
    $spreadsheet->disconnectWorksheets();

    return $bytes;
}

function officeMakePptx(array $slideTexts): string
{
    $tmp = tempnam(sys_get_temp_dir(), 'mkpptx').'.pptx';
    $zip = new ZipArchive;
    $zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $zip->addFromString('[Content_Types].xml', '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"/>');
    foreach (array_values($slideTexts) as $i => $text) {
        $n = $i + 1;
        $xml = '<?xml version="1.0"?><p:sld xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main">'
            .'<p:cSld><p:spTree><a:p><a:r><a:t>'.htmlspecialchars($text, ENT_XML1).'</a:t></a:r></a:p></p:spTree></p:cSld></p:sld>';
        $zip->addFromString("ppt/slides/slide{$n}.xml", $xml);
    }
    $zip->close();
    $bytes = (string) file_get_contents($tmp);
    @unlink($tmp);

    return $bytes;
}

it('ekstrak teks docx (PhpWord) mengandungi kandungan perenggan', function () {
    $path = officeWriteTemp(officeMakeDocx('MESYUARAT AGUNG KARIAH', 'Tindakan susulan bendahari masjid.'), 'docx');
    $text = OfficeTextExtractor::extract($path, 'docx');
    @unlink($path);

    expect($text)->toContain('MESYUARAT AGUNG KARIAH')
        ->and($text)->toContain('bendahari');
});

it('ekstrak teks xlsx (PhpSpreadsheet) mengandungi nama helaian + sel', function () {
    $path = officeWriteTemp(officeMakeXlsx('Kutipan', [['Nama', 'Catatan'], ['Ali Bin Abu', 'Derma Jumaat']]), 'xlsx');
    $text = OfficeTextExtractor::extract($path, 'xlsx');
    @unlink($path);

    expect($text)->toContain('Kutipan')
        ->and($text)->toContain('Ali Bin Abu')
        ->and($text)->toContain('Derma Jumaat');
});

it('ekstrak teks pptx (native XML) mengandungi teks slaid', function () {
    $path = officeWriteTemp(officeMakePptx(['Selamat Datang Jemaah', 'Agenda Program Ramadan']), 'pptx');
    $text = OfficeTextExtractor::extract($path, 'pptx');
    @unlink($path);

    expect($text)->toContain('Selamat Datang Jemaah')
        ->and($text)->toContain('Agenda Program Ramadan');
});

it('fail Office korup → null (gagal-anggun, rekod kekal sah)', function () {
    $path = officeWriteTemp('ini-bukan-fail-office-yang-sah-langsung', 'docx');
    $text = OfficeTextExtractor::extract($path, 'docx');
    @unlink($path);

    expect($text)->toBeNull();
});

it('teks melebihi had ocr_text_limit dipotong', function () {
    config()->set('diwan.ocr_text_limit', 50);
    $path = officeWriteTemp(officeMakeDocx(str_repeat('PANJANGSANGAT ', 300)), 'docx');
    $text = OfficeTextExtractor::extract($path, 'docx');
    @unlink($path);

    expect(mb_strlen((string) $text))->toBeLessThanOrEqual(50)
        ->and($text)->not->toBeNull();
});

it('normalizeText menyambung suku kata terpotong hujung baris + kemas whitespace', function () {
    expect(OfficeTextExtractor::normalizeText("maklu-\nmat   penting"))->toBe('maklumat penting');
});

it('docx yang diingest → OCR siap, teks diindeks, boleh dicari (hujung-ke-hujung)', function () {
    config()->set('scout.driver', 'collection');
    Storage::fake(config('diwan.storage_disk'));
    $mam = makeMosque('MAM', 'mam');
    $admin = makeMember($mam, 'admin_masjid');

    $record = app(InboxIngestService::class)->ingest(
        $mam,
        officeMakeDocx('Perkataan unik ZXQWANGSA dalam dokumen ini'),
        'nota.docx',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        $admin,
        SourceChannel::MuatNaik,
    )->fresh();

    expect($record->ocr_status)->toBe(OcrStatus::Siap)
        ->and($record->ocr_text)->toContain('ZXQWANGSA');

    $results = app(SearchService::class)->for($admin, $mam, 'ZXQWANGSA');
    expect($results->contains('id', $record->id))->toBeTrue();
});
