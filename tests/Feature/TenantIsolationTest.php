<?php

use App\Models\Record;
use App\Models\User;
use Filament\Facades\Filament;

beforeEach(function () {
    $this->mam = makeMosque('MAM', 'mam');
    $this->man = makeMosque('MAN', 'man');

    $this->recMam = makeRecord($this->mam, makeFile($this->mam, makeNode($this->mam, '100-4')));
    $this->recMan = makeRecord($this->man, makeFile($this->man, makeNode($this->man, '100-4')));
});

it('scopeForMosque hanya memulangkan rekod masjid berkenaan (query luar-panel §15.2)', function () {
    expect(Record::forMosque($this->mam)->pluck('id')->all())->toBe([$this->recMam->id])
        ->and(Record::forMosque($this->man)->pluck('id')->all())->toBe([$this->recMan->id]);
});

it('global scope tenant Filament menyembunyikan rekod masjid lain', function () {
    Filament::setCurrentPanel(Filament::getPanel('app'));

    Filament::setTenant($this->mam, isQuiet: true);
    expect(Record::pluck('id')->all())->toBe([$this->recMam->id]);

    Filament::setTenant($this->man, isQuiet: true);
    expect(Record::pluck('id')->all())->toBe([$this->recMan->id]);

    Filament::setTenant(null, isQuiet: true);
});

it('deep-link /r/{ulid}: ahli MAM tidak boleh akses rekod MAN → 404', function () {
    $mamUser = makeMember($this->mam, 'kerani');

    $this->actingAs($mamUser)->get('/r/'.$this->recMan->ulid)->assertNotFound();
    $this->actingAs($mamUser)->get('/r/'.$this->recMam->ulid)->assertRedirect('/app/mam/records/'.$this->recMam->id);
});

it('bukan ahli mana-mana masjid → 404 pada deep-link', function () {
    $outsider = User::query()->create([
        'name' => 'Orang Luar', 'email' => 'luar@ujian.test', 'is_active' => true,
    ]);

    $this->actingAs($outsider)->get('/r/'.$this->recMam->ulid)->assertNotFound();
});

it('pengguna dwi-masjid: peranan berasingan setiap tenant, set rekod berasingan', function () {
    $dwi = makeMember($this->mam, 'ajk', 'dwi@ujian.test');
    $this->man->users()->attach($dwi->id, ['role' => 'kerani', 'joined_at' => now()]);

    expect($dwi->roleIn($this->mam))->toBe('ajk')
        ->and($dwi->roleIn($this->man))->toBe('kerani')
        ->and(Record::forMosque($this->mam)->count())->toBe(1)
        ->and(Record::forMosque($this->man)->count())->toBe(1);
});

it('superadmin boleh akses mana-mana tenant', function () {
    $super = User::query()->create([
        'name' => 'Super', 'email' => 'super@ujian.test', 'is_superadmin' => true, 'is_active' => true,
    ]);

    expect($super->canAccessTenant($this->mam))->toBeTrue()
        ->and($super->canAccessTenant($this->man))->toBeTrue();

    $this->actingAs($super)->get('/r/'.$this->recMan->ulid)->assertRedirect('/app/man/records/'.$this->recMan->id);
});
