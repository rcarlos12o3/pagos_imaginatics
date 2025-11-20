# 🤖 INSTRUCCIONES PARA CLAUDE DE PRODUCCIÓN

**Para:** Claude Code en servidor de producción
**De:** Claude Code en desarrollo local
**Fecha:** 20 de Noviembre, 2025

---

## 🎯 TU MISIÓN

Debes desplegar el **Sistema de Recordatorios Automáticos Fase 1** con **UI mejorada Apple HIG** en el servidor de producción.

---

## 📂 ARCHIVOS QUE RECIBIRÁS

Ya están en el repositorio Git (branch master). Haz `git pull origin master` para obtenerlos:

### Documentación (LÉELA PRIMERO)
1. **DEPLOYMENT_PRODUCCION_INSTRUCCIONES.md** ← GUÍA PRINCIPAL
2. **DEPLOYMENT_FASE1_CHECKLIST.md** ← CHECKLIST PASO A PASO
3. **FASE1_RECORDATORIOS_AUTO.md** ← DOCUMENTACIÓN TÉCNICA COMPLETA

### Archivos a Desplegar
- `migrations/003_historial_recordatorios.sql` ← Aplicar a BD
- `api/enviar_recordatorios_auto.php` ← Script cron
- `api/clientes.php` ← Modificado (APIs dashboard)
- `api/envios.php` ← Modificado (funciones auxiliares)
- `index.php` ← Modificado (UI Apple HIG)
- `cron/instalar_cron.sh` ← Instalador cron
- `cron/recordatorios_auto.cron` ← Ejemplos

---

## ✅ PASOS RESUMIDOS

### 1. Hacer Pull del Repositorio
```bash
git pull origin master
```

### 2. Aplicar Migración de BD
```bash
mysql -u usuario -p nombre_bd < migrations/003_historial_recordatorios.sql
```

Verificar:
```sql
SHOW TABLES LIKE '%recordatorio%';
-- Debes ver: config_recordatorios, historial_recordatorios,
--             v_recordatorios_pendientes_hoy, v_estadisticas_recordatorios
```

### 3. Verificar Tokens de WhatsApp
```sql
SELECT clave, valor FROM configuracion
WHERE clave IN ('token_whatsapp', 'instancia_whatsapp', 'api_url_whatsapp');
```

Si no existen, agregarlos con los valores reales del cliente.

### 4. Configurar Permisos
```bash
chmod +x api/enviar_recordatorios_auto.php
chmod +x cron/instalar_cron.sh
mkdir -p logs
chmod 755 logs
chown -R www-data:www-data logs/  # Ajustar según servidor
```

### 5. Prueba Manual (CRÍTICO)
```bash
# Ejecutar UNA VEZ manualmente para verificar
php api/enviar_recordatorios_auto.php

# Ver logs
cat logs/recordatorios_auto.log

# Verificar BD
mysql -u usuario -p nombre_bd -e "SELECT * FROM historial_recordatorios ORDER BY id DESC LIMIT 5;"
```

**NO continúes si hay errores. Corrígelos primero.**

### 6. Instalar Cron Job
```bash
./cron/instalar_cron.sh
# O manualmente:
crontab -e
# Agregar: 0 9 * * * /usr/bin/php /ruta/completa/api/enviar_recordatorios_auto.php >> /ruta/completa/logs/recordatorios_auto.log 2>&1
```

### 7. Verificar UI
Ir a: `http://dominio-produccion/index.php`
- Click en pestaña "Notificaciones"
- Verificar que el Dashboard de Recordatorios Automáticos cargue correctamente
- Las estadísticas deben aparecer (no "Cargando...")
- Click en "⚙️ Configurar Sistema" → debe abrir modal
- Click en "📊 Ver Historial Completo" → debe mostrar envíos

---

## 🚨 CHECKLIST DE VERIFICACIÓN

Antes de dar por terminado, marca cada ítem:

