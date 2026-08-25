<?php

namespace Controllers;

use Model\AdminCita;
use MVC\Router;

class AdminController{
    public static function index(Router $router){
        //session_start();
        isAdmin();

        $fecha = $_GET['fecha'] ?? date('Y-m-d');

        // Validacion estricta de formato ANTES de tocar la BD.
        // Antes: se hacia explode('-', $fecha) y checkdate() sobre los
        // pedazos, pero la variable $fecha ORIGINAL (sin validar/escapar)
        // se seguia concatenando directo en el SQL -> inyeccion SQL.
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            header('Location: /404');
            exit;
        }

        [$anio, $mes, $dia] = explode('-', $fecha);
        if (!checkdate((int) $mes, (int) $dia, (int) $anio)) {
            header('Location: /404');
            exit;
        }

         // Consultar la base de datos
        // $fecha ya quedo validada con la regex de arriba (solo digitos
        // y guiones en formato YYYY-MM-DD), pero igual se escapa por
        // profundidad de defensa ya que ActiveRecord::SQL no soporta
        // consultas preparadas.
        $fechaEscapada = AdminCita::escape($fecha);

        $consulta = "SELECT citas.id, citas.hora, citas.estado, CONCAT( usuarios.nombre, ' ', usuarios.apellido) as cliente, ";
        $consulta .= " usuarios.email, usuarios.telefono, servicios.nombre as servicio, servicios.precio, ";
        $consulta .= " CONCAT( empleados.nombre, ' ', empleados.apellido) as empleado, ";
        $consulta .= " pagos.monto as montoAnticipo, pagos.estado as estadoPago ";
        $consulta .= " FROM citas  ";
        $consulta .= " INNER JOIN  usuarios ";
        $consulta .= " ON citas.usuarioId=usuarios.id  ";
        $consulta .= " INNER JOIN  citaservicios ";
        $consulta .= " ON citaservicios.citaId=citas.id ";
        $consulta .= " INNER JOIN  servicios ";
        $consulta .= " ON servicios.id=citaservicios.servicioId ";
        $consulta .= " LEFT JOIN  empleados ";
        $consulta .= " ON empleados.id=citas.empleadoId ";
        // El pago mas reciente de esta cita (si tuvo mas de un intento,
        // ej. uno rechazado y luego uno aprobado, se muestra el ultimo).
        $consulta .= " LEFT JOIN pagos ON pagos.id = (SELECT MAX(p2.id) FROM pagos p2 WHERE p2.citaId = citas.id) ";
        $consulta .= " WHERE fecha =  '{$fechaEscapada}' ";

        $citas = AdminCita::SQL($consulta);
       

        $router->render('admin/index',[
            'nombre'=> $_SESSION['nombre'],
            'citas' => $citas, 
            'fecha' => $fecha
        ]);
    }
}