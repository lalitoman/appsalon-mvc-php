document.addEventListener('DOMContentLoaded', function(){
    inicarApp();
});

function inicarApp(){
    buscarPorFecha();

}

function buscarPorFecha() {
    const fechaInput = document.querySelector('#fecha');
    fechaInput.addEventListener('input', function(e) {
        const fechaSeleccionda = e.target.value;

        window.location = `?fecha=${fechaSeleccionda}`;
    });
}