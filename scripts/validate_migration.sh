#!/bin/bash
# ============================================
# VALIDADOR DE MIGRACIONES SEGURAS
# ============================================
# Detecta comandos PELIGROSOS que pueden borrar datos
# BLOQUEA migraciones inseguras antes de ejecutarlas
# ============================================

set -e

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

MIGRATION_FILE=$1

if [ -z "$MIGRATION_FILE" ]; then
    echo -e "${RED}❌ Error: Debes proporcionar el archivo de migración${NC}"
    echo "Uso: ./validate_migration.sh migrations/001_mi_migracion.sql"
    exit 1
fi

if [ ! -f "$MIGRATION_FILE" ]; then
    echo -e "${RED}❌ Error: Archivo no encontrado: $MIGRATION_FILE${NC}"
    exit 1
fi

echo -e "${BLUE}🔍 Validando migración: $(basename $MIGRATION_FILE)${NC}"
echo ""

WARNINGS=0
ERRORS=0

# ============================================
# REGLAS DE VALIDACIÓN
# ============================================

# 1. Detectar DROP TABLE sin IF EXISTS
if grep -i "DROP TABLE" "$MIGRATION_FILE" | grep -iv "IF EXISTS" > /dev/null; then
    echo -e "${RED}❌ PELIGRO: DROP TABLE sin 'IF EXISTS' detectado${NC}"
    echo -e "${RED}   Esto puede borrar tablas permanentemente${NC}"
    grep -in "DROP TABLE" "$MIGRATION_FILE" | grep -iv "IF EXISTS"
    ERRORS=$((ERRORS + 1))
fi

# 2. Detectar TRUNCATE TABLE
if grep -i "TRUNCATE" "$MIGRATION_FILE" > /dev/null; then
    echo -e "${RED}❌ PELIGRO: TRUNCATE detectado${NC}"
    echo -e "${RED}   Esto borrará TODOS los datos de la tabla${NC}"
    grep -in "TRUNCATE" "$MIGRATION_FILE"
    ERRORS=$((ERRORS + 1))
fi

# 3. Detectar DELETE sin WHERE
if grep -iE "DELETE\s+FROM.*;" "$MIGRATION_FILE" | grep -iv "WHERE" > /dev/null; then
    echo -e "${RED}❌ PELIGRO: DELETE sin WHERE detectado${NC}"
    echo -e "${RED}   Esto borrará TODOS los datos de la tabla${NC}"
    grep -inE "DELETE\s+FROM.*;" "$MIGRATION_FILE" | grep -iv "WHERE"
    ERRORS=$((ERRORS + 1))
fi

# 4. Detectar DROP DATABASE
if grep -i "DROP DATABASE" "$MIGRATION_FILE" > /dev/null; then
    echo -e "${RED}❌ PELIGRO CRÍTICO: DROP DATABASE detectado${NC}"
    echo -e "${RED}   Esto borrará TODA la base de datos${NC}"
    grep -in "DROP DATABASE" "$MIGRATION_FILE"
    ERRORS=$((ERRORS + 1))
fi

# 5. Advertencias (no bloquean, pero alertan)

# Detectar DROP INDEX/KEY
if grep -iE "DROP (INDEX|KEY)" "$MIGRATION_FILE" > /dev/null; then
    echo -e "${YELLOW}⚠️  ADVERTENCIA: DROP INDEX/KEY detectado${NC}"
    grep -inE "DROP (INDEX|KEY)" "$MIGRATION_FILE"
    WARNINGS=$((WARNINGS + 1))
fi

# Detectar ALTER TABLE DROP COLUMN
if grep -iE "ALTER TABLE.*DROP COLUMN" "$MIGRATION_FILE" > /dev/null; then
    echo -e "${YELLOW}⚠️  ADVERTENCIA: DROP COLUMN detectado${NC}"
    echo -e "${YELLOW}   Esto eliminará una columna y sus datos${NC}"
    grep -inE "ALTER TABLE.*DROP COLUMN" "$MIGRATION_FILE"
    WARNINGS=$((WARNINGS + 1))
fi

# Detectar UPDATE sin WHERE
if grep -iE "UPDATE.*SET.*;" "$MIGRATION_FILE" | grep -iv "WHERE" > /dev/null; then
    echo -e "${YELLOW}⚠️  ADVERTENCIA: UPDATE sin WHERE detectado${NC}"
    echo -e "${YELLOW}   Esto actualizará TODAS las filas de la tabla${NC}"
    grep -inE "UPDATE.*SET.*;" "$MIGRATION_FILE" | grep -iv "WHERE"
    WARNINGS=$((WARNINGS + 1))
fi

# ============================================
# RESULTADO
# ============================================

echo ""
echo -e "${BLUE}═══════════════════════════════════════════${NC}"

if [ $ERRORS -gt 0 ]; then
    echo -e "${RED}❌ MIGRACIÓN BLOQUEADA${NC}"
    echo -e "${RED}   Errores críticos: $ERRORS${NC}"
    echo -e "${RED}   Advertencias: $WARNINGS${NC}"
    echo ""
    echo -e "${YELLOW}Esta migración contiene comandos PELIGROSOS${NC}"
    echo -e "${YELLOW}que pueden BORRAR DATOS permanentemente.${NC}"
    echo ""
    echo -e "${YELLOW}Opciones:${NC}"
    echo "  1. Revisar y modificar la migración"
    echo "  2. Usar CREATE TABLE IF NOT EXISTS"
    echo "  3. Usar DROP TABLE IF EXISTS (solo si es intencional)"
    echo "  4. Agregar WHERE a DELETE/UPDATE"
    echo ""
    exit 1
elif [ $WARNINGS -gt 0 ]; then
    echo -e "${YELLOW}⚠️  MIGRACIÓN CON ADVERTENCIAS${NC}"
    echo -e "${YELLOW}   Advertencias: $WARNINGS${NC}"
    echo ""
    echo -e "${YELLOW}Esta migración tiene operaciones que modifican estructura.${NC}"
    echo -e "${YELLOW}Asegúrate de tener un backup antes de continuar.${NC}"
    echo ""
    exit 0
else
    echo -e "${GREEN}✅ MIGRACIÓN SEGURA${NC}"
    echo -e "${GREEN}   No se detectaron comandos peligrosos${NC}"
    echo ""
    exit 0
fi
