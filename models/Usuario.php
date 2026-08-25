<?php

namespace Model;

class Usuario extends ActiveRecord {
    // Base de datos
    protected static $tabla = 'usuarios';
    protected static $columnasDB = [
        'id', 'nombre', 'apellido', 'email', 'password', 'telefono',
        'admin', 'confirmar', 'token'
    ];

    public $id;
    public $nombre;
    public $apellido;
    public $email;
    public $password;
    public $telefono;
    public $admin;
    public $confirmar;
    public $token;

    public function __construct($args = []) {
        $this->id = $args['id'] ?? null;
        $this->nombre = $args['nombre'] ?? '';
        $this->apellido = $args['apellido'] ?? '';
        $this->email = $args['email'] ?? '';
        $this->password = $args['password'] ?? '';
        $this->telefono = $args['telefono'] ?? '';
        $this->admin = $args['admin'] ?? '0';
        $this->confirmar = $args['confirmar'] ?? '0';
        $this->token = $args['token'] ?? '';
    }

    // Validación para nueva cuenta
    public function validarNuevaCuenta() {
        if (!$this->nombre) {
            self::$alertas['error'][] = 'El nombre es obligatorio';
        }
        if (!$this->apellido) {
            self::$alertas['error'][] = 'El apellido es obligatorio';
        }
        if (!$this->email) {
            self::$alertas['error'][] = 'El email es obligatorio';
        }
        if (!$this->password) {
            self::$alertas['error'][] = 'El password es obligatorio';
        }
        if ($this->password && strlen($this->password) < 6) {
            self::$alertas['error'][] = 'El password debe contener al menos 6 caracteres';
        }

        return self::$alertas;
    }
 public function validarLogin() {
        if(!$this->email) {
            self::$alertas['error'][] = 'El email es Obligatorio';
        }
        if(!$this->password) {
            self::$alertas['error'][] = 'El Password es Obligatorio';
        }

        return self::$alertas;
    }
public function validarEmail(){
     if(!$this->email) {
            self::$alertas['error'][] = 'El email es Obligatorio';
        }
        return self::$alertas;
}

public function validarPassword(){
    if(!$this->password){
        self::$alertas['error'][] = 'El Password es Obligatorio';
    }
    if(strlen($this->password)<6){
        self::$alertas['error'][] = 'El Password debe tener al menos 6 carcteres';
    }
    return self::$alertas;
}
    // Verifica si el usuario ya existe en la base de datos
    public function existeUsuario() {
        $emailSanitizado = self::$db->escape_string($this->email);
        $query = "SELECT * FROM " . self::$tabla . " WHERE email = '{$emailSanitizado}' LIMIT 1";
        $resultado = self::$db->query($query);

        if ($resultado && $resultado->num_rows > 0) {
            self::$alertas['error'][] = 'El usuario ya está registrado';
        }

        return $resultado;
    }

    // Hashea la contraseña con BCRYPT
    public function hashPassword() {
        $this->password = password_hash($this->password, PASSWORD_BCRYPT);
    }

    // Genera un token único
    // Antes: uniqid() se basa en la hora del servidor y es adivinable,
    // esto era riesgoso porque el token se usa para confirmar cuenta y
    // resetear password. random_bytes() es criptograficamente seguro.
    public function crearToken() {
        $this->token = bin2hex(random_bytes(32));
    }
    public function comprobarPasswordAndVerificado($password){
        $resultado = password_verify($password, $this->password);
        if(!$resultado || !$this->confirmar){
           self::$alertas['error'][]='Password Inocorrecto o tu cuenta no ha sido conformada';
        }else{
            return true;
        }
    }

    // Validacion para actualizar datos de "Mi Perfil" (nombre, apellido,
    // telefono). No incluye email ni password - esos tienen su propio
    // flujo (el email no se permite cambiar aqui, y el password usa
    // validarNuevoPassword()).
    public function validarPerfil() {
        if (!$this->nombre) {
            self::$alertas['error'][] = 'El nombre es obligatorio';
        }
        if (!$this->apellido) {
            self::$alertas['error'][] = 'El apellido es obligatorio';
        }
        return self::$alertas;
    }

    // Validacion para el formulario de cambio de password dentro de
    // "Mi Perfil" (distinto del flujo de "olvide mi password"): aqui
    // se pide la password actual para confirmar identidad.
    public function validarNuevoPassword($passwordActual, $passwordNuevo, $passwordConfirmar) {
        if (!$passwordActual || !$passwordNuevo || !$passwordConfirmar) {
            self::$alertas['error'][] = 'Todos los campos son obligatorios';
            return self::$alertas;
        }
        if (!password_verify($passwordActual, $this->password)) {
            self::$alertas['error'][] = 'Tu password actual no es correcta';
            return self::$alertas;
        }
        if (strlen($passwordNuevo) < 6) {
            self::$alertas['error'][] = 'El nuevo password debe tener al menos 6 caracteres';
            return self::$alertas;
        }
        if ($passwordNuevo !== $passwordConfirmar) {
            self::$alertas['error'][] = 'El nuevo password y su confirmación no coinciden';
            return self::$alertas;
        }
        return self::$alertas;
    }
}