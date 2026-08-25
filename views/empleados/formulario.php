<div class="campo">
    <label for="nombre">Nombre</label>
    <input
        type="text"
        id="nombre"
        placeholder="Nombre del <?php echo htmlspecialchars(strtolower($configuracionGlobal->etiqueta_empleado_singular)); ?>"
        name="nombre"
        value="<?php echo $empleado->nombre; ?>"
        required
        maxlength="60"
    />
</div>

<div class="campo">
    <label for="apellido">Apellido</label>
    <input
        type="text"
        id="apellido"
        placeholder="Apellido"
        name="apellido"
        value="<?php echo $empleado->apellido; ?>"
        required
        maxlength="60"
    />
</div>

<div class="campo">
    <label for="especialidad">Especialidad (opcional)</label>
    <input
        type="text"
        id="especialidad"
        placeholder="Ej. Cortes clásicos, Barba y diseño..."
        name="especialidad"
        value="<?php echo $empleado->especialidad; ?>"
        maxlength="100"
    />
</div>

<div class="campo">
    <label for="horario_inicio">Horario - Inicio</label>
    <input
        type="time"
        id="horario_inicio"
        name="horario_inicio"
        value="<?php echo substr($empleado->horario_inicio, 0, 5); ?>"
        required
    />
</div>

<div class="campo">
    <label for="horario_fin">Horario - Fin</label>
    <input
        type="time"
        id="horario_fin"
        name="horario_fin"
        value="<?php echo substr($empleado->horario_fin, 0, 5); ?>"
        required
    />
</div>

<?php if(!is_null($empleado->id)) { ?>
<div class="campo">
    <label for="activo">
        <input
            type="checkbox"
            id="activo"
            name="activo"
            value="1"
            <?php echo $empleado->activo ? 'checked' : ''; ?>
        />
        Activo (visible para los clientes al agendar)
    </label>
</div>
<?php } ?>

<?php echo csrfInput(); ?>
