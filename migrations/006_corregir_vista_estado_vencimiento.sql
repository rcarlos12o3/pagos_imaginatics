-- ============================================
-- CORRECCIÓN: Vista v_servicios_cliente
-- ============================================
-- Corregir el campo estado_vencimiento para que respete el estado real del servicio

USE imaginatics_ruc;

-- Eliminar vista existente
DROP VIEW IF EXISTS v_servicios_cliente;

-- Recrear vista con lógica corregida
CREATE VIEW v_servicios_cliente AS
SELECT
    c.id AS cliente_id,
    c.ruc,
    c.razon_social,
    c.whatsapp,
    c.email,
    cs.id AS servicio_id,
    cs.nombre AS servicio_nombre,
    cs.categoria,
    sc.id AS contrato_id,
    sc.precio,
    sc.moneda,
    sc.periodo_facturacion,
    sc.fecha_inicio,
    sc.fecha_vencimiento,
    sc.fecha_ultima_factura,
    sc.estado,
    sc.auto_renovacion,
    sc.configuracion,
    DATEDIFF(sc.fecha_vencimiento, CURDATE()) AS dias_restantes,
    -- LÓGICA CORREGIDA: Respetar estado del servicio primero
    CASE
        -- Si el servicio está suspendido, mostrar SUSPENDIDO
        WHEN sc.estado = 'suspendido' THEN 'SUSPENDIDO'
        -- Si el servicio está cancelado, mostrar CANCELADO
        WHEN sc.estado = 'cancelado' THEN 'CANCELADO'
        -- Si el servicio está vencido (por estado), mostrar VENCIDO
        WHEN sc.estado = 'vencido' THEN 'VENCIDO'
        -- Si el servicio está activo, evaluar por días
        WHEN sc.estado = 'activo' THEN
            CASE
                WHEN DATEDIFF(sc.fecha_vencimiento, CURDATE()) < 0 THEN 'VENCIDO'
                WHEN DATEDIFF(sc.fecha_vencimiento, CURDATE()) = 0 THEN 'VENCE_HOY'
                WHEN DATEDIFF(sc.fecha_vencimiento, CURDATE()) <= 3 THEN 'POR_VENCER'
                ELSE 'AL_DIA'
            END
        -- Cualquier otro estado
        ELSE 'INACTIVO'
    END AS estado_vencimiento
FROM clientes c
INNER JOIN servicios_contratados sc ON c.id = sc.cliente_id
INNER JOIN catalogo_servicios cs ON sc.servicio_id = cs.id
WHERE c.activo = TRUE
ORDER BY c.razon_social, sc.fecha_vencimiento;

-- Verificar el cambio
SELECT
    'VERIFICACIÓN - Estado del servicio ID 48' as '';

SELECT
    contrato_id,
    razon_social,
    estado,
    estado_vencimiento,
    dias_restantes,
    fecha_vencimiento
FROM v_servicios_cliente
WHERE contrato_id = 48;

-- Mostrar todos los estados posibles
SELECT
    'RESUMEN POR ESTADO' as '';

SELECT
    estado_vencimiento,
    COUNT(*) as cantidad
FROM v_servicios_cliente
GROUP BY estado_vencimiento
ORDER BY cantidad DESC;

SELECT '✅ Vista corregida exitosamente' as mensaje;
