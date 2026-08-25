<?php

namespace Model;

class Empleado extends ActiveRecord {
    protected static $tabla = 'empleados';
    protected static $columnasDB = ['id', 'nombre', 'apellido', 'especialidad', 'activo', 'horario_inicio', 'horario_fin', 'horario_virtual_inicio', 'horario_virtual_fin'];

    public $id;
    public $nombre;
    public $apellido;
    public $especialidad;
    public $activo;
    public $horario_inicio;
    public $horario_fin;
    public $horario_virtual_inicio;
    public $horario_virtual_fin;

    public function __construct($args = [])
    {
        $this->id = $args['id'] ?? null;
        $this->nombre = $args['nombre'] ?? '';
        $this->apellido = $args['apellido'] ?? '';
        $this->especialidad = $args['especialidad'] ?? '';
        // Por defecto activo, salvo que venga explicitamente en 0
        // (ej. al desmarcar el checkbox en el formulario de edicion).
        $this->activo = $args['activo'] ?? 1;
        // Horario PRESENCIAL del empleado.
        $this->horario_inicio = $args['horario_inicio'] ?? '10:00';
        $this->horario_fin = $args['horario_fin'] ?? '18:00';
        // Horario VIRTUAL del empleado (puede ser distinto del
        // presencial - ej. Presencial 11am-5pm, Virtual 6pm-8pm).
        $this->horario_virtual_inicio = $args['horario_virtual_inicio'] ?? '18:00';
        $this->horario_virtual_fin = $args['horario_virtual_fin'] ?? '20:00';
    }

    public function validar(){
        if(!$this->nombre){
            self::$alertas['error'][] = 'El Nombre es Obligatorio';
        }
        if(!$this->apellido){
            self::$alertas['error'][] = 'El Apellido es Obligatorio';
        }
        foreach (['horario_inicio' => 'presencial - inicio', 'horario_fin' => 'presencial - fin', 'horario_virtual_inicio' => 'virtual - inicio', 'horario_virtual_fin' => 'virtual - fin'] as $campo => $etiqueta) {
            if(!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $this->$campo ?? '')){
                self::$alertas['error'][] = "El horario {$etiqueta} no es válido";
            }
        }
        if(empty(self::$alertas)){
            if($this->horario_inicio >= $this->horario_fin){
                self::$alertas['error'][] = 'El horario presencial de inicio debe ser antes que el de fin';
            }
            if($this->horario_virtual_inicio >= $this->horario_virtual_fin){
                self::$alertas['error'][] = 'El horario virtual de inicio debe ser antes que el de fin';
            }
        }
        return self::$alertas;
    }

    // Devuelve solo los empleados activos, para el selector publico
    // que ve el cliente al agendar. Se usa consultarSQL directo (no
    // hay input de usuario involucrado, asi que no hace falta escapar).
    public static function activos() {
        return static::consultarSQL("SELECT * FROM empleados WHERE activo = 1 ORDER BY nombre ASC");
    }

    // Devuelve el rango de horario que le corresponde segun la
    // modalidad pedida ('presencial' o 'virtual').
    public function horarioParaModalidad(string $modalidad): array {
        if ($modalidad === 'virtual') {
            return [$this->horario_virtual_inicio, $this->horario_virtual_fin];
        }
        return [$this->horario_inicio, $this->horario_fin];
    }
}
