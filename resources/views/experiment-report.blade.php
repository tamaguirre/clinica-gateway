@extends('layouts.manual')
@section('title', 'Reporte de Experimentación')
@section('header-title', 'Experimentación con LMs')
@section('header-title', 'ANEXO 2: Reporte de Experimentación con Small LMs')

@section('content')
    <section class="mb-12">
        <h2 class="text-2xl font-bold text-blue-800 mb-4">Introducción</h2>
        <p class="text-gray-700">
            Esta vista detalla las pruebas realizadas con distintos modelos de lenguaje pequeños (Small Language Models) para determinar el motor de inferencia definitivo. El objetivo principal fue encontrar el equilibrio entre <strong>precisión de categorización</strong> y <strong>viabilidad técnica</strong> en entornos con recursos de hardware limitados (CPU/RAM).
        </p>
    </section>

    <section class="mb-12">
        <h2 class="text-2xl font-bold text-blue-800 mb-4">Cuadro Comparativo de Modelos</h2>
        <div class="overflow-x-auto shadow-sm border border-gray-200 rounded-xl">
            <table class="min-w-full divide-y divide-gray-200 bg-white">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Modelo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tamaño</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Precisión</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Velocidad</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Conclusión</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 text-sm">
                    <tr>
                        <td class="px-6 py-4 font-bold text-gray-900">Qwen 2.5</td>
                        <td class="px-6 py-4 text-gray-600">490M</td>
                        <td class="px-6 py-4 text-green-600 font-semibold">80.9%</td>
                        <td class="px-6 py-4 text-gray-600">13.64 segundos</td>
                        <td class="px-6 py-4 text-gray-700"><strong>Seleccionado.</strong> Excelente balance entre peso y comprensión.</td>
                    </tr>
                    <tr>
                        <td class="px-6 py-4 font-bold text-gray-900">Gemma 2</td>
                        <td class="px-6 py-4 text-gray-600">2.6B</td>
                        <td class="px-6 py-4 text-blue-600 font-semibold">44.2%</td>
                        <td class="px-6 py-4 text-gray-600">66.59 segundos</td>
                        <td class="px-6 py-4 text-gray-700">Descartado por requerir alta VRAM/CPU para ejecución fluida y no lograr mucha precisión.</td>
                    </tr>
                    <tr>
                        <td class="px-6 py-4 font-bold text-gray-900">SmolLM2</td>
                        <td class="px-6 py-4 text-gray-600">135M</td>
                        <td class="px-6 py-4 text-red-500 font-semibold">44.2%</td>
                        <td class="px-6 py-4 text-gray-600">40.54 segundos</td>
                        <td class="px-6 py-4 text-gray-700">Falla en matices contextuales.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <section class="mb-12">
        <h2 class="text-2xl font-bold text-blue-800 mb-6">Evidencia de Pruebas</h2>
        <h3 class="text-xl font-semibold text-gray-800 mb-4">Qwen 2.5 (0.5B)</h3>
        <img src="/img/qwen-report.png" alt="Reporte Qwen" class="w-full rounded-lg shadow-sm border mb-4">
        <hr class="my-8 border-gray-300">
        <h3 class="text-xl font-semibold text-gray-800 mb-4">SmolLM2 (135M)</h3>
        <img src="/img/smollm-report.png" alt="Reporte SmolLM" class="w-full rounded-lg shadow-sm border mb-4">
        <hr class="my-8 border-gray-300">
        <h3 class="text-xl font-semibold text-gray-800 mb-4">Gemma 2 (2.6B)</h3>
        <img src="/img/gemma-report.png" alt="Reporte Gemma" class="w-full rounded-lg shadow-sm border mb-4">
    </section>

    <section class="mb-12">
        <h2 class="text-2xl font-bold text-blue-800 mb-4">Justificación Final</h2>
        <div class="bg-blue-50 p-6 rounded-xl border border-blue-200 space-y-4">
            <p class="text-gray-800 leading-relaxed">
                Tras las pruebas realizadas, se determinó que <strong>Qwen 2.5 (0.5B)</strong> es el modelo idóneo para la clínica debido a su capacidad de comprender lenguaje técnico y administrativo con un consumo de recursos despreciable.
            </p>
        </div>
    </section>

    <section class="mb-12">
        <h2 class="text-2xl font-bold text-blue-800 mb-4">Alcance del Estudio de Experimentación</h2>
        <div class="bg-gray-100 p-6 rounded-xl border border-gray-200 space-y-4">
            <p class="text-gray-700 leading-relaxed">
                El conjunto de pruebas de precisión y rendimiento de la IA se ejecutó de manera controlada utilizando un dataset simulado de 100 tickets representativos (<code>tickets-dataset.json</code>) con distribución equitativa de categorías.
            </p>
            <p class="text-gray-700 leading-relaxed">
                <strong>Privacidad de Datos Clínicos:</strong> Para cumplir estrictamente con el espíritu de la <strong>Ley 21.719</strong> y resguardar la privacidad de la información de salud, no se expusieron ni procesaron datos históricos reales de pacientes de producción en los experimentos públicos de GitHub. Cabe señalar que la precisión real en producción (con textos redactados de forma informal o con ruido ortográfico por pacientes reales) podría diferir de los resultados controlados.
            </p>
        </div>
    </section>

    <section class="mb-12">
        <h2 class="text-2xl font-bold text-blue-800 mb-4">Límites de Escalabilidad y Arquitectura de Producción</h2>
        <div class="bg-amber-50 p-6 rounded-xl border border-amber-200 space-y-4">
            <p class="text-amber-900 leading-relaxed">
                La arquitectura actual del gateway está diseñada de forma <strong>monolítica y síncrona</strong>. Es una solución sumamente eficiente y suficiente para un flujo transaccional de clínica pequeña o mediana. No obstante, si se proyecta escalar el sistema a nivel nacional (miles de tickets concurrentes y múltiples centros de salud), se identifican los siguientes límites de escalabilidad:
            </p>
            <ul class="list-disc pl-5 space-y-2 text-gray-700 text-sm">
                <li><strong>Carga Eager en Memoria:</strong> El comando Artisan carga todos los tickets pendientes en memoria mediante <code>get()</code>. A gran escala, esto se sustituirá por procesamiento en lotes utilizando <code>chunk(50)</code> para mitigar la RAM.</li>
                <li><strong>Procesamiento Secuencial (Foreach):</strong> El clasificador atiende un ticket a la vez. En producción nacional, se despacharán <strong>Jobs asíncronos en colas</strong> (<code>CategorizarTicketJob</code>) y se levantarán múltiples workers paralelos (<code>php artisan queue:work --queue=tickets</code>).</li>
                <li><strong>Inferencia Serializada de Ollama:</strong> Por defecto, el servidor local de Ollama encola las peticiones serialmente. A gran escala, se requiere balancear la carga entre un clúster de servidores de inferencia o habilitar múltiples slots concurrentes.</li>
                <li><strong>driver de Caché de Base de Datos:</strong> Para evitar race conditions y cuellos de botella al almacenar estados de conversación de WhatsApp con alta concurrencia, se migrará a <strong>Redis</strong> como driver de caché, implementando bloqueos atómicos (<code>SETNX</code> / <code>Cache::add</code>).</li>
                <li><strong>Colas de Prioridad para Urgencias:</strong> Los tickets de categorías críticas (como hackeo o fugas) deben encolarse de forma prioritaria (<code>queue:work --queue=urgent,default</code>) en lugar de en orden FIFO simple.</li>
            </ul>
        </div>
    </section>
@endsection