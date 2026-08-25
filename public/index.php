<?php 

require_once __DIR__ . '/../includes/app.php';

use Controllers\AdminController;
use Controllers\APIcontroller;
use Controllers\CitaController;
use Controllers\ConfiguracionController;
use Controllers\EmpleadosController;
use Controllers\LoginController;
use Controllers\PagoController;
use Controllers\PerfilController;
use Controllers\ReportesController;
use Controllers\ServiciosController;
use MVC\Router;
$router = new Router();

// Inicar Sesión

$router->get('/', [LoginController::class, 'login']);
$router->post('/', [LoginController::class, 'login']);
$router->get('/logout', [LoginController::class, 'logout']);

// recuperar password
$router->get('/olvide', [LoginController::class, 'olvide']);
$router->post('/olvide', [LoginController::class, 'olvide']);
$router->get('/recuperar', [LoginController::class, 'recuperar']);
$router->post('/recuperar', [LoginController::class, 'recuperar']);

// Crear Cuenta 
$router->get('/crear-cuenta', [LoginController::class, 'crear']);
$router->post('/crear-cuenta', [LoginController::class, 'crear']);

// AConfirmar Cuenta
$router->get('/confirmar-cuenta',[LoginController::class, 'confirmar']);
$router->get('/mensaje',[LoginController::class, 'mensaje']);

// area privada
$router->get('/cita',[CitaController::class, 'index']);
$router->get('/cita/reagendar',[CitaController::class, 'reagendarForm']);
$router->get('/mis-citas',[CitaController::class, 'misCitas']);
$router->get('/mi-perfil',[PerfilController::class, 'index']);
$router->post('/mi-perfil',[PerfilController::class, 'index']);
$router->get('/admin',[AdminController::class, 'index']);
$router->get('/admin/reportes',[ReportesController::class, 'index']);
$router->get('/admin/configuracion',[ConfiguracionController::class, 'index']);
$router->post('/admin/configuracion',[ConfiguracionController::class, 'index']);

// API de citas
$router->get('/api/servicios', [APIcontroller::class,'index']);
$router->get('/api/empleados', [APIcontroller::class,'empleados']);
$router->get('/api/horarios-disponibles', [APIcontroller::class,'horariosDisponibles']);
$router->post('/api/citas',[APIcontroller::class, 'guardar']);
$router->post('/api/citas/reagendar',[APIcontroller::class, 'reagendar']);
$router->post('/api/eliminar', [APIcontroller::class, 'eliminar']);

// Mercado Pago: webhook (publico, lo llama Mercado Pago, no un navegador)
// y paginas de retorno despues del checkout.
$router->post('/webhook/mercadopago', [PagoController::class, 'webhook']);
$router->get('/webhook/mercadopago', [PagoController::class, 'webhook']); // MP a veces prueba con GET
$router->get('/pago/exito', [PagoController::class, 'exito']);
$router->get('/pago/pendiente', [PagoController::class, 'pendiente']);
$router->get('/pago/error', [PagoController::class, 'error']);

// CROUD de servicios
$router->get('/servicios',[ServiciosController::class, 'index']);
$router->get('/servicios/crear', [ServiciosController::class, 'crear']);
$router->post('/servicios/crear', [ServiciosController::class, 'crear']);
$router->get('/servicios/actualizar', [ServiciosController::class, 'actualizar']);
$router->post('/servicios/actualizar', [ServiciosController::class, 'actualizar']);
$router->post('/servicios/eliminar', [ServiciosController::class, 'eliminar']);

// CROUD de empleados
$router->get('/empleados',[EmpleadosController::class, 'index']);
$router->get('/empleados/crear', [EmpleadosController::class, 'crear']);
$router->post('/empleados/crear', [EmpleadosController::class, 'crear']);
$router->get('/empleados/actualizar', [EmpleadosController::class, 'actualizar']);
$router->post('/empleados/actualizar', [EmpleadosController::class, 'actualizar']);
$router->post('/empleados/eliminar', [EmpleadosController::class, 'eliminar']);


// Comprueba y valida las rutas, que existan y les asigna las funciones del Controlador
$router->comprobarRutas();