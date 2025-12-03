# 🔒 Sistema de Migraciones Seguras

## ⚠️ Protección Contra Pérdida de Datos

Este sistema fue diseñado después de un incidente de pérdida de datos en producción.
**NUNCA** se volverá a perder datos por una migración mal ejecutada.

## 🛡️ 5 Capas de Protección

### 1. ✅ Validación Automática
Bloquea migraciones con comandos peligrosos ANTES de ejecutarlas:
- `DROP TABLE` sin `IF EXISTS`
- `TRUNCATE TABLE`
- `DELETE FROM` sin `WHERE`
- `DROP DATABASE`

### 2. 💾 Backup Automático
Crea un backup completo de la BD ANTES de cada migración.
- Formato comprimido (.sql.gz)
- Timestamp para identificación
- Mantiene últimos 30 backups

### 3. 📝 Registro de Migraciones
Tabla `_migraciones_aplicadas` registra:
- Qué migraciones se ejecutaron
- Cuándo se ejecutaron
- Backup asociado a cada migración
- Estado (exitosa/fallida/revertida)

### 4. ⏪ Rollback Fácil
Un comando restaura la BD al estado previo:
```bash
./rollback_database.sh
```

### 5. 🚫 Aprobación Manual en Producción
Las migraciones NUNCA se ejecutan automáticamente.
Requieren confirmación explícita via GitHub Actions.

---

## 📋 Scripts Disponibles

### `migrate.sh` - Migrador Principal

Ejecuta migraciones con todas las protecciones.

```bash
# Ejecutar migración específica
./migrate.sh migrations/014_mi_migracion.sql

# Ver estado de migraciones
./migrate.sh --status

# Ejecutar todas las migraciones pendientes
./migrate.sh --all

# Hacer rollback
./migrate.sh --rollback

# Ayuda
./migrate.sh --help
```

**Proceso automático:**
1. ✅ Valida la migración
2. 💾 Crea backup
3. 🚀 Ejecuta migración
4. 📝 Registra en BD
5. ✅ Confirma éxito

**En caso de error:**
- Marca migración como fallida
- Mantiene el backup
- Muestra comando de rollback

---

### `backup_database.sh` - Creador de Backups

Crea backup completo de la base de datos.

```bash
# Crear backup
./backup_database.sh
```

**Características:**
- Auto-detecta entorno (Docker/Local)
- Comprime con gzip
- Guarda timestamp
- Mantiene últimos 30 backups
- Registra última ubicación

**Ubicación:**
```
backups/auto/backup_YYYYMMDD_HHMMSS.sql.gz
```

---

### `validate_migration.sh` - Validador de Seguridad

Valida migración SIN ejecutarla.

```bash
# Validar migración
./validate_migration.sh migrations/014_mi_migracion.sql
```

**Detecta:**
- ❌ `DROP TABLE` sin `IF EXISTS`
- ❌ `TRUNCATE TABLE`
- ❌ `DELETE FROM` sin `WHERE`
- ❌ `DROP DATABASE`
- ⚠️ `UPDATE` sin `WHERE`
- ⚠️ `ALTER TABLE ... DROP COLUMN`

**Estados de salida:**
- `0` - Migración segura o con advertencias
- `1` - Migración bloqueada (comandos peligrosos)

---

### `rollback_database.sh` - Restaurador de Backups

Restaura BD desde backup.

```bash
# Rollback al último backup
./rollback_database.sh

# Rollback a backup específico
./rollback_database.sh backups/auto/backup_20251202_120000.sql.gz
```

**Proceso seguro:**
1. ⚠️ Pide confirmación (escribir "CONFIRMO")
2. 💾 Crea backup del estado actual (antes del rollback)
3. 🔄 Restaura desde backup especificado
4. ✅ Verifica que la restauración funcionó

**Importante:**
- Siempre crea un backup de seguridad antes del rollback
- Si algo sale mal en el rollback, puedes volver al estado previo

---

## 🔄 Flujo Completo

### Desarrollo Local

```bash
# 1. Crear archivo de migración
nano migrations/015_mi_nueva_funcionalidad.sql

# 2. Validar (opcional pero recomendado)
./scripts/validate_migration.sh migrations/015_mi_nueva_funcionalidad.sql

# 3. Ejecutar
./scripts/migrate.sh migrations/015_mi_nueva_funcionalidad.sql

# 4. Verificar
./scripts/migrate.sh --status

# 5. Si algo salió mal, rollback
./scripts/rollback_database.sh
```

