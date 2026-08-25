<h1 class="nombre-pagina">Servicios</h1>
<p class="descripcion-pagina"> Admistración de Servicios</p>

<?php
    include_once __DIR__ . '/../templates/barra.php';
?>

<ul class="servicios">
    <?php foreach($servicios as $servicio) { ?>
        <li>
            <p>Nombre: <span><?php echo $servicio->nombre; ?></span> </p>
            <p>Precio: <span>$<?php echo $servicio->precio; ?></span> </p>

            <div class="acciones">
                <a class="boton" href="/servicios/actualizar?id=<?php echo $servicio->id; ?>">Actualizar</a>

                <form action="/servicios/eliminar" method="POST" onsubmit="return confirm('¿Seguro que quieres borrar este servicio? Esta acción no se puede deshacer.');">
                    <input type="hidden" name="id" value="<?php echo $servicio->id; ?>">
                    <?php echo csrfInput(); ?>
                    <input type="submit" value="Borrar" class="boton-eliminar">
                </form>
            </div>
        </li>
    <?php } ?>
</ul>

<?php
    $mensajeExito = '';
    if(isset($_GET['creado'])) {
        $mensajeExito = 'Servicio creado correctamente';
    } elseif(isset($_GET['actualizado'])) {
        $mensajeExito = 'Servicio actualizado correctamente';
    } elseif(isset($_GET['borrado'])) {
        $mensajeExito = 'Servicio borrado correctamente';
    }

    if($mensajeExito) {
        $script = "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
        $script .= "<script>Swal.fire({icon: 'success', title: '" . s($mensajeExito) . "', timer: 2500, showConfirmButton: false});</script>";
    }
?>