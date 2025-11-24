-- ============================================
-- MIGRACIÓN 011: Corrección Directa de Encoding
-- Reemplazar nombres corruptos con versiones correctas
-- SEGURO: Solo actualiza nombres, NO elimina datos
-- ============================================

USE imaginatics_ruc;

-- Corregir nombres de servicios uno por uno
UPDATE catalogo_servicios SET nombre = 'Facturación Electrónica - Plan Básico Mensual' WHERE id = 1;
UPDATE catalogo_servicios SET nombre = 'Facturación Electrónica - Plan Básico Trimestral' WHERE id = 2;
UPDATE catalogo_servicios SET nombre = 'Facturación Electrónica - Plan Básico Semestral' WHERE id = 3;
UPDATE catalogo_servicios SET nombre = 'Facturación Electrónica - Plan Básico Anual' WHERE id = 4;
UPDATE catalogo_servicios SET nombre = 'Facturación Electrónica - Plan Básico 2 Anual' WHERE id = 5;
UPDATE catalogo_servicios SET nombre = 'Facturación Electrónica - Plan Premium Mensual' WHERE id = 6;
UPDATE catalogo_servicios SET nombre = 'Facturación Electrónica - Plan Premium Trimestral' WHERE id = 7;
UPDATE catalogo_servicios SET nombre = 'Facturación Electrónica - Plan Premium Semestral' WHERE id = 8;
UPDATE catalogo_servicios SET nombre = 'Facturación Electrónica - Plan Premium Anual' WHERE id = 9;
UPDATE catalogo_servicios SET nombre = 'Facturación Electrónica - Plan Pro Mensual' WHERE id = 10;
UPDATE catalogo_servicios SET nombre = 'Facturación Electrónica - Plan Pro Trimestral' WHERE id = 11;
UPDATE catalogo_servicios SET nombre = 'Facturación Electrónica - Plan Pro Semestral' WHERE id = 12;
UPDATE catalogo_servicios SET nombre = 'Facturación Electrónica - Plan Pro Anual' WHERE id = 13;
UPDATE catalogo_servicios SET nombre = 'Facturación Electrónica - Plan Pro Max Mensual' WHERE id = 14;
UPDATE catalogo_servicios SET nombre = 'Facturación Electrónica - Plan Pro Max Trimestral' WHERE id = 15;
UPDATE catalogo_servicios SET nombre = 'Facturación Electrónica - Plan Pro Max Semestral' WHERE id = 16;
UPDATE catalogo_servicios SET nombre = 'Facturación Electrónica - Plan Pro Max Anual' WHERE id = 17;

SELECT 'Servicios corregidos exitosamente' as resultado;
SELECT id, nombre FROM catalogo_servicios LIMIT 5;
