<?php

use App\Models\FileAccessGrant;
use App\Models\User;
use App\Policies\RecordPolicy;

function policyView(User $user, $record): bool
{
    return (new RecordPolicy)->view($user, $record);
}

beforeEach(function () {
    $this->mam = makeMosque('MAM', 'mam');
    $nodeDalaman = makeNode($this->mam, '100-4', 'dalaman');
    $node200 = makeNode($this->mam, '200-2', 'sulit');
    $node800 = makeNode($this->mam, '800-1', 'sulit');

    $this->fileDalaman = makeFile($this->mam, $nodeDalaman, 'dalaman');
    $this->file200 = makeFile($this->mam, $node200, 'sulit');
    $this->file800 = makeFile($this->mam, $node800, 'sulit');

    $this->recDalaman = makeRecord($this->mam, $this->fileDalaman, 'dalaman');
    $this->rec200 = makeRecord($this->mam, $this->file200, 'sulit');
    $this->rec800 = makeRecord($this->mam, $this->file800, 'sulit');
});

it('ajk tidak nampak rekod sulit (§6.3)', function () {
    $ajk = makeMember($this->mam, 'ajk');

    expect(policyView($ajk, $this->recDalaman))->toBeTrue()
        ->and(policyView($ajk, $this->rec200))->toBeFalse()
        ->and(policyView($ajk, $this->rec800))->toBeFalse();
});

it('bendahari nampak sulit fail 200/300 sahaja', function () {
    $bendahari = makeMember($this->mam, 'bendahari');

    expect(policyView($bendahari, $this->rec200))->toBeTrue()      // Kewangan 200
        ->and(policyView($bendahari, $this->rec800))->toBeFalse()  // 800 bukan 200/300
        ->and(policyView($bendahari, $this->recDalaman))->toBeTrue();
});

it('kerani (peranan istimewa) nampak semua sulit', function () {
    $kerani = makeMember($this->mam, 'kerani');

    expect(policyView($kerani, $this->rec200))->toBeTrue()
        ->and(policyView($kerani, $this->rec800))->toBeTrue();
});

it('audit baca dalaman tetapi bukan sulit', function () {
    $audit = makeMember($this->mam, 'audit');

    expect(policyView($audit, $this->recDalaman))->toBeTrue()
        ->and(policyView($audit, $this->rec800))->toBeFalse();
});

it('grant individu membuka satu fail sulit sahaja untuk ajk', function () {
    $ajk = makeMember($this->mam, 'ajk');

    expect(policyView($ajk, $this->rec800))->toBeFalse();

    FileAccessGrant::query()->create([
        'registry_file_id' => $this->file800->id,
        'user_id' => $ajk->id,
    ]);

    expect(policyView($ajk->fresh(), $this->rec800->fresh()))->toBeTrue()
        ->and(policyView($ajk->fresh(), $this->rec200->fresh()))->toBeFalse(); // fail lain kekal tertutup
});

it('superadmin lulus semua (Gate::before)', function () {
    $super = User::query()->create([
        'name' => 'Super', 'email' => 'super@ujian.test', 'is_superadmin' => true, 'is_active' => true,
    ]);

    expect($super->can('view', $this->rec800))->toBeTrue();
});
