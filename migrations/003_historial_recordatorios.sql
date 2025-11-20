-- ============================================
-- MIGRACIÓN 003: Sistema de Historial de Recordatorios
-- Fecha: 2025-01-19
-- ============================================

-- Tabla para historial de recordatorios enviados
CREATE TABLE IF NOT EXISTS historial_recordatorios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cliente_id INT NOT NULL,
    servicio_id INT NULL,
    tipo_recordatorio ENUM('preventivo', 'urgente', 'critico', 'vencido', 'mora') NOT NULL,
    dias_antes_vencimiento INT NOT NULL COMMENT 'Negativo si ya vencio',
    fecha_envio DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_vencimiento DATE NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    estado_envio ENUM('pendiente', 'enviado', 'error', 'rebotado') DEFAULT 'pendiente',
    canal ENUM('whatsapp', 'email', 'sms') DEFAULT 'whatsapp',
    numero_destino VARCHAR(20),
    mensaje_enviado TEXT,
    respuesta_api TEXT COMMENT 'Respuesta de la API de WhatsApp',
    error_detalle TEXT COMMENT 'Detalle del error si fallo',
    sesion_envio_id INT COMMENT 'ID de la sesion de envio en lote',
    fue_automatico BOOLEAN DEFAULT FALSE COMMENT 'TRUE si fue enviado por cron, FALSE si fue manual',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_cliente (cliente_id),
    INDEX idx_fecha_envio (fecha_envio),
    INDEX idx_estado (estado_envio),
    INDEX idx_tipo (tipo_recordatorio),
    INDEX idx_automatico (fue_automatico),

    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
    FOREIGN KEY (servicio_id) REFERENCES servicios_contratados(id) ON DELETE SET NULL,
    FOREIGN KEY (sesion_envio_id) REFERENCES sesiones_envio(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de configuración de límites de recordatorios
CREATE TABLE IF NOT EXISTS config_recordatorios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    clave VARCHAR(100) UNIQUE NOT NULL,
    valor TEXT NOT NULL,
    descripcion TEXT,
    tipo_dato ENUM('integer', 'boolean', 'string', 'json') DEFAULT 'string',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar configuraciones por defecto
INSERT INTO config_recordatorios (clave, valor, descripcion, tipo_dato) VALUES
('dias_minimos_entre_recordatorios', '3', 'Días mínimos que deben pasar entre recordatorios al mismo cliente', 'integer'),
('max_recordatorios_mes', '8', 'Máximo de recordatorios permitidos por mes por cliente', 'integer'),
('recordatorios_automaticos_activos', 'true', 'Activar/desactivar envío automático de recordatorios', 'boolean'),
('hora_envio_automatico', '09:00', 'Hora del día para enviar recordatorios automáticos (formato HH:MM)', 'string'),
('dias_recordatorio_preventivo', '-7', 'Días antes del vencimiento para recordatorio preventivo', 'integer'),
('dias_recordatorio_urgente', '-3', 'Días antes del vencimiento para recordatorio urgente', 'integer'),
('dias_recordatorio_critico', '-1', 'Días antes del vencimiento para recordatorio crítico', 'integer'),
('enviar_recordatorio_vence_hoy', 'true', 'Enviar recordatorio el día del vencimiento', 'boolean'),
('dias_recordatorio_mora', '+3,+7,+15,+30', 'Días después del vencimiento para recordatorios de mora (separados por coma)', 'string'),
('max_recordatorios_mora', '4', 'Máximo de recordatorios de mora permitidos', 'integer')
ON DUPLICATE KEY UPDATE fecha_actualizacion = CURRENT_TIMESTAMP;

-- Vista para estadísticas de recordatorios por cliente
CREATE OR REPLACE VIEW v_estadisticas_recordatorios AS
SELECT
    c.id as cliente_id,
    c.razon_social,
    c.ruc,
    COUNT(hr.id) as total_recordatorios,
    COUNT(CASE WHEN hr.estado_envio = 'enviado' THEN 1 END) as recordatorios_exitosos,
    COUNT(CASE WHEN hr.estado_envio = 'error' THEN 1 END) as recordatorios_fallidos,
    COUNT(CASE WHEN hr.fue_automatico = TRUE THEN 1 END) as recordatorios_automaticos,
    COUNT(CASE WHEN hr.fue_automatico = FALSE THEN 1 END) as recordatorios_manuales,
    MAX(hr.fecha_envio) as ultimo_recordatorio,
    AVG(hr.dias_antes_vencimiento) as promedio_dias_anticipo,
    COUNT(CASE WHEN hr.fecha_envio >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as recordatorios_mes_actual
FROM clientes c
LEFT JOIN historial_recordatorios hr ON c.id = hr.cliente_id
GROUP BY c.id, c.razon_social, c.ruc;

-- Vista para recordatorios pendientes de hoy
CREATE OR REPLACE VIEW v_recordatorios_pendientes_hoy AS
SELECT
    c.id,
    c.ruc,
    c.razon_social,
    c.whatsapp,
    sc.precio as monto,
    sc.moneda,
    sc.fecha_vencimiento,
    sc.periodo_facturacion as periodicidad,
    DATEDIFF(sc.fecha_vencimiento, CURDATE()) as dias_restantes,
    COALESCE(
        (SELECT COUNT(*)
         FROM historial_recordatorios hr
         WHERE hr.cliente_id = c.id
         AND hr.fecha_envio >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ), 0
    ) as recordatorios_este_mes,
    COALESCE(
        (SELECT MAX(fecha_envio)
         FROM historial_recordatorios hr
         WHERE hr.cliente_id = c.id
        ), NULL
    ) as ultimo_recordatorio
FROM clientes c
INNER JOIN servicios_contratados sc ON c.id = sc.cliente_id
WHERE sc.estado = 'activo'
AND c.activo = 1
AND (
    -- Próximo a vencer (7 días o menos)
    DATEDIFF(sc.fecha_vencimiento, CURDATE()) BETWEEN 0 AND 7
    OR
    -- Ya vencido (hasta 30 días de atraso)
    DATEDIFF(sc.fecha_vencimiento, CURDATE()) BETWEEN -30 AND 0
)
-- Excluir si ya se envió recordatorio en los últimos 3 días
AND NOT EXISTS (
    SELECT 1 FROM historial_recordatorios hr
    WHERE hr.cliente_id = c.id
    AND hr.fecha_envio >= DATE_SUB(CURDATE(), INTERVAL 3 DAY)
)
ORDER BY sc.fecha_vencimiento ASC;

-- Añadir índices adicionales a tablas existentes para optimizar consultas de recordatorios
-- Nota: Si el índice ya existe, esta operación fallará sin afectar el resto de la migración
SET @exist := (SELECT COUNT(*) FROM information_schema.statistics
               WHERE table_schema = 'imaginatics_ruc'
               AND table_name = 'envios_whatsapp'
               AND index_name = 'idx_tipo_fecha');
SET @sqlstmt := IF(@exist = 0, 'ALTER TABLE envios_whatsapp ADD INDEX idx_tipo_fecha (tipo_envio, fecha_envio)', 'SELECT "Index already exists"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Log de migración
INSERT INTO logs_sistema (nivel, modulo, mensaje, datos_adicionales)
VALUES ('info', 'migracion', 'Migración 003: Sistema de historial de recordatorios aplicado',
        JSON_OBJECT(
            'version', '003',
            'tablas_creadas', 'historial_recordatorios, config_recordatorios',
            'vistas_creadas', 'v_estadisticas_recordatorios, v_recordatorios_pendientes_hoy'
        ));

-- ============================================
-- FIN DE MIGRACIÓN
-- ============================================
