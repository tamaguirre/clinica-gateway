<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WhatsappController;

Route::post('/whatsapp', [WhatsappController::class, 'index']);