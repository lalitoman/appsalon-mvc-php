<h1 class="nombre-pagina">Mi Perfil</h1>
<p class="descripcion-pagina">Edita tus datos y tu contraseña</p>

<?php
    include_once __DIR__ . '/../templates/barra.php';
?>

<?php if(isset($_GET['actualizado'])) { ?>
    <p class="tex-center" style="color: #329f00; font-weight: 700;">Tus datos se actualizaron correctamente</p>
<?php } ?>
<?php if(isset($_GET['password_actualizado'])) { ?>
    <p class="tex-center" style="color: #329f00; font-weight: 700;">Tu contraseña se actualizó correctamente</p>
<?php } ?>

<h2>Datos personales</h2>
<?php
    if(!empty($alertas)) {
        foreach($alertas as $tipo => $mensajes) {
            foreach($mensajes as $mensaje) {
                echo "<div class=\"alerta {$tipo}\">{$mensaje}</div>";
            }
        }
    }
?>
<form method="POST" class="formulario">
    <input type="hidden" name="accion" value="datos">

    <div class="campo">
        <label for="nombre">Nombre</label>
        <input type="text" id="nombre" name="nombre" value="<?php echo $usuario->nombre; ?>" required maxlength="60">
    </div>

    <div class="campo">
        <label for="apellido">Apellido</label>
        <input type="text" id="apellido" name="apellido" value="<?php echo $usuario->apellido; ?>" required maxlength="60">
    </div>

    <div class="campo">
        <label for="telefono">Teléfono</label>
        <input type="tel" id="telefono" name="telefono" value="<?php echo $usuario->telefono; ?>" placeholder="10 dígitos" maxlength="10">
    </div>

    <div class="campo">
        <label>Email</label>
        <input type="email" value="<?php echo $usuario->email; ?>" disabled>
    </div>

    <?php echo csrfInput(); ?>
    <input type="submit" class="boton" value="Guardar datos">
</form>

<h2>Cambiar contraseña</h2>
<?php
    if(!empty($alertasPassword)) {
        foreach($alertasPassword as $tipo => $mensajes) {
            foreach($mensajes as $mensaje) {
                echo "<div class=\"alerta {$tipo}\">{$mensaje}</div>";
            }
        }
    }
?>
<form method="POST" class="formulario">
    <input type="hidden" name="accion" value="password">

    <div class="campo">
        <label for="password_actual">Contraseña actual</label>
        <input type="password" id="password_actual" name="password_actual" required>
    </div>

    <div class="campo">
        <label for="password_nuevo">Nueva contraseña</label>
        <input type="password" id="password_nuevo" name="password_nuevo" required minlength="6">
    </div>

    <div class="campo">
        <label for="password_confirmar">Confirmar nueva contraseña</label>
        <input type="password" id="password_confirmar" name="password_confirmar" required minlength="6">
    </div>

    <?php echo csrfInput(); ?>
    <input type="submit" class="boton" value="Cambiar contraseña">
</form>
