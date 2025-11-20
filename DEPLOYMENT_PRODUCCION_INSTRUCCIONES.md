# 🚀 INSTRUCCIONES DE DEPLOYMENT A PRODUCCIÓN - FASE 1

**Sistema de Recordatorios Automáticos v1.0 con UI mejorada**
**Fecha:** 20 de Noviembre, 2025
**Desarrollado por:** Claude Code

---

## 📋 RESUMEN EJECUTIVO

Este deployment incluye:
- ✅ **Fase 1 Completa:** Sistema de recordatorios automáticos con cron job
- ✅ **UI Mejorada:** Dashboard y modals con Apple Human Interface Guidelines
- ✅ **APIs Backend:** Endpoints para dashboard de recordatorios
- ✅ **Base de datos:** Tablas, vistas y configuración de recordatorios
- ✅ **Documentación completa**

**IMPORTANTE:** La Fase 1 está 100% lista para producción. La Fase 2 (analytics avanzados) es opcional.

---

## 🎯 ARCHIVOS MODIFICADOS/CREADOS

### Nuevos Archivos
```
api/enviar_recordatorios_auto.php          ← Script cron principal
migrations/003_historial_recordatorios.sql ← Migración de BD
cron/instalar_cron.sh                      ← Instalador automático
cron/recordatorios_auto.cron               ← Ejemplos de configuración
FASE1_RECORDATORIOS_AUTO.md                ← Documentación completa
DEPLOYMENT_FASE1_CHECKLIST.md              ← Checklist paso a paso
FASE1_ARCHIVOS_CREADOS.md                  ← Resumen de archivos
DEPLOYMENT_PRODUCCION_INSTRUCCIONES.md     ← Este archivo
```

### Archivos Modificados
```
api/clientes.php   ← +200 líneas (APIs dashboard recordatorios)
api/envios.php     ← +180 líneas (funciones auxiliares historial)
index.php          ← +600 líneas (Dashboard UI Apple HIG + mejoras visuales)
```

### NO Subir a Producción
```
test_analisis.php       ← Script de prueba local
test_api_analisis.php   ← Script de prueba local
logs/*.log              ← Logs locales
```

---

## 🔧 PASOS DE DEPLOYMENT EN PRODUCCIÓN

### 1️⃣ **Pre-requisitos en Servidor de Producción**

Verificar que el servidor tenga:
- PHP 7.4+ con extensiones: PDO, mysqli, curl, json
- MySQL 5.7+ o MariaDB 10.3+
- Cron habilitado
- Permisos para crear directorios y archivos

### 2️⃣ **Aplicar Migración de Base de Datos**

```bash
# Conectar al servidor de producción
ssh usuario@servidor-produccion

# Ir al directorio del proyecto
cd /ruta/del/proyecto

# Aplicar migración
mysql -u usuario -p nombre_bd < migrations/003_historial_recordatorios.sql

# Verificar que se crearon las tablas
mysql -u usuario -p nombre_bd -e "SHOW TABLES LIKE '%recordatorio%';"

# Deberías ver:
# - config_recordatorios
# - historial_recordatorios
# - v_recordatorios_pendientes_hoy
# - v_estadisticas_recordatorios
```

### 3️⃣ **Verificar Configuración de WhatsApp**

```sql
-- Verificar que existan los tokens
SELECT clave, valor FROM configuracion
WHERE clave IN ('token_whatsapp', 'instancia_whatsapp', 'api_url_whatsapp');

-- Si NO existen, agregarlos:
INSERT INTO configuracion (clave, valor) VALUES
('token_whatsapp', 'TU_TOKEN_REAL_AQUI'),
('instancia_whatsapp', 'TU_INSTANCIA_REAL_AQUI'),
('api_url_whatsapp', 'https://tu-api-whatsapp.com/');
```

### 4️⃣ **Subir Archivos al Servidor**

```bash
# Opción A: Git (RECOMENDADO)
git pull origin master

# Opción B: SCP
scp api/enviar_recordatorios_auto.php usuario@servidor:/ruta/proyecto/api/
scp -r cron/ usuario@servidor:/ruta/proyecto/
scp migrations/003_historial_recordatorios.sql usuario@servidor:/ruta/proyecto/migrations/
```

### 5️⃣ **Configurar Permisos**

```bash
# En el servidor de producción
cd /ruta/del/proyecto

# Permisos del script cron
chmod +x api/enviar_recordatorios_auto.php
chmod +x cron/instalar_cron.sh

# Crear directorio de logs
mkdir -p logs
chmod 755 logs

# Verificar propietario (debe ser el usuario que ejecuta PHP)
chown -R www-data:www-data logs/  # En Ubuntu/Debian
# O
chown -R apache:apache logs/       # En CentOS/RHEL
```

