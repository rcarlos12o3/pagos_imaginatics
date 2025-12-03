# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## ⚠️ IMPORTANTE: Entornos y Seguridad de Datos

### Configuración Multi-Entorno (AUTO-DETECCIÓN)

El sistema **detecta automáticamente** el entorno y configura la base de datos correctamente:

| Entorno | Detección | DB Host | DB User | DB Pass | DEBUG_MODE |
|---------|-----------|---------|---------|---------|------------|
| **Local (Desarrollo)** | MySQL en 127.0.0.1 | `127.0.0.1` | `root` | ` ` (vacío) | `true` |
| **Producción (Docker)** | Hostname 'mysql' existe | `mysql` | `root` | `imaginatics123` | `false` |

**NO requiere cambios manuales** al hacer deploy. El archivo `config/database.php` detecta automáticamente el entorno.

### 🔒 Sistema de Migraciones Seguras

**NUNCA edites la base de datos directamente en producción.** Usa el sistema de migraciones que incluye 5 capas de protección:

```bash
# LOCAL: Ejecutar migración
./scripts/migrate.sh migrations/014_mi_migracion.sql

# LOCAL: Ver estado de migraciones
./scripts/migrate.sh --status

# LOCAL: Ejecutar todas las migraciones pendientes
./scripts/migrate.sh --all

# PRODUCCIÓN: Solo vía GitHub Actions (workflow manual "Run Migrations")
# REQUIERE confirmación explícita: escribir "EJECUTAR MIGRACIONES"
```

### Protecciones del Sistema de Migraciones

1. **Validación Automática**: Bloquea migraciones con comandos peligrosos (DROP TABLE, TRUNCATE, DELETE sin WHERE)
2. **Backup Automático**: Crea backup completo ANTES de cada migración
3. **Registro**: Tabla `_migraciones_aplicadas` registra qué migraciones se ejecutaron
4. **Rollback Fácil**: `./scripts/rollback_database.sh` restaura al último backup
5. **Aprobación Manual en Producción**: Las migraciones NUNCA se ejecutan automáticamente

### Comandos de Seguridad

```bash
# Crear backup manual
./scripts/backup_database.sh

# Validar migración (antes de ejecutarla)
./scripts/validate_migration.sh migrations/014_mi_migracion.sql

# Hacer rollback al último backup
./scripts/rollback_database.sh

# Rollback a backup específico
./scripts/rollback_database.sh /var/www/pagos_imaginatics/backups/auto/backup_20251202_120000.sql.gz
```

### Comandos PELIGROSOS Bloqueados

El validador detecta y **BLOQUEA** automáticamente:
- ❌ `DROP TABLE` sin `IF EXISTS`
- ❌ `TRUNCATE TABLE`
- ❌ `DELETE FROM` sin `WHERE`
- ❌ `DROP DATABASE`
- ⚠️ `UPDATE` sin `WHERE` (advertencia)
- ⚠️ `DROP COLUMN` (advertencia)

### Flujo de Trabajo Seguro

```bash
# 1. Desarrollo Local
cd /Users/pxndx1o2/Herd/pagos_imaginatics
./scripts/migrate.sh migrations/nueva_migracion.sql

# 2. Commit y Push
git add migrations/nueva_migracion.sql
git commit -m "Feat: Nueva migración segura"
git push origin master

# 3. Deploy Automático (código se despliega, migraciones NO)
# GitHub Actions ejecuta deploy automáticamente

# 4. Ejecutar Migraciones en Producción (MANUAL)
# Ve a GitHub → Actions → "Run Database Migrations" → Run workflow
# Escribe "EJECUTAR MIGRACIONES" para confirmar
```

## Descripción del Proyecto

Este es un sistema de consulta RUC basado en PHP para Imaginatics Perú SAC que genera órdenes de pago y las envía vía WhatsApp. El sistema incluye:

- **Frontend**: Página HTML única con JavaScript vanilla
- **Backend**: APIs PHP con base de datos MySQL
- **Infraestructura**: Auto-detección de entorno (Local/Docker)
- **Funciones principales**: Consulta RUC, gestión de clientes, generación de órdenes de pago, integración WhatsApp
- **Seguridad**: Sistema de migraciones con 5 capas de protección

