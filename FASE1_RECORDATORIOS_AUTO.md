# 📋 FASE 1: AUTOMATIZACIÓN DE RECORDATORIOS - DOCUMENTACIÓN COMPLETA

**Sistema de Recordatorios Automáticos v1.0**
**Imaginatics Peru SAC**
**Fecha de implementación:** 19 de Noviembre, 2025

---

## 📖 Índice

1. [Resumen Ejecutivo](#resumen-ejecutivo)
2. [Componentes Implementados](#componentes-implementados)
3. [Instalación](#instalación)
4. [Configuración](#configuración)
5. [Uso](#uso)
6. [Monitoreo y Troubleshooting](#monitoreo-y-troubleshooting)
7. [Preguntas Frecuentes](#preguntas-frecuentes)

---

## 🎯 Resumen Ejecutivo

La Fase 1 implementa un **sistema automático de recordatorios de pago** que:

✅ **Envía recordatorios automáticamente** mediante cron job
✅ **Respeta límites de frecuencia** (días mínimos entre recordatorios, máximo por mes)
✅ **Registra historial completo** de todos los recordatorios enviados
✅ **Clasifica recordatorios** por tipo (preventivo, urgente, crítico, vencido, mora)
✅ **Configurable** mediante tabla de configuración

### Beneficios Clave

- ⏰ **Ahorro de tiempo**: No requiere intervención manual diaria
- 📊 **Trazabilidad**: Historial completo de todos los recordatorios
- 🛡️ **Anti-spam**: Límites automáticos para no saturar a los clientes
- 🎯 **Efectividad**: Mensajes personalizados según estado de pago
- 📈 **Escalable**: Preparado para análisis y métricas futuras

---

## 🔧 Componentes Implementados

### 1. **Base de Datos**

#### Tabla `historial_recordatorios`
Registra todos los recordatorios enviados con:
- Cliente, servicio, tipo de recordatorio
- Días antes/después del vencimiento
- Estado del envío (pendiente, enviado, error, rebotado)
- Canal utilizado (WhatsApp, email, SMS)
- Mensajes enviados y respuestas de API
- Si fue automático o manual

#### Tabla `config_recordatorios`
Almacena configuraciones del sistema:
- Días mínimos entre recordatorios: **3 días**
- Máximo recordatorios por mes: **8**
- Recordatorios automáticos activos: **true**
- Hora de envío automático: **09:00**
- Días para recordatorios preventivos, urgentes, críticos
- Días para recordatorios de mora

#### Vista `v_recordatorios_pendientes_hoy`
Vista SQL que filtra automáticamente clientes que necesitan recordatorio según:
- Fecha de vencimiento
- Último recordatorio enviado
- Límites de frecuencia configurados

#### Vista `v_estadisticas_recordatorios`
Vista SQL con estadísticas por cliente:
- Total de recordatorios enviados
- Recordatorios exitosos vs fallidos
- Automáticos vs manuales
- Último recordatorio enviado

### 2. **Scripts PHP**

#### `api/enviar_recordatorios_auto.php`
Script principal ejecutado por cron que:
1. ✅ Verifica configuración activa
2. ✅ Obtiene clientes pendientes
3. ✅ Valida límites de frecuencia
4. ✅ Determina tipo de recordatorio
5. ✅ Envía mensajes personalizados
6. ✅ Registra en historial
7. ✅ Genera logs detallados

**Tipos de recordatorios:**
- `preventivo`: 7+ días antes del vencimiento
- `urgente`: 3-6 días antes
- `critico`: 0-2 días antes (incluyendo día de vencimiento)
- `vencido`: 1-14 días después del vencimiento
- `mora`: 15+ días después del vencimiento

### 3. **Funciones API Actualizadas**

#### `registrarRecordatorioEnHistorial()`
Registra recordatorios en la nueva tabla con todos los detalles

#### `verificarLimitesRecordatorioAPI()`
Valida si un cliente puede recibir recordatorio según:
- Días mínimos desde último envío
- Máximo de recordatorios del mes

#### `determinarTipoRecordatorioInterno()`
Clasifica el tipo de recordatorio según días restantes

### 4. **Archivos de Configuración**

#### `cron/recordatorios_auto.cron`
Ejemplos de configuración de cron para diferentes escenarios

#### `cron/instalar_cron.sh`
Script interactivo de instalación automática

---

## 🚀 Instalación

### Opción 1: Instalación Automática (Recomendada)

```bash
cd /ruta/a/pagos_imaginatics
./cron/instalar_cron.sh
```

El script te guiará paso a paso:
1. Detectará la ruta del proyecto
2. Encontrará el ejecutable de PHP
3. Creará el directorio de logs
4. Configurará permisos
5. Te preguntará la hora de ejecución
6. Instalará el cron automáticamente

### Opción 2: Instalación Manual

#### Paso 1: Aplicar Migración SQL

```bash
mysql -h 127.0.0.1 -u root imaginatics_ruc < migrations/003_historial_recordatorios.sql
```

#### Paso 2: Verificar Permisos

```bash
chmod +x api/enviar_recordatorios_auto.php
mkdir -p logs
chmod 755 logs
```

#### Paso 3: Configurar Cron

```bash
crontab -e
```

Agregar la línea (ajustar rutas según tu instalación):

```cron
0 9 * * * /usr/bin/php /ruta/completa/api/enviar_recordatorios_auto.php >> /ruta/completa/logs/recordatorios_auto.log 2>&1
```

**Formato cron:** `minuto hora día_mes mes día_semana comando`

Ejemplos:
- `0 9 * * *` - Todos los días a las 9:00 AM
- `0 9,15 * * *` - Dos veces al día (9 AM y 3 PM)
- `0 9 * * 1-5` - Solo días laborables a las 9 AM
- `*/30 9-17 * * 1-5` - Cada 30 min entre 9 AM y 5 PM, días laborables

#### Paso 4: Verificar Instalación

```bash
# Ver el crontab actual
crontab -l

# Probar el script manualmente
php api/enviar_recordatorios_auto.php

# Ver el log
tail -f logs/recordatorios_auto.log
```

---

## ⚙️ Configuración

### Parámetros Configurables

Todos los parámetros se configuran desde la tabla `config_recordatorios`:

```sql
-- Ver configuración actual
SELECT * FROM config_recordatorios ORDER BY id;

-- Cambiar días mínimos entre recordatorios (default: 3)
UPDATE config_recordatorios
SET valor = '5'
WHERE clave = 'dias_minimos_entre_recordatorios';

-- Cambiar máximo de recordatorios por mes (default: 8)
UPDATE config_recordatorios
SET valor = '6'
WHERE clave = 'max_recordatorios_mes';

-- Desactivar recordatorios automáticos temporalmente
UPDATE config_recordatorios
SET valor = 'false'
WHERE clave = 'recordatorios_automaticos_activos';

-- Cambiar hora de envío (formato HH:MM)
UPDATE config_recordatorios
SET valor = '10:30'
WHERE clave = 'hora_envio_automatico';

-- Configurar días para recordatorios de mora
UPDATE config_recordatorios
SET valor = '+3,+7,+15,+30,+45'
WHERE clave = 'dias_recordatorio_mora';
```

### Configuración Recomendada

Para un sistema balanceado:

| Parámetro | Valor Recomendado | Descripción |
|-----------|------------------|-------------|
| `dias_minimos_entre_recordatorios` | 3 | Mínimo 3 días entre recordatorios |
| `max_recordatorios_mes` | 8 | Máximo 8 recordatorios/mes por cliente |
| `recordatorios_automaticos_activos` | true | Mantener activo |
| `hora_envio_automatico` | 09:00 | Horario laboral matutino |
| `dias_recordatorio_preventivo` | -7 | 7 días antes |
| `dias_recordatorio_urgente` | -3 | 3 días antes |
| `dias_recordatorio_critico` | -1 | 1 día antes |

---

## 💻 Uso

### Monitoreo Diario

#### Ver recordatorios pendientes de hoy

```sql
SELECT * FROM v_recordatorios_pendientes_hoy;
```

#### Ver estadísticas por cliente

```sql
SELECT * FROM v_estadisticas_recordatorios
WHERE total_recordatorios > 0
ORDER BY ultimo_recordatorio DESC;
```

#### Ver historial de recordatorios

```sql
SELECT
    hr.id,
    hr.fecha_envio,
    c.razon_social,
    hr.tipo_recordatorio,
    hr.dias_antes_vencimiento,
    hr.estado_envio,
    hr.fue_automatico
FROM historial_recordatorios hr
JOIN clientes c ON hr.cliente_id = c.id
ORDER BY hr.fecha_envio DESC
LIMIT 50;
```

### Ejecución Manual

Si necesitas ejecutar el proceso manualmente:

```bash
# Ejecutar recordatorios
php api/enviar_recordatorios_auto.php

# Ver resultado en tiempo real
tail -f logs/recordatorios_auto.log
```

### Activar/Desactivar Recordatorios

```sql
-- Pausar temporalmente
UPDATE config_recordatorios
SET valor = 'false'
WHERE clave = 'recordatorios_automaticos_activos';

-- Reactivar
UPDATE config_recordatorios
SET valor = 'true'
WHERE clave = 'recordatorios_automaticos_activos';
```

---

## 🔍 Monitoreo y Troubleshooting

### Ver Logs en Tiempo Real

```bash
# Logs del script
tail -f logs/recordatorios_auto.log

# Logs del sistema
tail -f /var/log/syslog | grep cron    # Linux
tail -f /var/log/cron                   # CentOS/RHEL
log show --predicate 'process == "cron"' --last 1h  # macOS
```

### Verificar que el Cron Está Funcionando

```bash
# Ver configuración actual
crontab -l

# Ver cuándo ejecutará próximamente
# (No hay comando directo, pero puedes calcularlo)

# Ver si se ejecutó hoy
grep "recordatorios" logs/recordatorios_auto.log | grep $(date +%Y-%m-%d)
```

### Problemas Comunes

#### 1. El cron no se ejecuta

**Diagnóstico:**
```bash
# Verificar que el servicio cron esté activo
sudo service cron status  # Linux
sudo launchctl list | grep cron  # macOS
```

**Solución:**
```bash
# Iniciar servicio
sudo service cron start  # Linux
```

#### 2. Errores de permisos

**Diagnóstico:**
```bash
ls -la api/enviar_recordatorios_auto.php
ls -la logs/
```

**Solución:**
```bash
chmod +x api/enviar_recordatorios_auto.php
chmod 755 logs/
```

#### 3. PHP no encontrado

**Diagnóstico:**
```bash
which php
```

**Solución:**
Actualizar el crontab con la ruta correcta de PHP

#### 4. No se envían recordatorios

**Diagnóstico:**
```sql
-- Verificar configuración
SELECT * FROM config_recordatorios
WHERE clave = 'recordatorios_automaticos_activos';

-- Ver clientes pendientes
SELECT * FROM v_recordatorios_pendientes_hoy;
```

**Solución:**
- Verificar que `recordatorios_automaticos_activos = true`
- Verificar que hay clientes en la vista
- Revisar logs para ver errores específicos

#### 5. Se envían demasiados recordatorios

**Solución:**
```sql
-- Aumentar días mínimos entre envíos
UPDATE config_recordatorios
SET valor = '5'
WHERE clave = 'dias_minimos_entre_recordatorios';

-- Reducir máximo por mes
UPDATE config_recordatorios
SET valor = '5'
WHERE clave = 'max_recordatorios_mes';
```

---

## ❓ Preguntas Frecuentes

### ¿Puedo cambiar la hora de envío?

Sí, de dos formas:

1. **Editar el crontab:**
```bash
crontab -e
# Cambiar la hora (primera columna después del minuto)
```

2. **Actualizar la configuración:**
```sql
UPDATE config_recordatorios
SET valor = '14:30'
WHERE clave = 'hora_envio_automatico';
```

### ¿Cómo desactivo temporalmente los recordatorios?

```sql
UPDATE config_recordatorios
SET valor = 'false'
WHERE clave = 'recordatorios_automaticos_activos';
```

O comenta la línea en el crontab:
```bash
crontab -e
# Agregar # al inicio de la línea
```

### ¿Puedo ejecutar recordatorios varias veces al día?

Sí, configura múltiples horarios en el cron:
```cron
0 9,15 * * * php /ruta/api/enviar_recordatorios_auto.php >> /ruta/logs/recordatorios_auto.log 2>&1
```

### ¿Cómo veo qué recordatorios se enviaron hoy?

```sql
SELECT
    c.razon_social,
    hr.tipo_recordatorio,
    hr.fecha_envio,
    hr.estado_envio
FROM historial_recordatorios hr
JOIN clientes c ON hr.cliente_id = c.id
WHERE DATE(hr.fecha_envio) = CURDATE()
ORDER BY hr.fecha_envio DESC;
```

### ¿Los recordatorios usan el sistema de cola?

Actualmente NO. El script de recordatorios automáticos envía directamente.
Para integración con cola, espera la Fase 2.

### ¿Puedo personalizar los mensajes?

Sí, edita la función `generarMensajeRecordatorioAuto()` en:
`api/enviar_recordatorios_auto.php` (líneas 380-440)

### ¿Cómo roto los logs?

Agrega al crontab:
```cron
0 2 1 * * find /ruta/logs -name "recordatorios_auto.log.*" -mtime +30 -delete
```

O usa `logrotate` (Linux):
```bash
# /etc/logrotate.d/imaginatics-recordatorios
/ruta/logs/recordatorios_auto.log {
    daily
    rotate 30
    compress
    missingok
    notifempty
}
```

---

## 📊 Métricas Disponibles

### Recordatorios por Tipo

```sql
SELECT
    tipo_recordatorio,
    COUNT(*) as total,
    SUM(CASE WHEN estado_envio = 'enviado' THEN 1 ELSE 0 END) as exitosos,
    SUM(CASE WHEN estado_envio = 'error' THEN 1 ELSE 0 END) as fallidos
FROM historial_recordatorios
WHERE fecha_envio >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
GROUP BY tipo_recordatorio;
```

### Efectividad por Cliente

```sql
SELECT
    c.razon_social,
    v.total_recordatorios,
    v.recordatorios_exitosos,
    ROUND((v.recordatorios_exitosos / v.total_recordatorios) * 100, 2) as tasa_exito
FROM v_estadisticas_recordatorios v
JOIN clientes c ON v.cliente_id = c.id
WHERE v.total_recordatorios > 0
ORDER BY tasa_exito ASC;
```

### Recordatorios Automáticos vs Manuales

```sql
SELECT
    fue_automatico,
    COUNT(*) as total,
    ROUND(AVG(CASE WHEN estado_envio = 'enviado' THEN 1 ELSE 0 END) * 100, 2) as tasa_exito
FROM historial_recordatorios
WHERE fecha_envio >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
GROUP BY fue_automatico;
```

---

## 🎯 Próximos Pasos (Fase 2)

En la siguiente fase implementaremos:

1. **Escalamiento progresivo** de mensajes según número de recordatorios
2. **Generación de imágenes en backend** (en lugar de frontend)
3. **Dashboard de métricas** y análisis de efectividad
4. **Canales alternativos** (Email, SMS)
5. **Personalización por cliente** (preferencias de frecuencia)
6. **IA para optimización** de horarios y mensajes

---

## 📞 Soporte

Para problemas o preguntas:
- Revisar logs: `tail -f logs/recordatorios_auto.log`
- Revisar base de datos: `SELECT * FROM historial_recordatorios ORDER BY id DESC LIMIT 10`
- Contactar: soporte@imaginatics.pe

---

**Versión:** 1.0.0
**Última actualización:** 19 de Noviembre, 2025
**Autor:** Imaginatics Peru SAC
