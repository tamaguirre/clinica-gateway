@extends('layouts.manual')
@section('title', 'Reporte de Experimentación')
@section('header-title', 'Experimentación con LMs')

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
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Parámetros</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Precisión</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Latencia</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Conclusión</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 text-sm">
                    <tr>
                        <td class="px-6 py-4 font-bold text-gray-900">Qwen 2.5 (0.5B)</td>
                        <td class="px-6 py-4 text-gray-600">490M</td>
                        <td class="px-6 py-4 text-green-600 font-semibold">Alta (85-90%)</td>
                        <td class="px-6 py-4 text-gray-600">~300ms</td>
                        <td class="px-6 py-4 text-gray-700"><strong>Seleccionado.</strong> Excelente balance entre peso y comprensión.</td>
                    </tr>
                    <tr>
                        <td class="px-6 py-4 font-bold text-gray-900">Gemma 2 (2B)</td>
                        <td class="px-6 py-4 text-gray-600">2.6B</td>
                        <td class="px-6 py-4 text-blue-600 font-semibold">Muy Alta (92%+)</td>
                        <td class="px-6 py-4 text-gray-600">> 1.2s</td>
                        <td class="px-6 py-4 text-gray-700">Descartado por requerir alta VRAM/CPU para ejecución fluida.</td>
                    </tr>
                    <tr>
                        <td class="px-6 py-4 font-bold text-gray-900">SmolLM2 (135M)</td>
                        <td class="px-6 py-4 text-gray-600">135M</td>
                        <td class="px-6 py-4 text-red-500 font-semibold">Media (60-70%)</td>
                        <td class="px-6 py-4 text-gray-600">< 100ms</td>
                        <td class="px-6 py-4 text-gray-700">Extremadamente rápido, pero falla en matices contextuales.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <section class="mb-12">
        <h2 class="text-2xl font-bold text-blue-800 mb-6">Evidencia de Pruebas (Gráficos y Métricas)</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Espacio para Imagen 1: Precisión -->
            <div class="space-y-2">
                <h3 class="text-lg font-semibold text-gray-700">Comparativa de Precisión Global</h3>
                <div class="aspect-video w-full bg-gray-100 border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center text-gray-400">
                    <div class="text-center">
                        <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        <p>[ Insertar gráfico de barras: Accuracy per Model ]</p>
                    </div>
                </div>
            </div>

            <!-- Espacio para Imagen 2: Latencia -->
            <div class="space-y-2">
                <h3 class="text-lg font-semibold text-gray-700">Análisis de Latencia (ms)</h3>
                <div class="aspect-video w-full bg-gray-100 border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center text-gray-400">
                    <div class="text-center">
                        <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        <p>[ Insertar gráfico: Latency Distribution ]</p>
                    </div>
                </div>
            </div>

            <!-- Espacio para Imagen 3: Dashboard -->
            <div class="col-span-1 md:col-span-2 space-y-2 text-center">
                <h3 class="text-lg font-semibold text-gray-700">Captura del Sistema de Benchmarking</h3>
                <div class="w-full bg-gray-100 border-2 border-dashed border-gray-300 rounded-lg py-12 flex items-center justify-center text-gray-400">
                    <div class="text-center">
                        <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        <p>[ Insertar captura de pantalla del reporte HTML generado por artisan ]</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="mb-12">
        <h2 class="text-2xl font-bold text-blue-800 mb-4">Justificación Final</h2>
        <div class="bg-blue-50 p-6 rounded-xl border border-blue-200 space-y-4">
            <p class="text-gray-800 leading-relaxed">
                Tras las pruebas realizadas, se determinó que <strong>Qwen 2.5 (0.5B)</strong> es el modelo idóneo para la clínica debido a su capacidad de comprender lenguaje técnico médico y administrativo con un consumo de recursos despreciable. Mientras que Gemma 2 ofrece una precisión marginalmente superior, su costo computacional no justifica el cambio para el caso de uso de categorización de tickets.
            </p>
            <div class="flex items-center text-blue-700 font-bold">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"></path></svg>
                Decisión: Implementación de Qwen 2.5 vía Ollama Local.
            </div>
        </div>
    </section>

    <div class="flex justify-between items-center mt-12 border-t pt-8">
        <a href="{{ route('deploy-guide') }}" class="text-blue-700 hover:text-blue-900 font-semibold flex items-center">
            <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            Volver a la Guía de Despliegue
        </a>
    </div>
@endsection