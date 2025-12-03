#!/bin/bash
# ============================================
# MIGRADOR SEGURO DE BASE DE DATOS
# ============================================
# Ejecuta migraciones con protección total contra pérdida de datos:
# 1. Validación de comandos peligrosos
# 2. Backup automático antes de ejecutar
# 3. Registro de migraciones aplicadas
# 4. Rollback fácil en caso de error
# ============================================

set -e

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

# Directorios
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
MIGRATIONS_DIR="$PROJECT_ROOT/migrations"
BACKUP_DIR="$PROJECT_ROOT/backups/auto"

# Crear directorio de backups si no existe
mkdir -p "$BACKUP_DIR"

# ============================================
# FUNCIONES
# ============================================

show_help() {
    echo ""
    echo -e "${CYAN}═══════════════════════════════════════════${NC}"
    echo -e "${CYAN}  MIGRADOR SEGURO - Imaginatics${NC}"
    echo -e "${CYAN}═══════════════════════════════════════════${NC}"
    echo ""
    echo "Uso:"
    echo "  ./migrate.sh <archivo>           Ejecutar migración específica"
    echo "  ./migrate.sh --all               Ejecutar todas las migraciones pendientes"
    echo "  ./migrate.sh --status            Ver estado de migraciones"
    echo "  ./migrate.sh --rollback          Hacer rollback al último backup"
    echo ""
    echo "Ejemplos:"
    echo "  ./migrate.sh migrations/014_reglas_periodicidad_centralizadas.sql"
    echo "  ./migrate.sh --all"
    echo ""
}

get_db_connection() {
    # Detectar si está en Docker o local
    if docker ps 2>/dev/null | grep -q "imaginatics-mysql"; then
        echo "docker"
    else
        echo "local"
    fi
}

execute_sql() {
    local sql=$1
    local env=$(get_db_connection)

    if [ "$env" == "docker" ]; then
        docker exec -i imaginatics-mysql mysql \
            -u root \
            -pimaginations123 \
            imaginatics_ruc <<< "$sql"
    else
        mysql \
            -h 127.0.0.1 \
            -u root \
            imaginatics_ruc <<< "$sql"
    fi
}

execute_sql_file() {
    local file=$1
    local env=$(get_db_connection)

    if [ "$env" == "docker" ]; then
        docker exec -i imaginatics-mysql mysql \
            -u root \
            -pimaginations123 \
            imaginatics_ruc < "$file"
    else
        mysql \
            -h 127.0.0.1 \
            -u root \
            imaginatics_ruc < "$file"
    fi
}

create_migration_table() {
    echo -e "${YELLOW}📋 Verificando tabla de migraciones...${NC}"

    execute_sql "
    CREATE TABLE IF NOT EXISTS _migraciones_aplicadas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        archivo VARCHAR(255) UNIQUE NOT NULL,
        ejecutado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        backup_antes VARCHAR(255),
        estado ENUM('exitosa', 'fallida', 'revertida') DEFAULT 'exitosa',
        INDEX idx_archivo (archivo),
        INDEX idx_ejecutado (ejecutado_en)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    " 2>/dev/null

    echo -e "${GREEN}✅ Tabla de migraciones lista${NC}"
}

is_migration_applied() {
    local filename=$(basename "$1")
    local result=$(execute_sql "SELECT COUNT(*) as count FROM _migraciones_aplicadas WHERE archivo = '$filename' AND estado = 'exitosa';" 2>/dev/null | tail -n 1)

    if [ "$result" -gt 0 ]; then
        return 0  # Ya aplicada
    else
        return 1  # No aplicada
    fi
}

register_migration() {
    local filename=$(basename "$1")
    local backup_file=$2
    local estado=${3:-exitosa}

    execute_sql "
    INSERT INTO _migraciones_aplicadas (archivo, backup_antes, estado)
    VALUES ('$filename', '$backup_file', '$estado')
    ON DUPLICATE KEY UPDATE
        estado = '$estado',
        ejecutado_en = CURRENT_TIMESTAMP;
    "
}

