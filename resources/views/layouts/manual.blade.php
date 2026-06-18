<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <title>@yield('title') - Clínica UDLA</title>
</head>
<body class="bg-gray-50 p-6 md:p-12">
    <div class="max-w-5xl mx-auto bg-white p-8 md:p-12 shadow-xl rounded-2xl border border-gray-100">
        <nav class="flex gap-4 mb-8 border-b pb-4">
            <a href="/guia-operacion" 
            class="px-4 py-2 rounded-lg text-sm font-semibold transition {{ request()->is('guia-operacion') ? 'bg-blue-800 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                Guía de Operación
            </a>
            
            <a href="/manual-usuario" 
            class="px-4 py-2 rounded-lg text-sm font-semibold transition {{ request()->is('manual-usuario') ? 'bg-blue-800 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                Manual de Usuario
            </a>
            
            <a href="/guia-despliegue" 
            class="px-4 py-2 rounded-lg text-sm font-semibold transition {{ request()->is('guia-despliegue') ? 'bg-blue-800 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                Guía de Despliegue
            </a>

            <a href="/fragmentos-codigo" 
            class="px-4 py-2 rounded-lg text-sm font-semibold transition {{ request()->is('fragmentos-codigo') ? 'bg-blue-800 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                Fragmentos de Código
            </a>

             <a href="/reporte-experimentacion" 
            class="px-4 py-2 rounded-lg text-sm font-semibold transition {{ request()->is('reporte-experimentacion') ? 'bg-blue-800 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                Experimentación con IA
            </a>
        </nav>

        <header class="border-b-2 border-blue-100 pb-8 mb-10">
            <h1 class="text-4xl font-extrabold text-blue-900">@yield('header-title')</h1>
            <p class="text-xl text-gray-600 mt-2">Clínica de Ciberseguridad UDLA</p>
        </header>

        <main>
            @yield('content')
        </main>

        <footer class="mt-16 pt-8 border-t text-center text-gray-400 text-sm">
            Sistema de Gestión de Servicios - Clínica de Ciberseguridad UDLA 2026
        </footer>
    </div>
</body>
</html>