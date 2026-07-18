<?php

use App\Models\Mosque;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

beforeEach(function () {
    $this->super = User::query()->create(['name' => 'Super', 'email' => 's@x.test', 'password' => bcrypt('secret'), 'is_superadmin' => true, 'is_active' => true]);
    makeMosque('MAM', 'mam');
});

it('papan pemuka superadmin render', function () {
    $this->actingAs($this->super)->get('/admin')->assertOk();
});

it('senarai Masjid render', function () {
    $this->actingAs($this->super)->get('/admin/mosques')->assertOk();
});

it('senarai Pesanan Storan render', function () {
    $this->actingAs($this->super)->get('/admin/storage-orders')
        ->assertOk()
        ->assertDontSee('Cipta');
});

it('pesanan storan hanya boleh berubah melalui aliran bil terkawal', function () {
    $this->actingAs($this->super)->get('/admin/storage-orders/create')->assertNotFound();
    $this->actingAs($this->super)->get('/admin/storage-orders/1/edit')->assertNotFound();
});

it('tenant didaftarkan melalui borang awam dan tidak boleh dimusnahkan kekal', function () {
    $mosque = Mosque::query()->firstOrFail();

    $this->actingAs($this->super)->get('/admin/mosques/create')->assertNotFound();
    expect(Gate::forUser($this->super)->allows('forceDelete', $mosque))->toBeFalse();
});

it('senarai Pengguna render', function () {
    $this->actingAs($this->super)->get('/admin/users')->assertOk();
});

it('halaman Tetapan Platform render', function () {
    $this->actingAs($this->super)->get('/admin/tetapan-platform')->assertOk();
});

it('bukan superadmin tidak boleh akses /admin', function () {
    $user = makeMember(makeMosque('MAN', 'man'), 'kerani', 'k@man.test');

    $this->actingAs($user)->get('/admin')->assertForbidden();
});

it('menggunakan zon masa operasi Malaysia', function () {
    expect(config('app.timezone'))->toBe('Asia/Kuala_Lumpur');
});

it('ahli biasa tidak boleh membuka tenant yang digantung atau diarkibkan', function () {
    $mosque = makeMosque('TDG', 'tenant-digantung');
    $member = makeMember($mosque, 'kerani', 'suspended-member@ujian.test');

    $mosque->update(['status' => 'digantung']);
    expect($member->fresh()->canAccessTenant($mosque->fresh()))->toBeFalse();
    $this->actingAs($member)->get('/app/tenant-digantung')->assertForbidden();

    $mosque->update(['status' => 'aktif']);
    $mosque->delete();
    expect($member->fresh()->canAccessTenant($mosque))->toBeFalse();
});
