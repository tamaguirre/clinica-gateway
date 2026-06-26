@extends('layouts.manual')
@section('title', 'Manual de Despliegue')
@section('header-title', 'Despliegue y Configuración Técnica')

@section('content')
    <nav class="mb-8 p-4 bg-gray-50 rounded-lg border border-gray-200">
        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-2">Contenido</h3>
        <ul class="space-y-1">
            <li><a href="#requisitos-entorno" class="text-blue-700 hover:underline flex items-center">1. Requisitos del Entorno</a></li>
            <li><a href="#configuracion-env" class="text-blue-700 hover:underline flex items-center">2. Configuración de Entorno (.env)</a></li>
            <li><a href="#configuracion-ollama" class="text-blue-700 hover:underline flex items-center">3. Configuración de Ollama</a></li>
            <li><a href="#automatizacion-categorizacion" class="text-blue-700 hover:underline flex items-center">4. Automatización de Categorización</a></li>
            <li><a href="#benchmarking-reporte" class="text-blue-700 hover:underline flex items-center">5. Benchmarking y Reporte de IA</a></li>
            <li><a href="#pruebas-automatizadas" class="text-blue-700 hover:underline flex items-center">6. Pruebas Automatizadas</a></li>
            <li><a href="#repositorio-despliegue" class="text-blue-700 hover:underline flex items-center">7. Repositorio y Despliegue (Git + cPanel)</a></li>
            <li><a href="#excepciones-whatsapp" class="text-blue-700 hover:underline flex items-center">8. Excepciones de Saludos en WhatsApp</a></li>
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

    <section id="configuracion-env" class="mb-12">
        <h2 class="text-2xl font-bold text-blue-800 mb-4">2. Configuración de Entorno (.env)</h2>
        <pre class="bg-gray-800 text-white p-6 rounded-lg overflow-x-auto text-sm">
# URL de la API de osTicket
OSTICKET_URL="http://localhost:8080/api/http.php/tickets.json"
OSTICKET_API_KEY="C6210A463085CFC451815ABFBD84C8D0"

# URL del servidor Ollama
OLLAMA_URL="http://206.189.192.107:11434"
OLLAMA_MODEL="qwen2.5:0.5b"

