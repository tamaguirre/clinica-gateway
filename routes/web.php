<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::view('/ticket-report', 'ticket-report')->name('ticket-report');

Route::view('/manual-usuario', 'user-manual')->name('user-manual');
Route::view('/guia-operacion', 'operation-guide')->name('operation-guide');
Route::view('/guia-despliegue', 'deploy-guide')->name('deploy-guide');
Route::view('/fragmentos-codigo', 'code-snippets')->name('code-snippets');
Route::view('/reporte-experimentacion', 'experiment-report')->name('experiment-report');
Route::view('/reporte-pruebas', 'test-reports')->name('test.reports');
