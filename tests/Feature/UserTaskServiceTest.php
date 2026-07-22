<?php

use App\Enums\MinitPriority;
use App\Enums\OcrStatus;
use App\Models\Minit;
use App\Models\MinitRecipient;
use App\Services\UserTaskService;

beforeEach(function () {
    $this->mam = makeMosque('MAM', 'mam');
    $this->man = makeMosque('MAN', 'man');
    $this->mamFile = makeFile($this->mam, makeNode($this->mam, '100-1'));
    $this->manFile = makeFile($this->man, makeNode($this->man, '100-1'));
    $this->mamUser = makeMember($this->mam, 'ajk');
    $this->manUser = makeMember($this->man, 'ajk');
});

it('mengira hanya tugasan pengguna dan tenant semasa', function () {
    $mamRecord = makeRecord($this->mam, $this->mamFile);
    $manRecord = makeRecord($this->man, $this->manFile);
    $mamMinit = Minit::query()->create([
        'mosque_id' => $this->mam->id, 'record_id' => $mamRecord->id, 'from_user_id' => makeMember($this->mam, 'admin_masjid')->id,
        'body' => 'Tindakan MAM', 'priority' => MinitPriority::Biasa, 'due_at' => today()->subDay(), 'status' => 'terbuka',
    ]);
    MinitRecipient::query()->create(['minit_id' => $mamMinit->id, 'user_id' => $this->mamUser->id, 'jenis' => 'tindakan', 'status' => 'belum']);
    $manMinit = Minit::query()->create([
        'mosque_id' => $this->man->id, 'record_id' => $manRecord->id, 'from_user_id' => makeMember($this->man, 'admin_masjid')->id,
        'body' => 'Tindakan MAN', 'priority' => MinitPriority::Biasa, 'due_at' => today()->subDay(), 'status' => 'terbuka',
    ]);
    MinitRecipient::query()->create(['minit_id' => $manMinit->id, 'user_id' => $this->manUser->id, 'jenis' => 'tindakan', 'status' => 'belum']);

    $tasks = app(UserTaskService::class)->for($this->mamUser, 'app', $this->mam);

    expect($tasks->firstWhere('id', 'minit-overdue')['count'])->toBe(1)
        ->and($tasks->firstWhere('id', 'minit-overdue')['ownership'])->toBe('personal')
        ->and(app(UserTaskService::class)->actionableCount($this->mamUser, 'app', $this->mam))->toBe(1)
        ->and($tasks->sum('count'))->toBe(1)
        ->and($tasks->pluck('url')->every(fn (string $url): bool => str_starts_with($url, '/app/mam/')))->toBeTrue();
});

it('tidak memasukkan tugasan pasukan dalam badge peribadi topbar', function () {
    $admin = makeMember($this->mam, 'admin_masjid');
    makeRecord($this->mam, null, attrs: [
        'ocr_status' => OcrStatus::Siap,
        'virus_scan_status' => 'clean',
    ]);

    $tasks = app(UserTaskService::class)->for($admin, 'app', $this->mam);

    expect($tasks->firstWhere('id', 'inbox-ready')['count'])->toBe(1)
        ->and($tasks->firstWhere('id', 'inbox-ready')['ownership'])->toBe('team')
        ->and(app(UserTaskService::class)->actionableCount($admin, 'app', $this->mam))->toBe(0);
});
