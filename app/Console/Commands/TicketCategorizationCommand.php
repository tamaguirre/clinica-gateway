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

#[Signature('app:ticket-categorization {--from-json= : Ruta al JSON de tickets de prueba (omite escritura en BD)} {--driver=api : Driver de inferencia: api (HTTP local) o python (subprocess ollama SDK)}')]
#[Description('Categoriza tickets usando IA via Ollama')]
class TicketCategorizationCommand extends Command
{
    public function handle()
    {
        $categories = Topic::query()
            ->where('topic_id', '!=', 16)
            ->get(['topic_id', 'topic', 'sla_id', 'priority_id']);

        if ($categories->isEmpty()) {
            $this->error('No se encontraron categorías disponibles.');
            return Command::FAILURE;
        }

        $jsonPath = $this->option('from-json');
        if ($jsonPath !== null) {
            return $this->processFromJson($jsonPath, $categories);
        }

        $tickets = Ticket::with('thread.firstEntry', 'ticketData', 'formEntry.valueWithPriority')
            ->where('topic_id', 16)
            ->get();

        if ($tickets->isEmpty()) {
            $this->warn('No se encontraron tickets con topic_id = 16.');
            return Command::SUCCESS;
        }

        $this->info("Se encontraron {$tickets->count()} tickets. Categorizando...");

        foreach ($tickets as $ticket) {
            $entry = $ticket->thread?->firstEntry;

            if (!$entry) {
                $this->warn("Ticket #{$ticket->ticket_id}: sin contenido, omitiendo.");
                continue;
            }

            $texto = strip_tags($entry->body ?? '');
            $textoLower = mb_strtolower($texto);

            $systemPrompt = "Eres un clasificador de tickets de soporte. Responde ÚNICAMENTE con una de estas 4 palabras exactas: Orientación, Técnicas, Urgencia, Auditorías.\n\n" .
                            "REGLAS ESTRICTAS DE CLASIFICACIÓN:\n" .
                            "- Orientación: Dudas, solicitud de manuales, pasos a seguir o recuperación de contraseñas.\n" .
                            "- Técnicas: Fallos de sistema, impresoras, errores de red o aplicaciones que no abren.\n" .
                            "- Urgencia: EXCLUSIVO para incidentes críticos de ciberseguridad (hackeos, ransomware, robo de datos) o caída total de infraestructura.\n" .
                            "- Auditorías: Solicitud de revisión de logs, historiales, trazabilidad o control.";
            
            // El usuario ahora solo envía el texto limpio, sin instrucciones extra.
            $userPrompt = $texto;

            $this->line("Ticket #{$ticket->ticket_id}: consultando...");

            $response = $this->chatWithSmolLM($userPrompt, $systemPrompt);

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
                    similar_text(strtolower($suggestedName), strtolower($cat->topic), $percent);
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

        $keywords = [
            'Urgencia'    => ['urgencia', 'urgente', 'emergencia', 'crítico', 'critico', 'inmediato', 'inmediata', 'grave', 'riesgo', 'peligro', 'hackear', 'hackearon', 'hackeado', 'hackeo', 'hack', 'hacker', 'intrusión', 'intrusion', 'acceso no autorizado', 'brecha', 'vulnerabilidad', 'ataque', 'ciberataque', 'robo de datos', 'filtracion', 'filtración', 'comprometido', 'comprometida', 'base de datos comprometida', 'entraron al sistema', 'entraron a la base'],
            'Auditorías'  => ['auditoría', 'auditoria', 'auditar', 'revisión', 'revision', 'control de calidad', 'cumplimiento', 'inspección', 'inspeccion', 'normativa'],
            'Orientación' => ['orientación', 'orientacion', 'necesito ayuda', 'necesito información', 'necesito informacion', 'información', 'informacion', 'turno', 'consulta', 'cómo hago', 'como hago', 'dónde', 'donde', 'tramite', 'trámite', 'guía', 'guia'],
            'Técnicas'    => ['técnica', 'tecnica', 'técnico', 'tecnico', 'sistema', 'red', 'firewall', 'software', 'hardware', 'configurar', 'configuración', 'configuracion', 'instalar', 'instalación', 'instalacion', 'equipo', 'computadora', 'servidor', 'error', 'falla', 'conectividad', 'internet', 'acceso', 'contraseña', 'password'],
        ];

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
            foreach ($keywords as $categoryName => $words) {
                foreach ($words as $word) {
                    if (str_contains($textoLower, $word)) {
                        $matchedByKeyword = $categories->first(fn($cat) => $cat->topic === $categoryName);
                        break 2;
                    }
                }
            }

            if ($matchedByKeyword) {
                $method  = 'keyword';
                $matched = $matchedByKeyword;
            } else {
                $method  = 'AI';
                $matched = null;

            $systemPrompt = "Eres un clasificador de tickets de soporte. Responde ÚNICAMENTE con una de estas 4 palabras exactas: Orientación, Técnicas, Urgencia, Auditorías.\n\n" .
                            "REGLAS ESTRICTAS DE CLASIFICACIÓN:\n" .
                            "- Orientación: Dudas, solicitud de manuales, pasos a seguir o recuperación de contraseñas.\n" .
                            "- Técnicas: Fallos de sistema, impresoras, errores de red o aplicaciones que no abren.\n" .
                            "- Urgencia: EXCLUSIVO para incidentes críticos de ciberseguridad (hackeos, ransomware, robo de datos) o caída total de infraestructura.\n" .
                            "- Auditorías: Solicitud de revisión de logs, historiales, trazabilidad o control.";
            
            $userPrompt = $texto;
            
            $this->line("Ticket #{$id}: consultando...");

            $aiStart  = microtime(true);
            $response = $this->chatWithSmolLM($userPrompt, $systemPrompt);
            $aiTimeMs = round((microtime(true) - $aiStart) * 1000, 1);

            if ($response === null) {
                $this->error("Ticket #{$id}: no se pudo obtener respuesta de la IA.");
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
                    similar_text(strtolower($suggestedName), strtolower($cat->topic), $percent);
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

        $stats = [
            'generated_at'        => date('d/m/Y H:i:s'),
            'driver'              => $this->option('driver') ?? 'api',
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

        $htmlOutput = storage_path('app/tickets-report.html');
        file_put_contents($htmlOutput, $this->generateHtmlReport($results, $stats));
        $this->info("Reporte HTML guardado en: {$htmlOutput}");

        return Command::SUCCESS;
    }

    private function generateHtmlReport(array $results, array $stats): string
    {
        // ... (El contenido de tu reporte HTML queda exactamente igual)
        $statsJson  = json_encode($stats,   JSON_UNESCAPED_UNICODE);
        $resJson    = json_encode($results, JSON_UNESCAPED_UNICODE);
        $evaluated  = $stats['correct'] + $stats['incorrect'];
        $totalSec   = number_format($stats['total_time_ms'] / 1000, 2);
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
      <div>__BADGE_DRIVER__ __BADGE_SOURCE__</div>
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
    <div class="kpi-lbl">Memoria RAM (pico)</div>
    <div class="kpi-val" style="color:#0ea5e9">__MEM__ MB</div>
    <div class="kpi-sub">memory_get_peak_usage()</div>
  </div></div>
  <div class="col-6 col-md-3"><div class="kpi" style="border-color:#64748b">
    <div class="kpi-lbl">CPU — proceso PHP</div>
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
const CATS=['Orientación','Técnicas','Urgencia','Auditorías'];
const CLRS={'Orientación':'#3b82f6','Técnicas':'#f59e0b','Urgencia':'#ef4444','Auditorías':'#10b981'};

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
  const bar=ev>0?`<div class="progress mt-1" style="height:5px"><div class="progress-bar" style="width:${(s.correct/ev*100).toFixed(0)}%;background:${CLRS[cat]}"></div></div>`:'';
  document.getElementById('catBody').insertAdjacentHTML('beforeend',
    `<tr><td><span class="badge text-white" style="background:${CLRS[cat]}">${cat}</span></td>`+
    `<td class="text-center fw-bold">${s.total}</td>`+
    `<td class="text-center text-success fw-bold">${s.correct}</td>`+
    `<td class="text-center text-danger">${s.incorrect}</td>`+
    `<td style="min-width:120px"><span style="color:${CLRS[cat]};font-weight:600">${acc}</span>${bar}</td>`+
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
    `<td style="color:${CLRS[r.assigned]||'#94a3b8'};font-weight:600">${r.assigned||'?'}</td>`+
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
      backgroundColor:CATS.map(c=>CLRS[c]),
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
    datasets:[{label:'Tickets',data:CATS.map(c=>CS[c]?.total||0),backgroundColor:CATS.map(c=>CLRS[c]),borderRadius:4}]
  },
  options:{plugins:{legend:{display:false}}}
});
</script>
</body>
</html>
HTML;

        $driverBadge = $stats['driver'] === 'python'
            ? '<span class="badge" style="background:#f0fdf4;color:#15803d;border:1px solid #86efac">&#x1F40D; Python SDK</span>'
            : '<span class="badge" style="background:#eff6ff;color:#1d4ed8;border:1px solid #93c5fd">&#x1F310; HTTP API</span>';

        $sourceBadge = '<span class="badge" style="background:#fefce8;color:#a16207;border:1px solid #fde047">&#x1F4C4; JSON</span>';

        return str_replace(
            [
                '__GENERATED_AT__', '__TOTAL__',    '__ACCURACY__', '__CORRECT__', '__EVALUATED__',
                '__TOTAL_S__',      '__TOTAL_MS__', '__AVG__',
                '__KW_N__',         '__KW_PCT__',   '__KW_AVG__',
                '__AI_N__',         '__AI_PCT__',   '__AI_AVG__',
                '__MEM__',          '__CPU_U__',    '__CPU_S__',
                '__BADGE_DRIVER__', '__BADGE_SOURCE__',
                '__STATS__',        '__RESULTS__',
            ],
            [
                $stats['generated_at'],   $stats['total_tickets'],       $stats['accuracy'],
                $stats['correct'],        $evaluated,
                $totalSec,                $stats['total_time_ms'],        $stats['avg_time_ms'],
                $stats['keyword_count'],  $kwPct,                        $stats['avg_keyword_time_ms'],
                $stats['ai_count'],       $aiPct,                        $stats['avg_ai_time_ms'],
                $stats['memory_peak_mb'], $stats['cpu_user_ms'],         $stats['cpu_sys_ms'],
                $driverBadge,             $sourceBadge,
                $statsJson,               $resJson,
            ],
            $tpl
        );
    }

    public function chatWithSmolLM(string $message, string $systemPrompt = '', string $model = 'smollm'): ?string
    {
        if ($this->option('driver') === 'python') {
            return $this->chatWithSmolLMPython($message, $systemPrompt, $model);
        }

        $messages = [];

        if ($systemPrompt !== '') {
            $messages[] = ['role' => 'system', 'content' => $systemPrompt];
        }

        // --- INICIO DE EJEMPLOS TRAMPA Y PATRONES (Few-Shot) ---
        $messages[] = ['role' => 'user', 'content' => '¿Cómo activo el doble factor en mi correo?'];
        $messages[] = ['role' => 'assistant', 'content' => 'Orientación'];

        $messages[] = ['role' => 'user', 'content' => 'La impresora de farmacia no conecta a la red local'];
        $messages[] = ['role' => 'assistant', 'content' => 'Técnicas'];

        $messages[] = ['role' => 'user', 'content' => '¡Ayuda urgente! No puedo abrir el Excel y tengo que entregar un reporte.'];
        $messages[] = ['role' => 'assistant', 'content' => 'Técnicas'];

        $messages[] = ['role' => 'user', 'content' => 'Necesito urgente saber cómo resetear mi clave del portal desde casa.'];
        $messages[] = ['role' => 'assistant', 'content' => 'Orientación'];

        $messages[] = ['role' => 'user', 'content' => '¡Ataque de ransomware! Servidores encriptados y están robando datos'];
        $messages[] = ['role' => 'assistant', 'content' => 'Urgencia'];

        $messages[] = ['role' => 'user', 'content' => 'Solicito el registro y control de logs del directorio activo'];
        $messages[] = ['role' => 'assistant', 'content' => 'Auditorías'];
        // --- FIN DE EJEMPLOS ---

        // Ticket real enviado al final
        $messages[] = ['role' => 'user', 'content' => $message];

        $response = Http::timeout(240)->withHeaders([
            'Content-Type' => 'application/json',
        ])->post(config('services.ollama.url', 'http://localhost:11434') . '/api/chat', [
            'model'    => $model,
            'messages' => $messages,
            'options'  => [
                'temperature' => 0.0,
                'num_ctx'     => 512,
                'num_thread'  => 1,
                'num_predict' => 20,
            ],
            'stream'   => false,
        ]);

        if (!$response->successful()) {
            Log::error('Error al conectar con la IA: ' . $response->body());
            return null;
        }

        return trim($response->json('message.content'));
    }

    private function chatWithSmolLMPython(string $message, string $systemPrompt = '', string $model = 'smollm'): ?string
    {
        $scriptPath = base_path('scripts/smollm_classify.py');

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
            Log::error("Python smolLM2 exit({$exitCode}): {$stderr}");
            return null;
        }

        return trim($output) ?: null;
    }
}