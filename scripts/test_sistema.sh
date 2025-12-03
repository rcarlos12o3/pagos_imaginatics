#!/bin/bash
# ============================================
# TEST DEL SISTEMA DE SEGURIDAD
# ============================================
# Verifica que todas las protecciones funcionen
# ============================================

set -e

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

echo -e "${CYAN}╔════════════════════════════════════════╗${NC}"
echo -e "${CYAN}║  TEST DEL SISTEMA DE SEGURIDAD         ║${NC}"
echo -e "${CYAN}╔════════════════════════════════════════╗${NC}"
echo ""

TESTS_PASSED=0
TESTS_FAILED=0

# ============================================
# TEST 1: Scripts ejecutables
# ============================================
echo -e "${BLUE}TEST 1: Verificando permisos de scripts...${NC}"

SCRIPTS=(
    "backup_database.sh"
    "validate_migration.sh"
    "rollback_database.sh"
    "migrate.sh"
)

for script in "${SCRIPTS[@]}"; do
    if [ -x "$SCRIPT_DIR/$script" ]; then
        echo -e "  ${GREEN}✅${NC} $script es ejecutable"
        TESTS_PASSED=$((TESTS_PASSED + 1))
    else
        echo -e "  ${RED}❌${NC} $script NO es ejecutable"
        echo -e "     ${YELLOW}Ejecuta: chmod +x scripts/$script${NC}"
        TESTS_FAILED=$((TESTS_FAILED + 1))
    fi
done

echo ""

# ============================================
# TEST 2: Directorio de backups
# ============================================
echo -e "${BLUE}TEST 2: Verificando directorio de backups...${NC}"

BACKUP_DIR="$PROJECT_ROOT/backups/auto"

if [ -d "$BACKUP_DIR" ]; then
    echo -e "  ${GREEN}✅${NC} Directorio de backups existe"
    TESTS_PASSED=$((TESTS_PASSED + 1))
else
    echo -e "  ${YELLOW}⚠️${NC}  Creando directorio de backups..."
    mkdir -p "$BACKUP_DIR"
    if [ -d "$BACKUP_DIR" ]; then
        echo -e "  ${GREEN}✅${NC} Directorio creado exitosamente"
        TESTS_PASSED=$((TESTS_PASSED + 1))
    else
        echo -e "  ${RED}❌${NC} No se pudo crear directorio"
        TESTS_FAILED=$((TESTS_FAILED + 1))
    fi
fi

echo ""

# ============================================
# TEST 3: Conexión a base de datos
# ============================================
echo -e "${BLUE}TEST 3: Verificando conexión a base de datos...${NC}"

# Detectar entorno
if docker ps 2>/dev/null | grep -q "imaginatics-mysql"; then
    ENVIRONMENT="docker"
    echo -e "  ${BLUE}🐳${NC} Entorno detectado: Docker"

    if docker exec imaginatics-mysql mysql -u root -pimaginations123 -e "SELECT 1" > /dev/null 2>&1; then
        echo -e "  ${GREEN}✅${NC} Conexión a MySQL (Docker) exitosa"
        TESTS_PASSED=$((TESTS_PASSED + 1))
    else
        echo -e "  ${RED}❌${NC} No se puede conectar a MySQL en Docker"
        TESTS_FAILED=$((TESTS_FAILED + 1))
    fi
else
    ENVIRONMENT="local"
    echo -e "  ${BLUE}💻${NC} Entorno detectado: Local"

    if mysql -h 127.0.0.1 -u root -e "SELECT 1" > /dev/null 2>&1; then
        echo -e "  ${GREEN}✅${NC} Conexión a MySQL (Local) exitosa"
        TESTS_PASSED=$((TESTS_PASSED + 1))
    else
        echo -e "  ${RED}❌${NC} No se puede conectar a MySQL local"
        TESTS_FAILED=$((TESTS_FAILED + 1))
    fi
fi

echo ""

# ============================================
# TEST 4: Validador de migraciones
# ============================================
echo -e "${BLUE}TEST 4: Probando validador de migraciones...${NC}"

# Crear migración de prueba PELIGROSA
TEST_MIGRATION="/tmp/test_migration_dangerous.sql"
cat > "$TEST_MIGRATION" << 'EOF'
-- Migración de prueba PELIGROSA
TRUNCATE TABLE clientes;
EOF

echo -e "  ${YELLOW}📝${NC} Creando migración de prueba con TRUNCATE..."

if "$SCRIPT_DIR/validate_migration.sh" "$TEST_MIGRATION" > /dev/null 2>&1; then
    echo -e "  ${RED}❌${NC} El validador NO bloqueó TRUNCATE (FALLO)"
    TESTS_FAILED=$((TESTS_FAILED + 1))
else
    echo -e "  ${GREEN}✅${NC} El validador bloqueó TRUNCATE correctamente"
    TESTS_PASSED=$((TESTS_PASSED + 1))
fi

# Limpiar
rm -f "$TEST_MIGRATION"

