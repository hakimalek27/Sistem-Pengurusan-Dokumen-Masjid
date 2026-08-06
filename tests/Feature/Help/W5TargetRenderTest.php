<?php

/**
 * F6-W5 (§7.2 gate registry (b)) — sasaran BAHARU W5 mesti benar-benar wujud dalam HTML yang
 * dirender, bukan sekadar tersenarai dalam `resources/help/targets.json`.
 *
 * Sasaran yang dipasang oleh `decorateTargets()` dalam PELAYAR (`classnode-search`,
 * `sensitive-log-search`, `tickets-search`, `dashboard-stats`, `platform-*` carian) TIDAK
 * diuji di sini — HTML pelayan tidak pernah membawanya. Ia dikunci oleh
 * `PageTargetSelectorTest` (kelas vendor sauhnya) dan oleh shard e2e.
 */

use App\Models\MosqueActivityLog;
use App\Models\SensitiveAccessLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->mam = makeMosque('MAM', 'mam');
    $this->admin = makeMember($this->mam, 'admin_masjid', 'admin@mam.test');
    $this->nod = makeNode($this->mam, '100-4', 'dalaman');
});

/** Sasaran yang dirender dalam keadaan LALAI — tanpa satu baris data pun (pelajaran W1). */
dataset('sasaran-keadaan-lalai', [
    'dashboard-checklist' => ['/app/mam', 'dashboard-checklist'],
    'search-text' => ['/app/mam/carian', 'search-text'],
    'search-parties' => ['/app/mam/carian', 'search-parties'],
    'search-submit' => ['/app/mam/carian', 'search-submit'],
    'search-save' => ['/app/mam/carian', 'search-save'],
    'search-saved' => ['/app/mam/carian', 'search-saved'],
    'members-list' => ['/app/mam/ahli-peranan', 'members-list'],
    'mosque-settings-profile' => ['/app/mam/tetapan-masjid', 'mosque-settings-profile'],
    'mosque-settings-whatsapp' => ['/app/mam/tetapan-masjid', 'mosque-settings-whatsapp'],
    'report-breakdown' => ['/app/mam/laporan', 'report-breakdown'],
    'analytics-metrics' => ['/app/mam/analitik-bantuan', 'analytics-metrics'],
]);

test('sasaran keadaan LALAI wujud tepat sekali tanpa sebarang data', function (string $laluan, string $target) {
    $html = $this->actingAs($this->admin)->get($laluan)->assertOk()->getContent();

    expect(substr_count($html, 'data-help-target="'.$target.'"'))->toBe(1,
        "{$laluan}: sasaran {$target} mesti wujud TEPAT SEKALI dalam keadaan lalai");
})->with('sasaran-keadaan-lalai');

test('members-role dan members-actions ada pada ahli pertama yang BUKAN superadmin', function () {
    // Baris pertama boleh jadi superadmin, dan dropdown "Tindakan" baris itu tidak dirender
    // langsung (`@unless`). `$loop->first` akan meletakkan sasaran pada baris yang tiada
    // kawalan — keluarga defect `disposal-actions` W4 (sorotan pada sel kosong).
    $super = User::query()->create([
        'name' => 'Super', 'email' => 'super@w5.test', 'password' => bcrypt('x'),
        'is_superadmin' => true, 'is_active' => true,
    ]);
    $this->mam->users()->syncWithoutDetaching([$super->id => ['role' => 'admin_masjid', 'joined_at' => now()]]);

    $html = $this->actingAs($this->admin)->get('/app/mam/ahli-peranan')->assertOk()->getContent();

    expect(substr_count($html, 'data-help-target="members-actions"'))->toBe(1,
        'members-actions mesti unik')
        ->and(substr_count($html, 'data-help-target="members-role"'))->toBe(1,
            'members-role mesti unik');

    // Bukti KEDUDUKAN, bukan bilangan: sasaran mesti berada dalam baris yang benar-benar
    // mempunyai dropdown Tindakan. Jika ia mendarat pada baris superadmin, tiada butang
    // "Tindakan" selepasnya dalam potongan itu.
    // Skop STRUKTUR (`</td>`), bukan tetingkap bait tetap — pelajaran W3: kalibrasi pada
    // bilangan aksara hanyut apabila indentasi Blade atau markup vendor berubah.
    $pos = strpos($html, 'data-help-target="members-actions"');
    $sel = substr($html, $pos, max(0, strpos($html, '</td>', $pos) - $pos));
    expect(str_contains($sel, 'Tindakan'))->toBeTrue(
        'members-actions tidak berada pada pencetus dropdown Tindakan');
});

test('log-time ada pada sel masa baris pertama dan UNIK', function () {
    foreach (['Aktiviti pertama', 'Aktiviti kedua'] as $desc) {
        MosqueActivityLog::query()->create([
            'mosque_id' => $this->mam->id,
            'actor_id' => $this->admin->id,
            'actor_name' => $this->admin->name,
            'action' => 'record_uploaded',
            'description' => $desc,
        ]);
    }

    $html = $this->actingAs($this->admin)->get('/app/mam/log-aktiviti')->assertOk()->getContent();

    expect(substr_count($html, 'data-help-target="log-time"'))->toBe(1,
        'log-time tidak unik — corak baris1() tidak berkuat kuasa');
});

