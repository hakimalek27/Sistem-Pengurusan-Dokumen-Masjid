<?php

use App\Models\Mosque;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * D11 #11 (PELAN-PEMBAIKAN.md §9.1a — P16-04/P18-03): kontrak kitaran hayat fixture audit.
 * prepare → cleanup → cleanup = DB identik dengan sebelum prepare; tenant `smoke` gate deploy
 * TIDAK tersentuh dalam mana-mana laluan; output prepare TIDAK mengandungi kredensial
 * superadmin; run uuid divalidasi keras.
 */
const AUDIT_RUN_UUID = 'aaaaaaaa-bbbb-4ccc-8ddd-eeeeeeeeeeee';

function fixtureJsonPath(): string
{
    return storage_path('app/plan-ci/audit-fixture-test.json');
}

function snapshotCounts(): array
{
    return [
        'mosques' => DB::table('mosques')->count(),
        'users' => DB::table('users')->count(),
        'mosque_user' => DB::table('mosque_user')->count(),
        'classification_nodes' => DB::table('classification_nodes')->count(),
        'login_tokens' => DB::table('login_tokens')->count(),
    ];
}

test('run uuid tidak sah ditolak keras (tiada pembetulan senyap)', function () {
    expect(Artisan::call('diwan:audit-fixture', ['action' => 'prepare', '--run' => 'bukan-uuid', '--json' => fixtureJsonPath()]))
        ->toBe(1)
        ->and(Artisan::output())->toContain('UUIDv4');
});

test('prepare mencipta tenant run-scoped + 8 akaun role; kredensial TIDAK dicetak ke stdout', function () {
    // Tenant gate deploy `smoke` diwujudkan dahulu — mesti KEKAL tidak tersentuh.
    $smoke = makeMosque('SMK', 'smoke');
    $smokeAdmin = makeMember($smoke, 'admin_masjid', 'admin-smoke@smoke.test');

    $exit = Artisan::call('diwan:audit-fixture', ['action' => 'prepare', '--run' => AUDIT_RUN_UUID, '--json' => fixtureJsonPath()]);
    $output = Artisan::output();

    expect($exit)->toBe(0)
        ->and($output)->not->toContain('SUPERADMIN')
        ->and(Mosque::query()->where('slug', 'smoke-'.AUDIT_RUN_UUID)->exists())->toBeTrue()
        ->and(User::query()->where('email', 'like', '%-'.AUDIT_RUN_UUID.'@smoke.test')->count())->toBe(8);

    $inventory = json_decode((string) file_get_contents(fixtureJsonPath()), true);
    expect($inventory['run_uuid'])->toBe(AUDIT_RUN_UUID)
        ->and($inventory['created']['user_ids'])->toHaveCount(8)
        ->and(collect($inventory['role_credentials'])->pluck('role')->sort()->values()->all())
        ->toBe(collect(config('roles.list'))->sort()->values()->all());

    // Kata laluan wujud dalam FAIL sahaja, bukan stdout.
    foreach ($inventory['role_credentials'] as $credential) {
        expect($credential['password'])->not->toBeEmpty()
            ->and($output)->not->toContain($credential['password']);
    }
    // Inventori `created` tidak menandakan mana-mana pengguna superadmin (P18-03).
    expect(User::query()->whereIn('id', $inventory['created']['user_ids'])->where('is_superadmin', true)->count())->toBe(0)
        ->and($smoke->fresh()->slug)->toBe('smoke')
        ->and($smokeAdmin->fresh())->not->toBeNull();
});

test('prepare → cleanup → cleanup idempotent: DB identik + tenant smoke tidak tersentuh', function () {
    $smoke = makeMosque('SMK', 'smoke');
    makeMember($smoke, 'admin_masjid', 'admin-smoke@smoke.test');
    $smokeNode = makeNode($smoke, '100-1');
    $before = snapshotCounts();

    expect(Artisan::call('diwan:audit-fixture', ['action' => 'prepare', '--run' => AUDIT_RUN_UUID, '--json' => fixtureJsonPath()]))->toBe(0);
    expect(snapshotCounts())->not->toBe($before);

    expect(Artisan::call('diwan:audit-fixture', ['action' => 'cleanup', '--run' => AUDIT_RUN_UUID, '--json' => fixtureJsonPath()]))->toBe(0);
    expect(snapshotCounts())->toBe($before, 'cleanup mesti memulangkan DB ke keadaan sebelum prepare');

    // Idempotent: larian kedua = 0 perubahan, exit 0.
    expect(Artisan::call('diwan:audit-fixture', ['action' => 'cleanup', '--run' => AUDIT_RUN_UUID, '--json' => fixtureJsonPath()]))->toBe(0);
    expect(snapshotCounts())->toBe($before)
        ->and($smoke->fresh())->not->toBeNull()
        ->and($smoke->fresh()->slug)->toBe('smoke')
        ->and($smokeNode->fresh())->not->toBeNull()
        ->and(User::query()->where('email', 'admin-smoke@smoke.test')->exists())->toBeTrue();
});

test('cleanup enggan berjalan dengan inventori run lain (tiada padam silang larian)', function () {
    file_put_contents(fixtureJsonPath(), json_encode(['run_uuid' => 'ffffffff-1111-4222-8333-444444444444', 'created' => []]));
    expect(Artisan::call('diwan:audit-fixture', ['action' => 'cleanup', '--run' => AUDIT_RUN_UUID, '--json' => fixtureJsonPath()]))->toBe(1)
        ->and(Artisan::output())->toContain('bukan milik run uuid ini');
});

test('inventory read-only: melapor kiraan + e-mel superadmin SAHAJA (tiada kredensial)', function () {
    User::query()->create([
        'name' => 'Super', 'email' => 'super@ujian.test',
        'password' => bcrypt('rahsia-super'), 'is_active' => true, 'is_superadmin' => true,
    ]);
    $before = snapshotCounts();

    $exit = Artisan::call('diwan:audit-fixture', ['action' => 'inventory', '--run' => AUDIT_RUN_UUID]);
    $output = Artisan::output();

    expect($exit)->toBe(0)
        ->and(snapshotCounts())->toBe($before, 'inventory mesti read-only')
        ->and($output)->toContain('super@ujian.test')
        ->and($output)->not->toContain('rahsia-super');
});
