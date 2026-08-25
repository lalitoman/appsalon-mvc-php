<?php

namespace Model;

// Modelo de solo lectura para el historial de citas de UN cliente
// (usado por CitaController::misCitas). Sigue el mismo patron que
// AdminCita: se llena via consulta SQL cruda (AdminCita::SQL / self::SQL)
// en vez de mapear directo a una tabla.
class MisCita extends ActiveRecord {
    protected static $tabla = 'citaservicios';
    protected static $columnasDB = ['id', 'fecha', 'hora', 'servicio', 'precio', 'empleado', 'estado', 'montoAnticipo', 'estadoPago'];

    public $id;
    public $fecha;
    public $hora;
    public $servicio;
    public $precio;
    public $empleado;
    public $estado;
    public $montoAnticipo;
    public $estadoPago;

    public function __construct($args = [])
    {
        $this->id = $args['id'] ?? null;
        $this->fecha = $args['fecha'] ?? '';
        $this->hora = $args['hora'] ?? '';
        $this->servicio = $args['servicio'] ?? '';
        $this->precio = $args['precio'] ?? '';
        $this->empleado = $args['empleado'] ?? '';
        $this->estado = $args['estado'] ?? 'confirmada';
        $this->montoAnticipo = $args['montoAnticipo'] ?? null;
        $this->estadoPago = $args['estadoPago'] ?? null;
    }
}
