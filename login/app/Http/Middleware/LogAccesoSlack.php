<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class LogAccesoSlack
{
    /**
     * Registrar accesos relevantes y enviar notificación estructurada a Slack.
     * Captura: Quién accedió, Qué ruta visitó, Cuándo ocurrió y Desde Dónde (IP real + navegador).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Solo notificar rutas sensibles de autenticación (no assets, no AJAX internos)
        $rutasSensibles = ['/login', '/register', '/verify-otp', '/verify-2fa', '/logout', '/activate-account'];
        $rutaActual = '/' . ltrim($request->path(), '/');
        $esSensible = collect($rutasSensibles)->contains(fn($r) => str_starts_with($rutaActual, $r));

        if (!$esSensible) {
            return $response;
        }

        $webhookUrl = env('SLACK_WEBHOOK_URL');
        if (!$webhookUrl) {
            return $response;
        }

        // --- Datos de Auditoría (Quién, Qué, Cuándo, Dónde) ---
        $quien   = auth()->check()
            ? auth()->user()->name . ' (' . auth()->user()->email . ') [' . auth()->user()->role . ']'
            : 'Visitante Anónimo';
        $metodo  = $request->method();
        $ruta    = $request->fullUrl();
        $cuando  = now()->format('d/m/Y H:i:s') . ' (UTC-6)';
        $ip      = $this->getRealIp($request);
        $agente  = $request->userAgent() ?? 'Desconocido';
        $status  = $response->getStatusCode();

        // Emoji de estado según código HTTP
        $emoji = match(true) {
            $status >= 200 && $status < 300 => '✅',
            $status >= 300 && $status < 400 => '↪️',
            $status >= 400 && $status < 500 => '⚠️',
            $status >= 500                   => '🚨',
            default                          => '🔵',
        };

        try {
            Http::post($webhookUrl, [
                'blocks' => [
                    [
                        'type' => 'header',
                        'text' => [
                            'type' => 'plain_text',
                            'text' => "{$emoji} Acceso Detectado — {$metodo} {$rutaActual}",
                            'emoji' => true,
                        ],
                    ],
                    [
                        'type' => 'section',
                        'fields' => [
                            ['type' => 'mrkdwn', 'text' => "*👤 Quién:*\n{$quien}"],
                            ['type' => 'mrkdwn', 'text' => "*🕐 Cuándo:*\n{$cuando}"],
                        ],
                    ],
                    [
                        'type' => 'section',
                        'fields' => [
                            ['type' => 'mrkdwn', 'text' => "*🌐 IP (Dónde):*\n`{$ip}`"],
                            ['type' => 'mrkdwn', 'text' => "*📊 Estado HTTP:*\n{$status}"],
                        ],
                    ],
                    [
                        'type' => 'section',
                        'text' => [
                            'type' => 'mrkdwn',
                            'text' => "*🔗 URL:* {$ruta}\n*🖥️ Navegador/SO:* `{$agente}`",
                        ],
                    ],
                    ['type' => 'divider'],
                ],
            ]);
        } catch (\Exception $e) {
            // Silenciar errores de Slack para no afectar la respuesta al usuario
        }

        return $response;
    }

    /**
     * Obtener la IP real del cliente, revisando primero headers de proxies
     * (Cloudflare, Nginx, balanceadores de carga) y, si sigue siendo localhost
     * en entorno local, consultar la IP pública del servidor mediante API externa.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    private function getRealIp(Request $request): string
    {
        // 1. Revisar headers típicos de proxy/CDN en orden de prioridad
        $headersProxy = [
            'HTTP_CF_CONNECTING_IP',   // Cloudflare
            'HTTP_X_REAL_IP',          // Nginx
            'HTTP_X_FORWARDED_FOR',    // Proxies estándar (puede traer lista de IPs)
            'HTTP_CLIENT_IP',          // Alternativo
        ];

        foreach ($headersProxy as $header) {
            $valor = $request->server($header);
            if ($valor) {
                // X-Forwarded-For puede traer "IP_cliente, IP_proxy1, IP_proxy2..."
                // La primera es siempre la del cliente original
                $ip = trim(explode(',', $valor)[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        $ip = $request->ip();

        // 2. Si sigue siendo localhost (desarrollo local), obtener IP pública real del servidor
        if (in_array($ip, ['127.0.0.1', '::1', 'localhost'])) {
            try {
                $publicIp = Http::timeout(3)->get('https://api.ipify.org')->body();
                if (filter_var(trim($publicIp), FILTER_VALIDATE_IP)) {
                    return trim($publicIp) . ' (local→pública)';
                }
            } catch (\Exception $e) {
                // Si falla el lookup, devolver la IP de loopback indicando que es local
                return $ip . ' (localhost)';
            }
        }

        return $ip;
    }
}
