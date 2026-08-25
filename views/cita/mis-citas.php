<h1 class="nombre-pagina">Mis Citas</h1>
<p class="descripcion-pagina">Consulta el historial y estado de tus citas</p>

<?php
    include_once __DIR__ . '/../templates/barra.php';
?>

<?php if(isset($_GET['error']) && $_GET['error'] === 'fuera_de_plazo') { ?>
    <p class="tex-center" style="color: #cb0000; font-weight: 700;">
        No se puede cancelar/reagendar con menos de 24 horas de anticipación.
        Si es una emergencia, contacta directamente al negocio.
    </p>
<?php } ?>

<?php
    if(count($citas) === 0) {
        echo "<h2>Aún no tienes citas agendadas</h2>";
        echo '<div class="acciones"><a class="boton" href="/cita">Agendar una cita</a></div>';
    }
?>

<div id="mis-citas">
    <ul class="citas">
        <?php
            $idCita = 0;
            foreach( $citas as $key => $cita ) {

                if($idCita !== $cita->id) {
                    $total = 0;
                    $fechaFormateada = date('d/m/Y', strtotime($cita->fecha));
                    // Misma regla que el servidor (Cita::faltanMenosDe24Horas):
                    // no se puede cancelar/reagendar con <24h de anticipacion,
                    // salvo que la cita siga pendiente de pago.
                    $bloqueadoPor24h = $cita->estado !== 'pendiente_pago'
                        && strtotime($cita->fecha . ' ' . $cita->hora) < strtotime('+24 hours');
        ?>
        <li>
                <p>Fecha: <span><?php echo s($fechaFormateada); ?></span></p>
                <p>Hora: <span><?php echo s(substr($cita->hora, 0, 5)); ?></span></p>
                <p>Con: <span><?php echo s($cita->empleado ?: 'Sin asignar'); ?></span></p>
                <?php if($cita->estado === 'pendiente_pago') { ?>
                    <p style="color: #cb0000; font-weight: 700;">⏳ Pendiente de pago del anticipo</p>
                <?php } ?>
                <?php if($cita->montoAnticipo) {
                    $etiquetasPago = [
                        'approved' => ['✅ Anticipo pagado', '#329f00'],
                        'pending' => ['⏳ Anticipo en proceso', '#cb0000'],
                        'pendiente' => ['⏳ Anticipo en proceso', '#cb0000'],
                        'rejected' => ['❌ Anticipo rechazado', '#cb0000'],
                        'cancelled' => ['❌ Anticipo cancelado', '#cb0000'],
                    ];
                    [$etiquetaPago, $colorPago] = $etiquetasPago[$cita->estadoPago] ?? ['Anticipo: ' . s($cita->estadoPago), '#1a1b15'];
                ?>
                    <p style="color: <?php echo $colorPago; ?>; font-weight: 700;">
                        <?php echo $etiquetaPago; ?> — $<?php echo number_format((float) $cita->montoAnticipo, 2); ?>
                    </p>
                <?php } ?>

                <h3>Servicios</h3>
        <?php
            $idCita = $cita->id;
            $idCitaActual = $cita->id;
        } // Fin de IF
            $total += $cita->precio;
        ?>
                <p class="servicio"><?php echo s($cita->servicio) . " $" . s($cita->precio); ?></p>

        <?php
            $actual = $cita->id;
            $proximo = $citas[$key + 1]->id ?? 0;

            if(esUltimo($actual, $proximo)) { ?>
                <p class="total">Total: <span>$ <?php echo $total; ?></span></p>

                <?php if($bloqueadoPor24h) { ?>
                    <p style="opacity: 0.7;">Faltan menos de 24 horas — ya no se puede cancelar ni reagendar desde aquí.</p>
                <?php } else { ?>
                    <div class="acciones">
                        <a class="boton" href="/cita/reagendar?id=<?php echo $idCitaActual; ?>">Reagendar</a>
                    </div>
                    <form action="/api/eliminar" method="POST" onsubmit="return confirm('¿Seguro que quieres cancelar esta cita?');">
                        <input type="hidden" name="id" value="<?php echo $idCitaActual; ?>">
                        <?php echo csrfInput(); ?>
                        <input type="submit" class="boton-eliminar" value="Cancelar Cita">
                    </form>
                <?php } ?>

        <?php }
      } // Fin de Foreach ?>
     </ul>
</div>

<div class="acciones">
    <a class="boton" href="/cita">Agendar Nueva Cita</a>
</div>
