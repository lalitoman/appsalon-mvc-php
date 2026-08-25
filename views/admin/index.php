<h1 class="nombre-pagina">Panel de Administración</h1>

<?php 
    include_once __DIR__ . '/../templates/barra.php';
?>

<h2>Buscar Citas</h2>
<div class="busqueda">
    <form class="formulario">
        <div class="campo">
            <label for="fecha">Fecha</label>
            <input 
                type="date"
                id="fecha"
                name="fecha"
                value="<?php echo $fecha; ?>"
            />
        </div>
    </form> 
</div>

<?php
    if(count($citas) === 0) {
        echo "<h2>No Hay Citas en esta fecha</h2>";
    }
?>

<div id="citas-admin">
    <ul class="citas">   
            <?php 
                $idCita = 0;
                foreach( $citas as $key => $cita ) {
   
                    if($idCita !== $cita->id) {
                        $total = 0;
            ?>
            <li>
                    <p>ID: <span><?php echo $cita->id; ?></span></p>
                    <p>Hora: <span><?php echo substr($cita->hora, 0, 5); ?></span></p>
                    <p>Cliente: <span><?php echo $cita->cliente; ?></span></p>
                    <p>Email: <span><?php echo $cita->email; ?></span></p>
                    <p>Email: <span><?php echo $cita->telefono; ?></span></p>
                    <p>Con: <span><?php echo $cita->empleado ?: 'Sin asignar'; ?></span></p>
                    <?php if($cita->estado === 'pendiente_pago') { ?>
                        <p style="color: #cb0000; font-weight: 700;">⏳ Pendiente de pago (anticipo)</p>
                    <?php } ?>
                    <?php if($cita->montoAnticipo) {
                        $etiquetasPago = [
                            'approved' => ['✅ Anticipo pagado', '#329f00'],
                            'pending' => ['⏳ Anticipo en proceso', '#cb0000'],
                            'pendiente' => ['⏳ Anticipo en proceso', '#cb0000'],
                            'rejected' => ['❌ Anticipo rechazado', '#cb0000'],
                            'cancelled' => ['❌ Anticipo cancelado', '#cb0000'],
                        ];
                        [$etiquetaPago, $colorPago] = $etiquetasPago[$cita->estadoPago] ?? ['Anticipo: ' . $cita->estadoPago, '#1a1b15'];
                    ?>
                        <p style="color: <?php echo $colorPago; ?>; font-weight: 700;">
                            <?php echo $etiquetaPago; ?> — $<?php echo number_format((float) $cita->montoAnticipo, 2); ?>
                        </p>
                    <?php } ?>

                    <h3>Servicios</h3>
            <?php 
                $idCita = $cita->id;
            } // Fin de IF 
                $total += $cita->precio;
            ?>
                    <p class="servicio"><?php echo $cita->servicio . " " . $cita->precio; ?></p>
            
            <?php 
                $actual = $cita->id;
                $proximo = $citas[$key + 1]->id ?? 0;

                if(esUltimo($actual, $proximo)) { ?>
                    <p class="total">Total: <span>$ <?php echo $total; ?></span></p>

                    <?php if($cita->estado === 'confirmada') { ?>
                        <div class="acciones">
                            <a class="boton" href="/cita/reagendar?id=<?php echo $cita->id; ?>">Reagendar</a>
                        </div>
                    <?php } ?>

                    <form action="/api/eliminar" method="POST" onsubmit="return confirm('¿Seguro que quieres eliminar esta cita?');">
                        <input type="hidden" name="id" value="<?php echo $cita->id; ?>">
                        <?php echo csrfInput(); ?>
                        <input type="submit" class="boton-eliminar" value="Eliminar">
                    </form>

            <?php } 
          } // Fin de Foreach ?>
     </ul>
</div>

<?php
    $script = "<script src='build/js/buscador.js'></script>"
?>