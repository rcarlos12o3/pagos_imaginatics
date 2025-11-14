# 🚀 GUÍA DE DEPLOYMENT - Sistema de Envíos Inteligente

## 📋 Resumen del Sistema

Este documento contiene las instrucciones para configurar en **producción** el **Sistema de Análisis Inteligente y Envío de Órdenes de Pago** desarrollado en local.

**Fecha de desarrollo**: Noviembre 2025
**Ambiente de desarrollo**: macOS con Herd (PHP 8.1, MySQL 8.0)
**Sistema**: Imaginatics Perú SAC - Generador de Órdenes de Pago

---

## 🎯 Características Principales Implementadas

### 1. **Análisis Inteligente de Envíos**
- Sistema que determina automáticamente qué empresas deben recibir órdenes de pago
- Basado en reglas de periodicidad (mensual, trimestral, semestral, anual)
- Calcula ventanas ideales de envío según tipo de servicio
- Previene envíos masivos erróneos (solo envía a empresas que corresponden)

### 2. **Sistema de Cola de Envíos**
- Procesamiento en background de envíos WhatsApp
- Worker PHP que procesa cola automáticamente
- Registro completo de sesiones y trabajos
- Reintentos automáticos en caso de error
- Trazabilidad total de envíos

### 3. **Interfaz Apple Human Interface Guidelines**
- Diseño moderno y limpio
- Tarjetas de servicio con información detallada
- Selección múltiple con checkboxes
- Feedback visual de progreso
- Confirmaciones antes de enviar

---

## 📁 Archivos Nuevos/Modificados

### ✨ Archivos NUEVOS:
```
js/modulo-envios.js          # Módulo frontend de análisis y envío
test_analisis.php            # Script de prueba (SOLO desarrollo)
test_api_analisis.php        # Script de prueba (SOLO desarrollo)
DEPLOYMENT.md                # Este archivo
```

### 📝 Archivos MODIFICADOS:
```
api/clientes.php             # Nuevo endpoint: analizar_envios_pendientes
api/envios.php              # Sistema de cola mejorado
api/procesar_cola.php       # Worker corregido (líneas 83-91)
index.php                   # Nueva vista de Envíos (líneas 985-1097)
index.php                   # Carga modulo-envios.js (línea 1293)
index.php                   # Inicialización módulo (líneas 1372-1375)
```

---

## 🗄️ Estructura de Base de Datos

### Tablas Utilizadas:

#### 1. `servicios_contratados`
Ya existe. El sistema lee:
- `fecha_vencimiento` → Fecha de vencimiento del periodo actual
- `periodo_facturacion` → Tipo: mensual, trimestral, semestral, anual
- `precio` → Monto a cobrar
- `estado` → Solo procesa servicios 'activo'

#### 2. `sesiones_envio`
Ya existe. El sistema crea sesiones con:
- `tipo_envio` → 'orden_pago'
- `configuracion` → JSON con metadata: `creado_desde`, `servicios_ids`

#### 3. `cola_envios`
Ya existe. El sistema agrega trabajos con:
- `imagen_base64` → Imagen generada de la orden de pago
- `fecha_vencimiento` → Formato: **YYYY-MM-DD** (importante)

#### 4. `envios_whatsapp`
Ya existe. Registro final de envíos.

---

## ⚙️ CONFIGURACIONES CRÍTICAS DE PRODUCCIÓN

### 1. **Base de Datos MySQL**

Verificar configuración en `config/database.php`:

```php
// PRODUCCIÓN - Ajustar según tu servidor
private $host = "localhost";        // o IP del servidor MySQL
private $db_name = "imaginatics_ruc";
private $username = "tu_usuario";
private $password = "tu_password";
```

**✅ Verificar conexión:**
```bash
mysql -h localhost -u tu_usuario -p imaginatics_ruc -e "SELECT COUNT(*) FROM servicios_contratados;"
```

---

### 2. **Configuración API WhatsApp**

El sistema requiere 3 valores en la tabla `configuracion`:

```sql
-- Verificar que existen:
SELECT clave, valor FROM configuracion
WHERE clave IN ('token_whatsapp', 'instancia_whatsapp', 'api_url_whatsapp');
```

**Si no existen, crearlos:**
```sql
INSERT INTO configuracion (clave, valor) VALUES
('token_whatsapp', 'TU_TOKEN_API'),
('instancia_whatsapp', 'TU_INSTANCIA_ID'),
('api_url_whatsapp', 'https://tu-api-whatsapp.com/');
```

**📝 Formato esperado del API URL:**
Debe terminar en `/` y permitir estas rutas:
- `message/sendmedia/{instancia}` → Enviar imagen
- `message/sendtext/{instancia}` → Enviar texto

---

### 3. **Worker Automático (CRON JOB)**

El worker `api/procesar_cola.php` **NO se ejecuta automáticamente**.

#### Opción A: Cron Job (Recomendado para producción)

```bash
# Editar crontab
crontab -e

# Agregar esta línea (ejecuta cada minuto)
* * * * * cd /ruta/completa/al/proyecto && php api/procesar_cola.php >> logs/worker.log 2>&1
```

