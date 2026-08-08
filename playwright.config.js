import { defineConfig } from '@playwright/test';

// F0(iv)(e) — reporter JSON bersyarat-env (D11 #3): tanpa DIWAN_PW_JSON tingkah laku
// tempatan TIDAK berubah (line sahaja); CI menetapkannya PER STEP (bukan aras job) dan
// setiap gate mengesahkan failnya dengan scripts/audit/assert-playwright-json.mjs.
const reporter = process.env.DIWAN_PW_JSON
    ? [['line'], ['json', { outputFile: process.env.DIWAN_PW_JSON }]]
    : [['line']];

export default defineConfig({
    testDir: './e2e',
    fullyParallel: false,
    workers: 1,
    timeout: 180_000,
    expect: { timeout: 30_000 },
    reporter,
    use: {
        baseURL: process.env.E2E_BASE_URL ?? 'http://127.0.0.1:8092',
        browserName: 'chromium',
        channel: 'chrome',
        headless: true,
        navigationTimeout: 60_000,
        actionTimeout: 30_000,
        screenshot: 'only-on-failure',
        trace: 'retain-on-failure',
    },
    // F0(iv) — skop CI dibekukan sebagai project (BUKAN senarai dalam PR):
    //   unit          fungsi tulen (stepAdvancePlan) — pantas, tiada pelayar
    //   ci-guidance   lapis 1: canary sesi + smoke panduan (20 konteks) + registrasi + explore
    //   ci-domain     lapis 1b: aliran domain hujung-ke-hujung (P16-08)
    //   ci-ocr        lapis 1c: TIDAK required sehingga fixture tests/fixtures/ocr dikomit +
    //                 assert "tidak di-skip" (§1 F0(iv) syarat ci-ocr)
    //   guidance-full lapis 2: gate penuh G1–G5, dipandu GUIDANCE_SHARD (3 shard CI)
    // production-readonly.spec.js kekal di luar semua project (allowlist bersebab dalam
    // tests/Feature/PlanManifestTest.php). production-guidance-readonly.spec.js pula mempunyai
    // project BERSYARAT di bawah — ia perlukan satu untuk dipilih oleh `--project`, tetapi
    // project itu hanya wujud apabila E2E_PRODUCTION diset. CI tidak pernah menetapkannya.
    projects: [
        {
            name: 'ci-guidance',
            testMatch: [
                'e2e/ci-session-canary.spec.js',
                'e2e/guidance.spec.js',
                'e2e/guidance-f5.spec.js',
                'e2e/panel-landing.spec.js',
                'e2e/registration.spec.js',
                'e2e/explore.spec.js',
            ],
        },
        {
            // F2 (§3.6 C11): ujian unit fungsi tulen — tiada pelayar, tiada server.
            name: 'unit',
            testMatch: [
                'e2e/step-advance-plan.spec.js',
                'e2e/nav-target-plan.spec.js',
                'e2e/page-target-plan.spec.js',
                'e2e/viewer-control-plan.spec.js',
            ],
        },
        {
            name: 'ci-domain',
            testMatch: [
                'e2e/office-workflow.spec.js',
                'e2e/ddms-extended.spec.js',
                // F7 §8.5 — viewer dokumen dalam pelayar sebenar (had, clamp, cari, cetak,
                // keadaan ralat). Diletak dalam `ci-domain` kerana ia memandu aliran domain
                // penuh (muat naik -> Peti Masuk -> viewer), bukan sekadar imbasan a11y.
                'e2e/document-viewer.spec.js',
            ],
        },
        {
            // F7 §8.5 — larian axe sebenar (link-name / landmark-unique / empty-table-header)
            // pada 5 halaman × 2 viewport. Projek berasingan supaya kegagalan a11y boleh
            // dibaca tanpa menyelak keputusan domain.
            name: 'ci-a11y',
            testMatch: ['e2e/a11y-axe.spec.js'],
        },
        {
            name: 'ci-ocr',
            testMatch: ['e2e/ocr-upload.spec.js'],
        },
        {
            name: 'guidance-full',
            testMatch: ['e2e/guidance-full.spec.js'],
        },
        // 🔴 F8 §9.1a — DITAMBAH selepas penemuan bahawa runner produksi TIDAK BOLEH BERJALAN.
        //
        // `e2e/production-guidance-readonly.spec.js` sengaja diletakkan di luar setiap project
        // supaya CI tidak pernah menjalankannya, dan `PlanManifestTest` mengallowlistkannya
        // dengan sebab "HANYA melalui wrapper". Tetapi arahan TEPAT wrapper
        // (`run-production-guidance-readonly.ps1:87`) ialah
        //     playwright test e2e/production-guidance-readonly.spec.js --workers=1
        // dan Playwright menapis ikut PROJECT: fail yang tiada dalam mana-mana `testMatch`
        // memberi `Error: No tests found.` — DISAHKAN empirikal dengan menjalankan arahan itu.
        //
        // Jadi allowlist itu bercanggah dengan dirinya sendiri: spec dikecualikan daripada
        // project KERANA ia berjalan melalui wrapper, sedangkan wrapper memerlukan project.
        // Ia akan gagal pada saat pemilik akhirnya membekalkan kredensial.
        //
        // Project ini TIDAK dirujuk oleh `.github/workflows/ci.yml` — CI kekal tidak pernah
        // menjalankannya. Ia hanya memberi wrapper sesuatu untuk dipilih (`--project`).
        //
        // ⚠️ BERSYARAT. Spec ini melempar pada peringkat KUTIPAN apabila env produksi tiada,
        // dan kutipan yang melempar membatalkan keseluruhan larian — bukan satu project:
        //     npx playwright test --list   ->   Total: 0 tests in 0 files
        // Pendaftaran bersyarat mengeluarkannya daripada laluan kutipan biasa.
        //
        // ⚠️ Kejujuran ukuran: ini BUKAN satu-satunya sebab larian telanjang gagal.
        // `guidance-full.spec.js:37` melempar dengan cara yang sama apabila `GUIDANCE_SHARD`
        // tiada, dan itu SENGAJA (F0(iv)(e): "skip senyap ialah gate palsu"). Jadi
        // `npx playwright test` tanpa argumen kekal gagal selepas perubahan ini, dan ia tidak
        // diubah — CI sentiasa membekalkan shard melalui matriks. Yang diperbaiki di sini
        // hanyalah: spec produksi tidak lagi menjadi sebab KEDUA.
        //
        // Wrapper menetapkan E2E_PRODUCTION=1 pada proses anak (ps1:131) SEBELUM memanggil
        // `--project=production-readonly`, jadi project sentiasa wujud pada laluan yang sah
        // (disahkan: dengan env itu, larian mengadu `E2E_PROD_TENANT`, bukan project hilang).
        ...(process.env.E2E_PRODUCTION
            ? [{
                name: 'production-readonly',
                testMatch: ['e2e/production-guidance-readonly.spec.js'],
            }]
            : []),
    ],
});
