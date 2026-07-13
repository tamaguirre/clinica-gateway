@extends('layouts.dashboard')

@section('title', 'Dashboard')

@section('content')

{{-- ── Top bar ──────────────────────────────────────────────── --}}
<header class="sticky top-0 z-10 bg-white/80 backdrop-blur border-b border-slate-200 px-8 py-4 flex items-center justify-between">
    <div>
        <h1 class="text-lg font-semibold text-slate-800">Dashboard</h1>
        <p class="text-sm text-slate-500">Resumen del sistema de tickets</p>
    </div>
    <div class="flex items-center gap-4 text-sm">
        @if ($aiActive)
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                </span>
                IA Online ({{ config('services.ollama.model') }})
            </span>
        @else
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-rose-50 text-rose-700 border border-rose-200">
                <span class="relative flex h-2 w-2">
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-rose-500"></span>
                </span>
                IA Offline ({{ config('services.ollama.model') }})
            </span>
        @endif

        <div class="flex items-center gap-2 text-slate-500">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ now()->format('d/m/Y H:i') }}
        </div>
    </div>
</header>

{{-- ── Content ─────────────────────────────────────────────── --}}
<main class="flex-1 p-8 space-y-6">

    @if ($modeCollapseWarning)
        <div class="bg-amber-50/80 backdrop-blur border-l-4 border-amber-500 p-4 rounded-r-xl shadow-sm flex items-start gap-3">
            <div class="w-10 h-10 rounded-lg bg-amber-100 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-amber-800">Alerta de Rendimiento: Posible colapso de modo</h3>
                <p class="text-xs text-amber-700 mt-1 leading-relaxed">{{ $modeCollapseWarning }}</p>
                <p class="text-[10px] text-amber-500 mt-0.5">Se sugiere revisar la disponibilidad de Ollama, el estado del modelo local y las instrucciones del prompt.</p>
            </div>
        </div>
    @endif

    {{-- KPI Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">

        {{-- Total --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5 flex items-start gap-4">
            <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-xs text-slate-500 uppercase tracking-wide font-medium">Total tickets</p>
                <p class="text-3xl font-bold text-slate-800 mt-0.5">{{ number_format($stats['total']) }}</p>
            </div>
        </div>

        {{-- Sin categorizar (Pre-IA) --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5 flex items-start gap-4">
            <div class="w-10 h-10 rounded-lg bg-amber-50 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-xs text-slate-500 uppercase tracking-wide font-medium">Sin procesar (Pre-IA)</p>
                <p class="text-3xl font-bold text-slate-800 mt-0.5">{{ number_format($stats['preAi']) }}</p>
                @if ($stats['total'] > 0)
                    <p class="text-xs text-slate-400 mt-0.5">
                        {{ round($stats['preAi'] / $stats['total'] * 100, 1) }}% del total
                    </p>
                @endif
            </div>
        </div>

        {{-- Sin clasificar (IA) --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5 flex items-start gap-4">
            <div class="w-10 h-10 rounded-lg bg-slate-100 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-xs text-slate-500 uppercase tracking-wide font-medium">Sin clasificar (IA)</p>
                <p class="text-3xl font-bold text-slate-800 mt-0.5">{{ number_format($stats['fallback']) }}</p>
                @if ($stats['total'] > 0)
                    <p class="text-xs text-slate-400 mt-0.5">
                        {{ round($stats['fallback'] / $stats['total'] * 100, 1) }}% del total
                    </p>
                @endif
            </div>
        </div>

        {{-- Urgentes --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5 flex items-start gap-4">
            <div class="w-10 h-10 rounded-lg bg-red-50 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-xs text-slate-500 uppercase tracking-wide font-medium">Urgencias</p>
                <p class="text-3xl font-bold text-slate-800 mt-0.5">{{ number_format($stats['urgent']) }}</p>
            </div>
        </div>

    </div>

    {{-- Charts row --}}
    <div class="grid grid-cols-3 gap-6">

        {{-- Donut — distribución por categoría --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <h2 class="text-sm font-semibold text-slate-700 mb-4">Distribución por categoría</h2>
            <canvas id="chartCategories" class="max-h-52"></canvas>
            <div class="mt-4 space-y-1.5" id="legendCategories"></div>
        </div>

        {{-- Line — tickets por día --}}
        <div class="col-span-2 bg-white rounded-xl border border-slate-200 p-5">
            <h2 class="text-sm font-semibold text-slate-700 mb-4">Tickets por día — últimos 30 días</h2>
            <canvas id="chartDaily" class="max-h-52"></canvas>
        </div>

    </div>

    {{-- Recent tickets --}}
    <div class="bg-white rounded-xl border border-slate-200">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-slate-700">Tickets recientes</h2>
            <span class="text-xs text-slate-400">Últimos 15</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100">
                        <th class="text-left px-6 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">N°</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Categoría</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Estado</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Fecha</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($recent as $ticket)
                        @php
                            $catColors = [
                                'Orientación' => 'bg-blue-100 text-blue-700',
                                'Técnicas'    => 'bg-amber-100 text-amber-700',
                                'Urgencia'    => 'bg-red-100 text-red-700',
                                'Auditorías'  => 'bg-emerald-100 text-emerald-700',
                            ];
                            $catColor = $catColors[$ticket->topic] ?? 'bg-slate-100 text-slate-500';
                        @endphp
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-3.5 font-mono text-slate-600 text-xs">
                                #{{ $ticket->number ?? $ticket->ticket_id }}
                            </td>
                            <td class="px-6 py-3.5">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $catColor }}">
                                    {{ $ticket->topic ?? 'Sin categorizar' }}
                                </span>
                            </td>
                            <td class="px-6 py-3.5">
                                @if ($ticket->status_id == 1)
                                    <span class="inline-flex items-center gap-1.5 text-xs text-emerald-600">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Abierto
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 text-xs text-slate-400">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-300"></span> Cerrado
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-3.5 text-slate-400 text-xs">
                                {{ \Carbon\Carbon::parse($ticket->created)->format('d/m/Y H:i') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-10 text-center text-slate-400 text-sm">
                                No hay tickets registrados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</main>
@endsection

@push('scripts')
<script>
const CAT_COLORS = {
    'Orientación': '#3b82f6',
    'Técnicas':    '#f59e0b',
    'Urgencia':    '#ef4444',
    'Auditorías':  '#10b981',
};
const DEFAULT_COLOR = '#94a3b8';

// ── Donut — por categoría ────────────────────────────────────────
const catData  = @json($byCategory);
const catLabels = catData.map(c => c.topic);
const catTotals = catData.map(c => c.total);
const catColors = catLabels.map(l => CAT_COLORS[l] ?? DEFAULT_COLOR);

new Chart(document.getElementById('chartCategories'), {
    type: 'doughnut',
    data: {
        labels: catLabels,
        datasets: [{ data: catTotals, backgroundColor: catColors, borderWidth: 2, borderColor: '#fff' }],
    },
    options: {
        cutout: '65%',
        plugins: { legend: { display: false } },
    },
});

// Leyenda manual
const legend = document.getElementById('legendCategories');
catLabels.forEach((label, i) => {
    const pct = catTotals.reduce((a, b) => a + b, 0) > 0
        ? Math.round(catTotals[i] / catTotals.reduce((a, b) => a + b, 0) * 100)
        : 0;
    legend.insertAdjacentHTML('beforeend', `
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background:${catColors[i]}"></span>
                <span class="text-xs text-slate-600">${label}</span>
            </div>
            <span class="text-xs font-semibold text-slate-700">${catTotals[i]} <span class="text-slate-400 font-normal">(${pct}%)</span></span>
        </div>
    `);
});

// ── Line — por día ────────────────────────────────────────────────
const dailyLabels = @json($dailyLabels);
const dailyData   = @json($dailyData);

new Chart(document.getElementById('chartDaily'), {
    type: 'line',
    data: {
        labels: dailyLabels,
        datasets: [{
            label: 'Tickets',
            data: dailyData,
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59,130,246,0.08)',
            borderWidth: 2,
            pointRadius: 3,
            pointBackgroundColor: '#3b82f6',
            fill: true,
            tension: 0.4,
        }],
    },
    options: {
        scales: {
            x: {
                grid: { display: false },
                ticks: { font: { size: 11 }, maxTicksLimit: 10 },
            },
            y: {
                beginAtZero: true,
                grid: { color: '#f1f5f9' },
                ticks: { font: { size: 11 }, stepSize: 1 },
            },
        },
        plugins: { legend: { display: false } },
    },
});

// Auto-recarga del Dashboard cada 5 minutos (300,000 ms)
setTimeout(() => {
    window.location.reload();
}, 300000);
</script>
@endpush
