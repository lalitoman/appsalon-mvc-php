<h1 class="nombre-pagina"><?php echo htmlspecialchars($configuracionGlobal->etiqueta_empleado_plural); ?></h1>
<p class="descripcion-pagina"><?php echo htmlspecialchars($configuracionGlobal->etiqueta_empleado_plural); ?> del negocio</p>

<?php
    include_once __DIR__ . '/../templates/barra.php';
?>

<?php if(empty($empleados)) { ?>
    <p class="tex-center">Aún no has agregado ningún <?php echo htmlspecialchars(strtolower($configuracionGlobal->etiqueta_empleado_singular)); ?>.</p>
    <div class="acciones">
        <a class="boton" href="/empleados/crear">Agregar el primero</a>
    </div>
<?php } ?>

<ul class="servicios">
    <?php foreach($empleados as $empleado) { ?>
        <li>
            <p>Nombre: <span><?php echo $empleado->nombre . ' ' . $empleado->apellido; ?></span> </p>
            <?php if($empleado->especialidad) { ?>
                <p>Especialidad: <span><?php echo $empleado->especialidad; ?></span> </p>
            <?php } ?>
            <p>Horario: <span><?php echo substr($empleado->horario_inicio, 0, 5) . ' - ' . substr($empleado->horario_fin, 0, 5); ?></span> </p>
            <p>Estado: <span><?php echo $empleado->activo ? 'Activo' : 'Inactivo'; ?></span> </p>

            <div class="acciones">
                <a class="boton" href="/empleados/actualizar?id=<?php echo $empleado->id; ?>">Actualizar</a>

                <form action="/empleados/eliminar" method="POST" onsubmit="return confirm('¿Seguro que quieres borrar este empleado? Esto no afecta las citas ya agendadas, pero deja de estar disponible para citas nuevas.');">
                    <input type="hidden" name="id" value="<?php echo $empleado->id; ?>">
                    <?php echo csrfInput(); ?>
                    <input type="submit" value="Borrar" class="boton-eliminar">
                </form>
            </div>
        </li>
    <?php } ?>
</ul>

<?php
    $mensajeExito = '';
    $mensajeError = '';
    if(isset($_GET['creado'])) {
        $mensajeExito = 'Empleado creado correctamente';
    } elseif(isset($_GET['actualizado'])) {
        $mensajeExito = 'Empleado actualizado correctamente';
    } elseif(isset($_GET['borrado'])) {
        $mensajeExito = 'Empleado borrado correctamente';
    } elseif(isset($_GET['error']) && $_GET['error'] === 'tiene_citas') {
        $mensajeError = 'No se pudo borrar: este empleado ya tiene citas asociadas. Márcalo como inactivo en su lugar (edítalo y desmarca "Activo").';
    }

    if($mensajeExito || $mensajeError) {
        $script = "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
        if ($mensajeExito) {
            $script .= "<script>Swal.fire({icon: 'success', title: '" . s($mensajeExito) . "', timer: 2500, showConfirmButton: false});</script>";
        } else {
            $script .= "<script>Swal.fire({icon: 'error', title: 'No se pudo borrar', text: '" . s($mensajeError) . "'});</script>";
        }
    }
?>
