# Sistema de Pagos Multi-Servicio
## Imaginatics Perú SAC

---

## 📋 Descripción General

El sistema de **Pagos Multi-Servicio** permite registrar pagos para uno o más servicios de un cliente simultáneamente, con renovación automática de fechas de vencimiento según el periodo de facturación de cada servicio.

---

## 🎯 Características Principales

### 1. **Registro de Pagos para Múltiples Servicios**
- Selección de uno o más servicios en un solo pago
- Cálculo automático del total a pagar
- Resumen visual de servicios seleccionados

### 2. **Renovación Automática**
- Actualización de `fecha_vencimiento` según periodo de facturación
- Soporte para periodos: mensual, trimestral, semestral, anual
- Cambio de estado automático a `activo` al registrar pago

### 3. **Tracking Completo**
- Historial de pagos con servicios asociados
- Campo JSON `servicios_pagados` almacena IDs de servicios
- Log detallado de cada transacción

---

## 🗄️ Estructura de Base de Datos

### Tabla: `historial_pagos`

```sql
CREATE TABLE historial_pagos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    factura_id INT NULL,
    monto_pagado DECIMAL(10,2) NOT NULL,
    fecha_pago DATE NOT NULL,
    metodo_pago ENUM('transferencia','deposito','yape','plin','efectivo','otro') NOT NULL,
    numero_operacion VARCHAR(50) NULL,
    banco VARCHAR(100) NULL,
    comprobante_ruta VARCHAR(255) NULL,
    observaciones TEXT NULL,
    registrado_por VARCHAR(100) DEFAULT 'Sistema',
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    servicios_pagados JSON NULL,  -- NUEVO: Array de IDs de servicios
    periodo_inicio DATE NULL,
    periodo_fin DATE NULL,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
);
```

### Campo: `servicios_pagados`

- **Tipo**: JSON
- **Formato**: Array de números enteros
- **Ejemplo**: `[1, 3, 7, 12]`
- **Propósito**: Almacenar IDs de `servicios_contratados` incluidos en el pago

---

## 🔧 API Backend

### Endpoint: `POST /api/clientes.php?action=registrar_pago`

#### Request Body

```json
{
  "cliente_id": 5,
  "servicios_pagados": [1, 3, 7],  // IDs de servicios_contratados
  "monto_pagado": 531.00,
  "fecha_pago": "2025-11-11",
  "metodo_pago": "transferencia",
  "numero_operacion": "001234567890",
  "banco": "BCP",
  "observaciones": "Pago mensual de 3 servicios"
}
```

#### Response Success

```json
{
  "success": true,
  "message": "Pago registrado exitosamente",
  "data": {
    "pago_id": 42,
    "servicios_actualizados": [
      {
        "servicio_id": 1,
        "nueva_fecha_vencimiento": "2025-12-11"
      },
      {
        "servicio_id": 3,
        "nueva_fecha_vencimiento": "2026-02-11"
      },
      {
        "servicio_id": 7,
        "nueva_fecha_vencimiento": "2026-05-11"
      }
    ]
  }
}
```

#### Response Error

```json
{
  "success": false,
  "error": "El monto debe ser mayor a 0"
}
```

---

## 💻 Frontend - Uso

### 1. Abrir Modal de Pago

```javascript
// Desde cualquier lugar del sistema
PagosMultiServicio.abrirModalPago(clienteId);
```

### 2. Ejemplo de Uso

```javascript
// En el modal de servicios del cliente
<button onclick="PagosMultiServicio.abrirModalPago(5)">
  💰 Registrar Pago
</button>
```

### 3. Flujo de Usuario

1. Click en **"Servicios"** de un cliente
2. Click en **"💰 Registrar Pago"**
3. Seleccionar servicios a pagar (checkboxes)
4. Ver resumen automático con totales
5. Ingresar datos del pago (monto, método, etc.)
6. Click en **"💰 Registrar Pago"**
7. Confirmación → Renovación automática

---

## 🔄 Lógica de Renovación

### Cálculo de Nueva Fecha de Vencimiento

```php
switch ($periodo) {
    case 'mensual':
        $fechaVencimientoActual->add(new DateInterval('P1M'));
        break;
    case 'trimestral':
        $fechaVencimientoActual->add(new DateInterval('P3M'));
        break;
    case 'semestral':
        $fechaVencimientoActual->add(new DateInterval('P6M'));
        break;
    case 'anual':
        $fechaVencimientoActual->add(new DateInterval('P1Y'));
        break;
}
```

### Actualización de Servicios

```sql
UPDATE servicios_contratados
SET fecha_vencimiento = ?,
    fecha_ultima_factura = ?,
    fecha_proximo_pago = ?,
    estado = 'activo',
    fecha_actualizacion = NOW()
WHERE id = ?
```

---

## 📊 Casos de Uso

### Caso 1: Pago Único para Múltiples Servicios

**Escenario**: Cliente paga S/ 531 que cubre 3 servicios.

- Servicio 1 (mensual): S/ 177 → Vence 11/12/2025
- Servicio 2 (trimestral): S/ 177 → Vence 11/02/2026
- Servicio 3 (semestral): S/ 177 → Vence 11/05/2026

**Acción**: Seleccionar los 3 servicios en un solo pago.

**Resultado**:
- 1 registro en `historial_pagos` con `servicios_pagados = [1,2,3]`
- 3 servicios con fechas actualizadas según su periodo

---

### Caso 2: Pago Parcial

**Escenario**: Cliente tiene 5 servicios pero solo paga 2.

