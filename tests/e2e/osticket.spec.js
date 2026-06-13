import { test, expect } from '@playwright/test';

test.describe('osTicket E2E - Autenticación y Tickets', () => {
    test.setTimeout(120000); // 2 minutos de timeout

    test('Login fallido con credenciales inválidas', async ({ page }) => {
        const invalidEmail = 'usuario_inexistente';
        const invalidPassword = 'PasswordIncorrecto123';

        // Paso 1: Navegar a login
        await page.goto('/login.php');
        await page.waitForLoadState('load');

        // Paso 2: Llenar credenciales inválidas
        const emailInput = page.locator('input[type="text"]:visible').first();
        const passwordInput = page.locator('input[type="password"]').first();
        
        await emailInput.waitFor({ state: 'visible', timeout: 5000 });
        await emailInput.fill(invalidEmail);
        
        await passwordInput.waitFor({ state: 'visible', timeout: 5000 });
        await passwordInput.fill(invalidPassword);

        // Paso 3: Presionar login
        const loginBtn = page.getByRole('button', { name: /Inicia Sesión/ });
        await loginBtn.waitFor({ state: 'visible', timeout: 5000 });
        await loginBtn.click();

        // Paso 4: Verificar que aparece mensaje de error
        await page.waitForTimeout(1000);
        const errorMessage = page.locator('text=/Acceso denegado|error|invalido|incorrecto/i');
        await expect(errorMessage.first()).toBeVisible({ timeout: 5000 });

        // Verificar que seguimos en /login.php (no redirige)
        expect(page.url()).toContain('/login.php');
        
        console.log('✅ Login fallido detectado correctamente con credenciales inválidas');
    });

    test('Login exitoso', async ({ page }) => {
        const email = process.env.OSTICKET_EMAIL || 'tamara';
        const password = process.env.OSTICKET_PASSWORD || 'Tamara1234';

        if (!email || !password) {
            throw new Error('Falta OSTICKET_EMAIL o OSTICKET_PASSWORD en las variables de entorno');
        }

        // Paso 1: Navegar a login
        await page.goto('/login.php');
        await page.waitForLoadState('load');

        // Paso 2: Llenar credenciales correctas
        const emailInput = page.locator('input[type="text"]:visible').first();
        const passwordInput = page.locator('input[type="password"]').first();
        
        await emailInput.waitFor({ state: 'visible', timeout: 5000 });
        await emailInput.fill(email);
        
        await passwordInput.waitFor({ state: 'visible', timeout: 5000 });
        await passwordInput.fill(password);

        // Paso 3: Presionar login
        const loginBtn = page.getByRole('button', { name: /Inicia Sesión/ });
        await loginBtn.waitFor({ state: 'visible', timeout: 5000 });
        await loginBtn.click();

        // Paso 4: Esperar redireccionamiento a /tickets.php
        await page.waitForURL('**/tickets.php', { timeout: 10000 });
        
        // Paso 5: Verificar que está loguado
        await expect(page.locator('text=Welcome Tamara')).toBeVisible({ timeout: 5000 });
        
        console.log('✅ Login exitoso verificado');
    });

    test('debe loguear y crear un ticket exitosamente', async ({ page }) => {
        const email = process.env.OSTICKET_EMAIL || 'tamara';
        const password = process.env.OSTICKET_PASSWORD || 'Tamara1234';

        if (!email || !password) {
            throw new Error('Falta OSTICKET_EMAIL o OSTICKET_PASSWORD en las variables de entorno');
        }

        // Paso 1: Navegar a login
        await page.goto('/login.php');
        await page.waitForLoadState('load');

        // Paso 2: Llenar credenciales
        const emailInput = page.locator('input[type="text"]:visible').first();
        const passwordInput = page.locator('input[type="password"]').first();
        
        await emailInput.waitFor({ state: 'visible', timeout: 5000 });
        await emailInput.fill(email);
        
        await passwordInput.waitFor({ state: 'visible', timeout: 5000 });
        await passwordInput.fill(password);

        // Paso 3: Presionar login
        const loginBtn = page.getByRole('button', { name: /Inicia Sesión/ });
        await loginBtn.waitFor({ state: 'visible', timeout: 5000 });
        await loginBtn.click();

        // Esperar redireccionamiento a /tickets.php
        await page.waitForURL('**/tickets.php', { timeout: 10000 });
        
        // Verificar que está loguado (debe mostrar nombre)
        await expect(page.locator('text=Welcome Tamara')).toBeVisible({ timeout: 5000 });

        // Paso 4: Click en "Abrir un nuevo Ticket"
        const openNewTicketLink = page.getByRole('link', { name: /Abrir un nuevo Ticket/ });
        await openNewTicketLink.click();
        await page.waitForURL('**/open.php', { timeout: 10000 });

        // Paso 5: Llenar formulario de ticket
        const titleInput = page.locator('input[type="text"]').filter({ hasNot: page.locator('input[type="hidden"]') }).nth(0);
        await titleInput.waitFor({ state: 'visible', timeout: 5000 });
        await titleInput.fill('Test Playwright E2E');

        // Llenar descripción - encontrar el contenteditable y hacer click + escribir
        await page.waitForTimeout(500);
        
        const descContent = 'Ticket de prueba creado automáticamente por Playwright';
        
        // Encontrar el contenteditable (editor WYSIWYG)
        const descriptionEditor = page.locator('[contenteditable="true"]').nth(0);
        await descriptionEditor.waitFor({ state: 'visible', timeout: 5000 });
        
        // Click en el editor
        await descriptionEditor.click();
        await page.waitForTimeout(300);
        
        // Seleccionar todo el contenido existente y borrarlo
        await page.keyboard.press('Control+A');
        await page.waitForTimeout(100);
        await page.keyboard.press('Delete');
        await page.waitForTimeout(100);
        
        // Escribir el contenido
        await page.keyboard.type(descContent);
        await page.waitForTimeout(500);
        
        // Click fuera del editor para trigger blur event
        await page.locator('body').click({ position: { x: 0, y: 0 } });
        await page.waitForTimeout(300);

        // Paso 6: Click en "Crear Ticket"
        await page.waitForTimeout(500);
        const createBtn = page.getByRole('button', { name: /Crear Ticket/ });
        
        // Scroll al botón
        await createBtn.scrollIntoViewIfNeeded();
        await page.waitForTimeout(300);
        
        console.log(`✓ Clickeando botón "Crear Ticket"...`);
        await createBtn.click();

        // Esperar redireccionamiento a la página del ticket creado
        await page.waitForURL('**/tickets.php?id=*', { timeout: 20000 });

        // Paso 7: Verificar que el ticket se creó
        const ticketHeading = page.locator('h1');
        await expect(ticketHeading).toBeVisible({ timeout: 5000 });
        
        // Extraer el título del ticket
        const headingText = await ticketHeading.first().textContent();
        expect(headingText?.includes('Test Playwright E2E')).toBeTruthy();
        
        // Extraer el número de ticket del URL (forma más confiable)
        const url = page.url();
        const urlMatch = url.match(/id=(\d+)/);
        const ticketNum = urlMatch ? urlMatch[1] : 'desconocido';

        console.log(`✅ Ticket creado exitosamente: #${ticketNum}`);
        console.log(`✅ URL: ${url}`);
        console.log(`✅ Título: ${headingText}`);
        
        expect(ticketNum).not.toBe('desconocido');
    });

    test('Creación de ticket fallida sin descripción', async ({ page }) => {
        const email = process.env.OSTICKET_EMAIL || 'tamara';
        const password = process.env.OSTICKET_PASSWORD || 'Tamara1234';

        if (!email || !password) {
            throw new Error('Falta OSTICKET_EMAIL o OSTICKET_PASSWORD en las variables de entorno');
        }

        // Paso 1: Login
        await page.goto('/login.php');
        await page.waitForLoadState('load');

        const emailInput = page.locator('input[type="text"]:visible').first();
        const passwordInput = page.locator('input[type="password"]').first();
        
        await emailInput.fill(email);
        await passwordInput.fill(password);

        const loginBtn = page.getByRole('button', { name: /Inicia Sesión/ });
        await loginBtn.click();
        await page.waitForURL('**/tickets.php', { timeout: 10000 });

        // Paso 2: Navegar a crear ticket
        const openNewTicketLink = page.getByRole('link', { name: /Abrir un nuevo Ticket/ });
        await openNewTicketLink.click();
        await page.waitForURL('**/open.php', { timeout: 10000 });

        // Paso 3: Llenar SOLO el título, SIN descripción
        const titleInput = page.locator('input[type="text"]').filter({ hasNot: page.locator('input[type="hidden"]') }).nth(0);
        await titleInput.waitFor({ state: 'visible', timeout: 5000 });
        await titleInput.fill('Test Fallo - Sin Descripción');

        // NO llenar el descripción

        // Paso 4: Intentar crear ticket sin descripción
        await page.waitForTimeout(500);
        const createBtn = page.getByRole('button', { name: /Crear Ticket/ });
        await createBtn.scrollIntoViewIfNeeded();
        await page.waitForTimeout(300);
        
        console.log(`✓ Intentando crear ticket sin descripción...`);
        await createBtn.click();

        // Paso 5: Verificar que se muestra mensaje de error de validación
        await page.waitForTimeout(1000);
        const errorBox = page.locator('text=/imposible|error|requerido|obligatorio/i');
        await expect(errorBox.first()).toBeVisible({ timeout: 5000 });

        // Verificar que seguimos en /open.php (no se redirige)
        expect(page.url()).toContain('/open.php');
        
        console.log('✅ Creación de ticket fallida detectada correctamente (falta descripción)');
    });

    test('Visualizar y buscar ticket en listado', async ({ page }) => {
        const email = process.env.OSTICKET_EMAIL || 'tamara';
        const password = process.env.OSTICKET_PASSWORD || 'Tamara1234';

        if (!email || !password) {
            throw new Error('Falta OSTICKET_EMAIL o OSTICKET_PASSWORD en las variables de entorno');
        }

        // Paso 1: Login
        await page.goto('/login.php');
        await page.waitForLoadState('load');

        const emailInput = page.locator('input[type="text"]:visible').first();
        const passwordInput = page.locator('input[type="password"]').first();
        
        await emailInput.fill(email);
        await passwordInput.fill(password);

        const loginBtn = page.getByRole('button', { name: /Inicia Sesión/ });
        await loginBtn.click();
        await page.waitForURL('**/tickets.php', { timeout: 10000 });
        
        // Paso 2: Verificar que estamos en la página de tickets
        await expect(page.locator('h1')).toBeVisible({ timeout: 5000 });
        console.log(`✓ En página de tickets`);

        // Paso 3: Buscar tickets por búsqueda o verificar que hay tickets en la tabla
        await page.waitForTimeout(500);
        
        // Buscar campo de búsqueda
        const searchInput = page.locator('input[type="search"]').first();
        
        if (await searchInput.isVisible()) {
            console.log(`✓ Campo de búsqueda encontrado`);
            
            // Buscar por título del ticket de prueba
            await searchInput.fill('Test Playwright');
            await page.waitForTimeout(1000);
            
            // Verificar que hay resultados
            const tableRows = page.locator('tbody tr');
            const rowCount = await tableRows.count();
            
            if (rowCount > 0) {
                console.log(`✅ Tickets encontrados en búsqueda: ${rowCount} fila(s)`);
                const firstRow = tableRows.first();
                const rowText = await firstRow.textContent();
                console.log(`✅ Primer resultado: ${rowText?.substring(0, 100)}`);
                expect(rowText?.includes('Test Playwright')).toBeTruthy();
            } else {
                console.log(`⚠️  No hay resultados exactos para "Test Playwright", verificando tabla general`);
            }
        } else {
            console.log(`✓ Sin campo de búsqueda visible, verificando tabla de tickets`);
        }

        // Paso 4: Verificar tabla de tickets existe
        const ticketsTable = page.locator('table');
        await expect(ticketsTable).toBeVisible({ timeout: 5000 });
        
        const tableBody = page.locator('tbody');
        const tickets = await tableBody.locator('tr').count();
        
        console.log(`✅ Página de tickets visible con ${tickets} ticket(s) en la tabla`);
        expect(tickets).toBeGreaterThan(0);
    });

});


