<?php

namespace App\Support;

use PhpOffice\PhpSpreadsheet\IOFactory as SpreadsheetIO;
use PhpOffice\PhpWord\Element\Table as WordTable;
use PhpOffice\PhpWord\IOFactory as WordIO;
use PhpOffice\PhpWord\PhpWord;
use XMLReader;
use ZipArchive;

/**
 * Ekstrak teks kandungan fail Office (§20 Fasa 2 — ekstrak teks docx/xlsx/pptx).
 *
 * Membolehkan kandungan dokumen Office DICARI (diindeks ke ocr_text), bukan hanya
 * metadata/tajuk. Strategi: PhpOffice sebagai utama (docx/xlsx/xls/doc), dengan
 * SANDARAN native ZipArchive+XMLReader apabila PhpOffice gagal atau fail terlalu
 * besar (jimat RAM worker). pptx menggunakan native terus (reader PhpPresentation
 * tidak dipasang). Format lama binari (.doc/.xls/.ppt 97-2003) = cubaan terbaik;
 * gagal → null (rekod kekal sah, metadata sahaja).
 *
 * API: extract() pulangkan teks ternormal (had ocr_text_limit) atau null bila tidak
 * dapat diekstrak. null menyebabkan ProcessOcrJob kekal tingkah laku lama (ocr_text null).
 */
class OfficeTextExtractor
{
    /** Fail xlsx lebih besar daripada ini dilangkau PhpSpreadsheet → native streaming (RAM). */
    protected const XLSX_NATIVE_THRESHOLD_BYTES = 8 * 1024 * 1024;

    /**
     * Ekstrak teks daripada fail Office di laluan tempatan.
     *
     * @param  string  $path  laluan fail tempatan (sudah dimuat turun daripada storan)
     * @param  string  $ext  sambungan fail huruf kecil (docx/xlsx/pptx/doc/xls/ppt)
     * @return string|null teks ternormal, atau null jika tidak dapat diekstrak
     */
    public static function extract(string $path, string $ext): ?string
    {
        if (! is_file($path)) {
            return null;
        }

        $text = match (mb_strtolower(trim($ext))) {
            'docx' => static::fromDocx($path),
            'xlsx' => static::fromXlsx($path),
            'pptx' => static::fromPptx($path),
            'doc' => static::fromBinaryWord($path),
            'xls' => static::fromBinarySpreadsheet($path),
            default => null, // ppt (97-2003) tiada reader → metadata sahaja
        };

        if ($text === null) {
            return null;
        }

        $text = static::normalizeText($text);

        if ($text === '') {
            return null;
        }

        return mb_substr($text, 0, (int) config('diwan.ocr_text_limit', 1_000_000));
    }

    /**
     * Penormal teks selepas ekstrak (Office & sidecar OCR) — §12′ naik taraf OCR.
     *
     * (1) buang aksara kawalan kecuali baris/tab; (2) sambung suku kata terpotong
     * hujung baris (mis. "per-\nkara" → "perkara"); (3) kemas whitespace hujung baris
     * + hadkan baris kosong berturut kepada satu; (4) trim.
     */
    public static function normalizeText(string $text): string
    {
        // Normalkan penghujung baris.
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        // Buang aksara kawalan (kekal \n dan \t).
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text) ?? $text;

        // Sambung perkataan terpotong sempang di hujung baris: "maklu-\nmat" → "maklumat".
        $text = preg_replace('/(\p{L})-\n(\p{L}\p{Ll})/u', '$1$2', $text) ?? $text;

        // Kemas whitespace pada setiap baris + mampatkan ruang berganda.
        $lines = array_map(static function (string $line): string {
            $line = preg_replace('/[^\S\n]+/u', ' ', $line) ?? $line;

            return trim($line);
        }, explode("\n", $text));

        // Hadkan baris kosong berturut kepada satu baris kosong.
        $out = [];
        $blank = 0;
        foreach ($lines as $line) {
            if ($line === '') {
                if (++$blank > 1) {
                    continue;
                }
            } else {
                $blank = 0;
            }
            $out[] = $line;
        }

