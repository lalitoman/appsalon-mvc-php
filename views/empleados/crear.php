<h1 class="nombre-pagina">Nuevo Empleado</h1>
<p class="descripcion-pagina"> Llena los datos del barbero/estilista</p>

<?php
    include_once __DIR__ . '/../templates/barra.php';
    include_once __DIR__ . '/../templates/alertas.php';
?>

<form action="/empleados/crear" method="POST" class="formulario">
    <?php include_once __DIR__ . '/formulario.php'; ?>
    <input type="submit" class="boton" value="Guardar Empleado">
</form>
