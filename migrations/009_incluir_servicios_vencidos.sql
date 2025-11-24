-- ============================================
-- MIGRACIÓN 009: Incluir Servicios Vencidos y Suspendidos en la Vista
-- Fecha: 2025-11-19
-- Descripción: Modificar vista para mostrar servicios activos, vencidos y suspendidos
-- (Solo excluir los cancelados)
-- SEGURO: Solo recrea la vista, NO toca los datos
-- ============================================

USE imaginatics_ruc;

-- Eliminar vista existente y recrear con lógica mejorada
DROP VIEW IF EXISTS v_resumen_financiero_cliente;

CREATE VIEW v_resumen_financiero_cliente AS
SELECT
    c.id AS cliente_id,
    c.ruc,
    c.razon_social,
    c.whatsapp,
    c.email,

    -- Conteo de servicios (excluir solo cancelados)
    COUNT(DISTINCT CASE WHEN sc.estado != 'cancelado' THEN sc.id END) AS total_servicios,
    SUM(CASE WHEN sc.estado = 'activo' THEN 1 ELSE 0 END) AS servicios_activos,
    SUM(CASE WHEN sc.estado = 'suspendido' THEN 1 ELSE 0 END) AS servicios_suspendidos,

    -- Montos por moneda (incluir activos, vencidos y suspendidos)
    SUM(CASE
        WHEN sc.estado IN ('activo', 'vencido', 'suspendido') AND sc.moneda = 'PEN'
        THEN sc.precio
        ELSE 0
    END) AS monto_pen,

    SUM(CASE
        WHEN sc.estado IN ('activo', 'vencido', 'suspendido') AND sc.moneda = 'USD'
        THEN sc.precio
        ELSE 0
    END) AS monto_usd,

    -- Monto total de servicios (todas las monedas, incluir activos, vencidos y suspendidos)
    SUM(CASE
        WHEN sc.estado IN ('activo', 'vencido', 'suspendido')
        THEN sc.precio
        ELSE 0
    END) AS monto_servicios_activos,

    -- Próximo vencimiento y su periodo (incluir activos, vencidos y suspendidos)
    MIN(CASE
        WHEN sc.estado IN ('activo', 'vencido', 'suspendido')
        THEN sc.fecha_vencimiento
    END) AS proximo_vencimiento,

    (
        SELECT sc2.periodo_facturacion
        FROM servicios_contratados sc2
        WHERE sc2.cliente_id = c.id
        AND sc2.estado IN ('activo', 'vencido', 'suspendido')
        ORDER BY sc2.fecha_vencimiento ASC
        LIMIT 1
    ) AS periodo_proximo_vencimiento,

    -- Facturas pendientes
    (
        SELECT COUNT(*)
        FROM facturas_electronicas f
        WHERE f.cliente_id = c.id
        AND f.estado IN ('emitida', 'vencida')
    ) AS facturas_pendientes,

    -- Saldo pendiente
    (
        SELECT SUM(f.total)
        FROM facturas_electronicas f
        WHERE f.cliente_id = c.id
        AND f.estado IN ('emitida', 'vencida')
    ) AS saldo_pendiente,

    -- Pagado mes actual
    (
        SELECT SUM(f.total)
        FROM facturas_electronicas f
        WHERE f.cliente_id = c.id
        AND f.estado = 'pagada'
        AND YEAR(f.fecha_pago) = YEAR(CURDATE())
        AND MONTH(f.fecha_pago) = MONTH(CURDATE())
    ) AS pagado_mes_actual

FROM clientes c
LEFT JOIN servicios_contratados sc ON c.id = sc.cliente_id
WHERE c.activo = TRUE
GROUP BY c.id, c.ruc, c.razon_social, c.whatsapp, c.email;

-- Verificar que la vista se creó correctamente
SELECT 'Vista v_resumen_financiero_cliente actualizada exitosamente' AS resultado;

-- Verificar el caso específico del cliente 79 (Miami Real)
SELECT
    cliente_id,
    razon_social,
    total_servicios,
    servicios_activos,
    monto_pen,
    proximo_vencimiento
FROM v_resumen_financiero_cliente
WHERE cliente_id = 79;
