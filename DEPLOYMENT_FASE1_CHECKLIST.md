# ✅ CHECKLIST DE DEPLOYMENT - FASE 1 A PRODUCCIÓN

**Sistema de Recordatorios Automáticos v1.0**
**Imaginatics Peru SAC**

---

## 🎯 RESUMEN: ¿PUEDO DESPLEGAR AHORA?

### ✅ **SÍ, LA FASE 1 ESTÁ LISTA PARA PRODUCCIÓN**

**Estado:** Production-Ready ✅
- ✅ Integración real con API de WhatsApp implementada
- ✅ Base de datos probada y funcionando
- ✅ Sistema de límites anti-spam activo
- ✅ Logs y trazabilidad completos
- ✅ Documentación completa

**La Fase 2 NO es requisito** - Son mejoras opcionales (analytics, optimizaciones)

---

## 📋 PRE-DEPLOYMENT CHECKLIST

### 1️⃣ **VERIFICACIONES DE BASE DE DATOS** ✅

```bash
# Conectar al servidor de producción
ssh usuario@servidor-produccion

# Aplicar migración
mysql -u usuario -p nombre_bd < migrations/003_historial_recordatorios.sql

# Verificar tablas creadas
mysql -u usuario -p nombre_bd -e "SHOW TABLES LIKE '%recordatorio%';"

# Verificar configuración
mysql -u usuario -p nombre_bd -e "SELECT * FROM config_recordatorios;"
```

**Esperado:**
- [x] 2 tablas nuevas: `historial_recordatorios`, `config_recordatorios`
- [x] 2 vistas nuevas: `v_recordatorios_pendientes_hoy`, `v_estadisticas_recordatorios`
- [x] 10 registros en `config_recordatorios`

---

### 2️⃣ **CONFIGURACIÓN DE WHATSAPP** ⚠️ CRÍTICO

```sql
-- Verificar que existan los tokens
SELECT clave, valor FROM configuracion
WHERE clave IN ('token_whatsapp', 'instancia_whatsapp', 'api_url_whatsapp');
```

**Requerido:**
- [x] `token_whatsapp` - Token de API válido
- [x] `instancia_whatsapp` - ID de instancia
- [x] `api_url_whatsapp` - URL base de la API

**Si NO existen, agregarlos:**
```sql
INSERT INTO configuracion (clave, valor) VALUES
('token_whatsapp', 'TU_TOKEN_AQUI'),
('instancia_whatsapp', 'TU_INSTANCIA_AQUI'),
('api_url_whatsapp', 'https://api.whatsapp.com/v1/');
```

---

### 3️⃣ **SUBIR ARCHIVOS AL SERVIDOR** 📤

```bash
# Opción A: SCP
scp api/enviar_recordatorios_auto.php usuario@servidor:/path/to/api/
scp -r cron/ usuario@servidor:/path/to/
scp migrations/003_historial_recordatorios.sql usuario@servidor:/path/to/migrations/

# Opción B: Git (si usas repositorio)
git add .
git commit -m "Feat: Sistema de recordatorios automáticos Fase 1"
git push origin main

# En el servidor
git pull origin main
```

**Archivos críticos:**
- [x] `api/enviar_recordatorios_auto.php` ← NUEVO
- [x] `api/envios.php` ← MODIFICADO
- [x] `migrations/003_historial_recordatorios.sql`
- [x] `cron/instalar_cron.sh`
- [x] `cron/recordatorios_auto.cron`

---

### 4️⃣ **PERMISOS Y DIRECTORIOS** 🔐

```bash
# En el servidor de producción
cd /path/to/pagos_imaginatics

# Permisos del script
chmod +x api/enviar_recordatorios_auto.php
chmod +x cron/instalar_cron.sh

# Crear directorio de logs
mkdir -p logs
chmod 755 logs

# Verificar propietario (debe ser el usuario que ejecuta PHP)
chown -R www-data:www-data logs/  # En Ubuntu/Debian
# O
chown -R apache:apache logs/       # En CentOS/RHEL
# O
chown -R usuario:grupo logs/       # Usuario específico
```