- [ ] `git pull origin master` ejecutado exitosamente
- [ ] Migración SQL aplicada (4 objetos creados)
- [ ] Tokens de WhatsApp configurados en BD
- [ ] Permisos configurados (755 logs, +x scripts)
- [ ] Prueba manual ejecutada SIN ERRORES
- [ ] Al menos 1 recordatorio de prueba enviado exitosamente
- [ ] Cron job instalado (`crontab -l` muestra la línea)
- [ ] Servicio cron activo (`sudo service cron status`)
- [ ] Dashboard web carga y muestra estadísticas reales
- [ ] Modal de configuración funciona
- [ ] Modal de historial muestra datos
- [ ] Hora en "Próxima Ejecución" coincide con `config_recordatorios`

---

## 📋 INFORMACIÓN IMPORTANTE

### Base de Datos
- **Nueva tabla:** `historial_recordatorios` (registro de todos los envíos)
- **Nueva tabla:** `config_recordatorios` (10 parámetros configurables)
- **Nuevas vistas:** 2 vistas SQL para consultas rápidas

### APIs Nuevas
- `GET /api/clientes.php?action=estadisticas_recordatorios`
- `GET /api/clientes.php?action=historial_recordatorios`
- `GET /api/clientes.php?action=obtener_config_recordatorios`
- `GET /api/clientes.php?action=detalle_estado_recordatorios`
- `POST /api/clientes.php` con `action=actualizar_config_recordatorios`

### Configuración Default
- Sistema ACTIVO por defecto
- Hora de envío: 09:00 AM
- Días mínimos entre recordatorios: 3
- Máximo por mes: 8
- Se puede cambiar desde la UI web (Dashboard → Configurar Sistema)

---

## ❌ ERRORES COMUNES Y SOLUCIONES

### "Cargando..." nunca desaparece en Dashboard
**Causa:** Error en API o JavaScript bloqueado
**Solución:**
1. Abrir consola navegador (F12)
2. Ver errores en Console y Network
3. Verificar: `curl http://dominio/api/clientes.php?action=estadisticas_recordatorios`
4. Si dice "success: false", revisar logs PHP

### Cron no se ejecuta
**Causa:** Cron no activo o ruta incorrecta
**Solución:**
```bash
sudo service cron status
crontab -l
# Verificar que la ruta sea ABSOLUTA, no relativa
```

### No se envían mensajes WhatsApp
**Causa:** Tokens incorrectos o API caída
**Solución:**
1. Verificar tabla `configuracion` tiene tokens reales
2. Ver `logs/recordatorios_auto.log` para detalles del error
3. Probar API de WhatsApp manualmente con curl

---

## 🆘 SI ALGO SALE MAL

### Pausar el Sistema Inmediatamente
```sql
UPDATE config_recordatorios
SET valor = 'false'
WHERE clave = 'recordatorios_automaticos_activos';
```

```bash
# Comentar el cron
crontab -e
# Agregar # al inicio de la línea
```

### Rollback Completo (ÚLTIMA OPCIÓN)
```sql
DROP TABLE IF EXISTS historial_recordatorios;
DROP TABLE IF EXISTS config_recordatorios;
DROP VIEW IF EXISTS v_recordatorios_pendientes_hoy;
DROP VIEW IF EXISTS v_estadisticas_recordatorios;
```

```bash
git reset --hard HEAD~1  # Volver al commit anterior
```

---

## 📞 RECURSOS

- **Documentación completa:** `FASE1_RECORDATORIOS_AUTO.md`
- **Checklist detallado:** `DEPLOYMENT_FASE1_CHECKLIST.md`
- **Guía deployment:** `DEPLOYMENT_PRODUCCION_INSTRUCCIONES.md`
- **Troubleshooting:** Ver sección en cualquiera de los MDs

---

## ✨ RESULTADO ESPERADO

Al terminar, el cliente podrá:
1. Ver dashboard de recordatorios en tiempo real
2. Ver estadísticas (Vencidos, Vence Hoy, Por Vencer, Enviados Hoy)
3. Ver historial de todos los envíos
4. Configurar el sistema sin tocar código
5. Recibir recordatorios automáticos cada día a la hora configurada

**El sistema enviará recordatorios automáticamente cada día a las 9:00 AM (o la hora configurada) sin intervención humana.**

---

**¡Buena suerte en el deployment! 🚀**

*Desarrollado por Claude Code*
*20 de Noviembre, 2025*
