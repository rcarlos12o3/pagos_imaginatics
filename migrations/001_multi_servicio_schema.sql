-- ============================================
-- MIGRACIÓN 001: Sistema Multi-Servicio
-- Fecha: 2025-11-05
-- Descripción: Implementación de sistema de múltiples servicios por cliente
-- ============================================

USE imaginatics_ruc;

-- ============================================
-- TABLA 1: catalogo_servicios
-- Catálogo maestro de servicios disponibles
-- ============================================
CREATE TABLE IF NOT EXISTS catalogo_servicios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL COMMENT 'Nombre del servicio',
    descripcion TEXT COMMENT 'Descripción detallada del servicio',
    categoria ENUM('hosting', 'certificados', 'correo', 'dominio', 'internet', 'software', 'otros') NOT NULL,

    -- Precios y facturación
    precio_base DECIMAL(10,2) NOT NULL COMMENT 'Precio base del servicio',
    moneda ENUM('PEN', 'USD') DEFAULT 'PEN' COMMENT 'Moneda del precio (PEN=Soles, USD=Dólares)',
    periodos_disponibles JSON COMMENT 'Array de periodos: ["mensual","trimestral","semestral","anual"]',
    requiere_facturacion BOOLEAN DEFAULT TRUE COMMENT 'Si requiere factura electrónica',
    igv_incluido BOOLEAN DEFAULT FALSE COMMENT 'Si el precio incluye IGV (18%)',

    -- Configuración
    configuracion_default JSON COMMENT 'Configuración por defecto del servicio',

    -- Estado
    activo BOOLEAN DEFAULT TRUE,
    orden_visualizacion INT DEFAULT 0 COMMENT 'Orden para mostrar en listados',

    -- Auditoría
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Índices
    INDEX idx_categoria (categoria),
    INDEX idx_activo (activo),
    INDEX idx_orden (orden_visualizacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Catálogo maestro de servicios disponibles para contratar';

-- ============================================
-- TABLA 2: servicios_contratados
-- Servicios contratados por cada cliente
-- ============================================
CREATE TABLE IF NOT EXISTS servicios_contratados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    servicio_id INT NOT NULL,

    -- Facturación y precios
    precio DECIMAL(10,2) NOT NULL COMMENT 'Precio específico de este contrato',
    moneda ENUM('PEN', 'USD') DEFAULT 'PEN' COMMENT 'Moneda del precio (PEN=Soles, USD=Dólares)',
    periodo_facturacion ENUM('mensual', 'trimestral', 'semestral', 'anual') NOT NULL,

    -- Fechas importantes
    fecha_inicio DATE NOT NULL COMMENT 'Fecha de inicio del servicio',
    fecha_vencimiento DATE NOT NULL COMMENT 'Próxima fecha de vencimiento/renovación',
    fecha_ultima_factura DATE NULL COMMENT 'Última vez que se facturó',
    fecha_proximo_pago DATE NULL COMMENT 'Próxima fecha programada de pago',

    -- Estado del servicio
    estado ENUM('activo', 'suspendido', 'cancelado', 'vencido') DEFAULT 'activo',
    motivo_suspension TEXT NULL COMMENT 'Razón de suspensión o cancelación',
    auto_renovacion BOOLEAN DEFAULT TRUE COMMENT 'Renovar automáticamente al vencer',

    -- Configuración específica del servicio
    configuracion JSON COMMENT 'Datos específicos (ej: dominio, GB, etc.)',

    -- Notas y observaciones
    notas TEXT NULL,

    -- Auditoría
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    usuario_creacion VARCHAR(100) DEFAULT 'Sistema',

    -- Relaciones
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
    FOREIGN KEY (servicio_id) REFERENCES catalogo_servicios(id) ON DELETE RESTRICT,

    -- Índices
    INDEX idx_cliente_servicio (cliente_id, servicio_id),
    INDEX idx_cliente_estado (cliente_id, estado),
    INDEX idx_estado (estado),
    INDEX idx_fecha_vencimiento (fecha_vencimiento),
    INDEX idx_fecha_proximo_pago (fecha_proximo_pago),
    INDEX idx_activos (cliente_id, estado, fecha_vencimiento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Servicios contratados por cada cliente';

-- ============================================
-- TABLA 3: facturas_electronicas
-- Facturas electrónicas emitidas
-- ============================================
CREATE TABLE IF NOT EXISTS facturas_electronicas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,

    -- Datos de la factura
    numero_factura VARCHAR(50) NOT NULL UNIQUE COMMENT 'Número completo de factura',
    serie VARCHAR(10) NOT NULL COMMENT 'Serie de la factura (ej: F001)',
    numero_correlativo INT NOT NULL COMMENT 'Número correlativo',
    fecha_emision DATE NOT NULL,
    fecha_vencimiento DATE NULL COMMENT 'Fecha de vencimiento de pago',

    -- Montos
    subtotal DECIMAL(10,2) NOT NULL COMMENT 'Subtotal sin IGV',
    igv DECIMAL(10,2) NOT NULL COMMENT 'Monto del IGV (18%)',
    total DECIMAL(10,2) NOT NULL COMMENT 'Total a pagar',

    -- Estado de la factura
    estado ENUM('borrador', 'emitida', 'anulada', 'pagada', 'vencida') DEFAULT 'borrador',
    fecha_pago DATE NULL,

    -- Integración SUNAT (para futuro)
    estado_sunat ENUM('pendiente', 'aceptada', 'rechazada', 'baja') DEFAULT 'pendiente',
    codigo_hash VARCHAR(255) NULL COMMENT 'Hash del XML',
    xml_ruta VARCHAR(255) NULL COMMENT 'Ruta del archivo XML',
    pdf_ruta VARCHAR(255) NULL COMMENT 'Ruta del archivo PDF',
    cdr_ruta VARCHAR(255) NULL COMMENT 'Ruta del CDR de SUNAT',
    mensaje_sunat TEXT NULL COMMENT 'Mensaje de respuesta de SUNAT',

    -- Observaciones
    observaciones TEXT NULL,
    tipo_factura ENUM('manual', 'automatica') DEFAULT 'manual',

    -- Auditoría
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    usuario_creacion VARCHAR(100) DEFAULT 'Sistema',

    -- Relaciones
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,

    -- Índices
    INDEX idx_cliente (cliente_id),
    INDEX idx_numero_factura (numero_factura),
    INDEX idx_serie_correlativo (serie, numero_correlativo),
    INDEX idx_estado (estado),
    INDEX idx_fecha_emision (fecha_emision),
    INDEX idx_estado_sunat (estado_sunat),
    INDEX idx_pendientes (estado, fecha_emision)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Facturas electrónicas emitidas a clientes';

-- ============================================
-- TABLA 4: detalle_factura
-- Detalle de items en cada factura
-- ============================================
CREATE TABLE IF NOT EXISTS detalle_factura (
    id INT AUTO_INCREMENT PRIMARY KEY,
    factura_id INT NOT NULL,
    servicio_contratado_id INT NULL COMMENT 'Servicio relacionado si aplica',

    -- Detalle del item
    codigo_item VARCHAR(50) NULL COMMENT 'Código interno del producto/servicio',
    descripcion TEXT NOT NULL COMMENT 'Descripción del item',
    cantidad DECIMAL(10,2) NOT NULL DEFAULT 1,
    precio_unitario DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL COMMENT 'cantidad * precio_unitario',

    -- Periodo facturado (para servicios recurrentes)
    periodo_inicio DATE NULL COMMENT 'Inicio del periodo facturado',
    periodo_fin DATE NULL COMMENT 'Fin del periodo facturado',

    -- Auditoría
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Relaciones
    FOREIGN KEY (factura_id) REFERENCES facturas_electronicas(id) ON DELETE CASCADE,
    FOREIGN KEY (servicio_contratado_id) REFERENCES servicios_contratados(id) ON DELETE SET NULL,

    -- Índices
    INDEX idx_factura (factura_id),
    INDEX idx_servicio (servicio_contratado_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Detalle de items en cada factura electrónica';

-- ============================================
-- MODIFICACIONES A TABLAS EXISTENTES
-- ============================================

-- Agregar campos adicionales a tabla clientes (solo si no existen)
SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = 'imaginatics_ruc' AND TABLE_NAME = 'clientes' AND COLUMN_NAME = 'tipo_documento');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE clientes ADD COLUMN tipo_documento ENUM(''RUC'', ''DNI'', ''CE'', ''PASAPORTE'') DEFAULT ''RUC'' AFTER ruc',
    'SELECT "Column tipo_documento already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = 'imaginatics_ruc' AND TABLE_NAME = 'clientes' AND COLUMN_NAME = 'email');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE clientes ADD COLUMN email VARCHAR(255) NULL AFTER whatsapp',
    'SELECT "Column email already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = 'imaginatics_ruc' AND TABLE_NAME = 'clientes' AND COLUMN_NAME = 'contacto_nombre');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE clientes ADD COLUMN contacto_nombre VARCHAR(255) NULL AFTER email',
    'SELECT "Column contacto_nombre already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = 'imaginatics_ruc' AND TABLE_NAME = 'clientes' AND COLUMN_NAME = 'contacto_cargo');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE clientes ADD COLUMN contacto_cargo VARCHAR(100) NULL AFTER contacto_nombre',
    'SELECT "Column contacto_cargo already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Modificar tabla historial_pagos para vincular con facturas (solo si no existen)
SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = 'imaginatics_ruc' AND TABLE_NAME = 'historial_pagos' AND COLUMN_NAME = 'factura_id');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE historial_pagos ADD COLUMN factura_id INT NULL AFTER cliente_id',
    'SELECT "Column factura_id already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = 'imaginatics_ruc' AND TABLE_NAME = 'historial_pagos' AND COLUMN_NAME = 'servicios_pagados');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE historial_pagos ADD COLUMN servicios_pagados JSON NULL COMMENT ''IDs de servicios_contratados incluidos en el pago''',
    'SELECT "Column servicios_pagados already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = 'imaginatics_ruc' AND TABLE_NAME = 'historial_pagos' AND COLUMN_NAME = 'periodo_inicio');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE historial_pagos ADD COLUMN periodo_inicio DATE NULL',
    'SELECT "Column periodo_inicio already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = 'imaginatics_ruc' AND TABLE_NAME = 'historial_pagos' AND COLUMN_NAME = 'periodo_fin');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE historial_pagos ADD COLUMN periodo_fin DATE NULL',
    'SELECT "Column periodo_fin already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Agregar foreign key si no existe
SET @fk_exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = 'imaginatics_ruc'
    AND TABLE_NAME = 'historial_pagos'
    AND CONSTRAINT_NAME = 'fk_pago_factura');

SET @sql = IF(@fk_exists = 0,
    'ALTER TABLE historial_pagos ADD CONSTRAINT fk_pago_factura FOREIGN KEY (factura_id) REFERENCES facturas_electronicas(id) ON DELETE SET NULL',
    'SELECT "Foreign key fk_pago_factura already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Agregar índice para factura_id en historial_pagos (solo si no existe)
SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = 'imaginatics_ruc' AND TABLE_NAME = 'historial_pagos' AND INDEX_NAME = 'idx_factura');
SET @sql = IF(@idx_exists = 0,
    'CREATE INDEX idx_factura ON historial_pagos(factura_id)',
    'SELECT "Index idx_factura already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Modificar tabla envios_whatsapp para soportar nuevos tipos
ALTER TABLE envios_whatsapp
    MODIFY COLUMN tipo_envio ENUM(
        'orden_pago',
        'recordatorio_vencido',
        'recordatorio_proximo',
        'auth',
        'recuperacion_password',
        'factura_electronica',
        'confirmacion_pago',
        'servicio_suspendido',
        'servicio_renovado',
        'servicio_activado'
    ) NOT NULL;

-- Agregar columnas para vincular envíos con facturas y servicios (solo si no existen)
SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = 'imaginatics_ruc' AND TABLE_NAME = 'envios_whatsapp' AND COLUMN_NAME = 'factura_id');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE envios_whatsapp ADD COLUMN factura_id INT NULL AFTER cliente_id',
    'SELECT "Column factura_id already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = 'imaginatics_ruc' AND TABLE_NAME = 'envios_whatsapp' AND COLUMN_NAME = 'servicio_contratado_id');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE envios_whatsapp ADD COLUMN servicio_contratado_id INT NULL AFTER factura_id',
    'SELECT "Column servicio_contratado_id already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Agregar foreign keys para envios_whatsapp
SET @fk_exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = 'imaginatics_ruc'
    AND TABLE_NAME = 'envios_whatsapp'
    AND CONSTRAINT_NAME = 'fk_envio_factura');

