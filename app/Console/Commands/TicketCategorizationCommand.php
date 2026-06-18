<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Ticket;
use App\Models\Topic;
use App\Models\TicketData;
use App\Models\FormEntryValues;

#[Signature('app:ticket-categorization {--from-json= : Ruta al JSON de tickets de prueba} {--driver=api : Driver de inferencia} {--no-report : Omite el reporte HTML} {--debug : Muestra la respuesta en bruto de la IA} {--force-ai : Ignora las keywords y fuerza el uso de IA}')]
#[Description('Categoriza tickets usando IA via Ollama')]
class TicketCategorizationCommand extends Command
{
    public function handle()
    {
        $categories = Topic::query()
            ->where('topic_id', '!=', config('app.general_topic'))
            ->get(['topic_id', 'topic', 'sla_id', 'priority_id', 'notes']);

        if ($categories->isEmpty()) {
            $this->error('No se encontraron categorías disponibles.');
            return Command::FAILURE;
        }

        $jsonPath = $this->option('from-json');
        if ($jsonPath !== null) {
            return $this->processFromJson($jsonPath, $categories);
        }

        $tickets = Ticket::with('thread.firstEntry', 'ticketData', 'formEntry.valueWithPriority')
            ->where('topic_id', config('app.general_topic'))
            ->get();

        if ($tickets->isEmpty()) {
            $this->warn('No se encontraron tickets con topic_id = ' . config('app.general_topic') . '.');
            return Command::SUCCESS;
        }

        $this->info("Se encontraron {$tickets->count()} tickets. Categorizando...");

        foreach ($tickets as $ticket) {
            $entry = $ticket->thread?->firstEntry;
            
            // Si el ticket ya fue procesado y no es el tópico general, lo omitimos en modo normal
            if ($ticket->topic_id != config('app.general_topic')) {
                continue;
            }

            if (!$entry) {
                $this->warn("Ticket #{$ticket->ticket_id}: sin contenido, omitiendo.");
                continue;
            }

            $texto = strip_tags($entry->body ?? '');
            
            // Construcción dinámica del prompt basada en BD o Config
            $systemPrompt = $this->buildSystemPrompt($categories);
            $userPrompt   = $texto;

            $systemPrompt = trim($systemPrompt);

            $response = $this->chatWithIA($userPrompt, $systemPrompt);

            if ($this->option('debug')) $this->info("Respuesta IA: " . ($response ?? 'NULL'));

            if ($response === null) {
                $this->error("Ticket #{$ticket->ticket_id}: no se pudo obtener respuesta.");
                continue;
            }

            $suggestedName = trim($response);
            $matched = $categories->first(fn($cat) => strcasecmp($cat->topic, $suggestedName) === 0);

            if (!$matched) {
                $bestScore = 0;
                $bestMatch = null;
                foreach ($categories as $cat) {
                    similar_text(mb_strtolower($suggestedName), mb_strtolower($cat->topic), $percent);
                    if ($percent > $bestScore) {
                        $bestScore = $percent;
                        $bestMatch = $cat;
                    }
                }
                if ($bestScore >= 70) {
                    $matched = $bestMatch;
                    $this->warn("Ticket #{$ticket->ticket_id} → Coincidencia aproximada ({$bestScore}%): \"{$suggestedName}\" → \"{$matched->topic}\"");
                }
            }

            if (!$matched) {
                $this->warn("Ticket #{$ticket->ticket_id} → Categoría no reconocida: \"{$suggestedName}\". No se actualizó.");
                continue;
            }

            $ticket->update([
                'topic_id' => $matched->topic_id,
                'sla_id'   => $matched->sla_id,
            ]);

            if ($ticket->ticketData) {
                $ticket->ticketData->update(['priority' => $matched->priority_id]);
            } else {
                TicketData::create([
                    'ticket_id'   => $ticket->ticket_id,
                    'priority' => $matched->priority_id,
                ]);
            }

            $formEntryValue = $ticket->formEntry?->valueWithPriority;
            if ($formEntryValue) {
                $formEntryValue->update(['value_id' => $matched->priority_id]);
            }

            $this->info("Ticket #{$ticket->ticket_id} → Actualizado: topic={$matched->topic}, sla_id={$matched->sla_id}, priority_id={$matched->priority_id}");
        }

        return Command::SUCCESS;
    }

