
<div class="barra">
    <p>Hola: <?php echo $nombre ?? ''; ?> </p>
    <a class="boton" href="/logout">Cerrar Sesión</a>
</div>

<?php if(isset($_SESSION['admin'])) { ?>
    <div class="barra-servicios">
        <a class="boton" href="/admin">Ver Citas</a>
        <a class="boton" href="/servicios">Ver <?php echo htmlspecialchars($configuracionGlobal->etiqueta_servicios); ?></a>
        <a class="boton" href="/servicios/crear">Nuevo <?php echo htmlspecialchars(rtrim($configuracionGlobal->etiqueta_servicios, 's')); ?></a>
        <a class="boton" href="/empleados"><?php echo htmlspecialchars($configuracionGlobal->etiqueta_empleado_plural); ?></a>
        <a class="boton" href="/empleados/crear">Nuevo <?php echo htmlspecialchars($configuracionGlobal->etiqueta_empleado_singular); ?></a>
        <a class="boton" href="/admin/reportes">Reportes</a>
        <a class="boton" href="/admin/configuracion">Configuración</a>
        <a class="boton" href="/mi-perfil">Mi Perfil</a>
    </div>
<?php } else { ?>
    <div class="barra-servicios">
        <a class="boton" href="/cita">Agendar Cita</a>
        <a class="boton" href="/mis-citas">Mis Citas</a>
        <a class="boton" href="/mi-perfil">Mi Perfil</a>
    </div>
<?php } ?>