SET @sql = IF(@fk_exists = 0,
    'ALTER TABLE envios_whatsapp ADD CONSTRAINT fk_envio_factura FOREIGN KEY (factura_id) REFERENCES facturas_electronicas(id) ON DELETE SET NULL',
    'SELECT "Foreign key fk_envio_factura already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = 'imaginatics_ruc'
    AND TABLE_NAME = 'envios_whatsapp'
    AND CONSTRAINT_NAME = 'fk_envio_servicio');

SET @sql = IF(@fk_exists = 0,
    'ALTER TABLE envios_whatsapp ADD CONSTRAINT fk_envio_servicio FOREIGN KEY (servicio_contratado_id) REFERENCES servicios_contratados(id) ON DELETE SET NULL',
    'SELECT "Foreign key fk_envio_servicio already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================
-- VISTAS
-- ============================================

-- Vista de servicios activos por cliente
CREATE OR REPLACE VIEW v_servicios_cliente AS
SELECT
    c.id as cliente_id,
    c.ruc,
    c.razon_social,
    c.whatsapp,
    c.email,
    cs.id as servicio_id,
    cs.nombre as servicio_nombre,
    cs.categoria,
    sc.id as contrato_id,
    sc.precio,
    sc.moneda,
    sc.periodo_facturacion,
    sc.fecha_inicio,
    sc.fecha_vencimiento,
    sc.fecha_ultima_factura,
    sc.estado,
    sc.auto_renovacion,
    sc.configuracion,
    DATEDIFF(sc.fecha_vencimiento, CURDATE()) as dias_restantes,
    CASE
        WHEN sc.estado != 'activo' THEN 'INACTIVO'
        WHEN DATEDIFF(sc.fecha_vencimiento, CURDATE()) < 0 THEN 'VENCIDO'
        WHEN DATEDIFF(sc.fecha_vencimiento, CURDATE()) = 0 THEN 'VENCE_HOY'
        WHEN DATEDIFF(sc.fecha_vencimiento, CURDATE()) <= 3 THEN 'POR_VENCER'
        ELSE 'AL_DIA'
    END as estado_vencimiento
FROM clientes c
INNER JOIN servicios_contratados sc ON c.id = sc.cliente_id
INNER JOIN catalogo_servicios cs ON sc.servicio_id = cs.id
WHERE c.activo = TRUE
ORDER BY c.razon_social, sc.fecha_vencimiento;

-- Vista de resumen financiero por cliente
CREATE OR REPLACE VIEW v_resumen_financiero_cliente AS
SELECT
    c.id as cliente_id,
    c.ruc,
    c.razon_social,
    c.whatsapp,
    c.email,
    COUNT(DISTINCT sc.id) as total_servicios,
    SUM(CASE WHEN sc.estado = 'activo' THEN 1 ELSE 0 END) as servicios_activos,
    SUM(CASE WHEN sc.estado = 'suspendido' THEN 1 ELSE 0 END) as servicios_suspendidos,
    SUM(CASE WHEN sc.estado = 'activo' THEN sc.precio ELSE 0 END) as monto_servicios_activos,
    MIN(CASE WHEN sc.estado = 'activo' THEN sc.fecha_vencimiento END) as proximo_vencimiento,
    (SELECT COUNT(*) FROM facturas_electronicas f
     WHERE f.cliente_id = c.id AND f.estado IN ('emitida', 'vencida')) as facturas_pendientes,
    (SELECT SUM(total) FROM facturas_electronicas f
     WHERE f.cliente_id = c.id AND f.estado IN ('emitida', 'vencida')) as saldo_pendiente,
    (SELECT SUM(total) FROM facturas_electronicas f
     WHERE f.cliente_id = c.id AND f.estado = 'pagada'
     AND YEAR(f.fecha_pago) = YEAR(CURDATE())
     AND MONTH(f.fecha_pago) = MONTH(CURDATE())) as pagado_mes_actual
FROM clientes c
LEFT JOIN servicios_contratados sc ON c.id = sc.cliente_id
WHERE c.activo = TRUE
GROUP BY c.id, c.ruc, c.razon_social, c.whatsapp, c.email;

-- Vista de servicios por vencer
CREATE OR REPLACE VIEW v_servicios_por_vencer AS
SELECT
    sc.id as contrato_id,
    sc.cliente_id,
    c.ruc,
    c.razon_social,
    c.whatsapp,
    c.email,
    sc.servicio_id,
    cs.nombre as servicio_nombre,
    cs.categoria,
    sc.precio,
    sc.moneda,
    sc.periodo_facturacion,
    sc.fecha_vencimiento,
    DATEDIFF(sc.fecha_vencimiento, CURDATE()) as dias_restantes,
    sc.auto_renovacion,
    -- Verificar si ya se envió orden de pago reciente
    (SELECT MAX(fecha_envio) FROM envios_whatsapp
     WHERE cliente_id = sc.cliente_id
     AND servicio_contratado_id = sc.id
     AND tipo_envio IN ('orden_pago', 'factura_electronica')
     AND fecha_envio >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)) as ultima_notificacion
