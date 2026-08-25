<?php

namespace Controllers;

use Model\Empleado;
use MVC\Router;

class EmpleadosController{
    public static function index(Router $router){
        isAdmin();

        $empleados = Empleado::all();

        $router->render('empleados/index', [
            'nombre'=> $_SESSION['nombre'],
            'empleados'=>$empleados
        ]);
    }

    public static function crear(Router $router){
        isAdmin();
        $empleado = new Empleado;
        $alertas = [];

        if($_SERVER['REQUEST_METHOD']  ==='POST'){
            csrfVerificar();
            $empleado->sincronizar($_POST);
            // El checkbox "activo" solo llega en $_POST si esta marcado;
            // si no llega, el modelo ya lo pone en 1 por defecto en su
            // constructor, asi que aqui no hace falta forzarlo.
            $alertas = $empleado->validar();
            if(empty($alertas)){
                $empleado->guardar();
                header('Location: /empleados?creado=1');
                exit;
            }
        }
        $router->render('empleados/crear', [
            'nombre'=> $_SESSION['nombre'],
            'empleado'=> $empleado,
            'alertas'=> $alertas
        ]);
    }

    public static function actualizar(Router $router){
        isAdmin();

        if(!is_numeric($_GET['id'] ?? '')) {
            header('Location: /404');
            exit;
        }
        $empleado = Empleado::find(($_GET['id']));
        if (!$empleado) {
            header('Location: /404');
            exit;
        }
        $alertas = [];
        if($_SERVER['REQUEST_METHOD']  ==='POST'){
            csrfVerificar();
            // El checkbox de "activo" no llega en $_POST cuando esta
            // desmarcado (asi funcionan los checkboxes HTML), asi que se
            // fuerza explicitamente antes de sincronizar el resto.
            $_POST['activo'] = isset($_POST['activo']) ? 1 : 0;
            $empleado->sincronizar($_POST);

            $alertas = $empleado->validar();

            if(empty($alertas)) {
                $empleado->guardar();
                header('Location: /empleados?actualizado=1');
                exit;
            }
        }
        $router->render('empleados/actualizar', [
            'nombre'=> $_SESSION['nombre'],
            'empleado'=> $empleado,
            'alertas'=> $alertas
        ]);
    }

    public static function eliminar(){
        isAdmin();
        if($_SERVER['REQUEST_METHOD']  ==='POST'){
            csrfVerificar();
            $id = $_POST['id'] ?? '';
            if (!is_numeric($id)) {
                header('Location: /empleados');
                exit;
            }
            $empleado = Empleado::find($id);
            if ($empleado) {
                try {
                    $empleado->eliminar();
                    header('Location: /empleados?borrado=1');
                } catch (\Throwable $e) {
                    // Lo mas probable: el empleado ya tiene citas asociadas
                    // y la base de datos rechaza el borrado por la relacion
                    // (FK constraint). En vez de un error feo, se sugiere
                    // desactivarlo en su lugar (conserva el historial).
                    header('Location: /empleados?error=tiene_citas');
                }
            } else {
                header('Location: /empleados');
            }
            exit;
        }
    }
}