**Verificar:**
```bash
ls -la api/enviar_recordatorios_auto.php  # Debe tener +x
ls -ld logs/                               # Debe tener 755
```

---

### 5️⃣ **PRUEBA MANUAL ANTES DE CRON** 🧪 IMPORTANTE

```bash
# En el servidor de producción
cd /path/to/pagos_imaginatics

# Ejecutar manualmente UNA VEZ para probar
php api/enviar_recordatorios_auto.php

# Ver el resultado
cat logs/recordatorios_auto.log
```

**Verificar en el log:**
- [x] Conexión a BD exitosa
- [x] Configuración cargada
- [x] Clientes detectados (o mensaje "No hay clientes pendientes")
- [x] Sin errores de PHP
- [x] Mensajes de WhatsApp enviados (si hay clientes)

**Verificar en la BD:**
```sql
-- Ver si se registraron los envíos
SELECT * FROM historial_recordatorios ORDER BY id DESC LIMIT 5;

-- Ver respuestas de la API
SELECT id, cliente_id, estado_envio, error_detalle, respuesta_api
FROM historial_recordatorios
WHERE fecha_envio >= CURDATE()
ORDER BY id DESC;
```

---

### 6️⃣ **INSTALAR CRON JOB** ⏰

**Opción A: Instalador Automático**
```bash
cd /path/to/pagos_imaginatics
./cron/instalar_cron.sh
```

**Opción B: Manual**
```bash
# Abrir crontab
crontab -e

# Agregar línea (ajustar rutas según tu instalación):
0 9 * * * /usr/bin/php /path/completo/api/enviar_recordatorios_auto.php >> /path/completo/logs/recordatorios_auto.log 2>&1

# Guardar y salir
# En vi/vim: ESC, :wq
# En nano: Ctrl+X, Y, Enter
```

**Verificar cron instalado:**
```bash
crontab -l  # Debe mostrar la línea agregada
```

**Verificar que cron esté activo:**
```bash
# Ubuntu/Debian
sudo service cron status

# CentOS/RHEL
sudo systemctl status crond

# Iniciar si está detenido
sudo service cron start    # Ubuntu/Debian
sudo systemctl start crond # CentOS/RHEL
```

---

### 7️⃣ **CONFIGURACIÓN INICIAL** ⚙️

```sql
-- Revisar configuración por defecto
SELECT clave, valor, descripcion FROM config_recordatorios;

-- OPCIONAL: Ajustar según necesidades
-- Ejemplo: Reducir frecuencia al inicio
UPDATE config_recordatorios SET valor = '5' WHERE clave = 'dias_minimos_entre_recordatorios';
UPDATE config_recordatorios SET valor = '5' WHERE clave = 'max_recordatorios_mes';

-- OPCIONAL: Cambiar hora de envío
UPDATE config_recordatorios SET valor = '10:00' WHERE clave = 'hora_envio_automatico';
-- Nota: También debes ajustar el crontab si cambias la hora
```

**Configuración recomendada para inicio:**
- Días mínimos: **5** (más conservador)
- Máximo por mes: **5** (más conservador)
- Recordatorios activos: **true**
- Hora: **09:00** o **10:00**

**Después de 1 semana, ajustar a valores óptimos:**
- Días mínimos: **3**
- Máximo por mes: **8**

---

### 8️⃣ **MONITOREO POST-DEPLOYMENT** 📊

#### Día 1 (Primer día de ejecución)

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

-- Ver tasa de éxito del día
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

#### Semana 1 (Primeros 7 días)

```sql
-- Estadísticas semanales
SELECT
    DATE(fecha_envio) as fecha,
    COUNT(*) as total_envios,
    SUM(CASE WHEN estado_envio = 'enviado' THEN 1 ELSE 0 END) as exitosos,
    SUM(CASE WHEN estado_envio = 'error' THEN 1 ELSE 0 END) as errores
FROM historial_recordatorios
WHERE fecha_envio >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
GROUP BY DATE(fecha_envio)
ORDER BY fecha DESC;

-- Clientes que recibieron más recordatorios
SELECT
    c.razon_social,
    COUNT(*) as total_recordatorios,
    MAX(hr.fecha_envio) as ultimo_envio
FROM historial_recordatorios hr
JOIN clientes c ON hr.cliente_id = c.id
WHERE hr.fecha_envio >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
GROUP BY c.id, c.razon_social
HAVING total_recordatorios > 2
ORDER BY total_recordatorios DESC;
```

