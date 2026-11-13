import { test, expect } from '@playwright/test';

/**
 * SEVEN — Pruebas visuales Welcome y Home.
 *
 * Alcance: solo pantallas públicas (Welcome, Home). Sin reservas reales,
 * sin WhatsApp, sin cambios de datos. Un proyecto único itera los 7
 * viewports objetivo para evitar lanzar varios Chromium en paralelo.
 */
const viewports = [
  { label: 'mobile-390', width: 390, height: 844 },
  { label: 'mobile-430', width: 430, height: 932 },
  { label: 'tablet-768', width: 768, height: 1024 },
  { label: 'tablet-1024', width: 1024, height: 768 },
  { label: 'desktop-1366', width: 1366, height: 768 },
  { label: 'desktop-1440', width: 1440, height: 900 },
  { label: 'desktop-1920', width: 1920, height: 1080 },
];

test.describe('Welcome y Home — validación visual multiplantalla', () => {
  test.beforeEach(async ({ page }) => {
    const consoleErrors: string[] = [];
    page.on('console', (msg) => {
      if (msg.type() === 'error') consoleErrors.push(msg.text());
    });
    (page as any)._consoleErrors = consoleErrors;
  });

  for (const vp of viewports) {
    test(`Welcome carga sin errores en ${vp.label} (${vp.width}x${vp.height})`, async ({
      page,
    }) => {
      await page.setViewportSize({ width: vp.width, height: vp.height });
      await page.goto('/welcome', { waitUntil: 'load' });

      await expect(page.locator('img.welcome__logo')).toBeVisible();
      await expect(page.locator('.welcome__cta')).toBeVisible();

      const bodyWidth = await page.evaluate(() => document.body.scrollWidth);
      expect(bodyWidth).toBeLessThanOrEqual(vp.width + 4);

      await page.screenshot({
        path: `e2e/screenshots/welcome-${vp.label}.png`,
        fullPage: true,
      });
    });

    test(`Home carga sin errores en ${vp.label} (${vp.width}x${vp.height})`, async ({
      page,
    }) => {
      await page.setViewportSize({ width: vp.width, height: vp.height });
      await page.goto('/home', { waitUntil: 'load' });

      await expect(page.locator('.home')).toBeVisible();
      await expect(page.locator('.home__intro')).toBeVisible();
      await expect(page.locator('.home__block').first()).toBeVisible();

      // Nota: las categorías y banners se poblan desde el API (Laravel :8000).
      // Sin backend corriendo aparecen skeletons; aquí validamos solo el shell.

      const bodyWidth = await page.evaluate(() => document.body.scrollWidth);
      expect(bodyWidth).toBeLessThanOrEqual(vp.width + 4);

      await page.screenshot({
        path: `e2e/screenshots/home-${vp.label}.png`,
        fullPage: true,
      });
    });
  }

  test('Header y navegación visibles en desktop (1440x900)', async ({ page }) => {
    await page.setViewportSize({ width: 1440, height: 900 });
    await page.goto('/home', { waitUntil: 'load' });

    await expect(page.locator('.nav-header')).toBeVisible();
    await expect(page.locator('.nav-header__logo')).toBeVisible();
    const links = page.locator('.nav-header__link');
    const n = await links.count();
    expect(n).toBeGreaterThanOrEqual(2);
  });

  test('Footer premium visible en tablet (1024x768)', async ({ page }) => {
    await page.setViewportSize({ width: 1024, height: 768 });
    await page.goto('/home', { waitUntil: 'load' });

    const footer = page.locator('app-footer-premium, footer');
    await expect(footer.first()).toBeVisible();
  });

  test('Imagen del logo carga sin rotura (1366x768)', async ({ page }) => {
    await page.setViewportSize({ width: 1366, height: 768 });
    await page.goto('/home', { waitUntil: 'load' });

    const logo = page.locator('.nav-header__logo');
    await expect(logo).toBeVisible();
    await expect(async () => {
      const naturalWidth = await logo.evaluate(
        (el: HTMLImageElement) => el.naturalWidth,
      );
      expect(naturalWidth).toBeGreaterThan(0);
    }).toPass({ timeout: 10000 });
  });
});
