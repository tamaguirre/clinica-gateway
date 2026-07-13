<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Twilio\Security\RequestValidator;

class VerifyTwilioSignature
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip validation when running unit tests unless explicitly enabled
        if (app()->runningUnitTests() && !config('services.twilio.enable_signature_in_tests')) {
            return $next($request);
        }

        $authToken = config('services.twilio.auth_token');

        if (!$authToken) {
            Log::warning('Verificación de firma de Twilio omitida: TWILIO_AUTH_TOKEN no está configurado.');
            return $next($request);
        }

        $signature = $request->header('X-Twilio-Signature');

        if (!$signature) {
            Log::warning('Intento de acceso al webhook de Twilio sin firma X-Twilio-Signature.');
            return response('No Twilio signature provided.', 403);
        }

        $validator = new RequestValidator($authToken);

        // Twilio validation requires the full URL including query parameters
        $url = $request->fullUrl();

        // Twilio post parameters
        $postData = $request->request->all();

        if (!$validator->validate($signature, $url, $postData)) {
            Log::warning('Firma de Twilio inválida detectada.', [
                'url' => $url,
                'signature' => $signature,
                'ip' => $request->ip()
            ]);
            return response('Invalid Twilio signature.', 403);
        }

        return $next($request);
    }
}
