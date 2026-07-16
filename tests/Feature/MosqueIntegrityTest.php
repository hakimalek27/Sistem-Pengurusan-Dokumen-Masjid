<?php

use App\Models\Mosque;

it('mengunci kod tenant selepas fail registri digunakan', function () {
    $mosque = makeMosque('MKD', 'masjid-kod');
    $originalCode = $mosque->code;
    makeFile($mosque, makeNode($mosque, '100-1'));

    expect(fn () => $mosque->update(['code' => 'BARU']))
        ->toThrow(LogicException::class, 'Kod tenant tidak boleh diubah');

    expect(Mosque::query()->findOrFail($mosque->id)->code)->toBe($originalCode);
});
