# 🚀 Guía de Migración a Producción - v1.1.0
## Sistema de Pagos Multi-Servicio
### Imaginatics Perú SAC

---

## ⚠️ IMPORTANTE - LEER ANTES DE COMENZAR

Esta guía está diseñada para **actualizar el sistema SIN PERDER DATOS**.
Sigue cada paso cuidadosamente y en orden.

**Tiempo estimado**: 15-20 minutos
**Nivel de riesgo**: Bajo (solo agregamos funcionalidades)

---

## 📋 Pre-requisitos

Antes de comenzar, asegúrate de tener:

- [ ] Acceso SSH o terminal al servidor de producción
- [ ] Acceso a la base de datos MySQL de producción
- [ ] Backup reciente de la base de datos (por seguridad)
- [ ] Git configurado en el servidor
- [ ] Permisos de escritura en el directorio del proyecto

---

## 🛡️ PASO 0: Backup de Seguridad

### 0.1. Backup de Base de Datos

```bash
# Conectarse al servidor de producción
ssh usuario@servidor-produccion

# Crear directorio de backups si no existe
mkdir -p ~/backups

# Realizar backup de la base de datos
mysqldump -u [USUARIO_DB] -p [NOMBRE_DB] > ~/backups/backup_$(date +%Y%m%d_%H%M%S).sql

# Verificar que el backup se creó correctamente
ls -lh ~/backups/
```

### 0.2. Backup de Archivos

```bash
# Navegar al directorio del proyecto
cd /ruta/a/tu/proyecto

# Crear backup de archivos críticos
tar -czf ~/backups/archivos_$(date +%Y%m%d_%H%M%S).tar.gz \
  api/ \
  js/ \
  css/ \
  index.php \
  *.md

# Verificar el backup
ls -lh ~/backups/
```

---

## 📥 PASO 1: Actualizar Código desde Git

### 1.1. Verificar Estado Actual

```bash
cd /ruta/a/tu/proyecto

# Ver qué archivos han cambiado localmente
git status

# Si hay cambios locales importantes, guardarlos
git stash save "Cambios locales antes de actualizar a v1.1.0"
```

### 1.2. Actualizar desde el Repositorio

```bash
# Obtener últimos cambios
git fetch origin

# Ver qué cambios vienen
git log HEAD..origin/master --oneline

# Actualizar a la última versión
git pull origin master

# Si hubo conflictos, resolverlos manualmente y luego:
# git add .
# git commit -m "Merge: Resueltos conflictos de actualización"
```

### 1.3. Verificar Archivos Nuevos

```bash
# Verificar que los nuevos archivos existen
ls -l js/dashboard_pagos.js
ls -l css/servicios.css
```

---

## 🗄️ PASO 2: Actualizar Base de Datos

### 2.1. Verificar Estructura Actual

```bash
# Conectarse a MySQL
mysql -u [USUARIO_DB] -p [NOMBRE_DB]
```

```sql
-- Verificar estructura de la tabla clientes
DESCRIBE clientes;

-- Verificar estructura de servicios_contratados
DESCRIBE servicios_contratados;

-- Verificar estructura de historial_pagos
DESCRIBE historial_pagos;
```

### 2.2. Verificar Columna `servicios_pagados`

```sql
-- Verificar si la columna servicios_pagados existe
SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = '[NOMBRE_DB]'
AND TABLE_NAME = 'historial_pagos'
AND COLUMN_NAME = 'servicios_pagados';
```

**Si la columna NO existe**, ejecutar:

```sql
-- Agregar columna servicios_pagados
ALTER TABLE historial_pagos
ADD COLUMN servicios_pagados JSON NULL
COMMENT 'Array de IDs de servicios_contratados incluidos en este pago'
AFTER observaciones;
```

**Si la columna YA existe**, continuar al siguiente paso.

### 2.3. Verificar Nombres de Columnas en Clientes

```sql
-- Verificar si existe 'whatsapp' o 'telefono'
SELECT COLUMN_NAME
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = '[NOMBRE_DB]'
AND TABLE_NAME = 'clientes'
AND COLUMN_NAME IN ('whatsapp', 'telefono');
```

**Caso 1**: Si tienes columna `telefono` pero no `whatsapp`:

