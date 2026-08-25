<h1 class="nombre-pagina">No se pudo procesar el pago</h1>
<p class="descripcion-pagina">Tu cita no quedó reservada</p>

<?php
    include_once __DIR__ . '/../templates/barra.php';
?>

<p class="tex-center">
    El pago no se completó, así que el horario que intentaste apartar
    quedó liberado para otros clientes. Puedes intentar agendar de
    nuevo cuando quieras.
</p>

<div class="acciones">
    <a class="boton" href="/cita">Agendar de nuevo</a>
</div>
