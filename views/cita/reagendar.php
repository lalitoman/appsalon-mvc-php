<h1 class="nombre-pagina">Reagendar Cita</h1>
<p class="descripcion-pagina">Elige la nueva fecha, hora y <?php echo htmlspecialchars(strtolower($configuracionGlobal->etiqueta_empleado_singular)); ?></p>

<?php
    include_once __DIR__ . '/../templates/barra.php';
?>

<form id="formulario-reagendar" class="formulario" method="POST" action="#">
    <div class="campo">
        <label for="fecha">Fecha</label>
        <input
            id="fecha"
            type="date"
            min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
            value="<?php echo $cita->fecha; ?>"
        />
    </div>

    <div class="campo">
        <label for="empleado"><?php echo htmlspecialchars($configuracionGlobal->etiqueta_empleado_singular); ?></label>
        <select id="empleado">
            <option value="">-- Elige --</option>
        </select>
    </div>

    <div class="campo-horarios">
        <label>Hora</label>
        <p id="horarios-ayuda" class="tex-center">Elige la fecha para ver los horarios disponibles</p>
        <div id="horarios" class="listado-horarios"></div>
    </div>

    <div class="acciones">
        <button type="button" id="boton-guardar-reagendar" class="boton">Guardar nuevo horario</button>
    </div>
</form>

<?php
$script = "
        <script src='//cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        <script>
        const citaId = " . (int) $cita->id . ";
        const empleadoActualId = " . (int) $cita->empleadoId . ";
        let horaSeleccionada = '';
        let empleadoSeleccionado = String(empleadoActualId);

        document.addEventListener('DOMContentLoaded', function() {
            cargarEmpleados();
            document.querySelector('#fecha').addEventListener('input', actualizarHorarios);
            document.querySelector('#empleado').addEventListener('change', function(e) {
                empleadoSeleccionado = e.target.value;
                actualizarHorarios();
            });
            document.querySelector('#boton-guardar-reagendar').addEventListener('click', guardarReagendado);
        });

        async function cargarEmpleados() {
            const resultado = await fetch('/api/empleados');
            const empleados = await resultado.json();
            const select = document.querySelector('#empleado');
            empleados.forEach(emp => {
                const opcion = document.createElement('OPTION');
                opcion.value = emp.id;
                opcion.textContent = emp.especialidad ? `\${emp.nombre} \${emp.apellido} - \${emp.especialidad}` : `\${emp.nombre} \${emp.apellido}`;
                if (String(emp.id) === empleadoSeleccionado) opcion.selected = true;
                select.appendChild(opcion);
            });
            actualizarHorarios();
        }

        async function actualizarHorarios() {
            const fecha = document.querySelector('#fecha').value;
            const contenedor = document.querySelector('#horarios');
            const ayuda = document.querySelector('#horarios-ayuda');
            horaSeleccionada = '';
            contenedor.innerHTML = '';

            if (!fecha || !empleadoSeleccionado) {
                ayuda.textContent = 'Elige la fecha y el barbero/estilista para ver los horarios disponibles';
                ayuda.classList.remove('ocultar');
                return;
            }

            ayuda.textContent = 'Cargando horarios...';
            ayuda.classList.remove('ocultar');

            try {
                const url = `/api/horarios-disponibles?fecha=\${encodeURIComponent(fecha)}&empleadoId=\${encodeURIComponent(empleadoSeleccionado)}&excluirCitaId=\${citaId}`;
                const resultado = await fetch(url);
                const horarios = await resultado.json();

                if (horarios.error) {
                    ayuda.textContent = horarios.error;
                    return;
                }

                ayuda.classList.add('ocultar');
                horarios.forEach(slot => {
                    const boton = document.createElement('BUTTON');
                    boton.type = 'button';
                    boton.textContent = slot.hora;
                    boton.classList.add('horario');
                    if (!slot.disponible) {
                        boton.disabled = true;
                        boton.classList.add('ocupado');
                    } else {
                        boton.onclick = function() {
                            horaSeleccionada = slot.hora;
                            const previo = contenedor.querySelector('.seleccionado');
                            if (previo) previo.classList.remove('seleccionado');
                            boton.classList.add('seleccionado');
                        };
                    }
                    contenedor.appendChild(boton);
                });
            } catch (error) {
                ayuda.textContent = 'No se pudieron cargar los horarios, intenta de nuevo.';
            }
        }

        async function guardarReagendado() {
            const fecha = document.querySelector('#fecha').value;

            if (!fecha || !empleadoSeleccionado || !horaSeleccionada) {
                Swal.fire({ icon: 'error', title: 'Faltan datos', text: 'Elige fecha, barbero/estilista y hora antes de continuar.' });
                return;
            }

            const csrfToken = document.querySelector('meta[name=\"csrf-token\"]')?.content ?? '';
            const datos = new FormData();
            datos.append('citaId', citaId);
            datos.append('fecha', fecha);
            datos.append('hora', horaSeleccionada);
            datos.append('empleadoId', empleadoSeleccionado);
            datos.append('csrf_token', csrfToken);

            try {
                const respuesta = await fetch('/api/citas/reagendar', { method: 'POST', body: datos });
                const resultado = await respuesta.json();

                if (resultado.error) {
                    Swal.fire({ icon: 'error', title: 'No se pudo reagendar', text: resultado.error });
                    return;
                }

                Swal.fire({ icon: 'success', title: 'Cita reagendada', text: 'Tu cita se movió correctamente.' }).then(() => {
                    window.location.href = '/mis-citas';
                });
            } catch (error) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Hubo un error al reagendar la cita.' });
            }
        }
        </script>
    ";
?>
