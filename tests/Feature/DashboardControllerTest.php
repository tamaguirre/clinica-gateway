<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.dashboard_token' => 'test_token_123']);
    }

    public function test_dashboard_renders_successfully(): void
    {
        Http::fake([
            '*/api/generate' => Http::response(['status' => 'success'], 200),
        ]);

        $response = $this->get('/dashboard?token=test_token_123');

        $response->assertStatus(200);
        $response->assertViewIs('dashboard.index');
        $response->assertSee('Dashboard');
        $response->assertSee('IA Online');
        $response->assertDontSee('Alerta de Rendimiento: Posible colapso de modo');
    }

    public function test_dashboard_renders_mode_collapse_warning(): void
    {
        Http::fake([
            '*/api/generate' => Http::response([], 500),
        ]);

        $warning = 'Colapso de modo detectado: El 85% de los tickets fueron asignados a Urgencia.';
        Cache::put('model_mode_collapse_warning', $warning);

        $response = $this->get('/dashboard?token=test_token_123');

        $response->assertStatus(200);
        $response->assertSee('Alerta de Rendimiento: Posible colapso de modo');
        $response->assertSee($warning);
        $response->assertSee('IA Offline');
    }
}
