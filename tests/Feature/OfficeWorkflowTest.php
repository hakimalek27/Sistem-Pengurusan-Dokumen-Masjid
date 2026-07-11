<?php

use App\Enums\ApprovalStatus;
use App\Enums\MinitPriority;
use App\Models\User;
use App\Services\ApprovalService;
use App\Services\MinitService;

beforeEach(function () {
    $this->mosque = makeMosque('MAM', 'mam');
    $this->admin = makeMember($this->mosque, 'admin_masjid');
    $this->chair = makeMember($this->mosque, 'pengerusi');
    $this->record = makeRecord($this->mosque, makeFile($this->mosque, makeNode($this->mosque, '100-4')));
});

it('memaparkan dashboard tenant lengkap dan wizard Rekod Baharu', function () {
    $this->actingAs($this->admin)
        ->get('/app/mam')
        ->assertOk()
        ->assertSee('Ringkasan Pejabat')
        ->assertSee('Checklist Onboarding');

    $this->actingAs($this->admin)
        ->get('/app/mam/records')
        ->assertOk()
        ->assertSee('Rekod Baharu');
});

it('memaparkan dashboard superadmin dengan metrik platform', function () {
    $super = User::query()->create([
        'name' => 'Superadmin', 'email' => 'super-office@ujian.test', 'is_superadmin' => true, 'is_active' => true,
    ]);

    $this->actingAs($super)->get('/admin')->assertOk()->assertSee('Ringkasan Platform');
});

it('memaparkan laporan pejabat mengikut tenant', function () {
    $this->actingAs($this->admin)
        ->get('/app/mam/laporan')
        ->assertOk()
        ->assertSee('Jumlah Rekod')
        ->assertSee('Jenis Rekod')
        ->assertSee('Eksport CSV');
});

it('memaparkan bebenang minit sejarah kelulusan dan audit pada rekod', function () {
    app(MinitService::class)->create(
        $this->record,
        $this->admin,
        [$this->chair->id],
        [],
        'Sila semak dokumen ini',
        MinitPriority::Biasa,
    );
    $approval = app(ApprovalService::class)->request($this->record, $this->admin, $this->chair, 'Untuk kelulusan');
    app(ApprovalService::class)->decide($approval, $this->chair, ApprovalStatus::Lulus, 'Diluluskan', '127.0.0.1');

    $this->actingAs($this->admin)
        ->get('/app/mam/records/'.$this->record->id)
        ->assertOk()
        ->assertSee('Sila semak dokumen ini')
        ->assertSee('Untuk kelulusan')
        ->assertSee('Diluluskan')
        ->assertSee('Audit');
});
