<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class LogAccesoSlack
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = auth()->check() ? auth()->user()->name : 'Invitado';
        $ip      = $request->ip();
        $ruta    = $request->fullUrl();
        $hora    = now()->format('d/m/Y H:i:s');

        $webhookUrl = env('SLACK_WEBHOOK_URL');

        if ($webhookUrl) {
            try {
                Http::post($webhookUrl, [
                    'text' => "Acceso detectado\n Usuario: {$usuario}\n Ruta: {$ruta}\n IP: {$ip}\n Hora: {$hora}"
                ]);
            } catch (\Exception $e) {
            }
        }

        return $next($request);
    }
}
