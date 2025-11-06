-- ============================================
-- SISTEMA DE COLA DE ENVÍOS
-- Migración para procesamiento en background
-- ============================================

-- Tabla para sesiones de envío (lotes)
CREATE TABLE IF NOT EXISTS sesiones_envio (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo_envio ENUM('orden_pago', 'recordatorio_proximo', 'recordatorio_vencido') NOT NULL,
    total_clientes INT NOT NULL DEFAULT 0,
    procesados INT NOT NULL DEFAULT 0,
    exitosos INT NOT NULL DEFAULT 0,
    fallidos INT NOT NULL DEFAULT 0,
    estado ENUM('pendiente', 'procesando', 'completado', 'cancelado', 'error') NOT NULL DEFAULT 'pendiente',
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_inicio DATETIME NULL,
    fecha_finalizacion DATETIME NULL,
    usuario_creador VARCHAR(100) NULL,
    mensaje_error TEXT NULL,
    configuracion JSON NULL COMMENT 'Configuración específica del lote (ej: dias_anticipacion)',
    INDEX idx_estado (estado),
    INDEX idx_fecha_creacion (fecha_creacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla para cola de envíos individuales
CREATE TABLE IF NOT EXISTS cola_envios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sesion_id INT NOT NULL,
    cliente_id INT NOT NULL,
    tipo_envio ENUM('orden_pago', 'recordatorio_proximo', 'recordatorio_vencido') NOT NULL,
    prioridad INT NOT NULL DEFAULT 0 COMMENT 'Mayor número = mayor prioridad',
    estado ENUM('pendiente', 'procesando', 'enviado', 'error', 'cancelado') NOT NULL DEFAULT 'pendiente',
    intentos INT NOT NULL DEFAULT 0,
    max_intentos INT NOT NULL DEFAULT 3,

    -- Datos del cliente (snapshot para evitar joins)
    ruc VARCHAR(11) NOT NULL,
    razon_social VARCHAR(255) NOT NULL,
    whatsapp VARCHAR(20) NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    fecha_vencimiento DATE NOT NULL,
    tipo_servicio ENUM('mensual', 'trimestral', 'semestral', 'anual') DEFAULT 'anual',

    -- Datos del mensaje
    mensaje_texto TEXT NULL,
    imagen_base64 LONGTEXT NULL COMMENT 'Imagen pregenerada en base64',
    dias_restantes INT NULL COMMENT 'Para recordatorios',

    -- Control de ejecución
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_programada DATETIME NULL COMMENT 'Para envíos programados',
    fecha_procesamiento DATETIME NULL,
    fecha_envio DATETIME NULL,

    -- Resultados
    envio_whatsapp_id INT NULL COMMENT 'ID del registro en envios_whatsapp',
    respuesta_api TEXT NULL,
    mensaje_error TEXT NULL,

    FOREIGN KEY (sesion_id) REFERENCES sesiones_envio(id) ON DELETE CASCADE,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,

    INDEX idx_sesion (sesion_id),
    INDEX idx_estado (estado),
    INDEX idx_prioridad (prioridad),
    INDEX idx_fecha_programada (fecha_programada),
    INDEX idx_cliente (cliente_id),
    INDEX idx_estado_fecha (estado, fecha_programada)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Vista para monitoreo rápido de sesiones activas
CREATE OR REPLACE VIEW v_sesiones_activas AS
SELECT
    s.id,
    s.tipo_envio,
    s.estado,
    s.total_clientes,
    s.procesados,
    s.exitosos,
    s.fallidos,
    s.fecha_creacion,
    s.fecha_inicio,
    TIMESTAMPDIFF(SECOND, s.fecha_inicio, COALESCE(s.fecha_finalizacion, NOW())) as duracion_segundos,
    ROUND((s.procesados / s.total_clientes) * 100, 2) as porcentaje_completado,
    (SELECT COUNT(*) FROM cola_envios WHERE sesion_id = s.id AND estado = 'pendiente') as pendientes,
    (SELECT COUNT(*) FROM cola_envios WHERE sesion_id = s.id AND estado = 'procesando') as en_proceso
FROM sesiones_envio s
WHERE s.estado IN ('pendiente', 'procesando')
ORDER BY s.fecha_creacion DESC;

-- Vista para estadísticas de cola
CREATE OR REPLACE VIEW v_estadisticas_cola AS
SELECT
    COUNT(*) as total_en_cola,
    SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
    SUM(CASE WHEN estado = 'procesando' THEN 1 ELSE 0 END) as procesando,
    SUM(CASE WHEN estado = 'error' THEN 1 ELSE 0 END) as con_error,
    COUNT(DISTINCT sesion_id) as sesiones_activas,
    MIN(fecha_creacion) as trabajo_mas_antiguo,
    MAX(fecha_creacion) as trabajo_mas_reciente
FROM cola_envios
WHERE estado IN ('pendiente', 'procesando', 'error');

-- Triggers para actualizar contadores en sesiones
DELIMITER $$

CREATE TRIGGER after_cola_envio_update
AFTER UPDATE ON cola_envios
FOR EACH ROW
BEGIN
    -- Actualizar contadores de la sesión
    IF NEW.estado != OLD.estado THEN
        UPDATE sesiones_envio SET
            procesados = (
                SELECT COUNT(*)
                FROM cola_envios
                WHERE sesion_id = NEW.sesion_id
                AND estado IN ('enviado', 'error', 'cancelado')
            ),
            exitosos = (
                SELECT COUNT(*)
                FROM cola_envios
                WHERE sesion_id = NEW.sesion_id
                AND estado = 'enviado'
            ),
            fallidos = (
                SELECT COUNT(*)
                FROM cola_envios
                WHERE sesion_id = NEW.sesion_id
                AND estado = 'error'
            )
        WHERE id = NEW.sesion_id;

        -- Marcar sesión como completada si todos están procesados
        UPDATE sesiones_envio
        SET estado = 'completado',
            fecha_finalizacion = NOW()
        WHERE id = NEW.sesion_id
        AND estado = 'procesando'
        AND procesados >= total_clientes;
    END IF;
END$$

DELIMITER ;
