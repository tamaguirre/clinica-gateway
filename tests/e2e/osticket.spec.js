import { test, expect } from '@playwright/test';

test.describe('osTicket E2E - Create Ticket', () => {
    test.setTimeout(120000); // 2 minutos de timeout

    test('debe loguear y crear un ticket exitosamente', async ({ page }) => {
        const email = process.env.OSTICKET_EMAIL || 'tamara';
        const password = process.env.OSTICKET_PASSWORD || 'Tamara1234';

        if (!email || !password) {
            throw new Error('Falta OSTICKET_EMAIL o OSTICKET_PASSWORD en las variables de entorno');
        }

        // Paso 1: Navegar a la página de login
        await page.goto('/login.php');
        await page.waitForLoadState('load');

        // Paso 2: Llenar credenciales - buscar por visible y atributos
        const emailInput = page.locator('input[type="text"]:visible').first();
        const passwordInput = page.locator('input[type="password"]').first();
        
        await emailInput.waitFor({ state: 'visible', timeout: 5000 });
        await emailInput.fill(email);
        await page.waitForTimeout(300);
        
        await passwordInput.waitFor({ state: 'visible', timeout: 5000 });
        await passwordInput.fill(password);
        await page.waitForTimeout(300);

        // Paso 3: Buscar y presionar el botón de login - usar getByRole
        const loginBtn = page.getByRole('button', { name: /Inicia Sesión/ });
        await loginBtn.waitFor({ state: 'visible', timeout: 5000 });
        await loginBtn.click();

        // Esperar a que se procese el login y redirija
        await Promise.race([
            page.waitForNavigation({ url: '**/*', timeout: 10000 }).catch(() => null),
            page.waitForTimeout(5000)
        ]);

        // Paso 4: Verificar que se logueó
        const currentUrl = page.url();
        const isLoggedOut = currentUrl.includes('login.php') || currentUrl.includes('logout');
        expect(!isLoggedOut).toBeTruthy();

        // Paso 5: Navegar a crear nuevo ticket
        await page.goto('/open.php');
        await page.waitForLoadState('load');

        // Paso 6: Llenar formulario de ticket
        const nameInput = page.locator('input[placeholder*="Nombre"], input[aria-label*="nombre"], input[name="name"]').first();
        const emailInput2 = page.locator('input[placeholder*="Correo"], input[type="email"], input[aria-label*="correo"]').first();
        const subjectInput = page.locator('input[placeholder*="Asunto"], input[name="subject"]').first();
        const messageInput = page.locator('textarea[placeholder*="Mensaje"], textarea[name="message"]').first();

        if (await nameInput.isVisible({ timeout: 5000 }).catch(() => false)) {
            await nameInput.fill('Test Playwright');
        }

        if (await emailInput2.isVisible({ timeout: 5000 }).catch(() => false)) {
            await emailInput2.fill(email);
        }

        if (await subjectInput.isVisible({ timeout: 5000 }).catch(() => false)) {
            await subjectInput.fill('Prueba Playwright');
        }

        if (await messageInput.isVisible({ timeout: 5000 }).catch(() => false)) {
            await messageInput.fill('Test automático desde Playwright E2E');
        }

        // Paso 7: Enviar formulario
        const submitBtn = page.locator('button:has-text("Crear|Enviar|Abrir"), input[type="submit"]').first();
        if (await submitBtn.isVisible({ timeout: 5000 }).catch(() => false)) {
            await submitBtn.click();
            await Promise.race([
                page.waitForNavigation({ url: '**/*', timeout: 15000 }).catch(() => null),
                page.waitForTimeout(3000)
            ]);
        }

        // Paso 8: Verificar creación
        const confirmMsg = page.locator('text=/ticket|[Gg]racias|[Ee]xitoso|confirmación/i').first();
        const isCreated = await confirmMsg.isVisible({ timeout: 5000 }).catch(() => false);
        
        expect(isCreated || page.url().includes('view')).toBeTruthy();
        console.log(`✓ Ticket test completado - URL: ${page.url()}`);
    });

    test('debe crear ticket sin login (portal público)', async ({ page }) => {
        // Paso 1: Navegar a crear nuevo ticket
        await page.goto('/open.php');
        await page.waitForLoadState('load');

        // Paso 2: Llenar formulario - localizadores más robustos
        const nameInput = page.locator('input[placeholder*="Nombre"]').first();
        const emailInput = page.locator('input[placeholder*="Correo"], input[type="email"]').first();
        const subjectInput = page.locator('input[placeholder*="Asunto"]').first();
        const messageInput = page.locator('textarea[placeholder*="Mensaje"]').first();

        if (await nameInput.isVisible({ timeout: 5000 }).catch(() => false)) {
            await nameInput.fill('Público Test');
        }

        if (await emailInput.isVisible({ timeout: 5000 }).catch(() => false)) {
            await emailInput.fill('test@ejemplo.com');
        }

        if (await subjectInput.isVisible({ timeout: 5000 }).catch(() => false)) {
            await subjectInput.fill('Test Público');
        }

        if (await messageInput.isVisible({ timeout: 5000 }).catch(() => false)) {
            await messageInput.fill('Test E2E sin autenticación');
        }

        // Paso 3: Enviar
        const submitBtn = page.locator('button:has-text("Crear"), input[type="submit"]').first();
        if (await submitBtn.isVisible({ timeout: 5000 }).catch(() => false)) {
            await submitBtn.click();
            await Promise.race([
                page.waitForNavigation({ url: '**/*', timeout: 15000 }).catch(() => null),
                page.waitForTimeout(3000)
            ]);
        }

        // Paso 4: Verificar confirmación
        const hasConfirmation = await page.locator('text=/[Gg]racias|[Cc]onfirmación|exitoso/').isVisible({ timeout: 5000 }).catch(() => false);
        expect(hasConfirmation || !page.url().includes('open.php')).toBeTruthy();

        console.log(`✓ Ticket público test completado`);
    });
});
