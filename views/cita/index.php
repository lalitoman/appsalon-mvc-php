<h1 class="nombre-pagina">Crea Nueva Cita</h1>
<p class="descripcion-pagina">Elige tus servicios y coloca tus datos</p>

<?php 
    include_once __DIR__ . '/../templates/barra.php';
?>
<div class="app">
    <nav class="tabs">
        <button class="actual" type="button" data-paso="1">Servicios</button>
        <button type="button" data-paso="2">Información Cita</button>
        <button type="button" data-paso="3">Resumen</button>

    </nav>
    <div id="paso-1"class="seccion">
        <h2>Servicios</h2>
        <p class="tex-center">Elige tus servicios a continuación</p>
        <div id="servicios" class="listado-servicios"></div>
    </div>
    <div id="paso-2"class="seccion">
        <h2>Tus datos y cita </h2>
        <p class="tex-center">Coloca tus datos y fecha de tu cita</p>
        <form classs="formulario">
            <div class="campo">
                <label for="nombre">Nombre</label>
                <input 
                    id="nombre"
                    type="text"
                    placeholder="Tu nombre"
                    value="<?php echo $nombre;?>"
                    disabled
                
                />
            </div>

            <div class="campo">
                <label for="fecha">Fecha</label>
                <input 
                    id="fecha"
                    type="date"
                    min="<?php echo date('Y-m-d', strtotime('+1 day') ); ?>"             
                />
            </div>

            <div class="campo">
                <label for="empleado">Barbero / Estilista</label>
                <select id="empleado">
                    <option value="">-- Elige --</option>
                </select>
            </div>

            <div class="campo-horarios">
                <label>Hora</label>
                <p id="horarios-ayuda" class="tex-center">Elige primero la fecha y el barbero para ver los horarios disponibles</p>
                <div id="horarios" class="listado-horarios"></div>
            </div>
            <input type="hidden" id= "id" value="<?php echo $id;?>">
        </form>
    </div>
    <div id="paso-3"class="seccion contenido-resumen">
        <h2>Resumen</h2>
        <p class="tex-center">Verifica que la información sea correcta</p>
    </div>
    <div class="paginacion">
        <button 
            id="anterior"
            class="boton"
            >&laquo; Anterior</button>

            <button 
            id="siguiente"
            class="boton"
            >Siguiente &raquo;</button>
    </div>

</div>

<?php
$script = "
        <script src='//cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        <script src='build/js/app.js'></script>
    ";
?>