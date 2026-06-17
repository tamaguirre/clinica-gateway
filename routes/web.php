<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

Route::view('/manual-usuario', 'user-manual')->name('user-manual');
Route::view('/guia-operacion', 'operation-guide')->name('operation-guide');
Route::view('/guia-despliegue', 'deploy-guide')->name('deploy-guide');
