-- ============================================
-- MIGRACIÓN: Agregar soporte multi-servicio a historial_pagos
-- ============================================
-- Agregar columna servicios_pagados para tracking de múltiples servicios por pago

USE imaginatics_ruc;

-- Verificar estado actual
SELECT '========== ANTES DE LA MIGRACIÓN ==========' as '';

SELECT
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'imaginatics_ruc'
AND TABLE_NAME = 'historial_pagos'
ORDER BY ORDINAL_POSITION;

-- ============================================
-- AGREGAR COLUMNA servicios_pagados
-- ============================================

-- Agregar columna JSON para almacenar IDs de servicios pagados
ALTER TABLE historial_pagos
ADD COLUMN servicios_pagados JSON NULL COMMENT 'Array de IDs de servicios_contratados incluidos en este pago'
AFTER observaciones;

-- Actualizar pagos existentes con NULL (se pueden actualizar manualmente si es necesario)
SELECT '✅ Columna servicios_pagados agregada exitosamente' as mensaje;

-- ============================================
-- VERIFICACIÓN
-- ============================================

SELECT '========== DESPUÉS DE LA MIGRACIÓN ==========' as '';

SELECT
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'imaginatics_ruc'
AND TABLE_NAME = 'historial_pagos'
ORDER BY ORDINAL_POSITION;

-- Mostrar estructura de la tabla
DESCRIBE historial_pagos;

SELECT '========== ✅ MIGRACIÓN COMPLETADA ==========' as '';
SELECT 'Columna servicios_pagados agregada a historial_pagos' as mensaje;
