<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\UserData;

class WhatsappController extends Controller
{
    public function index(Request $request)
    {
        // obtiene teléfono y texto de whatsapp
        $from = $request->input('From', '');
        $body = trim($request->input('Body', ''));

        $cleanPhone = preg_replace('/^whatsapp:\+?/', '', $from);

        $cacheKey = 'whatsapp_state_' . $cleanPhone;

        // verifica si el teléfono está en cache
        if (Cache::has($cacheKey)) {
            $exceptionsPath = storage_path('app/whatsapp-exceptions.json');
            $exceptions = [];
            if (file_exists($exceptionsPath)) {
                $exceptions = json_decode(file_get_contents($exceptionsPath), true) ?: [];
            }

            $lowerBody = mb_strtolower($body);
            $lowerExceptions = array_map('mb_strtolower', $exceptions);

            if (in_array($lowerBody, $lowerExceptions, true)) {
                Cache::put($cacheKey, true, now()->addMinutes(10));
                return $this->twiml('¡Hola! Soy el asistente de la clínica. Por favor, describe brevemente tu consulta y la registraremos como ticket.');
            }

            Cache::forget($cacheKey);

            $userData = UserData::with('user.email')->where('phone', $cleanPhone)->first();

            if (!$userData) {
                return $this->twiml('No encontramos tu número registrado en el sistema. Por favor, comunicate con nuestro equipo en contacto@udla.cl.');
            }

            // crear ticket mediante la API de OS ticket
            $response = Http::timeout(10)->withHeaders([
                'X-API-Key' => config('services.os_ticket.api_key'),
            ])->post(config('services.os_ticket.url'), [
                'name'    => $userData->user->name,
                'email'   => $userData->user->email->address,
                'subject' => 'Consulta vía WhatsApp: +' . $cleanPhone,
                'message' => $body,
                'topicId' => config('app.general_topic', 1),
            ]);

            if (!$response->successful()) {
                return $this->twiml('Ocurrió un error al registrar tu consulta. Por favor, intentá nuevamente en unos minutos.');
            }

            $ticketNumber = trim($response->body(), " \t\n\r\0\x0B\"'");

            return $this->twiml("Tu ticket #{$ticketNumber} ha sido creado exitosamente. En breve nuestros agentes se pondrán en contacto contigo. ¡Gracias!");
        }

        Cache::put($cacheKey, true, now()->addMinutes(10));

        return $this->twiml('¡Hola! Soy el asistente de la clínica. Por favor, describe brevemente tu consulta y la registraremos como ticket.');
    }

    // responder en formato twilio
    private function twiml(string $message): \Illuminate\Http\Response
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?><Response><Message>' . e($message) . '</Message></Response>';

        return response($xml, 200)->header('Content-Type', 'text/xml');
    }
}
