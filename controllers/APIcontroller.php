<?php

namespace Controllers;

use Classes\Email;
use Classes\WhatsApp;
use Model\Cita;
use Model\CitaServicio;
use Model\Empleado;
use Model\Servicio;
use Model\Usuario;

class APIcontroller{
    public static function index(){
        $servicios = Servicio::all();
        echo json_encode($servicios);
    }

    // Lista de empleados activos, para el selector que ve el cliente
    // al agendar. Es publica (no requiere isAuth) igual que /api/servicios,
    // ya que solo expone informacion no sensible.
    public static function empleados(){
        $empleados = Empleado::activos();
        echo json_encode($empleados);
    }

    // Horario de atencion del negocio. Bloques fijos de 30 minutos,
    // pero el rango de horas ahora depende del horario propio de cada
    // empleado (ver Empleado::horario_inicio / horario_fin) en vez de
    // un rango global fijo.
    // Nota: no considera duracion variable por servicio (un corte y un
    // tinte ocupan el mismo bloque) - ver README, pendiente a futuro.
    private const INTERVALO_MINUTOS = 30;

    // Devuelve todos los horarios del dia dentro del horario propio del
    // empleado, marcando cuales ya estan ocupados para la fecha pedida,
    // para que el cliente elija de una cuadricula en vez de escribir la
    // hora libre.
    public static function horariosDisponibles(){
        header('Content-Type: application/json');

        // Libera horarios de citas "pendiente_pago" abandonadas antes de
        // calcular disponibilidad, para no mostrar como ocupado un
        // horario que en realidad nadie va a pagar.
        Cita::liberarPendientesAbandonadas();

        $fecha = $_GET['fecha'] ?? '';
        $empleadoId = $_GET['empleadoId'] ?? '';

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) || !is_numeric($empleadoId)) {
            echo json_encode(['error' => 'Fecha o ' . strtolower(\Model\Configuracion::obtener()->etiqueta_empleado_singular) . ' no válidos.']);
            exit;
        }

        $empleado = Empleado::find($empleadoId);
        if (!$empleado) {
            echo json_encode(['error' => 'Empleado no encontrado.']);
            exit;
        }

        $modalidad = $_GET['modalidad'] ?? 'presencial';
        if (!in_array($modalidad, ['presencial', 'virtual'], true)) {
            echo json_encode(['error' => 'Modalidad no válida.']);
            exit;
        }

        $fechaEscapada = Cita::escape($fecha);
        $empleadoIdEscapado = Cita::escape($empleadoId);

        $consultaOcupadas = "SELECT hora FROM citas WHERE fecha = '{$fechaEscapada}' AND empleadoId = '{$empleadoIdEscapado}'";
        // Al reagendar, la cita que se esta moviendo ocupa su propio
        // horario actual - hay que excluirla o se veria a si misma como
        // "ocupada" y el cliente no podria dejarla en la misma hora.
        if (isset($_GET['excluirCitaId']) && is_numeric($_GET['excluirCitaId'])) {
            $excluirCitaId = Cita::escape($_GET['excluirCitaId']);
            $consultaOcupadas .= " AND id != '{$excluirCitaId}'";
        }
        $ocupadas = Cita::SQL($consultaOcupadas);
        // Normaliza a formato HH:MM (la BD puede traer segundos: HH:MM:SS)
        // Nota: un horario ocupado bloquea esa hora sin importar la
        // modalidad de la cita que ya esta ahi - el empleado solo puede
        // atender una cosa a la vez (presencial o virtual), asi que no
        // se distingue por modalidad aqui.
        $horasOcupadas = array_map(fn($cita) => substr($cita->hora, 0, 5), $ocupadas);

        // El rango de horas depende de la modalidad elegida (ej.
        // Presencial 11am-5pm, Virtual 6pm-8pm - pueden ser rangos
        // completamente distintos y hasta sin traslape).
        [$horaInicioStr, $horaFinStr] = $empleado->horarioParaModalidad($modalidad);
        [$horaInicioH, $horaInicioM] = array_map('intval', explode(':', $horaInicioStr));
        [$horaFinH, $horaFinM] = array_map('intval', explode(':', $horaFinStr));
        $minutosInicio = $horaInicioH * 60 + $horaInicioM;
        $minutosFin = $horaFinH * 60 + $horaFinM;

        $slots = [];
        for ($minutos = $minutosInicio; $minutos < $minutosFin; $minutos += self::INTERVALO_MINUTOS) {
            $hora = sprintf('%02d:%02d', intdiv($minutos, 60), $minutos % 60);
            $slots[] = [
                'hora' => $hora,
                'disponible' => !in_array($hora, $horasOcupadas, true),
            ];
        }

        echo json_encode($slots);
    }

    public static function guardar(){
        // CRITICO: antes cualquiera (sin sesion) podia crear citas via POST
        isAuthAPI();
        // Validacion CSRF (version JSON, la llamada viene de fetch/JS)
        csrfVerificarAPI();

        header('Content-Type: application/json');

        // Igual que en horariosDisponibles(): libera horarios abandonados
        // antes de comprobar si el horario pedido esta libre.
        Cita::liberarPendientesAbandonadas();

        $fecha = $_POST['fecha'] ?? '';
        $hora = $_POST['hora'] ?? '';
        $empleadoId = $_POST['empleadoId'] ?? '';

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) || !preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $hora)) {
            echo json_encode(['error' => 'Fecha u hora no válidas.']);
            exit;
        }

        if (!is_numeric($empleadoId)) {
            echo json_encode(['error' => 'Debes elegir un(a) ' . strtolower(\Model\Configuracion::obtener()->etiqueta_empleado_singular) . '.']);
            exit;
        }

        $empleado = Empleado::find($empleadoId);
        if (!$empleado || !$empleado->activo) {
            echo json_encode(['error' => 'Ese(a) ' . strtolower(\Model\Configuracion::obtener()->etiqueta_empleado_singular) . ' ya no está disponible, elige otro(a).']);
            exit;
        }

        // La hora pedida debe caer dentro del horario propio de ESE
        // empleado PARA LA MODALIDAD elegida (defensa de servidor - el
        // selector de horarios ya solo muestra horas validas, pero esto
        // evita que alguien mande una hora fuera de rango directo por
        // API).
        $modalidad = $_POST['modalidad'] ?? 'presencial';
        if (!in_array($modalidad, ['presencial', 'virtual'], true)) {
            echo json_encode(['error' => 'Modalidad no válida.']);
            exit;
        }

        [$rangoInicio, $rangoFin] = $empleado->horarioParaModalidad($modalidad);
        $horaComparable = substr($hora, 0, 5);
        if ($horaComparable < substr($rangoInicio, 0, 5) || $horaComparable >= substr($rangoFin, 0, 5)) {
            echo json_encode(['error' => 'Esa hora está fuera del horario de este empleado para esa modalidad.']);
            exit;
        }

        // Evitar doble reserva: ahora el bloqueo es por fecha+hora+empleado,
        // no por fecha+hora en general -> dos clientes SI pueden agendar
        // la misma hora si es con barberos distintos.
        $fechaEscapada = Cita::escape($fecha);
        $horaEscapada = Cita::escape($hora);
        $empleadoIdEscapado = Cita::escape($empleadoId);
        $ocupado = Cita::SQL("SELECT id FROM citas WHERE fecha = '{$fechaEscapada}' AND hora = '{$horaEscapada}' AND empleadoId = '{$empleadoIdEscapado}'");
        if (count($ocupado) > 0) {
            echo json_encode(['error' => 'Ese(a) ' . strtolower(\Model\Configuracion::obtener()->etiqueta_empleado_singular) . ' ya está ocupado(a) en ese horario, elige otra hora u otro(a) ' . strtolower(\Model\Configuracion::obtener()->etiqueta_empleado_singular) . '.']);
            exit;
        }

        // Si Mercado Pago esta configurado, la cita nace "pendiente_pago"
        // (no se avisa al cliente por correo/WhatsApp todavia, ni se
        // considera confirmada en los reportes) hasta que el webhook
        // confirme el cobro del anticipo. Sin Mercado Pago configurado,
        // el comportamiento es exactamente el de siempre: confirmada al
        // instante.
        $mercadoPagoActivo = !empty($_ENV['MP_ACCESS_TOKEN']);

        //almacena la cita y duvulve el ID
        $cita = new Cita($_POST);
        $cita->usuarioId = $_SESSION['id']; // fuerza el dueño real, nunca confies en el POST
        $cita->empleadoId = $empleadoId;
        $cita->modalidad = $modalidad;
        $cita->estado = $mercadoPagoActivo ? 'pendiente_pago' : 'confirmada';
        $resultado = $cita->guardar();

        $id = $resultado['id'];
        
        // alamacena la cita y  el servicio, y de paso arma el detalle
        // para el correo de confirmacion
        $idServicios = explode(",", $_POST['servicios'] ?? '');
        $detalleServicios = [];
        $total = 0;
        $sumaAnticiposServicios = 0;
        foreach($idServicios as $idServicio){
            if (!is_numeric($idServicio)) continue;
            $args = [
                'citaId' => $id,
                'servicioId' => $idServicio
            ];
            $citaServicios = new CitaServicio($args);
            $citaServicios->guardar();

            $servicio = Servicio::find($idServicio);
            if ($servicio) {
                $detalleServicios[] = ['nombre' => $servicio->nombre, 'precio' => $servicio->precio];
                $total += (float) $servicio->precio;
                $sumaAnticiposServicios += (float) $servicio->anticipo;
            }
        }

        // --- Camino A: Mercado Pago activo -> generar el checkout del
        // anticipo y mandar al cliente a pagar. El correo/WhatsApp de
        // confirmacion se manda DESPUES, cuando el webhook confirme el
        // pago (ver PagoController::webhook()), no aqui.
        if ($mercadoPagoActivo) {
            try {
                // Anticipo variable: suma de lo configurado por cada
                // servicio elegido (0 = "usa el minimo"), nunca por
                // debajo del minimo global (MP_ANTICIPO_MINIMO, $50 por
                // defecto).
                $minimoGlobal = (float) ($_ENV['MP_ANTICIPO_MINIMO'] ?? 50);
                $montoAnticipo = max($minimoGlobal, $sumaAnticiposServicios);

                $pago = new \Model\Pago([
                    'citaId' => $id,
                    'monto' => $montoAnticipo,
                    'estado' => 'pendiente',
                ]);
                $resultadoPago = $pago->guardar();
                $pagoId = $resultadoPago['id'];

                $preferencia = \Classes\MercadoPago::crearPreferencia($pagoId, $montoAnticipo, rtrim($_ENV['APP_URL'], '/'));

                $pago = \Model\Pago::find($pagoId);
                $pago->mp_preference_id = $preferencia['preference_id'];
                $pago->guardar();

                echo json_encode(['redirigir' => $preferencia['url']]);
            } catch (\Exception $e) {
                error_log('No se pudo crear la preferencia de pago: ' . $e->getMessage());
                // Si Mercado Pago falla, no dejamos al cliente con una
                // cita "pendiente_pago" huerfana e invisible - se borra
                // (junto con su registro de pago) y se le avisa para
                // que intente de nuevo.
                Cita::eliminarConDependencias((int) $id);
                echo json_encode(['error' => 'No se pudo iniciar el pago del anticipo. Intenta de nuevo en unos momentos.']);
            }
            exit;
        }

        // --- Camino B: sin Mercado Pago -> comportamiento de siempre,
        // confirmar y avisar de inmediato.

        // Enviar correo de confirmacion. Si falla (SMTP mal configurado,
        // etc.) no se interrumpe la respuesta: la cita ya quedo guardada.
        try {
            if (!empty($detalleServicios)) {
                $nombreEmpleado = $empleado->nombre . ' ' . $empleado->apellido;
                $email = new Email($_SESSION['email'], $_SESSION['nombre'], '');
                $email->enviarConfirmacionCita($fecha, $hora, $detalleServicios, $total, $nombreEmpleado, $modalidad);
            }
        } catch (\Exception $e) {
            error_log('No se pudo enviar el correo de confirmacion de cita: ' . $e->getMessage());
        }

        // Enviar WhatsApp de confirmacion. Igual que el correo: si falla
        // o no esta configurado (Twilio no configurado, telefono
        // invalido, numero no unido al sandbox, etc.) no se interrumpe
        // la respuesta - la cita ya quedo guardada de todas formas.
        try {
            if (empty($_ENV['TWILIO_SID'])) {
                error_log('WhatsApp omitido: TWILIO_SID no configurado en .env');
            } elseif (empty($detalleServicios)) {
                error_log('WhatsApp omitido: no se guardaron servicios en la cita');
            } else {
                $usuario = Usuario::find($_SESSION['id']);
                if (!$usuario || !$usuario->telefono) {
                    error_log('WhatsApp omitido: el usuario no tiene telefono guardado');
                } else {
                    $nombreEmpleado = $empleado->nombre . ' ' . $empleado->apellido;
                    $fechaFormateada = date('d/m/Y', strtotime($fecha));
                    $horaFormateada = substr($hora, 0, 5);

                    $nombreNegocio = \Model\Configuracion::obtener()->nombre_negocio;
                    $mensaje = "Hola {$_SESSION['nombre']}, tu cita en {$nombreNegocio} fue agendada.\n";
                    $mensaje .= "Fecha: {$fechaFormateada}\n";
                    $mensaje .= "Hora: {$horaFormateada}\n";
                    $mensaje .= "Con: {$nombreEmpleado}\n";
                    $mensaje .= "Modalidad: " . ($modalidad === 'virtual' ? 'Virtual' : 'Presencial') . "\n";
                    $mensaje .= "Total: \${$total}\n";
                    $mensaje .= "Si necesitas cancelar, hazlo desde 'Mis Citas' en la app.";

                    WhatsApp::enviar($usuario->telefono, $mensaje);
                    error_log("WhatsApp enviado correctamente a {$usuario->telefono}");
                }
            }
        } catch (\Exception $e) {
            error_log('No se pudo enviar el WhatsApp de confirmacion de cita: ' . $e->getMessage());
        }

        // retornamos una respuesta
        echo json_encode(['resultado'=> $resultado]);
    }

    public static function eliminar(){
        // CRITICO: antes cualquiera podia borrar CUALQUIER cita solo
        // mandando su id, sin sesion y sin ser el dueño (IDOR).
        isAuthAPI();

        if($_SERVER['REQUEST_METHOD'] === 'POST'){
            // Este endpoint se usa desde forms HTML clasicos (panel admin
            // y "Mis Citas"), asi que si se valida CSRF aqui.
            csrfVerificar();

            $id = $_POST['id'] ?? '';
            if (!is_numeric($id)) {
                http_response_code(400);
                exit;
            }

            $cita = Cita::find($id);

            if (!$cita) {
                http_response_code(404);
                exit;
            }

            // Solo el dueño de la cita o un admin puede borrarla
            $esDueno = (int) $cita->usuarioId === (int) $_SESSION['id'];
            $esAdmin = isset($_SESSION['admin']);
            if (!$esDueno && !$esAdmin) {
                http_response_code(403);
                exit;
            }

            // Regla de 24 horas: un cliente no puede cancelar de ultimo
            // momento. No aplica a citas "pendiente_pago" (nunca se
            // confirmaron de verdad) ni a un admin (el negocio si puede
            // cancelar cuando haga falta, ej. el empleado se enfermo).
            if (!$esAdmin && $cita->estado !== 'pendiente_pago' && $cita->faltanMenosDe24Horas()) {
                header('Location: /mis-citas?error=fuera_de_plazo');
                exit;
            }

            $cita->eliminar();

            // Redirige a una ruta fija conocida en vez de confiar en
            // HTTP_REFERER (que el cliente controla y puede venir vacio).
            $destino = $esAdmin ? '/admin' : '/cita';
            header('Location: ' . $destino);
        }
    }

    // Cambia la fecha/hora/empleado de una cita YA EXISTENTE (en vez de
    // cancelarla y crear una nueva). Los servicios de la cita no
    // cambian, solo cuando y con quien.
    public static function reagendar(){
        isAuthAPI();
        csrfVerificarAPI();

        header('Content-Type: application/json');

        $citaId = $_POST['citaId'] ?? '';
        $fecha = $_POST['fecha'] ?? '';
        $hora = $_POST['hora'] ?? '';
        $empleadoId = $_POST['empleadoId'] ?? '';

        if (!is_numeric($citaId)) {
            echo json_encode(['error' => 'Cita no válida.']);
            exit;
        }

        $cita = Cita::find($citaId);
        if (!$cita) {
            echo json_encode(['error' => 'Esa cita ya no existe.']);
            exit;
        }

        $esDueno = (int) $cita->usuarioId === (int) $_SESSION['id'];
        $esAdmin = isset($_SESSION['admin']);
        if (!$esDueno && !$esAdmin) {
            echo json_encode(['error' => 'No tienes permiso para modificar esta cita.']);
            exit;
        }

        if ($cita->estado !== 'confirmada') {
            echo json_encode(['error' => 'Solo se pueden reagendar citas ya confirmadas.']);
            exit;
        }

        // Regla de 24 horas sobre la cita ACTUAL (antes de moverla) -
        // mismo criterio que al cancelar.
        if (!$esAdmin && $cita->faltanMenosDe24Horas()) {
            echo json_encode(['error' => 'No se puede reagendar con menos de 24 horas de anticipación.']);
            exit;
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) || !preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $hora)) {
            echo json_encode(['error' => 'Fecha u hora no válidas.']);
            exit;
        }

        if (!is_numeric($empleadoId)) {
            echo json_encode(['error' => 'Debes elegir un(a) ' . strtolower(\Model\Configuracion::obtener()->etiqueta_empleado_singular) . '.']);
            exit;
        }

        $empleado = Empleado::find($empleadoId);
        if (!$empleado || !$empleado->activo) {
            echo json_encode(['error' => 'Ese(a) ' . strtolower(\Model\Configuracion::obtener()->etiqueta_empleado_singular) . ' ya no está disponible, elige otro(a).']);
            exit;
        }

        $horaComparable = substr($hora, 0, 5);
        [$rangoInicio, $rangoFin] = $empleado->horarioParaModalidad($cita->modalidad);
        if ($horaComparable < substr($rangoInicio, 0, 5) || $horaComparable >= substr($rangoFin, 0, 5)) {
            echo json_encode(['error' => 'Esa hora está fuera del horario de este empleado para esa modalidad.']);
            exit;
        }

        // Anti doble-reserva, excluyendo la propia cita que se esta moviendo.
        $fechaEscapada = Cita::escape($fecha);
        $horaEscapada = Cita::escape($hora);
        $empleadoIdEscapado = Cita::escape($empleadoId);
        $citaIdEscapado = Cita::escape($citaId);
        $ocupado = Cita::SQL("SELECT id FROM citas WHERE fecha = '{$fechaEscapada}' AND hora = '{$horaEscapada}' AND empleadoId = '{$empleadoIdEscapado}' AND id != '{$citaIdEscapado}'");
        if (count($ocupado) > 0) {
            echo json_encode(['error' => 'Ese(a) ' . strtolower(\Model\Configuracion::obtener()->etiqueta_empleado_singular) . ' ya está ocupado(a) en ese horario, elige otra hora u otro(a) ' . strtolower(\Model\Configuracion::obtener()->etiqueta_empleado_singular) . '.']);
            exit;
        }

        $cita->fecha = $fecha;
        $cita->hora = $hora;
        $cita->empleadoId = $empleadoId;
        $cita->guardar();

        // Avisar del cambio por correo/WhatsApp - best-effort, no debe
        // tumbar la respuesta si falla.
        try {
            $usuario = Usuario::find($cita->usuarioId);
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
            if ($usuario && !empty($detalleServicios)) {
                $nombreEmpleado = $empleado->nombre . ' ' . $empleado->apellido;
                $email = new Email($usuario->email, $usuario->nombre, '');
                $email->enviarConfirmacionCita($fecha, $hora, $detalleServicios, $total, $nombreEmpleado, $cita->modalidad);
            }
        } catch (\Exception $e) {
            error_log('No se pudo enviar el correo de reagendado: ' . $e->getMessage());
        }

        echo json_encode(['ok' => true]);
    }
}
