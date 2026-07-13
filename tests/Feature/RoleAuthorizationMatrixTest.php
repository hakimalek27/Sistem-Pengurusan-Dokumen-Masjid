<?php

use App\Filament\App\Resources\Inbox\InboxResource;
use App\Filament\App\Resources\Records\RecordResource;
use App\Filament\App\Resources\SensitiveAccessLogs\SensitiveAccessLogResource;
use App\Models\FileAccessGrant;
use Filament\Facades\Filament;

beforeEach(function () {
    $this->mosque = makeMosque('MAM', 'mam');
    $this->dalaman = makeRecord($this->mosque, makeFile($this->mosque, makeNode($this->mosque, '100-4')), 'dalaman');
    $this->sulit200 = makeRecord($this->mosque, makeFile($this->mosque, makeNode($this->mosque, '200-2', 'sulit'), 'sulit'), 'sulit');
    $this->sulit800 = makeRecord($this->mosque, makeFile($this->mosque, makeNode($this->mosque, '800-1', 'sulit'), 'sulit'), 'sulit');

    Filament::setCurrentPanel(Filament::getPanel('app'));
    Filament::setTenant($this->mosque, isQuiet: true);
});

afterEach(function () {
    Filament::setTenant(null, isQuiet: true);
});

it('menguatkuasakan matriks 9 peranan pada Peti Masuk, Log Akses dan tetapan tenant', function () {
    $inboxRoles = ['admin_masjid', 'kerani', 'setiausaha'];
    $auditRoles = ['admin_masjid', 'pengerusi', 'audit'];

    foreach (config('roles.list') as $role) {
        $user = makeMember($this->mosque, $role);
        $this->actingAs($user);

        expect(InboxResource::canViewAny())->toBe(in_array($role, $inboxRoles, true), "Peti Masuk: {$role}")
            ->and(SensitiveAccessLogResource::canViewAny())->toBe(in_array($role, $auditRoles, true), "Log akses: {$role}");

        $inboxResponse = $this->get('/app/mam/peti-masuk');
        $logResponse = $this->get('/app/mam/sensitive-access-logs');
        $settingsResponse = $this->get('/app/mam/tetapan-masjid');
        $membersResponse = $this->get('/app/mam/ahli-peranan');

        in_array($role, $inboxRoles, true)
            ? $inboxResponse->assertOk()
            : $inboxResponse->assertForbidden();

        in_array($role, $auditRoles, true)
            ? $logResponse->assertOk()
            : $logResponse->assertForbidden();

        $role === 'admin_masjid'
            ? $settingsResponse->assertOk()
            : $settingsResponse->assertForbidden();

        $role === 'admin_masjid'
            ? $membersResponse->assertOk()
            : $membersResponse->assertForbidden();
    }
});

it('menapis sensitiviti pada query senarai sebelum metadata dirender', function () {
    $fullAccess = ['admin_masjid', 'kerani', 'pengerusi', 'setiausaha', 'nazir'];

    foreach (config('roles.list') as $role) {
        $user = makeMember($this->mosque, $role);
        $this->actingAs($user);

        $ids = RecordResource::getEloquentQuery()->pluck('id')->all();

        if (in_array($role, $fullAccess, true)) {
            expect($ids)->toEqualCanonicalizing([$this->dalaman->id, $this->sulit200->id, $this->sulit800->id]);
        } elseif ($role === 'bendahari') {
            expect($ids)->toEqualCanonicalizing([$this->dalaman->id, $this->sulit200->id]);
        } else {
            expect($ids)->toBe([$this->dalaman->id]);
        }
    }
});

it('grant individu membuka hanya rekod fail yang diberi akses dalam senarai', function () {
    $ajk = makeMember($this->mosque, 'ajk');
    FileAccessGrant::query()->create([
        'registry_file_id' => $this->sulit800->registry_file_id,
        'user_id' => $ajk->id,
        'granted_by' => makeMember($this->mosque, 'admin_masjid')->id,
    ]);
    $this->actingAs($ajk);

    expect(RecordResource::getEloquentQuery()->pluck('id')->all())
        ->toEqualCanonicalizing([$this->dalaman->id, $this->sulit800->id]);
});

it('tiada route cipta atau edit untuk log append-only', function () {
    $admin = makeMember($this->mosque, 'admin_masjid');

    $this->actingAs($admin)->get('/app/mam/sensitive-access-logs/create')->assertNotFound();
    $this->actingAs($admin)->get('/app/mam/sensitive-access-logs/1/edit')->assertNotFound();
});
