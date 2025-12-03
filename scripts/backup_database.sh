#!/bin/bash
# ============================================
# SCRIPT DE BACKUP AUTOMÁTICO DE BASE DE DATOS
# ============================================
# Crea backups antes de migraciones
# Guarda con timestamp para poder hacer rollback
# ============================================

set -e  # Detener en caso de error

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Crear directorio de backups si no existe
BACKUP_DIR="/var/www/pagos_imaginatics/backups/auto"
mkdir -p "$BACKUP_DIR"

# Timestamp
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_FILE="$BACKUP_DIR/backup_${TIMESTAMP}.sql"

# Detectar entorno
if docker ps | grep -q "imaginatics-mysql"; then
    ENVIRONMENT="docker"
    echo -e "${BLUE}🐳 Entorno detectado: Docker${NC}"

    # Backup desde contenedor Docker
    echo -e "${YELLOW}📦 Creando backup de base de datos...${NC}"
    docker exec imaginatics-mysql mysqldump \
        -u root \
        -pimaginations123 \
        --single-transaction \
        --routines \
        --triggers \
        --events \
        --hex-blob \
        imaginatics_ruc > "$BACKUP_FILE"
else
    ENVIRONMENT="local"
    echo -e "${BLUE}💻 Entorno detectado: Local${NC}"

    # Backup desde MySQL local
    echo -e "${YELLOW}📦 Creando backup de base de datos...${NC}"
    mysqldump \
        -h 127.0.0.1 \
        -u root \
        --single-transaction \
        --routines \
        --triggers \
        --events \
        --hex-blob \
        imaginatics_ruc > "$BACKUP_FILE"
fi

# Verificar que el backup se creó correctamente
if [ -f "$BACKUP_FILE" ] && [ -s "$BACKUP_FILE" ]; then
    FILE_SIZE=$(du -h "$BACKUP_FILE" | cut -f1)
    echo -e "${GREEN}✅ Backup creado exitosamente${NC}"
    echo -e "${GREEN}   Archivo: $BACKUP_FILE${NC}"
    echo -e "${GREEN}   Tamaño: $FILE_SIZE${NC}"

    # Comprimir backup
    echo -e "${YELLOW}🗜️  Comprimiendo backup...${NC}"
    gzip "$BACKUP_FILE"
    COMPRESSED_SIZE=$(du -h "${BACKUP_FILE}.gz" | cut -f1)
    echo -e "${GREEN}✅ Backup comprimido: ${COMPRESSED_SIZE}${NC}"

    # Guardar referencia al último backup
    echo "${BACKUP_FILE}.gz" > "$BACKUP_DIR/LAST_BACKUP.txt"
    echo "$TIMESTAMP" >> "$BACKUP_DIR/LAST_BACKUP.txt"

    # Limpiar backups antiguos (mantener últimos 30)
    echo -e "${YELLOW}🧹 Limpiando backups antiguos (manteniendo últimos 30)...${NC}"
    ls -t "$BACKUP_DIR"/backup_*.sql.gz 2>/dev/null | tail -n +31 | xargs -r rm

    TOTAL_BACKUPS=$(ls -1 "$BACKUP_DIR"/backup_*.sql.gz 2>/dev/null | wc -l)
    echo -e "${GREEN}📊 Total de backups disponibles: $TOTAL_BACKUPS${NC}"

    echo ""
    echo -e "${GREEN}═══════════════════════════════════════════${NC}"
    echo -e "${GREEN}✅ BACKUP COMPLETADO EXITOSAMENTE${NC}"
    echo -e "${GREEN}═══════════════════════════════════════════${NC}"
    echo ""

    exit 0
else
    echo -e "${RED}❌ ERROR: El backup falló o está vacío${NC}"
    echo -e "${RED}   Archivo: $BACKUP_FILE${NC}"
    exit 1
fi
