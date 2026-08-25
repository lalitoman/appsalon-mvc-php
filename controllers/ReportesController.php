<?php

namespace Controllers;

use Model\ActiveRecord;
use MVC\Router;

class ReportesController{
    public static function index(Router $router){
        isAdmin();

        // Rango de fechas: por defecto, el mes en curso.
        $desde = $_GET['desde'] ?? date('Y-m-01');
        $hasta = $_GET['hasta'] ?? date('Y-m-t');

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) {
            header('Location: /404');
            exit;
        }
        [$anioD, $mesD, $diaD] = explode('-', $desde);
        [$anioH, $mesH, $diaH] = explode('-', $hasta);
        if (!checkdate((int) $mesD, (int) $diaD, (int) $anioD) || !checkdate((int) $mesH, (int) $diaH, (int) $anioH)) {
            header('Location: /404');
            exit;
        }
        // "desde" nunca despues de "hasta" - si el admin invierte el
        // rango en la URL, se corrigen solos en vez de regresar vacio.
        if ($desde > $hasta) {
            [$desde, $hasta] = [$hasta, $desde];
        }

        $desdeEscapado = ActiveRecord::escape($desde);
        $hastaEscapado = ActiveRecord::escape($hasta);
        $rangoSQL = "citas.fecha BETWEEN '{$desdeEscapado}' AND '{$hastaEscapado}' AND citas.estado = 'confirmada'";

        // --- KPIs generales ---
        $resumen = ActiveRecord::consultarSQLPlano("
            SELECT
                COUNT(DISTINCT citas.id) as totalCitas,
                COALESCE(SUM(servicios.precio), 0) as totalIngresos,
                COUNT(DISTINCT citas.usuarioId) as totalClientes
            FROM citas
            INNER JOIN citaservicios ON citaservicios.citaId = citas.id
            INNER JOIN servicios ON servicios.id = citaservicios.servicioId
            WHERE {$rangoSQL}
        ");
        $resumen = $resumen[0] ?? ['totalCitas' => 0, 'totalIngresos' => 0, 'totalClientes' => 0];
        $ticketPromedio = $resumen['totalCitas'] > 0
            ? $resumen['totalIngresos'] / $resumen['totalCitas']
            : 0;

        // --- Servicios mas vendidos (por cantidad e ingresos) ---
        $topServicios = ActiveRecord::consultarSQLPlano("
            SELECT
                servicios.nombre,
                COUNT(*) as vecesVendido,
                SUM(servicios.precio) as ingresos
            FROM citaservicios
            INNER JOIN citas ON citas.id = citaservicios.citaId
            INNER JOIN servicios ON servicios.id = citaservicios.servicioId
            WHERE {$rangoSQL}
            GROUP BY servicios.id, servicios.nombre
            ORDER BY vecesVendido DESC
            LIMIT 10
        ");

        // --- Empleados con mas citas / mas ingresos generados ---
        $topEmpleados = ActiveRecord::consultarSQLPlano("
            SELECT
                CONCAT(empleados.nombre, ' ', empleados.apellido) as nombre,
                COUNT(DISTINCT citas.id) as totalCitas,
                COALESCE(SUM(servicios.precio), 0) as ingresos
            FROM citas
            INNER JOIN empleados ON empleados.id = citas.empleadoId
            INNER JOIN citaservicios ON citaservicios.citaId = citas.id
            INNER JOIN servicios ON servicios.id = citaservicios.servicioId
            WHERE {$rangoSQL}
            GROUP BY empleados.id, nombre
            ORDER BY totalCitas DESC
            LIMIT 10
        ");

        // --- Clientes mas frecuentes ---
        $topClientes = ActiveRecord::consultarSQLPlano("
            SELECT
                CONCAT(usuarios.nombre, ' ', usuarios.apellido) as nombre,
                usuarios.email,
                COUNT(DISTINCT citas.id) as totalCitas,
                COALESCE(SUM(servicios.precio), 0) as gastado
            FROM citas
            INNER JOIN usuarios ON usuarios.id = citas.usuarioId
            INNER JOIN citaservicios ON citaservicios.citaId = citas.id
            INNER JOIN servicios ON servicios.id = citaservicios.servicioId
            WHERE {$rangoSQL}
            GROUP BY usuarios.id, nombre, usuarios.email
            ORDER BY totalCitas DESC
            LIMIT 10
        ");

        // --- Ingresos por dia (para la barra visual simple) ---
        $ingresosPorDia = ActiveRecord::consultarSQLPlano("
            SELECT
                citas.fecha,
                COALESCE(SUM(servicios.precio), 0) as ingresos
            FROM citas
            INNER JOIN citaservicios ON citaservicios.citaId = citas.id
            INNER JOIN servicios ON servicios.id = citaservicios.servicioId
            WHERE {$rangoSQL}
            GROUP BY citas.fecha
            ORDER BY citas.fecha ASC
        ");

        $router->render('admin/reportes', [
            'nombre' => $_SESSION['nombre'],
            'desde' => $desde,
            'hasta' => $hasta,
            'resumen' => $resumen,
            'ticketPromedio' => $ticketPromedio,
            'topServicios' => $topServicios,
            'topEmpleados' => $topEmpleados,
            'topClientes' => $topClientes,
            'ingresosPorDia' => $ingresosPorDia,
        ]);
    }
}
