<?php

namespace Model;

class AdminCita extends ActiveRecord{
    protected static $tabla = 'citaservicios';
    protected static $columnasDB = ['id', 'hora', 'estado', 'cliente', 'email',
    'telefono', 'servicio', 'precio', 'empleado', 'montoAnticipo', 'estadoPago'];
     
    public $id;
    public $hora;
    public $estado;
    public $cliente;
    public $email;
    public $telefono;
    public $servicio;
    public $precio;
    public $empleado;
    public $montoAnticipo;
    public $estadoPago;

    public function __construct()
    {
        $this->id = $args['id'] ?? null;
        $this->hora = $args['hora'] ?? '';
        $this->estado = $args['estado'] ?? 'confirmada';
        $this->cliente = $args['cliente'] ?? '';
        $this->email = $args['email'] ?? '';
        $this->telefono = $args['telefono'] ?? '';
        $this->servicio = $args['servicio'] ?? '';
        $this->precio = $args['precio'] ?? '';
        $this->empleado = $args['empleado'] ?? '';
        $this->montoAnticipo = $args['montoAnticipo'] ?? null;
        $this->estadoPago = $args['estadoPago'] ?? null;
    }
}
