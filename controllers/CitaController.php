<?php

namespace Controllers;

use Model\Cita;
use Model\Empleado;
use Model\MisCita;
use MVC\Router;

class CitaController{
    public static function index(Router $router){
        
        //session_start();
        isAuth();

        $router->render('cita/index', [
            'nombre' => $_SESSION['nombre'],
            'id' => $_SESSION['id']
        ]);
    }

    // Muestra el formulario para cambiar fecha/hora/empleado de una
    // cita ya existente (sin tocar sus servicios).
    public static function reagendarForm(Router $router){
        isAuth();

        $citaId = $_GET['id'] ?? '';
        if (!is_numeric($citaId)) {
            header('Location: /mis-citas');
            exit;
        }

        $cita = Cita::find($citaId);
        if (!$cita) {
            header('Location: /mis-citas');
            exit;
        }

        $esDueno = (int) $cita->usuarioId === (int) $_SESSION['id'];
        $esAdmin = isset($_SESSION['admin']);
        if (!$esDueno && !$esAdmin) {
            header('Location: /mis-citas');
            exit;
        }

        if ($cita->estado !== 'confirmada') {
            header('Location: /mis-citas');
            exit;
        }

        if (!$esAdmin && $cita->faltanMenosDe24Horas()) {
            header('Location: /mis-citas?error=fuera_de_plazo');
            exit;
        }

        $empleadoActual = Empleado::find($cita->empleadoId);

        $router->render('cita/reagendar', [
            'nombre' => $_SESSION['nombre'],
            'cita' => $cita,
            'empleadoActual' => $empleadoActual,
        ]);
    }

    public static function misCitas(Router $router){
        isAuth();

        // El id viene de la sesion (no de input del usuario), pero
        // igual se castea a int por profundidad de defensa antes de
        // ir a una consulta SQL cruda (ActiveRecord::SQL no soporta
        // consultas preparadas).
        $usuarioId = (int) $_SESSION['id'];

        $consulta = "SELECT citas.id, citas.fecha, citas.hora, citas.estado, servicios.nombre as servicio, servicios.precio, ";
        $consulta .= " CONCAT( empleados.nombre, ' ', empleados.apellido) as empleado, ";
        $consulta .= " pagos.monto as montoAnticipo, pagos.estado as estadoPago ";
        $consulta .= " FROM citas ";
        $consulta .= " INNER JOIN citaservicios ON citaservicios.citaId = citas.id ";
        $consulta .= " INNER JOIN servicios ON servicios.id = citaservicios.servicioId ";
        $consulta .= " LEFT JOIN empleados ON empleados.id = citas.empleadoId ";
        $consulta .= " LEFT JOIN pagos ON pagos.id = (SELECT MAX(p2.id) FROM pagos p2 WHERE p2.citaId = citas.id) ";
        $consulta .= " WHERE citas.usuarioId = {$usuarioId} ";
        $consulta .= " ORDER BY citas.fecha DESC, citas.hora DESC, citas.id DESC ";

        $citas = MisCita::SQL($consulta);

        $router->render('cita/mis-citas', [
            'nombre' => $_SESSION['nombre'],
            'citas' => $citas
        ]);
    }
}