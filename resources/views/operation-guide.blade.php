@extends('layouts.manual')
@section('title', 'Guía de Usuario')
@section('header-title', 'Guía de Operación para Docentes y Estudiantes')

@section('content')
    <section class="mb-12">
        <h2 class="text-2xl font-bold text-blue-800 mb-4">1. ¿Para qué sirve esta plataforma?</h2>
        <p class="text-gray-700 leading-relaxed text-lg">Esta herramienta es el centro de operaciones de la Clínica. Aquí llegan todas las solicitudes de ayuda (tickets) de personas o empresas externas, permitiendo que la gestión sea ordenada, rápida y sin depender de procesos manuales dispersos.</p>
        <img src="/img/tickets.png" alt="Gestión de Tickets" class="w-full rounded-xl my-6 border shadow-sm">
    </section>

    <section class="mb-12">
        <h2 class="text-2xl font-bold text-blue-800 mb-4">2. Roles de Usuario</h2>
        <div class="flex justify-center gap-8 mb-6">
            <svg class="w-16 h-16 fill-blue-800" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/></svg>
            <svg class="w-16 h-16 fill-blue-800" viewBox="0 0 24 24"><path d="M20 6h-4V4c0-1.1-.9-2-2-2h-4c-1.1 0-2 .9-2 2v2H4c-1.1 0-2 .9-2 2v11c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2zM10 4h4v2h-4V4zm10 15H4V8h16v11z"/></svg>
            <svg class="w-16 h-16 fill-blue-800" viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>
        </div>
        <div class="grid md:grid-cols-3 gap-6">
            <div class="bg-blue-50 p-6 rounded-xl border border-blue-100">
                <h3 class="font-bold text-blue-900">Administrador (Docente)</h3>
                <p class="text-sm mt-2 text-gray-700">Configura la plataforma, gestiona el equipo y audita estándares.</p>
            </div>
            <div class="bg-blue-50 p-6 rounded-xl border border-blue-100">
                <h3 class="font-bold text-blue-900">Agente (Estudiante)</h3>
                <p class="text-sm mt-2 text-gray-700">Recibe, analiza y responde tickets. Gestión técnica de cada caso.</p>
            </div>
            <div class="bg-blue-50 p-6 rounded-xl border border-blue-100">
                <h3 class="font-bold text-blue-900">Usuario Externo</h3>
                <p class="text-sm mt-2 text-gray-700">Usuario externo que solicita asistencia en ciberseguridad a través de la creación y seguimiento de tickets.</p>
            </div>
        </div>
    </section>

    <section class="mb-12">
        <h2 class="text-2xl font-bold text-blue-800 mb-4">3. Conceptos Clave</h2>
        <div class="space-y-4 text-gray-700">
            <p><strong>Temas de Ayuda:</strong> Categorías donde se clasifican los incidentes (ej. "Phishing").</p>
            <p><strong>Prioridad:</strong> Define la urgencia del incidente.</p>
            <p><strong>Departamentos:</strong> Áreas funcionales para la asignación de tareas.</p>
            <p><strong>SLA:</strong> Tiempos máximos de respuesta para cumplir con los compromisos.</p>
            <p><strong>Tickets:</strong> Unidad básica de trabajo; incidente único con toda la trazabilidad.</p>
        </div>
    </section>

    <section class="mb-12">
        <h2 class="text-2xl font-bold text-blue-800 mb-4">4. Dashboard Inteligente</h2>
        <p class="text-gray-700 text-lg mb-6">
            El <strong>Dashboard</strong> es el centro de monitoreo en tiempo real de la Clínica. Aquí los docentes y estudiantes pueden visualizar el comportamiento del sistema y la efectividad del motor de IA.
        </p>
        
        <div class="bg-blue-50 p-6 rounded-xl border border-blue-100">
            <h3 class="font-bold text-blue-900 mb-2">Acceso al Dashboard</h3>
            <p class="text-gray-700 mb-4">Puedes acceder al resumen completo del sistema en el siguiente enlace:</p>
            <a href="/dashboard" target="_blank" 
               class="inline-block bg-blue-800 text-white px-6 py-2 rounded-lg font-semibold hover:bg-blue-900 transition">
                Ir al Dashboard Inteligente
            </a>
        </div>

        <div class="mt-6 space-y-4 text-gray-700">
            <p><strong>¿Qué contiene este panel?</strong></p>
            <ul class="list-disc ml-6 space-y-2">
                <li><strong>Métricas Generales:</strong> Visualización de tickets totales, urgencias y casos pendientes de categorización.</li>
                <li><strong>Distribución por Categoría:</strong> Gráfico de torta que resume cómo la IA ha clasificado las solicitudes.</li>
                <li><strong>Tendencias (Tickets por día):</strong> Evolución de la carga de trabajo en los últimos 30 días.</li>
                <li><strong>Resumen Tecnológico:</strong> Visualización de la integración bajo <em>Qwen2.5, Ollama y Laravel</em>.</li>
            </ul>
        </div>
    </section>

    <section class="mb-12">
        <h2 class="text-2xl font-bold text-blue-800 mb-6">5. Guía de Tareas Administrativas</h2>
        
        <div class="mb-10">
            <h3 class="text-xl font-semibold text-gray-800 mb-4">A. ¿Cómo crear un nuevo Tema de Ayuda?</h3>
            <img src="/img/topic-edit.png" alt="Editar Tema" class="w-full rounded-lg shadow-sm border mb-4">
            <ul class="list-decimal ml-6 space-y-2 text-gray-700">
                <li>Ve a <strong>Manage > Help Topics</strong> y haz clic en <strong>Add New Help Topic</strong>.</li>
                <li>Configura el nombre y selecciona <strong>Active</strong>.</li>
                <li>En el campo de <strong>Notas Internas</strong>, debes ingresar obligatoriamente la <strong>descripción, keywords y ejemplos</strong>. Esta información es fundamental para alimentar el motor de IA y asegurar una clasificación correcta.</li>
                <li>Marca el tópico como <strong>Privado</strong> (recuerda: el único público es <em>Consulta General</em>).</li>
            </ul>
        </div>

        <div>
            <h3 class="text-xl font-semibold text-gray-800 mb-4">B. ¿Cómo agregar un nuevo usuario?</h3>
            <img src="/img/user-edit.png" alt="Editar Usuario" class="w-full rounded-lg shadow-sm border mb-4">
            <p class="text-gray-700">Ve al panel de <strong>Users</strong> y haz clic en <strong>Add User</strong>. Al registrar el número de teléfono, recuerda ingresarlo <strong>sin el símbolo "+"</strong> (ejemplo: 56912345678). El sistema requiere este formato estricto para vincular correctamente al usuario y que este pueda solicitar tickets vía Whatsapp.</p>
        </div>
    </section>
@endsection