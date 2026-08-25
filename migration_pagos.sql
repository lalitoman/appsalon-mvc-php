-- Migración: agrega soporte de pagos/anticipos con Mercado Pago.
--
-- Correr UNA VEZ sobre tu base de datos ya existente:
--   mysql -u root -p appsalon < migration_pagos.sql
--
-- Como con las demas migraciones, es seguro correrla aunque ya tengas
-- citas/usuarios - no borra ni modifica lo que ya existe.

-- Estado de la cita: mientras no exista este sistema de pagos, todas
-- las citas se consideran "confirmada" automaticamente (comportamiento
-- de siempre). Si activas Mercado Pago en el .env, las citas nuevas
-- nacen "pendiente_pago" y pasan a "confirmada" solo cuando Mercado
-- Pago avisa que el anticipo si se pago.
SET @columna_existe = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'citas'
      AND COLUMN_NAME = 'estado'
);

SET @sql = IF(@columna_existe = 0,
    "ALTER TABLE citas ADD COLUMN estado VARCHAR(20) NOT NULL DEFAULT 'confirmada'",
    'SELECT "La columna estado ya existe, no se hizo nada"'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Tabla de pagos: un registro por cada intento de cobro de anticipo.
-- Se guarda el id de preferencia (se crea al mandar al cliente a pagar)
-- y despues el id de pago real de Mercado Pago (se llena cuando el
-- webhook confirma el cobro).
CREATE TABLE IF NOT EXISTS pagos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    citaId INT NOT NULL,
    mp_preference_id VARCHAR(100) NULL,
    mp_payment_id VARCHAR(100) NULL,
    monto DECIMAL(10,2) NOT NULL,
    estado VARCHAR(20) NOT NULL DEFAULT 'pendiente',
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (citaId) REFERENCES citas(id)
);
