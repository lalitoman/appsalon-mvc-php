<?php

namespace Model;

class Pago extends ActiveRecord {
    protected static $tabla = 'pagos';
    protected static $columnasDB = ['id', 'citaId', 'mp_preference_id', 'mp_payment_id', 'monto', 'estado'];

    public $id;
    public $citaId;
    public $mp_preference_id;
    public $mp_payment_id;
    public $monto;
    public $estado;

    public function __construct($args = [])
    {
        $this->id = $args['id'] ?? null;
        $this->citaId = $args['citaId'] ?? '';
        $this->mp_preference_id = $args['mp_preference_id'] ?? null;
        $this->mp_payment_id = $args['mp_payment_id'] ?? null;
        $this->monto = $args['monto'] ?? 0;
        $this->estado = $args['estado'] ?? 'pendiente';
    }
}
