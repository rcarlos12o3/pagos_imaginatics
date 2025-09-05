<?php 
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
</head>

<body>
    <div class="container">
        <div class="header">
            <div class="nav-buttons">
                <a href="pagos.php" class="nav-btn">💳 Gestión de Pagos</a>
                <a href="debug.php" class="nav-btn">🔧 Debug</a>
                <span class="nav-btn" style="background: rgba(255,255,255,0.1);">👤 <?php echo $_SESSION['nombre'] ?? 'Usuario'; ?></span>
                <a href="#" onclick="logout()" class="nav-btn" style="background: rgba(255,0,0,0.2);">🚪 Salir</a>
            </div>
            <h1>Generador de Ordenes de Pago</h1>
            <p>Sistema integral para gestion de pagos y notificaciones por WhatsApp</p>
        </div>

        <div class="main-content">
            <!-- Panel Izquierdo -->
            <div class="left-panel">
                <!-- Seccion 1: Datos del Cliente -->
                <div class="section">
                    <div class="section-title">1. Datos del Cliente</div>

                    <div class="form-group">
                        <label for="ruc">RUC:</label>
                        <div class="input-group">
                            <input type="text" id="ruc" placeholder="Ingrese RUC de 11 digitos" maxlength="11">
                            <button class="btn btn-primary" onclick="consultarRUC()">Consultar</button>
                        </div>
                    </div>

                    <div id="razonSocialDisplay" class="status-info-text hidden">
                        <strong>Razon Social:</strong> <span id="razonSocialText"></span>
                    </div>

                    <div class="form-group hidden" id="razonSocialEdit">
                        <label for="razonSocial">Razón Social:</label>
                        <input type="text" id="razonSocial" placeholder="Razón social del cliente">
                    </div>

                    <div class="form-group">
                        <label for="monto">Monto (S/):</label>
                        <input type="number" id="monto" placeholder="0.00" step="0.01" min="0">
                    </div>

                    <div class="form-group">
                        <label for="fechaVencimiento">Fecha de Vencimiento:</label>
                        <input type="date" id="fechaVencimiento">
                    </div>

                    <div class="form-group">
                        <label for="whatsapp">Numero WhatsApp:</label>
                        <div class="input-group">
                            <span
                                style="padding: 10px; background: #e9ecef; border: 1px solid #ced4da; border-radius: 5px 0 0 5px;">+51</span>
                            <input type="text" id="whatsapp" placeholder="987654321" maxlength="9"
                                style="border-radius: 0 5px 5px 0;">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="tipoServicio">Tipo de Servicio:</label>
                        <select id="tipoServicio" required>
                            <option value="">Seleccionar tipo de servicio</option>
                            <option value="mensual">Mensual</option>
                            <option value="trimestral">Trimestral</option>
                            <option value="semestral">Semestral</option>
                            <option value="anual" selected>Anual</option>
                        </select>
                    </div>

                    <div class="btn-group">
                        <button class="btn btn-success" onclick="agregarCliente()">Agregar Cliente</button>
                        <button class="btn btn-secondary" onclick="limpiarFormulario()">Limpiar</button>
                    </div>
                </div>

                <!-- Seccion 2: Carga Masiva CSV -->
                <div class="section">
                    <div class="section-title">2. Carga Masiva desde CSV</div>

                    <div class="csv-info">
                        <strong>Formato CSV:</strong> RUC|RAZON_SOCIAL|MONTO|VENCIMIENTO|NUMERO<br>
                        <strong>Ejemplo:</strong> 20123456789|EMPRESA SAC|1500.00|15/12/2025|987654321
                    </div>

                    <div class="btn-group">
                        <button class="btn btn-primary" onclick="document.getElementById('csvFile').click()">Cargar
                            CSV</button>
                        <button class="btn btn-warning" onclick="descargarPlantilla()">Descargar Plantilla</button>
                    </div>
                    <input type="file" id="csvFile" accept=".csv" style="display: none;" onchange="cargarCSV(event)">
                </div>

                <!-- Seccion 3: Estado de Imagenes -->
                <div class="section">
                    <div class="section-title">3. Estado de Imagenes</div>

                    <div id="logoStatus" class="status-info">
                        <span class="emoji">🔍</span> Logo: Verificando...
                    </div>
                    <div id="mascotaStatus" class="status-info">
                        <span class="emoji">🔍</span> Mascota: Verificando...
                    </div>
                    <div class="text-muted" style="font-size: 12px; margin-top: 10px;">
                        Coloque logo.png y mascota.png en la misma carpeta del proyecto
                    </div>
                </div>

                <!-- Seccion 4: Notificaciones de Vencimiento -->
                <div class="section">
                    <div class="section-title">4. Notificaciones de Vencimiento</div>

                    <div class="form-group">
                        <label for="diasAnticipacion">Dias de anticipacion:</label>
                        <div class="input-group">
                            <input type="number" id="diasAnticipacion" value="3" min="1" max="30"
                                style="flex: none; width: 80px;">
                            <span style="padding: 10px 0;">dias antes del vencimiento</span>
                        </div>
                    </div>

                    <div class="btn-group">
                        <button class="btn btn-warning" onclick="verificarVencimientos()">Verificar
                            Vencimientos</button>
                        <button class="btn btn-danger" id="btnEnviarRecordatorios" onclick="enviarRecordatorios()"
                            disabled>Enviar Recordatorios</button>
                    </div>

                    <div class="notification-area" id="notificationArea">
                        Presione "Verificar Vencimientos" para analizar el estado de las cuentas
                    </div>
                </div>

                <!-- Seccion 5: Envio en Lote -->
                <div class="section">
                    <div class="section-title">5. Envio en Lote</div>

                    <div class="btn-group">
                        <button class="btn btn-primary" id="btnEnviarLote" onclick="enviarLote()" disabled>Enviar Todo
                            por WhatsApp</button>
                        <button class="btn btn-warning" id="btnVistaPrevia" onclick="mostrarVistaPrevia()"
                            disabled>Vista Previa</button>
                        <button class="btn btn-danger" onclick="limpiarLista()">Limpiar Lista</button>
                    </div>

                    <div class="progress-bar">
                        <div class="progress-fill" id="progressBar"></div>
                    </div>
                    <div class="text-muted" id="progressText"></div>
                </div>
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
                        <input type="text" id="searchFilter" placeholder="🔍 Buscar por RUC, razón social o WhatsApp..." 
                               style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px; margin-bottom: 10px; font-size: 14px;">
                    </div>
                    <div class="client-list-content" id="clientList">
                        <div style="padding: 40px; text-align: center; color: #6c757d;">
                            <span class="emoji">📝</span><br>
                            No hay clientes agregados.<br>
                            Agregue clientes usando el formulario o cargue un archivo CSV.
                        </div>
                    </div>
                </div>

                <div class="btn-group">
                    <button class="btn btn-warning" id="btnEditarSeleccionado" onclick="editarClienteSeleccionado()"
                        disabled>
                        ✏️ Editar Seleccionado
                    </button>
                    <button class="btn btn-danger" id="btnEliminarCliente" onclick="eliminarClienteSeleccionado()"
                        disabled>🗑️ Eliminar Seleccionado</button>
                </div>

                <!-- Vista Previa -->
                <div class="section">
                    <div class="section-title">Vista Previa</div>
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

    <!-- JavaScript modular -->
    <script src="js/config.js"></script>
    <script src="js/database.js"></script>
    <script src="js/whatsapp.js"></script>
    <script src="js/csv.js"></script>
    <script src="js/main.js"></script>
</body>

</html>