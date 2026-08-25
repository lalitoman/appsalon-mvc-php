let paso = 1;
const pasoInicial=1;
const pasoFinal=3;
const cita = {
    id: '',
    nombre: '',
    fecha: '',
    hora: '',
    empleadoId: '',
    servicios: []
}
document.addEventListener('DOMContentLoaded', function() {
    iniciarApp();
});

function iniciarApp() {
    mostrarSeccion(); // Muestra y oculta las secciones
    tabs(); // Cambia la sección cuando se presionen los tabs
    botonesPaginador(); // Agrega o quita los botones del paginador
    paginaSiguiente(); 
    paginaAnterior();

    consultarAPI(); // Consilta la API en el backen de PHP
    consultarEmpleados(); // Trae la lista de barberos/estilistas activos

    idCliente();
    nombreCliente(); // Añade el nombre del cliente al objeto de cita
    seleccionarFecha(); //Añade la facha de la cita en el objeto
    seleccionarEmpleado(); // añade el barbero/estilista elegido en el objeto

    mostraResumen(); // MUestra el resumen de la cita

}

function mostrarSeccion() {

    // Ocultar la sección que tenga la clase de mostrar
    const seccionAnterior = document.querySelector('.mostrar');
    if(seccionAnterior) {
        seccionAnterior.classList.remove('mostrar');
    }

    // Seleccionar la sección con el paso...
    const pasoSelector = `#paso-${paso}`;
    const seccion = document.querySelector(pasoSelector);
    seccion.classList.add('mostrar');

    // Quita la clase de actual al tab anterior
    const tabAnterior = document.querySelector('.actual');
    if(tabAnterior) {
        tabAnterior.classList.remove('actual');
    }

    // Resalta el tab actual
    const tab = document.querySelector(`[data-paso="${paso}"]`);
    tab.classList.add('actual');
}
function tabs() {

    // Agrega y cambia la variable de paso según el tab seleccionado
    const botones = document.querySelectorAll('.tabs button');
    botones.forEach( boton => {
        boton.addEventListener('click', function(e) {
            e.preventDefault();

            paso = parseInt( e.target.dataset.paso );
            mostrarSeccion();

            botonesPaginador(); 

        });
    });
}
function botonesPaginador() {
    const paginaAnterior = document.querySelector('#anterior');
    const paginaSiguiente = document.querySelector('#siguiente');

    if(paso === 1) {
        paginaAnterior.classList.add('ocultar');
        paginaSiguiente.classList.remove('ocultar');
    } else if (paso === 3) {
        paginaAnterior.classList.remove('ocultar');
        paginaSiguiente.classList.add('ocultar');
        mostraResumen();
    } else {
        paginaAnterior.classList.remove('ocultar');
        paginaSiguiente.classList.remove('ocultar');
    }

        mostrarSeccion();
}

function paginaAnterior() {
    const paginaAnterior = document.querySelector('#anterior');
    paginaAnterior.addEventListener('click', function() {

        if(paso <= pasoInicial) return;
        paso--;
        
        botonesPaginador();
    })
}
function paginaSiguiente() {
    const paginaSiguiente = document.querySelector('#siguiente');
    paginaSiguiente.addEventListener('click', function() {

        if(paso >= pasoFinal) return;
        paso++;
        
        botonesPaginador();
    })
}
async function consultarAPI(){
    try {
        const url = '/api/servicios';
        const resultado = await fetch(url);
        const servicios = await resultado.json();
        mostrarServicios(servicios);
    }catch (error) {
        console.log(error);
    }
}

async function consultarEmpleados(){
    try {
        const url = '/api/empleados';
        const resultado = await fetch(url);
        const empleados = await resultado.json();
        mostrarEmpleados(empleados);
    } catch (error) {
        console.log(error);
    }
}

function mostrarEmpleados(empleados){
    const select = document.querySelector('#empleado');
    empleados.forEach(empleado => {
        const { id, nombre, apellido, especialidad } = empleado;
        const opcion = document.createElement('OPTION');
        opcion.value = id;
        opcion.textContent = especialidad ? `${nombre} ${apellido} - ${especialidad}` : `${nombre} ${apellido}`;
        select.appendChild(opcion);
    });
}

