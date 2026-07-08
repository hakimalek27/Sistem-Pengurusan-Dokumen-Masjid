<?php

use App\Enums\SourceChannel;
use App\Models\StorageAddon;
use App\Notifications\QuotaThresholdNotification;
use App\Services\InboxIngestService;
use App\Services\QuotaService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake(config('diwan.storage_disk'));
    Notification::fake();
    Cache::flush();
    $this->quota = app(QuotaService::class);
    $this->ingest = app(InboxIngestService::class);
    $this->mam = makeMosque('MAM', 'mam');
});

it('kaunter storan bertambah bila media dicipta (§5.14)', function () {
    $before = (int) $this->mam->fresh()->storage_used_bytes;

    $this->ingest->ingest($this->mam, str_repeat('x', 5000), 'a.pdf', 'application/pdf', null, SourceChannel::MuatNaik);

    expect((int) $this->mam->fresh()->storage_used_bytes)->toBeGreaterThan($before);
});

it('kaunter storan berkurang bila media dipadam', function () {
    $record = $this->ingest->ingest($this->mam, str_repeat('x', 5000), 'a.pdf', 'application/pdf', null, SourceChannel::MuatNaik);
    $used = (int) $this->mam->fresh()->storage_used_bytes;

    $record->clearMediaCollection('original');

    expect((int) $this->mam->fresh()->storage_used_bytes)->toBeLessThan($used);
});

it('kuota efektif = base + Σ addon AKTIF (luput tidak dikira)', function () {
    $this->mam->update(['storage_quota_bytes' => 10 * (1024 ** 3)]);
    StorageAddon::query()->create(['mosque_id' => $this->mam->id, 'gb' => 5, 'status' => 'aktif', 'starts_at' => now()]);
    StorageAddon::query()->create(['mosque_id' => $this->mam->id, 'gb' => 3, 'status' => 'luput', 'starts_at' => now()]);

    expect($this->quota->effectiveQuota($this->mam->fresh()))->toBe((10 + 5) * (1024 ** 3));
});

it('sekat TULIS bila 100% penuh; addon buka semula', function () {
    $this->mam->update(['storage_quota_bytes' => 1000, 'storage_used_bytes' => 1000]);

    expect($this->quota->isFull($this->mam))->toBeTrue()
        ->and($this->quota->canStore($this->mam))->toBeFalse();

    StorageAddon::query()->create(['mosque_id' => $this->mam->id, 'gb' => 1, 'status' => 'aktif', 'starts_at' => now()]);

    expect($this->quota->canStore($this->mam->fresh()))->toBeTrue();
});

it('notifikasi ambang dihantar bila melepasi 80% (maks sekali/bulan)', function () {
    $admin = makeMember($this->mam, 'admin_masjid', 'admin@mam.test');
    // kuota kecil supaya satu muat naik melepasi 80%.
    $this->mam->update(['storage_quota_bytes' => 6000, 'storage_used_bytes' => 0]);

    $this->ingest->ingest($this->mam, str_repeat('x', 5000), 'a.pdf', 'application/pdf', null, SourceChannel::MuatNaik);

    Notification::assertSentTo($admin, QuotaThresholdNotification::class);
});

it('reconcile membetulkan drift kaunter', function () {
    $this->ingest->ingest($this->mam, str_repeat('x', 5000), 'a.pdf', 'application/pdf', null, SourceChannel::MuatNaik);
    $this->mam->update(['storage_used_bytes' => 999_999_999]);

    $this->quota->reconcile($this->mam->fresh());

    expect((int) $this->mam->fresh()->storage_used_bytes)->toBeLessThan(999_999_999);
});
