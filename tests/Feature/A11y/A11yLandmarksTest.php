<?php

/**
 * F7 §8.2 (RR-04-01, axe `landmark-unique` moderate).
 *
 * Skrip a11y mesti dimuat sebagai entri BERASINGAN daripada runtime panduan. Ujian di sini
 * mengunci pemisahan itu daripada sisi pelayan; pengesahan bahawa `aria-label` benar-benar
 * dikenakan (dan `landmark-unique` = 0) berlaku dalam e2e, kerana ia kesan sisi-pelanggan.
 *
 * ⚠️ Ujian paling bernilai dalam fail ini ialah yang menjalankan halaman dengan
 * `DIWAN_GUIDANCE_ENABLED=false`. Tanpanya, "berasingan daripada help.js" hanyalah dakwaan
 * dalam komen.
 */

use App\Models\User;

beforeEach(function () {
    $this->mam = makeMosque('MAM', 'mam');
    $this->admin = makeMember($this->mam, 'admin_masjid', 'admin@mam.test');
});

/**
 * Bukti bahawa `vite.config.js` benar-benar dikemas — tanpa entri itu Vite tidak membina
 * fail dan `@vite(...)` melempar semasa render (kriteria siap F7).
 */
test('manifest Vite mengandungi entri a11y-landmarks selepas build', function () {
    $laluan = public_path('build/manifest.json');
    expect(file_exists($laluan))->toBeTrue('jalankan `npm run build` dahulu');

    $manifest = json_decode((string) file_get_contents($laluan), true, flags: JSON_THROW_ON_ERROR);

    expect($manifest)->toHaveKey('resources/js/a11y-landmarks.js');
    expect($manifest['resources/js/a11y-landmarks.js']['file'] ?? '')->toStartWith('assets/a11y-landmarks-');
});

test('panel tenant memuat skrip a11y', function () {
    $html = $this->actingAs($this->admin)->get('/app/mam')->assertOk()->getContent();

    expect($html)->toContain('a11y-landmarks-');
});

/**
 * ⚠️ Probe panel ADMIN mesti ujian BERASINGAN — bukan dataset bersama panel tenant.
 *
 * Punca direkod dalam F6-W5: hook banner onboarding (`AppPanelProvider.php`) diskop kepada
 * `Dashboard::class` yang DIKONGSI kedua-dua panel, dan konteks panel/tenant bocor dalam SATU
 * proses ujian. Melawat `/app/{tenant}` dahulu lalu `/admin` dalam ujian yang sama memberi
 * 403/500 yang BUKAN pepijat hidup (php-fpm menetapkan semula setiap permintaan).
 */
test('panel admin memuat skrip a11y', function () {
    $superadmin = User::query()->create([
        'name' => 'Pentadbir Platform',
        'email' => 'super-a11y@ujian.test',
        'password' => bcrypt('kata-laluan-ujian'),
        'is_superadmin' => true,
        'is_active' => true,
    ]);

    $html = $this->actingAs($superadmin)->get('/admin')->assertOk()->getContent();

    expect($html)->toContain('a11y-landmarks-');
});

/**
 * ⭐ Ujian yang membuktikan pemisahan itu NYATA.
 *
 * Dengan panduan dimatikan, butang pelancar panduan hilang — tetapi skrip a11y mesti KEKAL.
 * Jika seseorang kemudian memindahkan kod landmark ke dalam `help.js` "kerana ia sudah
 * sentiasa dimuat", ujian ini bertukar merah.
 */
test('skrip a11y KEKAL dimuat walaupun DIWAN_GUIDANCE_ENABLED=false', function () {
    config()->set('diwan.guidance.enabled', false);

    $html = $this->actingAs($this->admin)->get('/app/mam')->assertOk()->getContent();

    expect($html)->toContain('a11y-landmarks-');
});

/**
 * Skrip a11y didaftarkan melalui hooknya SENDIRI, bukan diselitkan ke dalam view panduan.
 * Jika seseorang menggabungkan kedua-duanya, view `a11y-assets` menjadi yatim dan ujian
 * ini menangkapnya.
 */
test('view a11y-assets wujud dan memuat entri a11y sahaja', function () {
    $laluan = resource_path('views/filament/a11y-assets.blade.php');
    expect(file_exists($laluan))->toBeTrue();

    $isi = (string) file_get_contents($laluan);
    expect($isi)->toContain("@vite('resources/js/a11y-landmarks.js')")
        ->and($isi)->not->toContain('help.js');
});

/**
 * Modul a11y tidak boleh mempunyai import: ia mesti kekal boleh dimuat walaupun setiap
 * dependensi lain gagal. Ini sebab utama ia dipisahkan daripada `help.js` (§8.2 (b)).
 */
test('modul a11y tiada import', function () {
    $sumber = (string) file_get_contents(resource_path('js/a11y-landmarks.js'));

    expect($sumber)->not->toMatch('/^\s*import\s/m');
});
