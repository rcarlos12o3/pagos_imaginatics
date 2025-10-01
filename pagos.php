<?php 
require_once 'auth/session_check.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Pagos - Imaginatics Perú</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 20px 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo img {
            height: 50px;
        }

        .logo h1 {
            color: #2581c4;
            font-size: 24px;
        }

        .nav-buttons {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-primary {
            background: #2581c4;
            color: white;
        }

        .btn-primary:hover {
            background: #1d6ca8;
        }

        .btn-success {
            background: #4caf50;
            color: white;
        }

        .btn-success:hover {
            background: #45a049;
        }

        .btn-danger {
            background: #f44336;
            color: white;
        }

        .btn-danger:hover {
            background: #da190b;
        }

        .btn-warning {
            background: #ff9800;
            color: white;
        }

        .btn-warning:hover {
            background: #e68900;
        }

        .btn-secondary {
            background: #757575;
            color: white;
        }

        .btn-secondary:hover {
            background: #616161;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .stat-card h3 {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .stat-card .value {
            font-size: 28px;
            font-weight: bold;
            color: #2581c4;
        }

        .stat-card .subtitle {
            color: #999;
            font-size: 12px;
            margin-top: 5px;
        }

        .main-content {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .filters {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .filter-group label {
            font-weight: 600;
            color: #666;
        }

        .filter-group select,
        .filter-group input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .search-box {
            flex: 1;
            max-width: 400px;
        }

        .search-box input {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        thead {
            background: #f8f9fa;
        }

        th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #666;
            border-bottom: 2px solid #e0e0e0;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #f0f0f0;
        }

        tbody tr:hover {
            background: #f9f9f9;
        }

        .metodo-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .metodo-transferencia { background: #e3f2fd; color: #1976d2; }
        .metodo-deposito { background: #f3e5f5; color: #7b1fa2; }
        .metodo-yape { background: #fff3e0; color: #e65100; }
        .metodo-plin { background: #e8f5e9; color: #2e7d32; }
        .metodo-efectivo { background: #fce4ec; color: #c2185b; }
        .metodo-otro { background: #f5f5f5; color: #616161; }

        .actions-cell {
            display: flex;
            gap: 5px;
        }

        .btn-icon {
            padding: 5px 10px;
            font-size: 18px;
            border-radius: 5px;
            background: transparent;
            border: 1px solid;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-edit {
            color: #2196f3;
            border-color: #2196f3;
        }

        .btn-edit:hover {
            background: #2196f3;
            color: white;
        }

        .btn-delete {
            color: #f44336;
            border-color: #f44336;
        }

        .btn-delete:hover {
            background: #f44336;
            color: white;
        }

        .btn-view {
            color: #4caf50;
            border-color: #4caf50;
        }

        .btn-view:hover {
            background: #4caf50;
            color: white;
        }

        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-content {
            background: white;
            padding: 0;
            border-radius: 15px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        .modal-header {
            padding: 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 15px 15px 0 0;
        }

        .modal-header h2 {
            color: #333;
            font-size: 20px;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }

        .modal-close:hover {
            color: #333;
        }

        .modal-body {
            padding: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #666;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 5px;
            margin-top: 20px;
        }

        .pagination button {
            padding: 8px 12px;
            border: 1px solid #ddd;
            background: white;
            cursor: pointer;
            border-radius: 5px;
            transition: all 0.3s;
        }

        .pagination button:hover {
            background: #f0f0f0;
        }

        .pagination button.active {
            background: #2581c4;
            color: white;
            border-color: #2581c4;
        }

        .pagination button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        .empty-state img {
            width: 150px;
            opacity: 0.5;
            margin-bottom: 20px;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #2581c4;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            display: none;
        }

        .alert.show {
            display: block;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .detalle-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .detalle-item:last-child {
            border-bottom: none;
        }

        .detalle-label {
            font-weight: 600;
            color: #666;
            min-width: 140px;
        }

        .detalle-valor {
            color: #333;
            flex: 1;
            text-align: right;
        }

        .detalle-valor.monto {
            font-weight: bold;
            color: #2581c4;
            font-size: 18px;
        }

        .detalle-valor.metodo {
            background: #f0f0f0;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            text-transform: uppercase;
        }

        .cliente-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .cliente-info h3 {
            color: #2581c4;
            margin-bottom: 8px;
            font-size: 16px;
        }

        .cliente-info p {
            color: #666;
            margin: 4px 0;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="logo">
                <img src="logo.png" alt="Logo">
                <h1>💳 Gestión de Pagos</h1>
            </div>
            <div class="nav-buttons">
                <button class="btn btn-success" onclick="mostrarModalNuevoPago()">➕ Nuevo Pago</button>
                <button class="btn btn-warning" onclick="exportarPagos()">📊 Exportar</button>
                <a href="index.php" class="btn btn-secondary">🏠 Volver</a>
                <span class="btn" style="background: rgba(37,129,196,0.1); color: #2581c4;">👤 <?php echo $_SESSION['nombre'] ?? 'Usuario'; ?></span>
                <button class="btn" style="background: rgba(255,0,0,0.1); color: #dc3545;" onclick="logout()">🚪 Salir</button>
            </div>
        </div>

        <!-- Estadísticas -->
        <div class="stats-container" id="estadisticas">
            <div class="stat-card">
                <h3>💰 Total Recaudado</h3>
                <div class="value" id="totalRecaudado">S/ 0</div>
                <div class="subtitle">Este mes</div>
            </div>
            <div class="stat-card">
                <h3>📈 Cantidad de Pagos</h3>
                <div class="value" id="cantidadPagos">0</div>
                <div class="subtitle">Este mes</div>
            </div>
            <div class="stat-card">
                <h3>💵 Pago Promedio</h3>
                <div class="value" id="pagoPromedio">S/ 0</div>
                <div class="subtitle">Este mes</div>
            </div>
            <div class="stat-card">
                <h3>🏆 Mayor Pago</h3>
                <div class="value" id="mayorPago">S/ 0</div>
                <div class="subtitle">Este mes</div>
            </div>
        </div>

        <!-- Contenido Principal -->
        <div class="main-content">
            <div class="content-header">
                <h2>📋 Historial de Pagos</h2>
                <div class="search-box">
                    <input type="text" id="busqueda" placeholder="🔍 Buscar por cliente, RUC o número de operación..." onkeyup="buscarPagos()">
                </div>
            </div>

            <!-- Filtros -->
            <div class="filters">
                <div class="filter-group">
                    <label>Mes:</label>
                    <select id="filtroMes" onchange="filtrarPagos()">
                        <option value="">Todos</option>
                        <option value="1">Enero</option>
                        <option value="2">Febrero</option>
                        <option value="3">Marzo</option>
                        <option value="4">Abril</option>
                        <option value="5">Mayo</option>
                        <option value="6">Junio</option>
                        <option value="7">Julio</option>
                        <option value="8">Agosto</option>
                        <option value="9">Septiembre</option>
                        <option value="10">Octubre</option>
                        <option value="11">Noviembre</option>
                        <option value="12">Diciembre</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Año:</label>
                    <select id="filtroAnio" onchange="filtrarPagos()">
                        <option value="">Todos</option>
                        <option value="2025">2025</option>
                        <option value="2024">2024</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Método:</label>
                    <select id="filtroMetodo" onchange="filtrarPagos()">
                        <option value="">Todos</option>
                        <option value="transferencia">Transferencia</option>
                        <option value="deposito">Depósito</option>
                        <option value="yape">Yape</option>
                        <option value="plin">Plin</option>
                        <option value="efectivo">Efectivo</option>
                        <option value="otro">Otro</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Banco:</label>
                    <select id="filtroBanco" onchange="filtrarPagos()">
                        <option value="">Todos</option>
                        <option value="BCP">BCP</option>
                        <option value="Scotiabank">Scotiabank</option>
                        <option value="Interbank">Interbank</option>
                        <option value="BBVA">BBVA</option>
                    </select>
                </div>
            </div>

            <!-- Alertas -->
            <div id="alertContainer"></div>

            <!-- Tabla de Pagos -->
            <div id="tablaPagosContainer">
                <div class="loading">
                    <div class="spinner"></div>
                    <p>Cargando pagos...</p>
                </div>
            </div>

            <!-- Paginación -->
            <div class="pagination" id="paginacion"></div>
        </div>
    </div>

    <!-- Modal de Pago -->
    <div class="modal-overlay" id="modalPago">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitulo">Nuevo Pago</h2>
                <button class="modal-close" onclick="cerrarModal()">×</button>
            </div>
            <div class="modal-body">
                <form id="formPago">
                    <input type="hidden" id="pagoId">
                    
                    <div class="form-group">
                        <label for="clienteId">Cliente: <span style="color: red;">*</span></label>
                        <select id="clienteId" required>
                            <option value="">Seleccionar cliente...</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="montoPagado">Monto Pagado (S/): <span style="color: red;">*</span></label>
                        <input type="number" id="montoPagado" step="0.01" min="0" required>
                    </div>

                    <div class="form-group">
                        <label for="fechaPago">Fecha de Pago: <span style="color: red;">*</span></label>
                        <input type="date" id="fechaPago" required>
                    </div>

                    <div class="form-group">
                        <label for="metodoPago">Método de Pago: <span style="color: red;">*</span></label>
                        <select id="metodoPago" required>
                            <option value="">Seleccionar método</option>
                            <option value="transferencia">Transferencia Bancaria</option>
                            <option value="deposito">Depósito</option>
                            <option value="yape">Yape</option>
                            <option value="plin">Plin</option>
                            <option value="efectivo">Efectivo</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="numeroOperacion">N° Operación/Voucher:</label>
                        <input type="text" id="numeroOperacion">
                    </div>

                    <div class="form-group">
                        <label for="banco">Banco:</label>
                        <select id="banco">
                            <option value="">Seleccionar banco (opcional)</option>
                            <option value="Scotiabank">Scotiabank</option>
                            <option value="BCP">BCP</option>
                            <option value="Interbank">Interbank</option>
                            <option value="BBVA">BBVA</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="observaciones">Observaciones:</label>
                        <textarea id="observaciones"></textarea>
                    </div>

                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="actualizarVencimiento" checked>
                            Actualizar fecha de vencimiento del cliente
                        </label>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-success">💾 Guardar</button>
                        <button type="button" class="btn btn-secondary" onclick="cerrarModal()">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de Detalles del Pago -->
    <div class="modal-overlay" id="modalDetalles">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalDetallesTitulo">Detalles del Pago</h2>
                <button class="modal-close" onclick="cerrarModalDetalles()">×</button>
            </div>
            <div class="modal-body">
                <div id="detallesPagoContent">
                    <!-- Contenido se carga dinámicamente -->
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="cerrarModalDetalles()">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Exportar -->
    <div class="modal-overlay" id="modalExportar">
        <div class="modal-content" style="max-width: 450px;">
            <div class="modal-header">
                <h2>📊 Exportar Pagos a CSV</h2>
                <button class="modal-close" onclick="cerrarModalExportar()">×</button>
            </div>
            <div class="modal-body">
                <p style="color: #666; margin-bottom: 20px;">Seleccione el período que desea exportar:</p>

                <div class="form-group">
                    <label for="exportarTipoFecha">Filtrar por:</label>
                    <select id="exportarTipoFecha">
                        <option value="pago">Fecha de Pago (cuando se registró)</option>
                        <option value="vencimiento">Fecha de Vencimiento (del cliente)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="exportarMes">Mes:</label>
                    <select id="exportarMes">
                        <option value="">Todos los meses</option>
                        <option value="1">Enero</option>
                        <option value="2">Febrero</option>
                        <option value="3">Marzo</option>
                        <option value="4">Abril</option>
                        <option value="5">Mayo</option>
                        <option value="6">Junio</option>
                        <option value="7">Julio</option>
                        <option value="8">Agosto</option>
                        <option value="9">Septiembre</option>
                        <option value="10">Octubre</option>
                        <option value="11">Noviembre</option>
                        <option value="12">Diciembre</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="exportarAnio">Año:</label>
                    <select id="exportarAnio">
                        <option value="">Todos los años</option>
                        <option value="2025">2025</option>
                        <option value="2024">2024</option>
                        <option value="2023">2023</option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-success" onclick="confirmarExportacion()">📥 Exportar</button>
                    <button type="button" class="btn btn-secondary" onclick="cerrarModalExportar()">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="js/config.js"></script>
    <script src="js/pagos.js"></script>
</body>
</html>