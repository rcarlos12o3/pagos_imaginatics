# Guía de Pruebas - Suspensión y Cancelación de Servicios

## ✅ Funcionalidad Implementada

Se ha completado el **sistema de gestión del ciclo de vida de servicios** con suspensión, reactivación y cancelación definitiva.

---

## 🎯 Características Implementadas

### 1. **Suspensión de Servicios** ⏸️
- Permite suspender temporalmente un servicio activo
- Requiere motivo obligatorio
- Servicio pasa a estado `suspendido`
- Se puede reactivar posteriormente
- Tarjeta muestra fondo amarillo

### 2. **Reactivación de Servicios** ▶️
- Permite reactivar servicios suspendidos
- Opción de extender fecha de vencimiento
- Servicio vuelve a estado `activo`
- Se pueden enviar órdenes de pago nuevamente

### 3. **Cancelación Definitiva** ❌
- Cancelación permanente e irreversible
- Requiere doble confirmación
- Requiere motivo obligatorio
- Servicio pasa a estado `cancelado`
- Se mantiene en historial
- Tarjeta muestra fondo rojo
- No se puede reactivar

---

## 🔄 Ciclo de Vida del Servicio

```
┌─────────┐
│ ACTIVO  │ ← Estado inicial
└────┬────┘
     │
     ├─── ⏸️ Suspender ──→ ┌────────────┐
     │                      │ SUSPENDIDO │
     │                      └──────┬─────┘
     │                             │
     │                ▶️ Reactivar ┘
     │
     └─── ❌ Cancelar ──→ ┌───────────┐
                           │ CANCELADO │ (Estado final)
                           └───────────┘
```

---

## 🧪 Casos de Prueba

### Test 1: Suspender Servicio Activo

**Objetivo:** Suspender temporalmente un servicio

**Pasos:**
1. Abrir servicios de un cliente
2. Localizar un servicio en estado **ACTIVO**
3. Click en el botón **"⏸️ Suspender"**
4. Se abre modal solicitando motivo
5. Escribir motivo: "Cliente solicitó suspensión temporal"
6. Click en "Confirmar"
7. Confirmar en el diálogo: "¿Está seguro...?"

**Resultado esperado:**
- ✅ Mensaje: "Servicio suspendido exitosamente"
- ✅ Modal se recarga automáticamente
- ✅ Servicio ahora muestra estado **"Suspendido"**
- ✅ Tarjeta tiene fondo amarillo
- ✅ Botones cambian a: Detalle, Reactivar, Cancelar
- ✅ Botón "Enviar Orden" ya no aparece

**Verificar en BD:**
```sql
SELECT id, estado, motivo_suspension, fecha_suspension
FROM servicios_contratados
WHERE id = [ID_SERVICIO];
```

Debería mostrar:
- `estado` = 'suspendido'
- `motivo_suspension` = "Cliente solicitó suspensión temporal"
- `fecha_suspension` = fecha actual

---

### Test 2: Reactivar Servicio (Sin Extender Fecha)

**Objetivo:** Reactivar servicio manteniendo fecha original

**Pasos:**
1. Tener un servicio **SUSPENDIDO**
2. Click en botón **"▶️ Reactivar"**
3. En el diálogo: "¿Desea extender la fecha de vencimiento?"
4. Click en **"NO"** (o "Cancelar")
5. Se procesa la reactivación

**Resultado esperado:**
- ✅ Mensaje: "Servicio reactivado exitosamente"
- ✅ Servicio vuelve a estado **"Activo"**
- ✅ Tarjeta vuelve a fondo blanco
- ✅ Botones: Detalle, Enviar, Suspender, Cancelar
- ✅ Fecha de vencimiento **NO cambió**

**Verificar en BD:**
```sql
SELECT id, estado, fecha_vencimiento, motivo_suspension
FROM servicios_contratados
WHERE id = [ID_SERVICIO];
```

Debería mostrar:
- `estado` = 'activo'
- `fecha_vencimiento` = (fecha original, sin cambios)
- `motivo_suspension` = NULL

---

### Test 3: Reactivar Servicio (Extendiendo Fecha)

**Objetivo:** Reactivar servicio con nueva fecha de vencimiento

**Pasos:**
1. Tener un servicio **SUSPENDIDO**
2. Click en botón **"▶️ Reactivar"**
3. En el diálogo: "¿Desea extender la fecha de vencimiento?"
4. Click en **"SÍ"** (o "Aceptar")
5. Se abre modal de selección de fecha
6. Seleccionar nueva fecha (ej: 30 días en el futuro)
7. Click en "Confirmar"

