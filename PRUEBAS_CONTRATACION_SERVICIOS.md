# Guía de Pruebas - Contratación de Servicios

## ✅ Funcionalidad Implementada

Se ha completado el **sistema de contratación de servicios** desde el frontend. El usuario puede ahora contratar nuevos servicios a sus clientes de forma interactiva.

---

## 🎯 Flujo de Contratación

### 1. Acceder al Modal de Servicios
1. En el dashboard principal (index.php)
2. Localizar un cliente en la lista
3. Hacer clic en el botón **"🛠️ Servicios"**
4. Se abre el modal con los servicios actuales del cliente

### 2. Iniciar Contratación
1. Dentro del modal de servicios
2. Hacer clic en el botón **"Agregar Servicio"** (esquina inferior derecha)
3. Se abre el modal de contratación

### 3. Llenar el Formulario
El formulario tiene **validación dinámica** y **cálculo automático**:

#### Paso 1: Seleccionar Servicio
- **Dropdown agrupado por categorías:**
  - 📄 Certificados
  - 📧 Email
  - 🌐 Dominios
  - 🛰️ Internet
  - 💼 Software (Facturación Electrónica)

- **Información mostrada:** Nombre - Moneda Precio (periodos disponibles)
- **Ejemplo:** `Facturación Electrónica - Plan Básico Mensual - PEN 77.00 (mensual)`

#### Paso 2: Seleccionar Periodo
- Dropdown se llena automáticamente con los periodos disponibles del servicio
- Opciones: Mensual, Trimestral, Semestral, Anual
- Depende del servicio seleccionado

#### Paso 3: Revisar/Personalizar Precio
- Precio base se carga automáticamente
- **Símbolo de moneda** cambia según el servicio (S/ o $)
- Puedes **personalizar el precio** si haces descuento/incremento
- El campo se marca en **naranja** si modificas el precio base

#### Paso 4: Fecha de Inicio
- Por defecto: HOY
- Puedes cambiarla si el servicio inicia en otra fecha

#### Paso 5: Fecha de Vencimiento (AUTOMÁTICA)
- Se calcula automáticamente según:
  - Fecha de inicio + periodo seleccionado
  - **Mensual:** +1 mes
  - **Trimestral:** +3 meses
  - **Semestral:** +6 meses
  - **Anual:** +1 año
- Campo de solo lectura (no editable)

#### Paso 6: Notas (Opcional)
- Campo de texto libre
- Útil para: condiciones especiales, descuentos, observaciones, etc.

### 4. Contratar Servicio
1. El botón **"Contratar Servicio"** se habilita automáticamente cuando:
   - Servicio seleccionado ✓
   - Periodo seleccionado ✓
   - Fecha de inicio válida ✓

2. Click en "Contratar Servicio"
3. El botón cambia a **"Contratando..."** (evita doble-click)
4. Se envía la petición al API
5. Si es exitoso:
   - Cierra el modal de contratación
   - Recarga el modal de servicios con el nuevo servicio
   - Muestra mensaje: **"✅ Servicio contratado exitosamente"**

---

## 🧪 Casos de Prueba

### Test 1: Contratación Básica
**Objetivo:** Contratar un servicio con valores por defecto

1. Cliente: Cualquier cliente activo
2. Servicio: "Facturación Electrónica - Plan Básico Mensual"
3. Periodo: Mensual
4. Precio: Dejar el predeterminado (S/ 77.00)
5. Fecha inicio: HOY
6. Notas: (vacío)

**Resultado esperado:**
- ✅ Servicio contratado
- ✅ Fecha vencimiento = HOY + 1 mes
- ✅ Estado = activo
- ✅ Aparece en la lista de servicios del cliente

---

### Test 2: Contratación con Precio Personalizado
**Objetivo:** Aplicar descuento especial

1. Servicio: "Certificado Digital Anual"
2. Periodo: Anual
3. Precio original: $ 100.00
4. **Cambiar precio a:** $ 85.00 (15% descuento)
5. Observar: Campo se marca en naranja
6. Notas: "Descuento corporativo 15%"

**Resultado esperado:**
- ✅ Servicio guardado con precio personalizado
- ✅ precio_personalizado = 1 en BD
- ✅ Nota guardada correctamente

---

### Test 3: Servicio Multi-Periodo
**Objetivo:** Verificar opciones de periodo

1. Servicio: "Facturación Electrónica - Plan Básico"
2. Observar dropdown de periodos
3. Probar cada periodo:
   - Mensual → Vencimiento = +1 mes
   - Trimestral → Vencimiento = +3 meses
   - Semestral → Vencimiento = +6 meses
   - Anual → Vencimiento = +1 año

**Resultado esperado:**
- ✅ Cálculo correcto para cada periodo
- ✅ Fechas consistentes

---

### Test 4: Multi-Moneda
**Objetivo:** Verificar soporte PEN y USD

1. **Servicio en PEN:** Facturación Electrónica
   - Símbolo: S/
   - Precio: 77.00