# Crear migración de prueba SEGURA
TEST_MIGRATION_SAFE="/tmp/test_migration_safe.sql"
cat > "$TEST_MIGRATION_SAFE" << 'EOF'
-- Migración de prueba SEGURA
CREATE TABLE IF NOT EXISTS test_table (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
EOF

echo -e "  ${YELLOW}📝${NC} Creando migración de prueba segura..."

if "$SCRIPT_DIR/validate_migration.sh" "$TEST_MIGRATION_SAFE" > /dev/null 2>&1; then
    echo -e "  ${GREEN}✅${NC} El validador aprobó migración segura"
    TESTS_PASSED=$((TESTS_PASSED + 1))
else
    echo -e "  ${RED}❌${NC} El validador bloqueó migración segura (FALLO)"
    TESTS_FAILED=$((TESTS_FAILED + 1))
fi

# Limpiar
rm -f "$TEST_MIGRATION_SAFE"

echo ""

# ============================================
# TEST 5: Auto-detección en PHP
# ============================================
echo -e "${BLUE}TEST 5: Verificando auto-detección en PHP...${NC}"

PHP_TEST=$(php -r "
require '$PROJECT_ROOT/config/database.php';
echo DB_HOST . '|' . ENVIRONMENT;
")

DB_HOST=$(echo "$PHP_TEST" | cut -d'|' -f1)
ENV=$(echo "$PHP_TEST" | cut -d'|' -f2)

if [ "$ENVIRONMENT" == "docker" ]; then
    if [ "$DB_HOST" == "mysql" ] && [ "$ENV" == "production" ]; then
        echo -e "  ${GREEN}✅${NC} Auto-detección correcta: Docker → mysql (production)"
        TESTS_PASSED=$((TESTS_PASSED + 1))
    else
        echo -e "  ${RED}❌${NC} Auto-detección incorrecta"
        echo -e "     Detectado: $DB_HOST ($ENV)"
        echo -e "     Esperado: mysql (production)"
        TESTS_FAILED=$((TESTS_FAILED + 1))
    fi
else
    if [ "$DB_HOST" == "127.0.0.1" ] && [ "$ENV" == "local" ]; then
        echo -e "  ${GREEN}✅${NC} Auto-detección correcta: Local → 127.0.0.1 (local)"
        TESTS_PASSED=$((TESTS_PASSED + 1))
    else
        echo -e "  ${RED}❌${NC} Auto-detección incorrecta"
        echo -e "     Detectado: $DB_HOST ($ENV)"
        echo -e "     Esperado: 127.0.0.1 (local)"
        TESTS_FAILED=$((TESTS_FAILED + 1))
    fi
fi

echo ""

# ============================================
# TEST 6: .gitignore
# ============================================
echo -e "${BLUE}TEST 6: Verificando .gitignore...${NC}"

GITIGNORE="$PROJECT_ROOT/.gitignore"

if [ -f "$GITIGNORE" ]; then
    if grep -q "backups/" "$GITIGNORE" && grep -q "*.sql.gz" "$GITIGNORE"; then
        echo -e "  ${GREEN}✅${NC} Backups excluidos del repositorio"
        TESTS_PASSED=$((TESTS_PASSED + 1))
    else
        echo -e "  ${YELLOW}⚠️${NC}  Backups no están en .gitignore"
        echo -e "     ${YELLOW}Recomendación: Agregar 'backups/' y '*.sql.gz'${NC}"
        TESTS_FAILED=$((TESTS_FAILED + 1))
    fi
else
    echo -e "  ${RED}❌${NC} No existe .gitignore"
    TESTS_FAILED=$((TESTS_FAILED + 1))
fi

echo ""

# ============================================
# RESULTADOS
# ============================================
TOTAL_TESTS=$((TESTS_PASSED + TESTS_FAILED))

echo ""
echo -e "${CYAN}╔════════════════════════════════════════╗${NC}"
echo -e "${CYAN}║  RESULTADOS                            ║${NC}"
echo -e "${CYAN}╔════════════════════════════════════════╗${NC}"
echo ""
echo -e "  Total de tests: $TOTAL_TESTS"
echo -e "  ${GREEN}✅ Pasados: $TESTS_PASSED${NC}"
echo -e "  ${RED}❌ Fallidos: $TESTS_FAILED${NC}"
echo ""

if [ $TESTS_FAILED -eq 0 ]; then
    echo -e "${GREEN}╔════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║  ✅ TODOS LOS TESTS PASARON            ║${NC}"
    echo -e "${GREEN}╔════════════════════════════════════════╗${NC}"
    echo ""
    echo -e "${GREEN}El sistema de seguridad está funcionando correctamente.${NC}"
    echo ""
    echo -e "${CYAN}Próximos pasos:${NC}"
    echo "  1. Hacer commit de los cambios"
    echo "  2. Push a master"
    echo "  3. Verificar deploy en producción"
    echo "  4. Ejecutar migraciones via GitHub Actions"
    echo ""
    exit 0
else
    echo -e "${RED}╔════════════════════════════════════════╗${NC}"
    echo -e "${RED}║  ❌ ALGUNOS TESTS FALLARON             ║${NC}"
    echo -e "${RED}╔════════════════════════════════════════╗${NC}"
    echo ""
    echo -e "${YELLOW}Revisa los errores arriba y corrígelos antes de continuar.${NC}"
    echo ""
    exit 1
fi
