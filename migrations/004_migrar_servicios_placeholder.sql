-- ============================================
-- MIGRACIÓN: Actualizar servicios "Migrado" a servicios reales
-- ============================================
-- Este script actualiza los servicios placeholder a servicios reales del catálogo
-- basándose en el precio y periodo de facturación

USE imaginatics_ruc;

-- Mostrar estado actual
SELECT
    'ANTES DE LA MIGRACIÓN' as estado,
    COUNT(*) as total_servicios_migrados
FROM servicios_contratados
WHERE servicio_id IN (23, 24);

-- ============================================
-- MAPEO: Mensual
-- ============================================

-- S/ 77.00 mensual → Plan Básico Mensual (ID: 1)
UPDATE servicios_contratados
SET servicio_id = 1
WHERE servicio_id IN (23, 24)
AND periodo_facturacion = 'mensual'
AND precio = 77.00;

-- S/ 118.00 mensual → Plan Premium Mensual (ID: 6)
UPDATE servicios_contratados
SET servicio_id = 6
WHERE servicio_id IN (23, 24)
AND periodo_facturacion = 'mensual'
AND precio = 118.00;

-- S/ 150.00 mensual → Plan Pro Mensual (ID: 10)
UPDATE servicios_contratados
SET servicio_id = 10
WHERE servicio_id IN (23, 24)
AND periodo_facturacion = 'mensual'
AND precio = 150.00;

-- S/ 177.00 mensual → Plan Pro Max Mensual (ID: 14)
UPDATE servicios_contratados
SET servicio_id = 14
WHERE servicio_id IN (23, 24)
AND periodo_facturacion = 'mensual'
AND precio = 177.00;

-- ============================================
-- MAPEO: Trimestral
-- ============================================

-- S/ 220.00 trimestral → Plan Básico Trimestral (ID: 2)
UPDATE servicios_contratados
SET servicio_id = 2
WHERE servicio_id IN (23, 24)
AND periodo_facturacion = 'trimestral'
AND precio = 220.00;

-- S/ 231.00 trimestral → Plan Básico Trimestral (ID: 2) [precio alternativo]
UPDATE servicios_contratados
SET servicio_id = 2
WHERE servicio_id IN (23, 24)
AND periodo_facturacion = 'trimestral'
AND precio = 231.00;

-- S/ 354.00 trimestral → Plan Premium Trimestral (ID: 7)
UPDATE servicios_contratados
SET servicio_id = 7
WHERE servicio_id IN (23, 24)
AND periodo_facturacion = 'trimestral'
AND precio = 354.00;

-- S/ 450.00 trimestral → Plan Pro Trimestral (ID: 11)
UPDATE servicios_contratados
SET servicio_id = 11
WHERE servicio_id IN (23, 24)
AND periodo_facturacion = 'trimestral'
AND precio = 450.00;

-- S/ 531.00 trimestral → Plan Pro Max Trimestral (ID: 15)
UPDATE servicios_contratados
SET servicio_id = 15
WHERE servicio_id IN (23, 24)
AND periodo_facturacion = 'trimestral'
AND precio = 531.00;

-- ============================================
-- MAPEO: Semestral
-- ============================================

-- S/ 354.00 semestral → Plan Básico Semestral (ID: 3)
UPDATE servicios_contratados
SET servicio_id = 3
WHERE servicio_id IN (23, 24)
AND periodo_facturacion = 'semestral'
AND precio = 354.00;

-- S/ 590.00 semestral → Plan Premium Semestral (ID: 8)
UPDATE servicios_contratados
SET servicio_id = 8
WHERE servicio_id IN (23, 24)
AND periodo_facturacion = 'semestral'
AND precio = 590.00;

-- S/ 900.00 semestral → Plan Pro Semestral (ID: 12)
UPDATE servicios_contratados
SET servicio_id = 12
WHERE servicio_id IN (23, 24)
AND periodo_facturacion = 'semestral'
AND precio = 900.00;

-- S/ 1,062.00 semestral → Plan Pro Max Semestral (ID: 16)
UPDATE servicios_contratados
SET servicio_id = 16
WHERE servicio_id IN (23, 24)
AND periodo_facturacion = 'semestral'
AND precio = 1062.00;

-- ============================================
-- MAPEO: Anual
-- ============================================

-- S/ 770.00 anual → Plan Básico Anual (ID: 4)
UPDATE servicios_contratados
SET servicio_id = 4
WHERE servicio_id IN (23, 24)
AND periodo_facturacion = 'anual'
AND precio = 770.00;

-- S/ 924.00 anual → Plan Básico 2 Anual (ID: 5)
UPDATE servicios_contratados
SET servicio_id = 5
WHERE servicio_id IN (23, 24)
AND periodo_facturacion = 'anual'
AND precio = 924.00;

-- S/ 1,180.00 anual → Plan Premium Anual (ID: 9)
UPDATE servicios_contratados
SET servicio_id = 9
WHERE servicio_id IN (23, 24)
AND periodo_facturacion = 'anual'
AND precio = 1180.00;

-- S/ 1,416.00 anual → Plan Pro Anual (ID: 13)
UPDATE servicios_contratados
SET servicio_id = 13
WHERE servicio_id IN (23, 24)
AND periodo_facturacion = 'anual'
AND precio = 1416.00;

-- S/ 1,770.00 anual → Plan Pro Max Anual (ID: 17)
UPDATE servicios_contratados
SET servicio_id = 17
WHERE servicio_id IN (23, 24)
AND periodo_facturacion = 'anual'
AND precio = 1770.00;

-- ============================================
-- VERIFICACIÓN
-- ============================================

-- Mostrar resultado
SELECT
    'DESPUÉS DE LA MIGRACIÓN' as estado,
    COUNT(*) as servicios_aun_migrados
FROM servicios_contratados
WHERE servicio_id IN (23, 24);

-- Detalle de servicios que quedaron sin migrar (precios que no coinciden)
SELECT
    'SERVICIOS SIN MIGRAR' as tipo,
    sc.id,
    sc.precio,
    sc.periodo_facturacion,
    c.razon_social
FROM servicios_contratados sc
JOIN clientes c ON sc.cliente_id = c.id
WHERE sc.servicio_id IN (23, 24);

-- Resumen de migración por servicio
SELECT
    cs.nombre as servicio_real,
    COUNT(*) as cantidad_migrados
FROM servicios_contratados sc
JOIN catalogo_servicios cs ON sc.servicio_id = cs.id
WHERE sc.servicio_id NOT IN (23, 24)
GROUP BY cs.nombre
ORDER BY cantidad_migrados DESC;

-- ============================================
-- OPCIONAL: Desactivar servicios migrados completamente
-- ============================================
-- Descomentar estas líneas si todos los servicios fueron migrados exitosamente

-- UPDATE catalogo_servicios
-- SET activo = 0
-- WHERE id IN (23, 24);

-- SELECT 'Servicios placeholder desactivados completamente' as mensaje;