    private function processFromJson(string $path, $categories): int
    {
        $fullPath = str_starts_with($path, '/') ? $path : base_path($path);

        if (!file_exists($fullPath)) {
            $this->error("Archivo no encontrado: {$fullPath}");
            return Command::FAILURE;
        }

        $raw = file_get_contents($fullPath);
        $tickets = json_decode($raw, true);

        if (!is_array($tickets) || empty($tickets)) {
            $this->error('El archivo JSON está vacío o no tiene el formato esperado (array de objetos).');
            return Command::FAILURE;
        }

        $this->info('Procesando ' . count($tickets) . ' tickets desde JSON (sin escritura en BD)...');
        $this->newLine();

        $globalStart = microtime(true);
        $cpuStart    = getrusage();

        $keywords = $categories->pluck('notes', 'topic') // Mapea [ 'Nombre del Topic' => 'Texto de notes' ]
            ->map(function ($notes) {
                if (empty($notes)) return [];

                if (preg_match("/keywords:\s*\[(.*?)\]/s", $notes, $matches)) {
                    $keywordsString = $matches[1];
                    preg_match_all("/['\"](.*?)['\"]/", $keywordsString, $wordsMatches);
                    return $wordsMatches[1];
                }
                return [];
            })
        ->toArray();

        $results   = [];
        $correct   = 0;
        $incorrect = 0;
        $noMatch   = 0;

        foreach ($tickets as $ticket) {
            $ticketStart = microtime(true);
            $aiTimeMs    = 0.0;

            $id       = $ticket['id'] ?? '?';
            $texto    = $ticket['body'] ?? '';
            $expected = $ticket['expected'] ?? null;

            if (empty($texto)) {
                $this->warn("Ticket #{$id}: sin contenido, omitiendo.");
                continue;
            }

            $textoLower = mb_strtolower($texto);

            // Detección por keywords
            $matchedByKeyword = null;
            if (!$this->option('force-ai')) {
                foreach ($keywords as $categoryName => $words) {
                    foreach ($words as $word) {
                        if (str_contains($textoLower, $word)) {
                            $matchedByKeyword = $categories->first(fn($cat) => $cat->topic === $categoryName);
                            break 2;
                        }
                    }
                }
            }

            if ($matchedByKeyword) {
                $method  = 'keyword';
                $matched = $matchedByKeyword;
            } else {
                $method  = 'AI';
                $matched = null;

            $systemPrompt = $this->buildSystemPrompt($categories);

            $userPrompt = $texto;
            
            $this->line("Ticket #{$id}: consultando...");

            $aiStart  = microtime(true);
            $response = $this->chatWithIA($userPrompt, $systemPrompt);
            $aiTimeMs = round((microtime(true) - $aiStart) * 1000, 1);

            if ($this->option('debug')) $this->info("Ticket #{$id} IA Raw: " . ($response ?? 'NULL'));

            if ($response === null) {
                $this->error("Ticket #{$id}: no se pudo obtener respuesta de la IA.");
                
                if ($expected !== null) {
                    $incorrect++; // Contamos el error de sistema como un fallo en la precisión
                }

                $results[] = [
                    'id'         => $id,
                    'preview'    => $texto,
                    'expected'   => $expected,
                    'assigned'   => null,
                    'method'     => $method,
                    'ok'         => '✗',
                    'time_ms'    => round((microtime(true) - $ticketStart) * 1000, 1),
                    'ai_time_ms' => $aiTimeMs,
                ];
                $noMatch++;
                continue;
            }

            $suggestedName = trim($response);
            $matched       = $categories->first(fn($cat) => strcasecmp($cat->topic, $suggestedName) === 0);

            if (!$matched) {
                $bestScore = 0;
                $bestMatch = null;
                foreach ($categories as $cat) {
                    similar_text(mb_strtolower($suggestedName), mb_strtolower($cat->topic), $percent);
                    if ($percent > $bestScore) {
                        $bestScore = $percent;
                        $bestMatch = $cat;
                    }
                }
                if ($bestScore >= 70) {
                    $matched = $bestMatch;
                    $this->warn("Ticket #{$id} → Coincidencia aproximada ({$bestScore}%): \"{$suggestedName}\" → \"{$matched->topic}\"");
                }
            }

            if (!$matched) {
                $this->warn("Ticket #{$id} → Categoría no reconocida: \"{$suggestedName}\".");
                if ($expected !== null) {
                    $incorrect++;
                }

                $results[] = [
                    'id'         => $id,
                    'preview'    => $texto,
                    'expected'   => $expected,
                    'assigned'   => null,
                    'method'     => $method,
                    'ok'         => '✗',
                    'time_ms'    => round((microtime(true) - $ticketStart) * 1000, 1),
                    'ai_time_ms' => $aiTimeMs,
                ];
                $noMatch++;
                continue;
            }
            } // end else (AI)

            $isCorrect = $expected !== null && strcasecmp($matched->topic, $expected) === 0;
            $okLabel   = $expected === null ? '-' : ($isCorrect ? '✓' : '✗');

            if ($expected !== null) {
                $isCorrect ? $correct++ : $incorrect++;
            }

            $this->line("Ticket #{$id} [{$method}] → {$matched->topic} {$okLabel}");

            $results[] = [
                'id'         => $id,
                'preview'    => $texto,
                'expected'   => $expected ?? '-',
                'assigned'   => $matched->topic,
                'method'     => $method,
                'ok'         => $okLabel,
                'time_ms'    => round((microtime(true) - $ticketStart) * 1000, 1),
                'ai_time_ms' => $aiTimeMs,
            ];
        }

        $this->newLine();
        $this->info('=== Resumen por categoría ===');
        $byCategory = collect($results)->groupBy('assigned');
        $summaryRows = [];
        foreach ($byCategory as $cat => $group) {
            $correctInGroup = $group->filter(fn($r) => $r['ok'] === '✓')->count();
            $totalInGroup   = $group->filter(fn($r) => $r['ok'] !== '-')->count();
            $pct = $totalInGroup > 0 ? round($correctInGroup / $totalInGroup * 100, 1) . '%' : '-';
            $summaryRows[] = [$cat ?? '?', $group->count(), $correctInGroup, $pct];
        }
        $this->table(['Categoría', 'Asignados', 'Correctos', 'Precisión'], $summaryRows);

        $totalEvaluados = $correct + $incorrect;
        if ($totalEvaluados > 0) {
            $accuracy = round($correct / $totalEvaluados * 100, 1);
            $this->info("Precisión global: {$correct}/{$totalEvaluados} ({$accuracy}%)");
        }

        $globalEnd  = microtime(true);
        $cpuEnd     = getrusage();
        $resultsCol = collect($results);
        $keywordCol = $resultsCol->where('method', 'keyword');
        $aiCol      = $resultsCol->where('method', 'AI');
        $totalMs    = round(($globalEnd - $globalStart) * 1000);

        $model = config('services.ollama.model');

        $stats = [
            'generated_at'        => date('d/m/Y H:i:s'),
            'driver'              => $this->option('driver') ?? 'api',
            'model'               => $model,
            'source'              => 'json',
            'total_tickets'       => $resultsCol->count(),
            'total_time_ms'       => $totalMs,
            'avg_time_ms'         => $resultsCol->count() > 0 ? round($resultsCol->avg('time_ms'), 1) : 0,
            'keyword_count'       => $keywordCol->count(),
            'ai_count'            => $aiCol->count(),
            'avg_keyword_time_ms' => $keywordCol->count() > 0 ? round($keywordCol->avg('time_ms'), 1) : 0,
            'avg_ai_time_ms'      => $aiCol->count() > 0 ? round($aiCol->avg('time_ms'), 1) : 0,
            'correct'             => $correct,
            'incorrect'           => $incorrect,
            'no_match'            => $noMatch,
            'accuracy'            => $totalEvaluados > 0 ? round($correct / $totalEvaluados * 100, 1) : 0,
            'memory_peak_mb'      => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'cpu_user_ms'         => round(
                (($cpuEnd['ru_utime.tv_sec']  - $cpuStart['ru_utime.tv_sec'])  * 1000)
              + (($cpuEnd['ru_utime.tv_usec'] - $cpuStart['ru_utime.tv_usec']) / 1000), 1
            ),
            'cpu_sys_ms'          => round(
                (($cpuEnd['ru_stime.tv_sec']  - $cpuStart['ru_stime.tv_sec'])  * 1000)
              + (($cpuEnd['ru_stime.tv_usec'] - $cpuStart['ru_stime.tv_usec']) / 1000), 1
            ),
        ];

        $jsonOutput = storage_path('app/tickets-results.json');
        file_put_contents($jsonOutput, json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $this->info("Resultados JSON guardados en: {$jsonOutput}");

        if (!$this->option('no-report')) {
            $htmlOutput = storage_path('app/tickets-report.html');
            file_put_contents($htmlOutput, $this->generateHtmlReport($results, $stats));
            $this->info("Reporte HTML guardado en: {$htmlOutput}");
        }

        return Command::SUCCESS;
    }

    private function generateHtmlReport(array $results, array $stats): string
    {
        // ... (El contenido de tu reporte HTML queda exactamente igual)
        $statsJson  = json_encode($stats,   JSON_UNESCAPED_UNICODE);
        $resJson    = json_encode($results, JSON_UNESCAPED_UNICODE);
        $evaluated  = $stats['correct'] + $stats['incorrect'];
        $totalSec   = number_format($stats['total_time_ms'] / 1000, 2);

        // Extraer categorías únicas presentes en los resultados para que el reporte sea dinámico
        $categoriesInResults = collect($results)
            ->pluck('assigned')
            ->filter()
            ->unique()
            ->values();

        $kwPct      = $stats['total_tickets'] > 0
            ? round($stats['keyword_count'] / $stats['total_tickets'] * 100, 1) : 0;
        $aiPct      = $stats['total_tickets'] > 0
            ? round($stats['ai_count'] / $stats['total_tickets'] * 100, 1) : 0;

        $tpl = <<<'HTML'
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Reporte — Categorización Automática de Tickets</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<style>
body{background:#f1f5f9;font-family:'Segoe UI',system-ui,sans-serif}
.hdr{background:linear-gradient(135deg,#1e3a5f,#2563eb);color:#fff;padding:2rem;border-radius:12px;margin-bottom:2rem}
.kpi{background:#fff;border-radius:10px;padding:1.25rem 1.5rem;box-shadow:0 1px 3px rgba(0,0,0,.08);border-left:5px solid;height:100%}
.kpi-lbl{font-size:.7rem;text-transform:uppercase;letter-spacing:.07em;color:#64748b;margin-bottom:.2rem}
.kpi-val{font-size:2rem;font-weight:700;line-height:1.1}
.kpi-sub{font-size:.78rem;color:#94a3b8;margin-top:.2rem}
.cht{background:#fff;border-radius:10px;padding:1.25rem;box-shadow:0 1px 3px rgba(0,0,0,.08);height:100%}
.stitle{font-size:1rem;font-weight:600;color:#1e293b;border-bottom:2px solid #e2e8f0;padding-bottom:.4rem;margin-bottom:1rem}
.bkw{background:#ede9fe;color:#5b21b6;font-weight:500}
.bai{background:#fff7ed;color:#c2410c;font-weight:500}
.oky{color:#16a34a;font-weight:700}
.okn{color:#dc2626}
</style>
</head>
<body>
<div class="container-xl py-4">

<div class="hdr">
  <div class="row align-items-center">
    <div class="col">
      <h1 class="h3 fw-bold mb-1">Reporte de Categorización Automática de Tickets</h1>
      <p class="mb-0 opacity-75 small">Clínica Universitaria</p>
    </div>
    <div class="col-auto text-end small">
      <div class="opacity-75 mb-2">Generado el<br><strong>__GENERATED_AT__</strong></div>
      <div>__BADGE_MODEL__ __BADGE_SOURCE__</div>
    </div>
  </div>
</div>

<div class="row g-3 mb-3">
  <div class="col-6 col-md-3"><div class="kpi" style="border-color:#3b82f6">
    <div class="kpi-lbl">Tickets procesados</div>
    <div class="kpi-val text-primary">__TOTAL__</div>
    <div class="kpi-sub">dataset completo</div>
  </div></div>
  <div class="col-6 col-md-3"><div class="kpi" style="border-color:#16a34a">
    <div class="kpi-lbl">Precisión global</div>
    <div class="kpi-val" style="color:#16a34a">__ACCURACY__%</div>
    <div class="kpi-sub">__CORRECT__ / __EVALUATED__ evaluados</div>
  </div></div>
  <div class="col-6 col-md-3"><div class="kpi" style="border-color:#8b5cf6">
    <div class="kpi-lbl">Tiempo total</div>
    <div class="kpi-val" style="color:#8b5cf6">__TOTAL_S__ s</div>
    <div class="kpi-sub">__TOTAL_MS__ ms en total</div>
  </div></div>
  <div class="col-6 col-md-3"><div class="kpi" style="border-color:#f59e0b">
    <div class="kpi-lbl">Tiempo promedio</div>
    <div class="kpi-val" style="color:#f59e0b">__AVG__ ms</div>
    <div class="kpi-sub">por ticket procesado</div>
  </div></div>
</div>

<div class="row g-3 mb-4">
  <div class="col-6 col-md-3"><div class="kpi" style="border-color:#6366f1">
    <div class="kpi-lbl">Por keyword</div>
    <div class="kpi-val" style="color:#6366f1">__KW_N__</div>
    <div class="kpi-sub">__KW_PCT__% · ~__KW_AVG__ ms/ticket</div>
  </div></div>
  <div class="col-6 col-md-3"><div class="kpi" style="border-color:#f97316">
    <div class="kpi-lbl">Por IA</div>
    <div class="kpi-val" style="color:#f97316">__AI_N__</div>
    <div class="kpi-sub">__AI_PCT__% · ~__AI_AVG__ ms/ticket</div>
  </div></div>
  <div class="col-6 col-md-3"><div class="kpi" style="border-color:#0ea5e9">
    <div class="kpi-lbl">Memoria RAM </div>
    <div class="kpi-val" style="color:#0ea5e9">__MEM__ MB</div>
    <div class="kpi-sub">memory_get_peak_usage()</div>
  </div></div>
  <div class="col-6 col-md-3"><div class="kpi" style="border-color:#64748b">
    <div class="kpi-lbl">CPU </div>
    <div class="kpi-val" style="color:#64748b">__CPU_U__ ms</div>
    <div class="kpi-sub">usuario · __CPU_S__ ms sistema</div>
  </div></div>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-3"><div class="cht">
    <div class="stitle">Método de clasificación</div>
    <canvas id="cM"></canvas>
  </div></div>
  <div class="col-md-3"><div class="cht">
    <div class="stitle">Precisión por categoría</div>
    <canvas id="cP"></canvas>
  </div></div>
  <div class="col-md-3"><div class="cht">
    <div class="stitle">Tiempo prom. por método (ms)</div>
    <canvas id="cT"></canvas>
  </div></div>
  <div class="col-md-3"><div class="cht">
    <div class="stitle">Tickets asignados por categoría</div>
    <canvas id="cD"></canvas>
  </div></div>
</div>

<div class="card border-0 shadow-sm mb-4">
  <div class="card-body">
    <div class="stitle">Resumen por categoría</div>
    <table class="table table-sm table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>Categoría</th>
          <th class="text-center">Asignados</th>
          <th class="text-center">Correctos</th>
          <th class="text-center">Incorrectos</th>
          <th class="text-center">Precisión</th>
          <th class="text-center">T. prom. (ms)</th>
        </tr>
      </thead>
      <tbody id="catBody"></tbody>
      <tfoot class="table-light fw-bold">
        <tr>
          <td>Total</td>
          <td class="text-center" id="fT"></td>
          <td class="text-center" id="fC"></td>
          <td class="text-center" id="fI"></td>
          <td class="text-center" id="fA"></td>
          <td></td>
        </tr>
      </tfoot>
    </table>
  </div>
</div>

<div class="card border-0 shadow-sm">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div class="stitle mb-0">Resultados individuales</div>
      <input id="qS" type="text" class="form-control form-control-sm" style="max-width:280px" placeholder="Buscar...">
    </div>
    <div style="max-height:520px;overflow-y:auto">
      <table class="table table-sm table-hover align-middle mb-0">
        <thead class="table-dark sticky-top">
          <tr>
            <th style="width:52px">#</th>
            <th>Vista previa del mensaje</th>
            <th style="width:130px">Esperada</th>
            <th style="width:130px">Asignada</th>
            <th style="width:90px">Método</th>
            <th style="width:75px" class="text-end">ms</th>
            <th style="width:40px" class="text-center">OK</th>
          </tr>
        </thead>
        <tbody id="resB"></tbody>
      </table>
    </div>
    <p class="text-muted small mt-2 mb-0" id="resN"></p>
  </div>
</div>

<footer class="text-center text-muted small mt-4 pb-3">
  Generado por <code>app:ticket-categorization</code> &nbsp;·&nbsp; Ollama &nbsp;·&nbsp; Laravel
</footer>
</div>

<script>
const STATS=__STATS__;
const RESULTS=__RESULTS__;
const CATS=__CATEGORIES_LIST__;
const CLRS={'Orientación':'#3b82f6','Técnicas':'#f59e0b','Urgencia':'#ef4444','Auditorías':'#10b981','Redes':'#06b6d4','Default':'#94a3b8'};

/* Estadísticas por categoría */
function buildCS(data){
  const m={};
  CATS.forEach(c=>{m[c]={total:0,correct:0,incorrect:0,times:[]};});
  data.forEach(r=>{
    const c=r.assigned;
    if(!m[c])m[c]={total:0,correct:0,incorrect:0,times:[]};
    m[c].total++;
    if(r.ok==='\u2713')m[c].correct++;
    else if(r.ok==='\u2717')m[c].incorrect++;
    if(r.time_ms!=null)m[c].times.push(r.time_ms);
  });
  return m;
}
const CS=buildCS(RESULTS);

/* Tabla por categoría */
let fT=0,fC=0,fI=0;
CATS.forEach(cat=>{
  const s=CS[cat]||{total:0,correct:0,incorrect:0,times:[]};
  const ev=s.correct+s.incorrect;
  const acc=ev>0?(s.correct/ev*100).toFixed(1)+'%':'—';
  const avgT=s.times.length>0?(s.times.reduce((a,b)=>a+b,0)/s.times.length).toFixed(1):'—';
  const color=CLRS[cat]||CLRS['Default'];
  const bar=ev>0?`<div class="progress mt-1" style="height:5px"><div class="progress-bar" style="width:${(s.correct/ev*100).toFixed(0)}%;background:${color}"></div></div>`:'';
  document.getElementById('catBody').insertAdjacentHTML('beforeend',
    `<tr><td><span class="badge text-white" style="background:${color}">${cat}</span></td>`+
    `<td class="text-center fw-bold">${s.total}</td>`+
    `<td class="text-center text-success fw-bold">${s.correct}</td>`+
    `<td class="text-center text-danger">${s.incorrect}</td>`+
    `<td style="min-width:120px"><span style="color:${color};font-weight:600">${acc}</span>${bar}</td>`+
    `<td class="text-center text-muted small">${avgT}</td></tr>`
  );
  fT+=s.total;fC+=s.correct;fI+=s.incorrect;
});
document.getElementById('fT').textContent=fT;
document.getElementById('fC').textContent=fC;
document.getElementById('fI').textContent=fI;
document.getElementById('fA').textContent=(fC+fI)>0?((fC/(fC+fI))*100).toFixed(1)+'%':'—';

/* Tabla de resultados individuales */
function render(data){
  const okM={'\u2713':'<span class="oky">✓</span>','\u2717':'<span class="okn">✗</span>'};
  document.getElementById('resB').innerHTML=data.map(r=>
    `<tr><td class="text-muted">${r.id}</td>`+
    `<td style="white-space:pre-wrap;word-break:break-word;max-width:480px;font-size:.82rem">${(r.preview||'').replace(/</g,'&lt;')}</td>`+
    `<td><small class="text-muted">${r.expected||'—'}</small></td>`+
    `<td style="color:${CLRS[r.assigned]||CLRS['Default']};font-weight:600">${r.assigned||'?'}</td>`+
    `<td><span class="badge ${r.method==='keyword'?'bkw':'bai'}">${r.method}</span></td>`+
    `<td class="text-end text-muted small">${r.time_ms}</td>`+
    `<td class="text-center">${okM[r.ok]||'<span class="text-muted">—</span>'}</td></tr>`
  ).join('');
  document.getElementById('resN').textContent='Mostrando '+data.length+' de '+RESULTS.length+' registros';
}
render(RESULTS);
document.getElementById('qS').addEventListener('input',function(){
  const q=this.value.toLowerCase();
  render(RESULTS.filter(r=>
    String(r.id).includes(q)||(r.preview||'').toLowerCase().includes(q)||
    (r.expected||'').toLowerCase().includes(q)||(r.assigned||'').toLowerCase().includes(q)
  ));
});

/* Chart.js */
Chart.defaults.font.family="'Segoe UI',system-ui,sans-serif";

new Chart(document.getElementById('cM'),{
  type:'doughnut',
  data:{
    labels:['Keyword','IA'],
    datasets:[{data:[STATS.keyword_count,STATS.ai_count],backgroundColor:['#6366f1','#f97316'],borderWidth:2}]
  },
  options:{cutout:'60%',plugins:{legend:{position:'bottom',labels:{boxWidth:12}}}}
});

new Chart(document.getElementById('cP'),{
  type:'bar',
  data:{
    labels:CATS,
    datasets:[{
      label:'Precisión (%)',
      data:CATS.map(c=>{const s=CS[c];const ev=s.correct+s.incorrect;return ev>0?+(s.correct/ev*100).toFixed(1):0;}),
      backgroundColor:CATS.map(c=>CLRS[c]||CLRS['Default']),
      borderRadius:4
    }]
  },
  options:{indexAxis:'y',scales:{x:{max:100,ticks:{callback:v=>v+'%'}}},plugins:{legend:{display:false}}}
});

new Chart(document.getElementById('cT'),{
  type:'bar',
  data:{
    labels:['Keyword','IA'],
    datasets:[{label:'ms',data:[STATS.avg_keyword_time_ms,STATS.avg_ai_time_ms],backgroundColor:['#6366f1','#f97316'],borderRadius:4}]
  },
  options:{scales:{y:{ticks:{callback:v=>v+' ms'}}},plugins:{legend:{display:false}}}
});

new Chart(document.getElementById('cD'),{
  type:'bar',
  data:{
    labels:CATS,
    datasets:[{label:'Tickets',data:CATS.map(c=>CS[c]?.total||0),backgroundColor:CATS.map(c=>CLRS[c]||CLRS['Default']),borderRadius:4}]
  },
  options:{plugins:{legend:{display:false}}}
});
</script>
</body>
</html>
HTML;

        $modelBadge = '<span class="badge" style="background:#f3e8ff;color:#7c3aed;border:1px solid #d8b4fe">🤖 ' . htmlspecialchars($stats['model']) . '</span>';

        $sourceBadge = '<span class="badge" style="background:#fefce8;color:#a16207;border:1px solid #fde047">&#x1F4C4; JSON</span>';

        return str_replace(
            [
                '__GENERATED_AT__', '__TOTAL__',    '__ACCURACY__', '__CORRECT__', '__EVALUATED__',
                '__TOTAL_S__',      '__TOTAL_MS__', '__AVG__',
                '__KW_N__',         '__KW_PCT__',   '__KW_AVG__',
                '__AI_N__',         '__AI_PCT__',   '__AI_AVG__',
                '__MEM__',          '__CPU_U__',    '__CPU_S__',
                '__BADGE_MODEL__', '__BADGE_SOURCE__',
                '__STATS__',        '__RESULTS__',
                '__CATEGORIES_LIST__',
            ],
            [
                $stats['generated_at'],   $stats['total_tickets'],       $stats['accuracy'],
                $stats['correct'],        $evaluated,
                $totalSec,                $stats['total_time_ms'],        $stats['avg_time_ms'],
                $stats['keyword_count'],  $kwPct,                        $stats['avg_keyword_time_ms'],
                $stats['ai_count'],       $aiPct,                        $stats['avg_ai_time_ms'],
                $stats['memory_peak_mb'], $stats['cpu_user_ms'],         $stats['cpu_sys_ms'],
                $modelBadge,              $sourceBadge,
                $statsJson,               $resJson,
                $categoriesInResults->toJson(),
            ],
            $tpl
        );
    }

    private function buildSystemPrompt($categories): string
    {
        $categoryNames = $categories->pluck('topic')->implode(', ');
        
        $prompt = "Actúa como un experto en soporte técnico. Tu tarea es clasificar tickets de soporte.\n";
        $prompt .= "Responde ÚNICAMENTE con el nombre de la categoría. No añadas texto extra.\n\n";
        $prompt .= "CATEGORÍAS DISPONIBLES: [{$categoryNames}]\n\n";
        $prompt .= "REGLAS POR CATEGORÍA:\n";

        $allExamples = [];

        foreach ($categories as $category) {
            $cleanNotes = strip_tags($category->notes);
            
            // Extraer descripción
            $description = $category->topic;
            if (preg_match('/descripción:\s*(.*?)(?=keywords:|ejemplos:|$)/is', $cleanNotes, $matches)) {
                $description = trim($matches[1]);
            }

            $prompt .= "- {$category->topic}: {$description}\n";

            if (preg_match('/ejemplos:\s*(.*?)(?=keywords:|descripción:|$)/is', $cleanNotes, $exMatches)) {
                $examplesList = explode(',', $exMatches[1]);
                foreach ($examplesList as $example) {
                    $trimmed = trim($example);
                    if ($trimmed !== '') {
                        $allExamples[] = "- '{$trimmed}' -> {$category->topic}";
                    }
                }
            }
        }

        if (!empty($allExamples)) {
            $prompt .= "\nEJEMPLOS DE REFERENCIA:\n" . implode("\n", $allExamples) . "\n";
        }

        $prompt .= "\nINSTRUCCIÓN FINAL: Analiza el sentimiento y la intención. ";
        $prompt .= "Si no estás seguro, elige la categoría más probable de la lista. ";
        $prompt .= "Respuesta de una sola palabra:";

        return $prompt;
    }

    public function chatWithIA(string $message, string $systemPrompt = '', string $model = null): ?string
    {
        $model = $model ?? config('services.ollama.model');
        if ($this->option('driver') === 'python') {
            return $this->chatWithIAPython($message, $systemPrompt, $model);
        }

        $messages = [];

        if ($systemPrompt !== '') {
            $messages[] = ['role' => 'system', 'content' => $systemPrompt];
        }

        // Ticket real enviado al final
        $messages[] = ['role' => 'user', 'content' => $message];

        // Corrección de URL: Prioriza .env, luego config, luego localhost
        $baseUrl = env('OLLAMA_URL') ?: (config('services.ollama.url') ?: 'http://localhost:11434');

        try {
            $response = Http::timeout(60)->withHeaders([
                'Content-Type' => 'application/json',
            ])->post(rtrim($baseUrl, '/') . '/api/chat', [
                'model'    => $model,
                'messages' => $messages,
                'options'  => [
                    'temperature' => 0.0,
                    'num_ctx'     => 1024,
                    'num_predict' => 20,
                ],
                'stream'   => false,
            ]);
        } catch (\Exception $e) {
            Log::error('Excepción al conectar con Ollama: ' . $e->getMessage());
            return null;
        }

        if (!$response->successful()) {
            Log::error('Error al conectar con la IA: ' . $response->body());
            return null;
        }

        return trim($response->json('message.content'));
    }

    private function chatWithIAPython(string $message, string $systemPrompt = '', string $model = null): ?string
    {
        $model = $model ?? config('services.ollama.model');
        $scriptPath = base_path('scripts/ia_classify.py');

        if (!file_exists($scriptPath)) {
            Log::error("Script Python no encontrado: {$scriptPath}");
            return null;
        }

        $payload = json_encode(
            ['message' => $message, 'system' => $systemPrompt, 'model' => $model],
            JSON_UNESCAPED_UNICODE
        );

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open(['python3', $scriptPath], $descriptors, $pipes);

        if (!is_resource($process)) {
            Log::error('No se pudo iniciar el proceso Python.');
            return null;
        }

        fwrite($pipes[0], $payload);
        fclose($pipes[0]);

        $output   = stream_get_contents($pipes[1]);
        $stderr   = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        if ($exitCode !== 0) {
            Log::error("Python exit({$exitCode}): {$stderr}");
            return null;
        }

        return trim($output) ?: null;
    }
}