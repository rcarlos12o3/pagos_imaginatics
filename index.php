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

        /* ================================================
           APPLE DESIGN - MÓDULO DE NOTIFICACIONES
           ================================================ */

        /* Dashboard de Estado Automático */
        .auto-dashboard {
            background: linear-gradient(135deg, #ffffff 0%, #f5f5f7 100%);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
            border: 1px solid rgba(0, 0, 0, 0.06);
            box-shadow: 0 2px 16px rgba(0, 0, 0, 0.06);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .auto-dashboard:hover {
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
            transform: translateY(-2px);
        }

        .auto-dashboard-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.08);
        }

        .auto-dashboard-title {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 20px;
            font-weight: 600;
            color: #1d1d1f;
            letter-spacing: -0.02em;
        }

        .auto-dashboard-title .icon {
            font-size: 28px;
        }

        .auto-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 0.01em;
        }

        .auto-status-badge.active {
            background: linear-gradient(135deg, #34C759 0%, #30D158 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(52, 199, 89, 0.3);
        }

        .auto-status-badge.paused,
        .auto-status-badge.inactive {
            background: linear-gradient(135deg, #FF9500 0%, #FF8000 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(255, 149, 0, 0.3);
        }

        .auto-status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: white;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.7; transform: scale(0.95); }
        }

        .auto-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }

        .auto-info-item {
            background: white;
            padding: 14px 16px;
            border-radius: 12px;
            border: 1px solid rgba(0, 0, 0, 0.06);
        }

        .auto-info-label {
            font-size: 13px;
            color: #86868b;
            margin-bottom: 4px;
            font-weight: 500;
        }

        .auto-info-value {
            font-size: 17px;
            color: #1d1d1f;
            font-weight: 600;
        }

        /* Cards de Estadísticas */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            border: 1px solid rgba(0, 0, 0, 0.06);
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: currentColor;
            opacity: 0.8;
        }

        .stat-card:hover {
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
            transform: translateY(-4px);
        }

        .stat-card.critical { color: #FF3B30; }
        .stat-card.today { color: #FF9500; }
        .stat-card.warning { color: #FFCC00; }
        .stat-card.success { color: #34C759; }

        .stat-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .stat-card-icon {
            font-size: 32px;
            line-height: 1;
        }

        .stat-card-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            background: currentColor;
            color: white;
            opacity: 0.9;
        }

        .stat-card-value {
            font-size: 48px;
            font-weight: 700;
            color: currentColor;
            line-height: 1;
            margin-bottom: 8px;
            letter-spacing: -0.03em;
        }

        .stat-card-label {
            font-size: 15px;
            color: #86868b;
            font-weight: 500;
            margin-bottom: 12px;
        }

        .stat-card-action {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: currentColor;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: gap 0.2s;
        }

        .stat-card-action:hover {
            gap: 10px;
        }

        /* Modal Mejorado Apple Style */
        .modal-overlay {
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            background: rgba(0, 0, 0, 0.4);
        }

        .modal-content {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 900px;
            animation: modalSlideIn 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: scale(0.95) translateY(20px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .modal-header {
            background: linear-gradient(135deg, #f5f5f7 0%, #ffffff 100%);
            padding: 24px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: 16px 16px 0 0;
        }

        .modal-header h2 {
            font-size: 24px;
            font-weight: 700;
            color: #1d1d1f;
            letter-spacing: -0.02em;
        }

        .modal-summary-cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin: 20px 0;
            padding: 20px;
            background: white;
            border-radius: 12px;
        }

        .modal-summary-card {
            text-align: center;
            padding: 16px;
            background: linear-gradient(135deg, #f5f5f7 0%, #fafafa 100%);
            border-radius: 12px;
            border: 1px solid rgba(0, 0, 0, 0.06);
        }

        .modal-summary-icon {
            font-size: 28px;
            margin-bottom: 8px;
        }

        .modal-summary-value {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .modal-summary-value.critical { color: #FF3B30; }
        .modal-summary-value.today { color: #FF9500; }
        .modal-summary-value.warning { color: #FFCC00; }

        .modal-summary-label {
            font-size: 13px;
            color: #86868b;
            font-weight: 500;
        }

        .client-card-enhanced {
            background: white;
            border-radius: 12px;
            padding: 18px;
            margin-bottom: 12px;
            border: 1px solid rgba(0, 0, 0, 0.06);
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .client-card-enhanced:hover {
            border-color: rgba(0, 122, 255, 0.3);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
            transform: translateX(4px);
        }

        .client-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
        }

        .client-name-enhanced {
            font-size: 17px;
            font-weight: 600;
            color: #1d1d1f;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .status-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .status-indicator.critical { background: #FF3B30; }
        .status-indicator.today { background: #FF9500; }
        .status-indicator.warning { background: #FFCC00; }

        .client-details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 10px;
            margin-bottom: 12px;
            padding: 12px;
            background: #f5f5f7;
            border-radius: 8px;
        }

        .client-detail-item {
            font-size: 13px;
            color: #1d1d1f;
        }

        .client-detail-item strong {
            color: #86868b;
            font-weight: 500;
            margin-right: 6px;
        }

        .alert-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            margin-top: 8px;
        }

        .alert-badge.critical {
            background: rgba(255, 59, 48, 0.1);
            color: #FF3B30;
        }

        .alert-badge.today {
            background: rgba(255, 149, 0, 0.1);
            color: #FF9500;
        }

        .alert-badge.warning {
            background: rgba(255, 204, 0, 0.1);
            color: #FFCC00;
        }

        .search-filter-bar {
            display: flex;
            gap: 12px;
            margin-bottom: 16px;
            padding: 16px;
            background: #f5f5f7;
            border-radius: 12px;
        }

        .search-input-enhanced {
            flex: 1;
            padding: 10px 16px;
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            font-size: 15px;
            background: white;
            transition: all 0.2s;
        }

        .search-input-enhanced:focus {
            outline: none;
            border-color: #007AFF;
            box-shadow: 0 0 0 4px rgba(0, 122, 255, 0.1);
        }

        .filter-button {
            padding: 10px 18px;
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            background: white;
            color: #007AFF;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .filter-button:hover {
            background: #007AFF;
            color: white;
            border-color: #007AFF;
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

                            <!-- Dashboard de Recordatorios Automáticos -->
                            <div class="auto-dashboard">
                                <div class="auto-dashboard-header">
                                    <div class="auto-dashboard-title">
                                        <span class="icon">🤖</span>
                                        <span>Recordatorios Automáticos</span>
                                    </div>
                                    <div class="auto-status-badge active" id="autoStatusBadge">
                                        <span class="auto-status-dot"></span>
                                        <span>ACTIVO</span>
                                    </div>
                                </div>

                                <div class="auto-info-grid">
                                    <div class="auto-info-item">
                                        <div class="auto-info-label">Última Ejecución</div>
                                        <div class="auto-info-value" id="ultimaEjecucion">Cargando...</div>
                                    </div>
                                    <div class="auto-info-item">
                                        <div class="auto-info-label">Próxima Ejecución</div>
                                        <div class="auto-info-value" id="proximaEjecucion">Mañana 09:00</div>
                                    </div>
                                    <div class="auto-info-item">
                                        <div class="auto-info-label">Días Entre Recordatorios</div>
                                        <div class="auto-info-value" id="diasMinimos">3 días</div>
                                    </div>
                                    <div class="auto-info-item">
                                        <div class="auto-info-label">Máximo por Mes</div>
                                        <div class="auto-info-value" id="maxPorMes">8 recordatorios</div>
                                    </div>
                                </div>

                                <div class="btn-group">
                                    <button class="btn btn-primary" onclick="verHistorialRecordatorios()" style="background: #007AFF; border-color: #007AFF;">
                                        📊 Ver Historial Completo
                                    </button>
                                    <button class="btn btn-secondary" onclick="configurarRecordatorios()">
                                        ⚙️ Configurar Sistema
                                    </button>
                                </div>
                            </div>

                            <!-- Cards de Estadísticas -->
                            <div class="stats-grid">
                                <div class="stat-card critical" onclick="verDetalleEstado('vencidos')">
                                    <div class="stat-card-header">
                                        <span class="stat-card-icon">🚨</span>
                                        <span class="stat-card-badge">CRÍTICO</span>
                                    </div>
                                    <div class="stat-card-value" id="statVencidos">0</div>
                                    <div class="stat-card-label">Vencidos</div>
                                    <div class="stat-card-action">
                                        Ver detalles
                                        <span>→</span>
                                    </div>
                                </div>

                                <div class="stat-card today" onclick="verDetalleEstado('vence_hoy')">
                                    <div class="stat-card-header">
                                        <span class="stat-card-icon">⏰</span>
                                        <span class="stat-card-badge">HOY</span>
                                    </div>
                                    <div class="stat-card-value" id="statVenceHoy">0</div>
                                    <div class="stat-card-label">Vence Hoy</div>
                                    <div class="stat-card-action">
                                        Ver detalles
                                        <span>→</span>
                                    </div>
                                </div>

                                <div class="stat-card warning" onclick="verDetalleEstado('por_vencer')">
                                    <div class="stat-card-header">
                                        <span class="stat-card-icon">⚠️</span>
                                        <span class="stat-card-badge">PRÓXIMOS</span>
                                    </div>
                                    <div class="stat-card-value" id="statPorVencer">0</div>
                                    <div class="stat-card-label">Por Vencer</div>
                                    <div class="stat-card-action">
                                        Ver detalles
                                        <span>→</span>
                                    </div>
                                </div>

                                <div class="stat-card success" onclick="actualizarEstadisticas()">
                                    <div class="stat-card-header">
                                        <span class="stat-card-icon">✅</span>
                                        <span class="stat-card-badge">ENVIADOS</span>
                                    </div>
                                    <div class="stat-card-value" id="statEnviados">0</div>
                                    <div class="stat-card-label">Este Mes</div>
                                    <div class="stat-card-action">
                                        Actualizar
                                        <span>↻</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Notificaciones Manuales de Vencimiento -->
                            <div class="section">
                                <div class="section-header" onclick="toggleSectionContent('section-notif-content')">
                                    <div class="section-title">
                                        <span class="section-icon">🔔</span>
                                        <span>Notificaciones Manuales</span>
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
            } else if (pageName === 'notificaciones') {
                // Cargar estadísticas de recordatorios
                setTimeout(cargarEstadisticasRecordatoriosWrapper, 100);
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
                    <div style="text-align: center; padding: 40px; color: #86868b;">
                        <div style="font-size: 64px; margin-bottom: 16px;">✅</div>
                        <div style="font-size: 17px; font-weight: 600; color: #1d1d1f; margin-bottom: 8px;">
                            Todo al día
                        </div>
                        <div style="font-size: 14px; color: #86868b;">
                            No hay clientes próximos a vencer
                        </div>
                    </div>
                `;
                return;
            }

            // Summary Cards with Apple HIG styling
            let html = `
                <div style="margin-bottom: 24px;">
                    <!-- Summary Stats -->
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 20px;">
                        ${resultado.vencidos.length > 0 ? `
                            <div class="modal-summary-card" style="background: linear-gradient(135deg, #FF3B30 0%, #D70015 100%);">
                                <div style="font-size: 32px; font-weight: 700; margin-bottom: 4px;">
                                    ${resultado.vencidos.length}
                                </div>
                                <div style="font-size: 13px; opacity: 0.9;">
                                    Vencidos
                                </div>
                            </div>
                        ` : ''}
                        ${resultado.vence_hoy.length > 0 ? `
                            <div class="modal-summary-card" style="background: linear-gradient(135deg, #FF9500 0%, #FF6D00 100%);">
                                <div style="font-size: 32px; font-weight: 700; margin-bottom: 4px;">
                                    ${resultado.vence_hoy.length}
                                </div>
                                <div style="font-size: 13px; opacity: 0.9;">
                                    Vence hoy
                                </div>
                            </div>
                        ` : ''}
                        ${resultado.por_vencer.length > 0 ? `
                            <div class="modal-summary-card" style="background: linear-gradient(135deg, #FFCC00 0%, #FF9500 100%);">
                                <div style="font-size: 32px; font-weight: 700; margin-bottom: 4px;">
                                    ${resultado.por_vencer.length}
                                </div>
                                <div style="font-size: 13px; opacity: 0.9;">
                                    Por vencer
                                </div>
                            </div>
                        ` : ''}
                    </div>

                    <!-- Search Bar -->
                    <div class="search-filter-bar">
                        <input type="text"
                               id="searchClientesNotif"
                               placeholder="Buscar por razón social o RUC..."
                               onkeyup="filtrarClientesNotif()"
                               style="width: 100%; padding: 12px 16px; border: 1px solid #d2d2d7; border-radius: 10px;
                                      font-size: 15px; transition: all 0.2s; background: #fff;">
                    </div>
                </div>
            `;

            // Enhanced Client Cards
            const todosClientes = [
                ...resultado.vencidos.map(c => ({...c, tipo: 'vencido', prioridad: 1})),
                ...resultado.vence_hoy.map(c => ({...c, tipo: 'hoy', prioridad: 2})),
                ...resultado.por_vencer.map(c => ({...c, tipo: 'por_vencer', prioridad: 3}))
            ];

            todosClientes.forEach((cliente) => {
                const diasInfo = cliente.tipo === 'vencido'
                    ? `${Math.abs(cliente.dias_restantes)} día${Math.abs(cliente.dias_restantes) !== 1 ? 's' : ''} de atraso`
                    : cliente.tipo === 'hoy'
                    ? 'ÚLTIMO DÍA'
                    : `${cliente.dias_restantes} día${cliente.dias_restantes !== 1 ? 's' : ''} restantes`;

                const colorConfig = {
                    'vencido': { bg: '#FFF5F5', border: '#FF3B30', text: '#FF3B30', badge: '#FF3B30' },
                    'hoy': { bg: '#FFF4E6', border: '#FF9500', text: '#FF9500', badge: '#FF9500' },
                    'por_vencer': { bg: '#FFFAE6', border: '#FFCC00', text: '#FF9500', badge: '#FFCC00' }
                };

                const config = colorConfig[cliente.tipo];

                html += `
                    <div class="client-card-enhanced" data-cliente="${cliente.razon_social.toLowerCase()} ${cliente.ruc}"
                         style="border-left: 4px solid ${config.border}; margin-bottom: 12px;">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 12px;">
                            <div>
                                <div style="font-size: 17px; font-weight: 600; color: #1d1d1f; margin-bottom: 4px;">
                                    ${cliente.razon_social}
                                </div>
                                <div style="font-size: 13px; color: #86868b;">
                                    RUC: ${cliente.ruc}
                                </div>
                            </div>
                            <span style="background: ${config.badge}; color: white; padding: 4px 12px;
                                       border-radius: 12px; font-size: 11px; font-weight: 600; text-transform: uppercase;">
                                ${cliente.tipo === 'vencido' ? 'Vencido' : cliente.tipo === 'hoy' ? 'Hoy' : 'Próximo'}
                            </span>
                        </div>

                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; padding-top: 12px;
                                    border-top: 1px solid #f5f5f7;">
                            <div>
                                <div style="font-size: 11px; color: #86868b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">
                                    WhatsApp
                                </div>
                                <div style="font-size: 15px; color: #1d1d1f; font-weight: 500;">
                                    ${cliente.whatsapp ? (cliente.whatsapp.startsWith('51') ? '+' + cliente.whatsapp : '+51' + cliente.whatsapp) : 'No registrado'}
                                </div>
                            </div>
                            <div>
                                <div style="font-size: 11px; color: #86868b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">
                                    Monto
                                </div>
                                <div style="font-size: 15px; color: #1d1d1f; font-weight: 500;">
                                    S/ ${cliente.monto}
                                </div>
                            </div>
                        </div>

                        <div style="margin-top: 12px; padding: 8px 12px; background: ${config.bg}; border-radius: 8px;
                                    font-size: 13px; color: ${config.text}; font-weight: 600; text-align: center;">
                            ${diasInfo}
                        </div>
                    </div>
                `;
            });

            container.innerHTML = html;

            // Add focus state to search input
            const searchInput = document.getElementById('searchClientesNotif');
            if (searchInput) {
                searchInput.addEventListener('focus', function() {
                    this.style.borderColor = '#007AFF';
                    this.style.boxShadow = '0 0 0 4px rgba(0, 122, 255, 0.1)';
                });
                searchInput.addEventListener('blur', function() {
                    this.style.borderColor = '#d2d2d7';
                    this.style.boxShadow = 'none';
                });
            }
        }

        function filtrarClientesNotif() {
            const searchValue = document.getElementById('searchClientesNotif').value.toLowerCase();
            const cards = document.querySelectorAll('.client-card-enhanced');

            cards.forEach(card => {
                const clienteData = card.getAttribute('data-cliente');
                if (clienteData.includes(searchValue)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
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
                                <button onclick="ServiciosUI.editarServicio(${servicio.contrato_id})"
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

        function eliminarServicioUsuario(servicioId) {
            if (!confirm('¿Estás seguro de eliminar este servicio?')) {
                return;
            }
            alert('Funcionalidad de eliminar servicio en desarrollo - ID: ' + servicioId);
            // TODO: Implementar eliminación de servicio
        }

        // ============================================
        // DASHBOARD DE RECORDATORIOS AUTOMÁTICOS
        // ============================================

        async function cargarEstadisticasRecordatorios() {
            try {
                // Cargar estadísticas
                const response = await fetch('/api/clientes.php?action=estadisticas_recordatorios');
                const data = await response.json();

                if (data.success && data.data) {
                    const stats = data.data;

                    // Actualizar tarjetas de estadísticas (IDs correctos en camelCase)
                    const statVencidos = document.getElementById('statVencidos');
                    const statVenceHoy = document.getElementById('statVenceHoy');
                    const statPorVencer = document.getElementById('statPorVencer');
                    const statEnviados = document.getElementById('statEnviados');

                    if (statVencidos) statVencidos.textContent = stats.vencidos || 0;
                    if (statVenceHoy) statVenceHoy.textContent = stats.vence_hoy || 0;
                    if (statPorVencer) statPorVencer.textContent = stats.por_vencer || 0;
                    if (statEnviados) statEnviados.textContent = stats.enviados_hoy || 0;

                    // Actualizar estado del sistema
                    const autoStatusBadge = document.getElementById('autoStatusBadge');
                    if (autoStatusBadge) {
                        const statusText = autoStatusBadge.querySelector('span:last-child');
                        if (statusText) {
                            if (stats.sistema_activo) {
                                statusText.textContent = 'ACTIVO';
                                autoStatusBadge.className = 'auto-status-badge active';
                            } else {
                                statusText.textContent = 'PAUSADO';
                                autoStatusBadge.className = 'auto-status-badge paused';
                            }
                        }
                    }
                }

                // Cargar detalles del sistema
                const detalleResponse = await fetch('/api/clientes.php?action=detalle_estado_recordatorios');
                const detalleData = await detalleResponse.json();

                if (detalleData.success && detalleData.data) {
                    const detalle = detalleData.data;

                    // Actualizar campos de información con validación
                    const ultimaEjecucion = document.getElementById('ultimaEjecucion');
                    const proximaEjecucion = document.getElementById('proximaEjecucion');
                    const diasMinimos = document.getElementById('diasMinimos');
                    const maxPorMes = document.getElementById('maxPorMes');

                    if (ultimaEjecucion) ultimaEjecucion.textContent = detalle.ultima_ejecucion || 'Sin registros';
                    if (proximaEjecucion) proximaEjecucion.textContent = detalle.proxima_ejecucion || 'No programada';
                    if (diasMinimos) diasMinimos.textContent = (detalle.dias_minimos || '3') + ' días';
                    if (maxPorMes) maxPorMes.textContent = (detalle.max_por_mes || '8') + ' recordatorios';
                }
            } catch (error) {
                console.error('Error al cargar estadísticas:', error);
                // Poner valores por defecto en caso de error con validación
                const ultimaEjecucion = document.getElementById('ultimaEjecucion');
                const proximaEjecucion = document.getElementById('proximaEjecucion');
                if (ultimaEjecucion) ultimaEjecucion.textContent = 'Error al cargar';
                if (proximaEjecucion) proximaEjecucion.textContent = 'Error al cargar';
            }
        }

        async function verHistorialRecordatorios() {
            try {
                const response = await fetch('/api/clientes.php?action=historial_recordatorios&limit=50');
                const data = await response.json();

                if (data.success && data.data) {
                    const historial = data.data;

                    let html = `
                        <div class="modal-overlay" onclick="cerrarModal(event)">
                            <div class="modal-content" style="max-width: 900px; max-height: 80vh; overflow-y: auto;">
                                <div class="modal-header">
                                    <h3>📊 Historial de Recordatorios</h3>
                                    <button class="modal-close-btn" onclick="cerrarModal(event)">✕</button>
                                </div>
                                <div class="modal-body">
                    `;

                    if (historial.length === 0) {
                        html += '<p style="text-align: center; color: #666; padding: 40px;">No hay recordatorios en el historial</p>';
                    } else {
                        html += `
                            <table style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr style="background: #f8f9fa; border-bottom: 2px solid #dee2e6;">
                                        <th style="padding: 12px; text-align: left;">Cliente</th>
                                        <th style="padding: 12px; text-align: center;">Tipo</th>
                                        <th style="padding: 12px; text-align: center;">Estado</th>
                                        <th style="padding: 12px; text-align: center;">Fecha</th>
                                        <th style="padding: 12px; text-align: center;">Automático</th>
                                    </tr>
                                </thead>
                                <tbody>
                        `;

                        historial.forEach(item => {
                            const estadoColor = item.estado_envio === 'enviado' ? '#34C759' : '#FF3B30';
                            const estadoTexto = item.estado_envio === 'enviado' ? 'Enviado' : 'Error';
                            const tipoTexto = item.tipo_recordatorio.charAt(0).toUpperCase() + item.tipo_recordatorio.slice(1);
                            const fecha = new Date(item.fecha_envio).toLocaleDateString('es-PE');

                            html += `
                                <tr style="border-bottom: 1px solid #f0f0f0;">
                                    <td style="padding: 12px;">${item.razon_social}</td>
                                    <td style="padding: 12px; text-align: center;">
                                        <span style="background: #007AFF; color: white; padding: 4px 12px; border-radius: 12px; font-size: 12px;">
                                            ${tipoTexto}
                                        </span>
                                    </td>
                                    <td style="padding: 12px; text-align: center;">
                                        <span style="background: ${estadoColor}; color: white; padding: 4px 12px; border-radius: 12px; font-size: 12px;">
                                            ${estadoTexto}
                                        </span>
                                    </td>
                                    <td style="padding: 12px; text-align: center;">${fecha}</td>
                                    <td style="padding: 12px; text-align: center;">${item.fue_automatico ? '✅' : '👤'}</td>
                                </tr>
                            `;
                        });

                        html += '</tbody></table>';
                    }

                    html += `
                                </div>
                            </div>
                        </div>
                    `;

                    document.body.insertAdjacentHTML('beforeend', html);
                }
            } catch (error) {
                console.error('Error al cargar historial:', error);
                alert('Error al cargar el historial de recordatorios');
            }
        }

        async function configurarRecordatorios() {
            try {
                const response = await fetch('/api/clientes.php?action=obtener_config_recordatorios');
                const data = await response.json();

                if (data.success && data.data) {
                    const config = data.data;

                    let html = `
                        <div class="modal-overlay" onclick="cerrarModalConfig(event)">
                            <div class="modal-content" style="max-width: 700px;">
                                <div class="modal-header">
                                    <h3>⚙️ Configuración de Recordatorios</h3>
                                    <button class="modal-close-btn" onclick="cerrarModalConfig(event)">✕</button>
                                </div>
                                <div class="modal-body">
                                    <form id="formConfigRecordatorios" onsubmit="guardarConfiguracion(event)">
                    `;

                    config.forEach(item => {
                        const valor = item.valor;
                        const isBoolean = valor === 'true' || valor === 'false';

                        html += `
                            <div style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #f0f0f0;">
                                <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #1d1d1f;">
                                    ${item.descripcion}
                                </label>
                        `;

                        if (isBoolean) {
                            html += `
                                <select name="${item.clave}" class="input-field" style="width: 100%;">
                                    <option value="true" ${valor === 'true' ? 'selected' : ''}>Activado</option>
                                    <option value="false" ${valor === 'false' ? 'selected' : ''}>Desactivado</option>
                                </select>
                            `;
                        } else {
                            html += `
                                <input type="text" name="${item.clave}" value="${valor}" class="input-field" style="width: 100%;">
                            `;
                        }

                        html += '</div>';
                    });

                    html += `
                                        <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px;">
                                            <button type="button" onclick="cerrarModalConfig(event)" class="btn-secondary">
                                                Cancelar
                                            </button>
                                            <button type="submit" class="btn-primary">
                                                💾 Guardar Configuración
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    `;

                    document.body.insertAdjacentHTML('beforeend', html);
                }
            } catch (error) {
                console.error('Error al cargar configuración:', error);
                alert('Error al cargar la configuración');
            }
        }

        async function guardarConfiguracion(event) {
            event.preventDefault();

            const formData = new FormData(event.target);
            const config = {};

            for (let [key, value] of formData.entries()) {
                config[key] = value;
            }

            try {
                const response = await fetch('/api/clientes.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'actualizar_config_recordatorios',
                        config: config
                    })
                });

                const data = await response.json();

                if (data.success) {
                    // Primero recargar estadísticas, luego cerrar modal
                    await cargarEstadisticasRecordatorios();
                    cerrarModalConfig();
                    alert('✅ Configuración guardada exitosamente\n\nLos cambios se reflejarán en el dashboard.');
                } else {
                    alert('❌ Error: ' + (data.error || 'No se pudo guardar la configuración'));
                }
            } catch (error) {
                console.error('Error al guardar configuración:', error);
                alert('❌ Error al guardar la configuración');
            }
        }

        function cerrarModalConfig(event) {
            if (event && event.target !== event.currentTarget && event.target.className !== 'modal-close-btn') {
                return;
            }
            const modal = document.querySelector('.modal-overlay');
            if (modal) {
                modal.remove();
            }
        }

        function cerrarModal(event) {
            if (event && event.target !== event.currentTarget && event.target.className !== 'modal-close-btn') {
                return;
            }
            const modal = document.querySelector('.modal-overlay');
            if (modal) {
                modal.remove();
            }
        }

        async function verDetalleEstado() {
            try {
                const response = await fetch('/api/clientes.php?action=detalle_estado_recordatorios');
                const data = await response.json();

                if (data.success && data.data) {
                    const detalle = data.data;

                    let html = `
                        <div class="modal-overlay" onclick="cerrarModal(event)">
                            <div class="modal-content" style="max-width: 600px;">
                                <div class="modal-header">
                                    <h3>🔍 Detalle del Estado</h3>
                                    <button class="modal-close-btn" onclick="cerrarModal(event)">✕</button>
                                </div>
                                <div class="modal-body">
                                    <div style="background: #f8f9fa; padding: 20px; border-radius: 12px; margin-bottom: 20px;">
                                        <h4 style="margin: 0 0 16px 0; color: #1d1d1f;">Estado del Sistema</h4>
                                        <div style="display: grid; gap: 12px;">
                                            <div style="display: flex; justify-content: space-between;">
                                                <span style="color: #666;">Estado:</span>
                                                <strong style="color: ${detalle.sistema_activo ? '#34C759' : '#FF9500'};">
                                                    ${detalle.sistema_activo ? 'Activo ✅' : 'Pausado ⏸️'}
                                                </strong>
                                            </div>
                                            <div style="display: flex; justify-content: space-between;">
                                                <span style="color: #666;">Última ejecución:</span>
                                                <strong>${detalle.ultima_ejecucion || 'Sin registros'}</strong>
                                            </div>
                                            <div style="display: flex; justify-content: space-between;">
                                                <span style="color: #666;">Próxima ejecución:</span>
                                                <strong>${detalle.proxima_ejecucion || 'No programada'}</strong>
                                            </div>
                                            <div style="display: flex; justify-content: space-between;">
                                                <span style="color: #666;">Hora configurada:</span>
                                                <strong>${detalle.hora_envio || '09:00'}</strong>
                                            </div>
                                        </div>
                                    </div>

                                    <div style="background: #fff; padding: 20px; border: 1px solid #e0e0e0; border-radius: 12px;">
                                        <h4 style="margin: 0 0 16px 0; color: #1d1d1f;">Límites Configurados</h4>
                                        <div style="display: grid; gap: 12px;">
                                            <div style="display: flex; justify-content: space-between;">
                                                <span style="color: #666;">Días mínimos entre recordatorios:</span>
                                                <strong>${detalle.dias_minimos || 3} días</strong>
                                            </div>
                                            <div style="display: flex; justify-content: space-between;">
                                                <span style="color: #666;">Máximo recordatorios por mes:</span>
                                                <strong>${detalle.max_por_mes || 8} recordatorios</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;

                    document.body.insertAdjacentHTML('beforeend', html);
                }
            } catch (error) {
                console.error('Error al cargar detalle:', error);
                alert('Error al cargar el detalle del estado');
            }
        }

        async function actualizarEstadisticas() {
            const btn = event.target;
            const originalText = btn.textContent;
            btn.textContent = '🔄 Actualizando...';
            btn.disabled = true;

            await cargarEstadisticasRecordatorios();

            setTimeout(() => {
                btn.textContent = originalText;
                btn.disabled = false;
            }, 1000);
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

        // Cargar estadísticas de recordatorios cuando se muestra la pestaña
        function cargarEstadisticasRecordatoriosWrapper() {
            console.log('🔄 Intentando cargar estadísticas de recordatorios...');

            // Verificar si la pestaña de notificaciones está visible
            const notificacionesPage = document.getElementById('page-notificaciones');
            if (notificacionesPage && notificacionesPage.classList.contains('active')) {
                console.log('✅ Página de notificaciones activa, cargando estadísticas...');
                cargarEstadisticasRecordatorios();
            } else {
                console.log('⏭️ Página de notificaciones no está activa aún');
            }
        }

        // Función global para que se pueda llamar desde el onclick del tab
        window.cargarEstadisticasRecordatoriosWrapper = cargarEstadisticasRecordatoriosWrapper;
    </script>
</body>

</html>