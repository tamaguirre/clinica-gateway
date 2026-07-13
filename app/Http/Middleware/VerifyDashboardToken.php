<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyDashboardToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $configuredToken = config('app.dashboard_token');

        // Si no está configurado el token en el .env, usamos un valor por defecto seguro
        $token = $configuredToken ?: 'clinica_admin_token';

        if ($request->query('token') !== $token) {
            return response()->view('errors.403', [
                'message' => 'Acceso denegado. Se requiere un token de acceso válido en la URL (ej. ?token=VALOR).'
            ], 403);
        }

        return $next($request);
    }
}