## Comandos de Desarrollo

### Iniciar la Aplicación
```bash
# Iniciar contenedores
docker-compose up -d

# Acceder a la aplicación
# http://localhost:8080 - Aplicación principal
# http://localhost:8080/test_db.php - Test de conexión a base de datos
# http://localhost:8080/debug.html - Interfaz de debug
```

### Operaciones de Base de Datos

**IMPORTANTE**: El sistema **auto-detecta** el entorno y se conecta automáticamente a la BD correcta.

**En Local (Desarrollo):**
```bash
# La aplicación se conecta a: 127.0.0.1 (MySQL local)

# Acceder a MySQL local
mysql -h 127.0.0.1 -u root imaginatics_ruc

# Consultar clientes
mysql -h 127.0.0.1 -u root -e "USE imaginatics_ruc; SELECT * FROM clientes LIMIT 10;"

# ⚠️ NO hagas cambios directos. Usa migraciones:
./scripts/migrate.sh migrations/mi_cambio.sql
```

**En Producción (Docker):**
```bash
# La aplicación se conecta a: mysql (contenedor Docker)

# Acceder a MySQL en producción
docker exec -it imaginatics-mysql mysql -u root -pimaginations123 imaginatics_ruc

# ⚠️ NUNCA hagas cambios directos. Usa GitHub Actions:
# GitHub → Actions → "Run Database Migrations"
```

**Backups (Ambos Entornos):**
```bash
# Crear backup (detecta entorno automáticamente)
./scripts/backup_database.sh

# Los backups se guardan en:
# backups/auto/backup_YYYYMMDD_HHMMSS.sql.gz
```

### Gestión de Contenedores
```bash
# Ver logs
docker logs imaginatics-web
docker logs imaginatics-mysql

# Detener contenedores
docker-compose down

# Reconstruir contenedores
docker-compose down && docker-compose up -d --build
```

## Arquitectura

### Capa de Base de Datos (config/database.php)
- **Clase Database**: Wrapper PDO con declaraciones preparadas, soporte de transacciones y logging
- **Funciones auxiliares**: Respuestas JSON, validación de entrada, validación RUC/WhatsApp
- **Gestión de configuración**: Almacén clave-valor para tokens y configuraciones
- **Manejo de errores**: Manejadores globales de excepciones y errores con modos debug

### Capa API (api/)
- **consultar_ruc.php**: Consulta RUC con cache de 24 horas, integración API externa
- **clientes.php**: Operaciones CRUD para gestión de clientes + **Sistema de Análisis Inteligente**
- **envios.php**: Envío de mensajes WhatsApp, seguimiento de entregas y **Sistema de Cola**
- **procesar_cola.php**: Worker que procesa envíos en background automáticamente
- **diagnostico_especifico.php**: Verificaciones de salud del sistema y diagnósticos
- **debug_whatsapp.php**: Testing y debugging de la API de WhatsApp

### Frontend (js/)
- **config.js**: Constantes de aplicación, endpoints API, colores corporativos, cuentas bancarias
- **main.js**: Funciones principales de UI, inicialización del sistema, manejo de fechas
- **database.js**: Operaciones de base de datos del lado cliente, wrappers CRUD
- **csv.js**: Funcionalidad de importación/exportación CSV
- **whatsapp.js**: Plantillas de mensajes, envío masivo, estado de entrega
- **modulo-envios.js**: **Sistema de Análisis Inteligente** - Determina automáticamente qué empresas deben recibir órdenes

### Esquema de Base de Datos
- **clientes**: Información de clientes con RUC, montos, fechas de vencimiento
- **servicios_contratados**: Servicios contratados por cliente con periodicidad (mensual, trimestral, semestral, anual)
- **catalogo_servicios**: Catálogo de servicios disponibles
- **envios_whatsapp**: Seguimiento de entrega de mensajes y estado
- **sesiones_envio**: Sesiones de envío masivo con estadísticas
- **cola_envios**: Cola de trabajos de envío para procesamiento en background
- **consultas_ruc**: Cache de consultas RUC y respuestas API
- **configuracion**: Configuraciones del sistema y tokens API
- **logs_sistema**: Logging de aplicación y auditoría

