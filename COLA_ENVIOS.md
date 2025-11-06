# Sistema de Cola de Envíos - Documentación

## Descripción

El sistema de cola permite procesar envíos de WhatsApp en segundo plano, sin depender del navegador. Los envíos se agregan a una cola y se procesan automáticamente.

## Ventajas

✅ **Sin pérdidas**: Si cierras el navegador o pierdes internet, los envíos continúan procesándose
✅ **Recuperable**: Si falla un envío, se reintenta automáticamente (hasta 3 intentos)
✅ **Monitoreable**: Puedes ver el progreso en tiempo real desde el historial
✅ **Escalable**: Puede procesar cientos de envíos sin sobrecargar el navegador

## Arquitectura

```
┌─────────────┐     ┌──────────────┐     ┌─────────────────┐
│  Frontend   │────▶│  API Envios  │────▶│  Cola (MySQL)   │
│  (Usuario)  │     │ (endpoints)  │     │  sesiones_envio │
└─────────────┘     └──────────────┘     │  cola_envios    │
                                          └─────────────────┘
                                                    │
                                                    ▼
                                          ┌─────────────────┐
                                          │   Procesador    │
                                          │  (cron/manual)  │
                                          └─────────────────┘
                                                    │
                                                    ▼
                                          ┌─────────────────┐
                                          │  API WhatsApp   │
                                          └─────────────────┘
```

## Cómo Funciona

### 1. Usuario Inicia Envío

Desde la interfaz web:
- Hace clic en "Enviar Lote" o "Enviar Recordatorios"
- El sistema genera las imágenes
- Se crea una **sesión de envío**
- Se agregan todos los trabajos a la **cola**
- El usuario puede cerrar el navegador

### 2. Procesador Automático

El procesador (`api/procesar_cola.php`) se ejecuta periódicamente y:
- Busca trabajos pendientes en la cola
- Procesa cada envío (imagen + texto)
- Respeta pausas aleatorias (modo cauteloso)
- Actualiza el estado en la base de datos
- Reintenta automáticamente si falla

### 3. Monitoreo

Desde `historial.html` puedes ver:
- Sesiones activas y su progreso
- Trabajos completados, pendientes y con error
- Detalles de cada envío

## Configuración

### Opción 1: Cron Job Automático (Recomendado)

El procesador debe ejecutarse cada 1-5 minutos para procesar la cola.

#### En macOS/Linux (crontab):

```bash
# Editar crontab
crontab -e

# Agregar línea (ejecutar cada 2 minutos):
*/2 * * * * /usr/bin/php /ruta/completa/pagos_imaginatics/api/procesar_cola.php >> /ruta/logs/cola.log 2>&1
```

**Ajustar la ruta:**
```bash
# Encuentra la ruta de PHP
which php

# Obtén la ruta completa del proyecto
pwd
```

Ejemplo completo:
```bash
*/2 * * * * /opt/homebrew/bin/php /Users/tu_usuario/Herd/pagos_imaginatics/api/procesar_cola.php >> /Users/tu_usuario/Herd/pagos_imaginatics/logs/cola.log 2>&1
```

#### En Windows (Programador de Tareas):

1. Abrir "Programador de tareas"
2. Crear tarea básica
3. **Desencadenador**: Repetir cada 2 minutos
4. **Acción**: Iniciar programa
   - Programa: `C:\php\php.exe`
   - Argumentos: `C:\xampp\htdocs\pagos_imaginatics\api\procesar_cola.php`
5. Guardar

### Opción 2: Ejecución Manual

Puedes ejecutar el procesador manualmente cuando sea necesario:

```bash
# Desde terminal
cd /Users/tu_usuario/Herd/pagos_imaginatics
php api/procesar_cola.php
```

O desde el navegador (temporal, para pruebas):
```
http://localhost:8080/api/procesar_cola.php
```

### Opción 3: Botón Manual en la Interfaz

Agregar un botón en `historial.html` que ejecute:

```javascript
async function procesarColaManual() {
    const response = await fetch('api/procesar_cola.php');
    alert('Procesador ejecutado');
}
```

## Verificar que Funciona

### 1. Crear un envío de prueba

- Ve a la página principal
- Selecciona 1-2 clientes
- Haz clic en "Enviar Lote"
- Verás el mensaje de éxito con el ID de sesión

### 2. Verificar en base de datos

```sql
-- Ver sesiones activas
SELECT * FROM sesiones_envio WHERE estado IN ('pendiente', 'procesando');

-- Ver trabajos pendientes
SELECT * FROM cola_envios WHERE estado = 'pendiente';
```

### 3. Ejecutar procesador

```bash
php api/procesar_cola.php
```

