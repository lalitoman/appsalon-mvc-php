<?php
namespace Model;

#[\AllowDynamicProperties]
class ActiveRecord {
    // Base de datos
    protected static $db;
    protected static $tabla = '';
    protected static $columnasDB = [];

    // Alertas y mensajes
    protected static $alertas = [];

    // Conectar a la base de datos
    public static function setDB($database) {
        self::$db = $database;
    }

    // Escapa un valor para usarlo en una consulta SQL cruda (self::$db->escape_string).
    // Uso recomendado SOLO como defensa extra tras validar el input;
    // preferir siempre consultas preparadas cuando sea posible.
    public static function escape($valor) {
        return self::$db->escape_string($valor);
    }

    // Definir una alerta
    public static function setAlerta($tipo, $mensaje) {
        static::$alertas[$tipo][] = $mensaje;
    }

    // Obtener alertas
    public static function getAlertas() {
        return static::$alertas;
    }

    // Validación base (vacía)
    public function validar() {
        static::$alertas = [];
        return static::$alertas;
    }

    // Consultar SQL y devolver objetos
    public static function consultarSQL($query) {
        $resultado = self::$db->query($query);
        $array = [];

        while($registro = $resultado->fetch_assoc()) {
            $array[] = static::crearObjeto($registro);
        }

        $resultado->free();
        return $array;
    }

    // Igual que consultarSQL, pero devuelve arrays asociativos planos en
    // vez de instancias del modelo. Se usa para consultas con columnas
    // calculadas (COUNT, SUM, alias que no existen como propiedad de
    // ningun modelo) - crearObjeto() ahi no tendria donde mapearlas.
    public static function consultarSQLPlano($query) {
        $resultado = self::$db->query($query);
        $array = [];

        while($registro = $resultado->fetch_assoc()) {
            $array[] = $registro;
        }

        $resultado->free();
        return $array;
    }

    // Crear objeto en memoria
    protected static function crearObjeto($registro) {
        $objeto = new static;

        foreach($registro as $key => $value) {
            if(property_exists($objeto, $key)) {
                $objeto->$key = $value;
            }
        }

        return $objeto;
    }

    // Obtener atributos del modelo según columnas reales (excluyendo id)
    public function atributos() {
        $atributos = [];
        foreach(static::$columnasDB as $columna) {
            if($columna === 'id') continue;
            // Solo agrega si la propiedad existe en el objeto y no es null
            if(property_exists($this, $columna)) {
                $atributos[$columna] = $this->$columna ?? '';
            }
        }
        return $atributos;
    }

    // Sanitizar datos para seguridad
    public function sanitizarAtributos() {
        $atributos = $this->atributos();
        $sanitizado = [];

        foreach($atributos as $key => $value) {
            $sanitizado[$key] = is_null($value) ? '' : self::$db->escape_string($value);
        }

        return $sanitizado;
    }

    // Sincronizar datos desde un array
    public function sincronizar($args = []) {
        foreach($args as $key => $value) {
            if(property_exists($this, $key) && !is_null($value)) {
                $this->$key = $value;
            }
        }
    }

    // Guardar (crear o actualizar)
    public function guardar() {
        if(!is_null($this->id)) {
            return $this->actualizar();
        } else {
            return $this->crear();
        }
    }

    // Obtener todos los registros
    public static function all() {
        $query = "SELECT * FROM " . static::$tabla;
        return self::consultarSQL($query);
    }

    // Buscar por ID
    public static function find($id) {
        $id = self::$db->escape_string($id);
        $query = "SELECT * FROM " . static::$tabla . " WHERE id = '{$id}' LIMIT 1";
        $resultado = self::consultarSQL($query);
        return array_shift($resultado);
    }

    // Obtener registros con límite
    public static function get($limite) {
        $limite = (int) $limite;
        $query = "SELECT * FROM " . static::$tabla . " LIMIT {$limite}";
        return self::consultarSQL($query);
    }

     public static function where($columna, $valor) {
         $query = "SELECT * FROM " . static::$tabla  ." WHERE {$columna} = '{$valor}'";
        $resultado = self::consultarSQL($query);
        return array_shift( $resultado ) ;
    }

    //Consulta Plana de SQL (utilizar cuando los métodos no son sufucientes)
        public static function SQL($query) {
        $resultado = self::consultarSQL($query);
        return $resultado;
    }
    // Crear nuevo registro
    public function crear() {
        $atributos = $this->sanitizarAtributos();

        // Solo columnas con valor (evitar columnas sin datos)
        $columnas = array_keys($atributos);
        $valores = array_values($atributos);

        if(empty($columnas)) {
            throw new \Exception("No hay datos para insertar");
        }

        $query = "INSERT INTO " . static::$tabla . " (";
        $query .= join(', ', $columnas);
        $query .= ") VALUES ('";
        $query .= join("', '", $valores);
        $query .= "')";

       // return json_encode(['query' => $query]);

        // Resultado de la cunsulta 
        $resultado = self::$db->query($query);

        if(!$resultado) {
            throw new \Exception("Error al crear el registro: " . self::$db->error);
        }

        return [
            'resultado' => $resultado,
            'id' => self::$db->insert_id
        ];
    }

    // Actualizar registro existente
    public function actualizar() {
        $atributos = $this->sanitizarAtributos();
        $valores = [];

        foreach($atributos as $key => $value) {
            $valores[] = "{$key}='{$value}'";
        }

        $id = self::$db->escape_string($this->id);

        $query = "UPDATE " . static::$tabla . " SET ";
        $query .= join(', ', $valores);
        $query .= " WHERE id = '{$id}' LIMIT 1";

        $resultado = self::$db->query($query);

        if(!$resultado) {
            throw new \Exception("Error al actualizar el registro: " . self::$db->error);
        }

        return $resultado;
    }

    // Eliminar registro
    public function eliminar() {
        $id = self::$db->escape_string($this->id);

        $query = "DELETE FROM " . static::$tabla . " WHERE id = '{$id}' LIMIT 1";

        $resultado = self::$db->query($query);

        if(!$resultado) {
            throw new \Exception("Error al eliminar el registro: " . self::$db->error);
        }

        return $resultado;
    }
}