---

### 9️⃣ **ROLLBACK PLAN** 🔄 (Si algo sale mal)

#### Si hay errores críticos:

**1. Pausar recordatorios inmediatamente:**
```sql
UPDATE config_recordatorios
SET valor = 'false'
WHERE clave = 'recordatorios_automaticos_activos';
```

**2. Comentar el cron:**
```bash
crontab -e
# Agregar # al inicio de la línea:
# 0 9 * * * /usr/bin/php /path/api/enviar_recordatorios_auto.php ...
```

**3. Revisar logs:**
```bash
tail -100 logs/recordatorios_auto.log
```

**4. Revisar errores en BD:**
```sql
SELECT * FROM historial_recordatorios
WHERE estado_envio = 'error'
AND fecha_envio >= CURDATE()
ORDER BY id DESC;
```

**5. Revertir migración (SOLO SI ES NECESARIO):**
```sql
DROP TABLE IF EXISTS historial_recordatorios;
DROP TABLE IF EXISTS config_recordatorios;
DROP VIEW IF EXISTS v_recordatorios_pendientes_hoy;
DROP VIEW IF EXISTS v_estadisticas_recordatorios;
```

---

### 🔟 **DOCUMENTACIÓN PARA EL EQUIPO** 📚

**Crear archivo README_PRODUCCION.md con:**
- URL del servidor
- Ruta de instalación
- Comandos comunes
- Contactos de soporte

**Ejemplo:**
```markdown
# Sistema de Recordatorios - Producción

**Servidor:** produccion.imaginatics.pe
**Ruta:** /var/www/pagos_imaginatics
**Logs:** /var/www/pagos_imaginatics/logs/recordatorios_auto.log

## Comandos Útiles
- Ver logs: `tail -f logs/recordatorios_auto.log`
- Ver cron: `crontab -l`
- Ejecutar manual: `php api/enviar_recordatorios_auto.php`

## Contactos
- Soporte técnico: tech@imaginatics.pe
- Admin BD: admin@imaginatics.pe
```

---

## ✅ CHECKLIST FINAL PRE-GO-LIVE

Marca cada ítem antes de activar en producción:

- [ ] Migración SQL aplicada en producción
- [ ] Tablas y vistas creadas correctamente
- [ ] Tokens de WhatsApp configurados
- [ ] Archivos subidos al servidor
- [ ] Permisos configurados (755 logs, +x scripts)
- [ ] Prueba manual exitosa (sin errores)
- [ ] Al menos 1 mensaje de prueba enviado por WhatsApp
- [ ] Cron job instalado
- [ ] Servicio cron activo
- [ ] Configuración inicial ajustada
- [ ] Logs funcionando correctamente
- [ ] Equipo notificado sobre el deployment
- [ ] Documentación compartida con el equipo
- [ ] Plan de rollback documentado
- [ ] Monitoreo configurado para los primeros días

---

## 🚀 GO-LIVE

**Una vez completado el checklist:**

1. ✅ Esperar a la hora configurada (ej: 9:00 AM del día siguiente)
2. ✅ Monitorear logs en tiempo real
3. ✅ Verificar envíos en la base de datos
4. ✅ Revisar después de 1 hora
5. ✅ Revisar al final del día

**¡El sistema está listo para producción!**

---

## 📞 SOPORTE POST-DEPLOYMENT

**Si necesitas ayuda:**
1. Revisar `FASE1_RECORDATORIOS_AUTO.md` (documentación completa)
2. Revisar sección Troubleshooting
3. Revisar logs del sistema
4. Contactar: soporte@imaginatics.pe

---

**✨ ¡Éxito en tu deployment!**

*Última actualización: 20 de Noviembre, 2025*
