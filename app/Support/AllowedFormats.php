<?php

namespace App\Support;

/**
 * Satu sumber kebenaran untuk format dokumen yang dibenarkan (§15.7).
 *
 * Digunakan oleh SEMUA saluran kemasukan — muat naik UI, e-mel intake,
 * WhatsApp inbound, dan validasi pelayan InboxIngestService — supaya senarai
 * format tidak lagi berselerak dalam pelbagai definisi. Kunci = sambungan fail
 * (extension huruf kecil), nilai = MIME kanonik.
 */
class AllowedFormats
{
    /** @return array<string, string> peta extension => MIME kanonik */
    public static function map(): array
    {
        return config('diwan.allowed_formats', []);
    }

    /** @return list<string> extension dibenarkan (huruf kecil) */
    public static function extensions(): array
    {
        return array_keys(static::map());
    }

    /** @return list<string> MIME unik dibenarkan */
    public static function mimes(): array
    {
        return array_values(array_unique(array_values(static::map())));
    }

    /**
     * Untuk Filament FileUpload::acceptedFileTypes() (gate sisi klien).
     *
     * @return list<string>
     */
    public static function acceptedFileTypes(): array
    {
        return static::mimes();
    }

    public static function allowsExtension(?string $extension): bool
    {
        return $extension !== null
            && array_key_exists(mb_strtolower(trim($extension)), static::map());
    }

    public static function allowsMime(?string $mime): bool
    {
        if ($mime === null) {
            return false;
        }

        // Buang parameter MIME (cth "text/plain; charset=utf-8") sebelum padan.
        $mime = mb_strtolower(trim(explode(';', $mime)[0]));

        return in_array($mime, static::mimes(), true);
    }

    /** MIME kanonik bagi extension; null jika extension tidak dibenarkan. */
    public static function mimeForExtension(?string $extension): ?string
    {
        return static::map()[mb_strtolower(trim((string) $extension))] ?? null;
    }

    /** Label ringkas format sah untuk mesej pengguna. */
    public static function label(): string
    {
        return 'PDF, DOC/DOCX, XLS/XLSX, PPT/PPTX, TXT, JPG/JPEG, PNG';
    }

    /** Mesej penolakan piawai (Bahasa Melayu) untuk semua saluran. */
    public static function rejectionMessage(): string
    {
        return 'Format fail tidak dibenarkan. Format sah: '.static::label().'.';
    }
}
