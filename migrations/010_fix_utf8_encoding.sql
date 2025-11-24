-- ============================================
-- MIGRACIÓN 010: Corregir Codificación UTF-8 en Datos Corruptos
-- Fecha: 2025-11-19
-- Descripción: Convertir datos que se guardaron con latin1 en vez de utf8
-- IMPORTANTE: Esta migración corrige los datos, NO los elimina
-- ============================================

USE imaginatics_ruc;

-- ============================================
-- PASO 1: Corregir tabla catalogo_servicios
-- ============================================

-- Los datos están codificados como latin1 pero deben ser utf8mb4
-- Convertir: FacturaciÃ³n → Facturación

UPDATE catalogo_servicios
SET nombre = CONVERT(CAST(CONVERT(nombre USING latin1) AS BINARY) USING utf8mb4)
WHERE nombre LIKE '%Ã%' OR nombre LIKE '%Ã%' OR nombre LIKE '%Ã©%';

UPDATE catalogo_servicios
SET descripcion = CONVERT(CAST(CONVERT(descripcion USING latin1) AS BINARY) USING utf8mb4)
WHERE descripcion LIKE '%Ã%' OR descripcion LIKE '%Ã%' OR descripcion LIKE '%Ã©%';

-- ============================================
-- PASO 2: Corregir otras tablas si es necesario
-- ============================================

-- Verificar si hay clientes con problemas (poco probable pero por si acaso)
UPDATE clientes
SET razon_social = CONVERT(CAST(CONVERT(razon_social USING latin1) AS BINARY) USING utf8mb4)
WHERE razon_social LIKE '%Ã%';

UPDATE clientes
SET direccion = CONVERT(CAST(CONVERT(direccion USING latin1) AS BINARY) USING utf8mb4)
WHERE direccion IS NOT NULL AND direccion LIKE '%Ã%';

-- ============================================
-- VERIFICACIÓN
-- ============================================

-- Mostrar algunos servicios corregidos
SELECT '=== SERVICIOS CORREGIDOS ===' as resultado;
SELECT id, nombre FROM catalogo_servicios LIMIT 5;

-- Contar cuántos registros se corrigieron
SELECT
    'Total servicios en catálogo' as metrica,
    COUNT(*) as cantidad
FROM catalogo_servicios
UNION ALL
SELECT
    'Servicios con nombres corregidos',
    COUNT(*)
FROM catalogo_servicios
WHERE nombre LIKE '%Factura%' OR nombre LIKE '%Electr%';
