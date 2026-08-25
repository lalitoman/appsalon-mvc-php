<?php

namespace Controllers;

use Model\Servicio;
use MVC\Router;

class ServiciosController{
    public static function index(Router $router){
       // session_start();
        isAdmin();

        $servicios = Servicio::all();

        $router->render('servicios/index', [
            'nombre'=> $_SESSION['nombre'],
            'servicios'=>$servicios
        ]);
    }

    public static function crear(Router $router){
        // session_start();
        isAdmin();
        $servicio = new Servicio;
        $alertas = [];

        if($_SERVER['REQUEST_METHOD']  ==='POST'){
            csrfVerificar();
            $servicio->sincronizar($_POST);
            $alertas = $servicio->validar();
            if(empty($alertas)){
                $servicio->guardar();
                header('Location: /servicios?creado=1');
                exit;
            }
        }
        $router->render('servicios/crear', [
            'nombre'=> $_SESSION['nombre'],
            'servicio'=> $servicio,
            'alertas'=> $alertas
        ]);
    }

    public static function actualizar(Router $router){
           // session_start();
            isAdmin();

        if(!is_numeric($_GET['id'] ?? '')) {
            header('Location: /404');
            exit;
        }
        $servicio = Servicio::find(($_GET['id']));
        $alertas = [];
        if($_SERVER['REQUEST_METHOD']  ==='POST'){
            csrfVerificar();
            $servicio->sincronizar($_POST);

            $alertas = $servicio->validar();

            if(empty($alertas)) {
                $servicio->guardar();
                header('Location: /servicios?actualizado=1');
                exit;
            }
            
        }
        $router->render('servicios/actualizar', [
            'nombre'=> $_SESSION['nombre'],
            'servicio'=> $servicio,
            'alertas'=> $alertas
        ]);
    }

    public static function eliminar(){
       //session_start();
        isAdmin();
        if($_SERVER['REQUEST_METHOD']  ==='POST'){
            csrfVerificar();
            $id = $_POST['id'] ?? '';
            if (!is_numeric($id)) {
                header('Location: /servicios');
                exit;
            }
            $servicio = Servicio::find($id);
            if ($servicio) {
                $servicio->eliminar();
            }
            header('Location: /servicios?borrado=1');
            exit;
        }
    
    }
}