**Resultado esperado:**
- ✅ Servicio reactivado
- ✅ Fecha de vencimiento **actualizada** a la seleccionada
- ✅ Estado = "Activo"

**Verificar en BD:**
```sql
SELECT id, estado, fecha_vencimiento
FROM servicios_contratados
WHERE id = [ID_SERVICIO];
```

- `estado` = 'activo'
- `fecha_vencimiento` = (nueva fecha seleccionada)

---

### Test 4: Cancelar Servicio Activo

**Objetivo:** Cancelación definitiva de un servicio

**Pasos:**
1. Tener un servicio **ACTIVO**
2. Click en botón **"❌ Cancelar"**
3. Lee la advertencia: "⚠️ ADVERTENCIA: CANCELACIÓN DEFINITIVA"
4. Click en **"Aceptar"**
5. Se abre modal solicitando motivo
6. Escribir motivo: "Cliente dio de baja el servicio"
7. Click en "Confirmar"

**Resultado esperado:**
- ✅ Mensaje: "Servicio cancelado exitosamente"
- ✅ Estado = **"Cancelado"**
- ✅ Tarjeta con fondo rojo
- ✅ Solo aparece botón "📊 Historial"
- ✅ Texto: "Servicio cancelado"
- ✅ NO se puede reactivar

**Verificar en BD:**
```sql
SELECT id, estado, motivo_cancelacion, fecha_cancelacion
FROM servicios_contratados
WHERE id = [ID_SERVICIO];
```

- `estado` = 'cancelado'
- `motivo_cancelacion` = "Cliente dio de baja el servicio"
- `fecha_cancelacion` = fecha actual

---

### Test 5: Cancelar Servicio Suspendido

**Objetivo:** Verificar que se puede cancelar desde estado suspendido

**Pasos:**
1. Tener un servicio **SUSPENDIDO**
2. Click en **"❌ Cancelar"**
3. Confirmar advertencia
4. Ingresar motivo: "Suspensión se volvió permanente"
5. Confirmar

**Resultado esperado:**
- ✅ Cancelación exitosa
- ✅ Pasa directo de `suspendido` → `cancelado`
- ✅ Se guardan ambos motivos (suspensión y cancelación)

---

### Test 6: Intentar Cancelar sin Motivo

**Objetivo:** Validar que el motivo es obligatorio

**Pasos:**
1. Click en **"❌ Cancelar"**
2. Confirmar advertencia
3. En el modal de motivo, dejar campo vacío
4. Click en "Confirmar"

**Resultado esperado:**
- ✅ Alert: "Por favor ingrese un motivo"
- ✅ Modal NO se cierra
- ✅ Servicio NO se cancela

---

### Test 7: Cancelar Modal de Motivo

**Objetivo:** Verificar que se puede cancelar la acción

**Pasos:**
1. Click en **"⏸️ Suspender"** o **"❌ Cancelar"**
2. En el modal de motivo, click en **"Cancelar"** o **"X"**

**Resultado esperado:**
- ✅ Modal se cierra
- ✅ Acción NO se ejecuta
- ✅ Servicio mantiene estado original

---

### Test 8: Múltiples Operaciones en Cascada

**Objetivo:** Probar secuencia completa del ciclo de vida

**Pasos:**
1. Servicio ACTIVO → **Suspender** → Servicio SUSPENDIDO
2. Servicio SUSPENDIDO → **Reactivar** → Servicio ACTIVO
3. Servicio ACTIVO → **Suspender** → Servicio SUSPENDIDO
4. Servicio SUSPENDIDO → **Cancelar** → Servicio CANCELADO

**Resultado esperado:**
- ✅ Todas las transiciones funcionan correctamente
- ✅ Cada estado muestra los botones apropiados
- ✅ Histórico de cambios se registra en BD

**Verificar en BD:**
```sql
SELECT
    id,
    estado,
    motivo_suspension,
    fecha_suspension,
    motivo_cancelacion,
    fecha_cancelacion
FROM servicios_contratados
WHERE id = [ID_SERVICIO];
```

---

### Test 9: Resumen Financiero Actualizado

**Objetivo:** Verificar que suspensión/cancelación afecta resumen

**Pasos:**
1. Cliente tiene 3 servicios activos
2. Suspender 1 servicio
3. Observar resumen en el modal

