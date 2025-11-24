-- ============================================
-- MIGRACIÓN 008: Validar OP antes de Recordatorios
-- Fecha: 2025-11-23
-- Descripción: Los recordatorios solo se envían si ya se envió
--              una Orden de Pago para ese periodo de facturación
-- ============================================

-- Actualizar vista de recordatorios pendientes
-- Ahora solo incluye servicios que YA recibieron una Orden de Pago
CREATE OR REPLACE VIEW v_recordatorios_pendientes_hoy AS
SELECT
    c.id,
    c.ruc,
    c.razon_social,
    c.whatsapp,
    sc.id as servicio_contratado_id,
    cs.nombre as servicio_nombre,
    sc.precio as monto,
    sc.moneda,
    sc.fecha_vencimiento,
    sc.periodo_facturacion as periodicidad,
    DATEDIFF(sc.fecha_vencimiento, CURDATE()) as dias_restantes,
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
    COALESCE(
        (SELECT MAX(fecha_envio)
         FROM historial_recordatorios hr
         WHERE hr.cliente_id = c.id
         AND hr.servicio_id = sc.id
        ), NULL
    ) as ultimo_recordatorio
FROM clientes c
INNER JOIN servicios_contratados sc ON c.id = sc.cliente_id
INNER JOIN catalogo_servicios cs ON sc.servicio_id = cs.id
WHERE sc.estado IN ('activo', 'vencido')
AND c.activo = 1
AND (
    -- Próximo a vencer (7 días o menos)
    DATEDIFF(sc.fecha_vencimiento, CURDATE()) BETWEEN 0 AND 7
    OR
    -- Ya vencido (hasta 30 días de atraso)
    DATEDIFF(sc.fecha_vencimiento, CURDATE()) BETWEEN -30 AND -1
)
-- *** VALIDACIÓN CRÍTICA: Solo si ya se envió una OP para este servicio en este periodo ***
AND EXISTS (
    SELECT 1 FROM envios_whatsapp ew
    WHERE ew.cliente_id = c.id
    AND ew.servicio_contratado_id = sc.id
    AND ew.tipo_envio = 'orden_pago'
    AND ew.estado = 'enviado'
    -- La OP debe haberse enviado en el periodo actual (desde el inicio del periodo hasta hoy)
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

-- Log de migración
INSERT INTO logs_sistema (nivel, modulo, mensaje, datos_adicionales)
VALUES ('info', 'migracion', 'Migración 008: Validación de OP antes de recordatorios implementada',
        JSON_OBJECT(
            'version', '008',
            'cambio', 'Los recordatorios ahora solo se envían si ya existe una OP enviada para ese periodo',
            'fecha', NOW()
        ));

-- ============================================
-- FIN DE MIGRACIÓN
-- ============================================
