<?php

namespace Controllers;

use Model\Configuracion;
use MVC\Router;

class ConfiguracionController{
    public static function index(Router $router){
        isAdmin();

        $configuracion = Configuracion::obtener();
        $alertas = [];

        if($_SERVER['REQUEST_METHOD'] === 'POST'){
            csrfVerificar();

            $configuracion->sincronizar($_POST);
            $configuracion->id = 1; // nunca permitir que se pierda / cambie el id fijo

            $alertas = $configuracion->validar();

            if(empty($alertas)){
                $configuracion->guardar();
                header('Location: /admin/configuracion?actualizado=1');
                exit;
            }
        }

        $router->render('admin/configuracion', [
            'nombre' => $_SESSION['nombre'],
            'configuracion' => $configuracion,
            'alertas' => $alertas,
        ]);
    }
}
