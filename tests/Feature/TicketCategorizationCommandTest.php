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

    private string $ollamaUrl;
    private string $tempJson;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.general_topic' => 16]);
        config(['app.fallback_topic' => 17]);
        config(['services.ollama.url' => env('OLLAMA_URL', 'http://localhost:11434')]);
        config(['services.ollama.model' => 'qwen2.5:0.5b']);
        $this->ollamaUrl = config('services.ollama.url') . '/api/chat';
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
        Topic::factory()->orientacion()->create(['notes' => "keywords: ['turno', 'consulta', 'ayuda']"]);
        Topic::factory()->tecnicas()->create(['notes' => "keywords: ['sistema', 'equipo', 'error', 'impresora']"]);
        Topic::factory()->urgencia()->create(['notes' => "keywords: ['hackear', 'hackeo', 'brecha', 'ataque']"]);
        Topic::factory()->auditorias()->create(['notes' => "keywords: ['auditoria', 'revisión']"]);
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
            $this->ollamaUrl => Http::response(['message' => ['content' => $category]], 200),
        ]);
    }

    private function loadResults(): array
    {
        $path = storage_path('app/tickets-results.json');
        return json_decode(file_get_contents($path), true);
    }

    private function runCommand(array $options = []): int
    {
        return Artisan::call('app:ticket-categorization', array_merge(['--no-report' => true], $options));
    }

    private function runJson(array $extraOptions = []): int
    {
        return $this->runCommand(array_merge(['--from-json' => $this->tempJson], $extraOptions));
    }

    // ── Tests originales y nuevos ─────────────────────────────────────────────

    public function test_fails_when_no_categories_in_db(): void
    {
        $this->assertEquals(Command::FAILURE, $this->runCommand());
    }

    public function test_json_fails_if_file_does_not_exist(): void
    {
        $this->seedCategories();
        $this->assertEquals(Command::FAILURE, $this->runCommand(['--from-json' => '/no/existe.json']));
    }

    public function test_json_fails_if_file_is_empty(): void
    {
        $this->seedCategories();
        file_put_contents($this->tempJson, '[]');
        $this->assertEquals(Command::FAILURE, $this->runJson());
    }

    public function test_json_categorizes_by_keyword_urgency(): void
    {
        $this->seedCategories();
        $this->writeJson([['id' => 1, 'body' => 'Nos hackearon el servidor', 'expected' => 'Urgencia']]);
        $this->runJson();
        $r = $this->loadResults()[0];
        $this->assertEquals('Urgencia', $r['assigned']);
    }

    public function test_json_categorizes_by_ai_when_no_keyword(): void
    {
        $this->seedCategories();
        $this->writeJson([['id' => 1, 'body' => 'Requerimiento administrativo ordinario', 'expected' => 'Auditorías']]);
        $this->fakeOllama('Auditorías');
        $this->runJson();
        $this->assertEquals('Auditorías', $this->loadResults()[0]['assigned']);
    }

    public function test_json_ai_registers_none_with_vague_input(): void
    {
        $this->seedCategories();
        $this->writeJson([['id' => 1, 'body' => 'oye', 'expected' => null]]);
        $this->fakeOllama('none');
        $this->runJson();
        $this->assertEquals('Sin Clasificar', $this->loadResults()[0]['assigned']);
    }

    public function test_json_ai_registers_no_match_with_invalid_response(): void
    {
        $this->seedCategories();
        $this->writeJson([['id' => 1, 'body' => 'Alguna cosa']]);
        $this->fakeOllama('XYZ');
        $this->runJson();
        $this->assertNull($this->loadResults()[0]['assigned']);
    }

    public function test_db_categorizes_ticket_via_ai_and_updates_topic(): void
    {
        $this->seedCategories();
        $urgencia = Topic::where('topic', 'Urgencia')->first();
        $ticket = $this->createTicketWithBody('Los servidores fueron atacados');
        $this->fakeOllama('Urgencia');
        $this->runCommand();
        $this->assertDatabaseHas('ticket', ['ticket_id' => $ticket->ticket_id, 'topic_id' => $urgencia->topic_id]);
    }

    public function test_db_applies_fallback_on_none_ai_response(): void
    {
        $this->seedCategories();
        $ticket = $this->createTicketWithBody('hola');
        $this->fakeOllama('none');
        $this->runCommand();
        $this->assertDatabaseHas('ticket', ['ticket_id' => $ticket->ticket_id, 'topic_id' => 17]);
    }

    public function test_db_categorizes_multiple_tickets_independently(): void
    {
        $this->seedCategories();
        $t1 = $this->createTicketWithBody('Ataque');
        $t2 = $this->createTicketWithBody('Impresora falla');
        Http::fake([
            $this->ollamaUrl => Http::sequence()
                ->push(['message' => ['content' => 'Urgencia']], 200)
                ->push(['message' => ['content' => 'Técnicas']], 200),
        ]);
        $this->runCommand();
        $this->assertDatabaseHas('ticket', ['ticket_id' => $t1->ticket_id, 'topic_id' => Topic::where('topic', 'Urgencia')->first()->topic_id]);
        $this->assertDatabaseHas('ticket', ['ticket_id' => $t2->ticket_id, 'topic_id' => Topic::where('topic', 'Técnicas')->first()->topic_id]);
    }
}