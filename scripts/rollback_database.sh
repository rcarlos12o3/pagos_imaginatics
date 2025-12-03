#!/bin/bash
# ============================================
# SCRIPT DE ROLLBACK DE BASE DE DATOS
# ============================================
# Restaura desde el último backup o uno específico
# ============================================

set -e

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

BACKUP_DIR="/var/www/pagos_imaginatics/backups/auto"
BACKUP_FILE=$1

# Si no se especifica backup, usar el último
if [ -z "$BACKUP_FILE" ]; then
    if [ -f "$BACKUP_DIR/LAST_BACKUP.txt" ]; then
        BACKUP_FILE=$(head -n 1 "$BACKUP_DIR/LAST_BACKUP.txt")
        BACKUP_TIME=$(tail -n 1 "$BACKUP_DIR/LAST_BACKUP.txt")
        echo -e "${YELLOW}No se especificó backup, usando el último${NC}"
        echo -e "${BLUE}Backup: $(basename $BACKUP_FILE)${NC}"
        echo -e "${BLUE}Fecha: $BACKUP_TIME${NC}"
    else
        echo -e "${RED}❌ Error: No se encontró ningún backup${NC}"
        echo "Uso: ./rollback_database.sh [ruta/al/backup.sql.gz]"
        exit 1
    fi
fi

# Verificar que el archivo existe
if [ ! -f "$BACKUP_FILE" ]; then
    echo -e "${RED}❌ Error: Backup no encontrado: $BACKUP_FILE${NC}"
    exit 1
fi

# Confirmación
echo ""
echo -e "${RED}═══════════════════════════════════════════${NC}"
echo -e "${RED}⚠️  ADVERTENCIA: ROLLBACK DE BASE DE DATOS${NC}"
echo -e "${RED}═══════════════════════════════════════════${NC}"
echo ""
echo -e "${YELLOW}Esto restaurará la base de datos al estado del backup:${NC}"
echo -e "${BLUE}Archivo: $(basename $BACKUP_FILE)${NC}"
echo ""
echo -e "${RED}TODOS LOS CAMBIOS POSTERIORES SE PERDERÁN${NC}"
echo ""
read -p "¿Estás ABSOLUTAMENTE seguro? Escribe 'CONFIRMO' para continuar: " CONFIRMACION

if [ "$CONFIRMACION" != "CONFIRMO" ]; then
    echo -e "${YELLOW}Rollback cancelado${NC}"
    exit 0
fi

# Crear backup de seguridad antes del rollback
echo ""
echo -e "${YELLOW}1️⃣  Creando backup de seguridad del estado actual...${NC}"
SAFETY_BACKUP="/var/www/pagos_imaginatics/backups/auto/before_rollback_$(date +%Y%m%d_%H%M%S).sql.gz"

# Detectar entorno
if docker ps | grep -q "imaginatics-mysql"; then
    docker exec imaginatics-mysql mysqldump \
        -u root \
        -pimaginations123 \
        --single-transaction \
        imaginatics_ruc | gzip > "$SAFETY_BACKUP"
else
    mysqldump \
        -h 127.0.0.1 \
        -u root \
        --single-transaction \
        imaginatics_ruc | gzip > "$SAFETY_BACKUP"
fi

echo -e "${GREEN}✅ Backup de seguridad creado: $(basename $SAFETY_BACKUP)${NC}"

# Restaurar desde backup
echo ""
echo -e "${YELLOW}2️⃣  Restaurando base de datos desde backup...${NC}"

if [[ "$BACKUP_FILE" == *.gz ]]; then
    # Descomprimir y restaurar
    if docker ps | grep -q "imaginatics-mysql"; then
        gunzip < "$BACKUP_FILE" | docker exec -i imaginatics-mysql mysql \
            -u root \
            -pimaginations123 \
            imaginatics_ruc
    else
        gunzip < "$BACKUP_FILE" | mysql \
            -h 127.0.0.1 \
            -u root \
            imaginatics_ruc
    fi
else
    # Restaurar directamente
    if docker ps | grep -q "imaginatics-mysql"; then
        docker exec -i imaginatics-mysql mysql \
            -u root \
            -pimaginations123 \
            imaginatics_ruc < "$BACKUP_FILE"
    else
        mysql \
            -h 127.0.0.1 \
            -u root \
            imaginatics_ruc < "$BACKUP_FILE"
    fi
fi

# Verificar
echo ""
echo -e "${YELLOW}3️⃣  Verificando restauración...${NC}"

if docker ps | grep -q "imaginatics-mysql"; then
    TABLE_COUNT=$(docker exec imaginatics-mysql mysql \
        -u root \
        -pimaginations123 \
        -Ns imaginatics_ruc \
        -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'imaginatics_ruc'")
else
    TABLE_COUNT=$(mysql \
        -h 127.0.0.1 \
        -u root \
        -Ns imaginatics_ruc \
        -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'imaginatics_ruc'")
fi

echo -e "${GREEN}✅ Base de datos restaurada${NC}"
echo -e "${GREEN}   Tablas encontradas: $TABLE_COUNT${NC}"

echo ""
echo -e "${GREEN}═══════════════════════════════════════════${NC}"
echo -e "${GREEN}✅ ROLLBACK COMPLETADO EXITOSAMENTE${NC}"
echo -e "${GREEN}═══════════════════════════════════════════${NC}"
echo ""
echo -e "${BLUE}Backup de seguridad guardado en:${NC}"
echo -e "${BLUE}$SAFETY_BACKUP${NC}"
echo ""
