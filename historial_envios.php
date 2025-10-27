<?php
header('Content-Type: text/html; charset=UTF-8');
require_once 'auth/session_check.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Envíos - Imaginatics</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #2581c4 0%, #1a6399 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(45deg, #2581c4, #1a6399);
            color: white;
            padding: 25px;
            text-align: center;
            position: relative;
        }
        .header h1 { font-size: 28px; margin-bottom: 8px; }
        .header p { font-size: 16px; opacity: 0.9; }
        .nav-buttons {
            position: absolute;
            top: 20px;
            right: 20px;
            display: flex;
            gap: 10px;
        }
        .nav-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 8px 15px;
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 20px;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
        }
        .nav-btn:hover { background: rgba(255,255,255,0.3); }
        .content {
            padding: 30px;
        }
        .filters {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: end;
        }
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        .filter-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #495057;
        }
        .filter-group input, .filter-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 5px;
            font-size: 14px;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
        }
        .btn-primary {
            background: linear-gradient(45deg, #2581c4, #1a6399);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(37, 129, 196, 0.4);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        th {
            background: #2581c4;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }
        td {
            padding: 12px 15px;
            border-bottom: 1px solid #f8f9fa;
        }
        tr:hover {
            background: #f8f9fa;
        }
        .badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        .badge-orden { background: #e3f2fd; color: #1976d2; }
        .badge-recordatorio { background: #fff3e0; color: #f57c00; }
        .badge-vencido { background: #ffebee; color: #c62828; }
        .badge-enviado { background: #e8f5e9; color: #2e7d32; }
        .badge-error { background: #ffebee; color: #c62828; }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-card h3 {
            font-size: 32px;
            color: #2581c4;
            margin-bottom: 5px;
        }
        .stat-card p {
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="nav-buttons">
                <a href="index.php" class="nav-btn">← Volver</a>
                <a href="#" onclick="logout()" class="nav-btn" style="background: rgba(255,0,0,0.2);">🚪 Salir</a>
            </div>
            <h1>📋 Historial de Envíos WhatsApp</h1>
            <p>Consulta todas las notificaciones enviadas a tus clientes</p>
        </div>

        <div class="content">
            <div class="stats" id="statsContainer"></div>

            <div class="filters">
                <div class="filter-group">
                    <label>Buscar Cliente:</label>
                    <input type="text" id="searchCliente" placeholder="RUC o Razón Social...">
                </div>
                <div class="filter-group">
                    <label>Tipo de Envío:</label>
                    <select id="filterTipo">
                        <option value="">Todos</option>
                        <option value="orden_pago">Orden de Pago</option>
                        <option value="recordatorio_proximo">Recordatorio Próximo</option>
                        <option value="recordatorio_vencido">Recordatorio Vencido</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Estado:</label>
                    <select id="filterEstado">
                        <option value="">Todos</option>
                        <option value="enviado">Enviado</option>
                        <option value="error">Error</option>
                    </select>
                </div>
                <div class="filter-group">
                    <button class="btn btn-primary" onclick="cargarHistorial()">🔍 Filtrar</button>
                </div>
            </div>

            <div style="overflow-x: auto;">
                <table id="historialTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Cliente</th>
                            <th>RUC</th>
                            <th>Tipo de Envío</th>
                            <th>Estado</th>
                            <th>Fecha de Envío</th>
                            <th>WhatsApp</th>
                        </tr>
                    </thead>
                    <tbody id="historialBody">
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px;">
                                Cargando historial...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        async function cargarHistorial() {
            const searchCliente = document.getElementById('searchCliente').value;
            const filterTipo = document.getElementById('filterTipo').value;
            const filterEstado = document.getElementById('filterEstado').value;

            try {
                const response = await fetch('/api/envios.php?action=list&limit=100');
                const data = await response.json();

                if (data.success) {
                    let envios = data.data;

                    // Aplicar filtros
                    if (searchCliente) {
                        const search = searchCliente.toLowerCase();
                        envios = envios.filter(e =>
                            e.razon_social.toLowerCase().includes(search) ||
                            e.ruc.includes(search)
                        );
                    }
                    if (filterTipo) {
                        envios = envios.filter(e => e.tipo_envio === filterTipo);
                    }
                    if (filterEstado) {
                        envios = envios.filter(e => e.estado === filterEstado);
                    }

                    mostrarHistorial(envios);
                    mostrarEstadisticas(data.data);
                }
            } catch (error) {
                console.error('Error:', error);
                document.getElementById('historialBody').innerHTML = `
                    <tr><td colspan="7" style="text-align: center; color: red;">
                        Error al cargar el historial
                    </td></tr>
                `;
            }
        }

        function mostrarHistorial(envios) {
            const tbody = document.getElementById('historialBody');

            if (envios.length === 0) {
                tbody.innerHTML = `
                    <tr><td colspan="7" style="text-align: center; padding: 40px; color: #666;">
                        No se encontraron envíos
                    </td></tr>
                `;
                return;
            }

            tbody.innerHTML = envios.map(e => `
                <tr>
                    <td>${e.id}</td>
                    <td>${e.razon_social}</td>
                    <td>${e.ruc}</td>
                    <td>
                        <span class="badge ${getBadgeClass(e.tipo_envio)}">
                            ${formatTipo(e.tipo_envio)}
                        </span>
                    </td>
                    <td>
                        <span class="badge ${e.estado === 'enviado' ? 'badge-enviado' : 'badge-error'}">
                            ${e.estado === 'enviado' ? '✓ Enviado' : '✗ Error'}
                        </span>
                    </td>
                    <td>${formatFecha(e.fecha_envio)}</td>
                    <td>${e.whatsapp}</td>
                </tr>
            `).join('');
        }

        function mostrarEstadisticas(envios) {
            const totalEnvios = envios.length;
            const enviados = envios.filter(e => e.estado === 'enviado').length;
            const errores = envios.filter(e => e.estado === 'error').length;
            const ordenes = envios.filter(e => e.tipo_envio === 'orden_pago').length;

            document.getElementById('statsContainer').innerHTML = `
                <div class="stat-card">
                    <h3>${totalEnvios}</h3>
                    <p>Total de Envíos</p>
                </div>
                <div class="stat-card">
                    <h3>${enviados}</h3>
                    <p>Enviados Exitosos</p>
                </div>
                <div class="stat-card">
                    <h3>${errores}</h3>
                    <p>Errores</p>
                </div>
                <div class="stat-card">
                    <h3>${ordenes}</h3>
                    <p>Órdenes de Pago</p>
                </div>
            `;
        }

        function getBadgeClass(tipo) {
            switch(tipo) {
                case 'orden_pago': return 'badge-orden';
                case 'recordatorio_proximo': return 'badge-recordatorio';
                case 'recordatorio_vencido': return 'badge-vencido';
                default: return 'badge-orden';
            }
        }

        function formatTipo(tipo) {
            switch(tipo) {
                case 'orden_pago': return 'Orden de Pago';
                case 'recordatorio_proximo': return 'Recordatorio Próximo';
                case 'recordatorio_vencido': return 'Recordatorio Vencido';
                default: return tipo;
            }
        }

        function formatFecha(fecha) {
            const d = new Date(fecha);
            return d.toLocaleString('es-PE', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        async function logout() {
            if (confirm('¿Estás seguro de que deseas cerrar sesión?')) {
                try {
                    await fetch('/api/auth.php?action=logout', { method: 'POST' });
                    window.location.href = 'login.html';
                } catch (error) {
                    window.location.href = 'login.html';
                }
            }
        }

        // Cargar historial al iniciar
        document.addEventListener('DOMContentLoaded', () => {
            cargarHistorial();
        });

        // Enter para buscar
        document.getElementById('searchCliente').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') cargarHistorial();
        });
    </script>
</body>
</html>
