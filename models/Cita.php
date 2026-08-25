<?php

namespace Model;

class Cita extends ActiveRecord{
    // base de datos

    protected static $tabla = 'citas';
    protected static $columnasDB = ['id', 'fecha', 'hora', 'usuarioId', 'empleadoId', 'estado', 'modalidad'];

    public $id;
    public $fecha;
    public $hora;
    public $usuarioId;
    public $empleadoId;
    public $estado;
    public $modalidad;

    public function __construct($args = [])
    {
        $this ->id = $args ['id'] ?? null;
        $this ->fecha = $args['fecha'] ?? '';
        $this ->hora = $args['hora'] ?? '';
        $this ->usuarioId = $args['usuarioId'] ??'';
        $this ->empleadoId = $args['empleadoId'] ?? null;
        // Por defecto una cita nace confirmada (comportamiento de
        // siempre); solo se pone 'pendiente_pago' explicitamente cuando
        // Mercado Pago esta activo (ver APIcontroller::guardar()).
        $this ->estado = $args['estado'] ?? 'confirmada';
        // 'presencial' o 'virtual'.
        $this ->modalidad = $args['modalidad'] ?? 'presencial';
    }

    // Borra una cita y TODO lo que depende de ella (citaservicios,
    // pagos) en el orden correcto para no chocar con las llaves
    // foraneas. Usar esto en vez de $cita->eliminar() directo cuando la
    // cita ya pudo haber generado registros en esas tablas (ej. al
    // cancelar un intento de pago fallido, o al liberar una cita
    // abandonada).
    public static function eliminarConDependencias(int $citaId): void {
        self::$db->query("DELETE FROM citaservicios WHERE citaId = {$citaId}");
        self::$db->query("DELETE FROM pagos WHERE citaId = {$citaId}");
        self::$db->query("DELETE FROM citas WHERE id = {$citaId}");
    }

    // Borra citas "pendiente_pago" abandonadas (el cliente empezo a
    // pagar el anticipo y nunca termino) con mas de 30 minutos de
    // antiguedad, para liberar esos horarios. Se basa en pagos.creado_en
    // (no se agrego una columna de fecha propia a citas para esto).
    //
    // No es un cron job real - se llama de forma "oportunista" cada vez
    // que alguien consulta horarios o intenta agendar, para no requerir
    // configurar una tarea programada en el servidor. Es barato (una
    // consulta) y suficiente para mantener los horarios correctos en la
    // practica. Para producción con mucho trafico, un cron job real
    // corriendo cada pocos minutos seria mas robusto.
    public static function liberarPendientesAbandonadas(): void {
        try {
            $citasAbandonadas = self::consultarSQLPlano("
                SELECT citas.id
                FROM citas
                INNER JOIN pagos ON pagos.citaId = citas.id
                WHERE citas.estado = 'pendiente_pago'
                  AND pagos.creado_en < (NOW() - INTERVAL 30 MINUTE)
            ");

            foreach ($citasAbandonadas as $fila) {
                self::eliminarConDependencias((int) $fila['id']);
            }
        } catch (\Throwable $e) {
            // No debe tumbar nunca el flujo normal (agendar cita, ver
            // horarios) por un fallo aqui - en el peor caso, simplemente
            // no se libera el horario abandonado esta vez.
            error_log('No se pudo liberar citas pendientes abandonadas: ' . $e->getMessage());
        }
    }

    // Devuelve true si a esta cita le faltan menos de 24 horas (o ya
    // pasó) - se usa para bloquear cancelar/reagendar de ultimo momento.
    public function faltanMenosDe24Horas(): bool {
        $fechaHoraCita = strtotime($this->fecha . ' ' . $this->hora);
        if ($fechaHoraCita === false) {
            return true; // por seguridad, si no se puede interpretar la fecha, se bloquea
        }
        return $fechaHoraCita < strtotime('+24 hours');
    }
}