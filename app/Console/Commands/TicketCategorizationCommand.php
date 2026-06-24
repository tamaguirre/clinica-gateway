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
            ->where('flags', 2)
            ->where('topic_id', '!=', config('app.general_topic'))
            ->get(['topic_id', 'topic', 'sla_id', 'priority_id', 'notes', 'dept_id']);

        if ($categories->isEmpty()) {
            $this->error('No se encontraron categorías disponibles.');
            return Command::FAILURE;
        }

        // Extraer keywords desde el campo 'notes' de cada Topic
        $keywords = $categories->pluck('notes', 'topic')
            ->map(function ($notes) {
                if (empty($notes) || !preg_match("/keywords:\s*\[(.*?)\]/s", $notes, $matches)) {
                    return [];
                }
                preg_match_all("/['\"](.*?)['\"]/", $matches[1], $wordsMatches);
                return $wordsMatches[1];
            })
            ->filter() // Eliminar categorías sin keywords
            ->toArray();


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
            $textoLower = mb_strtolower($texto);
            $matched = null;
            $method = '';

            // 1. Detección por keywords
            if (!$this->option('force-ai')) {
                foreach ($keywords as $categoryName => $words) {
                    foreach ($words as $word) {
                        if (str_contains($textoLower, $word)) {
                            $matched = $categories->first(fn($cat) => $cat->topic === $categoryName);
                            $method = 'keyword';
                            break 2;
                        }
                    }
                }
            }

            // 2. Si no hay match por keyword, usar IA
            if (!$matched) {
                $method = 'AI';
                $systemPrompt = $this->buildSystemPrompt($categories);
                $response = $this->chatWithIA($texto, $systemPrompt);

                if ($this->option('debug')) $this->info("Respuesta IA: " . ($response ?? 'NULL'));

                if ($response === null) {
                    $this->error("Ticket #{$ticket->ticket_id}: no se pudo obtener respuesta de la IA.");
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
                    } else {
                        $this->warn("Ticket #{$ticket->ticket_id} → Categoría no reconocida: \"{$suggestedName}\". No se actualizó.");
                        continue;
                    }
                }
            }

            // 3. Actualizar el ticket con la categoría encontrada
            $ticket->update([
                'dept_id'     => $matched->dept_id,
                'topic_id'    => $matched->topic_id,
                'sla_id'      => $matched->sla_id,
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

            $this->info("Ticket #{$ticket->ticket_id} [{$method}] → Actualizado: topic={$matched->topic}");
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

        return view('ticket-report', [
            'results' => $results,
            'stats' => $stats,
            'evaluated' => $evaluated,
            'totalSec' => $totalSec,
            'categoriesInResults' => $categoriesInResults,
            'kwPct' => $kwPct,
            'aiPct' => $aiPct,
        ])->render();
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
        $baseUrl =config('services.ollama.url', 'http://localhost:11434');

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