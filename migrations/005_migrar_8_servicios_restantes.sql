-- ============================================
-- MIGRACIÓN: 8 servicios restantes con precios personalizados
-- ============================================
-- Migración manual de los 8 clientes que no se migraron automáticamente

USE imaginatics_ruc;

-- ============================================
-- MOSTRAR ESTADO ANTES DE MIGRACIÓN
-- ============================================

SELECT '========== ANTES DE LA MIGRACIÓN ==========' as '';

SELECT
    sc.id as contrato_id,
    c.razon_social,
    sc.precio,
    sc.periodo_facturacion,
    cs.nombre as servicio_actual
FROM servicios_contratados sc
JOIN clientes c ON sc.cliente_id = c.id
JOIN catalogo_servicios cs ON sc.servicio_id = cs.id
WHERE sc.servicio_id IN (23, 24)
ORDER BY sc.precio DESC;

-- ============================================
-- MIGRACIONES INDIVIDUALES
-- ============================================

-- 1. CARLOS CHUQUILLANQUI RAUL EVERSON
-- S/ 10,000 anual → Plan Pro Max Anual (precio especial corporativo)
UPDATE servicios_contratados
SET
    servicio_id = 17,  -- Plan Pro Max Anual
    precio = 10000.00,
    notas = CONCAT(IFNULL(notas, ''), '\nPrecio corporativo especial. Migrado automáticamente.')
WHERE id = 1;

SELECT '✅ 1/8 - CARLOS CHUQUILLANQUI migrado a Plan Pro Max Anual (S/ 10,000)' as progreso;

-- 2. MADERERA C & G E.I.R.L.
-- S/ 1,600 anual → Plan Pro Max Anual (precio personalizado)
UPDATE servicios_contratados
SET
    servicio_id = 17,  -- Plan Pro Max Anual
    precio = 1600.00,
    notas = CONCAT(IFNULL(notas, ''), '\nPrecio personalizado. Migrado automáticamente.')
WHERE id = 48;

SELECT '✅ 2/8 - MADERERA C & G migrado a Plan Pro Max Anual (S/ 1,600)' as progreso;

-- 3. BARRETO TUNQUI JUANA MERCEDES
-- S/ 1,000 semestral → Plan Pro Max Semestral (precio con descuento)
UPDATE servicios_contratados
SET
    servicio_id = 16,  -- Plan Pro Max Semestral
    precio = 1000.00,
    notas = CONCAT(IFNULL(notas, ''), '\nPrecio con descuento. Migrado automáticamente.')
WHERE id = 2;

SELECT '✅ 3/8 - BARRETO TUNQUI migrado a Plan Pro Max Semestral (S/ 1,000)' as progreso;

-- 4. IMPORTACIONES ODALIA E.I.R.L.
-- S/ 590 anual → Plan Básico Anual (precio especial S/ 590, base S/ 770)
UPDATE servicios_contratados
SET
    servicio_id = 4,   -- Plan Básico Anual
    precio = 590.00,
    notas = CONCAT(IFNULL(notas, ''), '\nPrecio especial anual S/ 590. Migrado automáticamente.')
WHERE id = 42;

SELECT '✅ 4/8 - IMPORTACIONES ODALIA migrado a Plan Básico Anual (S/ 590)' as progreso;

-- 5. ROMERO AVENDAÑO FREUD ERICK
-- S/ 354 mensual → Plan Pro Max Mensual (precio doble, incluye extras)
UPDATE servicios_contratados
SET
    servicio_id = 14,  -- Plan Pro Max Mensual
    precio = 354.00,
    notas = CONCAT(IFNULL(notas, ''), '\nPrecio doble (base S/ 177). Incluye servicios adicionales. Migrado automáticamente.')
WHERE id = 38;

SELECT '✅ 5/8 - ROMERO AVENDAÑO FREUD migrado a Plan Pro Max Mensual (S/ 354)' as progreso;

-- 6. HUAPRI CAFETERIA Y BODEGA E.I.R.L.
-- S/ 236 mensual → Plan Premium Mensual (precio doble S/ 236, base S/ 118)
UPDATE servicios_contratados
SET
    servicio_id = 6,   -- Plan Premium Mensual
    precio = 236.00,
    notas = CONCAT(IFNULL(notas, ''), '\nPrecio doble (base S/ 118). Incluye servicios adicionales. Migrado automáticamente.')
WHERE id = 25;

SELECT '✅ 6/8 - HUAPRI CAFETERIA migrado a Plan Premium Mensual (S/ 236)' as progreso;

-- 7. MAURICIO RICRA JUANA
-- S/ 70 mensual → Plan Básico Mensual (descuento 9%)
UPDATE servicios_contratados
SET
    servicio_id = 1,   -- Plan Básico Mensual
    precio = 70.00,
    notas = CONCAT(IFNULL(notas, ''), '\nPrecio con descuento 9% (base S/ 77). Migrado automáticamente.')
WHERE id = 13;

SELECT '✅ 7/8 - MAURICIO RICRA migrado a Plan Básico Mensual (S/ 70)' as progreso;

-- 8. BERNUY RARAZ CARLOS
-- S/ 70 mensual → Plan Básico Mensual (descuento 9%)
UPDATE servicios_contratados
SET
    servicio_id = 1,   -- Plan Básico Mensual
    precio = 70.00,
    notas = CONCAT(IFNULL(notas, ''), '\nPrecio con descuento 9% (base S/ 77). Migrado automáticamente.')
WHERE id = 37;

SELECT '✅ 8/8 - BERNUY RARAZ migrado a Plan Básico Mensual (S/ 70)' as progreso;

-- ============================================
-- VERIFICACIÓN FINAL
-- ============================================

SELECT '========== DESPUÉS DE LA MIGRACIÓN ==========' as '';

-- Verificar que no quedan servicios migrados
SELECT
    COUNT(*) as servicios_aun_sin_migrar
FROM servicios_contratados
WHERE servicio_id IN (23, 24);

-- Mostrar los 8 servicios recién migrados
SELECT
    sc.id as contrato_id,
    c.razon_social,
    cs.nombre as servicio_real,
    sc.precio as precio_personalizado,
    cs.precio_base as precio_catalogo,
    sc.periodo_facturacion,
    CONCAT('S/ ', ROUND((sc.precio - cs.precio_base), 2)) as diferencia_precio
FROM servicios_contratados sc
JOIN clientes c ON sc.cliente_id = c.id
JOIN catalogo_servicios cs ON sc.servicio_id = cs.id
WHERE sc.id IN (1, 48, 2, 42, 38, 25, 13, 37)
ORDER BY sc.precio DESC;

-- Resumen final de todos los servicios
SELECT
    cs.nombre as servicio,
    COUNT(*) as cantidad_clientes,
    CONCAT('S/ ', MIN(sc.precio), ' - S/ ', MAX(sc.precio)) as rango_precios
FROM servicios_contratados sc
JOIN catalogo_servicios cs ON sc.servicio_id = cs.id
WHERE cs.activo = 1
GROUP BY cs.nombre
ORDER BY cantidad_clientes DESC;

SELECT '========== ✅ MIGRACIÓN COMPLETADA ==========' as '';
SELECT 'Todos los servicios placeholder han sido migrados a servicios reales.' as mensaje;
SELECT '48 clientes originales + 1 nuevo = 49 servicios activos en el sistema.' as estado_final;
