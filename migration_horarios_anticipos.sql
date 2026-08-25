-- Migración: horario propio por empleado, anticipo variable por
-- servicio, y soporte para la regla de "no cancelar/reagendar con
-- menos de 24 horas de anticipación".
--
-- Correr UNA VEZ sobre tu base de datos ya existente:
--   Copia y pega este contenido completo en tu editor SQL (TablePlus,
--   phpMyAdmin, etc.) y ejecútalo.
--
-- NOTA: MySQL 8.0.29+ soporta "ADD COLUMN IF NOT EXISTS". Si tu
-- versión es más vieja y te da error de sintaxis con eso, quita el
-- "IF NOT EXISTS" de cada línea (es seguro correrlo una sola vez).

-- --- Horario propio por empleado ---
-- Por defecto 10:00-18:00 (el horario general que ya tenías), para no
-- cambiar nada visualmente hasta que el admin edite un empleado.
ALTER TABLE empleados
    ADD COLUMN IF NOT EXISTS horario_inicio TIME NOT NULL DEFAULT '10:00:00',
    ADD COLUMN IF NOT EXISTS horario_fin TIME NOT NULL DEFAULT '18:00:00';

-- --- Anticipo variable por servicio ---
-- 0 = "usa el mínimo global" (ver MP_ANTICIPO_MINIMO en el .env).
ALTER TABLE servicios
    ADD COLUMN IF NOT EXISTS anticipo DECIMAL(10,2) NOT NULL DEFAULT 0;
