<?php

namespace Classes;

// Envia mensajes de WhatsApp via la API de Twilio, usando cURL directo
// (sin instalar el SDK completo de Twilio via Composer - una sola
// llamada REST es suficiente para lo que necesita este proyecto).
//
// Requiere en el .env:
//   TWILIO_SID=...
//   TWILIO_TOKEN=...
//   TWILIO_WHATSAPP_FROM=whatsapp:+14155238886   (numero de Twilio, con el prefijo whatsapp:)
//   WHATSAPP_PAIS_CODIGO=52                       (opcional, default 52 = Mexico)
class WhatsApp {

    // Envia un mensaje de texto simple a un numero de telefono mexicano
    // (10 digitos, tal como se guarda en usuarios.telefono). Antepone
    // el codigo de pais automaticamente si el numero no lo trae ya.
    // Lanza una excepcion si la llamada a la API falla - el que la usa
    // debe envolver esto en try/catch (igual que con los correos) para
    // que un fallo de WhatsApp nunca tumbe el flujo de agendar una cita.
    public static function enviar(string $telefono, string $mensaje): void {
        $sid = $_ENV['TWILIO_SID'] ?? '';
        $token = $_ENV['TWILIO_TOKEN'] ?? '';
        $from = $_ENV['TWILIO_WHATSAPP_FROM'] ?? '';

        if (!$sid || !$token || !$from) {
            throw new \Exception('WhatsApp no está configurado (faltan credenciales de Twilio en el .env)');
        }

        $numeroDestino = self::normalizarNumero($telefono);
        if (!$numeroDestino) {
            throw new \Exception('Número de teléfono no válido para WhatsApp');
        }

        $url = "https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json";

        $datos = http_build_query([
            'From' => $from,
            'To' => "whatsapp:{$numeroDestino}",
            'Body' => $mensaje,
        ]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $datos);
        curl_setopt($ch, CURLOPT_USERPWD, "{$sid}:{$token}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $respuesta = curl_exec($ch);
        $codigoHttp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errorCurl = curl_error($ch);
        curl_close($ch);

        if ($errorCurl) {
            throw new \Exception('Error de conexión al enviar WhatsApp: ' . $errorCurl);
        }

        if ($codigoHttp < 200 || $codigoHttp >= 300) {
            throw new \Exception("Twilio respondió con error ({$codigoHttp}): " . $respuesta);
        }
    }

    // Convierte "3312345678" -> "+523312345678". Si el numero ya trae
    // un "+" al inicio, se respeta tal cual (por si algun dia se
    // guardan numeros de otros paises).
    private static function normalizarNumero(string $telefono): ?string {
        $telefono = trim($telefono);
        if ($telefono === '') {
            return null;
        }
        if (str_starts_with($telefono, '+')) {
            return $telefono;
        }
        $soloDigitos = preg_replace('/\D/', '', $telefono);
        if (strlen($soloDigitos) < 10) {
            return null;
        }
        $codigoPais = $_ENV['WHATSAPP_PAIS_CODIGO'] ?? '52';
        return '+' . $codigoPais . $soloDigitos;
    }
}
