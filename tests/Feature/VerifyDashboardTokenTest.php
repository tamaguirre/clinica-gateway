<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerifyDashboardTokenTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.dashboard_token' => 'secure_test_token']);
    }

    public function test_accessing_dashboard_without_token_returns_403(): void
    {
        $response = $this->get('/dashboard');

        $response->assertStatus(403);
        $response->assertSee('Acceso Restringido');
        $response->assertSee('Se requiere un token de acceso válido en la URL');
    }

    public function test_accessing_dashboard_with_invalid_token_returns_403(): void
    {
        $response = $this->get('/dashboard?token=wrong_token');

        $response->assertStatus(403);
        $response->assertSee('Acceso Restringido');
    }

    public function test_accessing_dashboard_with_valid_token_allows_access(): void
    {
        // For testing, since the dashboard queries ost_ticket, we need to bypass any potential DB errors
        // by verifying it loads the view successfully or redirects.
        // Actually, the database is fully migrated by RefreshDatabase, so empty tables will not cause crashes.
        $response = $this->get('/dashboard?token=secure_test_token');

        $response->assertStatus(200);
        $response->assertSee('Dashboard');
    }

    public function test_accessing_ticket_report_without_token_returns_403(): void
    {
        $response = $this->get('/ticket-report');

        $response->assertStatus(403);
    }
}