function mostrarServicios(servicios){
    servicios.forEach(servicio =>{
        const {id, nombre, precio } = servicio;
        
        const nombreServicio = document.createElement('P');
        nombreServicio.classList.add('nombre-servicio');
        nombreServicio.textContent = nombre;

        const precioServicio = document.createElement('P');
        precioServicio.classList.add('precio-servicio');
        precioServicio.textContent = `$${precio}`;

        const servicioDiv = document.createElement('DIV');
        servicioDiv.classList.add('servicio');
        servicioDiv.dataset.idServicio = id;
        servicioDiv.onclick = function() {
            seleccionarServicio(servicio);
        }

        servicioDiv.appendChild(nombreServicio);
        servicioDiv.appendChild(precioServicio);

        document.querySelector('#servicios').appendChild(servicioDiv)
    });
}
function seleccionarServicio(servicio){
    const {id} = servicio;
    const {servicios} = cita;

    cita.servicios = [...servicios,servicio];

     // Identificar el elemento al que se le da click
    const divServicio = document.querySelector(`[data-id-servicio="${id}"]`);
    divServicio.classList.add('seleccionado');
    
      // Comprobar si un servicio ya fue agregado 
    if( servicios.some( agregado => agregado.id === id ) ) {
        // Eliminarlo
        cita.servicios = servicios.filter( agregado => agregado.id !== id );
        divServicio.classList.remove('seleccionado');
    } else {
        // Agregarlo
        cita.servicios = [...servicios, servicio];
        divServicio.classList.add('seleccionado');
    }
            console.log(cita);
            
}
function idCliente(){
    cita.id = document.querySelector('#id').value;
}
function nombreCliente(){
    cita.nombre = document.querySelector('#nombre').value;
    
}
function seleccionarFecha() {
    const inputFecha = document.querySelector('#fecha');
    inputFecha.addEventListener('input', function(e) {

        const dia = new Date(e.target.value).getUTCDay();

        if( [6, 0].includes(dia) ) {
            e.target.value = '';
            cita.fecha = '';
            mostrarAlerta('Fines de semana no permitidos', 'error', '#paso-2');
        } else {
            cita.fecha = e.target.value;
        }

        actualizarHorarios();
    });
}

function seleccionarEmpleado() {
    const selectEmpleado = document.querySelector('#empleado');
    selectEmpleado.addEventListener('change', function(e) {
        cita.empleadoId = e.target.value;
        actualizarHorarios();
    });
}

// Consulta /api/horarios-disponibles cada vez que ya se tiene fecha Y
// empleado, y dibuja la cuadricula de horas. Reemplaza el input de hora
// libre por horarios reales, marcando ocupados los que ya tiene el
// barbero elegido ese dia.
async function actualizarHorarios(){
    const contenedor = document.querySelector('#horarios');
    const ayuda = document.querySelector('#horarios-ayuda');

    // Cualquier cambio de fecha/empleado invalida la hora que ya
    // estuviera elegida.
    cita.hora = '';
    contenedor.innerHTML = '';

    if(!cita.fecha || !cita.empleadoId) {
        ayuda.textContent = 'Elige primero la fecha y el barbero para ver los horarios disponibles';
        ayuda.classList.remove('ocultar');
        return;
    }

    ayuda.textContent = 'Cargando horarios...';
    ayuda.classList.remove('ocultar');

    try {
        const url = `/api/horarios-disponibles?fecha=${encodeURIComponent(cita.fecha)}&empleadoId=${encodeURIComponent(cita.empleadoId)}`;
        const resultado = await fetch(url);
        const horarios = await resultado.json();

        if(horarios.error) {
            ayuda.textContent = horarios.error;
            return;
        }

        ayuda.classList.add('ocultar');
        mostrarHorarios(horarios);
    } catch (error) {
        ayuda.textContent = 'No se pudieron cargar los horarios, intenta de nuevo.';
        console.log(error);
    }
}

function mostrarHorarios(horarios){
    const contenedor = document.querySelector('#horarios');

    horarios.forEach(slot => {
        const { hora, disponible } = slot;

        const boton = document.createElement('BUTTON');
        boton.type = 'button';
        boton.textContent = hora;
        boton.classList.add('horario');

        if(!disponible) {
            boton.disabled = true;
            boton.classList.add('ocupado');
        } else {
            boton.onclick = function() {
                cita.hora = hora;

                // Quita "seleccionado" del boton anterior y lo marca en este
                const previo = contenedor.querySelector('.seleccionado');
                if(previo) previo.classList.remove('seleccionado');
                boton.classList.add('seleccionado');
            }
        }

        contenedor.appendChild(boton);
    });
}

function mostrarAlerta(mensaje, tipo, elemento, desaparece = true) {

    // Previene que se generen más de 1 alerta
    const alertaPrevia = document.querySelector('.alerta');
    if(alertaPrevia) {
        alertaPrevia.remove();
    }

    // Scripting para crear la alerta
    const alerta = document.createElement('DIV');
    alerta.textContent = mensaje;
    alerta.classList.add('alerta');
    alerta.classList.add(tipo);

    const referencia = document.querySelector(elemento);
    referencia.appendChild(alerta);

    if(desaparece) {
        // Eliminar la alerta
        setTimeout(() => {
            alerta.remove();
        }, 3000);
    }
  
}

