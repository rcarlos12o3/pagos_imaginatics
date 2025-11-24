-- ============================================
-- MIGRACIÓN 010: Reglas de Periodicidad Centralizadas
-- Fecha: 2025-11-23
-- Descripción: Centraliza las reglas de días de anticipación
--              para OPs y recordatorios según periodicidad.
--              Elimina valores hardcodeados en el código.
-- ============================================

-- 1. Crear tabla de reglas por periodicidad
CREATE TABLE IF NOT EXISTS reglas_recordatorio_periodicidad (
    id INT AUTO_INCREMENT PRIMARY KEY,
    periodo_facturacion ENUM('mensual','trimestral','semestral','anual') UNIQUE NOT NULL,
    dias_anticipacion_op INT NOT NULL COMMENT 'Días antes del vencimiento para enviar Orden de Pago',
    dias_anticipacion_recordatorio INT DEFAULT 3 COMMENT 'Días después de OP para primer recordatorio',
    dias_urgente INT DEFAULT 3 COMMENT 'Días antes de vencer para marcar como urgente',
    dias_critico INT DEFAULT 1 COMMENT 'Días antes de vencer para marcar como crítico',
    max_dias_mora INT DEFAULT 30 COMMENT 'Máximo días de mora para seguir enviando recordatorios',
    descripcion VARCHAR(255) NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Insertar reglas por defecto
INSERT INTO reglas_recordatorio_periodicidad
    (periodo_facturacion, dias_anticipacion_op, dias_urgente, dias_critico, descripcion)
VALUES
    ('mensual', 4, 2, 1, 'Servicios mensuales: OP 4 días antes'),
    ('trimestral', 7, 3, 1, 'Servicios trimestrales: OP 7 días antes'),
    ('semestral', 15, 5, 2, 'Servicios semestrales: OP 15 días antes'),
    ('anual', 30, 7, 3, 'Servicios anuales: OP 30 días antes')
ON DUPLICATE KEY UPDATE
    dias_anticipacion_op = VALUES(dias_anticipacion_op),
    descripcion = VALUES(descripcion),
    fecha_actualizacion = CURRENT_TIMESTAMP;

-- 3. Actualizar vista de recordatorios pendientes para usar la tabla de reglas
DROP VIEW IF EXISTS v_recordatorios_pendientes_hoy;

CREATE VIEW v_recordatorios_pendientes_hoy AS
SELECT
    c.id,
    c.ruc,
    c.razon_social,
    c.whatsapp,
    sc.id as servicio_contratado_id,
    cs.nombre as servicio_nombre,
    sc.precio AS monto,
    sc.moneda,
    sc.fecha_vencimiento,
    sc.periodo_facturacion AS periodicidad,
    DATEDIFF(sc.fecha_vencimiento, CURDATE()) AS dias_restantes,
    r.dias_anticipacion_op,
    r.dias_urgente,
    r.dias_critico,
    -- Tipo de recordatorio basado en reglas
    CASE
        WHEN DATEDIFF(sc.fecha_vencimiento, CURDATE()) < 0 THEN 'vencido'
        WHEN DATEDIFF(sc.fecha_vencimiento, CURDATE()) <= r.dias_critico THEN 'critico'
        WHEN DATEDIFF(sc.fecha_vencimiento, CURDATE()) <= r.dias_urgente THEN 'urgente'
        ELSE 'preventivo'
    END as tipo_recordatorio,
    -- Última OP enviada para este servicio
    (SELECT MAX(ew.fecha_envio)
     FROM envios_whatsapp ew
     WHERE ew.cliente_id = c.id
     AND ew.servicio_contratado_id = sc.id
     AND ew.tipo_envio = 'orden_pago'
     AND ew.estado = 'enviado'
    ) as fecha_ultima_op,
    -- Recordatorios este mes
    COALESCE(
        (SELECT COUNT(*)
         FROM historial_recordatorios hr
         WHERE hr.cliente_id = c.id
         AND hr.servicio_id = sc.id
         AND hr.fecha_envio >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ), 0
    ) as recordatorios_este_mes,
    -- Último recordatorio
    (SELECT MAX(hr.fecha_envio)
     FROM historial_recordatorios hr
     WHERE hr.cliente_id = c.id
     AND hr.servicio_id = sc.id
    ) as ultimo_recordatorio
FROM clientes c
INNER JOIN servicios_contratados sc ON c.id = sc.cliente_id
INNER JOIN catalogo_servicios cs ON sc.servicio_id = cs.id
INNER JOIN reglas_recordatorio_periodicidad r ON sc.periodo_facturacion = r.periodo_facturacion
WHERE sc.estado IN ('activo', 'vencido')
AND c.activo = 1
-- Usar días de anticipación de la tabla de reglas
AND DATEDIFF(sc.fecha_vencimiento, CURDATE()) BETWEEN -r.max_dias_mora AND r.dias_anticipacion_op
-- Solo si ya se envió una OP para este servicio en este periodo
AND EXISTS (
    SELECT 1 FROM envios_whatsapp ew
    WHERE ew.cliente_id = c.id
    AND ew.servicio_contratado_id = sc.id
    AND ew.tipo_envio = 'orden_pago'
    AND ew.estado = 'enviado'
    AND ew.fecha_envio >= (
        CASE sc.periodo_facturacion
            WHEN 'mensual' THEN DATE_SUB(sc.fecha_vencimiento, INTERVAL 1 MONTH)
            WHEN 'trimestral' THEN DATE_SUB(sc.fecha_vencimiento, INTERVAL 3 MONTH)
            WHEN 'semestral' THEN DATE_SUB(sc.fecha_vencimiento, INTERVAL 6 MONTH)
            WHEN 'anual' THEN DATE_SUB(sc.fecha_vencimiento, INTERVAL 1 YEAR)
            ELSE DATE_SUB(sc.fecha_vencimiento, INTERVAL 1 MONTH)
        END
    )
)
-- Excluir si ya se envió recordatorio en los últimos 3 días para este servicio
AND NOT EXISTS (
    SELECT 1 FROM historial_recordatorios hr
    WHERE hr.cliente_id = c.id
    AND hr.servicio_id = sc.id
    AND hr.fecha_envio >= DATE_SUB(CURDATE(), INTERVAL 3 DAY)
    AND hr.estado_envio = 'enviado'
)
ORDER BY sc.fecha_vencimiento ASC;

-- 4. Log de migración
INSERT INTO logs_sistema (nivel, modulo, mensaje, datos_adicionales)
VALUES ('info', 'migracion', 'Migración 010: Reglas de periodicidad centralizadas',
        JSON_OBJECT(
            'version', '010',
            'cambios', JSON_ARRAY(
                'Tabla reglas_recordatorio_periodicidad creada',
                'Vista v_recordatorios_pendientes_hoy actualizada para usar reglas',
                'Eliminados valores hardcodeados'
            ),
            'reglas', JSON_OBJECT(
                'mensual', 4,
                'trimestral', 7,
                'semestral', 15,
                'anual', 30
            ),
            'fecha', NOW()
        ));

-- ============================================
-- FIN DE MIGRACIÓN
-- ============================================
