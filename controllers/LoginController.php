<?php

namespace Controllers;

use Classes\Email;
use Model\Usuario;
use MVC\Router;

class LoginController{
    public static function login(Router $router) {
    $alertas = [];

    if($_SERVER['REQUEST_METHOD'] === 'POST'){
      // Rate limiting simple contra fuerza bruta: max 5 intentos fallidos,
      // luego 60s de espera. No es tan robusto como algo con Redis/IP,
      // pero frena scripts basicos.
      csrfVerificar();
      $_SESSION['login_intentos'] = $_SESSION['login_intentos'] ?? 0;
      $_SESSION['login_ultimo_intento'] = $_SESSION['login_ultimo_intento'] ?? 0;

      if ($_SESSION['login_intentos'] >= 5 && (time() - $_SESSION['login_ultimo_intento']) < 60) {
          Usuario::setAlerta('error', 'Demasiados intentos fallidos. Espera un minuto e intenta de nuevo.');
          $alertas = Usuario::getAlertas();
          $router->render('auth/login', ['alertas' => $alertas]);
          return;
      }
      if ((time() - $_SESSION['login_ultimo_intento']) >= 60) {
          $_SESSION['login_intentos'] = 0;
      }

      $auth = new Usuario($_POST);
      $alertas = $auth->validarLogin();

      if(empty($alertas)){
          // Comprobar que exita el usuario
          $usuario = Usuario::where('email', $auth->email);

          if($usuario){
            // verificar el paswword
           if($usuario->comprobarPasswordAndVerificado($auth->password)) {
            // Autenticar el usiario
            $_SESSION['login_intentos'] = 0; // reset tras login exitoso
            $_SESSION['id'] = $usuario->id;
                        $_SESSION['nombre'] = $usuario->nombre . " " . $usuario->apellido;
                        $_SESSION['email'] = $usuario->email;
                        $_SESSION['login'] = true;

                        // CRITICO: siempre limpiar la bandera admin antes de
                        // decidir si se vuelve a poner. Si no se hace esto,
                        // un usuario que se loguea como admin y LUEGO se
                        // loguea como cliente normal (en el mismo navegador,
                        // sin pasar por /logout) se queda con permisos de
                        // admin heredados de la sesion anterior.
                        unset($_SESSION['admin']);

                        // Redireccionamiento
                        if($usuario->admin === "1") {
                            $_SESSION['admin'] = $usuario->admin;
                            header('Location: /admin');
                        } else {
                            header('Location: /cita');
                        }
                        exit;
  
          } else {
              $_SESSION['login_intentos']++;
              $_SESSION['login_ultimo_intento'] = time();
          }
              
          }else{
            $_SESSION['login_intentos']++;
            $_SESSION['login_ultimo_intento'] = time();
            Usuario::setAlerta('error', 'Credenciales incorrectas');
          }

      }
      
    }
    $alertas = Usuario::getAlertas();

      $router->render('auth/login', [
            'alertas' => $alertas
        ]);
    }

    
      public static function logout() {
        //echo "Desde Logout";
        session_start();
        $_SESSION = [];
        header('Location: /');
        exit;
    }
      public static function olvide(Router $router) {
      $alertas =[];
      
      if($_SERVER['REQUEST_METHOD'] === 'POST'){
        csrfVerificar();
        $auth = new Usuario($_POST);
        $alertas = $auth-> validarEmail();
        
        if(empty($alertas)){
            $usuario = Usuario::where('email',$auth->email);

            if($usuario && $usuario->confirmar ==="1"){
              // Generar un token
              $usuario->crearToken();
              $usuario->guardar();

              // enviar email
              // Try/catch: al activar excepciones en PHPMailer (para poder
              // detectar fallos de conexion SMTP con Gmail), un error de
              // envio ya no debe tumbar la pagina con un error fatal.
              try {
                  $email = new Email($usuario->email, $usuario->nombre, $usuario->token);
                  $email->enviarInstrucciones();
              } catch (\Exception $e) {
                  error_log('No se pudo enviar el correo de recuperacion: ' . $e->getMessage());
              }

              //alerta de exito
              Usuario::setAlerta('exito', 'Revisa tu email');
            }else{
              Usuario::setAlerta('error', 'El usuario no exite o no esta confrimado');
             
            }
        }
      }
      $alertas = Usuario::getAlertas();



      $router->render('auth/olvide-password',[
        'alertas' => $alertas
        ]);
    }

