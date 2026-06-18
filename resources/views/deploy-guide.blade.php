@extends('layouts.manual')
@section('title', 'Manual de Despliegue')
@section('header-title', 'Despliegue y Configuración Técnica')

@section('content')
    <nav class="mb-8 p-4 bg-gray-50 rounded-lg border border-gray-200">
        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-2">Contenido</h3>
        <ul class="space-y-1">
            <li><a href="#requisitos-entorno" class="text-blue-700 hover:underline flex items-center">1. Requisitos del Entorno</a></li>
            <li><a href="#configuracion-entorno" class="text-blue-700 hover:underline flex items-center">2. Configuración de Entorno (.env)</a></li>
            <li><a href="#automatizacion-categorizacion" class="text-blue-700 hover:underline flex items-center">3. Automatización de Categorización</a></li>
            <li><a href="#benchmarking-reporte" class="text-blue-700 hover:underline flex items-center">4. Benchmarking y Reporte de IA</a></li>
            <li><a href="#pruebas-automatizadas" class="text-blue-700 hover:underline flex items-center">5. Pruebas Automatizadas</a></li>
            <li><a href="#repositorio-despliegue" class="text-blue-700 hover:underline flex items-center">6. Repositorio y Despliegue (Git + cPanel)</a></li>
        </ul>
    </nav>

    <section id="requisitos-entorno" class="mb-12">
        <h2 class="text-2xl font-bold text-blue-800 mb-4">1. Requisitos del Entorno</h2>
        <ul class="list-disc ml-6 space-y-2 text-gray-700">
            <li><strong>PHP 8.2+</strong> con extensiones: <code>mbstring</code>, <code>curl</code>, <code>xml</code>, <code>dom</code>, <code>fileinfo</code>.</li>
            <li><strong>Composer</strong> para gestión de dependencias.</li>
            <li><strong>Node.js y NPM</strong> para la compilación y gestión de assets del frontend.</li>
        </ul>
        <div class="bg-blue-50 p-4 rounded-lg border border-blue-200 mt-4 text-sm text-blue-800">
            <strong>Nota:</strong> No se requiere instalar MySQL, ya que el sistema utiliza la base de datos nativa gestionada por osTicket.
        </div>
    </section>

    <section id="configuracion-entorno" class="mb-12">
        <h2 class="text-2xl font-bold text-blue-800 mb-4">2. Configuración de Entorno (.env)</h2>
        <pre class="bg-gray-800 text-white p-6 rounded-lg overflow-x-auto text-sm">
# URL de la API de osTicket
OSTICKET_URL="http://localhost:8080/api/http.php/tickets.json"
OSTICKET_API_KEY="C6210A463085CFC451815ABFBD84C8D0"

# URL del servidor Ollama (VPS Digital Ocean)
OLLAMA_URL="206.189.192.107:11434"
OLLAMA_MODEL="qwen2.5:0.5b"