```sql
-- Renombrar telefono a whatsapp
ALTER TABLE clientes
CHANGE COLUMN telefono whatsapp VARCHAR(15) NOT NULL;
```

**Caso 2**: Si tienes columna `whatsapp`:
✅ Todo bien, continúa al siguiente paso.

**Caso 3**: Si tienes ambas columnas:
```sql
-- Verificar cuál tiene datos
SELECT
    COUNT(*) as total,
    COUNT(whatsapp) as con_whatsapp,
    COUNT(telefono) as con_telefono
FROM clientes;

-- Si 'telefono' tiene datos y 'whatsapp' está vacío:
UPDATE clientes SET whatsapp = telefono WHERE whatsapp IS NULL OR whatsapp = '';

-- Luego eliminar columna telefono si ya no la necesitas
-- ALTER TABLE clientes DROP COLUMN telefono;
```

### 2.4. Verificar Datos de Prueba (Opcional)

```sql
-- Ver cuántos servicios tienes
SELECT COUNT(*) FROM servicios_contratados;

-- Ver distribución de estados
SELECT estado, COUNT(*) as cantidad
FROM servicios_contratados
GROUP BY estado;

-- Ver servicios próximos a vencer (debería funcionar el dashboard)
SELECT
    c.razon_social,
    cs.nombre as servicio,
    sc.fecha_vencimiento,
    DATEDIFF(sc.fecha_vencimiento, CURDATE()) as dias_restantes
FROM servicios_contratados sc
JOIN clientes c ON sc.cliente_id = c.id
JOIN catalogo_servicios cs ON sc.servicio_id = cs.id
WHERE sc.estado IN ('activo', 'vencido')
AND DATEDIFF(sc.fecha_vencimiento, CURDATE()) <= 7
ORDER BY sc.fecha_vencimiento;

-- Salir de MySQL
EXIT;
```

---

## 🔧 PASO 3: Verificar Configuración

### 3.1. Verificar Permisos de Archivos

```bash
# Asegurar permisos correctos
chmod 644 js/dashboard_pagos.js
chmod 644 css/servicios.css
chmod 644 api/clientes.php
chmod 644 index.php
```

### 3.2. Verificar Configuración de Base de Datos

```bash
# Revisar el archivo de configuración
cat config/database.php | grep -E "DB_HOST|DB_NAME|DB_USER"
```

Si hay diferencias con desarrollo, **NO CAMBIAR NADA**, la configuración es correcta.

---

## ✅ PASO 4: Probar el Sistema

### 4.1. Pruebas de Endpoints API

```bash
# Probar endpoint de dashboard (reemplaza con tu dominio)
curl -X GET "https://tu-dominio.com/api/clientes.php?action=dashboard_pagos&filtro=todos"

# Deberías ver una respuesta JSON con métricas y servicios
```

### 4.2. Pruebas desde el Navegador

Abre el navegador y accede a tu sistema:

1. **Acceder al sistema**: `https://tu-dominio.com`
2. **Verificar header**: Deberías ver el botón "📊 Dashboard de Pagos"
3. **Abrir dashboard**: Click en "📊 Dashboard de Pagos"
4. **Verificar métricas**: Deberías ver tarjetas con números
5. **Probar filtros**: Click en "Vencidos", "Próximos a Vencer", etc.
6. **Probar búsqueda**: Buscar un cliente por nombre o RUC
7. **Probar acciones**:
   - Click en "💰 Registrar Pago" en un servicio
   - Verificar que el modal se abre con el servicio preseleccionado

### 4.3. Probar Funcionalidad de Edición

1. Click en "Servicios" de cualquier cliente
2. Click en "✏️ Editar" en un servicio
3. Cambiar algún valor (ej. precio o fecha)
4. Guardar cambios
5. Verificar que se actualizó correctamente

### 4.4. Probar Historial de Pagos

1. Click en "Servicios" de un cliente con pagos
2. Click en "📊 Detalle" en un servicio
3. Verificar que se muestra el historial de pagos
4. Verificar estadísticas (total pagado, cantidad de pagos)

---

## 🐛 PASO 5: Troubleshooting

### Error: "Column not found: telefono"

**Solución**: Ejecutar en MySQL:

