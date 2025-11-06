-- ============================================
-- MIGRACIÓN 003: Migrar Datos Existentes
-- Fecha: 2025-11-05
-- Descripción: Migrar servicios existentes del modelo monolítico al multi-servicio
-- ============================================

USE imaginatics_ruc;

-- ============================================
-- PASO 1: Crear servicio genérico para migración
-- ============================================

-- Insertar un servicio genérico que represente los contratos actuales
INSERT INTO catalogo_servicios (nombre, descripcion, categoria, precio_base, moneda, periodos_disponibles, orden_visualizacion, activo, configuracion_default)
VALUES ('Servicio Existente (Migrado)', 'Servicio migrado del sistema anterior - NO USAR PARA NUEVOS CONTRATOS', 'software', 0.00, 'PEN', '["mensual","trimestral","semestral","anual"]', 999, FALSE,
    JSON_OBJECT('migrado', true, 'origen', 'sistema_anterior', 'fecha_migracion', NOW()));

SET @servicio_migracion_id = LAST_INSERT_ID();

SELECT 'Servicio genérico de migración creado' as paso_1,
       @servicio_migracion_id as servicio_id;

-- ============================================
-- PASO 2: Migrar servicios existentes
-- ============================================

-- Migrar todos los clientes activos que tienen monto y fecha_vencimiento
INSERT INTO servicios_contratados (
    cliente_id,
    servicio_id,
    precio,
    moneda,
    periodo_facturacion,
    fecha_inicio,
    fecha_vencimiento,
    fecha_ultima_factura,
    fecha_proximo_pago,
    estado,
    auto_renovacion,
    configuracion,
    notas,
    usuario_creacion
)
SELECT
    id as cliente_id,
    @servicio_migracion_id as servicio_id,
    monto as precio,
    'PEN' as moneda,
    COALESCE(tipo_servicio, 'anual') as periodo_facturacion,

    -- Calcular fecha de inicio restando el periodo desde la fecha de vencimiento
    CASE COALESCE(tipo_servicio, 'anual')
        WHEN 'mensual' THEN DATE_SUB(fecha_vencimiento, INTERVAL 1 MONTH)
        WHEN 'trimestral' THEN DATE_SUB(fecha_vencimiento, INTERVAL 3 MONTH)
        WHEN 'semestral' THEN DATE_SUB(fecha_vencimiento, INTERVAL 6 MONTH)
        ELSE DATE_SUB(fecha_vencimiento, INTERVAL 1 YEAR)
    END as fecha_inicio,

    fecha_vencimiento,

    -- Última factura: buscar en historial_pagos
    (SELECT MAX(fecha_pago) FROM historial_pagos hp WHERE hp.cliente_id = clientes.id) as fecha_ultima_factura,

    -- Próximo pago es la fecha de vencimiento
    fecha_vencimiento as fecha_proximo_pago,

    -- Estado según días restantes
    CASE
        WHEN DATEDIFF(fecha_vencimiento, CURDATE()) < 0 THEN 'vencido'
        ELSE 'activo'
    END as estado,

    TRUE as auto_renovacion,

    -- Configuración con datos migrados
    JSON_OBJECT(
        'migrado', true,
        'fecha_migracion', NOW(),
        'datos_originales', JSON_OBJECT(
            'monto_original', monto,
            'fecha_vencimiento_original', fecha_vencimiento,
            'tipo_servicio_original', tipo_servicio
        )
    ) as configuracion,

    CONCAT('Servicio migrado del sistema anterior. Fecha migración: ', NOW()) as notas,
    'Sistema - Migración Automática' as usuario_creacion

FROM clientes
WHERE activo = TRUE
AND monto IS NOT NULL
AND fecha_vencimiento IS NOT NULL;

-- Obtener estadísticas de migración
SET @total_migrados = (SELECT COUNT(*) FROM servicios_contratados WHERE servicio_id = @servicio_migracion_id);

SELECT 'Servicios migrados exitosamente' as paso_2,
       @total_migrados as total_servicios_migrados;

-- ============================================
-- PASO 3: Verificar integridad de migración
-- ============================================

