<div class="campo">
    <label for="nombre">Nombre</label>
    <input
        type="text"
        id="nombre"
        placeholder="Nombre Servicio"
        name="nombre"
        value="<?php echo $servicio-> nombre; ?>"
        required
        maxlength="100"
    />
</div>

<div class="campo">
    <label for="precio">Precio</label>
    <input
        type="number"
        id="precio"
        placeholder="Precio Servicio"
        name="precio"
        value="<?php echo $servicio-> precio; ?>"
        required
        min="0"
        step="0.01"
    />
</div>

<div class="campo">
    <label for="anticipo">Anticipo para agendar (opcional)</label>
    <input
        type="number"
        id="anticipo"
        placeholder="Déjalo en 0 para usar el mínimo general"
        name="anticipo"
        value="<?php echo $servicio->anticipo; ?>"
        min="0"
        step="0.01"
    />
</div>
<?php echo csrfInput(); ?>