FROM servicios_contratados sc
INNER JOIN clientes c ON sc.cliente_id = c.id
INNER JOIN catalogo_servicios cs ON sc.servicio_id = cs.id
WHERE sc.estado = 'activo'
AND c.activo = TRUE
AND DATEDIFF(sc.fecha_vencimiento, CURDATE()) <= 7
AND DATEDIFF(sc.fecha_vencimiento, CURDATE()) >= 0
ORDER BY sc.fecha_vencimiento ASC, c.razon_social;

-- Vista de estadísticas de facturación
CREATE OR REPLACE VIEW v_estadisticas_facturacion AS
SELECT
    DATE_FORMAT(fecha_emision, '%Y-%m') as mes,
    COUNT(*) as total_facturas,
    SUM(CASE WHEN estado = 'emitida' THEN 1 ELSE 0 END) as facturas_pendientes,
    SUM(CASE WHEN estado = 'pagada' THEN 1 ELSE 0 END) as facturas_pagadas,
    SUM(CASE WHEN estado = 'anulada' THEN 1 ELSE 0 END) as facturas_anuladas,
    SUM(subtotal) as subtotal_total,
    SUM(igv) as igv_total,
    SUM(total) as monto_total,
    SUM(CASE WHEN estado = 'pagada' THEN total ELSE 0 END) as monto_cobrado,
    SUM(CASE WHEN estado IN ('emitida', 'vencida') THEN total ELSE 0 END) as monto_pendiente
