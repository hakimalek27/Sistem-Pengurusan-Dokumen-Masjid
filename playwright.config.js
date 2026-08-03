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
    // production-*.spec.js SENGAJA di luar semua project — produksi sahaja, melalui wrapper
    // (allowlist bersebab dalam tests/Feature/PlanManifestTest.php).
    projects: [
        {
            name: 'ci-guidance',
            testMatch: [
                'e2e/ci-session-canary.spec.js',
                'e2e/guidance.spec.js',
                'e2e/registration.spec.js',
                'e2e/explore.spec.js',
            ],
        },
        {
            // F2 (§3.6 C11): ujian unit fungsi tulen — tiada pelayar, tiada server.
            name: 'unit',
            testMatch: ['e2e/step-advance-plan.spec.js'],
        },
        {
            name: 'ci-domain',
            testMatch: [
                'e2e/office-workflow.spec.js',
                'e2e/ddms-extended.spec.js',
            ],
        },
        {
            name: 'ci-ocr',
            testMatch: ['e2e/ocr-upload.spec.js'],
        },
        {
            name: 'guidance-full',
            testMatch: ['e2e/guidance-full.spec.js'],
        },
    ],
});
