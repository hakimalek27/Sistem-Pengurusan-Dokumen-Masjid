<?php

use App\Enums\OrderStatus;
use App\Models\PlatformSetting;
use App\Notifications\AddonExpiringNotification;
use App\Services\BillingService;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake(config('diwan.storage_disk'));
    Notification::fake();
    PlatformSetting::put('pricing', ['per_gb_year_rm' => 2.0, 'block_gb' => 10]);
    $this->billing = app(BillingService::class);
    $this->mam = makeMosque('MAM', 'mam');
    $this->bendahari = makeMember($this->mam, 'bendahari', 'b@mam.test');
});

it('cipta pesanan → invois bersiri INV-YYYY-0001/0002 + PDF + menunggu bayaran', function () {
    $o1 = $this->billing->createOrder($this->mam, $this->bendahari, 1); // 10GB
    $o2 = $this->billing->createOrder($this->mam, $this->bendahari, 2); // 20GB
    $year = now()->format('Y');

    expect($o1->invoice_no)->toBe("INV-{$year}-0001")
        ->and($o2->invoice_no)->toBe("INV-{$year}-0002")
        ->and($o1->gb)->toBe(10)
        ->and($o1->amount_cents)->toBe(10 * 200) // 10GB × RM2 × 100
        ->and($o1->status)->toBe(OrderStatus::MenungguBayaran)
        ->and(Storage::disk(config('diwan.storage_disk'))->exists($o1->invoice_path))->toBeTrue();
});

it('tandakan dibayar → addon aktif + kuota efektif naik serta-merta (§18.32)', function () {
    $this->mam->update(['storage_quota_bytes' => 10 * (1024 ** 3)]);
    $order = $this->billing->createOrder($this->mam, $this->bendahari, 1); // 10GB

    $addon = $this->billing->markPaid($order, $this->bendahari);

    expect($order->fresh()->status)->toBe(OrderStatus::Dibayar)
        ->and($addon->status)->toBe('aktif')
        ->and($this->mam->fresh()->effectiveQuotaBytes())->toBe((10 + 10) * (1024 ** 3));
});

it('luput addon → status luput + kuota turun semula (§18.33)', function () {
    $this->mam->update(['storage_quota_bytes' => 10 * (1024 ** 3)]);
    $order = $this->billing->createOrder($this->mam, $this->bendahari, 1);
    $addon = $this->billing->markPaid($order, $this->bendahari);

    $addon->update(['expires_at' => now()->subDay()]);
    $this->billing->processExpiringAddons();

    expect($addon->fresh()->status)->toBe('luput')
        ->and($this->mam->fresh()->effectiveQuotaBytes())->toBe(10 * (1024 ** 3));
});

it('notis T-30 dihantar untuk addon yang akan luput', function () {
    $order = $this->billing->createOrder($this->mam, $this->bendahari, 1);
    $addon = $this->billing->markPaid($order, $this->bendahari);
    $addon->update(['expires_at' => now()->addDays(30)->startOfDay()]);

    $this->billing->processExpiringAddons();

    Notification::assertSentTo($this->bendahari, AddonExpiringNotification::class);
});
