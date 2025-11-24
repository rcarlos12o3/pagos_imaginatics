#!/bin/bash
# Script para configurar el worker automático de procesamiento de cola
# Sistema de Órdenes de Pago - Imaginatics Perú SAC

echo "========================================"
echo "CONFIGURACIÓN DEL WORKER AUTOMÁTICO"
echo "========================================"
echo ""

# Verificar que estamos en el directorio correcto
if [ ! -f "api/procesar_cola.php" ]; then
    echo "❌ Error: Debe ejecutar este script desde el directorio raíz del proyecto"
    exit 1
fi

PROJECT_PATH=$(pwd)
echo "📁 Ruta del proyecto: $PROJECT_PATH"
echo ""

# Opción 1: Cron Job (Recomendado para producción con Docker)
echo "=== OPCIÓN 1: CRON JOB (Recomendado) ==="
echo ""
echo "Agregue esta línea a su crontab (ejecuta cada minuto):"
echo ""
echo "* * * * * docker exec imaginatics-web php /app/api/procesar_cola.php >> /var/log/imaginatics-worker.log 2>&1"
echo ""
echo "Para editarlo ejecute: crontab -e"
echo ""
echo "Para ver los logs: tail -f /var/log/imaginatics-worker.log"
echo ""

# Opción 2: Supervisor
echo "=== OPCIÓN 2: SUPERVISOR (Alta disponibilidad) ==="
echo ""
echo "Crear archivo: /etc/supervisor/conf.d/imaginatics-worker.conf"
echo ""
cat << 'EOF'
[program:imaginatics-worker]
process_name=%(program_name)s
command=docker exec imaginatics-web php /app/api/procesar_cola.php
autostart=true
autorestart=true
user=deploy
numprocs=1
redirect_stderr=true
stdout_logfile=/var/log/imaginatics-worker.log
EOF
echo ""
echo "Luego ejecutar:"
echo "  sudo supervisorctl reread"
echo "  sudo supervisorctl update"
echo "  sudo supervisorctl start imaginatics-worker"
echo ""

# Opción 3: Systemd
echo "=== OPCIÓN 3: SYSTEMD SERVICE ==="
echo ""
echo "Crear archivo: /etc/systemd/system/imaginatics-worker.service"
echo ""
cat << EOF
[Unit]
Description=Imaginatics WhatsApp Worker
After=network.target docker.service
Requires=docker.service

[Service]
Type=simple
User=deploy
WorkingDirectory=$PROJECT_PATH
ExecStart=/usr/bin/docker exec imaginatics-web php /app/api/procesar_cola.php
Restart=always
RestartSec=60

[Install]
WantedBy=multi-user.target
EOF
echo ""
echo "Luego ejecutar:"
echo "  sudo systemctl daemon-reload"
echo "  sudo systemctl enable imaginatics-worker"
echo "  sudo systemctl start imaginatics-worker"
echo "  sudo systemctl status imaginatics-worker"
echo ""

echo "========================================"
echo "✅ Revise las opciones y configure la que prefiera"
echo "========================================"