2. **Servicio en USD:** Certificado Digital
   - Símbolo: $
   - Precio: 100.00

**Resultado esperado:**
- ✅ Símbolo cambia según servicio
- ✅ Moneda se guarda correctamente en BD

---

### Test 5: Validación de Formulario
**Objetivo:** Verificar que no se puede enviar sin datos completos

1. Abrir modal de contratación
2. NO seleccionar servicio → Botón DESHABILITADO ✓
3. Seleccionar servicio → Campos aparecen
4. NO seleccionar periodo → Botón DESHABILITADO ✓
5. Seleccionar periodo → Botón HABILITADO ✓

**Resultado esperado:**
- ✅ Validación en tiempo real funciona
- ✅ No se puede enviar formulario incompleto

---

### Test 6: Múltiples Servicios por Cliente
**Objetivo:** Verificar que un cliente puede tener varios servicios

1. Contratar "Facturación Electrónica Básico Mensual"
2. Contratar "Certificado Digital Anual"
3. Contratar "Email Corporativo"
4. Ver modal de servicios del cliente

**Resultado esperado:**
- ✅ Se muestran los 3 servicios
- ✅ Resumen financiero actualizado:
  - Total servicios: 3
  - Servicios activos: 3
  - Monto mensual: SUMA de todos (convertido a PEN)

---

## 🔍 Verificación en Base de Datos

Después de cada contratación, verificar en MySQL:

```sql
-- Ver servicios contratados del cliente
SELECT
    sc.id,
    c.razon_social,
    cs.nombre as servicio,
    sc.periodo_facturacion,
    sc.precio_acordado,
    sc.moneda,
    sc.fecha_inicio,
    sc.fecha_vencimiento,
    sc.estado,
    sc.precio_personalizado,
    sc.notas
FROM servicios_contratados sc
JOIN clientes c ON sc.cliente_id = c.id
JOIN catalogo_servicios cs ON sc.servicio_id = cs.id
WHERE c.id = [ID_CLIENTE]
ORDER BY sc.fecha_inicio DESC;
```

---

## 🐛 Errores Comunes y Soluciones

### Error: "No hay servicios disponibles en el catálogo"
**Causa:** Tabla `catalogo_servicios` vacía o sin servicios activos
**Solución:**
```sql
-- Verificar catálogo
SELECT * FROM catalogo_servicios WHERE activo = 1;

-- Si está vacío, ejecutar:
-- migrations/002_poblar_catalogo_servicios.sql
```

---

### Error: "Error al contratar servicio"
**Causa:** Problema en el API
**Solución:**
1. Ver consola del navegador (F12)
2. Ver logs de PHP: `api/servicios.php`
3. Verificar permisos de tabla `servicios_contratados`

---

### Error: Fechas incorrectas
**Causa:** Problema con zona horaria
**Solución:** Verificar que la fecha tenga formato correcto 'T00:00:00' en JavaScript

---

## 📊 Datos de Ejemplo para Pruebas

### Servicios Recomendados para Testing:

1. **Mensual:** Facturación Electrónica - Plan Básico Mensual (S/ 77.00)
2. **Trimestral:** Facturación Electrónica - Plan Básico Trimestral (S/ 220.00)
3. **Anual:** Certificado Digital Anual ($ 100.00)
4. **Multi-periodo:** Facturación Electrónica - Plan Medio (Todos los periodos)

---

## ✨ Funcionalidades Extra Implementadas

### 1. Cálculo Automático de Vencimiento
- No necesitas calcular manualmente
- Se actualiza en tiempo real al cambiar fecha o periodo

### 2. Indicador Visual de Precio Personalizado
- Borde naranja cuando modificas el precio
- Facilita identificar descuentos/incrementos

### 3. Agrupación de Servicios por Categoría
- Dropdown organizado y fácil de navegar
- Mejora la UX

### 4. Validación Progresiva
- Campos aparecen solo cuando son necesarios
- Reduce confusión

### 5. Integración Automática
- Al contratar, el modal se refresca automáticamente
- Ves el nuevo servicio inmediatamente

---

## 🎉 Resultado Final

Al completar estas pruebas, deberías tener:

- ✅ Clientes con múltiples servicios contratados
- ✅ Servicios en diferentes periodos (mensual, trimestral, anual)
- ✅ Servicios en PEN y USD
- ✅ Algunos con precios personalizados
- ✅ Resumen financiero actualizado
- ✅ Sistema listo para producción

---

## 📝 Próximos Pasos Sugeridos

1. **Edición de Servicios:** Modificar precio, periodo o notas de servicio existente
2. **Suspensión/Cancelación:** Implementar botones en las tarjetas de servicio
3. **Dashboard de Estadísticas:** Usar `api/reportes.php` para métricas
4. **Renovaciones Automáticas:** Sistema para renovar servicios vencidos
5. **Facturación Electrónica:** Integración con SUNAT (cuando estés listo)

---

**Documentación creada:** 2025-01-10
**Versión del Sistema:** Multi-Servicio v1.0
**Autor:** Claude Code (Anthropic)