# Número de tópico general para la IA
APP_GENERAL_TOPIC=16
# Número de tópico para fallbacks (sin clasificar)
APP_FALLBACK_TOPIC=17
        </pre>
    </section>

    <section id="configuracion-ollama" class="mb-12">
        <h2 class="text-2xl font-bold text-blue-800 mb-4">3. Configuración de Ollama</h2>
        <p class="text-gray-700 mb-4">
            Ollama es una herramienta que permite ejecutar modelos de lenguaje grandes (LLMs) localmente. Es fundamental para el funcionamiento de la categorización de tickets por IA.
        </p>

        <h3 class="font-bold text-gray-800 mb-2">Instalación de Ollama</h3>
        <p class="text-gray-700 mb-2">
            Para instalar Ollama, visita su <a href="https://ollama.com/download" target="_blank" class="text-blue-600 underline">sitio web oficial</a> y sigue las instrucciones para tu sistema operativo (Linux, macOS, Windows).
        </p>
        <ul class="list-disc ml-6 space-y-1 text-gray-700 mb-4">
            <li><strong>Local:</strong> Instala Ollama directamente en tu máquina de desarrollo.</li>
            <li><strong>Servidor:</strong> Instala Ollama en tu servidor o VPS. Asegúrate de que el puerto <code>11434</code> esté accesible (puede requerir configuración de firewall).</li>
        </ul>
        <div class="bg-blue-50 p-4 rounded-lg border border-blue-200 mt-4 text-sm text-blue-800 mb-4">
            <strong>Nota:</strong>
            Una vez instalado, puedes verificar su funcionamiento ejecutando <code>ollama --version</code> en tu terminal.
        </div>

        <h3 class="font-bold text-gray-800 mb-2">Descarga de Modelos de IA</h3>
        <p class="text-gray-700 mb-2">
            Después de instalar Ollama, necesitarás descargar los modelos de IA que deseas utilizar. El modelo recomendado para este proyecto es <code>qwen2.5:0.5b</code>. Puedes descargarlo con el siguiente comando:
        </p>
        <code class="block bg-gray-800 text-white p-3 rounded border font-mono text-sm mb-4">
            ollama pull qwen2.5:0.5b
        </code>
        <p class="text-gray-700">
            Puedes explorar otros modelos disponibles en el <a href="https://ollama.com/library" target="_blank" class="text-blue-600 underline">Ollama Library</a>.
        </p>

        <h3 class="font-bold text-gray-800 mb-2">Configuración en el archivo <code>.env</code></h3>
        <p class="text-gray-700 mb-2">
            La aplicación gateway se conecta a Ollama utilizando las variables de entorno <code>OLLAMA_URL</code> y <code>OLLAMA_MODEL</code>.
        </p>
        <ul class="list-disc ml-6 space-y-1 text-gray-700 mb-4">
            <li><strong><code>OLLAMA_URL</code>:</strong> La URL donde se está ejecutando el servidor Ollama.
                <ul class="list-circle ml-6 mt-1 text-sm">
                    <li><strong>Local:</strong> <code>http://localhost:11434</code></li>
                    <li><strong>VPS/Remoto:</strong> <code>http://DIRECCION_IP_O_DOMINIO:11434</code></li>
                </ul>
            </li>
            <li><strong><code>OLLAMA_MODEL</code>:</strong> El nombre del modelo de IA a utilizar (ej. <code>qwen2.5:0.5b</code>).</li>
        </ul>
        <div class="bg-blue-50 p-4 rounded-lg border border-blue-200 mt-4 text-sm text-blue-800">
            <strong>Nota:</strong>
            Asegúrate de que estos valores estén correctamente configurados en tu archivo <code>.env</code> para que la aplicación pueda comunicarse con Ollama.
        </div>
    </section>

    <section id="automatizacion-categorizacion" class="mb-12">
        <h2 class="text-2xl font-bold text-blue-800 mb-4">4. Automatización de Categorización</h2>
        <p class="text-gray-700">El proceso de análisis por IA se ejecuta automáticamente mediante un <strong>Cron Job</strong> configurado en cPanel que se dispara cada <strong>1 minuto</strong> para gestionar las tareas programadas:</p>
        
        <code class="block bg-gray-100 p-3 mt-2 rounded border font-mono text-sm mb-4">
            * * * * * cd /ruta/al/proyecto && php artisan schedule:run >> /dev/null 2>&1
        </code>
        
        <p class="text-gray-700 mb-6">
            Este comando ejecuta la lógica de categorización internamente. Recuerda que el driver por defecto es <code>api</code> (no requiere configuración adicional). Si deseas cambiarlo, puedes ajustar la configuración en el sistema; para otros drivers específicos como <code>python</code>, asegúrate de que el entorno tenga las dependencias necesarias.
        </p>

        <h3 class="font-bold text-gray-800 mb-2">A. Watchdog / Validación de Estado de la IA</h3>
        <p class="text-gray-700 mb-4">
            Para evitar errores en cadena y procesamiento erróneo de tickets si el servidor de Inteligencia Artificial (Ollama) está apagado o no responde, el comando implementa una validación activa inicial (<strong>Watchdog</strong>).
        </p>
        <p class="text-gray-700 mb-4">
            Antes de categorizar los tickets, el watchdog realiza una petición de prueba al endpoint de Ollama. Si la IA no responde o devuelve un error, el comando cancela inmediatamente su ejecución y registra el error de manera segura, impidiendo que el sistema intente procesar tickets con un motor inactivo.
        </p>

        <h3 class="font-bold text-gray-800 mb-2">B. Tópico de Fallback (Tickets "Sin Clasificar")</h3>
        <p class="text-gray-700 mb-4">
            En caso de que un ticket no logre ser clasificado automáticamente, ya sea porque no coincide con ninguna palabra clave (<em>keywords</em>) o porque el modelo de IA no logra determinar una categoría reconocida, el sistema aplica un <strong>Fallback</strong>.
        </p>
        <p class="text-gray-700 mb-4">
            El ticket es reasignado automáticamente al ID de tópico configurado en la variable:
        </p>
        <code class="block bg-gray-100 p-3 rounded border font-mono text-sm mb-4">
            APP_FALLBACK_TOPIC=17
        </code>
        <p class="text-gray-700 mb-4">
            Esto los deja bajo el estado de "Sin Clasificar" (o el nombre que defina el tópico en osTicket) para que los agentes puedan revisarlos y clasificarlos de forma manual en el panel de control.
        </p>

        <img src="/img/cron-job.png" alt="Esquema de Automatización" class="w-full rounded-lg border-2 border-dashed mt-6">
    </section>

    <section id="benchmarking-reporte" class="mb-12">
        <h2 class="text-2xl font-bold text-blue-800 mb-4">5. Benchmarking y Reporte de IA</h2>
        <p class="text-gray-700 mb-4">Para validar el rendimiento y la precisión del modelo, el sistema permite realizar pruebas masivas mediante un dataset de entrenamiento/validación:</p>
        
        <code class="block bg-gray-100 p-3 rounded border font-mono text-sm">
            php artisan app:ticket-categorization --from-json=storage/app/tickets-dataset.json --force-ai --debug
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
        <h2 class="text-2xl font-bold text-blue-800 mb-4">6. Pruebas Automatizadas</h2>
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
# URL de Ollama para pruebas (Local)
OLLAMA_URL="http://localhost:11434"

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
        <h2 class="text-2xl font-bold text-blue-800 mb-4">7. Repositorio y Despliegue (Git + cPanel)</h2>
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

    <section id="excepciones-whatsapp" class="mb-12">
        <h2 class="text-2xl font-bold text-blue-800 mb-4">8. Excepciones de Saludos en WhatsApp</h2>
        <p class="text-gray-700 mb-4">
            Para evitar la creación accidental de tickets vacíos o innecesarios cuando los usuarios envían un saludo repetitivo (ej: "hola", "buenos días") en lugar de detallar su consulta, el sistema utiliza un archivo de excepciones en formato JSON.
        </p>
        <p class="text-gray-700 mb-4">
            El archivo debe ubicarse en la siguiente ruta dentro del servidor:
        </p>
        <code class="block bg-gray-100 p-3 rounded border font-mono text-sm mb-4">
            storage/app/whatsapp-exceptions.json
        </code>
        <p class="text-gray-700 mb-4">
            Este archivo contiene un array de strings en minúsculas con los saludos y expresiones que se desean ignorar en la segunda interacción:
        </p>
        <pre class="bg-gray-800 text-white p-6 rounded-lg overflow-x-auto text-sm">
[
    "hola",
    "hola!",
    "hola.",
    "buenas",
    "buenos días",
    "buenos dias",
    "buenas tardes",
    "buenas noches",
    "aló",
    "alo",
    "hey",
    "buen día",
    "buen dia",
    "saludos",
    "cómo estás",
    "como estas",
    "que tal",
    "qué tal"
]
        </pre>
        <div class="bg-blue-50 p-4 rounded-lg border border-blue-200 mt-4 text-sm text-blue-800">
            <strong>Nota:</strong>
            Si el mensaje enviado por el usuario coincide con alguna de estas frases (de forma exacta y sin importar mayúsculas/minúsculas), el bot volverá a mostrar el mensaje de bienvenida instructivo y mantendrá el estado del chat activo, evitando así crear un ticket en osTicket.
        </div>
    </section>
@endsection