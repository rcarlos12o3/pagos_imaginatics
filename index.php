<?php
header('Content-Type: text/html; charset=UTF-8');
require_once 'auth/session_check.php';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generador de Ordenes de Pago</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', 'Museo Sans Rounded', Tahoma, Geneva, Verdana, sans-serif;
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
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: #f39325;
            opacity: 0.1;
            border-radius: 50%;
            transform: translate(30px, -30px);
        }

        .header {
            position: relative;
        }

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
            font-weight: 500;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .nav-btn:hover {
            background: rgba(255,255,255,0.3);
            color: white;
            transform: translateY(-1px);
        }

        .header h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
            color: white;
        }

        .header p {
            font-size: 16px;
            opacity: 0.9;
            font-weight: 300;
        }

        .main-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            padding: 20px;
            min-height: 800px;
        }

        .left-panel,
        .right-panel {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            border: 1px solid #e9ecef;
        }

        .section-title {
            font-size: 16px;
            font-weight: 700;
            color: #2581c4;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #2581c4;
            position: relative;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 30px;
            height: 2px;
            background: #f39325;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #495057;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #2581c4;
            box-shadow: 0 0 8px rgba(37, 129, 196, 0.3);
        }

        .input-group {
            display: flex;
            gap: 10px;
            align-items: end;
        }

        .input-group input {
            flex: 1;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background: linear-gradient(45deg, #2581c4, #1a6399);
            color: white;
            border: 1px solid #2581c4;
        }

        .btn-primary:hover {
            background: linear-gradient(45deg, #1a6399, #2581c4);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(37, 129, 196, 0.4);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
            border: 1px solid #6c757d;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-1px);
        }

        .btn-success {
            background: linear-gradient(45deg, #f39325, #e8841c);
            color: white;
            border: 1px solid #f39325;
        }

        .btn-success:hover {
            background: linear-gradient(45deg, #e8841c, #f39325);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(243, 147, 37, 0.4);
        }

        .btn-danger {
            background: #dc3545;
            color: white;
            border: 1px solid #dc3545;
        }

        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-1px);
        }

        .btn-warning {
            background: linear-gradient(45deg, #f39325, #ffb347);
            color: white;
            border: 1px solid #f39325;
        }

        .btn-warning:hover {
            background: linear-gradient(45deg, #e8841c, #f39325);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(243, 147, 37, 0.3);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .btn-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .status-info {
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .status-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .status-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .status-info-text {
            background: #e3f2fd;
            color: #2581c4;
            border: 1px solid #2581c4;
            border-left: 4px solid #f39325;
        }

        .client-list {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid #e9ecef;
        }

        .client-list-header {
            background: #f8f9fa;
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
            font-weight: bold;
        }

        .client-list-content {
            max-height: 400px;
            overflow-y: auto;
        }

        .client-item {
            padding: 15px;
            border-bottom: 1px solid #f8f9fa;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }

        .client-item:hover {
            background: #f8f9fa;
        }

        .client-item.vencido {
            border-left: 5px solid #F28B82;
        }

        .client-item.proximo-vencer {
            border-left: 5px solid #FFF176;
        }

        .client-item.al-dia {
            border-left: 5px solid #81C784;
        }

        .client-item.selected {
            background: #e3f2fd;
            border-left: 5px solid #2581c4;
            box-shadow: 0 2px 8px rgba(37, 129, 196, 0.1);
        }

        .client-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            font-size: 14px;
        }

        .client-name {
            font-weight: bold;
            color: #495057;
            grid-column: span 2;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
        }

        .client-id {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #e9ecef;
            color: #6c757d;
            font-size: 12px;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 4px;
            margin-right: 8px;
            min-width: 40px;
            font-family: 'Courier New', monospace;
        }

        .preview-area {
            background: white;
            border-radius: 10px;
            padding: 20px;
            border: 1px solid #e9ecef;
            min-height: 300px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .preview-canvas {
            width: 100%;
            max-width: 400px;
            height: 240px;
            border: 2px dashed #ced4da;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            font-style: italic;
            background: #f8f9fa;
        }

        .progress-bar {
            width: 100%;
            height: 10px;
            background: #e9ecef;
            border-radius: 5px;
            overflow: hidden;
            margin: 10px 0;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(45deg, #2581c4, #f39325);
            width: 0%;
            transition: width 0.3s;
            border-radius: 5px;
        }

        .notification-area {
            background: white;
            border-radius: 8px;
            padding: 15px;
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #e9ecef;
            font-family: monospace;
            font-size: 12px;
            line-height: 1.4;
        }

        .emoji {
            font-style: normal;
            font-size: 16px;
        }

        .text-success {
            color: #28a745;
        }

        .text-danger {
            color: #dc3545;
        }

        .text-warning {
            color: #ffc107;
        }

        .text-muted {
            color: #6c757d;
        }

        .hidden {
            display: none;
        }

        .csv-info {
            background: #f8fbff;
            border: 1px solid #e3f2fd;
            border-left: 3px solid #f39325;
            padding: 12px;
            border-radius: 5px;
            font-size: 12px;
            color: #2581c4;
            margin-bottom: 10px;
        }

        @media (max-width: 768px) {
            .main-content {
                grid-template-columns: 1fr;
            }

            .btn-group {
                flex-direction: column;
            }

            .input-group {
                flex-direction: column;
                align-items: stretch;
            }
        }

        /* Estilos para Modal de Detalle Cliente */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-content {
            background: white;
            border-radius: 15px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }

        .modal-header {
            background: linear-gradient(45deg, #2581c4, #1a6399);
            color: white;
            padding: 20px;
            border-radius: 15px 15px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 1.5em;
        }

        .modal-close {
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background-color 0.3s;
        }

        .modal-close:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .modal-body {
            padding: 20px;
        }

        .cliente-datos h3 {
            color: #2581c4;
            margin-bottom: 15px;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 5px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 10px;
            margin-bottom: 20px;
        }

        .info-grid div {
            padding: 8px 0;
            border-bottom: 1px solid #f8f9fa;
        }

        .estado {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .estado.vencido { background: #ff6b6b; color: white; }
        .estado.vence_hoy { background: #ffa726; color: white; }
        .estado.por_vencer { background: #ffeb3b; color: #333; }
        .estado.al_dia { background: #4caf50; color: white; }
        .estado.pagado { background: #2196f3; color: white; }

        .historial-envios, .historial-pagos {
            margin: 20px 0;
        }

        .tabla-envios, .tabla-pagos {
            border: 1px solid #e9ecef;
            border-radius: 8px;
            overflow: hidden;
        }

        .envio-item, .pago-item {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 10px;
            padding: 12px;
            border-bottom: 1px solid #f8f9fa;
            align-items: center;
        }

        .envio-item:last-child, .pago-item:last-child {
            border-bottom: none;
        }

        .envio-item .estado {
            justify-self: end;
        }

        .no-data {
            text-align: center;
            color: #6c757d;
            padding: 20px;
            font-style: italic;
        }

        .acciones-cliente {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #e9ecef;
        }

        @media (max-width: 600px) {
            .modal-content {
                width: 95%;
                margin: 10px;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .acciones-cliente {
                flex-direction: column;
            }
        }

        /* Estilos adicionales para formulario de pagos */
        .cliente-info-pago {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #2581c4;
        }

        .cliente-info-pago h3 {
            margin: 0 0 10px 0;
            color: #2581c4;
        }

        .cliente-info-pago p {
            margin: 5px 0;
            color: #666;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            box-sizing: border-box;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #2581c4;
            box-shadow: 0 0 0 2px rgba(37, 129, 196, 0.1);
        }

        .nueva-fecha-info {
            background: #e8f5e8;
            padding: 10px;
            border-radius: 5px;
            margin: 15px 0;
            border-left: 4px solid #4caf50;
        }

        .nueva-fecha-info p {
            margin: 0;
            color: #2e7d32;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .form-actions .btn {
            min-width: 120px;
        }
    </style>
    <link rel="stylesheet" href="css/servicios.css">
    <link rel="stylesheet" href="css/apple-design.css">
    <link rel="stylesheet" href="css/apple-servicios.css">
    <link rel="stylesheet" href="css/apple-client-list.css">
    <link rel="stylesheet" href="css/apple-sidebar.css">
</head>

<body>
    <!-- App Container con Sidebar -->
    <div class="app-container">
        <!-- SIDEBAR -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="img/logo-imaginatics.png" alt="Logo" class="sidebar-logo" onerror="this.style.display='none'">
                <div class="sidebar-brand">
                    <h2>Imaginatics</h2>
                    <p>Pagos & WhatsApp</p>
                </div>
            </div>

            <nav class="sidebar-nav">
                <!-- Sección Principal -->
                <div class="nav-section">
                    <div class="nav-section-title">Principal</div>
                    <a href="#" class="nav-item active" onclick="navigateTo('principal'); return false;">
                        <span class="nav-item-icon">🏠</span>
                        <span class="nav-item-text">Dashboard</span>
                    </a>
                </div>

                <!-- Sección Resources -->
                <div class="nav-section">
                    <div class="nav-section-title">Resources</div>
                    <a href="#" class="nav-item" onclick="navigateTo('usuarios'); return false;">
                        <span class="nav-item-icon">👥</span>
                        <span class="nav-item-text">Usuarios</span>
                    </a>
                    <a href="#" class="nav-item" onclick="navigateTo('notificaciones'); return false;">
                        <span class="nav-item-icon">🔔</span>
                        <span class="nav-item-text">Notificaciones</span>
                        <span class="nav-item-badge">3</span>
                    </a>
                    <a href="#" class="nav-item" onclick="navigateTo('envios'); return false;">
                        <span class="nav-item-icon">📤</span>
                        <span class="nav-item-text">Envíos</span>
                    </a>
                </div>

                <!-- Otras opciones -->
                <div class="nav-section">
                    <div class="nav-section-title">Herramientas</div>
                    <a href="#" class="nav-item" onclick="navigateTo('carga-csv'); return false;">
                        <span class="nav-item-icon">📄</span>
                        <span class="nav-item-text">Carga Masiva CSV</span>
                    </a>
                    <a href="historial_envios.php" class="nav-item">
                        <span class="nav-item-icon">📋</span>
                        <span class="nav-item-text">Historial</span>
                    </a>
                    <a href="pagos.php" class="nav-item">
                        <span class="nav-item-icon">💳</span>
                        <span class="nav-item-text">Gestión Pagos</span>
                    </a>
                    <a href="#" class="nav-item" onclick="DashboardPagos.abrir(); return false;">
                        <span class="nav-item-icon">📊</span>
                        <span class="nav-item-text">Dashboard Pagos</span>
                    </a>
                </div>
            </nav>

            <div class="sidebar-footer">
                <div class="sidebar-footer-item" onclick="navigateTo('configuracion');" style="cursor: pointer;">
                    <span>⚙️</span>
                    <span>Configuración</span>
                </div>
            </div>
        </aside>

        <!-- MAIN WRAPPER -->
        <div class="main-wrapper">
            <!-- TOPBAR -->
            <header class="topbar">
                <div class="topbar-left">
                    <img src="img/logo-imaginatics.png" alt="Logo" class="topbar-logo" onerror="this.style.display='none'">
                    <h1 class="topbar-title">Generador de Ordenes de Pago</h1>
                </div>
                <div class="topbar-right">
                    <div class="user-menu" id="userMenu">
                        <div class="user-menu-btn" onclick="toggleUserMenu()">
                            <div class="user-avatar">👤</div>
                            <span class="user-name"><?php echo $_SESSION['nombre'] ?? 'Usuario'; ?></span>
                            <svg class="user-chevron" width="10" height="6" viewBox="0 0 10 6" fill="none">
                                <path d="M1 1L5 5L9 1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                            </svg>
                        </div>
                        <div class="user-dropdown">
                            <a href="#" class="user-dropdown-item">
                                <span>👤</span>
                                <span>Mi Perfil</span>
                            </a>
                            <a href="#" class="user-dropdown-item">
                                <span>⚙️</span>
                                <span>Configuración</span>
                            </a>
                            <div class="user-dropdown-divider"></div>
                            <a href="#" onclick="logout(); return false;" class="user-dropdown-item danger">
                                <span>🚪</span>
                                <span>Cerrar Sesión</span>
                            </a>
                        </div>
                    </div>
                </div>
            </header>

            <!-- MAIN CONTENT -->
            <main class="main-content-wrapper">
                <!-- PÁGINA: PRINCIPAL (Dashboard) -->
                <div id="page-principal" class="page-view active">
                    <div class="page-content">
                        <div class="main-content">
            <!-- Panel Izquierdo -->
            <div class="left-panel">
                <!-- Panel izquierdo vacío - funcionalidad movida al sidebar -->
            </div>

            <!-- Panel Derecho -->
            <div class="right-panel">
                <!-- Lista de Clientes -->
                <div class="client-list">
                    <div class="client-list-header">
                        Lista de Clientes (<span id="clientCount">0</span>)
                    </div>
                    <!-- Filtro de búsqueda -->
                    <div class="client-filter">
                        <input type="text" id="searchFilter" placeholder="🔍 Buscar por ID, RUC, razón social o WhatsApp..."
                               style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px; margin-bottom: 10px; font-size: 14px;">
                    </div>
                    <div class="client-list-content" id="clientList">
                        <div style="padding: 40px; text-align: center; color: #6c757d;">
                            <span class="emoji">📝</span><br>
                            No hay clientes agregados.<br>
                            Agregue clientes usando el formulario o cargue un archivo CSV.
                        </div>
                    </div>
                    <div class="client-bulk-actions">
                        <button class="btn btn-warning" id="btnEditarSeleccionado" onclick="editarClienteSeleccionado()"
                            disabled>
                            ✏️ Editar
                        </button>
                        <button class="btn btn-danger" id="btnEliminarCliente" onclick="eliminarClienteSeleccionado()"
                            disabled>🗑️ Eliminar</button>
                    </div>
                </div>

                <!-- Vista Previa -->
                <div class="section">
                    <div class="section-header" onclick="toggleSectionContent('section-preview-content')">
                        <div class="section-title">
                            <span class="section-icon">🖼️</span>
                            <span>Vista Previa</span>
                        </div>
                        <button class="collapse-btn" type="button">
                            <svg class="chevron" width="14" height="14" viewBox="0 0 14 14" fill="none">
                                <path d="M3 5L7 9L11 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                    </div>
                    <div class="section-content" id="section-preview-content">
                        <div class="preview-area">
                            <div class="preview-canvas" id="previewCanvas">
                                <span class="emoji">🖼️</span><br>
                                Seleccione un cliente para ver la vista previa
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
                    </div>
                </div>
                <!-- FIN PÁGINA: PRINCIPAL -->

                <!-- PÁGINA: USUARIOS -->
                <div id="page-usuarios" class="page-view">
                    <div class="page-content">
                        <div style="background: var(--bg-glass); backdrop-filter: var(--blur-medium); border-radius: var(--radius-xl); padding: var(--spacing-xl);">
                            <!-- Header con título y botón -->
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-xl);">
                                <div>
                                    <h2 style="margin: 0 0 var(--spacing-xs) 0; color: var(--text-primary);">
                                        <span style="font-size: 24px;">👥</span> Gestión de Usuarios
                                    </h2>
                                    <p style="color: var(--text-secondary); margin: 0;">
                                        Administra todos los clientes del sistema
                                    </p>
                                </div>
                                <button class="btn btn-primary" onclick="crearNuevoUsuario()" style="display: flex; align-items: center; gap: 8px;">
                                    <span>➕</span>
                                    <span>Crear Usuario</span>
                                </button>
                            </div>

                            <!-- Tabla de Usuarios -->
                            <div class="usuarios-table-container">
                                <table class="usuarios-table" id="tablaUsuarios">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>RUC</th>
                                            <th>Razón Social</th>
                                            <th>WhatsApp</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody id="usuariosTableBody">
                                        <tr>
                                            <td colspan="6" style="text-align: center; padding: var(--spacing-xl); color: var(--text-tertiary);">
                                                <div class="loading-spinner" style="margin: 0 auto;"></div>
                                                <p style="margin-top: var(--spacing-md);">Cargando usuarios...</p>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- FIN PÁGINA: USUARIOS -->

                <!-- PÁGINA: NOTIFICACIONES -->
                <div id="page-notificaciones" class="page-view">
                    <div class="page-content">
                        <div class="main-content">
                            <!-- Notificaciones de Vencimiento -->
                            <div class="section">
                                <div class="section-header" onclick="toggleSectionContent('section-notif-content')">
                                    <div class="section-title">
                                        <span class="section-icon">🔔</span>
                                        <span>Notificaciones de Vencimiento</span>
                                    </div>
                                    <button class="collapse-btn" type="button">
                                        <svg class="chevron" width="14" height="14" viewBox="0 0 14 14" fill="none">
                                            <path d="M3 5L7 9L11 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </button>
                                </div>
                                <div class="section-content" id="section-notif-content">

                                    <div class="form-group">
                                        <label for="diasAnticipacionNotif">Días de anticipación:</label>
                                        <div class="input-group">
                                            <input type="number" id="diasAnticipacionNotif" value="3" min="1" max="30" style="flex: none; width: 80px;">
                                            <span style="padding: 10px 0;">días antes del vencimiento</span>
                                        </div>
                                    </div>

                                    <div class="btn-group">
                                        <button class="btn btn-warning" onclick="verificarVencimientosNotif()">
                                            Verificar Vencimientos
                                        </button>
                                        <button class="btn btn-danger" id="btnEnviarRecordatoriosNotif" onclick="enviarRecordatoriosNotif()" disabled>
                                            Enviar Recordatorios
                                        </button>
                                    </div>

                                    <div class="notification-area" id="notificationAreaNotif">
                                        Presione "Verificar Vencimientos" para analizar el estado de las cuentas
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- FIN PÁGINA: NOTIFICACIONES -->

                <!-- PÁGINA: ENVÍOS -->
                <div id="page-envios" class="page-view">
                    <div class="page-content">
                        <div class="main-content">

                            <!-- Panel de Análisis Inteligente -->
                            <div class="section">
                                <div class="section-header">
                                    <div class="section-title">
                                        <span class="section-icon">🤖</span>
                                        <span>Análisis Inteligente de Envíos</span>
                                    </div>
                                    <button class="btn btn-sm" onclick="ModuloEnvios.analizarEnvios()">
                                        🔄 Actualizar Análisis
                                    </button>
                                </div>
                                <div class="section-content">

                                    <!-- Resumen de análisis -->
                                    <div id="resumen-analisis" style="display: none; margin-bottom: 20px; padding: 15px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 10px;">
                                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                                            <div style="text-align: center;">
                                                <div style="font-size: 32px; font-weight: bold;" id="total-deben-enviarse">0</div>
                                                <div style="opacity: 0.9;">Deben recibir orden</div>
                                            </div>
                                            <div style="text-align: center;">
                                                <div style="font-size: 32px; font-weight: bold;" id="total-pendientes">0</div>
                                                <div style="opacity: 0.9;">Pendientes (fecha futura)</div>
                                            </div>
                                            <div style="text-align: center;">
                                                <div style="font-size: 32px; font-weight: bold;" id="total-enviados">0</div>
                                                <div style="opacity: 0.9;">Ya enviados este periodo</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Loader -->
                                    <div id="loader-analisis" style="text-align: center; padding: 40px;">
                                        <div style="font-size: 48px;">⏳</div>
                                        <p style="color: #666; margin-top: 10px;">Analizando servicios...</p>
                                    </div>

                                    <!-- Lista de empresas que DEBEN recibir orden -->
                                    <div id="lista-deben-enviarse" style="display: none;">
                                        <h3 style="margin-bottom: 15px; color: #2c3e50;">
                                            ✅ Empresas que deben recibir órdenes de pago
                                        </h3>
                                        <div id="empresas-deben-enviarse"></div>

                                        <!-- Botones de acción -->
                                        <div style="margin-top: 20px; display: flex; gap: 10px; justify-content: center;">
                                            <button class="btn btn-primary btn-lg" onclick="ModuloEnvios.enviarSeleccionadas()" disabled id="btn-enviar-seleccionadas">
                                                📤 Enviar Órdenes Seleccionadas
                                            </button>
                                            <button class="btn btn-success btn-lg" onclick="ModuloEnvios.seleccionarTodas()">
                                                ☑️ Seleccionar Todas
                                            </button>
                                            <button class="btn btn-secondary" onclick="ModuloEnvios.deseleccionarTodas()">
                                                ⬜ Deseleccionar Todas
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Mensaje cuando no hay envíos pendientes -->
                                    <div id="sin-envios-pendientes" style="display: none; text-align: center; padding: 40px;">
                                        <div style="font-size: 64px;">✨</div>
                                        <h3 style="color: #27ae60; margin-top: 10px;">¡Todo al día!</h3>
                                        <p style="color: #666;">No hay empresas que requieran órdenes de pago en este momento.</p>
                                    </div>

                                    <!-- Barra de progreso -->
                                    <div id="progress-envio" style="display: none; margin-top: 30px;">
                                        <div class="progress-bar">
                                            <div class="progress-fill" id="progressBar"></div>
                                        </div>
                                        <div class="text-muted" id="progressText" style="text-align: center; margin-top: 10px;"></div>
                                    </div>

                                </div>
                            </div>

                            <!-- Sección expandible: Detalles técnicos -->
                            <div class="section" style="margin-top: 20px;">
                                <div class="section-header" onclick="toggleSectionContent('section-detalles-tecnicos')">
                                    <div class="section-title">
                                        <span class="section-icon">📊</span>
                                        <span>Detalles Técnicos y Explicación</span>
                                    </div>
                                    <button class="collapse-btn" type="button">
                                        <svg class="chevron" width="14" height="14" viewBox="0 0 14 14" fill="none">
                                            <path d="M3 5L7 9L11 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </button>
                                </div>
                                <div class="section-content collapsed" id="section-detalles-tecnicos">
                                    <div id="detalles-reglas" style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
                                        <h4>📋 Reglas de Envío</h4>
                                        <ul style="line-height: 1.8;">
                                            <li><strong>Mensual:</strong> Se envía 4 días antes del vencimiento (último día del mes)</li>
                                            <li><strong>Trimestral:</strong> Se envía 7 días antes del vencimiento</li>
                                            <li><strong>Semestral:</strong> Se envía 15 días antes del vencimiento</li>
                                            <li><strong>Anual:</strong> Se envía 30 días antes del vencimiento</li>
                                        </ul>
                                        <p style="margin-top: 15px; color: #e74c3c; font-weight: bold;">
                                            ⚠️ Solo se enviarán órdenes a las empresas listadas arriba. Esto evita envíos masivos erróneos.
                                        </p>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
                <!-- FIN PÁGINA: ENVÍOS -->

                <!-- PÁGINA: CARGA MASIVA CSV -->
                <div id="page-carga-csv" class="page-view">
                    <div class="page-content">
                        <div style="background: var(--bg-glass); backdrop-filter: var(--blur-medium); border-radius: var(--radius-xl); padding: var(--spacing-xl);">
                            <div style="margin-bottom: var(--spacing-xl);">
                                <h2 style="margin: 0 0 var(--spacing-xs) 0; color: var(--text-primary);">
                                    <span style="font-size: 24px;">📄</span> Carga Masiva desde CSV
                                </h2>
                                <p style="color: var(--text-secondary); margin: 0;">
                                    Importa múltiples clientes desde un archivo CSV
                                </p>
                            </div>

                            <div class="csv-info">
                                <strong>Formato CSV:</strong> RUC|RAZON_SOCIAL|MONTO|VENCIMIENTO|NUMERO<br>
                                <strong>Ejemplo:</strong> 20123456789|EMPRESA SAC|1500.00|15/12/2025|987654321
                            </div>

                            <div class="btn-group">
                                <button class="btn btn-primary" onclick="document.getElementById('csvFile').click()">Cargar CSV</button>
                                <button class="btn btn-warning" onclick="descargarPlantilla()">Descargar Plantilla</button>
                            </div>
                            <input type="file" id="csvFile" accept=".csv" style="display: none;" onchange="cargarCSV(event)">
                        </div>
                    </div>
                </div>
                <!-- FIN PÁGINA: CARGA MASIVA CSV -->

                <!-- PÁGINA: CONFIGURACIÓN -->
                <div id="page-configuracion" class="page-view">
                    <div class="page-content">
                        <div style="background: var(--bg-glass); backdrop-filter: var(--blur-medium); border-radius: var(--radius-xl); padding: var(--spacing-xl);">
                            <div style="margin-bottom: var(--spacing-xl);">
                                <h2 style="margin: 0 0 var(--spacing-xs) 0; color: var(--text-primary);">
                                    <span style="font-size: 24px;">⚙️</span> Configuración
                                </h2>
                                <p style="color: var(--text-secondary); margin: 0;">
                                    Administra la configuración del sistema
                                </p>
                            </div>

                            <!-- Sección: Estado de Imágenes -->
                            <div style="margin-bottom: var(--spacing-xl);">
                                <h3 style="margin: 0 0 var(--spacing-md) 0; color: var(--text-primary); font-size: 18px; font-weight: 600;">
                                    🖼️ Estado de Imágenes
                                </h3>

                                <div id="logoStatusConfig" class="status-info">
                                    <span class="emoji">🔍</span> Logo: Verificando...
                                </div>
                                <div id="mascotaStatusConfig" class="status-info">
                                    <span class="emoji">🔍</span> Mascota: Verificando...
                                </div>
                                <div class="text-muted" style="font-size: 12px; margin-top: 10px;">
                                    Coloque logo.png y mascota.png en la misma carpeta del proyecto
                                </div>
                            </div>

                            <!-- Aquí se pueden agregar más secciones de configuración en el futuro -->
                        </div>
                    </div>
                </div>
                <!-- FIN PÁGINA: CONFIGURACIÓN -->

            </main>
        </div>
    </div>

    <!-- Modal para Crear/Editar Usuario -->
    <div id="modalUsuario" class="modal-overlay" style="display: none;" onclick="cerrarModalUsuario(event)">
        <div class="modal-content" onclick="event.stopPropagation()" style="max-width: 600px;">
            <div class="modal-header">
                <h2 id="modalUsuarioTitulo">📝 Datos del Cliente</h2>
                <button class="modal-close" onclick="cerrarModalUsuario()">&times;</button>
            </div>
            <div class="modal-body" id="modalUsuarioBody">
                <!-- COPIA EXACTA de la Sección 1: Datos del Cliente con IDs únicos -->
                <div class="form-group">
                    <label for="modal_ruc">RUC:</label>
                    <div class="input-group">
                        <input type="text" id="modal_ruc" placeholder="Ingrese RUC de 11 digitos" maxlength="11">
                        <button class="btn btn-primary" onclick="consultarRUCModal()">Consultar</button>
                    </div>
                </div>

                <div id="modal_razonSocialDisplay" class="status-info-text hidden">
                    <strong>Razon Social:</strong> <span id="modal_razonSocialText"></span>
                </div>

                <div class="form-group hidden" id="modal_razonSocialEdit">
                    <label for="modal_razonSocial">Razón Social:</label>
                    <input type="text" id="modal_razonSocial" placeholder="Razón social del cliente">
                </div>

                <div class="form-group">
                    <label for="modal_whatsapp">Numero WhatsApp:</label>
                    <div class="input-group">
                        <span style="padding: 10px; background: #e9ecef; border: 1px solid #ced4da; border-radius: 5px 0 0 5px;">+51</span>
                        <input type="text" id="modal_whatsapp" placeholder="987654321" maxlength="9" style="border-radius: 0 5px 5px 0;">
                    </div>
                </div>

                <div class="form-group">
                    <label for="modal_email">Email (opcional):</label>
                    <input type="email" id="modal_email" placeholder="correo@ejemplo.com">
                </div>

                <div class="form-group">
                    <label for="modal_direccion">Dirección (opcional):</label>
                    <input type="text" id="modal_direccion" placeholder="Dirección del cliente">
                </div>

                <div class="btn-group">
                    <button id="btnGuardarModal" class="btn btn-success" onclick="agregarClienteModal()">Agregar Cliente</button>
                    <button class="btn btn-secondary" onclick="cerrarModalUsuario()">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Lista de Envío -->
    <div id="modalListaEnvio" class="modal-overlay" style="display: none;" onclick="cerrarModalListaEnvio(event)">
        <div class="modal-content" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h2>📋 Lista de Clientes para Envío</h2>
                <button class="modal-close" onclick="cerrarModalListaEnvio()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="status-info-text" style="margin-bottom: 20px;">
                    <strong>Total de clientes:</strong> <span id="totalClientesEnvio">0</span>
                </div>
                <div id="listaEnvioContainer" style="max-height: 500px; overflow-y: auto;">
                    <!-- Aquí se llenará dinámicamente la lista -->
                </div>
                <div class="form-actions">
                    <button class="btn btn-secondary" onclick="cerrarModalListaEnvio()">Cerrar</button>
                    <button class="btn btn-primary" onclick="cerrarModalListaEnvio(); enviarLote();">Proceder con Envío</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Notificaciones de Vencimiento -->
    <div id="modalVencimientos" class="modal-overlay" style="display: none;" onclick="cerrarModalVencimientos(event)">
        <div class="modal-content" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h2>⚠️ Notificaciones de Vencimiento</h2>
                <button class="modal-close" onclick="cerrarModalVencimientos()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="status-info-text" style="margin-bottom: 20px;">
                    <strong>Total de clientes a notificar:</strong> <span id="totalClientesVencimiento">0</span>
                </div>
                <div id="listaVencimientosContainer" style="max-height: 500px; overflow-y: auto;">
                    <!-- Aquí se llenará dinámicamente la lista -->
                </div>
                <div class="form-actions">
                    <button class="btn btn-secondary" onclick="cerrarModalVencimientos()">Cerrar</button>
                    <button class="btn btn-warning" onclick="cerrarModalVencimientos(); enviarRecordatorios();" id="btnEnviarRecordatoriosModal">Enviar Recordatorios</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Servicios de Usuario -->
    <div id="modalServiciosUsuario" class="modal-overlay" style="display: none;" onclick="cerrarModalServiciosUsuario(event)">
        <div class="modal-content" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h2 id="tituloServiciosUsuario">📦 Servicios Contratados</h2>
                <button class="modal-close" onclick="cerrarModalServiciosUsuario()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="status-info-text" style="margin-bottom: 20px;">
                    <strong>Cliente:</strong> <span id="nombreClienteServicios"></span><br>
                    <strong>RUC:</strong> <span id="rucClienteServicios"></span>
                </div>
                <div id="listaServiciosContainer" style="max-height: 500px; overflow-y: auto;">
                    <!-- Aquí se llenará dinámicamente la lista de servicios -->
                </div>
                <div class="form-actions">
                    <button class="btn btn-primary" onclick="agregarServicioUsuario()">➕ Agregar Servicio</button>
                    <button class="btn btn-secondary" onclick="cerrarModalServiciosUsuario()">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript modular -->
    <script src="js/config.js"></script>
    <script src="js/database.js"></script>
    <script src="js/whatsapp.js"></script>
    <script src="js/csv.js"></script>
    <script src="js/servicios.js"></script>
    <script src="js/dashboard_pagos.js"></script>
    <script src="js/modulo-envios.js"></script>
    <script src="js/main.js"></script>

    <!-- Script para menú de herramientas y secciones colapsables -->
    <script>
        function toggleToolsMenu(event) {
            event.stopPropagation();
            const menu = document.getElementById('toolsMenu');
            menu.classList.toggle('show');
        }

        function closeToolsMenu() {
            const menu = document.getElementById('toolsMenu');
            menu.classList.remove('show');
        }

        function toggleSection(sectionId) {
            const section = document.getElementById(sectionId);
            if (section) {
                section.classList.toggle('hidden');
            }
        }

        // Nueva función para colapsar/expandir secciones con animación
        function toggleSectionContent(contentId) {
            const content = document.getElementById(contentId);
            const section = content.closest('.section');

            if (content && section) {
                content.classList.toggle('collapsed');
                section.classList.toggle('collapsed');
            }
        }

        // Cerrar menú al hacer click fuera
        document.addEventListener('click', function(event) {
            const dropdown = document.querySelector('.tools-dropdown');
            const menu = document.getElementById('toolsMenu');
            if (dropdown && !dropdown.contains(event.target)) {
                menu.classList.remove('show');
            }
        });

        // ============================================
        // NAVEGACIÓN SIDEBAR
        // ============================================

        function navigateTo(pageName) {
            // Ocultar todas las páginas
            document.querySelectorAll('.page-view').forEach(page => {
                page.classList.remove('active');
            });

            // Mostrar la página seleccionada
            const targetPage = document.getElementById('page-' + pageName);
            if (targetPage) {
                targetPage.classList.add('active');
            }

            // Actualizar nav items activos
            document.querySelectorAll('.nav-item').forEach(item => {
                item.classList.remove('active');
            });

            // Encontrar y activar el nav item correspondiente
            const navItems = document.querySelectorAll('.nav-item');
            navItems.forEach(item => {
                const onclick = item.getAttribute('onclick');
                if (onclick && onclick.includes(`'${pageName}'`)) {
                    item.classList.add('active');
                }
            });

            // Cargar datos según la página
            if (pageName === 'usuarios') {
                cargarUsuarios();
            } else if (pageName === 'configuracion') {
                verificarImagenesConfig();
            } else if (pageName === 'envios') {
                // Inicializar módulo de envíos inteligente
                if (typeof ModuloEnvios !== 'undefined') {
                    ModuloEnvios.init();
                }
            }
        }

        // Sincronizar estado de botones en página de envíos
        function sincronizarBotonesEnvios() {
            const btnVerListaEnvioPage = document.getElementById('btnVerListaEnvioPage');
            const btnEnviarLotePage = document.getElementById('btnEnviarLotePage');
            const btnVistaPreviaPage = document.getElementById('btnVistaPreviaPage');

            // Sincronizar con el estado de la lista de clientes
            const tieneClientes = clientes.length > 0;

            if (btnVerListaEnvioPage) btnVerListaEnvioPage.disabled = !tieneClientes;
            if (btnEnviarLotePage) btnEnviarLotePage.disabled = !tieneClientes;
            if (btnVistaPreviaPage) btnVistaPreviaPage.disabled = !tieneClientes;
        }

        // Función global para actualizar progress bar en ambas ubicaciones
        window.actualizarProgressGlobal = function(porcentaje, texto) {
            // Actualizar progress bar del dashboard
            const progressBar = document.getElementById('progressBar');
            const progressText = document.getElementById('progressText');
            if (progressBar) progressBar.style.width = porcentaje + '%';
            if (progressText) progressText.textContent = texto;

            // Actualizar progress bar de la página de envíos
            const progressBarPage = document.getElementById('progressBarPage');
            const progressTextPage = document.getElementById('progressTextPage');
            if (progressBarPage) progressBarPage.style.width = porcentaje + '%';
            if (progressTextPage) progressTextPage.textContent = texto;
        };

        // Verificar imágenes en la página de configuración
        function verificarImagenesConfig() {
            verificarImagenConfig('logo.png', 'logoStatusConfig', 'Logo');
            verificarImagenConfig('mascota.png', 'mascotaStatusConfig', 'Mascota');
        }

        function verificarImagenConfig(ruta, elementoId, nombre) {
            const img = new Image();
            const elemento = document.getElementById(elementoId);

            if (!elemento) return;

            img.onload = function () {
                elemento.className = 'status-info status-success';
                const tamaño = img.width + 'x' + img.height;
                elemento.innerHTML = '<span class="emoji">✅</span> ' + nombre + ': Encontrado (' + tamaño + ')';
            };

            img.onerror = function () {
                elemento.className = 'status-info status-error';
                elemento.innerHTML = '<span class="emoji">❌</span> ' + nombre + ': No encontrado';
            };

            img.src = ruta;
        }

        // ============================================
        // NOTIFICACIONES DE VENCIMIENTO
        // ============================================

        let clientesNotificarNotif = [];

        async function verificarVencimientosNotif() {
            if (clientes.length === 0) {
                alert('No hay clientes en la lista para verificar');
                return;
            }

            const diasAnticipacion = parseInt(document.getElementById('diasAnticipacionNotif').value);
            if (isNaN(diasAnticipacion) || diasAnticipacion < 1) {
                alert('Días de anticipación debe ser un número válido mayor a 0');
                return;
            }

            try {
                const response = await fetch(API_CLIENTES_BASE + '?action=vencimientos&dias=' + diasAnticipacion);
                const data = await response.json();

                if (data.success) {
                    const resultado = data.data;
                    clientesNotificarNotif = [];

                    // Procesar todos los tipos de vencimiento
                    ['vencidos', 'vence_hoy', 'por_vencer'].forEach(tipo => {
                        resultado[tipo].forEach(cliente => {
                            // Buscar el cliente en la lista global para verificar si está excluido
                            const clienteLocal = clientes.find(c => c.id === cliente.id);

                            // Solo agregar si no está excluido de notificaciones
                            if (!clienteLocal || !clienteLocal.excluidoNotificaciones) {
                                clientesNotificarNotif.push({
                                    cliente: {
                                        id: cliente.id,
                                        ruc: cliente.ruc,
                                        razonSocial: cliente.razon_social,
                                        monto: cliente.monto,
                                        fecha: cliente.fecha_vencimiento,
                                        whatsapp: cliente.whatsapp
                                    },
                                    diasDiferencia: tipo === 'vence_hoy' ? 0 : cliente.dias_restantes
                                });
                            }
                        });
                    });

                    // Verificar si hay clientes para notificar
                    if (clientesNotificarNotif.length === 0 && resultado.total === 0) {
                        alert('ℹ️ No hay clientes para enviar recordatorios.\n\n' +
                              '📋 Los recordatorios solo se envían a clientes que:\n' +
                              '• Ya recibieron su orden de pago este mes\n' +
                              '• Están próximos a vencer o vencidos\n' +
                              '• No están excluidos de notificaciones\n\n' +
                              '💡 Primero envíe las órdenes de pago y luego los recordatorios.');
                        return;
                    }

                    mostrarResultadosVencimientosNotif(resultado);
                    document.getElementById('btnEnviarRecordatoriosNotif').disabled = clientesNotificarNotif.length === 0;
                } else {
                    alert('Error al verificar vencimientos: ' + data.error);
                }
            } catch (error) {
                console.error('Error verificando vencimientos:', error);
                alert('Error de conexión al verificar vencimientos');
            }
        }

        function mostrarResultadosVencimientosNotif(resultado) {
            const container = document.getElementById('notificationAreaNotif');
            const totalClientes = resultado.vencidos.length + resultado.vence_hoy.length + resultado.por_vencer.length;

            if (totalClientes === 0) {
                container.innerHTML = `
                    <div style="text-align: center; padding: var(--spacing-xl); color: var(--text-tertiary);">
                        <div style="font-size: 48px; margin-bottom: var(--spacing-md);">✅</div>
                        <div style="font-size: 16px; font-weight: 500;">No hay clientes próximos a vencer</div>
                    </div>
                `;
                return;
            }

            let html = `<div style="margin-bottom: var(--spacing-lg); padding: var(--spacing-md); background: var(--apple-blue); color: white; border-radius: var(--radius-md); font-weight: 600; text-align: center;">
                📊 Total de clientes para notificar: ${totalClientes}
            </div>`;

            // Sección de VENCIDOS
            if (resultado.vencidos.length > 0) {
                html += `
                    <div style="margin-bottom: 20px;">
                        <div style="background: #ff6b6b; color: white; padding: 12px; border-radius: 8px 8px 0 0; font-weight: bold;">
                            🚨 VENCIDOS (${resultado.vencidos.length})
                        </div>
                        <div style="border: 2px solid #ff6b6b; border-top: none; border-radius: 0 0 8px 8px; overflow: hidden;">
                `;

                resultado.vencidos.forEach((cliente, index) => {
                    const diasAtraso = Math.abs(cliente.dias_restantes);
                    html += `
                        <div style="padding: 12px; border-bottom: 1px solid #f8f9fa; ${index % 2 === 0 ? 'background: #fff5f5;' : 'background: white;'}">
                            <div style="font-weight: bold; color: #c92a2a; margin-bottom: 4px;">${cliente.razon_social}</div>
                            <div style="font-size: 13px; color: #666;">
                                RUC: ${cliente.ruc} •
                                WhatsApp: ${cliente.whatsapp ? (cliente.whatsapp.startsWith('51') ? '+' + cliente.whatsapp : '+51' + cliente.whatsapp) : 'No registrado'} •
                                Monto: S/ ${cliente.monto} •
                                <strong style="color: #c92a2a;">${diasAtraso} día${diasAtraso !== 1 ? 's' : ''} de atraso</strong>
                            </div>
                        </div>
                    `;
                });

                html += '</div></div>';
            }

            // Sección de VENCE HOY
            if (resultado.vence_hoy.length > 0) {
                html += `
                    <div style="margin-bottom: 20px;">
                        <div style="background: #ff8800; color: white; padding: 12px; border-radius: 8px 8px 0 0; font-weight: bold;">
                            ⏰ VENCE HOY (${resultado.vence_hoy.length})
                        </div>
                        <div style="border: 2px solid #ff8800; border-top: none; border-radius: 0 0 8px 8px; overflow: hidden;">
                `;

                resultado.vence_hoy.forEach((cliente, index) => {
                    html += `
                        <div style="padding: 12px; border-bottom: 1px solid #f8f9fa; ${index % 2 === 0 ? 'background: #fff4e6;' : 'background: white;'}">
                            <div style="font-weight: bold; color: #d9480f; margin-bottom: 4px;">${cliente.razon_social}</div>
                            <div style="font-size: 13px; color: #666;">
                                RUC: ${cliente.ruc} •
                                WhatsApp: ${cliente.whatsapp ? (cliente.whatsapp.startsWith('51') ? '+' + cliente.whatsapp : '+51' + cliente.whatsapp) : 'No registrado'} •
                                Monto: S/ ${cliente.monto} •
                                <strong style="color: #d9480f;">ÚLTIMO DÍA</strong>
                            </div>
                        </div>
                    `;
                });

                html += '</div></div>';
            }

            // Sección de POR VENCER
            if (resultado.por_vencer.length > 0) {
                html += `
                    <div style="margin-bottom: 20px;">
                        <div style="background: #fab005; color: white; padding: 12px; border-radius: 8px 8px 0 0; font-weight: bold;">
                            ⚠️ POR VENCER (${resultado.por_vencer.length})
                        </div>
                        <div style="border: 2px solid #fab005; border-top: none; border-radius: 0 0 8px 8px; overflow: hidden;">
                `;

                resultado.por_vencer.forEach((cliente, index) => {
                    html += `
                        <div style="padding: 12px; border-bottom: 1px solid #f8f9fa; ${index % 2 === 0 ? 'background: #fffae6;' : 'background: white;'}">
                            <div style="font-weight: bold; color: #e67700; margin-bottom: 4px;">${cliente.razon_social}</div>
                            <div style="font-size: 13px; color: #666;">
                                RUC: ${cliente.ruc} •
                                WhatsApp: ${cliente.whatsapp ? (cliente.whatsapp.startsWith('51') ? '+' + cliente.whatsapp : '+51' + cliente.whatsapp) : 'No registrado'} •
                                Monto: S/ ${cliente.monto} •
                                <strong style="color: #e67700;">${cliente.dias_restantes} día${cliente.dias_restantes !== 1 ? 's' : ''} restantes</strong>
                            </div>
                        </div>
                    `;
                });

                html += '</div></div>';
            }

            container.innerHTML = html;
        }

        async function enviarRecordatoriosNotif() {
            if (clientesNotificarNotif.length === 0) {
                alert('No hay clientes para notificar');
                return;
            }

            // Usar la función global de enviarRecordatorios pero con clientesNotificarNotif
            const clientesNotificarOriginal = window.clientesNotificar;
            window.clientesNotificar = clientesNotificarNotif;

            await enviarRecordatorios();

            window.clientesNotificar = clientesNotificarOriginal;
        }

        // ============================================
        // GESTIÓN DE USUARIOS
        // ============================================

        async function cargarUsuarios() {
            const tbody = document.getElementById('usuariosTableBody');

            try {
                // Cargar TODOS los clientes, incluyendo deshabilitados
                const response = await fetch(API_CLIENTES_BASE + '?action=list_all');
                const data = await response.json();

                if (data.success && data.data.length > 0) {
                    // Ordenar por ID
                    const usuarios = data.data.sort((a, b) => a.id - b.id);

                    tbody.innerHTML = usuarios.map(usuario => {
                        // Determinar estado
                        let estadoClass = 'activo';
                        let estadoTexto = 'Activo';

                        // Verificar si está deshabilitado (activo = FALSE)
                        if (usuario.activo === false || usuario.activo === 0 || usuario.activo === '0') {
                            estadoClass = 'inactivo';
                            estadoTexto = 'Deshabilitado';
                        } else if (usuario.estado_servicio === 'vencido') {
                            estadoClass = 'vencido';
                            estadoTexto = 'Vencido';
                        } else if (usuario.estado_servicio === 'suspendido') {
                            estadoClass = 'suspendido';
                            estadoTexto = 'Suspendido';
                        } else if (usuario.estado_servicio === 'inactivo') {
                            estadoClass = 'inactivo';
                            estadoTexto = 'Inactivo';
                        }

                        // Mostrar icono diferente si está deshabilitado
                        const botonAccion = (usuario.activo === false || usuario.activo === 0 || usuario.activo === '0')
                            ? `<button class="usuario-action-btn" onclick="habilitarUsuario(${usuario.id})" title="Habilitar">
                                    ✅
                               </button>`
                            : `<button class="usuario-action-btn danger" onclick="deshabilitarUsuario(${usuario.id})" title="Deshabilitar">
                                    🚫
                               </button>`;

                        return `
                            <tr style="${(usuario.activo === false || usuario.activo === 0 || usuario.activo === '0') ? 'opacity: 0.6;' : ''}">
                                <td>${usuario.id}</td>
                                <td>${usuario.ruc || '-'}</td>
                                <td>${usuario.razon_social || usuario.nombre || '-'}</td>
                                <td>${usuario.whatsapp || '-'}</td>
                                <td>
                                    <span class="usuario-estado-badge ${estadoClass}">
                                        ${estadoTexto}
                                    </span>
                                </td>
                                <td>
                                    <div class="usuario-actions">
                                        <button class="usuario-action-btn" onclick="verServiciosUsuario(${usuario.id})" title="Servicios">
                                            📦
                                        </button>
                                        <button class="usuario-action-btn" onclick="editarUsuario(${usuario.id})" title="Editar">
                                            ✏️
                                        </button>
                                        ${botonAccion}
                                    </div>
                                </td>
                            </tr>
                        `;
                    }).join('');
                } else {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="6" style="text-align: center; padding: var(--spacing-xl); color: var(--text-tertiary);">
                                <div class="empty-state">
                                    <div class="empty-state-icon">👥</div>
                                    <div class="empty-state-text">No hay usuarios registrados</div>
                                </div>
                            </td>
                        </tr>
                    `;
                }
            } catch (error) {
                console.error('Error al cargar usuarios:', error);
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" style="text-align: center; padding: var(--spacing-xl); color: var(--apple-red);">
                            Error al cargar los usuarios. Por favor, intente nuevamente.
                        </td>
                    </tr>
                `;
            }
        }

        // Variable para almacenar la razón social del modal
        let razonSocialModalActual = null;

        // Función de validación de WhatsApp
        function validarWhatsApp(numero) {
            // Limpiar el número
            numero = numero.replace(/\D/g, '');

            // Validar longitud (debe ser 9 dígitos sin código de país, o 11 con código 51)
            if (numero.length === 9) {
                // Número sin código de país, agregarlo
                return {
                    valido: true,
                    whatsapp: '51' + numero
                };
            } else if (numero.length === 11 && numero.startsWith('51')) {
                // Ya tiene el código de país
                return {
                    valido: true,
                    whatsapp: numero
                };
            } else {
                return {
                    valido: false,
                    mensaje: 'El número de WhatsApp debe tener 9 dígitos'
                };
            }
        }

        function crearNuevoUsuario() {
            // Resetear ID de edición
            usuarioEditandoId = null;

            // Limpiar campos del modal
            document.getElementById('modal_ruc').value = '';
            document.getElementById('modal_razonSocial').value = '';
            document.getElementById('modal_whatsapp').value = '';
            document.getElementById('modal_email').value = '';
            document.getElementById('modal_direccion').value = '';
            document.getElementById('modal_razonSocialDisplay').classList.add('hidden');
            document.getElementById('modal_razonSocialEdit').classList.add('hidden');
            razonSocialModalActual = null;

            // Cambiar título del modal y texto del botón
            document.getElementById('modalUsuarioTitulo').innerHTML = '📝 Datos del Cliente';
            document.getElementById('btnGuardarModal').textContent = 'Agregar Cliente';

            // Mostrar modal
            document.getElementById('modalUsuario').style.display = 'flex';
        }

        function cerrarModalUsuario(event) {
            if (event && event.target !== event.currentTarget) return;
            document.getElementById('modalUsuario').style.display = 'none';
            usuarioEditandoId = null; // Resetear ID al cerrar
        }

        // Consultar RUC en el modal (copia de la función original)
        async function consultarRUCModal() {
            const rucInput = document.getElementById('modal_ruc');
            const ruc = rucInput.value.trim();

            if (!ruc) {
                alert('Por favor ingrese un RUC');
                return;
            }

            const validacion = validarRUC(ruc);
            if (!validacion.valido) {
                alert(validacion.mensaje);
                return;
            }

            const btnConsultar = rucInput.nextElementSibling;
            const textoOriginal = btnConsultar.textContent;
            btnConsultar.textContent = CONFIG.MENSAJES.CONSULTANDO;
            btnConsultar.disabled = true;

            try {
                const response = await fetch(API_RUC_BASE + validacion.ruc);
                const data = await response.json();

                if (data.success && data.data) {
                    const razonSocial = data.data.nombre_o_razon_social;
                    razonSocialModalActual = razonSocial;

                    const fuente = data.source === 'cache' ? ' (Cache)' : ' (API)';
                    document.getElementById('modal_razonSocialText').textContent = razonSocial + fuente;
                    document.getElementById('modal_razonSocialDisplay').classList.remove('hidden');
                    document.getElementById('modal_razonSocial').value = razonSocial;
                } else {
                    throw new Error(data.error || 'RUC no encontrado o invalido');
                }

            } catch (error) {
                console.error('Error consultando RUC:', error);
                alert('Error al consultar RUC: ' + error.message);

                document.getElementById('modal_razonSocialDisplay').classList.add('hidden');
                razonSocialModalActual = null;

            } finally {
                btnConsultar.textContent = textoOriginal;
                btnConsultar.disabled = false;
            }
        }

        // Agregar o editar cliente desde el modal
        async function agregarClienteModal() {
            // Si estamos editando, permitir que se use el campo editable de razón social
            let razonSocial;
            if (usuarioEditandoId) {
                // Modo edición: tomar del campo editable
                razonSocial = document.getElementById('modal_razonSocial').value.trim();
                if (!razonSocial) {
                    alert('La razón social es obligatoria');
                    return;
                }
            } else {
                // Modo creación: debe consultarse desde RUC
                if (!razonSocialModalActual) {
                    alert('Primero debe consultar un RUC válido');
                    return;
                }
                razonSocial = razonSocialModalActual;
            }

            const ruc = document.getElementById('modal_ruc').value.trim();
            const whatsapp = document.getElementById('modal_whatsapp').value.trim();
            const email = document.getElementById('modal_email').value.trim();
            const direccion = document.getElementById('modal_direccion').value.trim();

            if (!whatsapp) {
                alert('El número de WhatsApp es obligatorio');
                return;
            }

            const validacionWhatsApp = validarWhatsApp(whatsapp);
            if (!validacionWhatsApp.valido) {
                alert(validacionWhatsApp.mensaje);
                return;
            }

            try {
                const formData = new FormData();

                // Si estamos editando, usar action 'update', sino 'create'
                if (usuarioEditandoId) {
                    formData.append('action', 'update');
                    formData.append('id', usuarioEditandoId);
                } else {
                    formData.append('action', 'create');
                }

                formData.append('ruc', ruc);
                formData.append('razon_social', razonSocial);
                formData.append('whatsapp', validacionWhatsApp.whatsapp);

                // Campos opcionales
                if (email) formData.append('email', email);
                if (direccion) formData.append('direccion', direccion);

                const response = await fetch(API_CLIENTES_BASE, {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    alert(usuarioEditandoId ? 'Cliente actualizado exitosamente' : 'Cliente agregado exitosamente');

                    // Cerrar el modal
                    cerrarModalUsuario();

                    // Recargar la tabla de usuarios si estamos en esa página
                    const paginaUsuarios = document.getElementById('page-usuarios');
                    if (paginaUsuarios && paginaUsuarios.classList.contains('active')) {
                        cargarUsuarios();
                    }

                    // Recargar lista de clientes en el dashboard principal
                    if (typeof cargarClientesDesdeDB === 'function') {
                        cargarClientesDesdeDB();
                    }
                } else {
                    alert('Error: ' + (data.error || 'No se pudo guardar el cliente'));
                }
            } catch (error) {
                console.error('Error al guardar cliente:', error);
                alert('Error al guardar el cliente');
            }
        }

        let usuarioEditandoId = null;

        async function editarUsuario(id) {
            usuarioEditandoId = id;

            try {
                // Obtener los datos del usuario
                const response = await fetch(API_CLIENTES_BASE + '?action=get&id=' + id);
                const data = await response.json();

                if (data.success && data.data) {
                    const usuario = data.data;

                    // Cargar datos básicos del cliente
                    document.getElementById('modal_ruc').value = usuario.ruc || '';

                    // Limpiar el número de WhatsApp: quitar el prefijo +51 o 51 si existe
                    let whatsappLimpio = usuario.whatsapp || '';
                    if (whatsappLimpio.startsWith('+51')) {
                        whatsappLimpio = whatsappLimpio.substring(3);
                    } else if (whatsappLimpio.startsWith('51')) {
                        whatsappLimpio = whatsappLimpio.substring(2);
                    }
                    document.getElementById('modal_whatsapp').value = whatsappLimpio;

                    // Cargar campos opcionales
                    document.getElementById('modal_email').value = usuario.email || '';
                    document.getElementById('modal_direccion').value = usuario.direccion || '';

                    // Mostrar razón social - En modo edición, mostrar campo editable
                    razonSocialModalActual = usuario.razon_social || '';

                    // Ocultar el display de solo lectura
                    const displayDiv = document.getElementById('modal_razonSocialDisplay');
                    if (displayDiv) {
                        displayDiv.classList.add('hidden');
                    }

                    // Mostrar el campo editable y cargar el valor
                    const editDiv = document.getElementById('modal_razonSocialEdit');
                    const inputRazonSocial = document.getElementById('modal_razonSocial');
                    if (editDiv && inputRazonSocial) {
                        editDiv.classList.remove('hidden');
                        inputRazonSocial.value = razonSocialModalActual;
                    }

                    // Cambiar título del modal y texto del botón
                    document.getElementById('modalUsuarioTitulo').innerHTML = '✏️ Editar Cliente';
                    document.getElementById('btnGuardarModal').textContent = 'Guardar Cambios';

                    // Mostrar modal
                    document.getElementById('modalUsuario').style.display = 'flex';
                } else {
                    alert('Error: No se pudo cargar los datos del usuario');
                }
            } catch (error) {
                console.error('Error al cargar usuario:', error);
                alert('Error al cargar los datos del usuario');
            }
        }

        async function deshabilitarUsuario(id) {
            if (!confirm('¿Estás seguro de que deseas deshabilitar este cliente? Podrás habilitarlo después si lo necesitas.')) {
                return;
            }

            try {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', id);

                const response = await fetch(API_CLIENTES_BASE, {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    alert('Cliente deshabilitado exitosamente');
                    cargarUsuarios(); // Recargar la tabla
                } else {
                    alert('Error: ' + (data.error || 'No se pudo deshabilitar el cliente'));
                }
            } catch (error) {
                console.error('Error al deshabilitar cliente:', error);
                alert('Error al deshabilitar el cliente');
            }
        }

        async function habilitarUsuario(id) {
            if (!confirm('¿Deseas habilitar este cliente nuevamente?')) {
                return;
            }

            try {
                const formData = new FormData();
                formData.append('action', 'enable');
                formData.append('id', id);

                const response = await fetch(API_CLIENTES_BASE, {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    alert('Cliente habilitado exitosamente');
                    cargarUsuarios(); // Recargar la tabla
                } else {
                    alert('Error: ' + (data.error || 'No se pudo habilitar el cliente'));
                }
            } catch (error) {
                console.error('Error al habilitar cliente:', error);
                alert('Error al habilitar el cliente');
            }
        }

        // Ver servicios de un usuario
        let clienteServiciosActual = null;

        async function verServiciosUsuario(id) {
            try {
                // Obtener datos del cliente
                const response = await fetch(API_CLIENTES_BASE + '?action=get&id=' + id);
                const data = await response.json();

                if (data.success && data.data) {
                    clienteServiciosActual = data.data;

                    // Actualizar información del cliente en el modal
                    document.getElementById('nombreClienteServicios').textContent = data.data.razon_social;
                    document.getElementById('rucClienteServicios').textContent = data.data.ruc;

                    // Cargar servicios (el API devuelve 'servicios_contratados')
                    cargarServiciosUsuario(data.data.servicios_contratados || []);

                    // Mostrar modal
                    document.getElementById('modalServiciosUsuario').style.display = 'flex';
                } else {
                    alert('Error al cargar el cliente');
                }
            } catch (error) {
                console.error('Error al cargar servicios:', error);
                alert('Error al cargar los servicios del cliente');
            }
        }

        function cargarServiciosUsuario(servicios) {
            const container = document.getElementById('listaServiciosContainer');

            if (!servicios || servicios.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-state-icon">📦</div>
                        <div class="empty-state-text">No hay servicios contratados</div>
                    </div>
                `;
                return;
            }

            container.innerHTML = servicios.map((servicio, index) => {
                const estadoClass = servicio.estado === 'activo' ? 'activo' :
                                   servicio.estado === 'vencido' ? 'vencido' :
                                   servicio.estado === 'suspendido' ? 'suspendido' : 'inactivo';

                // Determinar símbolo de moneda
                const simboloMoneda = servicio.moneda === 'USD' ? '$' : 'S/';

                return `
                    <div style="padding: 15px; border: 1px solid #e9ecef; border-radius: 8px; margin-bottom: 10px; ${index % 2 === 0 ? 'background: #f8f9fa;' : 'background: white;'}">
                        <div style="display: grid; grid-template-columns: 1fr auto; gap: 15px; align-items: start;">
                            <div>
                                <div style="font-weight: bold; color: #333; font-size: 16px; margin-bottom: 8px;">
                                    ${servicio.servicio_nombre || 'Servicio'}
                                </div>
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 8px; font-size: 14px; color: #666;">
                                    <div><strong>Precio:</strong> ${simboloMoneda} ${servicio.precio} ${servicio.moneda}</div>
                                    <div><strong>Período:</strong> ${servicio.periodo_facturacion}</div>
                                    <div><strong>Vencimiento:</strong> ${servicio.fecha_vencimiento}</div>
                                    <div><strong>Días restantes:</strong> ${servicio.dias_restantes} días</div>
                                    <div><strong>Estado:</strong> <span class="usuario-estado-badge ${estadoClass}">${servicio.estado}</span></div>
                                </div>
                            </div>
                            <div style="display: flex; gap: 5px;">
                                <button onclick="editarServicioUsuario(${servicio.contrato_id})"
                                        class="usuario-action-btn"
                                        title="Editar servicio">
                                    ✏️
                                </button>
                                <button onclick="eliminarServicioUsuario(${servicio.contrato_id})"
                                        class="usuario-action-btn danger"
                                        title="Eliminar servicio">
                                    🗑️
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }

        function cerrarModalServiciosUsuario(event) {
            if (event && event.target !== event.currentTarget) {
                return;
            }
            document.getElementById('modalServiciosUsuario').style.display = 'none';
            clienteServiciosActual = null;
        }

        async function agregarServicioUsuario() {
            if (!clienteServiciosActual) {
                alert('Error: No se ha seleccionado un cliente');
                return;
            }

            // Reutilizar la funcionalidad del dashboard
            if (typeof ServiciosUI !== 'undefined' && ServiciosUI.abrirModalContratacion) {
                await ServiciosUI.abrirModalContratacion(clienteServiciosActual.id);
            } else {
                // Fallback: crear modal simple si ServiciosUI no está disponible
                await abrirModalAgregarServicioSimple();
            }
        }

        async function abrirModalAgregarServicioSimple() {
            // Obtener catálogo de servicios
            try {
                const response = await fetch('/api/servicios.php?action=catalogo&activos=1');
                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.error || 'Error al cargar catálogo');
                }

                mostrarModalAgregarServicioSimple(data.data);
            } catch (error) {
                console.error('Error al cargar catálogo:', error);
                alert('Error al cargar el catálogo de servicios');
            }
        }

        function mostrarModalAgregarServicioSimple(catalogoServicios) {
            // Crear modal de agregar servicio
            const modalHTML = `
                <div id="modalAgregarServicioSimple" class="modal-overlay" style="display: flex;" onclick="cerrarModalAgregarServicioSimple(event)">
                    <div class="modal-content" onclick="event.stopPropagation()" style="max-width: 600px;">
                        <div class="modal-header">
                            <h2>➕ Agregar Servicio</h2>
                            <button class="modal-close" onclick="cerrarModalAgregarServicioSimple()">&times;</button>
                        </div>
                        <div class="modal-body">
                            <form id="formAgregarServicio" onsubmit="enviarNuevoServicio(event)">
                                <div class="form-group">
                                    <label for="servicioSelectSimple">Servicio *</label>
                                    <select id="servicioSelectSimple" required onchange="actualizarFormularioServicioSimple()">
                                        <option value="">Seleccione un servicio</option>
                                        ${catalogoServicios.map(s => `
                                            <option value="${s.id}"
                                                data-precio="${s.precio_base}"
                                                data-moneda="${s.moneda}"
                                                data-periodos='${JSON.stringify(s.periodos_disponibles)}'>
                                                ${s.nombre} - ${s.moneda === 'USD' ? '$' : 'S/'} ${s.precio_base}
                                            </option>
                                        `).join('')}
                                    </select>
                                </div>

                                <div class="form-group" id="periodoGroupSimple" style="display: none;">
                                    <label for="periodoSelectSimple">Período de Facturación *</label>
                                    <select id="periodoSelectSimple" required onchange="calcularFechaVencimientoSimple()">
                                        <option value="">Seleccione período</option>
                                    </select>
                                </div>

                                <div class="form-group" id="precioGroupSimple" style="display: none;">
                                    <label for="precioInputSimple">Precio *</label>
                                    <div class="input-group">
                                        <span id="monedaLabelSimple" style="padding: 10px; background: #e9ecef; border: 1px solid #ced4da; border-radius: 5px 0 0 5px;">S/</span>
                                        <input type="number" id="precioInputSimple" step="0.01" min="0" required
                                               style="flex: 1; padding: 10px; border: 1px solid #ced4da; border-left: none; border-radius: 0 5px 5px 0;">
                                    </div>
                                </div>

                                <div class="form-group" id="fechaInicioGroupSimple" style="display: none;">
                                    <label for="fechaInicioInputSimple">Fecha de Inicio *</label>
                                    <input type="date" id="fechaInicioInputSimple" required onchange="calcularFechaVencimientoSimple()">
                                </div>

                                <div class="form-group" id="fechaVencimientoGroupSimple" style="display: none;">
                                    <label for="fechaVencimientoInputSimple">Fecha de Vencimiento</label>
                                    <input type="date" id="fechaVencimientoInputSimple" readonly style="background: #f8f9fa;">
                                </div>

                                <div class="form-actions">
                                    <button type="button" class="btn btn-secondary" onclick="cerrarModalAgregarServicioSimple()">Cancelar</button>
                                    <button type="submit" class="btn btn-primary" id="btnGuardarServicioSimple" disabled>Agregar Servicio</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            `;

            // Remover modal existente si hay
            const modalExistente = document.getElementById('modalAgregarServicioSimple');
            if (modalExistente) {
                modalExistente.remove();
            }

            document.body.insertAdjacentHTML('beforeend', modalHTML);

            // Configurar fecha de inicio por defecto (hoy)
            const hoy = new Date().toISOString().split('T')[0];
            document.getElementById('fechaInicioInputSimple').value = hoy;
        }

        function actualizarFormularioServicioSimple() {
            const select = document.getElementById('servicioSelectSimple');
            const option = select.options[select.selectedIndex];

            if (!option.value) {
                document.getElementById('periodoGroupSimple').style.display = 'none';
                document.getElementById('precioGroupSimple').style.display = 'none';
                document.getElementById('fechaInicioGroupSimple').style.display = 'none';
                document.getElementById('fechaVencimientoGroupSimple').style.display = 'none';
                document.getElementById('btnGuardarServicioSimple').disabled = true;
                return;
            }

            const precio = option.dataset.precio;
            const moneda = option.dataset.moneda;
            const periodos = JSON.parse(option.dataset.periodos || '[]');

            // Actualizar precio
            document.getElementById('monedaLabelSimple').textContent = moneda === 'USD' ? '$' : 'S/';
            document.getElementById('precioInputSimple').value = parseFloat(precio).toFixed(2);

            // Actualizar períodos
            const periodoSelect = document.getElementById('periodoSelectSimple');
            periodoSelect.innerHTML = '<option value="">Seleccione período</option>';
            periodos.forEach(periodo => {
                const opt = document.createElement('option');
                opt.value = periodo;
                opt.textContent = periodo.charAt(0).toUpperCase() + periodo.slice(1);
                periodoSelect.appendChild(opt);
            });

            // Mostrar campos
            document.getElementById('periodoGroupSimple').style.display = 'block';
            document.getElementById('precioGroupSimple').style.display = 'block';
            document.getElementById('fechaInicioGroupSimple').style.display = 'block';
            document.getElementById('fechaVencimientoGroupSimple').style.display = 'block';
        }

        function calcularFechaVencimientoSimple() {
            const fechaInicio = document.getElementById('fechaInicioInputSimple').value;
            const periodo = document.getElementById('periodoSelectSimple').value;

            if (!fechaInicio || !periodo) {
                return;
            }

            const fecha = new Date(fechaInicio + 'T00:00:00');

            switch (periodo) {
                case 'mensual':
                    fecha.setMonth(fecha.getMonth() + 1);
                    break;
                case 'trimestral':
                    fecha.setMonth(fecha.getMonth() + 3);
                    break;
                case 'semestral':
                    fecha.setMonth(fecha.getMonth() + 6);
                    break;
                case 'anual':
                    fecha.setFullYear(fecha.getFullYear() + 1);
                    break;
            }

            const fechaVencimiento = fecha.toISOString().split('T')[0];
            document.getElementById('fechaVencimientoInputSimple').value = fechaVencimiento;
            document.getElementById('btnGuardarServicioSimple').disabled = false;
        }

        async function enviarNuevoServicio(event) {
            event.preventDefault();

            const servicioId = document.getElementById('servicioSelectSimple').value;
            const periodo = document.getElementById('periodoSelectSimple').value;
            const precio = document.getElementById('precioInputSimple').value;
            const fechaInicio = document.getElementById('fechaInicioInputSimple').value;
            const fechaVencimiento = document.getElementById('fechaVencimientoInputSimple').value;

            const datos = {
                action: 'contratar',
                cliente_id: clienteServiciosActual.id,
                servicio_id: servicioId,
                precio: precio,
                periodo_facturacion: periodo,
                fecha_inicio: fechaInicio,
                fecha_vencimiento: fechaVencimiento
            };

            try {
                const response = await fetch('/api/servicios.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(datos)
                });

                const data = await response.json();

                if (data.success) {
                    alert('Servicio agregado exitosamente');
                    cerrarModalAgregarServicioSimple();
                    // Recargar servicios del cliente
                    verServiciosUsuario(clienteServiciosActual.id);
                } else {
                    alert('Error: ' + (data.error || 'No se pudo agregar el servicio'));
                }
            } catch (error) {
                console.error('Error al agregar servicio:', error);
                alert('Error al agregar el servicio');
            }
        }

        function cerrarModalAgregarServicioSimple(event) {
            if (event && event.target !== event.currentTarget) {
                return;
            }
            const modal = document.getElementById('modalAgregarServicioSimple');
            if (modal) {
                modal.remove();
            }
        }

        function editarServicioUsuario(servicioId) {
            alert('Funcionalidad de editar servicio en desarrollo - ID: ' + servicioId);
            // TODO: Implementar modal para editar servicio
        }

        function eliminarServicioUsuario(servicioId) {
            if (!confirm('¿Estás seguro de eliminar este servicio?')) {
                return;
            }
            alert('Funcionalidad de eliminar servicio en desarrollo - ID: ' + servicioId);
            // TODO: Implementar eliminación de servicio
        }

        // Toggle user menu
        function toggleUserMenu() {
            const userMenu = document.getElementById('userMenu');
            userMenu.classList.toggle('active');
        }

        // Cerrar user menu al hacer click fuera
        document.addEventListener('click', function(event) {
            const userMenu = document.getElementById('userMenu');
            if (userMenu && !userMenu.contains(event.target)) {
                userMenu.classList.remove('active');
            }
        });
    </script>
</body>

</html>