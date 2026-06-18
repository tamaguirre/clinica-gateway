@extends('layouts.manual')
@section('title', 'Fragmentos de Código Críticos')
@section('header-title', 'Arquitectura del Código Fuente')

@section('content')
    <nav class="mb-8 p-4 bg-gray-50 rounded-lg border border-gray-200">
        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-2">Secciones Críticas</h3>
        <ul class="space-y-1">
            <li><a href="#rutas-api" class="text-blue-700 hover:underline flex items-center">1. Definición de Rutas (Gateway)</a></li>
            <li><a href="#whatsapp-controller" class="text-blue-700 hover:underline flex items-center">2. Controlador de WhatsApp</a></li>
            <li><a href="#orquestacion-ia" class="text-blue-700 hover:underline flex items-center">3. Orquestación de IA (Ollama)</a></li>
            <li><a href="#script-python" class="text-blue-700 hover:underline flex items-center">4. Script de Inferencia (Python)</a></li>
        </ul>
    </nav>

    <!-- SECCIÓN 1: RUTAS -->
    <section id="rutas-api" class="mb-12">
        <div class="flex items-center mb-4">
            <div class="bg-blue-600 p-2 rounded-lg mr-3">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A2 2 0 013 15.488V5.111a2 2 0 011.106-1.789l5.447-2.724a2 2 0 011.788 0l5.447 2.724A2 2 0 0118 5.111v10.377a2 2 0 01-1.106 1.789L11.447 20a2 2 0 01-1.788 0z"></path></svg>
            </div>
            <h2 class="text-2xl font-bold text-gray-800">1. Definición de Rutas (api.php)</h2>
        </div>
        <p class="text-gray-600 mb-4">Puntos de entrada para los webhooks de mensajería y la consulta de reportes de IA.</p>
        <pre class="bg-gray-900 text-gray-300 p-6 rounded-xl shadow-inner text-sm overflow-x-auto">
<span class="text-purple-400">use</span> App\Http\Controllers\WhatsAppController;

<span class="text-gray-500">// Webhook de entrada para Meta/WhatsApp</span>
<span class="text-blue-400">Route</span>::post(<span class="text-green-400">'/whatsapp/webhook'</span>, [WhatsAppController::<span class="text-purple-400">class</span>, <span class="text-green-400">'handle'</span>]);
<span class="text-blue-400">Route</span>::get(<span class="text-green-400">'/whatsapp/webhook'</span>, [WhatsAppController::<span class="text-purple-400">class</span>, <span class="text-green-400">'verify'</span>]);

<span class="text-gray-500">// Dashboard de reportes de categorización</span>
<span class="text-blue-400">Route</span>::get(<span class="text-green-400">'/ticket-report'</span>, <span class="text-purple-400">fn</span>() => view(<span class="text-green-400">'ticket-report'</span>));</pre>
    </section>

    <!-- SECCIÓN 2: WHATSAPP CONTROLLER -->
    <section id="whatsapp-controller" class="mb-12">
        <div class="flex items-center mb-4">
            <div class="bg-green-600 p-2 rounded-lg mr-3">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
            </div>
            <h2 class="text-2xl font-bold text-gray-800">2. Controlador de WhatsApp</h2>
        </div>
        <p class="text-gray-600 mb-4">Lógica para recibir mensajes, procesar el texto y enviarlo a la API de osTicket.</p>
        <pre class="bg-gray-900 text-gray-300 p-6 rounded-xl shadow-inner text-sm overflow-x-auto">
