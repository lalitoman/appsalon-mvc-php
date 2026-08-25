<?php

namespace Classes;

// Integracion con Mercado Pago (Checkout Pro) via su API REST directa
// con cURL - mismo patron que Classes\WhatsApp, sin instalar el SDK
// completo por Composer.
//
// Requiere en el .env:
//   MP_ACCESS_TOKEN=...        (Access Token de PRUEBA o de PRODUCCION,
//                                segun en que credenciales estes parado
//                                en tu cuenta de Mercado Pago)
//   MP_MONTO_ANTICIPO=100      (monto fijo del anticipo, en pesos)
class MercadoPago {

    // Crea una "preferencia" de pago (un checkout de Mercado Pago) para
    // el anticipo de una cita. Devuelve la URL a la que hay que mandar
    // al cliente para que pague.
    // Lanza excepcion si algo falla - quien la use debe envolverla en
    // try/catch.
    public static function crearPreferencia(int $pagoId, float $monto, string $appUrl): array {
        $token = $_ENV['MP_ACCESS_TOKEN'] ?? '';
        if (!$token) {
            throw new \Exception('Mercado Pago no está configurado (falta MP_ACCESS_TOKEN en el .env)');
        }

        $body = [
            'items' => [[
                'title' => 'Anticipo de cita',
                'quantity' => 1,
                'unit_price' => $monto,
                'currency_id' => 'MXN',
            ]],
            'external_reference' => (string) $pagoId,
            'back_urls' => [
                'success' => $appUrl . '/pago/exito',
                'pending' => $appUrl . '/pago/pendiente',
                'failure' => $appUrl . '/pago/error',
            ],
            'auto_return' => 'approved',
            'notification_url' => $appUrl . '/webhook/mercadopago',
        ];

        $respuesta = self::llamar('POST', 'https://api.mercadopago.com/checkout/preferences', $token, $body);

        if (!isset($respuesta['id']) || !isset($respuesta['init_point'])) {
            throw new \Exception('Mercado Pago no devolvió una preferencia válida: ' . json_encode($respuesta));
        }

        return [
            'preference_id' => $respuesta['id'],
            // sandbox_init_point se usa automaticamente cuando el
            // Access Token es de PRUEBA; con credenciales reales,
            // Mercado Pago regresa init_point normal.
            'url' => $respuesta['sandbox_init_point'] ?? $respuesta['init_point'],
        ];
    }

    // Consulta el estado real de un pago en Mercado Pago por su id
    // (se usa desde el webhook, nunca hay que confiar ciegamente en
    // los datos que manda la notificacion sin verificar contra la API).
    public static function obtenerPago(string $paymentId): array {
        $token = $_ENV['MP_ACCESS_TOKEN'] ?? '';
        if (!$token) {
            throw new \Exception('Mercado Pago no está configurado (falta MP_ACCESS_TOKEN en el .env)');
        }

        return self::llamar('GET', "https://api.mercadopago.com/v1/payments/{$paymentId}", $token);
    }

    private static function llamar(string $metodo, string $url, string $token, array $body = null): array {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $metodo);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        $respuesta = curl_exec($ch);
        $codigoHttp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errorCurl = curl_error($ch);
        curl_close($ch);

        if ($errorCurl) {
            throw new \Exception('Error de conexión con Mercado Pago: ' . $errorCurl);
        }

        $decodificado = json_decode($respuesta, true);

        if ($codigoHttp < 200 || $codigoHttp >= 300) {
            throw new \Exception("Mercado Pago respondió con error ({$codigoHttp}): " . $respuesta);
        }

        return $decodificado ?? [];
    }
}