### 6️⃣ **Prueba Manual ANTES de Instalar Cron**

```bash
# IMPORTANTE: Ejecutar manualmente UNA vez para verificar
cd /ruta/del/proyecto
php api/enviar_recordatorios_auto.php

# Ver el log para verificar éxito
cat logs/recordatorios_auto.log

# Verificar en BD que se registraron envíos
mysql -u usuario -p nombre_bd -e "SELECT * FROM historial_recordatorios ORDER BY id DESC LIMIT 5;"
```

### 7️⃣ **Instalar Cron Job**

**Opción A: Instalador Automático**
```bash
cd /ruta/del/proyecto
./cron/instalar_cron.sh
```

**Opción B: Manual**
```bash
crontab -e

# Agregar línea (ajustar hora según config_recordatorios):
0 9 * * * /usr/bin/php /ruta/completa/api/enviar_recordatorios_auto.php >> /ruta/completa/logs/recordatorios_auto.log 2>&1

# Guardar y salir
```

**Verificar cron instalado:**
```bash
crontab -l  # Debe mostrar la línea agregada

# Verificar que cron esté activo
sudo service cron status     # Ubuntu/Debian
sudo systemctl status crond  # CentOS/RHEL
```

### 8️⃣ **Configuración Inicial del Sistema**

```sql
-- Revisar configuración por defecto
SELECT clave, valor, descripcion FROM config_recordatorios;

-- OPCIONAL: Ajustar para inicio conservador
UPDATE config_recordatorios SET valor = '5' WHERE clave = 'dias_minimos_entre_recordatorios';
UPDATE config_recordatorios SET valor = '5' WHERE clave = 'max_recordatorios_mes';

-- IMPORTANTE: Ajustar hora según crontab
UPDATE config_recordatorios SET valor = '09:00' WHERE clave = 'hora_envio_automatico';
-- Si cambias la hora aquí, también cambia el crontab
```

### 9️⃣ **Verificar UI en Producción**

1. Ir a `http://tu-dominio-produccion.com/index.php`
2. Click en pestaña **"Notificaciones"**
3. Verificar que el **Dashboard de Recordatorios Automáticos** cargue:
   - ✅ Estado del sistema (Activo/Pausado)
   - ✅ Última Ejecución
   - ✅ Próxima Ejecución (debe coincidir con hora_envio_automatico)
   - ✅ Estadísticas (Vencidos, Vence Hoy, Por Vencer, Enviados Hoy)
4. Click en **"⚙️ Configurar Sistema"** para verificar que el modal funciona
5. Click en **"📊 Ver Historial Completo"** para ver envíos registrados

---

## 🔍 MONITOREO POST-DEPLOYMENT

### Día 1 (Primer día de ejecución)

```bash
# Ver logs en tiempo real
tail -f logs/recordatorios_auto.log

# Ver cuántos se enviaron
grep "✅ Recordatorio enviado exitosamente" logs/recordatorios_auto.log | wc -l

# Ver si hubo errores
grep "❌ Error" logs/recordatorios_auto.log
```

```sql
-- Ver envíos del día
SELECT
    c.razon_social,
    hr.tipo_recordatorio,
    hr.dias_antes_vencimiento,
    hr.estado_envio,
    hr.fecha_envio
FROM historial_recordatorios hr
JOIN clientes c ON hr.cliente_id = c.id
WHERE DATE(hr.fecha_envio) = CURDATE()
ORDER BY hr.fecha_envio DESC;

-- Ver tasa de éxito
SELECT
    fue_automatico,
    COUNT(*) as total,
    SUM(CASE WHEN estado_envio = 'enviado' THEN 1 ELSE 0 END) as exitosos,
    SUM(CASE WHEN estado_envio = 'error' THEN 1 ELSE 0 END) as errores,
    ROUND(SUM(CASE WHEN estado_envio = 'enviado' THEN 1 ELSE 0 END) / COUNT(*) * 100, 2) as tasa_exito
FROM historial_recordatorios
WHERE DATE(fecha_envio) = CURDATE()
GROUP BY fue_automatico;
```

### Uso del Dashboard Web

En lugar de consultas SQL, los usuarios pueden:
1. Ir a **Notificaciones → Dashboard de Recordatorios Automáticos**
2. Ver estadísticas en tiempo real
3. Click en **"🔄 Actualizar"** para refrescar datos
4. Click en **"📊 Ver Historial Completo"** para ver últimos 50 envíos
5. Click en **"⚙️ Configurar Sistema"** para ajustar parámetros sin tocar código

---

## 🚨 TROUBLESHOOTING COMÚN

### Error: "Cargando..." nunca se actualiza en Dashboard

