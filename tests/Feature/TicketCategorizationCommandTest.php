<?php

namespace Tests\Feature;

use App\Models\Thread;
use App\Models\ThreadEntry;
use App\Models\Ticket;
use App\Models\Topic;
use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;


class TicketCategorizationCommandTest extends TestCase
{
    use RefreshDatabase;

    private string $ollamaUrl = 'http://localhost:11434/api/chat';
    private string $tempJson;

    protected function setUp(): void
    {
        parent::setUp();
        // Limpiar archivos de pruebas anteriores
        @unlink(storage_path('app/tickets-report.html'));
        @unlink(storage_path('app/tickets-results.json'));
        $this->tempJson = storage_path('app/test-tickets-' . uniqid() . '.json');
    }

    protected function tearDown(): void
    {
        @unlink($this->tempJson);
        @unlink(storage_path('app/tickets-results.json'));
        @unlink(storage_path('app/tickets-report.html'));
        parent::tearDown();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function seedCategories(): void
    {
        Topic::factory()->orientacion()->create();
        Topic::factory()->tecnicas()->create();
        Topic::factory()->urgencia()->create();
        Topic::factory()->auditorias()->create();
    }

    private function createTicketWithBody(string $body, int $topicId = 16): Ticket
    {
        $ticket = Ticket::factory()->create(['topic_id' => $topicId]);
        $thread = Thread::create(['object_id' => $ticket->ticket_id, 'object_type' => 'T']);
        ThreadEntry::create(['thread_id' => $thread->id, 'type' => 'M', 'body' => $body]);
        return $ticket;
    }

    private function writeJson(array $tickets): void
    {
        file_put_contents($this->tempJson, json_encode($tickets));
    }

    private function fakeOllama(string $category): void
    {
        Http::fake([
            $this->ollamaUrl => Http::response(
                ['message' => ['content' => $category]], 200
            ),
        ]);
    }

    private function loadResults(): array
    {
        $path = storage_path('app/tickets-results.json');
        $this->assertFileExists($path, 'El archivo tickets-results.json no fue generado.');
        return json_decode(file_get_contents($path), true);
    }

    private function runCommand(array $options = []): int
    {
        return Artisan::call('app:ticket-categorization', array_merge(
            ['--no-report' => true],
            $options
        ));
    }

    private function runJson(array $extraOptions = []): int
    {
        return $this->runCommand(array_merge(
            ['--from-json' => $this->tempJson],
            $extraOptions
        ));
    }


    public function test_fails_when_no_categories_in_db(): void
    {
        $this->assertEquals(Command::FAILURE, $this->runCommand());
    }

    // ── Modo JSON — errores de entrada ────────────────────────────────────────

    public function test_json_fails_if_file_does_not_exist(): void
    {
        $this->seedCategories();

        $code = $this->runCommand(['--from-json' => '/ruta/inexistente/tickets.json', '--no-report' => true]);

        $this->assertEquals(Command::FAILURE, $code);
    }

    public function test_json_fails_if_file_is_empty(): void
    {
        $this->seedCategories();
        file_put_contents($this->tempJson, '[]');

        $this->assertEquals(Command::FAILURE, $this->runJson());
    }

    // ── Modo JSON — keywords ──────────────────────────────────────────────────

    public function test_json_categorizes_by_keyword_urgency(): void
    {
        $this->seedCategories();
        $this->writeJson([
            ['id' => 1, 'body' => 'Nos hackearon el servidor de bases de datos', 'expected' => 'Urgencia'],
        ]);

        $this->runJson();

        $r = $this->loadResults()[0];
        $this->assertEquals('Urgencia', $r['assigned']);
        $this->assertEquals('keyword',  $r['method']);
        $this->assertEquals('✓',        $r['ok']);
    }

    public function test_json_categorizes_by_keyword_techniques(): void
    {
        $this->seedCategories();
        $this->writeJson([
            ['id' => 1, 'body' => 'El equipo de laboratorio no responde al encendido', 'expected' => 'Técnicas'],
        ]);

        $this->runJson();

        $r = $this->loadResults()[0];
        $this->assertEquals('Técnicas', $r['assigned']);
        $this->assertEquals('keyword',  $r['method']);
    }

    public function test_json_keyword_marks_incorrect_if_expected_does_not_match(): void
    {
        $this->seedCategories();
        $this->writeJson([
            ['id' => 1, 'body' => 'Hubo un hackeo masivo al sistema principal', 'expected' => 'Técnicas'],
        ]);

        $this->runJson();

        $r = $this->loadResults()[0];
        $this->assertEquals('Urgencia', $r['assigned']);
        $this->assertEquals('✗',        $r['ok']);
    }

    public function test_json_categorizes_by_ai_when_no_keyword(): void
    {
        $this->seedCategories();
        $this->writeJson([
            ['id' => 1, 'body' => 'Un empleado solicita permiso para una capacitación externa el próximo mes', 'expected' => 'Técnicas'],
        ]);
        $this->fakeOllama('Técnicas');

        $this->runJson();

        $r = $this->loadResults()[0];
        $this->assertEquals('Técnicas', $r['assigned']);
        $this->assertEquals('AI',       $r['method']);
        $this->assertEquals('✓',        $r['ok']);
    }

    public function test_json_ai_marks_incorrect_if_expected_does_not_match(): void
    {
        $this->seedCategories();
        $this->writeJson([
            ['id' => 1, 'body' => 'Un empleado solicita permiso para una capacitación externa', 'expected' => 'Urgencia'],
        ]);
        $this->fakeOllama('Técnicas');

        $this->runJson();

        $r = $this->loadResults()[0];
        $this->assertEquals('Técnicas', $r['assigned']);
        $this->assertEquals('✗',        $r['ok']);
    }

    public function test_json_ai_accepts_name_without_accent_due_to_similarity(): void
    {
        $this->seedCategories();
        $this->writeJson([
            ['id' => 1, 'body' => 'Un empleado solicita permiso para una capacitación externa', 'expected' => 'Auditorías'],
        ]);
        $this->fakeOllama('Auditorias'); // sin tilde → similitud >= 70 %

        $this->runJson();

        $r = $this->loadResults()[0];
        $this->assertEquals('Auditorías', $r['assigned']);
        $this->assertEquals('✓',          $r['ok']);
    }

    public function test_json_ai_registers_no_match_with_invalid_response(): void
    {
        $this->seedCategories();
        $this->writeJson([
            ['id' => 1, 'body' => 'Un empleado solicita permiso para una capacitación externa'],
        ]);
        $this->fakeOllama('XYZ respuesta completamente inválida que no coincide');

        $this->runJson();

        $r = $this->loadResults()[0];
        $this->assertNull($r['assigned']);
        $this->assertEquals('✗', $r['ok']);
    }

    public function test_json_processes_multiple_tickets_by_keyword(): void
    {
        $this->seedCategories();
        $this->writeJson([
            ['id' => 1, 'body' => 'Hubo un hackeo masivo en la madrugada',            'expected' => 'Urgencia'],
            ['id' => 2, 'body' => 'El equipo de laboratorio no enciende',              'expected' => 'Técnicas'],
            ['id' => 3, 'body' => 'Quiero saber cómo obtener un turno médico',        'expected' => 'Orientación'],
        ]);

        $this->runJson();

        $results = $this->loadResults();
        $this->assertCount(3, $results);
        foreach ($results as $r) {
            $this->assertEquals('keyword', $r['method']);
            $this->assertEquals('✓', $r['ok']);
        }
    }

    public function test_json_generates_results_json_file(): void
    {
        $this->seedCategories();
        $this->writeJson([
            ['id' => 1, 'body' => 'Brecha de datos detectada en el servidor principal', 'expected' => 'Urgencia'],
        ]);

        $this->runJson();

        $this->assertFileExists(storage_path('app/tickets-results.json'));
    }

    public function test_json_no_generates_html_with_no_report_flag(): void
    {
        $this->seedCategories();
        $this->writeJson([
            ['id' => 1, 'body' => 'Brecha de datos detectada en el servidor', 'expected' => 'Urgencia'],
        ]);

        $this->runJson();

        $this->assertFileDoesNotExist(storage_path('app/tickets-report.html'));
    }

    public function test_db_without_uncategorized_tickets_returns_success(): void
    {
        $this->seedCategories();

        $this->assertEquals(Command::SUCCESS, $this->runCommand());
    }

    public function test_db_categorizes_ticket_via_ai_and_updates_topic(): void
    {
        $this->seedCategories();
        $urgencia = Topic::where('topic', 'Urgencia')->first();
        $ticket   = $this->createTicketWithBody('Los servidores fueron comprometidos por atacantes externos');

        $this->fakeOllama('Urgencia');

        $this->runCommand();

        $this->assertDatabaseHas('ticket', [
            'ticket_id' => $ticket->ticket_id,
            'topic_id'  => $urgencia->topic_id,
        ]);
    }

    public function test_db_creates_ticket_cdata_if_not_exists(): void
    {
        $this->seedCategories();
        $urgencia = Topic::where('topic', 'Urgencia')->first();
        $ticket   = $this->createTicketWithBody('Brecha de seguridad detectada en la infraestructura');

        $this->fakeOllama('Urgencia');

        $this->runCommand();

        $this->assertDatabaseHas('ticket__cdata', [
            'ticket_id' => $ticket->ticket_id,
            'priority'  => $urgencia->priority_id,
        ]);
    }

    public function test_db_does_not_update_ticket_with_invalid_ai_response(): void
    {
        $this->seedCategories();
        $ticket = $this->createTicketWithBody('Un empleado requiere asistencia con una tarea pendiente de su sector');

        $this->fakeOllama('XYZ categoría que no existe');

        $this->runCommand();

        $this->assertDatabaseHas('ticket', [
            'ticket_id' => $ticket->ticket_id,
            'topic_id'  => 16,
        ]);
    }

    public function test_db_omits_ticket_without_thread(): void
    {
        $this->seedCategories();
        $ticket = Ticket::factory()->create(['topic_id' => 16]);

        $this->fakeOllama('Urgencia');

        $code = $this->runCommand();

        $this->assertEquals(Command::SUCCESS, $code);
        $this->assertDatabaseHas('ticket', ['ticket_id' => $ticket->ticket_id, 'topic_id' => 16]);
    }

    public function test_db_categorizes_multiple_tickets_independently(): void
    {
        $this->seedCategories();
        $urgencia = Topic::where('topic', 'Urgencia')->first();
        $tecnicas = Topic::where('topic', 'Técnicas')->first();

        $t1 = $this->createTicketWithBody('Ataque al servidor de producción principal');
        $t2 = $this->createTicketWithBody('La impresora de farmacia no enciende desde ayer');

        Http::fake([
            $this->ollamaUrl => Http::sequence()
                ->push(['message' => ['content' => 'Urgencia']], 200)
                ->push(['message' => ['content' => 'Técnicas']], 200),
        ]);

        $this->runCommand();

        $this->assertDatabaseHas('ticket', ['ticket_id' => $t1->ticket_id, 'topic_id' => $urgencia->topic_id]);
        $this->assertDatabaseHas('ticket', ['ticket_id' => $t2->ticket_id, 'topic_id' => $tecnicas->topic_id]);
    }
}