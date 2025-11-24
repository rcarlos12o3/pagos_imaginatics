# SINCRONIZACIÓN LOCAL → PRODUCCIÓN
**Fecha:** 23 de Noviembre 2025
**Autor:** Claude Local

---

## RESUMEN DE CAMBIOS

Se implementaron mejoras para:
1. **Centralizar reglas de días de anticipación** por periodicidad (eliminar hardcoding)
2. **Vincular envíos con servicios específicos** para mejor trazabilidad
3. **Validar que recordatorios solo se envíen después de una OP**
4. **Corregir campos legacy** en tabla clientes

---

## PASO 1: Corregir campos legacy en tabla clientes

```sql
ALTER TABLE clientes
  MODIFY COLUMN monto DECIMAL(10,2) NULL DEFAULT NULL,
  MODIFY COLUMN fecha_vencimiento DATE NULL DEFAULT NULL;
```

**Contexto:** Estos campos son del sistema antiguo. Ahora los montos y fechas se manejan en `servicios_contratados`.

---

## PASO 2: Agregar campo servicio_contratado_id a cola_envios

```sql
-- Verificar si ya existe el campo
SHOW COLUMNS FROM cola_envios LIKE 'servicio_contratado_id';

-- Si NO existe, ejecutar:
ALTER TABLE cola_envios
ADD COLUMN servicio_contratado_id INT NULL AFTER cliente_id,
ADD INDEX idx_servicio_contratado (servicio_contratado_id),
ADD CONSTRAINT fk_cola_servicio FOREIGN KEY (servicio_contratado_id)
    REFERENCES servicios_contratados(id) ON DELETE SET NULL;
```

---

## PASO 3: Crear tabla de reglas centralizadas

```sql
CREATE TABLE IF NOT EXISTS reglas_recordatorio_periodicidad (
    id INT AUTO_INCREMENT PRIMARY KEY,
    periodo_facturacion ENUM('mensual','trimestral','semestral','anual') UNIQUE NOT NULL,
    dias_anticipacion_op INT NOT NULL COMMENT 'Días antes del vencimiento para enviar Orden de Pago',
    dias_anticipacion_recordatorio INT DEFAULT 3 COMMENT 'Días después de OP para primer recordatorio',
    dias_urgente INT DEFAULT 3 COMMENT 'Días antes de vencer para marcar como urgente',
    dias_critico INT DEFAULT 1 COMMENT 'Días antes de vencer para marcar como crítico',
    max_dias_mora INT DEFAULT 30 COMMENT 'Máximo días de mora para seguir enviando recordatorios',
    descripcion VARCHAR(255) NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar reglas
INSERT INTO reglas_recordatorio_periodicidad
    (periodo_facturacion, dias_anticipacion_op, dias_urgente, dias_critico, descripcion)
VALUES
    ('mensual', 4, 2, 1, 'Servicios mensuales: OP 4 días antes'),
    ('trimestral', 7, 3, 1, 'Servicios trimestrales: OP 7 días antes'),
    ('semestral', 15, 5, 2, 'Servicios semestrales: OP 15 días antes'),
    ('anual', 30, 7, 3, 'Servicios anuales: OP 30 días antes')
ON DUPLICATE KEY UPDATE
    dias_anticipacion_op = VALUES(dias_anticipacion_op),
    descripcion = VALUES(descripcion);
```

---

## PASO 4: Actualizar vista de recordatorios pendientes

```sql
DROP VIEW IF EXISTS v_recordatorios_pendientes_hoy;

CREATE VIEW v_recordatorios_pendientes_hoy AS
SELECT
    c.id,
    c.ruc,
    c.razon_social,
    c.whatsapp,
    sc.id as servicio_contratado_id,
    cs.nombre as servicio_nombre,
    sc.precio AS monto,
    sc.moneda,
    sc.fecha_vencimiento,
    sc.periodo_facturacion AS periodicidad,
    DATEDIFF(sc.fecha_vencimiento, CURDATE()) AS dias_restantes,
    r.dias_anticipacion_op,
    r.dias_urgente,
    r.dias_critico,
    CASE
        WHEN DATEDIFF(sc.fecha_vencimiento, CURDATE()) < 0 THEN 'vencido'
        WHEN DATEDIFF(sc.fecha_vencimiento, CURDATE()) <= r.dias_critico THEN 'critico'
        WHEN DATEDIFF(sc.fecha_vencimiento, CURDATE()) <= r.dias_urgente THEN 'urgente'
        ELSE 'preventivo'
    END as tipo_recordatorio,
    (SELECT MAX(ew.fecha_envio)
     FROM envios_whatsapp ew
     WHERE ew.cliente_id = c.id
     AND ew.servicio_contratado_id = sc.id
     AND ew.tipo_envio = 'orden_pago'
     AND ew.estado = 'enviado'
    ) as fecha_ultima_op,
    COALESCE(
        (SELECT COUNT(*)
         FROM historial_recordatorios hr
         WHERE hr.cliente_id = c.id
         AND hr.servicio_id = sc.id
         AND hr.fecha_envio >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ), 0
    ) as recordatorios_este_mes,
    (SELECT MAX(hr.fecha_envio)
     FROM historial_recordatorios hr
     WHERE hr.cliente_id = c.id
     AND hr.servicio_id = sc.id
    ) as ultimo_recordatorio
FROM clientes c
INNER JOIN servicios_contratados sc ON c.id = sc.cliente_id
INNER JOIN catalogo_servicios cs ON sc.servicio_id = cs.id
INNER JOIN reglas_recordatorio_periodicidad r ON sc.periodo_facturacion = r.periodo_facturacion
WHERE sc.estado IN ('activo', 'vencido')
AND c.activo = 1
AND DATEDIFF(sc.fecha_vencimiento, CURDATE()) BETWEEN -r.max_dias_mora AND r.dias_anticipacion_op
AND EXISTS (
    SELECT 1 FROM envios_whatsapp ew
    WHERE ew.cliente_id = c.id
    AND ew.servicio_contratado_id = sc.id
    AND ew.tipo_envio = 'orden_pago'
    AND ew.estado = 'enviado'
    AND ew.fecha_envio >= (
        CASE sc.periodo_facturacion
            WHEN 'mensual' THEN DATE_SUB(sc.fecha_vencimiento, INTERVAL 1 MONTH)
            WHEN 'trimestral' THEN DATE_SUB(sc.fecha_vencimiento, INTERVAL 3 MONTH)
            WHEN 'semestral' THEN DATE_SUB(sc.fecha_vencimiento, INTERVAL 6 MONTH)
            WHEN 'anual' THEN DATE_SUB(sc.fecha_vencimiento, INTERVAL 1 YEAR)
            ELSE DATE_SUB(sc.fecha_vencimiento, INTERVAL 1 MONTH)
        END
    )
)
AND NOT EXISTS (
    SELECT 1 FROM historial_recordatorios hr
    WHERE hr.cliente_id = c.id
    AND hr.servicio_id = sc.id
    AND hr.fecha_envio >= DATE_SUB(CURDATE(), INTERVAL 3 DAY)
    AND hr.estado_envio = 'enviado'
)
ORDER BY sc.fecha_vencimiento ASC;
```

