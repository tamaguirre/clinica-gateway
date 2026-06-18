# Playwright E2E Tests para osTicket

## Configuración

### 1. Variables de Entorno
Copia `.env.example` a `.env.test` (local) o configura las variables:

```bash
export OSTICKET_EMAIL="tu-email@clinica.com"
export OSTICKET_PASSWORD="tu-contraseña"
export OSTICKET_ADMIN_USER="admin"
export OSTICKET_ADMIN_PASSWORD="admin-password"
export OSTICKET_URL="https://clinicaciberseguridad.equipoweb.cl"
```

### 2. Ejecutar Tests

**Headless (sin UI):**
```bash
npm run test:e2e
```

**Con navegador visible:**
```bash
npx playwright test --headed
```

**Un test específico:**
```bash
npx playwright test osticket.spec.js -g "debe loguear y crear"
```

**Con modo debug:**
```bash
npx playwright test --debug
```

### 3. Ver Reporte HTML
Después de ejecutar los tests, abre el reporte:
```bash
npx playwright show-report
```

## Tests Incluidos

### Test 1: Login + Crear Ticket Autenticado
- ✅ Loguea con email/contraseña
- ✅ Navega a crear nuevo ticket
- ✅ Llena formulario (nombre, email, asunto, mensaje)
- ✅ Verifica que se creó el ticket

### Test 2: Crear Ticket Público
- ✅ Accede sin login a `/open.php`
- ✅ Llena formulario como usuario anónimo
- ✅ Verifica confirmación de creación

## Estructura
```
tests/e2e/
├── osticket.spec.js    # Tests principales
```

## Notas
- Los screenshots de fallos se guardan en `storage/app/playwright-report/`
- El timeout por defecto es 30s (ticket creation hasta 10s de confirmación)
- Los tests se ejecutan secuencialmente (fullyParallel: false)
