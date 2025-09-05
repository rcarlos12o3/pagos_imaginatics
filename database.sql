-- ============================================
-- BASE DE DATOS: IMAGINATICS RUC CONSULTOR
-- ============================================

CREATE DATABASE IF NOT EXISTS imaginatics_ruc CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE imaginatics_ruc;

-- ============================================
-- TABLA: clientes
-- ============================================
CREATE TABLE clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ruc VARCHAR(11) NOT NULL UNIQUE,
    razon_social VARCHAR(255) NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    fecha_vencimiento DATE NOT NULL,
    whatsapp VARCHAR(15) NOT NULL,
    direccion TEXT NULL,
    estado_sunat VARCHAR(50) NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    activo BOOLEAN DEFAULT TRUE,
    INDEX idx_ruc (ruc),
    INDEX idx_fecha_vencimiento (fecha_vencimiento),
    INDEX idx_activo (activo)
);

-- ============================================
-- TABLA: envios_whatsapp
-- ============================================
CREATE TABLE envios_whatsapp (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NULL COMMENT 'Puede ser NULL para envíos de autenticación',
    numero_destino VARCHAR(15) NOT NULL COMMENT 'Número de destino del mensaje',
    tipo_envio ENUM('orden_pago', 'recordatorio_vencido', 'recordatorio_proximo', 'auth', 'recuperacion_password') NOT NULL,
    estado ENUM('pendiente', 'enviado', 'error') DEFAULT 'pendiente',
    mensaje_texto TEXT,
    imagen_generada BOOLEAN DEFAULT FALSE,
    respuesta_api TEXT NULL,
    mensaje_error TEXT NULL,
    fecha_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_programado DATE NULL,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
    INDEX idx_cliente_id (cliente_id),
    INDEX idx_numero_destino (numero_destino),
    INDEX idx_tipo_envio (tipo_envio),
    INDEX idx_estado (estado),
    INDEX idx_fecha_envio (fecha_envio)
);

-- ============================================
-- TABLA: historial_pagos
-- ============================================
CREATE TABLE historial_pagos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    monto_pagado DECIMAL(10,2) NOT NULL,
    fecha_pago DATE NOT NULL,
    metodo_pago ENUM('transferencia', 'deposito', 'yape', 'plin', 'efectivo', 'otro') NOT NULL,
    numero_operacion VARCHAR(50) NULL,
    banco VARCHAR(100) NULL,
    comprobante_ruta VARCHAR(255) NULL,
    observaciones TEXT NULL,
    registrado_por VARCHAR(100) DEFAULT 'Sistema',
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
    INDEX idx_cliente_id (cliente_id),
    INDEX idx_fecha_pago (fecha_pago),
    INDEX idx_metodo_pago (metodo_pago)
);

-- ============================================
-- TABLA: consultas_ruc
-- ============================================
CREATE TABLE consultas_ruc (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ruc VARCHAR(11) NOT NULL,
    respuesta_api JSON,
    estado_consulta ENUM('exitosa', 'error', 'no_encontrado') NOT NULL,
    fecha_consulta TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_origen VARCHAR(45) NULL,
    INDEX idx_ruc (ruc),
    INDEX idx_fecha_consulta (fecha_consulta)
);

-- ============================================
-- TABLA: configuracion
-- ============================================
CREATE TABLE configuracion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(100) NOT NULL UNIQUE,
    valor TEXT NOT NULL,
    descripcion VARCHAR(255) NULL,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================
-- TABLA: logs_sistema
-- ============================================
CREATE TABLE logs_sistema (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nivel ENUM('info', 'warning', 'error', 'debug') NOT NULL,
    modulo VARCHAR(50) NOT NULL,
    mensaje TEXT NOT NULL,
    datos_adicionales JSON NULL,
    fecha_log TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_nivel (nivel),
    INDEX idx_modulo (modulo),
    INDEX idx_fecha_log (fecha_log)
);

