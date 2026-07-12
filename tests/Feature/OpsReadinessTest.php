<?php

use App\Jobs\FailureProbeJob;

it('healthcheck dalaman mengesahkan DB dan cache', function () {
    $this->artisan('diwan:health')->expectsOutput('OK')->assertSuccessful();
});

it('failure probe job gagal dengan id yang boleh dijejak', function () {
    expect(fn () => (new FailureProbeJob('probe-123'))->handle())
        ->toThrow(RuntimeException::class, 'DIWAN_FAILURE_PROBE:probe-123');
});

it('failure drill queue mempunyai mod pengesahan failed jobs', function () {
    $command = file_get_contents(app_path('Console/Commands/FailureDrill.php'));

    expect($command)->toContain('{--verify')
        ->and($command)->toContain("DB::table('failed_jobs')")
        ->and($command)->toContain('tidak muncul dalam failed_jobs');
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
        ->and($dockerfile)->toContain('FROM php:8.4-fpm-bookworm AS runtime')
        ->and($dockerfile)->toContain('composer install')
        ->and($dockerfile)->toContain('--no-dev')
        ->and($dockerfile)->toContain('FROM runtime AS vendor')
        ->and($dockerfile)->toContain('COPY --from=composer:2 /usr/bin/composer')
        ->and($dockerfile)->not->toContain('--ignore-platform-req')
        ->and($dockerfile)->toContain('mbstring dom simplexml xmlreader opcache')
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

it('menyediakan CI integration Docker dan deploy staging berrollback', function () {
    $ci = file_get_contents(base_path('.github/workflows/ci.yml'));
    $deployWorkflow = file_get_contents(base_path('.github/workflows/deploy-staging.yml'));
    $deployScript = file_get_contents(base_path('scripts/deploy-staging.sh'));

    expect($ci)->toContain('postgres:16-alpine')
        ->and($ci)->toContain('redis:7-alpine')
        ->and($ci)->toContain('getmeili/meilisearch:v1.12')
        ->and($ci)->toContain('php artisan diwan:staging-check')
        ->and($ci)->toContain('docker/build-push-action@v6')
        ->and($ci)->toContain('Run built image smoke')
        ->and($ci)->toContain('PHP runtime extensions OK')
        ->and($ci)->toContain('nginx "$IMAGE" -t')
        ->and($deployWorkflow)->toContain('environment: staging')
        ->and($deployWorkflow)->toContain('STAGING_KNOWN_HOSTS')
        ->and($deployScript)->toContain('trap rollback ERR')
        ->and($deployScript)->toContain('failure-drill queue --verify')
        ->and($deployScript)->toContain('restore-drill.sh');
});
