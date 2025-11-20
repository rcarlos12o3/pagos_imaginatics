#!/bin/bash

# ============================================
# SCRIPT DE INSTALACIÓN AUTOMÁTICA DE CRON
# Sistema de Recordatorios Automáticos
# Imaginatics Peru SAC
# ============================================

set -e  # Salir si hay algún error

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}============================================${NC}"
echo -e "${BLUE}INSTALADOR DE CRON - RECORDATORIOS AUTOMÁTICOS${NC}"
echo -e "${BLUE}Imaginatics Peru SAC${NC}"
echo -e "${BLUE}============================================${NC}"
echo ""

# Obtener ruta absoluta del proyecto (directorio padre de este script)
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PROJECT_PATH="$( cd "$SCRIPT_DIR/.." && pwd )"

echo -e "${GREEN}✓${NC} Ruta del proyecto detectada: ${PROJECT_PATH}"
echo ""

# Detectar ejecutable de PHP
echo -e "${YELLOW}➤${NC} Detectando PHP..."

if command -v php &> /dev/null; then
    PHP_BIN=$(command -v php)
    PHP_VERSION=$(php -v | head -n 1)
    echo -e "${GREEN}✓${NC} PHP encontrado: ${PHP_BIN}"
    echo -e "  Versión: ${PHP_VERSION}"
else
    echo -e "${RED}✗${NC} PHP no encontrado en PATH"
    echo -e "${YELLOW}Por favor, ingresa la ruta completa al ejecutable de PHP:${NC}"
    read -p "Ruta de PHP: " PHP_BIN

    if [ ! -f "$PHP_BIN" ]; then
        echo -e "${RED}✗${NC} Archivo no encontrado: $PHP_BIN"
        exit 1
    fi
fi
echo ""

# Crear directorio de logs si no existe
echo -e "${YELLOW}➤${NC} Configurando directorio de logs..."
LOG_DIR="${PROJECT_PATH}/logs"

if [ ! -d "$LOG_DIR" ]; then
    mkdir -p "$LOG_DIR"
    echo -e "${GREEN}✓${NC} Directorio de logs creado: ${LOG_DIR}"
else
    echo -e "${GREEN}✓${NC} Directorio de logs ya existe"
fi

# Dar permisos de escritura
chmod 755 "$LOG_DIR"
echo ""

# Configurar permisos del script
echo -e "${YELLOW}➤${NC} Configurando permisos del script..."
SCRIPT_FILE="${PROJECT_PATH}/api/enviar_recordatorios_auto.php"

if [ -f "$SCRIPT_FILE" ]; then
    chmod +x "$SCRIPT_FILE"
    echo -e "${GREEN}✓${NC} Permisos configurados en: ${SCRIPT_FILE}"
else
    echo -e "${RED}✗${NC} Script no encontrado: ${SCRIPT_FILE}"
    exit 1
fi
echo ""

# Preguntar hora de ejecución
echo -e "${YELLOW}➤${NC} Configuración de horario"
echo "¿A qué hora deseas ejecutar los recordatorios automáticos?"
read -p "Hora (0-23, por defecto 9): " HORA
HORA=${HORA:-9}

# Validar hora
if ! [[ "$HORA" =~ ^[0-9]+$ ]] || [ "$HORA" -lt 0 ] || [ "$HORA" -gt 23 ]; then
    echo -e "${RED}✗${NC} Hora inválida. Debe ser entre 0 y 23"
    exit 1
fi

echo -e "${GREEN}✓${NC} Hora configurada: ${HORA}:00"
echo ""

# Crear entrada de cron
LOG_FILE="${LOG_DIR}/recordatorios_auto.log"
CRON_COMMAND="0 ${HORA} * * * ${PHP_BIN} ${SCRIPT_FILE} >> ${LOG_FILE} 2>&1"

echo -e "${YELLOW}➤${NC} Configurando crontab..."
echo ""
echo -e "${BLUE}Se agregará la siguiente línea al crontab:${NC}"
echo -e "${GREEN}${CRON_COMMAND}${NC}"
echo ""

read -p "¿Deseas continuar? (s/n): " CONFIRM
if [ "$CONFIRM" != "s" ] && [ "$CONFIRM" != "S" ]; then
    echo -e "${YELLOW}Instalación cancelada${NC}"
    exit 0
fi

# Verificar si ya existe una entrada similar
EXISTING_CRON=$(crontab -l 2>/dev/null | grep -F "enviar_recordatorios_auto.php" || true)

if [ ! -z "$EXISTING_CRON" ]; then
    echo -e "${YELLOW}⚠${NC}  Ya existe una entrada de cron para recordatorios automáticos:"
    echo -e "${YELLOW}${EXISTING_CRON}${NC}"
    echo ""
    read -p "¿Deseas reemplazarla? (s/n): " REPLACE

    if [ "$REPLACE" = "s" ] || [ "$REPLACE" = "S" ]; then
        # Eliminar entrada existente
        crontab -l 2>/dev/null | grep -v "enviar_recordatorios_auto.php" | crontab -
        echo -e "${GREEN}✓${NC} Entrada anterior eliminada"
    else
        echo -e "${YELLOW}Conservando entrada existente. Abortando instalación.${NC}"
        exit 0
    fi
fi

# Agregar nueva entrada
(crontab -l 2>/dev/null; echo "$CRON_COMMAND") | crontab -

echo ""
echo -e "${GREEN}============================================${NC}"
echo -e "${GREEN}✓ INSTALACIÓN COMPLETADA EXITOSAMENTE${NC}"
echo -e "${GREEN}============================================${NC}"
echo ""
echo -e "${BLUE}Configuración:${NC}"
echo -e "  • Hora de ejecución: ${HORA}:00 hrs"
echo -e "  • Script: ${SCRIPT_FILE}"
echo -e "  • Log: ${LOG_FILE}"
echo ""
echo -e "${BLUE}Comandos útiles:${NC}"
echo -e "  • Ver crontab actual:     ${YELLOW}crontab -l${NC}"
echo -e "  • Editar crontab:         ${YELLOW}crontab -e${NC}"
echo -e "  • Ver logs en tiempo real:${YELLOW}tail -f ${LOG_FILE}${NC}"
echo -e "  • Probar script manual:   ${YELLOW}${PHP_BIN} ${SCRIPT_FILE}${NC}"
echo ""
echo -e "${BLUE}Próxima ejecución:${NC}"
NEXT_RUN=$(date -v+1d -v${HORA}H -v0M -v0S "+%Y-%m-%d %H:%M:%S" 2>/dev/null || date -d "tomorrow ${HORA}:00" "+%Y-%m-%d %H:%M:%S" 2>/dev/null || echo "Mañana a las ${HORA}:00")
echo -e "  ${GREEN}${NEXT_RUN}${NC}"
echo ""
echo -e "${YELLOW}TIP:${NC} Ejecuta el script manualmente una vez para verificar que funciona:"
echo -e "     ${YELLOW}${PHP_BIN} ${SCRIPT_FILE}${NC}"
echo ""
