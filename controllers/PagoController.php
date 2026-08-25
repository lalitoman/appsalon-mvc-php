<?php

namespace Controllers;

use Classes\Email;
use Classes\MercadoPago;
use Classes\WhatsApp;
use Model\Cita;
use Model\CitaServicio;
use Model\Configuracion;
use Model\Empleado;
use Model\Pago;
use Model\Servicio;
use Model\Usuario;
use MVC\Router;

class PagoController{

    // Mercado Pago llama esta URL server-a-server cuando el estado de
    // un pago cambia (approved, rejected, etc). NUNCA hay que confiar
    // en los datos que manda la notificacion sin mas: se usa el id que
    // trae para volver a preguntarle a la API de Mercado Pago cual es
    // el estado real, y solo entonces se actualiza la cita.
    public static function webhook(){
        header('Content-Type: application/json');

        // Mercado Pago manda el id del pago como query param, en dos
        // formatos posibles segun la version de su integracion.
        $paymentId = $_GET['data_id'] ?? $_GET['id'] ?? null;
        $tipo = $_GET['type'] ?? $_GET['topic'] ?? null;

        if (!$paymentId || $tipo !== 'payment') {
            // Mercado Pago tambien manda notificaciones de otros tipos
            // (merchant_order, etc) que no nos interesan - se responde
            // 200 igual para que no reintente indefinidamente.
            http_response_code(200);
            echo json_encode(['ok' => true, 'ignorado' => true]);
            exit;
        }

        try {
            $pagoMP = MercadoPago::obtenerPago((string) $paymentId);
        } catch (\Exception $e) {
            error_log('Webhook Mercado Pago: no se pudo consultar el pago ' . $paymentId . ': ' . $e->getMessage());
            http_response_code(200); // 200 igual, para no generar reintentos infinitos por un error nuestro
            echo json_encode(['ok' => false]);
            exit;
        }

        $pagoId = $pagoMP['external_reference'] ?? null;
        $estadoMP = $pagoMP['status'] ?? null; // approved | pending | rejected | ...

        if (!$pagoId || !is_numeric($pagoId)) {
            http_response_code(200);
            echo json_encode(['ok' => false, 'error' => 'external_reference invalido']);
            exit;
        }

        $pago = Pago::find($pagoId);
        if (!$pago) {
            http_response_code(200);
            echo json_encode(['ok' => false, 'error' => 'pago no encontrado']);
            exit;
        }

        $pago->mp_payment_id = (string) $paymentId;
        $pago->estado = $estadoMP;
        $pago->guardar();

        if ($estadoMP === 'approved') {
            self::confirmarCita($pago->citaId);
        } elseif (in_array($estadoMP, ['rejected', 'cancelled'], true)) {
            // El anticipo no se pago - se libera el horario borrando la
            // cita, para que otro cliente si pueda agendar ese espacio.
            $cita = Cita::find($pago->citaId);
            if ($cita && $cita->estado === 'pendiente_pago') {
                $cita->eliminar();
            }
        }
        // Si el estado es "pending" (ej. pago en efectivo con ficha,
        // OXXO, etc.) no se hace nada todavia - se espera la siguiente
        // notificacion cuando cambie a approved o rejected.

        http_response_code(200);
        echo json_encode(['ok' => true]);
    }

    // Marca la cita como confirmada y manda el correo/WhatsApp de
    // confirmacion - exactamente lo mismo que hacia APIcontroller::guardar()
    // antes de que existiera el flujo de pago, solo que ahora se dispara
    // aqui, una vez que el anticipo ya esta pagado de verdad.
    private static function confirmarCita(int $citaId): void {
        $cita = Cita::find($citaId);
        if (!$cita || $cita->estado === 'confirmada') {
            return; // ya se proceso antes (Mercado Pago puede notificar mas de una vez)
        }

        $cita->estado = 'confirmada';
        $cita->guardar();

        $usuario = Usuario::find($cita->usuarioId);
        $empleado = Empleado::find($cita->empleadoId);
        if (!$usuario || !$empleado) {
            return;
        }

        $filasServicios = CitaServicio::SQL("SELECT servicioId FROM citaservicios WHERE citaId = " . (int) $citaId);
        $detalleServicios = [];
        $total = 0;
        foreach ($filasServicios as $fila) {
            $servicio = Servicio::find($fila->servicioId);
            if ($servicio) {
                $detalleServicios[] = ['nombre' => $servicio->nombre, 'precio' => $servicio->precio];
                $total += (float) $servicio->precio;
            }
        }

        $nombreEmpleado = $empleado->nombre . ' ' . $empleado->apellido;

        try {
            $email = new Email($usuario->email, $usuario->nombre, '');
            $email->enviarConfirmacionCita($cita->fecha, $cita->hora, $detalleServicios, $total, $nombreEmpleado);
        } catch (\Exception $e) {
            error_log('No se pudo enviar el correo de confirmacion (post-pago): ' . $e->getMessage());
        }

        try {
            if (!empty($_ENV['TWILIO_SID']) && $usuario->telefono) {
                $nombreNegocio = Configuracion::obtener()->nombre_negocio;
                $fechaFormateada = date('d/m/Y', strtotime($cita->fecha));
                $horaFormateada = substr($cita->hora, 0, 5);

                $mensaje = "Hola {$usuario->nombre}, tu cita en {$nombreNegocio} fue confirmada (anticipo recibido).\n";
                $mensaje .= "Fecha: {$fechaFormateada}\n";
                $mensaje .= "Hora: {$horaFormateada}\n";
                $mensaje .= "Con: {$nombreEmpleado}\n";
                $mensaje .= "Total del servicio: \${$total}\n";
                $mensaje .= "Si necesitas cancelar, hazlo desde 'Mis Citas' en la app.";

                WhatsApp::enviar($usuario->telefono, $mensaje);
            }
        } catch (\Exception $e) {
            error_log('No se pudo enviar el WhatsApp de confirmacion (post-pago): ' . $e->getMessage());
        }
    }

    // --- Paginas de retorno (a donde Mercado Pago manda de vuelta al
    // cliente despues de pagar, segun back_urls). El estado REAL ya se
    // confirmo (o se confirmara en breve) via el webhook de arriba -
    // estas paginas son solo para que el cliente vea un mensaje claro,
    // no la fuente de verdad del estado del pago.

    public static function exito(Router $router){
        isAuth();
        $router->render('pago/exito', ['nombre' => $_SESSION['nombre']]);
    }

    public static function pendiente(Router $router){
        isAuth();
        $router->render('pago/pendiente', ['nombre' => $_SESSION['nombre']]);
    }

    public static function error(Router $router){
        isAuth();
        $router->render('pago/error', ['nombre' => $_SESSION['nombre']]);
    }
}
