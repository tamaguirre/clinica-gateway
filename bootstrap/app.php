<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            \Illuminate\Support\Facades\Route::get('/ticket-report', function () {
                $path = storage_path('app/tickets-report.html');
                if (!file_exists($path)) {
                    abort(404, 'El reporte aún no fue generado. Ejecutá: php artisan app:ticket-categorization --from-json=storage/app/tickets-dataset.json');
                }
                return response(file_get_contents($path), 200)
                    ->header('Content-Type', 'text/html; charset=UTF-8');
            });
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'api/whatsapp',
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->shouldRenderJsonWhen(function (\Illuminate\Http\Request $request, \Throwable $e) {
            if ($request->is('api/*')) {
                return true;
            }
            return $request->expectsJson();
        });

    })->create();