**Acción**: Seleccionar solo los 2 servicios pagados.

**Resultado**:
- Solo los servicios seleccionados se renuevan
- Los otros 3 permanecen en su estado actual

---

### Caso 3: Servicios en Diferentes Monedas

**Escenario**: Cliente tiene servicios en PEN y USD.

**Acción**: El sistema muestra totales separados por moneda en el resumen.

**Nota**: Actualmente el campo `monto_pagado` es único, se recomienda registrar pagos separados por moneda.

---

## 🧪 Testing

### Test Manual

1. **Preparación**:
   ```sql
   -- Verificar cliente con múltiples servicios
   SELECT * FROM v_servicios_cliente WHERE cliente_id = 5;
   ```

2. **Ejecutar Pago**:
   - Abrir modal de servicios del cliente
   - Click en "💰 Registrar Pago"
   - Seleccionar 2-3 servicios
   - Completar formulario
   - Confirmar

3. **Verificar Resultados**:
   ```sql
   -- Ver último pago registrado
   SELECT * FROM historial_pagos ORDER BY id DESC LIMIT 1;

   -- Ver servicios actualizados
   SELECT id, fecha_vencimiento, estado
   FROM servicios_contratados
   WHERE id IN (1, 3, 7);
   ```

### Test de API (cURL)

```bash
curl -X POST http://pagos_imaginatics.test/api/clientes.php?action=registrar_pago \
  -H "Content-Type: application/json" \
  -d '{
    "cliente_id": 5,
    "servicios_pagados": [1, 3],
    "monto_pagado": 354.00,
    "fecha_pago": "2025-11-11",
    "metodo_pago": "transferencia",
    "banco": "BCP"
  }'
```

---

## ⚠️ Consideraciones Importantes

### 1. **Validaciones**

- Cliente debe existir y estar activo
- Al menos 1 servicio debe ser seleccionado
- Servicios deben pertenecer al cliente
- Monto debe ser mayor a 0
- Método de pago debe ser válido

### 2. **Transacciones**

- Todo el proceso usa transacciones SQL
- Si falla la actualización de algún servicio, se hace ROLLBACK completo
- Garantiza consistencia de datos

### 3. **Estados de Servicio**

- Solo servicios `activo` o `vencido` pueden ser pagados
- Servicios `cancelado` no aparecen en la lista
- Servicios `suspendido` pueden ser reactivados con un pago

### 4. **Logging**

- Cada pago se registra en `logs_sistema`
- Se almacena: cliente_id, monto, método, cantidad de servicios

---

## 📝 Mejoras Futuras

### Versión 2.0

- [ ] Soporte para múltiples monedas en un solo pago
- [ ] Cálculo de descuentos por pago adelantado
- [ ] Generación automática de recibos PDF
- [ ] Envío de recibo por WhatsApp post-pago
- [✅] Dashboard de pagos pendientes **(IMPLEMENTADO - v1.1.0)**
- [ ] Recordatorios automáticos de renovación

### Versión 3.0

- [ ] Integración con pasarelas de pago online
- [ ] Pagos recurrentes automáticos
- [ ] Portal de cliente para auto-servicio
- [ ] Reportes financieros avanzados

---

## 🐛 Troubleshooting

### Problema: "Error al cargar servicios del cliente"

**Causa**: Cliente no tiene servicios contratados o todos están cancelados.

**Solución**: Verificar que el cliente tenga servicios activos o vencidos.

```sql
SELECT * FROM servicios_contratados WHERE cliente_id = ? AND estado IN ('activo', 'vencido');
```

---

### Problema: "Error al registrar pago"

**Causa**: Puede ser error de validación o problema de transacción.

**Solución**: Revisar logs del sistema y console del navegador.

```sql
SELECT * FROM logs_sistema WHERE modulo = 'pagos' ORDER BY fecha DESC LIMIT 10;
```

---

### Problema: Fecha de vencimiento no se actualiza

**Causa**: Servicio no está en estado `activo` o `vencido`.

**Solución**: Verificar estado del servicio antes del pago.

```sql
UPDATE servicios_contratados SET estado = 'vencido' WHERE id = ?;
```

---

## 📚 Referencias

- **Archivo API**: `/api/clientes.php` (líneas 717-859)
- **Archivo Frontend**: `/js/servicios.js` (líneas 1094-1431)
- **Archivo CSS**: `/css/servicios.css` (líneas 551-605)
- **Migración DB**: `/migrations/007_agregar_servicios_pagados.sql`

---

## 👥 Contacto y Soporte

Para dudas o reportar problemas:

- **Desarrollador**: Claude Code AI
- **Empresa**: Imaginatics Perú SAC
- **Fecha**: 11 de Noviembre, 2025

---

## 📜 Changelog

### v1.1.0 - 2025-11-11
- ✅ Dashboard de Pagos Pendientes
- ✅ Filtros por urgencia (muy vencido, vencido, próximo a vencer)
- ✅ Métricas financieras en tiempo real
- ✅ Búsqueda y filtrado de servicios
- ✅ Acciones rápidas desde el dashboard
- ✅ Edición de servicios contratados
- ✅ Historial de pagos por servicio
- ✅ Mejora en selector de bancos

### v1.0.0 - 2025-11-11
- ✅ Implementación inicial
- ✅ Soporte para múltiples servicios por pago
- ✅ Renovación automática de fechas
- ✅ UI completa con resumen visual
- ✅ Backend con transacciones seguras
- ✅ Campo JSON para tracking de servicios
