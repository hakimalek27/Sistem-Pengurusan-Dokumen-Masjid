<?php

use App\Support\AllowedFormats;

it('peta format mengandungi 11 extension yang dijangka dan bukan webp', function () {
    $ext = AllowedFormats::extensions();

    expect($ext)->toContain('pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'jpg', 'jpeg', 'png')
        ->and($ext)->not->toContain('webp')
        ->and($ext)->toHaveCount(11);
});

it('allowsExtension case-insensitive dan menolak format luar senarai', function () {
    expect(AllowedFormats::allowsExtension('PDF'))->toBeTrue()
        ->and(AllowedFormats::allowsExtension('Docx'))->toBeTrue()
        ->and(AllowedFormats::allowsExtension('webp'))->toBeFalse()
        ->and(AllowedFormats::allowsExtension('exe'))->toBeFalse()
        ->and(AllowedFormats::allowsExtension('zip'))->toBeFalse()
        ->and(AllowedFormats::allowsExtension(null))->toBeFalse();
});

it('allowsMime mengabai parameter charset dan menolak webp/octet-stream', function () {
    expect(AllowedFormats::allowsMime('text/plain; charset=utf-8'))->toBeTrue()
        ->and(AllowedFormats::allowsMime('application/pdf'))->toBeTrue()
        ->and(AllowedFormats::allowsMime('image/webp'))->toBeFalse()
        ->and(AllowedFormats::allowsMime('application/octet-stream'))->toBeFalse()
        ->and(AllowedFormats::allowsMime(null))->toBeFalse();
});

it('mimeForExtension memulangkan MIME kanonik', function () {
    expect(AllowedFormats::mimeForExtension('jpg'))->toBe('image/jpeg')
        ->and(AllowedFormats::mimeForExtension('JPEG'))->toBe('image/jpeg')
        ->and(AllowedFormats::mimeForExtension('doc'))->toBe('application/msword')
        ->and(AllowedFormats::mimeForExtension('txt'))->toBe('text/plain')
        ->and(AllowedFormats::mimeForExtension('webp'))->toBeNull();
});

it('acceptedFileTypes selaras dengan mimes dan unik', function () {
    expect(AllowedFormats::acceptedFileTypes())->toBe(AllowedFormats::mimes())
        ->and(AllowedFormats::mimes())->toBe(array_values(array_unique(AllowedFormats::mimes())));
});
