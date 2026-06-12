<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class KeywordDetectionTest extends TestCase
{
    private array $keywords = [
        'Urgencia'    => [
            'urgencia', 'urgente', 'emergencia', 'crítico', 'critico',
            'inmediato', 'inmediata', 'grave', 'riesgo', 'peligro',
            'hackear', 'hackearon', 'hackeado', 'hackeo', 'hack', 'hacker',
            'intrusión', 'intrusion', 'acceso no autorizado', 'brecha',
            'vulnerabilidad', 'ataque', 'ciberataque', 'robo de datos',
            'filtracion', 'filtración', 'comprometido', 'comprometida',
            'base de datos comprometida', 'entraron al sistema', 'entraron a la base',
        ],
        'Auditorías'  => [
            'auditoría', 'auditoria', 'auditar', 'revisión', 'revision',
            'control de calidad', 'cumplimiento', 'inspección', 'inspeccion', 'normativa',
        ],
        'Orientación' => [
            'orientación', 'orientacion', 'necesito ayuda', 'necesito información',
            'necesito informacion', 'información', 'informacion', 'turno', 'consulta',
            'cómo hago', 'como hago', 'dónde', 'donde', 'tramite', 'trámite', 'guía', 'guia',
        ],
        'Técnicas'    => [
            'técnica', 'tecnica', 'técnico', 'tecnico', 'sistema', 'red', 'firewall',
            'software', 'hardware', 'configurar', 'configuración', 'configuracion',
            'instalar', 'instalación', 'instalacion', 'equipo', 'computadora', 'servidor',
            'error', 'falla', 'conectividad', 'internet', 'acceso', 'contraseña', 'password',
        ],
    ];

    private function detectCategory(string $texto): ?string
    {
        $textoLower = mb_strtolower($texto);
        foreach ($this->keywords as $categoryName => $words) {
            foreach ($words as $word) {
                if (str_contains($textoLower, $word)) {
                    return $categoryName;
                }
            }
        }
        return null;
    }

    public function test_is_emergency_by_hack(): void
    {
        $this->assertEquals('Urgencia', $this->detectCategory('Han hackeado nuestro servidor de producción'));
    }

    public function test_is_emergency_by_attack(): void
    {
        $this->assertEquals('Urgencia', $this->detectCategory('Sufrimos un ciberataque esta madrugada'));
    }

    public function test_is_emergency_by_vulnerability(): void
    {
        $this->assertEquals('Urgencia', $this->detectCategory('Detectamos una brecha de seguridad en la red'));
    }

    public function test_is_emergency_by_unauthorized_access(): void
    {
        $this->assertEquals('Urgencia', $this->detectCategory('Hubo un acceso no autorizado al sistema de historia clínica'));
    }

    public function test_is_audit_by_audit(): void
    {
        $this->assertEquals('Auditorías', $this->detectCategory('Necesito una auditoría de los accesos del mes pasado'));
    }

    public function test_is_audit_by_review(): void
    {
        $this->assertEquals('Auditorías', $this->detectCategory('Se requiere una revisión de los registros de turnos'));
    }

    public function test_is_audit_by_regulation(): void
    {
        $this->assertEquals('Auditorías', $this->detectCategory('El área de cumplimiento normativa solicita un informe'));
    }

    public function test_is_orientation_by_consultation(): void
    {
        $this->assertEquals('Orientación', $this->detectCategory('Tengo una consulta sobre cómo sacar un turno'));
    }

    public function test_is_orientation_by_appointment(): void
    {
        $this->assertEquals('Orientación', $this->detectCategory('Quiero saber cómo pedir un turno con el médico'));
    }

    public function test_is_orientation_by_where(): void
    {
        $this->assertEquals('Orientación', $this->detectCategory('¿Donde puedo encontrar el formulario de admisión?'));
    }

    public function test_is_technical_by_error(): void
    {
        $this->assertEquals('Técnicas', $this->detectCategory('El sistema arroja un error al intentar iniciar sesión'));
    }

    public function test_is_technical_by_firewall(): void
    {
        $this->assertEquals('Técnicas', $this->detectCategory('El firewall está bloqueando el acceso a la VPN'));
    }

    public function test_is_technical_by_printer(): void
    {
        $this->assertEquals('Técnicas', $this->detectCategory('El equipo de impresión no responde desde ayer'));
    }

    public function test_is_technical_by_password(): void
    {
        $this->assertEquals('Técnicas', $this->detectCategory('Olvidé mi password y no puedo entrar al sistema'));
    }

    // ── Insensibilidad a mayúsculas ───────────────────────────────────────────

    public function test_is_emergency_in_uppercase(): void
    {
        $this->assertEquals('Urgencia', $this->detectCategory('HACKEARON NUESTRA BASE DE DATOS'));
    }

    public function test_is_technical_in_uppercase(): void
    {
        $this->assertEquals('Técnicas', $this->detectCategory('HAY UN ERROR EN EL SISTEMA'));
    }

    public function test_is_orientation_in_uppercase(): void
    {
        $this->assertEquals('Orientación', $this->detectCategory('NECESITO AYUDA CON MI TURNO'));
    }

    // ── Sin coincidencia ─────────────────────────────────────────────────────

    public function test_returns_null_when_no_keywords(): void
    {
        $this->assertNull($this->detectCategory('Buenos días, me comunico para saludar a todos'));
    }

    public function test_returns_null_with_generic_text(): void
    {
        $this->assertNull($this->detectCategory('Hola, quisiera hacer una pregunta sobre la institución'));
    }

    public function test_is_emergency_when_keyword_is_first(): void
    {
        $result = $this->detectCategory('Hubo un hackeo y además hay un error en el servidor');
        $this->assertEquals('Urgencia', $result);
    }

    public function test_first_category_in_order_wins_in_case_of_tie(): void
    {
        $result = $this->detectCategory('Se necesita una auditoría urgente del sistema');
        $this->assertEquals('Urgencia', $result);
    }

    // ── Keyword embebida en texto largo ──────────────────────────────────────

    public function test_detects_keyword_in_long_text(): void
    {
        $texto = 'Estimado equipo de soporte, les escribo para informarles que esta mañana '
               . 'notamos que habían comprometido el servidor principal de registros clínicos. '
               . 'Necesitamos asistencia urgente.';

        $this->assertEquals('Urgencia', $this->detectCategory($texto));
    }

    public function test_detects_keyword_with_accents(): void
    {
        $this->assertEquals('Auditorías', $this->detectCategory('Se solicita una auditoría de los procesos internos'));
    }

    public function test_detects_keyword_without_accents(): void
    {
        $this->assertEquals('Auditorías', $this->detectCategory('Se solicita una auditoria de los procesos internos'));
    }
}
