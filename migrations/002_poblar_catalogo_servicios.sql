-- ============================================
-- MIGRACIÓN 002: Poblar Catálogo de Servicios
-- Fecha: 2025-11-05
-- Descripción: Insertar servicios iniciales en el catálogo
-- ============================================

USE imaginatics_ruc;

-- ============================================
-- SERVICIOS DE FACTURACIÓN ELECTRÓNICA (PEN)
-- ============================================

-- Plan Básico
INSERT INTO catalogo_servicios (nombre, descripcion, categoria, precio_base, moneda, periodos_disponibles, orden_visualizacion)
VALUES
    ('Facturación Electrónica - Plan Básico Mensual', 'Plan básico de facturación electrónica con cobro mensual', 'software', 77.00, 'PEN', '["mensual"]', 1),
    ('Facturación Electrónica - Plan Básico Trimestral', 'Plan básico de facturación electrónica con cobro trimestral', 'software', 231.00, 'PEN', '["trimestral"]', 2),
    ('Facturación Electrónica - Plan Básico Semestral', 'Plan básico de facturación electrónica con cobro semestral', 'software', 354.00, 'PEN', '["semestral"]', 3),
    ('Facturación Electrónica - Plan Básico Anual', 'Plan básico de facturación electrónica con cobro anual', 'software', 770.00, 'PEN', '["anual"]', 4),
    ('Facturación Electrónica - Plan Básico 2 Anual', 'Plan básico 2 de facturación electrónica con cobro anual', 'software', 924.00, 'PEN', '["anual"]', 5);

-- Plan Premium
INSERT INTO catalogo_servicios (nombre, descripcion, categoria, precio_base, moneda, periodos_disponibles, orden_visualizacion)
VALUES
    ('Facturación Electrónica - Plan Premium Mensual', 'Plan premium de facturación electrónica con cobro mensual', 'software', 118.00, 'PEN', '["mensual"]', 10),
    ('Facturación Electrónica - Plan Premium Trimestral', 'Plan premium de facturación electrónica con cobro trimestral', 'software', 354.00, 'PEN', '["trimestral"]', 11),
    ('Facturación Electrónica - Plan Premium Semestral', 'Plan premium de facturación electrónica con cobro semestral', 'software', 590.00, 'PEN', '["semestral"]', 12),
    ('Facturación Electrónica - Plan Premium Anual', 'Plan premium de facturación electrónica con cobro anual', 'software', 1180.00, 'PEN', '["anual"]', 13);

-- Plan Pro
INSERT INTO catalogo_servicios (nombre, descripcion, categoria, precio_base, moneda, periodos_disponibles, orden_visualizacion)
VALUES
    ('Facturación Electrónica - Plan Pro Mensual', 'Plan pro de facturación electrónica con cobro mensual', 'software', 150.00, 'PEN', '["mensual"]', 20),
    ('Facturación Electrónica - Plan Pro Trimestral', 'Plan pro de facturación electrónica con cobro trimestral', 'software', 450.00, 'PEN', '["trimestral"]', 21),
    ('Facturación Electrónica - Plan Pro Semestral', 'Plan pro de facturación electrónica con cobro semestral', 'software', 900.00, 'PEN', '["semestral"]', 22),
    ('Facturación Electrónica - Plan Pro Anual', 'Plan pro de facturación electrónica con cobro anual', 'software', 1416.00, 'PEN', '["anual"]', 23);

-- Plan Pro Max
INSERT INTO catalogo_servicios (nombre, descripcion, categoria, precio_base, moneda, periodos_disponibles, orden_visualizacion)
VALUES
    ('Facturación Electrónica - Plan Pro Max Mensual', 'Plan pro max de facturación electrónica con cobro mensual', 'software', 177.00, 'PEN', '["mensual"]', 30),
    ('Facturación Electrónica - Plan Pro Max Trimestral', 'Plan pro max de facturación electrónica con cobro trimestral', 'software', 531.00, 'PEN', '["trimestral"]', 31),
    ('Facturación Electrónica - Plan Pro Max Semestral', 'Plan pro max de facturación electrónica con cobro semestral', 'software', 1062.00, 'PEN', '["semestral"]', 32),
    ('Facturación Electrónica - Plan Pro Max Anual', 'Plan pro max de facturación electrónica con cobro anual', 'software', 1770.00, 'PEN', '["anual"]', 33);

