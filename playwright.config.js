import { defineConfig, devices } from '@playwright/test';
import dotenv from 'dotenv';
import path from 'path';

/**
 * Carga las variables de entorno desde .env.test
 */
dotenv.config({ path: path.resolve(process.cwd(), '.env.test') });

export default defineConfig({
    testDir: './tests/e2e',
    timeout: 120_000,  // 2 minutos por test
    expect: { timeout: 10_000 },
    fullyParallel: false,
    retries: 0,  // Sin reintentos para debugging
    reporter: [['list'], ['html', { outputFolder: 'storage/app/public/playwright-report', open: 'never' }]],

    use: {
        baseURL: process.env.OSTICKET_URL ?? 'https://clinicaciberseguridad.equipoweb.cl',
        screenshot: 'only-on-failure',
        video: 'off',
        headless: true,
        actionTimeout: 10_000,
        navigationTimeout: 30_000,
    },

    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
        },
    ],
});
