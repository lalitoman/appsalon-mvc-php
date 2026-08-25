<?php

namespace Model;

class Servicio extends ActiveRecord {
    // Base de datos 
    protected static $tabla = 'servicios';
    protected static $columnasDB = ['id', 'nombre', 'precio', 'anticipo'];

    public $id;
    public $nombre;
    public $precio;
    public $anticipo;

    public function __construct($args = [])
    {
        $this->id = $args['id'] ?? null;
        $this->nombre = $args['nombre'] ?? '';
        $this->precio = $args['precio'] ?? '';
        // 0 = usa el minimo global (MP_ANTICIPO_MINIMO en el .env) en
        // vez de un monto especifico de este servicio.
        $this->anticipo = $args['anticipo'] ?? 0;
    }

    public function validar(){
        if(!$this->nombre){
            self::$alertas['error'][] = 'El Nombre del servicio es Obligatorio';
        } 
        if(!$this->precio){
            self::$alertas['error'][] = 'El Precio del servicio es Obligatorio';
        }  
        if(!is_numeric($this->precio)){
            self::$alertas['error'][] = 'El Precio no es Válido';
        }  
        if($this->anticipo !== '' && $this->anticipo !== null && !is_numeric($this->anticipo)){
            self::$alertas['error'][] = 'El Anticipo no es Válido';
        }
        return self::$alertas;  
    }
}
