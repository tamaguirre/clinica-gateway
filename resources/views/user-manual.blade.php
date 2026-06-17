@extends('layouts.manual')
@section('title', 'Guía de Usuario')
@section('header-title', 'Guía para el Usuario Externo')

@section('content')
    <nav class="mb-8 p-4 bg-gray-50 rounded-lg border border-gray-200">
        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-2">Contenido</h3>
        <ul class="space-y-1">
            <li><a href="#solicitar-ayuda" class="text-blue-700 hover:underline flex items-center">1. ¿Cómo solicitar ayuda?</a></li>
            <li><a href="#consultar-tickets" class="text-blue-700 hover:underline flex items-center">2. Consultar Tickets</a></li>
        </ul>
    </nav>

    <section id="solicitar-ayuda" class="mb-12">
        <h2 class="text-2xl font-bold text-blue-800 mb-4">1. ¿Cómo solicitar ayuda?</h2>
        <div class="grid md:grid-cols-2 gap-6">
            <div class="bg-blue-50 p-6 rounded-xl border">
                <h3 class="font-bold text-blue-900">Por WhatsApp</h3>
                <p class="text-sm">Envía mensaje, espera saludo y cuando se te pida, describe el incidente, debe estar registrado previamente en la plataforma con tu número de teléfono para usar esta opción.</p>
            </div>
            <div class="bg-blue-50 p-6 rounded-xl border">
                <h3 class="font-bold text-blue-900">Por Plataforma</h3>
                <p class="text-sm">Inicia sesión, usa "Abrir nuevo Ticket", elige "Consulta general" y detalla tu problema.</p>
            </div>
        </div>
        <img src="/img/open-ticket.png" alt="Formulario de Ticket" class="w-full rounded-lg border-2 border-dashed mt-6">
    </section>

    <section id="consultar-tickets">
        <h2 class="text-2xl font-bold text-blue-800 mb-4">2. Consultar Tickets</h2>
        <p>Inicia sesión y revisa tu panel de <strong>Tickets</strong> para ver el estado de cada uno.</p>
        <img src="/img/my-tickets.png" alt="Listado de Tickets" class="w-full rounded-lg border-2 border-dashed mt-6">
    </section>
@endsection