<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Twilio\Security\RequestValidator;

class VerifyTwilioSignatureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['services.twilio.auth_token' => 'dummy_token_1234567890']);
        config(['services.twilio.enable_signature_in_tests' => true]);
    }

    public function test_request_without_signature_is_forbidden(): void
    {
        $response = $this->post('/api/whatsapp', [
            'From' => 'whatsapp:+5491123456789',
            'Body' => 'Hola',
        ]);

        $response->assertStatus(403);
        $this->assertEquals('No Twilio signature provided.', $response->getContent());
    }

    public function test_request_with_invalid_signature_is_forbidden(): void
    {
        $response = $this->post('/api/whatsapp', [
            'From' => 'whatsapp:+5491123456789',
            'Body' => 'Hola',
        ], [
            'X-Twilio-Signature' => 'invalid_signature_value',
        ]);

        $response->assertStatus(403);
        $this->assertEquals('Invalid Twilio signature.', $response->getContent());
    }

    public function test_request_with_valid_signature_passes_middleware(): void
    {
        $token = 'dummy_token_1234567890';
        $validator = new RequestValidator($token);

        $url = 'http://localhost/api/whatsapp'; // Laravel's default testing base URL
        $postData = [
            'From' => 'whatsapp:+5491123456789',
            'Body' => 'Hola',
        ];

        // Calculate valid signature using the validator helper
        $signature = $validator->computeSignature($url, $postData);

        $response = $this->post('/api/whatsapp', $postData, [
            'X-Twilio-Signature' => $signature,
        ]);

        // The request passes the middleware and hits the WhatsappController, returning 200 (welcome XML message)
        $response->assertStatus(200);
        $this->assertStringContainsString('<Response>', $response->getContent());
    }
}