Deberías ver en consola:
```
[2025-11-03 10:00:00] 📦 Procesando sesión #1 - Tipo: orden_pago
[2025-11-03 10:00:01] 📋 5 trabajos pendientes en sesión #1
[2025-11-03 10:00:02] [1/5] Procesando: EMPRESA SAC
[2025-11-03 10:00:03]   📷 Enviando imagen...
[2025-11-03 10:00:05]   ✅ Imagen enviada
[2025-11-03 10:00:06]   💬 Enviando texto...
[2025-11-03 10:00:08]   ✅ Texto enviado
...
```

### 4. Verificar en historial

- Ve a `historial.html`
- Deberías ver la sesión con progreso actualizado

## Configuración Avanzada

### Ajustar Frecuencia de Cron

```bash
# Cada 1 minuto (muy frecuente)
* * * * * php /ruta/procesar_cola.php

# Cada 5 minutos (normal)
*/5 * * * * php /ruta/procesar_cola.php

# Solo en horario laboral (8am-8pm)
*/2 8-20 * * * php /ruta/procesar_cola.php
```

### Ajustar Pausas en el Procesador

Editar `api/procesar_cola.php`:

```php
// Línea ~12-14
define('PAUSA_ENTRE_MENSAJES', [5, 10]);    // Más rápido
define('PAUSA_ENTRE_CLIENTES', [15, 30]);   // Más rápido

// O más cauteloso (default):
define('PAUSA_ENTRE_MENSAJES', [10, 20]);   // Modo cauteloso
define('PAUSA_ENTRE_CLIENTES', [30, 60]);   // Modo cauteloso
```

### Procesar Más Trabajos por Ejecución

```php
// Línea ~11
define('MAX_TRABAJOS_POR_EJECUCION', 100);  // Más trabajos por ciclo
```

## Solución de Problemas

### El cron no se ejecuta

```bash
# Verificar que cron está corriendo
sudo launchctl list | grep cron

# Ver logs de cron (macOS)
tail -f /var/log/system.log | grep cron

# Ver logs del procesador
tail -f /ruta/logs/cola.log
```

### Los trabajos quedan en "procesando"

Si un trabajo se queda en "procesando" por más de 2 horas, se considera abandonado y será retomado:

```sql
-- Resetear trabajos abandonados manualmente
UPDATE cola_envios
SET estado = 'pendiente', intentos = 0
WHERE estado = 'procesando'
AND fecha_procesamiento < DATE_SUB(NOW(), INTERVAL 2 HOUR);
```

### Ver errores específicos

```sql
-- Ver trabajos con error
SELECT id, razon_social, mensaje_error, intentos
FROM cola_envios
WHERE estado = 'error'
ORDER BY fecha_creacion DESC;
```

### Reintentar un trabajo manualmente

Desde el historial puedes hacer clic en "Reintentar" o via API:

```javascript
await fetch('api/envios.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        action: 'reintentar_trabajo',
        trabajo_id: 123
    })
});
```

## Monitoreo y Estadísticas

### Ver estado de la cola

```sql
SELECT * FROM v_estadisticas_cola;
```

### Ver sesiones activas

```sql
SELECT * FROM v_sesiones_activas;
```

### Ver trabajos de una sesión

```sql
SELECT id, razon_social, estado, fecha_creacion, mensaje_error
FROM cola_envios
WHERE sesion_id = 1
ORDER BY fecha_creacion;
```

## Mantenimiento

### Limpiar sesiones antiguas (opcional)

```sql
-- Eliminar sesiones completadas de hace más de 30 días
DELETE FROM sesiones_envio
WHERE estado IN ('completado', 'cancelado')
AND fecha_finalizacion < DATE_SUB(NOW(), INTERVAL 30 DAY);
```

### Backup de la cola

```bash
# Exportar datos de la cola
mysqldump -u imaginatics -p imaginatics_ruc sesiones_envio cola_envios > cola_backup.sql
```

## Preguntas Frecuentes

**¿Necesito dejar el navegador abierto?**
No, una vez que agregues los envíos a la cola, puedes cerrar el navegador.

**¿Qué pasa si se va la luz durante el procesamiento?**
El procesador retomará los trabajos pendientes cuando se reinicie el cron.

**¿Puedo cancelar una sesión?**
Sí, desde el historial puedes cancelar sesiones y evitar que se procesen más trabajos.

**¿Cuánto tiempo tarda en procesarse todo?**
Aproximadamente 40-80 segundos por cliente (con pausas cautelosas). Una sesión de 50 clientes tarda ~30-60 minutos.

**¿Puedo ver el progreso en tiempo real?**
Sí, en `historial.html` verás el progreso actualizado cada vez que refresques la página.

## Próximos Pasos

1. ✅ Configurar cron job para ejecución automática
2. ✅ Probar con 1-2 clientes primero
3. ✅ Monitorear logs y verificar envíos exitosos
4. ✅ Escalar a lotes más grandes

---

**¿Necesitas ayuda?** Revisa los logs en `/logs/cola.log` o consulta la tabla `logs_sistema` en la base de datos.
