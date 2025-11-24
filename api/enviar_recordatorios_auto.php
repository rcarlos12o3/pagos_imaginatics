#!/usr/bin/env php
<?php
/**
 * Script de Envío Automático de Recordatorios
 *
 * Este script se ejecuta mediante cron job para enviar recordatorios
 * automáticos de pago a clientes con vencimientos próximos o vencidos.
 *
 * Configuración del cron:
 * 0 9 * * * /usr/bin/php /path/to/api/enviar_recordatorios_auto.php >> /path/to/logs/recordatorios_auto.log 2>&1
 *
 * @author Imaginatics Peru SAC
 * @version 1.0.0
 */

// Asegurarse de que el script se ejecute desde la línea de comandos
if (php_sapi_name() !== 'cli') {
    die("Este script solo puede ejecutarse desde la línea de comandos\n");
}

// Configurar zona horaria
date_default_timezone_set('America/Lima');

// Cargar configuración y base de datos
require_once __DIR__ . '/../config/database.php';

// Configuración de logging
$logFile = __DIR__ . '/../logs/recordatorios_auto.log';
$logDir = dirname($logFile);

// Crear directorio de logs si no existe
if (!file_exists($logDir)) {
    mkdir($logDir, 0755, true);
}

// Función para logging
function logMessage($nivel, $mensaje, $datos = null) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] [$nivel] $mensaje";

    if ($datos !== null) {
        $logEntry .= " | " . json_encode($datos, JSON_UNESCAPED_UNICODE);
    }

    $logEntry .= "\n";

    file_put_contents($logFile, $logEntry, FILE_APPEND);
    echo $logEntry; // También mostrar en consola
}

