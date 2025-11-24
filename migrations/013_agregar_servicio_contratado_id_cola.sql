-- ============================================
-- MIGRACIÓN 009: Agregar servicio_contratado_id a cola_envios
-- Fecha: 2025-11-23
-- Descripción: Vincula cada envío con su servicio específico
--              para poder validar recordatorios después de OP
-- ============================================

-- Agregar campo servicio_contratado_id a cola_envios
ALTER TABLE cola_envios
ADD COLUMN servicio_contratado_id INT NULL AFTER cliente_id,
ADD INDEX idx_servicio_contratado (servicio_contratado_id),
ADD CONSTRAINT fk_cola_servicio FOREIGN KEY (servicio_contratado_id)
    REFERENCES servicios_contratados(id) ON DELETE SET NULL;

-- Log de migración
INSERT INTO logs_sistema (nivel, modulo, mensaje, datos_adicionales)
VALUES ('info', 'migracion', 'Migración 009: Campo servicio_contratado_id agregado a cola_envios',
        JSON_OBJECT(
            'version', '009',
            'cambio', 'Ahora cada trabajo en cola se vincula con un servicio específico',
            'fecha', NOW()
        ));

-- ============================================
-- FIN DE MIGRACIÓN
-- ============================================
