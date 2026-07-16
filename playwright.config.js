import { defineConfig } from '@playwright/test';

export default defineConfig({
    testDir: './e2e',
    fullyParallel: false,
    workers: 1,
    timeout: 180_000,
    expect: { timeout: 30_000 },
    reporter: [['line']],
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
});