**Resultado esperado:**
- Total Servicios: 3 (no cambia)
- Servicios Activos: 2 (disminuyó)
- Monto Mensual: Reducido (no incluye el suspendido)

---

### Test 10: Enviar Orden de Pago a Servicio Suspendido

**Objetivo:** Verificar que servicios suspendidos no pueden recibir órdenes

**Pasos:**
1. Suspender un servicio
2. Intentar usar función de envío masivo

**Resultado esperado:**
- ✅ Servicio suspendido NO debe incluirse en envíos automáticos
- ✅ Solo servicios ACTIVOS reciben órdenes

---

## 🎨 Indicadores Visuales

### Estados y Colores:

| Estado | Color de Fondo | Borde | Badge |
|--------|---------------|-------|-------|
| **Activo** | Blanco | Gris | Verde (AL_DIA) |
| **Suspendido** | Amarillo claro (#fff3cd) | Naranja (izq) | Amarillo (SUSPENDIDO) |
| **Cancelado** | Rojo claro (#f8d7da) | Rojo (izq) | Rojo (CANCELADO) |

### Botones Disponibles:

| Estado | Botones Visibles |
|--------|------------------|
| **Activo** | 📊 Detalle, 📤 Enviar, ⏸️ Suspender, ❌ Cancelar |
| **Suspendido** | 📊 Detalle, ▶️ Reactivar, ❌ Cancelar |
| **Cancelado** | 📊 Historial + Texto "Servicio cancelado" |

---

## 📊 Verificación en Base de Datos

### Campos Relevantes:

```sql
-- Ver estado actual de servicios
SELECT
    c.razon_social,
    cs.nombre as servicio,
    sc.estado,
    sc.motivo_suspension,
    sc.fecha_suspension,
    sc.motivo_cancelacion,
    sc.fecha_cancelacion
FROM servicios_contratados sc
JOIN clientes c ON sc.cliente_id = c.id
JOIN catalogo_servicios cs ON sc.servicio_id = cs.id
ORDER BY sc.estado, c.razon_social;
```

### Conteo por Estado:

```sql
SELECT
    estado,
    COUNT(*) as cantidad
FROM servicios_contratados
GROUP BY estado;
```

Resultado esperado:
```
+-----------+----------+
| estado    | cantidad |
+-----------+----------+
| activo    | XX       |
| suspendido| XX       |
| cancelado | XX       |
+-----------+----------+
```

---

## 🐛 Posibles Errores y Soluciones

### Error: "Cannot read property 'dataset' of null"

**Causa:** Modal no tiene el atributo `data-cliente-id`

**Solución:** Verificar que el modal tenga:
```html
<div class="modal-overlay" id="modalServiciosCliente" data-cliente-id="[ID]">
```

---

### Error: No se recarga el modal después de la acción

**Causa:** No se encuentra el clienteId para recargar

**Solución:** Asegurarse que todas las funciones busquen el clienteId del modal:
```javascript
const modal = document.getElementById('modalServiciosCliente');
const clienteId = modal.dataset.clienteId;
```

---

### Error: Botón "window.modalMotivoResolve is not a function"

**Causa:** Función resolve no se guardó correctamente

**Solución:** Verificar que se asigna antes de mostrar el modal:
```javascript
window.modalMotivoResolve = resolve;
```

---

## ✨ Mejoras Futuras Opcionales

1. **Historial de Estados**
   - Tabla separada para registrar cada cambio de estado
   - Ver timeline completo del servicio

2. **Suspensiones Automáticas**
   - Suspender automáticamente si pasan X días sin pago

3. **Reactivación Programada**
   - Configurar fecha futura para reactivación automática

4. **Notificaciones**
   - Enviar WhatsApp cuando se suspende
   - Recordatorio antes de cancelación definitiva

5. **Reportes**
   - Dashboard de servicios suspendidos/cancelados
   - Razones más comunes de cancelación

---

## 🎉 Resultado Final

Después de estas pruebas, deberías tener:

- ✅ Servicios en estado ACTIVO funcionando normalmente
- ✅ Servicios SUSPENDIDOS con indicador visual
- ✅ Servicios CANCELADOS en historial
- ✅ Sistema completo de gestión del ciclo de vida
- ✅ Registros de motivos en base de datos
- ✅ Interfaz clara y fácil de usar

---

**Documentación creada:** 2025-01-10
**Sistema:** Multi-Servicio v1.0 - Gestión de Ciclo de Vida
**Autor:** Claude Code (Anthropic)
