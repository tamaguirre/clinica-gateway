@extends('layouts.manual')
@section('title', 'Reportes de Pruebas')
@section('header-title', 'Centro de Reportes de Calidad')

@section('content')
    <div class="mb-8">
        <p class="text-gray-700">
            Esta sección centraliza el estado de las pruebas de software del Gateway. El objetivo es garantizar que la integración con osTicket y la categorización por IA funcionen sin regresiones antes de cada despliegue.
        </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-12">
        <!-- PHPUnit Summary Card -->
        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center">
                    <div class="p-2 bg-blue-100 rounded-lg mr-3">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800">PHPUnit (Backend)</h3>
                </div>
                <span class="px-2 py-1 bg-green-100 text-green-700 text-xs font-bold rounded">ESTABLE</span>
            </div>
            <p class="text-gray-600 text-sm mb-4">Valida la lógica de negocio, detección de keywords e integración con la base de datos de osTicket.</p>
            <div class="space-y-2 mb-4">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Pruebas Unitarias:</span>
                    <span class="font-mono font-bold text-green-600">100% Pass</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Pruebas de Funciones:</span>
                    <span class="font-mono font-bold text-green-600">100% Pass</span>
                </div>
            </div>
            <code class="block bg-gray-900 text-blue-400 p-2 rounded text-xs">php artisan test</code>
        </div>

        <!-- Playwright Summary Card -->
        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm hover:hover:shadow-md transition">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center">
                    <div class="p-2 bg-indigo-100 rounded-lg mr-3">
                        <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800">Playwright (E2E)</h3>
                </div>
                <span class="px-2 py-1 bg-blue-100 text-blue-700 text-xs font-bold rounded">FUNCIONAL</span>
            </div>
            <p class="text-gray-600 text-sm mb-4">Simula flujos de usuario reales: Login, creación de tickets y acceso administrativo en el navegador.</p>
            <div class="space-y-2 mb-4">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Navegadores:</span>
                    <span class="font-mono font-bold text-gray-700">Chromium, Firefox</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Último Reporte:</span>
                    <span class="font-mono font-bold text-gray-700">Hace 1 hora</span>
                </div>
            </div>
            <div class="flex gap-2">
                <a href="/storage/playwright-report/index.html" target="_blank" class="text-xs bg-indigo-600 text-white px-3 py-2 rounded hover:bg-indigo-700 font-semibold transition">
                    Ver Reporte HTML
                </a>
                <code class="flex-1 bg-gray-900 text-green-400 p-2 rounded text-xs">npm run test:e2e</code>
            </div>
        </div>
    </div>

    <!-- Detalle de Cobertura -->
    <section class="mb-12">
        <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center">
            <span class="w-2 h-8 bg-blue-600 rounded mr-3"></span>
            Detalle de Cobertura de Pruebas
        </h2>
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="p-4 text-sm font-semibold text-gray-600">Componente</th>
                        <th class="p-4 text-sm font-semibold text-gray-600">Tipo</th>
                        <th class="p-4 text-sm font-semibold text-gray-600">Lo que se evalúa</th>
                        <th class="p-4 text-sm font-semibold text-gray-600 text-center">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr class="hover:bg-gray-50 transition">
                        <td class="p-4 text-sm font-medium text-gray-900">KeywordDetection</td>
                        <td class="p-4 text-xs font-bold text-blue-500 uppercase">Unit</td>
                        <td class="p-4 text-sm text-gray-600">Lógica de detección de palabras clave, manejo de acentos y mayúsculas.</td>
                        <td class="p-4 text-center text-green-600 font-bold text-lg">✓</td>
                    </tr>
                    <tr class="hover:bg-gray-50 transition">
                        <td class="p-4 text-sm font-medium text-gray-900">TicketCategorization</td>
                        <td class="p-4 text-xs font-bold text-purple-500 uppercase">Feature</td>
                        <td class="p-4 text-sm text-gray-600">Integración con Ollama (Mocked) y actualización de tablas nativas de osTicket.</td>
                        <td class="p-4 text-center text-green-600 font-bold text-lg">✓</td>
                    </tr>
                    <tr class="hover:bg-gray-50 transition">
                        <td class="p-4 text-sm font-medium text-gray-900">osTicket E2E Flow</td>
                        <td class="p-4 text-xs font-bold text-indigo-500 uppercase">E2E</td>
                        <td class="p-4 text-sm text-gray-600">Login de staff, creación de tickets públicos y navegación por el panel SCP.</td>
                        <td class="p-4 text-center text-green-600 font-bold text-lg">✓</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
@endsection