FROM facturas_electronicas
GROUP BY DATE_FORMAT(fecha_emision, '%Y-%m')
ORDER BY mes DESC;

-- ============================================
-- TRIGGERS
-- ============================================

-- Trigger para actualizar estado de servicio a vencido
DELIMITER $$

DROP TRIGGER IF EXISTS tr_actualizar_servicio_vencido$$
CREATE TRIGGER tr_actualizar_servicio_vencido
BEFORE UPDATE ON servicios_contratados
FOR EACH ROW
BEGIN
    -- Si la fecha de vencimiento pasó y está activo, cambiar a vencido
    IF NEW.fecha_vencimiento < CURDATE()
       AND NEW.estado = 'activo'
       AND OLD.estado = 'activo' THEN
        SET NEW.estado = 'vencido';
    END IF;
END$$

-- Trigger para actualizar totales de factura cuando se agregan detalles
DROP TRIGGER IF EXISTS tr_actualizar_totales_factura_insert$$
CREATE TRIGGER tr_actualizar_totales_factura_insert
AFTER INSERT ON detalle_factura
FOR EACH ROW
BEGIN
    UPDATE facturas_electronicas
    SET
        subtotal = (SELECT SUM(subtotal) FROM detalle_factura WHERE factura_id = NEW.factura_id),
        igv = (SELECT SUM(subtotal) * 0.18 FROM detalle_factura WHERE factura_id = NEW.factura_id),
        total = (SELECT SUM(subtotal) * 1.18 FROM detalle_factura WHERE factura_id = NEW.factura_id)
    WHERE id = NEW.factura_id;
