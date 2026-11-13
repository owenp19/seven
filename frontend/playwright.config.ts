import { defineConfig, devices } from '@playwright/test';

/**
 * SEVEN — configuración Playwright Test (pruebas visuales y funcionales).
 *
 * El servidor Angular se asume en ejecución en `ng serve` (puerto 4200 por
 * defecto). Cambiar `baseURL` si el entorno local usa otro puerto.
 * Las pruebas son ligeras: no reservan, no envían WhatsApp, no escriben datos.
 */
export default defineConfig({
  testDir: './e2e',
  snapshotDir: './e2e/snapshots',
  outputDir: './e2e/results',
  fullyParallel: false,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: 1,
  reporter: [
    ['list'],
    ['html', { outputFolder: 'e2e/report', open: 'never' }],
  ],
  use: {
    baseURL: 'http://localhost:4200',
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
    video: 'off',
    // Timeout reducido para no colgar la máquina si el servidor no responde.
    navigationTimeout: 15000,
    actionTimeout: 10000,
  },
  projects: [
    // Móvil
    {
      name: 'mobile-390',
      use: { ...devices['iPhone 13'], viewport: { width: 390, height: 844 } },
    },
    {
      name: 'mobile-430',
      use: { ...devices['Pixel 7'], viewport: { width: 430, height: 932 } },
    },
    // Tablet
    {
      name: 'tablet-768',
      use: { ...devices['iPad Mini'], viewport: { width: 768, height: 1024 } },
    },
    {
      name: 'tablet-1024',
      use: { ...devices['iPad Air'], viewport: { width: 1024, height: 768 } },
    },
    // Escritorio
    {
      name: 'desktop-1366',
      use: { ...devices['Desktop Chrome'], viewport: { width: 1366, height: 768 } },
    },
    {
      name: 'desktop-1440',
      use: { ...devices['Desktop Chrome'], viewport: { width: 1440, height: 900 } },
    },
    {
      name: 'desktop-1920',
      use: { ...devices['Desktop Chrome'], viewport: { width: 1920, height: 1080 } },
    },
  ],
  ...(process.env.SEVEN_PLAYWRIGHT_WEBSERVER === '1'
    ? {
        webServer: {
          command: 'npm start',
          url: 'http://localhost:4200',
          reuseExistingServer: true,
          timeout: 120000,
        },
      }
    : {}),
});
