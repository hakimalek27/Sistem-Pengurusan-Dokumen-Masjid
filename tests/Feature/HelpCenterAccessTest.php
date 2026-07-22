<?php

use App\Models\SupportRequest;
use App\Models\User;

beforeEach(function () {
    $this->mam = makeMosque('MAM', 'mam');
    $this->man = makeMosque('MAN', 'man');
    $this->admin = makeMember($this->mam, 'admin_masjid');
    $this->secretary = makeMember($this->mam, 'setiausaha');
    $this->ajk = makeMember($this->mam, 'ajk');
    $this->superadmin = User::query()->create([
        'name' => 'Superadmin', 'email' => 'super-help@ujian.test', 'password' => bcrypt('kata-laluan-ujian'),
        'is_superadmin' => true, 'is_active' => true,
    ]);
});

it('merender pusat bantuan dan analitik mengikut role', function () {
    $this->actingAs($this->admin)->get('/app/mam/bantuan')->assertOk()->assertSee('Cari panduan')->assertSee('data-panel="app"', false);
    $this->actingAs($this->admin)->get('/app/mam/analitik-bantuan')->assertOk()->assertSee('Data ini agregat');
    $this->actingAs($this->ajk)->get('/app/mam/bantuan')->assertOk();
    $this->actingAs($this->ajk)->get('/app/mam/analitik-bantuan')->assertForbidden();

    $this->actingAs($this->superadmin)->get('/admin/bantuan')->assertOk()->assertSee('Cari panduan');
    $this->actingAs($this->superadmin)->get('/admin/analitik-bantuan')->assertOk();
    $this->actingAs($this->superadmin)->get('/admin/help-announcements')->assertOk();
});

it('menghadkan resource tiket kepada Admin Kerani tenant dan superadmin', function () {
    $mamTicket = SupportRequest::query()->create([
        'reference' => 'SUP-MAM-1', 'mosque_id' => $this->mam->id, 'user_id' => $this->admin->id,
        'panel' => 'app', 'role' => 'admin_masjid', 'category' => 'upload', 'subject' => 'Tiket MAM',
        'expected' => 'Berjaya', 'actual' => 'Gagal', 'status' => 'baharu',
    ]);
    $manTicket = SupportRequest::query()->create([
        'reference' => 'SUP-MAN-1', 'mosque_id' => $this->man->id,
        'panel' => 'app', 'role' => 'admin_masjid', 'category' => 'upload', 'subject' => 'Tiket MAN',
        'expected' => 'Berjaya', 'actual' => 'Gagal', 'status' => 'baharu',
    ]);

    $this->actingAs($this->admin)->get('/app/mam/tiket-sokongan')->assertOk()->assertSee('Tiket MAM')->assertDontSee('Tiket MAN');
    $this->actingAs($this->admin)->get('/app/mam/tiket-sokongan/'.$manTicket->id)->assertNotFound();
    $this->actingAs($this->ajk)->get('/app/mam/tiket-sokongan')->assertForbidden();
    $this->actingAs($this->superadmin)->get('/admin/tiket-sokongan')->assertOk()->assertSee('Tiket MAM')->assertSee('Tiket MAN');
    $this->actingAs($this->superadmin)->get('/admin/tiket-sokongan/'.$mamTicket->id)->assertOk();
});

it('menghidang gambar latihan ikut akses guide', function () {
    $this->get('/bantuan/imej/public.registration')->assertOk()->assertHeader('Content-Type', 'image/png');
    $this->get('/bantuan/imej/tenant.peti-masuk?tenant='.$this->mam->id)->assertNotFound();
    $this->actingAs($this->admin)->get('/bantuan/imej/tenant.peti-masuk?tenant='.$this->mam->id)->assertOk();
    $this->actingAs($this->admin)->get('/bantuan/imej/tenant.peti-masuk?tenant='.$this->man->id)->assertNotFound();

    $adminImage = $this->actingAs($this->admin)
        ->get('/bantuan/imej/screen.klasifikasi-peti-masuk?tenant='.$this->mam->id);
    $secretaryImage = $this->actingAs($this->secretary)
        ->get('/bantuan/imej/screen.klasifikasi-peti-masuk?tenant='.$this->mam->id);

    expect(str_replace('\\', '/', $adminImage->baseResponse->getFile()->getPathname()))->toContain('/01-Admin-Kerani/');
    expect(str_replace('\\', '/', $secretaryImage->baseResponse->getFile()->getPathname()))->toContain('/03-Setiausaha/');
});

it('menutup pusat bantuan apabila feature flag dimatikan', function () {
    config()->set('diwan.guidance.enabled', false);

    $this->get('/bantuan')->assertNotFound();
    $this->actingAs($this->admin)->get('/app/mam/bantuan')->assertForbidden();
});
