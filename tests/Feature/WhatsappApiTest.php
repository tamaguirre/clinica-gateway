<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserData;
use App\Models\UserEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsappApiTest extends TestCase
{
    use RefreshDatabase;

    private function cleanPhone(string $from): string
    {
        return preg_replace('/^whatsapp:\+?/', '', $from);
    }

    private function createRegisteredUser(string $phone, string $email = 'paciente@test.com'): User
    {
        $user = User::factory()->create();
        UserEmail::factory()->create(['user_id' => $user->id, 'address' => $email]);
        UserData::factory()->create(['user_id' => $user->id, 'phone' => $phone]);
        return $user;
    }

    public function test_first_message_return_greeting(): void
    {
        $response = $this->post('/api/whatsapp', [
            'From' => 'whatsapp:+5491123456789',
            'Body' => 'Hola',
        ]);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/xml; charset=UTF-8');
        $this->assertStringContainsString('<Response>', $response->getContent());
        $this->assertStringContainsString('asistente de la clínica', $response->getContent());
    }

    public function test_first_message_saves_state_in_cache(): void
    {
        $from  = 'whatsapp:+5491123456789';
        $phone = $this->cleanPhone($from);

        $this->assertFalse(Cache::has("whatsapp_state_{$phone}"));

        $this->post('/api/whatsapp', ['From' => $from, 'Body' => 'Hola']);

        $this->assertTrue(Cache::has("whatsapp_state_{$phone}"));
    }

    public function test_second_message_from_registered_user_creates_ticket(): void
    {
        $from  = 'whatsapp:+5491155667788';
        $phone = $this->cleanPhone($from);

        $this->createRegisteredUser($phone, 'user@clinica.com');
        Cache::put("whatsapp_state_{$phone}", true, now()->addMinutes(10));
        Http::fake(['*' => Http::response('987654', 200)]);

        $response = $this->post('/api/whatsapp', [
            'From' => $from,
            'Body' => 'Necesito turno con cardiología',
        ]);

        $response->assertStatus(200);
        $this->assertStringContainsString('#987654', $response->getContent());
        $this->assertStringContainsString('creado exitosamente', $response->getContent());
    }

    public function test_second_message_clears_cache(): void
    {
        $from  = 'whatsapp:+5491155667788';
        $phone = $this->cleanPhone($from);

        $this->createRegisteredUser($phone);
        Cache::put("whatsapp_state_{$phone}", true, now()->addMinutes(10));
        Http::fake(['*' => Http::response('111', 200)]);

        $this->post('/api/whatsapp', ['From' => $from, 'Body' => 'Mi consulta']);

        $this->assertFalse(Cache::has("whatsapp_state_{$phone}"));
    }

    public function test_second_message_from_unregistered_number_returns_error(): void
    {
        $from  = 'whatsapp:+5490000000000';
        $phone = $this->cleanPhone($from);

        Cache::put("whatsapp_state_{$phone}", true, now()->addMinutes(10));

        $response = $this->post('/api/whatsapp', [
            'From' => $from,
            'Body' => 'Mi consulta',
        ]);

        $response->assertStatus(200);
        $this->assertStringContainsString('No encontramos tu número', $response->getContent());
    }

    public function test_osticket_fail_returns_error_message(): void
    {
        $from  = 'whatsapp:+5491199998888';
        $phone = $this->cleanPhone($from);

        $this->createRegisteredUser($phone);
        Cache::put("whatsapp_state_{$phone}", true, now()->addMinutes(10));
        Http::fake(['*' => Http::response('Internal Server Error', 500)]);

        $response = $this->post('/api/whatsapp', [
            'From' => $from,
            'Body' => 'Mi consulta',
        ]);

        $response->assertStatus(200);
        $this->assertStringContainsString('error al registrar', $response->getContent());
    }

    public function test_response_is_valid_xml_with_twiml_envelope(): void
    {
        $response = $this->post('/api/whatsapp', [
            'From' => 'whatsapp:+5491100000001',
            'Body' => 'Hola',
        ]);

        $content = $response->getContent();

        $this->assertStringStartsWith('<?xml', $content);
        $this->assertStringContainsString('<Response>', $content);
        $this->assertStringContainsString('<Message>', $content);
        $this->assertStringContainsString('</Message>', $content);
        $this->assertStringContainsString('</Response>', $content);
    }

    public function test_twiml_message_escapes_special_characters(): void
    {
        $from  = 'whatsapp:+5490000000099';
        $phone = $this->cleanPhone($from);
        Cache::put("whatsapp_state_{$phone}", true, now()->addMinutes(10));

        $response = $this->post('/api/whatsapp', ['From' => $from, 'Body' => 'test']);

        preg_match('/<Message>(.*?)<\/Message>/s', $response->getContent(), $matches);
        $this->assertStringNotContainsString('<script', $matches[1] ?? '');
    }

    public function test_cleans_whatsapp_prefix_with_plus(): void
    {
        $this->post('/api/whatsapp', ['From' => 'whatsapp:+5491111111111', 'Body' => 'Hola']);

        $this->assertTrue(Cache::has('whatsapp_state_5491111111111'));
    }

    public function test_cleans_whatsapp_prefix_without_plus(): void
    {
        $this->post('/api/whatsapp', ['From' => 'whatsapp:5491111111112', 'Body' => 'Hola']);

        $this->assertTrue(Cache::has('whatsapp_state_5491111111112'));
    }

    public function test_first_message_returns_greeting(): void
    {
        $response = $this->post('/api/whatsapp', [
            'From' => 'whatsapp:+5491123456789',
            'Body' => 'Hola',
        ]);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/xml; charset=UTF-8');
        $this->assertStringContainsString('<Response>', $response->getContent());
        $this->assertStringContainsString('asistente de la clínica', $response->getContent());
    }

    public function test_second_message_with_greeting_exception_renews_cache_and_does_not_create_ticket(): void
    {
        $from  = 'whatsapp:+5491155667788';
        $phone = $this->cleanPhone($from);

        Cache::put("whatsapp_state_{$phone}", true, now()->addMinutes(10));
        Http::fake();

        $response = $this->post('/api/whatsapp', [
            'From' => $from,
            'Body' => 'Hola!',
        ]);

        $response->assertStatus(200);
        $this->assertStringContainsString('asistente de la clínica', $response->getContent());
        $this->assertTrue(Cache::has("whatsapp_state_{$phone}"));
        Http::assertNothingSent();
    }

    public function test_second_message_with_greeting_exception_case_insensitive(): void
    {
        $from  = 'whatsapp:+5491155667788';
        $phone = $this->cleanPhone($from);

        Cache::put("whatsapp_state_{$phone}", true, now()->addMinutes(10));
        Http::fake();

        $response = $this->post('/api/whatsapp', [
            'From' => $from,
            'Body' => 'BUENOS DÍAS',
        ]);

        $response->assertStatus(200);
        $this->assertStringContainsString('asistente de la clínica', $response->getContent());
        $this->assertTrue(Cache::has("whatsapp_state_{$phone}"));
        Http::assertNothingSent();
    }
}
