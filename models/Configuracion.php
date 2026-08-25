<?php

namespace Model;

// Configuracion global del negocio (white-label): nombre, color,
// terminologia. Es una sola fila (id=1) en la tabla `configuracion`.
class Configuracion extends ActiveRecord {
    protected static $tabla = 'configuracion';
    protected static $columnasDB = [
        'id', 'nombre_negocio', 'color_primario',
        'etiqueta_empleado_singular', 'etiqueta_empleado_plural', 'etiqueta_servicios'
    ];

    public $id;
    public $nombre_negocio;
    public $color_primario;
    public $etiqueta_empleado_singular;
    public $etiqueta_empleado_plural;
    public $etiqueta_servicios;

    // Cache en memoria para no repetir la consulta si obtener() se
    // llama varias veces en el mismo request (ej. layout.php + una vista).
    private static $instancia = null;

    public function __construct($args = [])
    {
        $this->id = $args['id'] ?? 1;
        $this->nombre_negocio = $args['nombre_negocio'] ?? 'AppSalon';
        $this->color_primario = $args['color_primario'] ?? '#0da6f3';
        $this->etiqueta_empleado_singular = $args['etiqueta_empleado_singular'] ?? 'Barbero / Estilista';
        $this->etiqueta_empleado_plural = $args['etiqueta_empleado_plural'] ?? 'Empleados';
        $this->etiqueta_servicios = $args['etiqueta_servicios'] ?? 'Servicios';
    }

    // Trae la fila de configuracion (o valores por defecto si la
    // migracion todavia no se ha corrido / la tabla esta vacia, para
    // que el sitio nunca se rompa por esto).
    public static function obtener(): self {
        if (self::$instancia !== null) {
            return self::$instancia;
        }

        try {
            $resultado = static::find(1);
            self::$instancia = $resultado ?: new self();
        } catch (\Throwable $e) {
            // La tabla probablemente no existe todavia (migracion no
            // corrida) - se usan los valores por defecto del constructor.
            self::$instancia = new self();
        }

        return self::$instancia;
    }

    public function validar(){
        if(!$this->nombre_negocio){
            self::$alertas['error'][] = 'El nombre del negocio es obligatorio';
        }
        if(!preg_match('/^#[0-9a-fA-F]{6}$/', $this->color_primario ?? '')){
            self::$alertas['error'][] = 'El color debe ser un código hexadecimal válido (ej. #0da6f3)';
        }
        if(!$this->etiqueta_empleado_singular){
            self::$alertas['error'][] = 'La etiqueta de empleado (singular) es obligatoria';
        }
        if(!$this->etiqueta_empleado_plural){
            self::$alertas['error'][] = 'La etiqueta de empleado (plural) es obligatoria';
        }
        if(!$this->etiqueta_servicios){
            self::$alertas['error'][] = 'La etiqueta de servicios es obligatoria';
        }
        return self::$alertas;
    }
}