<span class="text-purple-400">public function</span> <span class="text-blue-400">handle</span>(Request $request) {
    <span class="text-orange-400">$message</span> = $request->input(<span class="text-green-400">'entry.0.changes.0.value.messages.0.body'</span>);
    <span class="text-orange-400">$from</span> = $request->input(<span class="text-green-400">'entry.0.changes.0.value.messages.0.from'</span>);

    <span class="text-gray-500">// Enviar a osTicket vía HTTP Client</span>
    <span class="text-blue-400">Http</span>::withHeaders([<span class="text-green-400">'X-API-Key'</span> => config(<span class="text-green-400">'services.osticket.key'</span>)])
        ->post(config(<span class="text-green-400">'services.osticket.url'</span>), [
            <span class="text-green-400">'name'</span> => <span class="text-orange-400">$from</span>,
            <span class="text-green-400">'email'</span> => <span class="text-green-400">'whatsapp@clinica.com'</span>,
            <span class="text-green-400">'subject'</span> => <span class="text-green-400">'Nuevo mensaje desde WhatsApp'</span>,
            <span class="text-green-400">'message'</span> => <span class="text-orange-400">$message</span>,
        ]);
}</pre>
    </section>

    <!-- SECCIÓN 3: ORQUESTACIÓN IA -->
    <section id="orquestacion-ia" class="mb-12">
        <div class="flex items-center mb-4">
            <div class="bg-purple-600 p-2 rounded-lg mr-3">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
            </div>
            <h2 class="text-2xl font-bold text-gray-800">3. Orquestación de IA (Ollama Connector)</h2>
        </div>
        <p class="text-gray-600 mb-4">Método principal que comunica el sistema con el LM (Qwen 2.5) vía HTTP.</p>
        <pre class="bg-gray-900 text-gray-300 p-6 rounded-xl shadow-inner text-sm overflow-x-auto">
<span class="text-purple-400">private function</span> <span class="text-blue-400">chatWithIA</span>(string $message, string $systemPrompt) {
    <span class="text-orange-400">$response</span> = <span class="text-blue-400">Http</span>::timeout(<span class="text-orange-400">240</span>)->post(config(<span class="text-green-400">'services.ollama.url'</span>) . <span class="text-green-400">'/api/chat'</span>, [
        <span class="text-green-400">'model'</span>    => config(<span class="text-green-400">'services.ollama.model'</span>),
        <span class="text-green-400">'messages'</span> => [
            [<span class="text-green-400">'role'</span> => <span class="text-green-400">'system'</span>, <span class="text-green-400">'content'</span> => <span class="text-orange-400">$systemPrompt</span>],
            [<span class="text-green-400">'role'</span> => <span class="text-green-400">'user'</span>, <span class="text-green-400">'content'</span> => <span class="text-orange-400">$message</span>],
        ],
        <span class="text-green-400">'options'</span>  => [
            <span class="text-green-400">'temperature'</span> => <span class="text-orange-400">0.0</span>,
            <span class="text-green-400">'num_predict'</span> => <span class="text-orange-400">20</span>,
        ],
        <span class="text-green-400">'stream'</span>   => <span class="text-purple-400">false</span>,
    ]);

    <span class="text-purple-400">return</span> trim(<span class="text-orange-400">$response</span>->json(<span class="text-green-400">'message.content'</span>));
}</pre>
    </section>

    <!-- SECCIÓN 4: SCRIPT PYTHON -->
    <section id="script-python" class="mb-12">
        <div class="flex items-center mb-4">
            <div class="bg-yellow-500 p-2 rounded-lg mr-3">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path></svg>
            </div>
            <h2 class="text-2xl font-bold text-gray-800">4. Script de Inferencia (ia_classify.py)</h2>
        </div>
        <p class="text-gray-600 mb-4">Conector de bajo nivel mediante el SDK de Python para procesamiento pesado.</p>
        <pre class="bg-gray-900 text-gray-300 p-6 rounded-xl shadow-inner text-sm overflow-x-auto">
<span class="text-purple-400">import</span> sys, json, ollama

<span class="text-purple-400">def</span> <span class="text-blue-400">classify</span>():
    data = json.load(sys.stdin)
    response = ollama.chat(model=data[<span class="text-green-400">'model'</span>], messages=[
        {<span class="text-green-400">'role'</span>: <span class="text-green-400">'system'</span>, <span class="text-green-400">'content'</span>: data[<span class="text-green-400">'system'</span>]},
        {<span class="text-green-400">'role'</span>: <span class="text-green-400">'user'</span>, <span class="text-green-400">'content'</span>: data[<span class="text-green-400">'message'</span>]},
    ])
    <span class="text-blue-400">print</span>(response[<span class="text-green-400">'message'</span>][<span class="text-green-400">'content'</span>])

<span class="text-purple-400">if</span> __name__ == <span class="text-green-400">"__main__"</span>:
    classify()</pre>
    </section>

    <div class="bg-gray-100 p-6 rounded-xl border-l-4 border-blue-500 text-sm text-gray-700">
        <p><strong>Nota Técnica:</strong> Este sistema utiliza una arquitectura de Gateway, donde Laravel actúa como orquestador, gestionando la persistencia en base de datos y delegando la inferencia cognitiva a Ollama mediante protocolos HTTP o subprocesos de Python.</p>
    </div>
@endsection