test('memo baris1 MosqueActivityLogsTable diset semula setiap render', function () {
    // Regresi W3 (sifat statik = hayat PROSES). Kelas ini mengisytiharkan memo sejak W1
    // TETAPI tidak pernah menetapkannya semula sehingga W5. Ujian menuntut render KEDUA,
    // dengan baris pertama BERBEZA, tetap menandakan satu sel.
    $lama = MosqueActivityLog::query()->create([
        'mosque_id' => $this->mam->id, 'actor_id' => $this->admin->id,
        'actor_name' => $this->admin->name, 'action' => 'record_uploaded',
        'description' => 'Aktiviti lama',
    ]);
    // `MosqueActivityLog::booted()` melarang `updating` — log audit memang tidak boleh
    // diubah. Tarikh ditolak ke belakang melalui query builder, memintas model dengan sengaja
    // dan HANYA dalam ujian, supaya susunan baris deterministik.
    DB::table('mosque_activity_logs')->where('id', $lama->id)->update(['created_at' => now()->subDay()]);

    $this->actingAs($this->admin)->get('/app/mam/log-aktiviti')->assertOk();

    // Baris BAHARU menjadi baris pertama (defaultSort created_at desc).
    MosqueActivityLog::query()->create([
        'mosque_id' => $this->mam->id, 'actor_id' => $this->admin->id,
        'actor_name' => $this->admin->name, 'action' => 'record_uploaded',
        'description' => 'Aktiviti terkini',
    ]);

    $html = $this->actingAs($this->admin)->get('/app/mam/log-aktiviti')->assertOk()->getContent();

    // ⚠️ BILANGAN TIDAK PERNAH DAPAT MENANGKAP KECACATAN INI — dibuktikan dengan memasang
    // semula kod lama: memo yang basi menandakan baris KEDUA, jadi `substr_count` kekal 1 dan
    // ujian lulus secara palsu. Ini pengulangan tepat pelajaran W4. Yang berubah ialah
    // KEDUDUKAN, jadi assertion mesti menguji URUTAN dalam aliran HTML: sasaran mesti berada
    // SEBELUM baris lama.
    expect(substr_count($html, 'data-help-target="log-time"'))->toBe(1,
        'log-time tidak unik pada render kedua');

    // Sauh mesti teks baris PERTAMA, bukan baris lama: dalam baris yang SAMA lajur masa
    // sentiasa mendahului lajur Aktiviti, jadi membandingkan dengan "Aktiviti lama" lulus
    // walaupun sasaran berada pada baris kedua. (Versi pertama assertion ini melakukan
    // kesilapan itu dan LULUS dengan regresi dipasang — disemak, bukan diandaikan.)
    $posSasaran = strpos($html, 'data-help-target="log-time"');
    $posBarisPertama = strpos($html, 'Aktiviti terkini');
    expect($posSasaran)->toBeLessThan($posBarisPertama,
        'log-time bukan pada baris PERTAMA — memo statik tidak diset semula dalam configure()');
});

test('sensitive-log-target ada pada sel Rekod, berasingan daripada sensitive-log-record', function () {
    $fail = makeFile($this->mam, $this->nod, 'dalaman');
    $rekod = makeRecord($this->mam, $fail, 'dalaman', 'surat_menyurat', ['title' => 'Rekod sulit diakses']);
    SensitiveAccessLog::query()->create([
        'mosque_id' => $this->mam->id, 'is_superadmin' => false, 'user_id' => $this->admin->id,
        'record_id' => $rekod->id, 'action' => 'view', 'ip' => '127.0.0.1', 'user_agent' => 'ujian',
    ]);

    $html = $this->actingAs($this->admin)->get('/app/mam/sensitive-access-logs')->assertOk()->getContent();

    expect(substr_count($html, 'data-help-target="sensitive-log-target"'))->toBe(1)
        ->and(substr_count($html, 'data-help-target="sensitive-log-record"'))->toBe(1);
});

test('regfiles-status unik walaupun ada beberapa fail', function () {
    makeFile($this->mam, $this->nod, 'dalaman');
    makeFile($this->mam, $this->nod, 'dalaman');

    $html = $this->actingAs($this->admin)->get('/app/mam/registry-files')->assertOk()->getContent();

    expect(substr_count($html, 'data-help-target="regfiles-status"'))->toBe(1);
});

test('sasaran seksyen panel admin wujud', function () {
    $super = User::query()->create([
        'name' => 'Super', 'email' => 'super-admin@w5.test', 'password' => bcrypt('x'),
        'is_superadmin' => true, 'is_active' => true,
    ]);

    foreach ([
        '/admin/status-sambungan' => 'platform-channels',
        '/admin/whatsapp-platform' => 'platform-whatsapp',
        '/admin/tetapan-platform' => 'platform-settings',
        '/admin/analitik-bantuan' => 'analytics-metrics',
    ] as $laluan => $target) {
        $html = $this->actingAs($super)->get($laluan)->assertOk()->getContent();
        expect(substr_count($html, 'data-help-target="'.$target.'"'))->toBe(1,
            "{$laluan}: sasaran {$target} tiada atau tidak unik");
    }
});
