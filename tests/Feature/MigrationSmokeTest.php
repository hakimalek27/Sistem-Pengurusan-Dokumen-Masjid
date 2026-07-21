<?php

use App\Models\Mosque;
use App\Models\RetentionRule;
use Illuminate\Support\Facades\Schema;

it('mencipta semua jadual §5 + jadual pakej', function () {
    $tables = [
        // §5 DIWAN
        'mosques', 'mosque_user', 'users', 'login_tokens', 'classification_nodes',
        'registry_files', 'records', 'minits', 'minit_recipients', 'approvals',
        'retention_rules', 'disposal_batches', 'disposal_items', 'storage_orders',
        'storage_addons', 'platform_settings', 'sensitive_access_logs',
        'notification_logs', 'file_access_grants',
        'whatsapp_integrations',
        // Pakej
        'media', 'activity_log', 'notifications', 'jobs',
    ];

    foreach ($tables as $table) {
        $this->assertTrue(Schema::hasTable($table), "Jadual '{$table}' tidak wujud");
    }
});

it('menyemai 2 masjid demo (MAM & MAN)', function () {
    $this->seed();

    expect(Mosque::count())->toBe(2)
        ->and(Mosque::where('code', 'MAM')->exists())->toBeTrue()
        ->and(Mosque::where('code', 'MAN')->exists())->toBeTrue();
});

it('config record_types mengandungi katalog teras dan tambahan DDMS', function () {
    expect(config('record_types'))->toHaveCount(33)
        ->and(config('record_types'))->toHaveKeys(['agenda_mesyuarat', 'rekod_aset', 'permit_lesen']);
});

it('config roles mengandungi 9 peranan (§6.1)', function () {
    expect(config('roles.list'))->toHaveCount(9);
});

it('peraturan retensi lalai platform wujud (§16.1)', function () {
    $this->seed();

    expect(RetentionRule::whereNull('mosque_id')->count())->toBeGreaterThan(0)
        ->and(RetentionRule::whereNull('mosque_id')->where('action', 'kekal')->exists())->toBeTrue()
        ->and(RetentionRule::whereNull('mosque_id')->where('classification_prefix', '200')->exists())->toBeTrue();
});

it('KF tersalin ke setiap masjid dengan 40 nod (9 fungsi + 31 aktiviti)', function () {
    $this->seed();

    $mam = Mosque::where('code', 'MAM')->first();
    expect($mam->classificationNodes()->count())->toBe(40)
        ->and($mam->classificationNodes()->where('level', 'fungsi')->count())->toBe(9)
        ->and($mam->classificationNodes()->where('level', 'aktiviti')->count())->toBe(31);
});