        return trim(implode("\n", $out));
    }

    // ---- docx --------------------------------------------------------------

    protected static function fromDocx(string $path): ?string
    {
        try {
            $doc = WordIO::load($path, 'Word2007');
            $text = static::readPhpWord($doc);
            if (trim($text) !== '') {
                return $text;
            }
        } catch (\Throwable $e) {
            // Jatuh ke sandaran native di bawah.
        }

        return static::nativeOpenXml(
            $path,
            ['word/document.xml', 'word/header1.xml', 'word/header2.xml', 'word/header3.xml', 'word/footer1.xml', 'word/footer2.xml', 'word/footer3.xml'],
            textTags: ['w:t'],
            breakTags: ['w:p', 'w:br'],
            tabTags: ['w:tab'],
        );
    }

    protected static function readPhpWord(PhpWord $doc): string
    {
        $lines = [];
        foreach ($doc->getSections() as $section) {
            static::walkWordElements($section->getElements(), $lines);
            foreach ($section->getHeaders() as $header) {
                static::walkWordElements($header->getElements(), $lines);
            }
            foreach ($section->getFooters() as $footer) {
                static::walkWordElements($footer->getElements(), $lines);
            }
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<int, mixed>  $elements
     * @param  array<int, string>  $lines
     */
    protected static function walkWordElements(array $elements, array &$lines): void
    {
        foreach ($elements as $element) {
            if ($element instanceof WordTable) {
                foreach ($element->getRows() as $row) {
                    $cells = [];
                    foreach ($row->getCells() as $cell) {
                        $cellLines = [];
                        static::walkWordElements($cell->getElements(), $cellLines);
                        $cells[] = implode(' ', $cellLines);
                    }
                    $lines[] = implode("\t", $cells);
                }

                continue;
            }

            if (method_exists($element, 'getText')) {
                $value = $element->getText();
                if (is_string($value) && $value !== '') {
                    $lines[] = $value;

                    continue;
                }
            }

            if (method_exists($element, 'getElements')) {
                static::walkWordElements($element->getElements(), $lines);
            }
        }
    }

    // ---- xlsx --------------------------------------------------------------

    protected static function fromXlsx(string $path): ?string
    {
        // Fail besar → native streaming untuk elak beban RAM worker (768M).
        if ((int) @filesize($path) <= static::XLSX_NATIVE_THRESHOLD_BYTES) {
            try {
                $reader = SpreadsheetIO::createReader('Xlsx');
                $reader->setReadDataOnly(true);
                $spreadsheet = $reader->load($path);

                $out = [];
                foreach ($spreadsheet->getAllSheets() as $sheet) {
                    $out[] = '== '.$sheet->getTitle().' ==';
                    foreach ($sheet->toArray(null, true, false, false) as $row) {
                        $cells = array_map(static fn ($cell) => trim((string) $cell), $row);
                        $line = trim(implode("\t", $cells));
                        if ($line !== '') {
                            $out[] = $line;
                        }
                    }
                }
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet);

                $joined = trim(implode("\n", $out));
                if ($joined !== '') {
                    return $joined;
                }
            } catch (\Throwable $e) {
                // Jatuh ke sandaran native.
            }
        }

        // Sandaran native: sharedStrings menangkap majoriti teks boleh cari.
        return static::nativeOpenXml($path, ['xl/sharedStrings.xml'], textTags: ['t'], breakTags: ['si'], tabTags: []);
    }

    // ---- pptx --------------------------------------------------------------

    protected static function fromPptx(string $path): ?string
    {
        $slides = static::zipEntries($path, '#^ppt/slides/slide\d+\.xml$#');
        if ($slides === []) {
            return null;
        }

        $out = [];
        foreach ($slides as $index => $entry) {
            $xml = static::zipRead($path, $entry);
            if ($xml === null) {
                continue;
            }
            $text = static::collectXmlText($xml, textTags: ['a:t'], breakTags: ['a:p', 'a:br'], tabTags: []);
            if (trim($text) !== '') {
                $out[] = '== Slaid '.($index + 1).' ==';
                $out[] = $text;
            }
        }

        $joined = trim(implode("\n", $out));

        return $joined === '' ? null : $joined;
    }

    // ---- binari lama (cubaan terbaik) --------------------------------------

    protected static function fromBinaryWord(string $path): ?string
    {
        try {
            $doc = WordIO::load($path, 'MsDoc');
            $text = static::readPhpWord($doc);

            return trim($text) === '' ? null : $text;
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected static function fromBinarySpreadsheet(string $path): ?string
    {
        try {
            $reader = SpreadsheetIO::createReader('Xls');
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($path);

            $out = [];
            foreach ($spreadsheet->getAllSheets() as $sheet) {
                $out[] = '== '.$sheet->getTitle().' ==';
                foreach ($sheet->toArray(null, true, false, false) as $row) {
                    $line = trim(implode("\t", array_map(static fn ($cell) => trim((string) $cell), $row)));
                    if ($line !== '') {
                        $out[] = $line;
                    }
                }
            }
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);

            $joined = trim(implode("\n", $out));

            return $joined === '' ? null : $joined;
        } catch (\Throwable $e) {
            return null;
        }
    }

    // ---- primitif native ZIP + XML -----------------------------------------

    /**
     * Kumpul teks daripada senarai entri XML dalam fail OOXML (ZIP).
     *
     * @param  list<string>  $entries
     * @param  list<string>  $textTags
     * @param  list<string>  $breakTags
     * @param  list<string>  $tabTags
     */
    protected static function nativeOpenXml(string $path, array $entries, array $textTags, array $breakTags, array $tabTags): ?string
    {
        $chunks = [];
        foreach ($entries as $entry) {
            $xml = static::zipRead($path, $entry);
            if ($xml === null || $xml === '') {
                continue;
            }
            $text = static::collectXmlText($xml, $textTags, $breakTags, $tabTags);
            if (trim($text) !== '') {
                $chunks[] = $text;
            }
        }

        $joined = trim(implode("\n", $chunks));

        return $joined === '' ? null : $joined;
    }

    /**
     * @param  list<string>  $textTags  nama tag yang teksnya dikumpul (mis. w:t, a:t, t)
     * @param  list<string>  $breakTags  nama tag yang menandakan pemisah baris (mis. w:p)
     * @param  list<string>  $tabTags  nama tag yang menandakan tab/ruang (mis. w:tab)
     */
    protected static function collectXmlText(string $xml, array $textTags, array $breakTags, array $tabTags): string
    {
        $reader = new XMLReader;
        if (@$reader->XML($xml) === false) {
            return '';
        }

        $out = '';
        try {
            while (@$reader->read()) {
                if ($reader->nodeType !== XMLReader::ELEMENT) {
                    continue;
                }
                $name = $reader->name;
                if (in_array($name, $breakTags, true)) {
                    $out .= "\n";
                }
                if (in_array($name, $tabTags, true)) {
                    $out .= ' ';
                }
                if (in_array($name, $textTags, true)) {
                    $out .= $reader->readString();
                }
            }
        } catch (\Throwable $e) {
            // Pulangkan apa yang sempat dikumpul.
        } finally {
            $reader->close();
        }

        return $out;
    }

    /** Baca satu entri daripada arkib ZIP; null jika tiada / gagal. */
    protected static function zipRead(string $path, string $entry): ?string
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            return null;
        }

        try {
            $contents = $zip->getFromName($entry);

            return $contents === false ? null : $contents;
        } finally {
            $zip->close();
        }
    }

    /**
     * Senarai nama entri ZIP yang padan corak, disusun secara semula jadi.
     *
     * @return list<string>
     */
    protected static function zipEntries(string $path, string $pattern): array
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            return [];
        }

        $entries = [];
        try {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (is_string($name) && preg_match($pattern, $name)) {
                    $entries[] = $name;
                }
            }
        } finally {
            $zip->close();
        }

        natsort($entries);

        return array_values($entries);
    }
}