# Número de tópico general para la IA
APP_GENERAL_TOPIC=16
        </pre>
    </section>

    <section id="automatizacion-categorizacion" class="mb-12">
        <h2 class="text-2xl font-bold text-blue-800 mb-4">3. Automatización de Categorización</h2>
        <p class="text-gray-700">El proceso de análisis por IA se ejecuta automáticamente mediante un <strong>Cron Job</strong> configurado en cPanel que se dispara cada <strong>1 minuto</strong> para gestionar las tareas programadas:</p>
        
        <code class="block bg-gray-100 p-3 mt-2 rounded border font-mono text-sm">
            * * * * * cd /ruta/al/proyecto && php artisan schedule:run >> /dev/null 2>&1
        </code>
        
        <p class="text-gray-700 mt-4">
            Este comando ejecuta la lógica de categorización internamente. Recuerda que el driver por defecto es <code>api</code> (no requiere configuración adicional). Si deseas cambiarlo, puedes ajustar la configuración en el sistema; para otros drivers específicos como <code>python</code>, asegúrate de que el entorno tenga las dependencias necesarias.
        </p>
        
        <img src="/img/cron-job.png" alt="Esquema de Automatización" class="w-full rounded-lg border-2 border-dashed mt-6">
    </section>

    <section id="benchmarking-reporte" class="mb-12">
        <h2 class="text-2xl font-bold text-blue-800 mb-4">4. Benchmarking y Reporte de IA</h2>
        <p class="text-gray-700 mb-4">Para validar el rendimiento y la precisión del modelo, el sistema permite realizar pruebas masivas mediante un dataset de entrenamiento/validación:</p>
        
        <code class="block bg-gray-100 p-3 rounded border font-mono text-sm">
            php artisan app:ticket-categorization --from-json=storage/app/tickets-dataset.json --driver=[api|python]
        </code>
        
        <div class="mt-6 space-y-4 text-gray-700">
            <p><strong>Detalles del reporte generado:</strong></p>
            <ul class="list-disc ml-6 space-y-1">
                <li><strong>Métricas de Precisión:</strong> Contador de clasificaciones correctas vs. incorrectas.</li>
                <li><strong>Rendimiento de Hardware:</strong> Monitoreo de consumo de CPU y RAM durante el procesamiento.</li>
                <li><strong>Flexibilidad de Drivers:</strong> 
                    <ul class="list-circle ml-6 mt-1 text-sm">
                        <li><code>api</code>: Inferencia vía HTTP (por defecto).</li>
                        <li><code>python</code>: Inferencia mediante subprocesos con el SDK de Ollama (ideal para tests de rendimiento pesado).</li>
                    </ul>
                </li>
            </ul>
        </div>

        <div class="mt-8 p-6 bg-indigo-50 rounded-xl border border-indigo-200">
            <h3 class="font-bold text-indigo-900 mb-2">Visualización de Resultados</h3>
            <p class="text-gray-700 mb-4">
                Una vez ejecutado el proceso, los resultados detallados del benchmarking se encuentran disponibles para su análisis en el panel de reportes:
            </p>
            <a href="/ticket-report" target="_blank" 
               class="inline-block bg-indigo-700 text-white px-6 py-2 rounded-lg font-semibold hover:bg-indigo-800 transition">
                Ir al Reporte de IA
            </a>
        </div>
    </section>

    <section id="pruebas-automatizadas" class="mb-12">
        <h2 class="text-2xl font-bold text-blue-800 mb-4">5. Pruebas Automatizadas</h2>
        <p class="text-gray-700 mb-4">El proyecto integra un suite de pruebas para garantizar la estabilidad del sistema:</p>
        
        <div class="space-y-6">
            <div>
                <h3 class="font-bold text-gray-800">A. Pruebas Unitarias y Funcionales (PHPUnit)</h3>
                <p class="text-sm text-gray-600 mb-2">Valida la lógica de negocio y la integración con la API de osTicket.</p>
                <div class="bg-gray-100 p-3 rounded border font-mono text-sm space-y-2">
                    <p class="text-gray-800"># Ejecutar solo pruebas unitarias:</p>
                    <code class="block text-blue-800">php artisan test --unit</code>
                    
                    <p class="text-gray-800 mt-2"># Ejecutar todas las pruebas (Unitarias y Funcionales):</p>
                    <code class="block text-blue-800">php artisan test</code>
                </div>
            </div>
            
            <div>
                <h3 class="font-bold text-gray-800">B. Pruebas E2E (Playwright)</h3>
                <p class="text-sm text-gray-600 mb-2">Simula el flujo completo del usuario en el navegador (creación de tickets, inicio de sesión).</p>
                
                <p class="text-xs text-gray-700 mb-2 italic">Requisito: Configurar el archivo <code>.env.test</code> en la raíz:</p>
                <pre class="bg-gray-800 text-white p-4 rounded-lg overflow-x-auto text-xs mb-3">
# Credenciales de Agente/Staff
OSTICKET_EMAIL="agente@clinica.com"
OSTICKET_PASSWORD="password_seguro"

# Credenciales de Administrador (Panel SCP)
OSTICKET_ADMIN_USER="admin"
OSTICKET_ADMIN_PASSWORD="admin_password"

# URL base de la instalación de osTicket
OSTICKET_URL="http://localhost:8080"
                </pre>

                <code class="block bg-gray-100 p-3 rounded border font-mono text-sm">npm run test:playwright</code>
            </div>
        </div>
        
        <div class="bg-indigo-50 p-4 rounded-lg border border-indigo-200 mt-6 text-sm text-indigo-800">
            <strong>Recomendación:</strong> Ejecuta ambas suites de pruebas antes de realizar cualquier <code>git push</code> hacia el repositorio principal para asegurar que no existan regresiones.
        </div>
    </section>

    <section id="repositorio-despliegue" class="mb-12">
        <h2 class="text-2xl font-bold text-blue-800 mb-4">6. Repositorio y Despliegue (Git + cPanel)</h2>
        <p class="text-gray-700 mb-4">El sistema reside en el repositorio: <a href="https://github.com/tamaguirre/clinica-gateway" class="text-blue-600 underline" target="_blank">clinica-gateway</a>.</p>
        <p class="text-gray-700 mb-4">Para desplegar cambios, ejecuta en la terminal del servidor:</p>
        
        <div class="bg-gray-800 text-white p-6 rounded-lg space-y-4 font-mono text-sm">
            <p class="text-blue-300"># 1. Entrar al directorio de la aplicación</p>
            <p>cd api</p>

            <p class="text-blue-300 mt-4"># 2. Traer cambios desde GitHub</p>
            <p>git pull origin main</p>
            
            <p class="text-blue-300 mt-4"># 3. Limpiar caché y aplicar migraciones</p>
            <p>php artisan cache:clear</p>
            <p>php artisan migrate</p>
        </div>
    </section>
@endsection