END$$

-- Trigger para actualizar totales cuando se modifican detalles
DROP TRIGGER IF EXISTS tr_actualizar_totales_factura_update$$
CREATE TRIGGER tr_actualizar_totales_factura_update
AFTER UPDATE ON detalle_factura
FOR EACH ROW
BEGIN
    UPDATE facturas_electronicas
    SET
        subtotal = (SELECT SUM(subtotal) FROM detalle_factura WHERE factura_id = NEW.factura_id),
        igv = (SELECT SUM(subtotal) * 0.18 FROM detalle_factura WHERE factura_id = NEW.factura_id),
        total = (SELECT SUM(subtotal) * 1.18 FROM detalle_factura WHERE factura_id = NEW.factura_id)
    WHERE id = NEW.factura_id;
END$$

-- Trigger para actualizar totales cuando se eliminan detalles
DROP TRIGGER IF EXISTS tr_actualizar_totales_factura_delete$$
CREATE TRIGGER tr_actualizar_totales_factura_delete
AFTER DELETE ON detalle_factura
FOR EACH ROW
BEGIN
    UPDATE facturas_electronicas
    SET
        subtotal = IFNULL((SELECT SUM(subtotal) FROM detalle_factura WHERE factura_id = OLD.factura_id), 0),
        igv = IFNULL((SELECT SUM(subtotal) * 0.18 FROM detalle_factura WHERE factura_id = OLD.factura_id), 0),
        total = IFNULL((SELECT SUM(subtotal) * 1.18 FROM detalle_factura WHERE factura_id = OLD.factura_id), 0)
    WHERE id = OLD.factura_id;