#### Opción B: Supervisor (Para alta disponibilidad)

Crear archivo: `/etc/supervisor/conf.d/imaginatics-worker.conf`

```ini
[program:imaginatics-worker]
process_name=%(program_name)s
command=php /ruta/completa/al/proyecto/api/procesar_cola.php
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/log/imaginatics-worker.log
```

Activar:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start imaginatics-worker
```

#### Opción C: Systemd Service

Crear archivo: `/etc/systemd/system/imaginatics-worker.service`

```ini
[Unit]
Description=Imaginatics WhatsApp Worker
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/ruta/completa/al/proyecto
ExecStart=/usr/bin/php /ruta/completa/al/proyecto/api/procesar_cola.php
Restart=always
RestartSec=60

[Install]
WantedBy=multi-user.target
```

Activar:
```bash
sudo systemctl daemon-reload
sudo systemctl enable imaginatics-worker
sudo systemctl start imaginatics-worker
sudo systemctl status imaginatics-worker
```

---

### 4. **Permisos de Archivos**

```bash
# Permisos generales
sudo chown -R www-data:www-data /ruta/al/proyecto
sudo chmod -R 755 /ruta/al/proyecto

# Directorio de logs (si existe)
sudo chmod -R 775 /ruta/al/proyecto/logs
```

---

### 5. **Imágenes Requeridas**

El sistema genera canvas con estas imágenes:

```
logo.png     → Logo de Imaginatics (145x80px recomendado)
mascota.png  → Mascota/ilustración (200x200px recomendado)
```

**Ubicación**: Raíz del proyecto o carpeta `/img/`

**✅ Verificar que existen:**
```bash
ls -lh logo.png mascota.png
```

**⚠️ Si no existen**: El canvas se genera igual, pero sin imágenes.

---

### 6. **Configuración PHP**

Verificar en `php.ini`:

```ini
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 300
memory_limit = 256M
```

**✅ Verificar configuración actual:**
```bash
php -i | grep -E "upload_max_filesize|post_max_size|max_execution_time|memory_limit"
```

---

## 🧪 PRUEBAS EN PRODUCCIÓN

### 1. **Verificar Endpoint de Análisis**

```bash
curl "https://tu-dominio.com/api/clientes.php?action=analizar_envios_pendientes" | jq
```

**Respuesta esperada:**
```json
{
  "success": true,
  "data": {
    "servicios": [
      {
        "empresa": "EMPRESA X",
        "periodicidad": "anual",
        "estado": "dentro_del_plazo_ideal",
        "debe_enviarse": true,
        ...
      }
    ],
    "total": 1
  }
}
```

---

### 2. **Probar Worker Manualmente**

```bash
cd /ruta/al/proyecto
php api/procesar_cola.php
```

**Salida esperada:**
```
[2025-11-14 10:44:03] 📦 Procesando sesión #X
[2025-11-14 10:44:03]   📋 1 trabajos pendientes
[2025-11-14 10:44:03]   [1/1] Procesando: EMPRESA X
[2025-11-14 10:44:03]       📷 Enviando imagen...
[2025-11-14 10:44:08]       ✅ Imagen enviada
[2025-11-14 10:44:23]     ✅ Enviado exitosamente
```

---

### 3. **Verificar Logs del Sistema**

```sql
-- Ver últimos logs
SELECT nivel, modulo, mensaje, fecha_log
FROM logs_sistema
ORDER BY fecha_log DESC
LIMIT 20;
```

---

### 4. **Prueba End-to-End**

1. Abrir navegador: `https://tu-dominio.com`
2. Login al sistema
3. Ir a **Envíos** en el menú lateral
4. Esperar análisis (debería mostrar empresas que corresponden)
5. Seleccionar una empresa
6. Click en "Enviar Órdenes Seleccionadas"
7. Confirmar
8. Debería:
   - Generar imagen
   - Crear sesión en BD
   - Redirigir a historial
   - Worker procesa automáticamente (si está configurado)

---

## 🔒 VALIDACIONES DE SEGURIDAD IMPLEMENTADAS

✅ **No envíos masivos erróneos**: Solo se envía a empresas analizadas por el sistema
✅ **Confirmación explícita**: Usuario debe confirmar antes de enviar
✅ **Trazabilidad completa**: Cada envío registrado en BD
✅ **Prepared statements**: Todas las queries usan PDO preparado
✅ **Validación de fechas**: Conversión correcta dd/mm/yyyy → yyyy-mm-dd
✅ **Límite de reintentos**: Trabajos con fallas se reintentan máximo 3 veces

---

## 🐛 TROUBLESHOOTING

### Problema 1: "No se muestran empresas en Envíos"

**Causas posibles:**
- No hay servicios activos con vencimientos próximos
- Error de conexión a BD
- Error en JavaScript

**Solución:**
```bash
# Verificar endpoint manualmente
curl "https://tu-dominio.com/api/clientes.php?action=analizar_envios_pendientes"

# Verificar servicios activos
mysql -u user -p -e "SELECT COUNT(*) FROM servicios_contratados WHERE estado = 'activo';"

# Ver consola del navegador (F12) para errores JS
```

