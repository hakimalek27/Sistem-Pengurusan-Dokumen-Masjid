<?php

use App\Jobs\FailureProbeJob;

it('healthcheck dalaman mengesahkan DB dan cache', function () {
    $this->artisan('diwan:health')->expectsOutput('OK')->assertSuccessful();
});

it('failure probe job gagal dengan id yang boleh dijejak', function () {
    expect(fn () => (new FailureProbeJob('probe-123'))->handle())
        ->toThrow(RuntimeException::class, 'DIWAN_FAILURE_PROBE:probe-123');
});

it('Horizon mengasingkan queue umum OCR dan eksport', function () {
    expect(config('horizon.defaults.general.queue'))->toBe(['default'])
        ->and(config('horizon.defaults.ocr.queue'))->toBe(['ocr'])
        ->and(config('horizon.defaults.ocr.maxProcesses'))->toBe(1)
        ->and(config('horizon.defaults.exports.queue'))->toBe(['exports'])
        ->and(config('horizon.defaults.exports.timeout'))->toBeGreaterThan(1800);
});

it('Docker production membina vendor asset dan berjalan tanpa bind mount kod', function () {
    $dockerfile = file_get_contents(base_path('docker/Dockerfile'));
    $compose = file_get_contents(base_path('docker-compose.yml'));

    expect($dockerfile)->toContain('npm ci --no-audit --no-fund')
        ->and($dockerfile)->toContain('composer install')
        ->and($dockerfile)->toContain('--no-dev')
        ->and($dockerfile)->toContain('USER www-data')
        ->and($dockerfile)->toContain('HEALTHCHECK')
        ->and($compose)->not->toContain('./:/var/www/html')
        ->and($compose)->toContain('condition: service_healthy')
        ->and(file_exists(base_path('package-lock.json')))->toBeTrue();
});

it('menyediakan staging smoke failure injection dan restore drill sebenar', function () {
    $staging = file_get_contents(base_path('scripts/staging-smoke.sh'));
    $restore = file_get_contents(base_path('scripts/restore-drill.sh'));
    $stagingCommand = file_get_contents(app_path('Console/Commands/StagingCheck.php'));

    expect($staging)->toContain('diwan:staging-check')
        ->and($staging)->toContain('horizon:status')
        ->and($staging)->toContain('if docker compose logs')
        ->and($stagingCommand)->toContain("ImapClient::account('default')")
        ->and($stagingCommand)->toContain('getFolders(false)')
        ->and(file_exists(config_path('imap.php')))->toBeTrue()
        ->and(config('imap.accounts.default.validate_cert'))->toBeTrue()
        ->and($restore)->toContain('postgres:16-alpine')
        ->and($restore)->toContain('pg_restore')
        ->and($restore)->toContain('count(*) FROM records')
        ->and($restore)->toContain('LULUS restore drill');
});
