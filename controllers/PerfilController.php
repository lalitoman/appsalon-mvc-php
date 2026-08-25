<?php

namespace Controllers;

use Model\Usuario;
use MVC\Router;

class PerfilController{

    public static function index(Router $router){
        isAuth();

        $usuario = Usuario::find($_SESSION['id']);
        $alertas = [];
        $alertasPassword = [];

        if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'datos'){
            csrfVerificar();

            $usuario->sincronizar($_POST);
            // El email y el password NUNCA se tocan desde este formulario
            // (sincronizar() solo asigna lo que venga en $_POST, y el
            // formulario de datos no manda esos dos campos - pero se
            // fuerza aqui tambien por profundidad de defensa).
            $usuario->email = Usuario::find($_SESSION['id'])->email;

            $alertas = $usuario->validarPerfil();

            if(empty($alertas)){
                $usuario->guardar();
                $_SESSION['nombre'] = $usuario->nombre . ' ' . $usuario->apellido;
                header('Location: /mi-perfil?actualizado=1');
                exit;
            }
        }

        if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'password'){
            csrfVerificar();

            $passwordActual = $_POST['password_actual'] ?? '';
            $passwordNuevo = $_POST['password_nuevo'] ?? '';
            $passwordConfirmar = $_POST['password_confirmar'] ?? '';

            $alertasPassword = $usuario->validarNuevoPassword($passwordActual, $passwordNuevo, $passwordConfirmar);

            if(empty($alertasPassword)){
                $usuario->password = $passwordNuevo;
                $usuario->hashPassword();
                $usuario->guardar();
                header('Location: /mi-perfil?password_actualizado=1');
                exit;
            }
        }

        $router->render('perfil/index', [
            'nombre' => $_SESSION['nombre'],
            'usuario' => $usuario,
            'alertas' => $alertas,
            'alertasPassword' => $alertasPassword,
        ]);
    }
}