### Producción (GitHub Actions)

```bash
# 1. Commit y push
git add migrations/015_mi_nueva_funcionalidad.sql
git commit -m "Feat: Nueva funcionalidad"
git push origin master

# 2. Deploy automático (código se despliega, migraciones NO)
# GitHub Actions ejecuta CI/CD automáticamente

# 3. Ejecutar migraciones (MANUAL)
# GitHub → Actions → "Run Database Migrations"
# Click "Run workflow"
# Escribir "EJECUTAR MIGRACIONES" para confirmar
# Click "Run workflow"

# 4. Si algo sale mal:
# SSH al servidor
ssh usuario@servidor
cd /var/www/pagos_imaginatics
./scripts/rollback_database.sh
```

---

## 📊 Tabla de Migraciones

El sistema crea automáticamente la tabla `_migraciones_aplicadas`:

```sql
CREATE TABLE _migraciones_aplicadas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    archivo VARCHAR(255) UNIQUE NOT NULL,
    ejecutado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    backup_antes VARCHAR(255),
    estado ENUM('exitosa', 'fallida', 'revertida') DEFAULT 'exitosa',
    INDEX idx_archivo (archivo),
    INDEX idx_ejecutado (ejecutado_en)
);
```

**Ver historial:**
```sql
SELECT * FROM _migraciones_aplicadas ORDER BY ejecutado_en DESC;
```

---

## 🚨 Comandos de Emergencia

### En caso de error crítico en producción

```bash
# SSH al servidor
ssh usuario@161.97.100.196

# Ir al directorio del proyecto
cd /var/www/pagos_imaginatics

# Ver backups disponibles
ls -lh backups/auto/

# Rollback inmediato al último backup
./scripts/rollback_database.sh

# O rollback a backup específico
./scripts/rollback_database.sh backups/auto/backup_20251202_120000.sql.gz
```

### Verificar estado después del rollback

```bash
# Verificar tablas
docker exec -i imaginatics-mysql mysql \
  -u root -pimaginations123 imaginatics_ruc \
  -e "SHOW TABLES;"

# Verificar datos
docker exec -i imaginatics-mysql mysql \
  -u root -pimaginations123 imaginatics_ruc \
  -e "SELECT COUNT(*) FROM clientes;"

# Verificar aplicación
curl http://localhost:8080
```

---

## 📝 Mejores Prácticas

### ✅ DO (Hacer)

1. **Siempre validar** antes de ejecutar en producción
2. **Probar en local** antes de subir al repo
3. **Usar `IF NOT EXISTS`** para CREATE TABLE
4. **Usar `IF EXISTS`** para DROP (si es necesario)
5. **Agregar WHERE** a todos los DELETE/UPDATE
6. **Documentar** qué hace cada migración
7. **Nombrar claramente** los archivos: `015_descripcion_clara.sql`

### ❌ DON'T (No hacer)

1. **NUNCA** ejecutar migraciones directamente con mysql
2. **NUNCA** hacer DROP sin IF EXISTS
3. **NUNCA** usar TRUNCATE en producción sin backup
4. **NUNCA** hacer DELETE sin WHERE
5. **NUNCA** modificar migraciones ya ejecutadas
6. **NUNCA** borrar backups manualmente
7. **NUNCA** ejecutar migraciones en producción sin aprobar workflow

---

## 🔐 Seguridad

- Backups **NUNCA** se suben al repositorio (ver `.gitignore`)
- Contraseñas **NUNCA** en el código (auto-detección de entorno)
- DEBUG_MODE automático: `true` en local, `false` en producción
- Migraciones en producción requieren aprobación manual

---

## 📞 Soporte

Si algo sale mal:

1. **NO PÁNICO** - Todos los backups están guardados
2. Revisa los logs del script que falló
3. Ve a `backups/auto/` y encuentra el backup más reciente
4. Ejecuta `./scripts/rollback_database.sh`
5. Reporta el issue en GitHub con los logs

**Los datos están protegidos. Siempre hay un camino de vuelta.**

---

## 🎯 Resumen

- ✅ 5 capas de protección contra pérdida de datos
- ✅ Backups automáticos antes de cada cambio
- ✅ Validación de comandos peligrosos
- ✅ Rollback en 1 comando
- ✅ Auto-detección de entorno
- ✅ Aprobación manual en producción
- ✅ Historial completo de migraciones

**Nunca más perderás datos por una migración.**