## Patrones Clave

### Acceso a Base de Datos
```php
$database = new Database();
$db = $database->connect();
$result = $database->fetch("SELECT * FROM clientes WHERE ruc = ?", [$ruc]);
```

### Respuestas API
```php
jsonResponse(['success' => true, 'data' => $data]);
jsonResponse(['success' => false, 'error' => 'Mensaje'], 400);
```

### Llamadas API Frontend
```javascript
fetch(API_CLIENTES_BASE + "?action=list")
    .then(response => response.json())
    .then(data => processResult(data));
```

### Gestión de Configuración
- Usar `$database->getConfig($key)` y `$database->setConfig($key, $value)` para configuraciones
- Tokens API almacenados en tabla de configuración de base de datos
- Colores de marca corporativa y cuentas bancarias definidos en js/config.js

### Generación de Imágenes Canvas
- Órdenes de pago generadas como imágenes canvas para compartir en WhatsApp
- Imágenes de logo y mascota cargadas dinámicamente
- Escalado responsivo y estilo corporativo

## Sistema de Análisis Inteligente de Envíos

### Descripción
Sistema que analiza automáticamente qué empresas deben recibir órdenes de pago según reglas de negocio basadas en periodicidad y fechas de vencimiento.

### Reglas de Envío
- **Mensual**: Se envía 4 días antes del vencimiento (último día del mes)
- **Trimestral**: Se envía 7 días antes del vencimiento
- **Semestral**: Se envía 15 días antes del vencimiento
- **Anual**: Se envía 30 días antes del vencimiento

### Estados de Envío
- **dentro_del_plazo_ideal**: Estamos en la ventana de envío (entre fecha ideal y vencimiento)
- **fuera_del_plazo**: Ya pasó el vencimiento (orden atrasada) - DEBE enviarse
- **pendiente**: Aún no llega la fecha ideal
- **ya_enviado**: Ya se envió orden este periodo

### Endpoint Principal
```php
// api/clientes.php?action=analizar_envios_pendientes
// Retorna lista de servicios que deben recibir órdenes
```

### Validación de Seguridad
- ⚠️ **CRÍTICO**: Solo se envía a empresas que el sistema determine como pendientes
- No se permiten envíos masivos manuales
- Confirmación explícita antes de cada envío
- Trazabilidad total en base de datos

### Sistema de Cola
Los envíos se procesan mediante un sistema de cola:
1. Frontend genera imágenes y crea sesión
2. Trabajos se agregan a tabla `cola_envios`
3. Worker PHP (`api/procesar_cola.php`) procesa automáticamente
4. Estados actualizados en tiempo real

**Worker debe configurarse para ejecutarse automáticamente en producción** (ver DEPLOYMENT.md)

### Formato de Fechas
- **IMPORTANTE**: Frontend usa formato `dd/mm/yyyy` (14/11/2025)
- **Base de datos requiere**: `yyyy-mm-dd` (2025-11-14)
- **Conversión**: Función `convertirFechaAISO()` en `modulo-envios.js`

## Notas Importantes

- **Seguridad**: Usa declaraciones preparadas, validación de entrada y headers CORS
- **Cache**: Consultas RUC cacheadas por 24 horas para reducir llamadas API
- **Logging**: Todas las operaciones registradas en tabla logs_sistema
- **Manejo de Errores**: Toggle de modo debug para desarrollo vs producción
- **Integración WhatsApp**: Requiere tokens API válidos en configuración
- **Zona Horaria**: Configurada a America/Lima para operaciones peruanas
- **Worker**: `api/procesar_cola.php` debe ejecutarse periódicamente (cron/supervisor/systemd)
- **Deployment**: Ver `DEPLOYMENT.md` para instrucciones completas de producción