<?php

use App\Models\SensitiveAccessLog;

beforeEach(function () {
    $this->mam = makeMosque('MAM', 'mam');
    $this->admin = makeMember($this->mam, 'admin_masjid', 'admin@mam.test');
    $node = makeNode($this->mam, '100-4', 'dalaman');
    $this->file = makeFile($this->mam, $node, 'dalaman');
    makeRecord($this->mam, $this->file, 'dalaman');
});

it('halaman Peti Masuk render', function () {
    $this->actingAs($this->admin)->get('/app/mam/peti-masuk')->assertOk();
});

it('halaman Rekod render', function () {
    $this->actingAs($this->admin)->get('/app/mam/records')->assertOk();
});

it('halaman Fail render', function () {
    $this->actingAs($this->admin)->get('/app/mam/registry-files')->assertOk();
});

it('halaman Klasifikasi Fail render', function () {
    $this->actingAs($this->admin)->get('/app/mam/classification-nodes')->assertOk();
});

it('halaman buka fail (create) render', function () {
    $this->actingAs($this->admin)->get('/app/mam/registry-files/create')->assertOk();
});

it('view rekod sulit render + tulis log akses sulit (§15.4)', function () {
    $node200 = makeNode($this->mam, '200-2', 'sulit');
    $file200 = makeFile($this->mam, $node200, 'sulit');
    $rec = makeRecord($this->mam, $file200, 'sulit');

    $this->actingAs($this->admin)->get('/app/mam/records/'.$rec->getKey())->assertOk();

    expect(SensitiveAccessLog::query()->where('record_id', $rec->id)->where('action', 'view')->exists())->toBeTrue();
});

it('halaman Carian render', function () {
    $this->actingAs($this->admin)->get('/app/mam/carian')->assertOk();
});

it('halaman Kegemaran render', function () {
    $this->actingAs($this->admin)->get('/app/mam/kegemaran')->assertOk();
});

it('halaman Pembetulan Rekod render', function () {
    $this->actingAs($this->admin)->get('/app/mam/pembetulan-rekod')->assertOk();
});

it('halaman Delegasi render', function () {
    $this->actingAs($this->admin)->get('/app/mam/delegasi')->assertOk();
});

it('halaman Kelulusan render', function () {
    $this->actingAs($this->admin)->get('/app/mam/kelulusan')->assertOk();
});

it('halaman Retensi & Pegangan render', function () {
    $this->actingAs($this->admin)->get('/app/mam/retensi')->assertOk();
});

it('halaman Peraturan Retensi render', function () {
    $this->actingAs($this->admin)->get('/app/mam/retensi-peraturan')->assertOk();
});

it('halaman Ahli & Peranan render', function () {
    $this->actingAs($this->admin)->get('/app/mam/ahli-peranan')->assertOk();
});

it('halaman Tetapan Masjid render', function () {
    $this->actingAs($this->admin)->get('/app/mam/tetapan-masjid')->assertOk();
});

it('halaman Pelupusan Manual render', function () {
    $this->actingAs($this->admin)->get('/app/mam/pelupusan')->assertOk();
});

it('halaman Profil render', function () {
    $this->actingAs($this->admin)->get('/app/mam/profil')->assertOk();
});

it('halaman Log Akses Sulit render', function () {
    $this->actingAs($this->admin)->get('/app/mam/sensitive-access-logs')->assertOk();
});

it('halaman Penggunaan & Storan render', function () {
    $this->actingAs($this->admin)->get('/app/mam/penggunaan')->assertOk();
});

it('halaman Minit Saya render', function () {
    $this->actingAs($this->admin)->get('/app/mam/minit-saya')->assertOk();
});

it('halaman view Fail render (termasuk relation manager grants)', function () {
    $this->actingAs($this->admin)->get('/app/mam/registry-files/'.$this->file->getKey())->assertOk();
});

it('halaman edit Fail render dengan medan medium fizikal', function () {
    $this->actingAs($this->admin)->get('/app/mam/registry-files/'.$this->file->getKey().'/edit')->assertOk();
});

it('rekod MAN tidak dapat dilihat dalam konteks tenant MAM via URL resource → 404 (§18.3)', function () {
    $man = makeMosque('MAN', 'man');
    $recMan = makeRecord($man, makeFile($man, makeNode($man, '100-4')), 'dalaman');

    $this->actingAs($this->admin)->get('/app/mam/records/'.$recMan->getKey())->assertNotFound();
});
