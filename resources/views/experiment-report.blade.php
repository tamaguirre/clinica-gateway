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
                Tras las pruebas realizadas, se determinó que <strong>Qwen 2.5 (0.5B)</strong> es el modelo idóneo para la clínica debido a su capacidad de comprender lenguaje técnico médico y administrativo con un consumo de recursos despreciable.
            </p>
        </div>
    </section>
@endsection