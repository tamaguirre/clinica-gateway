<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WhatsappController;
use App\Http\Middleware\VerifyTwilioSignature;

Route::post('/whatsapp', [WhatsappController::class, 'index'])->middleware(VerifyTwilioSignature::class);