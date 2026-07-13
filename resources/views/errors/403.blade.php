<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Denegado — Clínica Universitaria</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background: radial-gradient(circle at center, #0f172a 0%, #020617 100%);
        }
    </style>
</head>
<body class="h-full flex items-center justify-center p-6 overflow-hidden">
    
    <!-- Background glow decoration -->
    <div class="absolute w-[500px] h-[500px] rounded-full bg-blue-500/10 blur-[120px] top-1/4 left-1/4 -z-10 animate-pulse"></div>
    <div class="absolute w-[400px] h-[400px] rounded-full bg-indigo-500/10 blur-[100px] bottom-1/4 right-1/4 -z-10 animate-pulse delay-700"></div>

    <div class="max-w-md w-full text-center relative">
        <!-- Glass Card -->
        <div class="bg-slate-900/60 backdrop-blur-xl border border-slate-800/80 p-8 rounded-3xl shadow-2xl space-y-6">
            
            <!-- Lock Icon with Glow -->
            <div class="mx-auto w-16 h-16 rounded-2xl bg-gradient-to-tr from-rose-500 to-amber-500 p-0.5 shadow-lg shadow-rose-500/20">
                <div class="w-full h-full bg-slate-950 rounded-[14px] flex items-center justify-center">
                    <svg class="w-8 h-8 text-rose-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                    </svg>
                </div>
            </div>

            <!-- Title -->
            <div class="space-y-2">
                <h1 class="text-2xl font-bold text-white tracking-tight">Acceso Restringido</h1>
                <p class="text-xs font-semibold uppercase tracking-wider text-rose-400">Error 403 Forbidden</p>
            </div>

            <!-- Message -->
            <p class="text-slate-400 text-sm leading-relaxed">
                {{ $message ?? 'No tienes permisos para acceder a esta sección o el token de acceso es inválido.' }}
            </p>

            <!-- Action -->
            <div class="pt-4 border-t border-slate-800/60">
                <p class="text-xs text-slate-500">
                    Clínica Universitaria · Gateway de Soporte
                </p>
            </div>

        </div>
    </div>

</body>
</html>