show_migration_status() {
    echo -e "${CYAN}═══════════════════════════════════════════${NC}"
    echo -e "${CYAN}  ESTADO DE MIGRACIONES${NC}"
    echo -e "${CYAN}═══════════════════════════════════════════${NC}"
    echo ""

    # Crear tabla si no existe
    create_migration_table

    # Obtener migraciones aplicadas
    echo -e "${GREEN}Migraciones aplicadas:${NC}"
    execute_sql "
    SELECT archivo, ejecutado_en, estado
    FROM _migraciones_aplicadas
    ORDER BY ejecutado_en DESC
    LIMIT 20;
    " | column -t -s $'\t'

    echo ""
    echo -e "${YELLOW}Migraciones disponibles:${NC}"
    ls -1 "$MIGRATIONS_DIR"/*.sql 2>/dev/null | while read -r migration; do
        filename=$(basename "$migration")
        if is_migration_applied "$migration"; then
            echo -e "${GREEN}✅${NC} $filename"
        else
            echo -e "${YELLOW}⏳${NC} $filename"
        fi
    done
    echo ""
}

run_migration() {
    local migration_file=$1
    local filename=$(basename "$migration_file")

    echo ""
    echo -e "${CYAN}═══════════════════════════════════════════${NC}"
    echo -e "${CYAN}  EJECUTANDO MIGRACIÓN${NC}"
    echo -e "${CYAN}═══════════════════════════════════════════${NC}"
    echo ""
    echo -e "${BLUE}Archivo: $filename${NC}"
    echo ""

    # Verificar que el archivo existe
    if [ ! -f "$migration_file" ]; then
        echo -e "${RED}❌ Error: Archivo no encontrado${NC}"
        exit 1
    fi

    # Verificar si ya fue aplicada
    if is_migration_applied "$migration_file"; then
        echo -e "${YELLOW}⚠️  Esta migración ya fue aplicada${NC}"
        read -p "¿Ejecutar de todas formas? (y/N): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            echo -e "${YELLOW}Migración cancelada${NC}"
            exit 0
        fi
    fi

    # PASO 1: Validar migración
    echo -e "${YELLOW}1️⃣  Validando migración...${NC}"
    if ! "$SCRIPT_DIR/validate_migration.sh" "$migration_file"; then
        echo -e "${RED}❌ Validación fallida - Migración bloqueada${NC}"
        exit 1
    fi
    echo ""

    # PASO 2: Crear backup
    echo -e "${YELLOW}2️⃣  Creando backup de seguridad...${NC}"
    if ! "$SCRIPT_DIR/backup_database.sh"; then
        echo -e "${RED}❌ Error al crear backup${NC}"
        exit 1
    fi
    BACKUP_FILE=$(head -n 1 "$BACKUP_DIR/LAST_BACKUP.txt")
    echo ""

    # PASO 3: Ejecutar migración
    echo -e "${YELLOW}3️⃣  Ejecutando migración...${NC}"
    if execute_sql_file "$migration_file"; then
        echo -e "${GREEN}✅ Migración ejecutada exitosamente${NC}"
        register_migration "$migration_file" "$BACKUP_FILE" "exitosa"

        echo ""
        echo -e "${GREEN}═══════════════════════════════════════════${NC}"
        echo -e "${GREEN}✅ MIGRACIÓN COMPLETADA${NC}"
        echo -e "${GREEN}═══════════════════════════════════════════${NC}"
        echo ""
        echo -e "${BLUE}Backup guardado en:${NC}"
        echo -e "${BLUE}$(basename $BACKUP_FILE)${NC}"
        echo ""
        echo -e "${YELLOW}Si algo salió mal, puedes hacer rollback con:${NC}"
        echo -e "${YELLOW}./scripts/rollback_database.sh${NC}"
        echo ""
    else
        echo -e "${RED}❌ Error al ejecutar migración${NC}"
        register_migration "$migration_file" "$BACKUP_FILE" "fallida"

        echo ""
        echo -e "${RED}═══════════════════════════════════════════${NC}"
        echo -e "${RED}❌ MIGRACIÓN FALLIDA${NC}"
        echo -e "${RED}═══════════════════════════════════════════${NC}"
        echo ""
        echo -e "${YELLOW}Para hacer rollback:${NC}"
        echo -e "${YELLOW}./scripts/rollback_database.sh${NC}"
        echo ""

        exit 1
    fi
}

run_all_pending() {
    echo -e "${CYAN}═══════════════════════════════════════════${NC}"
    echo -e "${CYAN}  EJECUTAR TODAS LAS MIGRACIONES PENDIENTES${NC}"
    echo -e "${CYAN}═══════════════════════════════════════════${NC}"
    echo ""

    create_migration_table

    PENDING=()
    while IFS= read -r migration; do
        if ! is_migration_applied "$migration"; then
            PENDING+=("$migration")
        fi
    done < <(ls -1 "$MIGRATIONS_DIR"/*.sql 2>/dev/null | sort)

    if [ ${#PENDING[@]} -eq 0 ]; then
        echo -e "${GREEN}✅ No hay migraciones pendientes${NC}"
        exit 0
    fi

    echo -e "${YELLOW}Migraciones pendientes:${NC}"
    for migration in "${PENDING[@]}"; do
        echo "  - $(basename $migration)"
    done
    echo ""

    read -p "¿Ejecutar todas? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo -e "${YELLOW}Operación cancelada${NC}"
        exit 0
    fi

    for migration in "${PENDING[@]}"; do
        run_migration "$migration"
        echo ""
        sleep 2
    done

    echo -e "${GREEN}✅ Todas las migraciones completadas${NC}"
}

# ============================================
# MAIN
# ============================================

# Crear tabla de migraciones
create_migration_table

case "${1:-}" in
    --help|-h)
        show_help
        ;;
    --status|-s)
        show_migration_status
        ;;
    --all|-a)
        run_all_pending
        ;;
    --rollback|-r)
        "$SCRIPT_DIR/rollback_database.sh"
        ;;
    "")
        show_help
        ;;
    *)
        run_migration "$1"
        ;;
esac