---

## PASO 5: Actualizar archivos PHP

### 5.1 En `api/envios.php` - Función `crearSesionCola()`

Buscar (~línea 1403):
```php
INSERT INTO cola_envios (
    sesion_id, cliente_id, tipo_envio, prioridad,
```

Cambiar a:
```php
INSERT INTO cola_envios (
    sesion_id, cliente_id, servicio_contratado_id, tipo_envio, prioridad,
```

Y en los VALUES agregar:
```php
$cliente['contrato_id'] ?? null, // Vincular con servicio específico
```

### 5.2 En `api/procesar_cola.php` - Registro de envío

Buscar (~línea 244):
```php
INSERT INTO envios_whatsapp
(cliente_id, numero_destino, tipo_envio, estado, respuesta_api, imagen_generada, mensaje_texto)
VALUES (?, ?, ?, 'enviado', ?, ?, ?)
```

Cambiar a:
```php
INSERT INTO envios_whatsapp
(cliente_id, servicio_contratado_id, numero_destino, tipo_envio, estado, respuesta_api, imagen_generada, mensaje_texto)
VALUES (?, ?, ?, ?, 'enviado', ?, ?, ?)
```

Y agregar en el array de parámetros:
```php
$trabajo['servicio_contratado_id'] ?? null,
```

### 5.3 En `api/clientes.php` - Función `analizarServicio()`

Buscar el switch hardcodeado (~línea 1446):
```php
// Determinar días de anticipación según periodicidad
$diasAnticipacion = 0;
switch ($periodo) {
    case 'mensual':
        $diasAnticipacion = 4;
        break;
    case 'trimestral':
        $diasAnticipacion = 7;
        break;
    case 'semestral':
        $diasAnticipacion = 15;
        break;
    case 'anual':
        $diasAnticipacion = 30;
        break;
}
```

Reemplazar por:
```php
// Obtener días de anticipación desde tabla de reglas (centralizado)
$regla = $database->fetch(
    "SELECT dias_anticipacion_op, dias_urgente, dias_critico, max_dias_mora
     FROM reglas_recordatorio_periodicidad
     WHERE periodo_facturacion = ?",
    [$periodo]
);
$diasAnticipacion = $regla ? (int)$regla['dias_anticipacion_op'] : 7; // Default 7 si no existe
```

### 5.4 En `index.php` - Botón editar servicio

Buscar:
```javascript
onclick="editarServicioUsuario(${servicio.contrato_id})"
```

Cambiar a:
```javascript
onclick="ServiciosUI.editarServicio(${servicio.contrato_id})"
```

Y eliminar las funciones `editarServicioUsuario`, `cerrarModalEditarServicioUsuario` y `guardarEdicionServicioUsuario` si existen (son código duplicado).

---

## PASO 6: Verificar que todo funciona

```sql
-- Verificar tabla de reglas
SELECT * FROM reglas_recordatorio_periodicidad;

-- Verificar vista (debe retornar vacío si no hay OPs enviadas con servicio_contratado_id)
SELECT * FROM v_recordatorios_pendientes_hoy LIMIT 5;

-- Verificar estructura cola_envios
DESCRIBE cola_envios;
```

---

## NOTAS IMPORTANTES

1. **Es retroactivo**: Los cambios aplican inmediatamente a todos los clientes existentes

2. **Envíos anteriores**: Los que no tienen `servicio_contratado_id` seguirán funcionando, pero no se vincularán para la validación de "OP antes de recordatorio"

3. **Cambiar reglas en el futuro**: Solo ejecutar:
   ```sql
   UPDATE reglas_recordatorio_periodicidad
   SET dias_anticipacion_op = 5
   WHERE periodo_facturacion = 'mensual';
   ```

4. **No requiere reinicio**: Los cambios en BD y vistas aplican inmediatamente

---

## ARCHIVOS DE MIGRACIÓN CREADOS EN LOCAL

- `migrations/008_validar_op_antes_recordatorio.sql`
- `migrations/009_agregar_servicio_contratado_id_cola.sql`
- `migrations/010_reglas_periodicidad_centralizadas.sql`

Puedes hacer `git pull` y ejecutar las migraciones en orden, o aplicar los SQLs de este documento directamente.