-- Verificar que todos los clientes activos tienen al menos un servicio
SELECT
    (SELECT COUNT(*) FROM clientes WHERE activo = TRUE) as total_clientes_activos,
    (SELECT COUNT(DISTINCT cliente_id) FROM servicios_contratados) as clientes_con_servicios,
    (SELECT COUNT(*) FROM clientes c
     WHERE c.activo = TRUE
     AND NOT EXISTS (SELECT 1 FROM servicios_contratados sc WHERE sc.cliente_id = c.id)) as clientes_sin_servicios;

-- Verificar montos migrados
SELECT
    'Verificación de montos' as verificacion,
    SUM(c.monto) as suma_montos_originales,
    SUM(sc.precio) as suma_precios_migrados,
    CASE
        WHEN ABS(SUM(c.monto) - SUM(sc.precio)) < 0.01 THEN 'OK'
        ELSE 'ERROR'
    END as estado_verificacion
FROM clientes c
INNER JOIN servicios_contratados sc ON c.id = sc.cliente_id
WHERE c.activo = TRUE
AND sc.servicio_id = @servicio_migracion_id;

-- ============================================
-- PASO 4: Resumen de migración por periodo
-- ============================================

SELECT
    periodo_facturacion,
    COUNT(*) as cantidad_servicios,
    MIN(precio) as precio_minimo,
    MAX(precio) as precio_maximo,
    AVG(precio) as precio_promedio,
    SUM(precio) as total_mensual_estimado
FROM servicios_contratados
WHERE servicio_id = @servicio_migracion_id
GROUP BY periodo_facturacion
ORDER BY
    CASE periodo_facturacion
        WHEN 'mensual' THEN 1
        WHEN 'trimestral' THEN 2
        WHEN 'semestral' THEN 3
        WHEN 'anual' THEN 4
    END;

-- ============================================
-- PASO 5: Resumen de servicios por estado
-- ============================================

SELECT
    estado,
    COUNT(*) as cantidad,
    SUM(precio) as monto_total
FROM servicios_contratados
WHERE servicio_id = @servicio_migracion_id
GROUP BY estado;

-- ============================================
-- PASO 6: Advertencias y validaciones
-- ============================================

-- Advertencia: Clientes sin servicios migrados
SELECT
    'ADVERTENCIA: Clientes activos sin servicios migrados' as alerta,
    c.id,
    c.ruc,
    c.razon_social,
    c.monto,
    c.fecha_vencimiento
FROM clientes c
WHERE c.activo = TRUE
AND NOT EXISTS (SELECT 1 FROM servicios_contratados sc WHERE sc.cliente_id = c.id)
AND (c.monto IS NULL OR c.fecha_vencimiento IS NULL);

-- ============================================
-- PASO 7: Registrar en logs
-- ============================================

INSERT INTO logs_sistema (nivel, modulo, mensaje, datos_adicionales)
VALUES ('info', 'migracion', 'Migración 003: Datos existentes migrados a modelo multi-servicio',
    JSON_OBJECT(
        'version', '003',
        'fecha', NOW(),
        'servicio_migracion_id', @servicio_migracion_id,
        'total_servicios_migrados', @total_migrados,
        'clientes_activos', (SELECT COUNT(*) FROM clientes WHERE activo = TRUE),
        'clientes_con_servicios', (SELECT COUNT(DISTINCT cliente_id) FROM servicios_contratados)
    ));

-- ============================================
-- RESULTADO FINAL
-- ============================================

SELECT
    '✓ Migración de datos completada exitosamente' as resultado,
    @total_migrados as servicios_migrados,
    (SELECT COUNT(*) FROM clientes WHERE activo = TRUE) as clientes_activos,
    (SELECT COUNT(DISTINCT cliente_id) FROM servicios_contratados) as clientes_con_servicios,
    CASE
        WHEN (SELECT COUNT(*) FROM clientes WHERE activo = TRUE) =
             (SELECT COUNT(DISTINCT cliente_id) FROM servicios_contratados)
        THEN 'OK - Todos los clientes tienen servicios'
        ELSE 'REVISAR - Algunos clientes sin servicios'
    END as estado_final;

-- ============================================
-- NOTAS IMPORTANTES
-- ============================================

/*
IMPORTANTE:
- Los campos monto, fecha_vencimiento y tipo_servicio de la tabla clientes
  NO se eliminan automáticamente en esta migración
- Se mantienen para referencia y validación
- Se eliminarán en una migración posterior una vez validado todo
- El servicio de migración está marcado como inactivo (activo = FALSE)
  para que no aparezca en listados de servicios disponibles para nuevos contratos
*/