```sql
ALTER TABLE clientes
CHANGE COLUMN telefono whatsapp VARCHAR(15) NOT NULL;
```

### Error: "dashboard_pagos.js not found"

**Solución**: Verificar que el archivo existe y tiene permisos:

```bash
ls -l js/dashboard_pagos.js
chmod 644 js/dashboard_pagos.js
```

### Error: "Unknown action: dashboard_pagos"

**Solución**: Verificar que el archivo `api/clientes.php` se actualizó:

```bash
grep -n "dashboard_pagos" api/clientes.php
```

Deberías ver líneas 76-78 con el case statement.

### Dashboard muestra "0" en todas las métricas

**Posibles causas**:
1. No hay servicios en estado 'activo' o 'vencido'
2. Todos los servicios están cancelados
3. La fecha de vencimiento está muy lejos

**Verificar en MySQL**:

```sql
SELECT estado, COUNT(*)
FROM servicios_contratados
GROUP BY estado;
```

### Error: "Internal Server Error 500"

**Solución**: Revisar logs de Apache/PHP:

```bash
tail -f /var/log/apache2/error.log
# o
tail -f /var/log/php_errors.log
```

---

## 🔄 PASO 6: Rollback (Solo si algo salió muy mal)

Si necesitas volver atrás:

### 6.1. Restaurar Base de Datos

```bash
# Restaurar backup
mysql -u [USUARIO_DB] -p [NOMBRE_DB] < ~/backups/backup_[FECHA].sql
```

### 6.2. Restaurar Archivos

```bash
# Volver a la versión anterior en Git
cd /ruta/a/tu/proyecto
git log --oneline -10
git reset --hard [HASH_DEL_COMMIT_ANTERIOR]

# O restaurar desde backup
cd /ruta/a/tu/proyecto
rm -rf api/ js/ css/ index.php
tar -xzf ~/backups/archivos_[FECHA].tar.gz
```

---

## 📊 PASO 7: Verificación Final

### Checklist de Verificación

- [ ] El sistema carga sin errores
- [ ] El botón "📊 Dashboard de Pagos" aparece en el header
- [ ] El dashboard abre correctamente
- [ ] Las métricas muestran números correctos
- [ ] Los filtros funcionan
- [ ] La búsqueda funciona
- [ ] Los botones de acción funcionan
- [ ] Se puede editar un servicio
- [ ] Se puede ver el historial de pagos
- [ ] Se puede registrar un pago con servicios preseleccionados
- [ ] Los datos existentes NO se perdieron
- [ ] Los clientes siguen apareciendo normalmente
- [ ] Los pagos históricos siguen visibles

---

## 📝 Notas Adicionales

### Diferencias entre Desarrollo y Producción

Si tu estructura de producción tiene diferencias:

1. **Columnas extra en tablas**: No hay problema, solo se usan las necesarias
2. **Nombres de tablas diferentes**: Necesitarás ajustar los queries en `api/clientes.php`
3. **Campos adicionales**: No afectan, el sistema solo lee lo que necesita

### Mantenimiento Post-Migración

1. **Monitorear logs** durante las primeras 24 horas
2. **Verificar métricas** del dashboard diariamente
3. **Solicitar feedback** de usuarios sobre el dashboard
4. **Ajustar filtros** si es necesario según uso real

---

## 🆘 Soporte

Si encuentras problemas durante la migración:

1. **No entrar en pánico** - tienes backups
2. **Documentar el error** exacto que ves
3. **Revisar logs** del servidor
4. **Consultar esta guía** de troubleshooting
5. **Restaurar desde backup** si es necesario

---

## ✅ Migración Exitosa

Si completaste todos los pasos y las verificaciones:

🎉 **¡Felicitaciones!** El sistema se actualizó exitosamente a la versión 1.1.0

**Nuevas funcionalidades disponibles**:
- Dashboard de Pagos Pendientes con métricas en tiempo real
- Edición de servicios contratados
- Historial detallado por servicio
- Filtros avanzados y búsqueda
- Acciones rápidas desde el dashboard
- Mejor selector de bancos para pagos

---

**Documento creado**: 11 de Noviembre, 2025
**Versión**: 1.1.0
**Autor**: Claude Code AI
**Empresa**: Imaginatics Perú SAC