-- ============================================
-- INSERTAR CONFIGURACIÓN INICIAL
-- ============================================
INSERT INTO configuracion (clave, valor, descripcion) VALUES
('token_ruc', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiI2ODgiLCJodHRwOi8vc2NoZW1hcy5taWNyb3NvZnQuY29tL3dzLzIwMDgvMDYvaWRlbnRpdHkvY2xhaW1zL3JvbGUiOiJjb25zdWx0b3IifQ.3fcdt2SZXSf1qU-FTImovivjGFO71CHFvOscUgMtvIc', 'Token para API de consulta RUC'),
('token_whatsapp', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiI2ODgiLCJodHRwOi8vc2NoZW1hcy5taWNyb3NvZnQuY29tL3dzLzIwMDgvMDYvaWRlbnRpdHkvY2xhaW1zL3JvbGUiOiJjb25zdWx0b3IifQ.XGFgrP--LtcZxaGbPKd4zNqQeIzbTmsmnAP7WBtJwAs', 'Token para API de WhatsApp'),
('instancia_whatsapp', 'NTE5MDMwOTIzNzc=', 'Instancia de WhatsApp'),
('dias_anticipacion_default', '3', 'Días por defecto para notificaciones de vencimiento'),
('empresa_nombre', 'Imaginatics Perú SAC', 'Nombre de la empresa'),
('empresa_colores', '{"primario": "#2581c4", "secundario": "#f39325"}', 'Colores corporativos');

-- ============================================
-- INSERTAR DATOS DE EJEMPLO (OPCIONAL)
-- ============================================
INSERT INTO clientes (ruc, razon_social, monto, fecha_vencimiento, whatsapp, direccion, estado_sunat) VALUES
('20123456789', 'EMPRESA EJEMPLO SAC', 1500.00, '2025-08-15', '51987654321', 'AV. EJEMPLO 123, LIMA', 'ACTIVO'),
('20987654321', 'CONSULTORA DEMO EIRL', 2500.50, '2025-08-20', '51912345678', 'JR. DEMO 456, SAN ISIDRO', 'ACTIVO'),
('20555666777', 'SERVICIOS PRUEBA SRL', 850.00, '2025-07-25', '51998877665', 'CALLE PRUEBA 789, MIRAFLORES', 'ACTIVO');

-- ============================================
-- VISTAS ÚTILES
-- ============================================

-- Vista: Clientes con estado de vencimiento
CREATE VIEW v_clientes_vencimiento AS
SELECT 
    c.*,
    DATEDIFF(c.fecha_vencimiento, CURDATE()) as dias_restantes,
    CASE 
        WHEN DATEDIFF(c.fecha_vencimiento, CURDATE()) < 0 THEN 'VENCIDO'
        WHEN DATEDIFF(c.fecha_vencimiento, CURDATE()) = 0 THEN 'VENCE_HOY'
        WHEN DATEDIFF(c.fecha_vencimiento, CURDATE()) <= 3 THEN 'POR_VENCER'
        ELSE 'AL_DIA'
    END as estado_vencimiento,
    ABS(DATEDIFF(c.fecha_vencimiento, CURDATE())) as dias_absolutos
FROM clientes c 
WHERE c.activo = TRUE;

-- Vista: Resumen de envíos por cliente
CREATE VIEW v_resumen_envios AS
SELECT 
    c.id,
    c.ruc,
    c.razon_social,
    COUNT(e.id) as total_envios,
    MAX(e.fecha_envio) as ultimo_envio,
    SUM(CASE WHEN e.estado = 'enviado' THEN 1 ELSE 0 END) as envios_exitosos,
    SUM(CASE WHEN e.estado = 'error' THEN 1 ELSE 0 END) as envios_fallidos
FROM clientes c
LEFT JOIN envios_whatsapp e ON c.id = e.cliente_id
WHERE c.activo = TRUE
GROUP BY c.id, c.ruc, c.razon_social;

-- Vista: Estadísticas de pagos
CREATE VIEW v_estadisticas_pagos AS
SELECT 
    c.id,
    c.ruc,
    c.razon_social,
    c.monto as monto_original,
    COALESCE(SUM(hp.monto_pagado), 0) as total_pagado,
    (c.monto - COALESCE(SUM(hp.monto_pagado), 0)) as saldo_pendiente,
    COUNT(hp.id) as cantidad_pagos,
    MAX(hp.fecha_pago) as ultimo_pago
FROM clientes c
LEFT JOIN historial_pagos hp ON c.id = hp.cliente_id
WHERE c.activo = TRUE
GROUP BY c.id, c.ruc, c.razon_social, c.monto;

-- ============================================
-- PROCEDIMIENTOS ALMACENADOS
-- ============================================

-- Procedimiento: Registrar envío de WhatsApp
DELIMITER //
CREATE PROCEDURE sp_registrar_envio(
    IN p_cliente_id INT,
    IN p_tipo_envio VARCHAR(50),
    IN p_mensaje_texto TEXT,
    IN p_estado VARCHAR(20),
    IN p_respuesta_api TEXT,
    IN p_mensaje_error TEXT
)
BEGIN
    INSERT INTO envios_whatsapp (
        cliente_id, 
        tipo_envio, 
        mensaje_texto, 
        estado, 
        respuesta_api, 
        mensaje_error,
        imagen_generada
    ) VALUES (
        p_cliente_id, 
        p_tipo_envio, 
        p_mensaje_texto, 
        p_estado, 
        p_respuesta_api, 
        p_mensaje_error,
        TRUE
    );
    
    -- Log del sistema
    INSERT INTO logs_sistema (nivel, modulo, mensaje, datos_adicionales) 
    VALUES ('info', 'whatsapp', 'Envío registrado', JSON_OBJECT(
        'cliente_id', p_cliente_id,
        'tipo', p_tipo_envio,
        'estado', p_estado
    ));
END //

-- Procedimiento: Obtener clientes por vencer
DELIMITER //
CREATE PROCEDURE sp_clientes_por_vencer(IN p_dias_anticipacion INT)
BEGIN
    SELECT 
        c.*,
        DATEDIFF(c.fecha_vencimiento, CURDATE()) as dias_restantes,
        CASE 
            WHEN DATEDIFF(c.fecha_vencimiento, CURDATE()) < 0 THEN 'VENCIDO'
            WHEN DATEDIFF(c.fecha_vencimiento, CURDATE()) = 0 THEN 'VENCE_HOY'
            ELSE 'POR_VENCER'
        END as estado_vencimiento
    FROM clientes c 
    WHERE c.activo = TRUE 
    AND DATEDIFF(c.fecha_vencimiento, CURDATE()) <= p_dias_anticipacion
    ORDER BY c.fecha_vencimiento ASC;
END //

DELIMITER ;

-- ============================================
-- TABLA: usuarios (Sistema de Autenticación)
-- ============================================
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    celular VARCHAR(15) NOT NULL UNIQUE COMMENT 'Número de celular sin +51',
    password_hash VARCHAR(255) NOT NULL COMMENT 'Hash de la contraseña',
    nombre VARCHAR(100) NOT NULL COMMENT 'Nombre del usuario',
    activo BOOLEAN DEFAULT TRUE COMMENT 'Usuario activo',
    primera_vez BOOLEAN DEFAULT TRUE COMMENT 'Primera vez que se loguea',
    ultimo_acceso TIMESTAMP NULL COMMENT 'Último acceso al sistema',
    intentos_fallidos INT DEFAULT 0 COMMENT 'Intentos fallidos de login',
    bloqueado_hasta TIMESTAMP NULL COMMENT 'Bloqueado hasta esta fecha',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insertar usuarios predefinidos
INSERT INTO usuarios (celular, password_hash, nombre, primera_vez) VALUES 
('989613295', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Usuario Admin 1', TRUE),
('991705393', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Usuario Admin 2', TRUE);
-- Nota: El hash corresponde a 'password', se cambiará en el primer login

-- Tabla para sesiones
CREATE TABLE sesiones (
    id VARCHAR(128) PRIMARY KEY COMMENT 'ID de sesión generado',
    usuario_id INT NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    payload TEXT,
    last_activity INT NOT NULL,
    fecha_expiracion TIMESTAMP NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- ============================================
-- ÍNDICES ADICIONALES PARA OPTIMIZACIÓN
-- ============================================
CREATE INDEX idx_clientes_compuesto ON clientes(activo, fecha_vencimiento);
CREATE INDEX idx_envios_compuesto ON envios_whatsapp(cliente_id, fecha_envio, estado);
CREATE INDEX idx_sesiones_expiracion ON sesiones(fecha_expiracion);
CREATE INDEX idx_sesiones_usuario ON sesiones(usuario_id);

-- ============================================
-- COMENTARIOS DE LA ESTRUCTURA
-- ============================================
/*
TABLAS PRINCIPALES:
- clientes: Almacena la información de todos los clientes
- envios_whatsapp: Registro de todos los envíos realizados
- historial_pagos: Control de pagos realizados por cliente
- consultas_ruc: Cache de consultas realizadas a la API
- configuracion: Parámetros del sistema
- logs_sistema: Auditoría y debug

CARACTERÍSTICAS:
- Soporte para UTF-8 completo
- Índices optimizados para consultas frecuentes
- Vistas para consultas complejas comunes
- Procedimientos almacenados para operaciones frecuentes
- Sistema de logs integrado
- Configuración flexible
- Relaciones con integridad referencial
*/