**Solución:**
1. Abre la consola del navegador (F12 → Console)
2. Verifica errores de red en la pestaña Network
3. Verifica que la API responda: `curl http://tu-dominio/api/clientes.php?action=estadisticas_recordatorios`
4. Revisa permisos de archivos PHP

### Error: "Próxima Ejecución" no coincide con configuración

**Solución:**
1. Verifica que la hora en `config_recordatorios` coincida con el crontab
2. Recarga la página y espera 1 segundo para que el JavaScript cargue
3. Click en botón "🔄 Actualizar" en el dashboard

### Error: Cron no se ejecuta

**Solución:**
```bash
# Verificar que cron esté activo
sudo service cron status

# Ver logs de cron del sistema
sudo tail -f /var/log/syslog | grep CRON    # Ubuntu/Debian
sudo tail -f /var/log/cron                   # CentOS/RHEL

# Verificar sintaxis del crontab
crontab -l

# Probar ejecución manual
php /ruta/completa/api/enviar_recordatorios_auto.php
```

### Error: No se envían mensajes de WhatsApp

**Solución:**
1. Verificar tokens en tabla `configuracion`
2. Verificar logs: `cat logs/recordatorios_auto.log`
3. Probar API de WhatsApp manualmente
4. Verificar que clientes tengan números de WhatsApp válidos

---

## 📊 CARACTERÍSTICAS IMPLEMENTADAS

### Dashboard de Recordatorios Automáticos
- ✅ Estado del sistema en tiempo real (Activo/Pausado)
- ✅ Última ejecución del cron
- ✅ Próxima ejecución programada
- ✅ 4 tarjetas de estadísticas con diseño Apple HIG
- ✅ Modal de historial completo (últimos 50 envíos)
- ✅ Modal de configuración editable
- ✅ Botón de actualización manual

### Notificaciones Manuales Mejoradas
- ✅ Summary cards con gradientes
- ✅ Barra de búsqueda en tiempo real
- ✅ Client cards mejoradas con badges de color
- ✅ Información estructurada en grid
- ✅ Estados visuales según prioridad

### Sistema Automático
- ✅ Ejecución diaria por cron
- ✅ 5 tipos de recordatorios (preventivo, urgente, crítico, vencido, mora)
- ✅ Límites anti-spam configurables
- ✅ Registro completo en historial
- ✅ Logs detallados
- ✅ Integración real con API de WhatsApp

---

## 🔄 ROLLBACK (Si algo sale mal)

### Pausar el sistema inmediatamente

```sql
-- Pausar recordatorios automáticos
UPDATE config_recordatorios
SET valor = 'false'
WHERE clave = 'recordatorios_automaticos_activos';
```

```bash
# Comentar el cron
crontab -e
# Agregar # al inicio de la línea del cron de recordatorios
```

### Revertir migración (SOLO SI ES NECESARIO)

```sql
DROP TABLE IF EXISTS historial_recordatorios;
DROP TABLE IF EXISTS config_recordatorios;
DROP VIEW IF EXISTS v_recordatorios_pendientes_hoy;
DROP VIEW IF EXISTS v_estadisticas_recordatorios;
```

---

## ✅ CHECKLIST FINAL PRE-GO-LIVE

- [ ] Migración SQL aplicada en producción
- [ ] Tablas y vistas creadas correctamente
- [ ] Tokens de WhatsApp configurados
- [ ] Archivos subidos al servidor
- [ ] Permisos configurados (755 logs, +x scripts)
- [ ] Prueba manual exitosa (sin errores)
- [ ] Al menos 1 mensaje de prueba enviado por WhatsApp
- [ ] Cron job instalado y verificado
- [ ] Servicio cron activo en el servidor
- [ ] Dashboard web carga correctamente
- [ ] Modal de configuración funciona
- [ ] Modal de historial muestra datos
- [ ] Equipo notificado sobre el deployment
- [ ] Plan de rollback documentado y disponible

---

## 📞 CONTACTO Y SOPORTE

**Documentación completa:**
- `FASE1_RECORDATORIOS_AUTO.md` - Guía completa del sistema
- `DEPLOYMENT_FASE1_CHECKLIST.md` - Checklist detallado paso a paso
- `FASE1_ARCHIVOS_CREADOS.md` - Resumen de todos los archivos

**Si necesitas ayuda durante el deployment:**
1. Revisar la documentación completa
2. Revisar logs: `logs/recordatorios_auto.log`
3. Revisar sección Troubleshooting en `FASE1_RECORDATORIOS_AUTO.md`
4. Contactar: soporte@imaginatics.pe

---

**✨ ¡Éxito en tu deployment a producción!**

*Documento generado: 20 de Noviembre, 2025*
*Sistema: Recordatorios Automáticos v1.0 con UI Apple HIG*
*Imaginatics Peru SAC*