      public static function recuperar(Router $router) {
        $alertas = [];
        $error = false;
        
        $token = s($_GET['token']);

        // buscar ususario por su token
        $usuario = Usuario::where('token', $token);

        if(empty($usuario)){
          Usuario::setAlerta('error', 'Token no Válido');
          $errror = true;
        }
        if($_SERVER['REQUEST_METHOD'] === 'POST'){
          csrfVerificar();
          //LEER EL NUVEVO PASSWORD Y GUARDARLO 
          $password = new Usuario($_POST);
          $alertas= $password-> validarPassword();

          if(empty($alertas)){
            $usuario->password = null;
            //debuguear($password);
            $usuario->password = $password->password;
            $usuario->hashPassword();
            $usuario->token = null;

            $resultado = $usuario->guardar();
            if($resultado){
              header('Location: /');
              exit;
            }

            //debuguear($usuario);
          }
        }

        //debuguear($password);

        $alertas = Usuario::getAlertas();
        $router->render('auth/recuperar-password',[
            'alertas'=> $alertas,
            'error'=>$error
        ]);
    }
      public static function crear(Router $router) {
        $usuario = new Usuario;

        // Alertas vacaias 
        $alertas = [];

        if($_SERVER ['REQUEST_METHOD'] === 'POST'){
          csrfVerificar();

          $usuario->sincronizar($_POST);
          $alertas = $usuario->validarNuevaCuenta();

          //Revisar que alertas este vacio 

          if(empty($alertas)){
            // verificar que el usuasriio no este registrado
            $resultado = $usuario->existeUsuario();
            
            if($resultado->num_rows){
              $alertas = Usuario::getAlertas();
            }else{
              // Hashear el paswoord
              $usuario->hashPassword();

              // Gnerar un token único
              $usuario->crearToken();

              // Enviar el amail
              // Try/catch: la cuenta debe crearse aunque el correo falle
              // (ej. credenciales SMTP mal puestas); con excepciones
              // activadas en PHPMailer, sin este try/catch un fallo de
              // envio tumbaria la pagina antes de llegar a guardar().
              try {
                  $email = new Email($usuario->email, $usuario->nombre, $usuario->token);
                  $email ->enviarConfirmacion();
              } catch (\Exception $e) {
                  error_log('No se pudo enviar el correo de confirmacion de cuenta: ' . $e->getMessage());
              }
              
             // Crear el usuario
              $resultado = $usuario->guardar();
              // debuguear($usuario);
              if($resultado) {
                  header('Location: /mensaje');
                  exit;
                    }

              
            }
          }
        
        }

        $router->render('auth/crear-cuenta', [
            'usuario' => $usuario,
            'alertas' => $alertas
        ]);
      
    }

    public static function mensaje(Router $router){

        $router->render('auth/mensaje');
    }
    public static function confirmar(Router $router){
        $alertas =[];
        $token = s($_GET['token']);
        $usuario = Usuario::where('token', $token);

        if(empty ($usuario)){
          //mostrar mensaje de error
          Usuario::setAlerta('error', 'Token no Válido');
        }else{
          // modificar a usuario confirmado
         
          $usuario->confirmar = "1";
          $usuario->token = null;
          $usuario->guardar();
          Usuario::setAlerta('exito', 'Cuenta Comprobada Correctamente');

          
        }
        
        $alertas = Usuario::getAlertas();
        $router->render('auth/confirmar-cuenta', [
        'alertas' => $alertas
      ]);

    }
}