<h1 class="nombre-pagina">Pago en proceso</h1>
<p class="descripcion-pagina">Tu pago está siendo procesado</p>

<?php
    include_once __DIR__ . '/../templates/barra.php';
?>

<p class="tex-center">
    Esto puede pasar con algunos métodos de pago (ej. pago en efectivo
    en tienda) que tardan en confirmarse. En cuanto se confirme el
    pago, tu cita queda reservada automáticamente y te llega el aviso
    por correo/WhatsApp. Puedes revisar el estado más tarde en "Mis
    Citas".
</p>

<div class="acciones">
    <a class="boton" href="/mis-citas">Ver Mis Citas</a>
</div>