try {
    logMessage('INFO', '========================================');
    logMessage('INFO', 'Iniciando proceso de recordatorios automáticos');
    logMessage('INFO', '========================================');

    // Conectar a base de datos
    $database = new Database();
    $db = $database->connect();

    if (!$db) {
        throw new Exception('No se pudo conectar a la base de datos');
    }

    logMessage('INFO', '✅ Conexión a base de datos establecida');

    // 1. Verificar si los recordatorios automáticos están activos
    $config = cargarConfiguracionRecordatorios($database);

    if (!$config['recordatorios_automaticos_activos']) {
        logMessage('INFO', '⏸️  Recordatorios automáticos desactivados - finalizando');
        exit(0);
    }

    logMessage('INFO', '✅ Recordatorios automáticos activos', $config);

    // 2. Obtener clientes que necesitan recordatorios
    $clientesPendientes = obtenerClientesPendientesRecordatorio($database, $config);

    if (empty($clientesPendientes)) {
        logMessage('INFO', '📭 No hay clientes pendientes de recordatorio');
        exit(0);
    }

    logMessage('INFO', "📋 Se encontraron " . count($clientesPendientes) . " clientes pendientes");

    // 3. Procesar cada cliente
    $exitosos = 0;
    $errores = 0;
    $omitidos = 0;

    foreach ($clientesPendientes as $cliente) {
        try {
            logMessage('INFO', "📤 Procesando: {$cliente['razon_social']} (RUC: {$cliente['ruc']})");

            // Verificar límites de frecuencia
            if (!verificarLimitesRecordatorio($database, $cliente['id'], $config)) {
                logMessage('WARNING', "⏭️  Cliente omitido por límites de frecuencia", [
                    'cliente_id' => $cliente['id'],
                    'razon_social' => $cliente['razon_social']
                ]);
                $omitidos++;
                continue;
            }

            // Determinar tipo de recordatorio
            $tipoRecordatorio = determinarTipoRecordatorio($cliente['dias_restantes']);

            // Enviar recordatorio
            $resultado = enviarRecordatorioIndividual($database, $cliente, $tipoRecordatorio, $config);

            if ($resultado['success']) {
                $exitosos++;
                logMessage('INFO', "✅ Recordatorio enviado exitosamente", [
                    'cliente_id' => $cliente['id'],
                    'tipo' => $tipoRecordatorio,
                    'dias_restantes' => $cliente['dias_restantes']
                ]);
            } else {
                $errores++;
                logMessage('ERROR', "❌ Error enviando recordatorio: {$resultado['error']}", [
                    'cliente_id' => $cliente['id'],
                    'razon_social' => $cliente['razon_social']
                ]);
            }

            // Pausa entre envíos para evitar saturar la API
            sleep(rand(5, 10));

        } catch (Exception $e) {
            $errores++;
            logMessage('ERROR', "❌ Excepción procesando cliente: " . $e->getMessage(), [
                'cliente_id' => $cliente['id'],
                'razon_social' => $cliente['razon_social'],
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    // 4. Resumen final
    logMessage('INFO', '========================================');
    logMessage('INFO', 'Proceso de recordatorios finalizado', [
        'total_procesados' => count($clientesPendientes),
        'exitosos' => $exitosos,
        'errores' => $errores,
        'omitidos' => $omitidos
    ]);
    logMessage('INFO', '========================================');

    // Registrar en logs del sistema
    $database->query(
        "INSERT INTO logs_sistema (nivel, modulo, mensaje, datos_adicionales) VALUES (?, ?, ?, ?)",
        [
            'info',
            'recordatorios_auto',
            'Ejecución de recordatorios automáticos completada',
            json_encode([
                'fecha' => date('Y-m-d H:i:s'),
                'total' => count($clientesPendientes),
                'exitosos' => $exitosos,
                'errores' => $errores,
                'omitidos' => $omitidos
            ])
        ]
    );

    exit(0);

} catch (Exception $e) {
    logMessage('CRITICAL', '💥 Error fatal: ' . $e->getMessage(), [
        'trace' => $e->getTraceAsString()
    ]);

    exit(1);
}

// ============================================
// FUNCIONES AUXILIARES
// ============================================

/**
 * Cargar configuración de recordatorios desde la base de datos
 */
function cargarConfiguracionRecordatorios($database) {
    $configs = $database->fetchAll("SELECT clave, valor, tipo_dato FROM config_recordatorios");

    $configuracion = [];
    foreach ($configs as $config) {
        $valor = $config['valor'];

        // Convertir según tipo de dato
        switch ($config['tipo_dato']) {
            case 'integer':
                $valor = (int)$valor;
                break;
            case 'boolean':
                $valor = ($valor === 'true' || $valor === '1');
                break;
            case 'json':
                $valor = json_decode($valor, true);
                break;
        }

        $configuracion[$config['clave']] = $valor;
    }

    return $configuracion;
}

/**
 * Obtener clientes que necesitan recordatorio hoy
 */
function obtenerClientesPendientesRecordatorio($database, $config) {
    // Usar la vista que ya filtra por límites de frecuencia
    $sql = "SELECT * FROM v_recordatorios_pendientes_hoy
            ORDER BY dias_restantes ASC, ultimo_recordatorio ASC
            LIMIT 100";

    return $database->fetchAll($sql);
}

/**
 * Verificar si el cliente cumple con los límites de frecuencia
 */
function verificarLimitesRecordatorio($database, $clienteId, $config) {
    // 1. Verificar días mínimos entre recordatorios
    $diasMinimos = $config['dias_minimos_entre_recordatorios'];

    $ultimoEnvio = $database->fetch(
        "SELECT MAX(fecha_envio) as ultima_fecha
         FROM historial_recordatorios
         WHERE cliente_id = ? AND estado_envio = 'enviado'",
        [$clienteId]
    );

    if ($ultimoEnvio && $ultimoEnvio['ultima_fecha']) {
        $diasDesdeUltimo = (strtotime('now') - strtotime($ultimoEnvio['ultima_fecha'])) / (60 * 60 * 24);

        if ($diasDesdeUltimo < $diasMinimos) {
            return false; // Muy pronto para enviar otro recordatorio
        }
    }

    // 2. Verificar máximo de recordatorios por mes
    $maxRecordatoriosMes = $config['max_recordatorios_mes'];

    $countMes = $database->fetch(
        "SELECT COUNT(*) as total
         FROM historial_recordatorios
         WHERE cliente_id = ?
         AND fecha_envio >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
         AND estado_envio = 'enviado'",
        [$clienteId]
    );

    if ($countMes && $countMes['total'] >= $maxRecordatoriosMes) {
        return false; // Ya se alcanzó el máximo de recordatorios del mes
    }

    return true;
}

/**
 * Determinar el tipo de recordatorio según días restantes
 */
function determinarTipoRecordatorio($diasRestantes) {
    if ($diasRestantes < 0) {
        // Ya vencido
        $diasAtraso = abs($diasRestantes);
        if ($diasAtraso >= 15) {
            return 'mora';
        } else if ($diasAtraso >= 3) {
            return 'vencido';
        } else {
            return 'vencido';
        }
    } else if ($diasRestantes == 0) {
        return 'critico'; // Vence hoy
    } else if ($diasRestantes <= 1) {
        return 'critico';
    } else if ($diasRestantes <= 3) {
        return 'urgente';
    } else {
        return 'preventivo';
    }
}

/**
 * Enviar recordatorio individual a un cliente
 */
function enviarRecordatorioIndividual($database, $cliente, $tipoRecordatorio, $config) {
    try {
        // Crear entrada en historial ANTES de enviar
        $historialId = $database->insert(
            "INSERT INTO historial_recordatorios
            (cliente_id, tipo_recordatorio, dias_antes_vencimiento, fecha_vencimiento,
             monto, estado_envio, canal, numero_destino, fue_automatico)
            VALUES (?, ?, ?, ?, ?, 'pendiente', 'whatsapp', ?, TRUE)",
            [
                $cliente['id'],
                $tipoRecordatorio,
                $cliente['dias_restantes'],
                $cliente['fecha_vencimiento'],
                $cliente['monto'],
                $cliente['whatsapp']
            ]
        );

        // Generar mensaje según tipo
        $mensaje = generarMensajeRecordatorioAuto($cliente, $tipoRecordatorio);

        // ENVÍO REAL POR WHATSAPP
        $resultadoEnvio = enviarWhatsAppReal($database, $cliente, $mensaje);
        $envioExitoso = $resultadoEnvio['success'];

        if ($envioExitoso) {
            // Actualizar estado a enviado
            $database->query(
                "UPDATE historial_recordatorios
                 SET estado_envio = 'enviado', mensaje_enviado = ?, respuesta_api = ?
                 WHERE id = ?",
                [$mensaje, json_encode($resultadoEnvio['response'] ?? []), $historialId]
            );

            return ['success' => true, 'historial_id' => $historialId];
        } else {
            // Actualizar estado a error
            $database->query(
                "UPDATE historial_recordatorios
                 SET estado_envio = 'error', error_detalle = ?
                 WHERE id = ?",
                [$resultadoEnvio['error'] ?? 'Error desconocido', $historialId]
            );

            return ['success' => false, 'error' => $resultadoEnvio['error'] ?? 'Error en envío'];
        }

    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Generar mensaje personalizado según tipo de recordatorio
 */
function generarMensajeRecordatorioAuto($cliente, $tipoRecordatorio) {
    $razonSocial = $cliente['razon_social'];
    $monto = number_format($cliente['monto'], 2);
    $moneda = $cliente['moneda'] === 'USD' ? '$' : 'S/';
    $fechaVenc = date('d/m/Y', strtotime($cliente['fecha_vencimiento']));
    $diasRestantes = $cliente['dias_restantes'];

    switch ($tipoRecordatorio) {
        case 'preventivo':
            return "📅 RECORDATORIO - {$razonSocial}

Hola, te recordamos que tienes una orden de pago próxima a vencer en {$diasRestantes} días.

💰 Monto: {$moneda} {$monto}
📅 Vencimiento: {$fechaVenc}

Mantén tus cuentas al día para evitar interrupciones del servicio.

Saludos,
Equipo de Cobranza - Imaginatics Peru SAC";

        case 'urgente':
            return "⚠️ RECORDATORIO URGENTE - {$razonSocial}

Tu orden de pago vence en {$diasRestantes} días ({$fechaVenc}).

💰 Monto: {$moneda} {$monto}

Por favor, coordina tu pago a la brevedad para evitar cortes de servicio.

Saludos,
Equipo de Cobranza - Imaginatics Peru SAC";

        case 'critico':
            if ($diasRestantes == 0) {
                return "🚨 ÚLTIMO DÍA - {$razonSocial}

Tu orden de pago VENCE HOY ({$fechaVenc}).

💰 Monto: {$moneda} {$monto}

Este es el último día para realizar tu pago y evitar suspensión del servicio.

Saludos,
Equipo de Cobranza - Imaginatics Peru SAC";
            } else {
                return "⏰ RECORDATORIO CRÍTICO - {$razonSocial}

Tu orden de pago vence mañana ({$fechaVenc}).

💰 Monto: {$moneda} {$monto}

Por favor realiza tu pago hoy para evitar cortes.

Saludos,
Equipo de Cobranza - Imaginatics Peru SAC";
            }

        case 'vencido':
            $diasAtraso = abs($diasRestantes);
            return "❗ PAGO VENCIDO - {$razonSocial}

Tu orden de pago tiene {$diasAtraso} día(s) de atraso (venció el {$fechaVenc}).

💰 Monto: {$moneda} {$monto}

Para evitar suspensión del servicio, te solicitamos regularizar tu pago a la brevedad.

Saludos,
Equipo de Cobranza - Imaginatics Peru SAC";

        case 'mora':
            $diasAtraso = abs($diasRestantes);
            return "🚨 AVISO DE MORA - {$razonSocial}

Tu orden de pago tiene {$diasAtraso} días de atraso (venció el {$fechaVenc}).

💰 Monto: {$moneda} {$monto}

URGENTE: Contacta con nosotros para evitar la suspensión inmediata de tu servicio.

Equipo de Cobranza - Imaginatics Peru SAC
WhatsApp: [NÚMERO DE CONTACTO]";

        default:
            return "Recordatorio de pago - {$razonSocial}";
    }
}

/**
 * Enviar mensaje real por WhatsApp usando la API configurada
 */
function enviarWhatsAppReal($database, $cliente, $mensaje) {
    try {
        // 1. Obtener configuración de WhatsApp
        $token = $database->fetch("SELECT valor FROM configuracion WHERE clave = 'token_whatsapp'");
        $instancia = $database->fetch("SELECT valor FROM configuracion WHERE clave = 'instancia_whatsapp'");
        $apiUrl = $database->fetch("SELECT valor FROM configuracion WHERE clave = 'api_url_whatsapp'");

        if (!$token || !$instancia || !$apiUrl) {
            return [
                'success' => false,
                'error' => 'Configuración de WhatsApp incompleta'
            ];
        }

        $config = [
            'token' => $token['valor'],
            'instancia' => $instancia['valor'],
            'api_url' => $apiUrl['valor']
        ];

        // 2. Formatear número (agregar 51 si es necesario)
        $numero = $cliente['whatsapp'];
        if (strlen($numero) === 9) {
            $numero = '51' . $numero;
        }

        // 3. Preparar URL y payload
        $url = $config['api_url'] . "message/sendtext/" . $config['instancia'];

        $payload = [
            'number' => $numero,
            'text' => $mensaje
        ];

        // 4. Enviar mediante cURL
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $config['token'],
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        // 5. Procesar respuesta
        if ($curlError) {
            return [
                'success' => false,
                'error' => 'Error cURL: ' . $curlError,
                'response' => null
            ];
        }

        $responseData = json_decode($response, true);

        if ($httpCode >= 200 && $httpCode < 300) {
            return [
                'success' => true,
                'response' => $responseData,
                'error' => null
            ];
        } else {
            return [
                'success' => false,
                'error' => 'HTTP ' . $httpCode . ': ' . ($responseData['error'] ?? 'Error desconocido'),
                'response' => $responseData
            ];
        }

    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => 'Excepción: ' . $e->getMessage(),
            'response' => null
        ];
    }
}