---

### Problema 2: "Envíos quedan en estado pendiente"

**Causa**: Worker no está ejecutándose

**Solución:**
```bash
# Verificar si el worker está corriendo
ps aux | grep procesar_cola

# Ver logs del worker
tail -f /var/log/imaginatics-worker.log

# Ejecutar manualmente
php api/procesar_cola.php
```

---

### Problema 3: "Error de fecha inválida"

**Error:** `Invalid datetime format: Incorrect date value: '14/11/2025'`

**Causa**: Fecha en formato dd/mm/yyyy en vez de yyyy-mm-dd

**Solución**: Ya está corregido en `js/modulo-envios.js` (función `convertirFechaAISO`)

**Verificar:**
```javascript
// En consola del navegador
ModuloEnvios.convertirFechaAISO('14/11/2025')
// Debe devolver: "2025-11-14"
```

---

### Problema 4: "Imágenes no se cargan en canvas"

**Causa**: Archivos `logo.png` o `mascota.png` no existen

**Solución:**
```bash
# Verificar archivos
ls -lh logo.png mascota.png

# Si no existen, el canvas se genera sin imágenes (aún funcional)
# Recomendado: Agregar las imágenes al proyecto
```

---

### Problema 5: "Worker da error de sintaxis"

**Error anterior:** `unexpected '+', expecting ::`

**Solución**: Ya está corregido en líneas 83-91 de `api/procesar_cola.php`

**Antes:**
```php
log_mensaje("  [{$index + 1}/{$trabajos[0]['count(*)']}]..."); // ❌
```

**Después:**
```php
$num_trabajo = $index + 1; // ✅
log_mensaje("  [{$num_trabajo}/{$total_trabajos}]...");
```

---

## 📊 REGLAS DE NEGOCIO IMPLEMENTADAS

### Ventanas Ideales de Envío:

| Periodicidad | Días de Anticipación | Fecha de Vencimiento |
|--------------|---------------------|----------------------|
| **Mensual**  | 4 días antes | Último día del mes |
| **Trimestral** | 7 días antes | 3 meses después - 1 día |
| **Semestral** | 15 días antes | 6 meses después - 1 día |
| **Anual** | 30 días antes | 12 meses después - 1 día |

### Estados de Envío:

- **dentro_del_plazo_ideal**: Hoy está entre (fecha_ideal) y (fecha_vencimiento)
- **fuera_del_plazo**: Ya pasó la fecha de vencimiento (orden atrasada)
- **pendiente**: Aún no llega la fecha ideal de envío
- **ya_enviado**: Ya se envió una orden este periodo

### Ejemplo de Cálculo:

```
Servicio: Anual
Fecha inicio: 13/11/2025
Fecha vencimiento: 13/11/2025
Fecha ideal envío: 14/10/2025 (30 días antes)
Siguiente vencimiento: 12/11/2026

Hoy: 14/11/2025
Estado: FUERA DEL PLAZO (venció ayer)
Acción: DEBE ENVIARSE ✅
```

---

## 📞 CONTACTOS Y SOPORTE

**Proyecto**: Sistema de Órdenes de Pago - Imaginatics Perú SAC
**Desarrollado**: Noviembre 2025
**Ambiente desarrollo**: macOS + Herd (PHP 8.1, MySQL 8.0)
**Ambiente producción**: A configurar según servidor

---

## ✅ CHECKLIST DE DEPLOYMENT

Antes de dar por terminado el deployment, verificar:

- [ ] Base de datos configurada y accesible
- [ ] Tabla `configuracion` tiene tokens de WhatsApp
- [ ] Worker configurado (cron/supervisor/systemd)
- [ ] Permisos de archivos correctos
- [ ] `logo.png` y `mascota.png` existen
- [ ] Endpoint `/api/clientes.php?action=analizar_envios_pendientes` responde
- [ ] Endpoint `/api/envios.php?action=crear_sesion_cola` responde
- [ ] Worker procesa cola correctamente (`php api/procesar_cola.php`)
- [ ] Prueba end-to-end exitosa (envío desde navegador)
- [ ] Logs del sistema se están generando
- [ ] WhatsApp API responde correctamente

---

## 📚 DOCUMENTACIÓN ADICIONAL

- **CLAUDE.md**: Instrucciones generales del proyecto
- **api/clientes.php** (líneas 1355-1578): Código de análisis inteligente
- **js/modulo-envios.js**: Código frontend completo
- **api/procesar_cola.php**: Worker de procesamiento

---

## 🎉 CONCLUSIÓN

Una vez completadas todas las configuraciones de este documento, el sistema estará listo para:

✅ Analizar automáticamente qué empresas deben recibir órdenes
✅ Generar imágenes de órdenes de pago profesionales
✅ Enviar por WhatsApp con sistema de cola
✅ Procesar envíos en background automáticamente
✅ Registrar y auditar todos los envíos
✅ Prevenir envíos masivos erróneos

**¡Éxito con el deployment!** 🚀
