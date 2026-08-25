<h1 class="nombre-pagina">Reportes</h1>
<p class="descripcion-pagina">Ingresos, servicios y empleados más solicitados</p>

<?php
    include_once __DIR__ . '/../templates/barra.php';
?>

<form class="formulario formulario-reportes" method="GET">
    <div class="campo">
        <label for="desde">Desde</label>
        <input type="date" id="desde" name="desde" value="<?php echo s($desde); ?>">
    </div>
    <div class="campo">
        <label for="hasta">Hasta</label>
        <input type="date" id="hasta" name="hasta" value="<?php echo s($hasta); ?>">
    </div>
    <input type="submit" class="boton" value="Filtrar">
</form>

<div class="kpis">
    <div class="kpi">
        <p class="kpi-numero">$<?php echo number_format((float) $resumen['totalIngresos'], 2); ?></p>
        <p class="kpi-etiqueta">Ingresos totales</p>
    </div>
    <div class="kpi">
        <p class="kpi-numero"><?php echo (int) $resumen['totalCitas']; ?></p>
        <p class="kpi-etiqueta">Citas atendidas</p>
    </div>
    <div class="kpi">
        <p class="kpi-numero"><?php echo (int) $resumen['totalClientes']; ?></p>
        <p class="kpi-etiqueta">Clientes distintos</p>
    </div>
    <div class="kpi">
        <p class="kpi-numero">$<?php echo number_format($ticketPromedio, 2); ?></p>
        <p class="kpi-etiqueta">Ticket promedio</p>
    </div>
</div>

<?php if(!empty($ingresosPorDia)) { ?>
    <h2>Ingresos por día</h2>
    <?php
        $maxIngreso = max(array_column($ingresosPorDia, 'ingresos'));
        $maxIngreso = $maxIngreso > 0 ? $maxIngreso : 1;
    ?>
    <div class="grafica-barras">
        <?php foreach($ingresosPorDia as $dia) {
            $alturaPct = ((float) $dia['ingresos'] / $maxIngreso) * 100;
            $fechaCorta = date('d/m', strtotime($dia['fecha']));
        ?>
            <div class="barra-dia">
                <div class="barra" style="height: <?php echo $alturaPct; ?>%;" title="$<?php echo number_format((float) $dia['ingresos'], 2); ?>"></div>
                <p class="barra-etiqueta"><?php echo s($fechaCorta); ?></p>
            </div>
        <?php } ?>
    </div>
<?php } ?>

<div class="reportes-tablas">
    <div class="reporte-tabla">
        <h2>Servicios más vendidos</h2>
        <?php if(empty($topServicios)) { ?>
            <p class="tex-center">Sin datos en este rango de fechas</p>
        <?php } else { ?>
            <table>
                <thead>
                    <tr><th>Servicio</th><th>Veces vendido</th><th>Ingresos</th></tr>
                </thead>
                <tbody>
                    <?php foreach($topServicios as $servicio) { ?>
                        <tr>
                            <td><?php echo s($servicio['nombre']); ?></td>
                            <td><?php echo (int) $servicio['vecesVendido']; ?></td>
                            <td>$<?php echo number_format((float) $servicio['ingresos'], 2); ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        <?php } ?>
    </div>

    <div class="reporte-tabla">
        <h2>Empleados con más citas</h2>
        <?php if(empty($topEmpleados)) { ?>
            <p class="tex-center">Sin datos en este rango de fechas</p>
        <?php } else { ?>
            <table>
                <thead>
                    <tr><th>Empleado</th><th>Citas</th><th>Ingresos generados</th></tr>
                </thead>
                <tbody>
                    <?php foreach($topEmpleados as $empleado) { ?>
                        <tr>
                            <td><?php echo s($empleado['nombre']); ?></td>
                            <td><?php echo (int) $empleado['totalCitas']; ?></td>
                            <td>$<?php echo number_format((float) $empleado['ingresos'], 2); ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        <?php } ?>
    </div>

    <div class="reporte-tabla">
        <h2>Clientes más frecuentes</h2>
        <?php if(empty($topClientes)) { ?>
            <p class="tex-center">Sin datos en este rango de fechas</p>
        <?php } else { ?>
            <table>
                <thead>
                    <tr><th>Cliente</th><th>Email</th><th>Citas</th><th>Total gastado</th></tr>
                </thead>
                <tbody>
                    <?php foreach($topClientes as $cliente) { ?>
                        <tr>
                            <td><?php echo s($cliente['nombre']); ?></td>
                            <td><?php echo s($cliente['email']); ?></td>
                            <td><?php echo (int) $cliente['totalCitas']; ?></td>
                            <td>$<?php echo number_format((float) $cliente['gastado'], 2); ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        <?php } ?>
    </div>
</div>