function mostraResumen(){
  const resumen = document.querySelector('.contenido-resumen');

    // Limpiar el Contenido de Resumen
    while(resumen.firstChild) {
        resumen.removeChild(resumen.firstChild);
    }

    if(Object.values(cita).includes('') || cita.servicios.length === 0 ) {
        mostrarAlerta('Faltan datos de Servicios, Fecha, Hora o Barbero', 'error', '.contenido-resumen', false);

        return;
    } 
    // Formatear el div de resumen
    const { nombre, fecha, hora, servicios } = cita;

    // Heading para Servicios en Resumen
    const headingServicios = document.createElement('H3');
    headingServicios.textContent = 'Resumen de Servicios';
    resumen.appendChild(headingServicios);

    // Iterando y mostrando los servicios 
    servicios.forEach(servicio =>{
        const {id, precio, nombre} =servicio;
        const contenedorServicio = document.createElement('DIV');
        contenedorServicio.classList.add('contenedor-servicio');
        
        const textoServicio = document.createElement('p');
        textoServicio.textContent = nombre;

        const precioServicio = document.createElement('P');
        precioServicio.innerHTML = `<span>Precio;</span> $${precio}`;

        contenedorServicio.appendChild(textoServicio);
        contenedorServicio.appendChild(precioServicio);

        resumen.appendChild(contenedorServicio);
    })

       // Heading para Cita en Resumen
    const headingCita = document.createElement('H3');
    headingCita.textContent = 'Resumen de Cita';
    resumen.appendChild(headingCita);

    const nombreCliente = document.createElement('P');
    nombreCliente.innerHTML = `<span>Nombre</span> ${nombre}`;
    //formatear la fecha en esapañol
    const fechaObj = new Date(fecha);
    const mes = fechaObj.getMonth();
    const dia = fechaObj.getDate() + 2;
    const year = fechaObj.getFullYear();

    const fechaUTC = new Date(Date.UTC(year, mes, dia));

    const opciones = {weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'}
    const fechaFormateada = fechaUTC.toLocaleDateString('es-MX', opciones);
    console.log(fechaFormateada);

    const fechacita = document.createElement('P');
    fechacita.innerHTML = `<span>Fecha</span> ${fechaFormateada}`;

    const horaCita = document.createElement('P');
    horaCita.innerHTML = `<span>Hora</span> ${hora} Horas`;

    const empleadoSeleccionado = document.querySelector('#empleado').selectedOptions[0];
    const empleadoCita = document.createElement('P');
    empleadoCita.innerHTML = `<span>Con</span> ${empleadoSeleccionado ? empleadoSeleccionado.textContent : ''}`;

    // Botenes para crear una cita 

    const botonReservar = document.createElement('BUTTON');
    botonReservar.classList.add('boton');
    botonReservar.textContent = 'Reservar Cita';
    botonReservar.onclick = reservarCita;

    resumen.appendChild(nombreCliente);
    resumen.appendChild(fechacita);
    resumen.appendChild(horaCita);
    resumen.appendChild(empleadoCita);

    resumen.appendChild(botonReservar);
}

async function reservarCita(){

    const { nombre, fecha, hora, servicios, id, empleadoId} = cita;

    const idServicios = servicios.map(servicio => servicio.id);
    //console.log(idServicios);

    // El token CSRF se expone en un <meta> en el layout (ver views/layout.php)
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    const datos = new FormData();
    datos.append('fecha', fecha);
    datos.append('hora', hora);
    datos.append('usuarioId', id);
    datos.append('empleadoId', empleadoId);
    datos.append('servicios', idServicios);
    datos.append('csrf_token', csrfToken);

    try {
         // Peticion hacia la api
    const url = '/api/citas'
    const respuesta = await fetch(url, {
        method: 'POST',
        body: datos
    });
    
    const resultado = await respuesta.json();
    console.log(resultado);

    if(resultado.error) {
        // Ej: horario ya ocupado, token CSRF invalido, etc.
        Swal.fire({
            icon: 'error',
            title: 'No se pudo agendar',
            text: resultado.error
        });
        return;
    }

    if(resultado.redirigir) {
        // Mercado Pago esta activo: la cita quedo "pendiente_pago" y
        // hay que mandar al cliente a pagar el anticipo antes de que
        // se confirme de verdad.
        Swal.fire({
            icon: 'info',
            title: 'Te vamos a redirigir a Mercado Pago',
            text: 'Para confirmar tu cita, completa el pago del anticipo.',
            timer: 2000,
            showConfirmButton: false
        }).then(() => {
            window.location.href = resultado.redirigir;
        });
        return;
    }

    if(resultado.resultado) {
            Swal.fire({
                icon: 'success',
                title: 'Cita Creada',
                text: 'Tu cita fue creada correctamente',
                button: 'OK'
            }).then( () => {
                setTimeout(() => {
                    window.location.reload();
                }, 3000);
            })
        } 
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Hubo un error al guardar la cita'
        })
    }
        
    
    //console.log([...datos]);
}