END$$

DELIMITER ;

-- ============================================
-- ÍNDICES ADICIONALES PARA OPTIMIZACIÓN
-- ============================================

-- Mejorar rendimiento de consultas frecuentes (solo si no existen)
SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = 'imaginatics_ruc' AND TABLE_NAME = 'clientes' AND INDEX_NAME = 'idx_cliente_activo');
SET @sql = IF(@idx_exists = 0,
    'CREATE INDEX idx_cliente_activo ON clientes(activo, id)',
    'SELECT "Index idx_cliente_activo already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = 'imaginatics_ruc' AND TABLE_NAME = 'servicios_contratados' AND INDEX_NAME = 'idx_servicio_vencimiento_estado');
SET @sql = IF(@idx_exists = 0,
    'CREATE INDEX idx_servicio_vencimiento_estado ON servicios_contratados(fecha_vencimiento, estado)',
    'SELECT "Index idx_servicio_vencimiento_estado already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = 'imaginatics_ruc' AND TABLE_NAME = 'facturas_electronicas' AND INDEX_NAME = 'idx_factura_estado_fecha');
SET @sql = IF(@idx_exists = 0,
    'CREATE INDEX idx_factura_estado_fecha ON facturas_electronicas(estado, fecha_emision)',
    'SELECT "Index idx_factura_estado_fecha already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================
-- COMENTARIOS FINALES
-- ============================================

-- Log de migración exitosa
INSERT INTO logs_sistema (nivel, modulo, mensaje, datos_adicionales)
VALUES ('info', 'migracion', 'Migración 001: Sistema Multi-Servicio ejecutada exitosamente',
    JSON_OBJECT(
        'version', '001',
        'fecha', NOW(),
        'descripcion', 'Creación de tablas para sistema multi-servicio'
    ));

SELECT 'Migración 001 completada exitosamente' as resultado;
