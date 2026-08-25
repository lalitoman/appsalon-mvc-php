<h1 class="nombre-pagina">Configuración del negocio</h1>
<p class="descripcion-pagina">Personaliza el nombre, color y terminología — útil si reutilizas este sistema para otro tipo de negocio (dentista, spa, consultorio, etc.)</p>

<?php
    include_once __DIR__ . '/../templates/barra.php';
    include_once __DIR__ . '/../templates/alertas.php';
?>

<?php if(isset($_GET['actualizado'])) { ?>
    <p class="tex-center" style="color: #329f00; font-weight: 700;">Configuración actualizada correctamente</p>
<?php } ?>

<form method="POST" class="formulario">
    <div class="campo">
        <label for="nombre_negocio">Nombre del negocio</label>
        <input
            type="text"
            id="nombre_negocio"
            name="nombre_negocio"
            value="<?php echo $configuracion->nombre_negocio; ?>"
            placeholder="Ej. AppSalon, Clínica Dental Sonrisa, Consultorio Psicológico..."
            required
            maxlength="100"
        />
    </div>

    <div class="campo">
        <label for="color_primario">Color principal</label>
        <input
            type="color"
            id="color_primario"
            name="color_primario"
            value="<?php echo $configuracion->color_primario; ?>"
        />
    </div>

    <div class="campo">
        <label for="etiqueta_servicios">Etiqueta para "Servicios"</label>
        <input
            type="text"
            id="etiqueta_servicios"
            name="etiqueta_servicios"
            value="<?php echo $configuracion->etiqueta_servicios; ?>"
            placeholder="Ej. Servicios, Tratamientos, Consultas..."
            required
            maxlength="60"
        />
    </div>

    <div class="campo">
        <label for="etiqueta_empleado_singular">Etiqueta para empleado (singular)</label>
        <input
            type="text"
            id="etiqueta_empleado_singular"
            name="etiqueta_empleado_singular"
            value="<?php echo $configuracion->etiqueta_empleado_singular; ?>"
            placeholder="Ej. Barbero / Estilista, Doctor, Terapeuta..."
            required
            maxlength="60"
        />
    </div>

    <div class="campo">
        <label for="etiqueta_empleado_plural">Etiqueta para empleados (plural, botón del menú)</label>
        <input
            type="text"
            id="etiqueta_empleado_plural"
            name="etiqueta_empleado_plural"
            value="<?php echo $configuracion->etiqueta_empleado_plural; ?>"
            placeholder="Ej. Empleados, Doctores, Terapeutas..."
            required
            maxlength="60"
        />
    </div>

    <?php echo csrfInput(); ?>
    <input type="submit" class="boton" value="Guardar cambios">
</form>

<p class="tex-center" style="margin-top: 2rem; opacity: 0.7;">
    Nota: la imagen de fondo del login y el logo siguen siendo fijos por ahora —
    para cambiarlos hay que reemplazar el archivo de imagen directamente
    (ver README, sección White-label).
</p>
