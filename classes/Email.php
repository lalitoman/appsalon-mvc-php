<?php

namespace Classes;

use PHPMailer\PHPMailer\PHPMailer;

class Email{
    public $email;
    public $nombre;
    public $token;

    public function __construct($email, $nombre, $token){
        $this->email = $email;
        $this->nombre = $nombre;
        $this->token = $token;
    }

    // Configuracion SMTP compartida por los 3 correos que manda esta clase.
    // Antes cada metodo repetia este bloque y dejaba que PHPMailer
    // "adivinara" el cifrado segun el puerto. Aqui se fuerza explicitamente:
    //   - puerto 465  -> SSL/TLS implicito (SMTPS)
    //   - cualquier otro puerto (587, 2525 de Mailtrap, etc.) -> STARTTLS
    // Esto evita fallos silenciosos de conexion con proveedores como
    // Gmail que son estrictos con el tipo de cifrado, y sigue funcionando
    // igual con Mailtrap si en algun momento regresas a el.
    private function crearMailer(): PHPMailer {
        $mail = new PHPMailer(true); // true = lanzar excepciones en errores
        $mail->isSMTP();
        $mail->Host = $_ENV['EMAIL_HOST'];
        $mail->SMTPAuth = true;
        $mail->Port = (int) $_ENV['EMAIL_PORT'];
        $mail->Username = $_ENV['EMAIL_USER'];
        $mail->Password = $_ENV['EMAIL_PASS'];

        if ($mail->Port === 465) {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }

        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';

        return $mail;
    }

    public function enviarConfirmacion(){
        //Crear el objeto e email
        $mail = $this->crearMailer();

        $mail->setFrom('cuentas@appsalon.com', 'AppSalon');
        $mail->addAddress($this->email, $this->nombre); //Add a recipient
        $mail->Subject = 'Confrima tu Cuenta';

        $contenido = "<html>";
        $contenido .= "<p><strong>Hola ". $this->nombre ."</strong>, Has creado una cuenta en App salon, solo debes confrimala presionando el siguiente enlace</p>";
        $contenido .= "<p>Preciona aquí: <a href='" . $_ENV['APP_URL'] . "/confirmar-cuenta?token=". $this->token . "'>Confirmar Cuenta</a> </p>";
        $contenido .= "<p>Si tu no solicitaste la creacion de esta cuenta ignora este mensaje</p>";
        $contenido .= "</html>";

        $mail->Body = $contenido;

        //Enviar el Email
        $mail->send();
    }


    // Envia un correo de confirmacion cuando el cliente agenda una cita.
    // $servicios es un array de ['nombre' => ..., 'precio' => ...]
    // $nombreEmpleado es opcional (compatibilidad con llamadas antiguas).
    public function enviarConfirmacionCita($fecha, $hora, $servicios, $total, $nombreEmpleado = ''){
        $mail = $this->crearMailer();

        $mail->setFrom('citas@appsalon.com', 'AppSalon');
        $mail->addAddress($this->email, $this->nombre);
        $mail->Subject = 'Confirmación de tu cita en AppSalon';

        $fechaFormateada = date('d/m/Y', strtotime($fecha));
        $horaFormateada = substr($hora, 0, 5);

        $contenido = "<html>";
        $contenido .= "<p><strong>Hola " . htmlspecialchars($this->nombre) . "</strong>, tu cita fue agendada correctamente.</p>";
        $contenido .= "<p><strong>Fecha:</strong> " . htmlspecialchars($fechaFormateada) . "</p>";
        $contenido .= "<p><strong>Hora:</strong> " . htmlspecialchars($horaFormateada) . "</p>";
        if ($nombreEmpleado) {
            $contenido .= "<p><strong>Atiende:</strong> " . htmlspecialchars($nombreEmpleado) . "</p>";
        }
        $contenido .= "<p><strong>Servicios:</strong></p><ul>";
        foreach ($servicios as $servicio) {
            $contenido .= "<li>" . htmlspecialchars($servicio['nombre']) . " - $" . htmlspecialchars($servicio['precio']) . "</li>";
        }
        $contenido .= "</ul>";
        $contenido .= "<p><strong>Total: $" . htmlspecialchars($total) . "</strong></p>";
        $contenido .= "<p>Si necesitas cancelar, puedes hacerlo desde tu cuenta en la sección 'Mis Citas'.</p>";
        $contenido .= "</html>";

        $mail->Body = $contenido;

        $mail->send();
    }

    public function enviarInstrucciones(){
      //Crear el objeto e email para recuperar contraseña
        $mail = $this->crearMailer();

        $mail->setFrom('cuentas@appsalon.com', 'AppSalon');
        $mail->addAddress($this->email, $this->nombre); //Add a recipient
        $mail->Subject = 'Restblce tu password';

        $contenido = "<html>";
        $contenido .= "<p><strong>Hola ". $this->nombre ."</strong> Has solicitado reestablecer tu password, sigue el siguiente enlace para hacerlo.</p>";
        $contenido .= "<p>Preciona aquí: <a href='" . $_ENV['APP_URL'] . "/recuperar?token=". $this->token . "'>Reestablecer Password</a> </p>";
        $contenido .= "<p>Si tu no solicitaste la creacion de esta cuenta ignora este mensaje</p>";
        $contenido .= "</html>";

        $mail->Body = $contenido;

        //Enviar el Email
        $mail->send();
    }
}