-- ============================================
-- CERTIFICADOS DIGITALES (USD)
-- ============================================

INSERT INTO catalogo_servicios (nombre, descripcion, categoria, precio_base, moneda, periodos_disponibles, orden_visualizacion, configuracion_default)
VALUES
    ('Certificado Digital Anual', 'Certificado digital válido por 1 año', 'certificados', 100.00, 'USD', '["anual"]', 100,
     JSON_OBJECT('vigencia_anios', 1, 'tipo', 'persona_natural')),
    ('Certificado Digital 3 Años', 'Certificado digital válido por 3 años', 'certificados', 270.00, 'USD', '["anual"]', 101,
     JSON_OBJECT('vigencia_anios', 3, 'tipo', 'persona_natural'));

-- ============================================
-- CORREO CORPORATIVO (USD)
-- ============================================

INSERT INTO catalogo_servicios (nombre, descripcion, categoria, precio_base, moneda, periodos_disponibles, orden_visualizacion, configuracion_default)
VALUES
    ('Correo Corporativo', 'Servicio de correo corporativo profesional', 'correo', 108.00, 'USD', '["anual"]', 200,
     JSON_OBJECT('capacidad_gb', 50, 'usuarios_incluidos', 1, 'soporte', '24/7'));

-- ============================================
-- DOMINIOS (PEN)
-- ============================================

INSERT INTO catalogo_servicios (nombre, descripcion, categoria, precio_base, moneda, periodos_disponibles, orden_visualizacion, configuracion_default)
VALUES
    ('Dominio .com', 'Registro y renovación de dominio .com', 'dominio', 200.00, 'PEN', '["anual"]', 300,
     JSON_OBJECT('extension', '.com', 'incluye_privacidad', true, 'dns_incluido', true));

-- ============================================
-- INTERNET (PEN)
-- ============================================

INSERT INTO catalogo_servicios (nombre, descripcion, categoria, precio_base, moneda, periodos_disponibles, orden_visualizacion, configuracion_default)
VALUES
    ('Internet Starlink', 'Servicio de internet satelital Starlink', 'internet', 140.00, 'PEN', '["mensual"]', 400,
     JSON_OBJECT('tipo', 'satelital', 'velocidad_mbps', 200, 'latencia_ms', '20-40', 'equipo_incluido', false));

-- ============================================
-- VERIFICAR INSERCIONES
-- ============================================

-- Resumen por categoría
SELECT
    categoria,
    moneda,
    COUNT(*) as total_servicios,
    MIN(precio_base) as precio_minimo,
    MAX(precio_base) as precio_maximo
FROM catalogo_servicios
GROUP BY categoria, moneda
ORDER BY categoria, moneda;

-- Resumen general
SELECT
    COUNT(*) as total_servicios_catalogo,
    SUM(CASE WHEN moneda = 'PEN' THEN 1 ELSE 0 END) as servicios_soles,
    SUM(CASE WHEN moneda = 'USD' THEN 1 ELSE 0 END) as servicios_dolares,
    COUNT(DISTINCT categoria) as total_categorias
FROM catalogo_servicios;

-- Log de migración
INSERT INTO logs_sistema (nivel, modulo, mensaje, datos_adicionales)
VALUES ('info', 'migracion', 'Migración 002: Catálogo de servicios poblado exitosamente',
    JSON_OBJECT(
        'version', '002',
        'fecha', NOW(),
        'total_servicios', (SELECT COUNT(*) FROM catalogo_servicios),
        'servicios_pen', (SELECT COUNT(*) FROM catalogo_servicios WHERE moneda = 'PEN'),
        'servicios_usd', (SELECT COUNT(*) FROM catalogo_servicios WHERE moneda = 'USD')
    ));

SELECT '✓ Catálogo de servicios poblado exitosamente' as resultado,
       (SELECT COUNT(*) FROM catalogo_servicios) as total_servicios,
       (SELECT COUNT(*) FROM catalogo_servicios WHERE moneda = 'PEN') as servicios_PEN,
       (SELECT COUNT(*) FROM catalogo_servicios WHERE moneda = 'USD') as servicios_USD;
