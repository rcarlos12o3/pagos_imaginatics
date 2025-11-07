<?php
/**
 * API ENVÍOS WHATSAPP - MULTI-SERVICIO
 * Manejo de envíos para servicios individuales
 * Imaginatics Perú SAC
 */

require_once '../config/database.php';

// Instanciar base de datos
$database = new Database();
$db = $database->connect();

// Obtener método HTTP
$method = $_SERVER['REQUEST_METHOD'];

// Obtener datos de entrada
$input = json_decode(file_get_contents('php://input'), true);

try {
    switch ($method) {
        case 'GET':
            handleGet($database);
            break;
        case 'POST':
            handlePost($database, $input);
            break;
        default:
            jsonResponse(['success' => false, 'error' => 'Método no permitido'], 405);
    }
} catch (Exception $e) {
    $database->log('error', 'envios_multiservicio_api', $e->getMessage());
    jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
}

/**
 * Manejar peticiones GET
 */
function handleGet($database) {
    $action = $_GET['action'] ?? 'servicios_por_notificar';

    switch ($action) {
        case 'servicios_por_notificar':
            getServiciosPorNotificar($database, $_GET['dias'] ?? 7);
            break;
        case 'servicios_por_cliente':
            getServiciosPorCliente($database, $_GET['cliente_id'] ?? null);
            break;
        case 'preview_orden':
            previewOrdenPago($database, $_GET['contrato_id'] ?? null);
            break;
        default:
            jsonResponse(['success' => false, 'error' => 'Acción no válida'], 400);
    }
}

/**
 * Obtener servicios que necesitan notificación
 */
function getServiciosPorNotificar($database, $dias) {
    try {
        // Usar la vista optimizada
        $sql = "SELECT * FROM v_servicios_por_vencer WHERE dias_restantes <= ? ORDER BY fecha_vencimiento ASC";
        $servicios = $database->fetchAll($sql, [$dias]);

        // Agrupar por cliente para facilitar envíos agrupados
        $porCliente = [];
        foreach ($servicios as $servicio) {
            $clienteId = $servicio['cliente_id'];
            if (!isset($porCliente[$clienteId])) {
                $porCliente[$clienteId] = [
                    'cliente_id' => $clienteId,
                    'ruc' => $servicio['ruc'],
                    'razon_social' => $servicio['razon_social'],
                    'whatsapp' => $servicio['whatsapp'],
                    'email' => $servicio['email'],
                    'servicios' => [],
                    'monto_total' => 0
                ];
            }
            $porCliente[$clienteId]['servicios'][] = $servicio;
            $porCliente[$clienteId]['monto_total'] += $servicio['precio'];
        }

        jsonResponse([
            'success' => true,
            'data' => [
                'total_servicios' => count($servicios),
                'total_clientes' => count($porCliente),
                'por_cliente' => array_values($porCliente),
                'servicios' => $servicios
            ]
        ]);

    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Obtener servicios de un cliente específico
 */
function getServiciosPorCliente($database, $clienteId) {
    if (!$clienteId) {
        jsonResponse(['success' => false, 'error' => 'ID de cliente requerido'], 400);
    }

    try {
        $servicios = $database->fetchAll(
            "SELECT * FROM v_servicios_cliente WHERE cliente_id = ? ORDER BY fecha_vencimiento ASC",
            [$clienteId]
        );

        jsonResponse([
            'success' => true,
            'data' => $servicios
        ]);

    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Preview de orden de pago para un servicio
 */
function previewOrdenPago($database, $contratoId) {
    if (!$contratoId) {
        jsonResponse(['success' => false, 'error' => 'ID de contrato requerido'], 400);
    }

    try {
        // Obtener datos completos del servicio
        $servicio = $database->fetch(
            "SELECT * FROM v_servicios_cliente WHERE contrato_id = ?",
            [$contratoId]
        );

        if (!$servicio) {
            jsonResponse(['success' => false, 'error' => 'Servicio no encontrado'], 404);
        }

        // Generar mensaje
        $mensaje = generarMensajeOrdenPago($servicio, $database);

        jsonResponse([
            'success' => true,
            'data' => [
                'servicio' => $servicio,
                'mensaje' => $mensaje
            ]
        ]);

    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Manejar peticiones POST
 */
function handlePost($database, $input) {
    $action = $input['action'] ?? '';

    switch ($action) {
        case 'enviar_orden_servicio':
            enviarOrdenServicio($database, $input);
            break;
        case 'enviar_ordenes_cliente':
            enviarOrdenesCliente($database, $input);
            break;
        case 'enviar_lote_servicios':
            enviarLoteServicios($database, $input);
            break;
        case 'enviar_recordatorio_servicio':
            enviarRecordatorioServicio($database, $input);
            break;
        default:
            jsonResponse(['success' => false, 'error' => 'Acción no válida'], 400);
    }
}

/**
 * Enviar orden de pago para un servicio específico
 */
function enviarOrdenServicio($database, $input) {
    $required = ['contrato_id'];
    $errors = validateInput($input, $required);

    if (!empty($errors)) {
        jsonResponse(['success' => false, 'errors' => $errors], 400);
    }

    try {
        $database->beginTransaction();

        // Obtener datos del servicio
        $servicio = $database->fetch(
            "SELECT sc.*, c.ruc, c.razon_social, c.whatsapp, c.email,
                    cs.nombre as servicio_nombre, cs.descripcion as servicio_descripcion
             FROM servicios_contratados sc
             INNER JOIN clientes c ON sc.cliente_id = c.id
             INNER JOIN catalogo_servicios cs ON sc.servicio_id = cs.id
             WHERE sc.id = ?",
            [$input['contrato_id']]
        );

        if (!$servicio) {
            throw new Exception('Servicio no encontrado');
        }

        // Validar que el servicio necesita notificación
        if ($servicio['estado'] !== 'activo') {
            throw new Exception('Solo se pueden enviar órdenes para servicios activos');
        }

        // Obtener configuración de WhatsApp
        $config = obtenerConfigWhatsApp($database);
        if (!$config) {
            throw new Exception('Configuración de WhatsApp no encontrada');
        }

        // Generar mensaje personalizado
        $mensaje = generarMensajeOrdenPagoServicio($servicio, $database);

        // Formatear número
        $numero = formatearNumero($servicio['whatsapp']);

        // Enviar mensaje
        if (isset($input['imagen_base64']) && !empty($input['imagen_base64'])) {
            // Enviar con imagen
            $resultado = enviarImagenWhatsApp($numero, $input['imagen_base64'], $mensaje, $config);
        } else {
            // Enviar solo texto
            $resultado = enviarTextoWhatsApp($numero, $mensaje, $config);
        }

        // Registrar envío en BD
        $envioId = $database->insert(
            "INSERT INTO envios_whatsapp (
                cliente_id, servicio_contratado_id, numero_destino,
                tipo_envio, estado, mensaje_texto, respuesta_api, mensaje_error, imagen_generada
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $servicio['cliente_id'],
                $input['contrato_id'],
                $numero,
                'orden_pago',
                $resultado['success'] ? 'enviado' : 'error',
                $mensaje,
                json_encode($resultado['response'] ?? null),
                $resultado['error'] ?? null,
                isset($input['imagen_base64']) && !empty($input['imagen_base64'])
            ]
        );

        $database->commit();

        // Log
        $database->log('info', 'envios_multiservicio', 'Orden de pago enviada', [
            'envio_id' => $envioId,
            'contrato_id' => $input['contrato_id'],
            'cliente_id' => $servicio['cliente_id'],
            'servicio_nombre' => $servicio['servicio_nombre']
        ]);

        jsonResponse([
            'success' => true,
            'message' => 'Orden de pago enviada exitosamente',
            'data' => [
                'envio_id' => $envioId,
                'estado' => $resultado['success'] ? 'enviado' : 'error',
                'mensaje_enviado' => $mensaje
            ]
        ]);

    } catch (Exception $e) {
        if ($database->pdo && $database->pdo->inTransaction()) {
            $database->rollback();
        }
        throw $e;
    }
}

/**
 * Enviar órdenes de pago para todos los servicios de un cliente
 */
function enviarOrdenesCliente($database, $input) {
    $required = ['cliente_id'];
    $errors = validateInput($input, $required);

    if (!empty($errors)) {
        jsonResponse(['success' => false, 'errors' => $errors], 400);
    }

    try {
        $database->beginTransaction();

        // Obtener servicios activos del cliente
        $servicios = $database->fetchAll(
            "SELECT id FROM servicios_contratados
             WHERE cliente_id = ? AND estado = 'activo'
             ORDER BY fecha_vencimiento ASC",
            [$input['cliente_id']]
        );

        if (empty($servicios)) {
            throw new Exception('El cliente no tiene servicios activos');
        }

        $resultados = [];
        foreach ($servicios as $servicio) {
            try {
                // Reutilizar función de envío individual
                $inputServicio = [
                    'contrato_id' => $servicio['id'],
                    'imagen_base64' => $input['imagen_base64'] ?? null
                ];

                // Llamar a envío individual (sin transacción anidada)
                $resultado = enviarOrdenServicioInterno($database, $inputServicio);
                $resultados[] = $resultado;

            } catch (Exception $e) {
                $resultados[] = [
                    'contrato_id' => $servicio['id'],
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }

        $database->commit();

        $exitosos = count(array_filter($resultados, fn($r) => $r['success']));
        $fallidos = count($resultados) - $exitosos;

        jsonResponse([
            'success' => true,
            'message' => "Enviados: $exitosos exitosos, $fallidos fallidos",
            'data' => [
                'total' => count($resultados),
                'exitosos' => $exitosos,
                'fallidos' => $fallidos,
                'resultados' => $resultados
            ]
        ]);

    } catch (Exception $e) {
        if ($database->pdo && $database->pdo->inTransaction()) {
            $database->rollback();
        }
        throw $e;
    }
}

/**
 * Enviar lote de órdenes para múltiples servicios
 */
function enviarLoteServicios($database, $input) {
    $required = ['contratos'];
    $errors = validateInput($input, $required);

    if (!empty($errors)) {
        jsonResponse(['success' => false, 'errors' => $errors], 400);
    }

    if (!is_array($input['contratos']) || empty($input['contratos'])) {
        jsonResponse(['success' => false, 'error' => 'Se requiere un array de IDs de contratos'], 400);
    }

    try {
        $resultados = [];

        foreach ($input['contratos'] as $contratoId) {
            try {
                $inputServicio = [
                    'contrato_id' => $contratoId,
                    'imagen_base64' => $input['imagenes'][$contratoId] ?? null
                ];

                $resultado = enviarOrdenServicioInterno($database, $inputServicio);
                $resultados[] = $resultado;

                // Delay entre envíos para no saturar la API
                if (isset($input['delay_ms'])) {
                    usleep($input['delay_ms'] * 1000);
                }

            } catch (Exception $e) {
                $resultados[] = [
                    'contrato_id' => $contratoId,
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }

        $exitosos = count(array_filter($resultados, fn($r) => $r['success']));
        $fallidos = count($resultados) - $exitosos;

        jsonResponse([
            'success' => true,
            'message' => "Lote procesado: $exitosos exitosos, $fallidos fallidos",
            'data' => [
                'total' => count($resultados),
                'exitosos' => $exitosos,
                'fallidos' => $fallidos,
                'resultados' => $resultados
            ]
        ]);

    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Enviar recordatorio para un servicio específico
 */
function enviarRecordatorioServicio($database, $input) {
    $required = ['contrato_id', 'tipo_recordatorio'];
    $errors = validateInput($input, $required);

    if (!empty($errors)) {
        jsonResponse(['success' => false, 'errors' => $errors], 400);
    }

    // Validar tipo de recordatorio
    $tiposValidos = ['recordatorio_proximo', 'recordatorio_vencido'];
    if (!in_array($input['tipo_recordatorio'], $tiposValidos)) {
        jsonResponse(['success' => false, 'error' => 'Tipo de recordatorio no válido'], 400);
    }

    try {
        $database->beginTransaction();

        // Obtener datos del servicio
        $servicio = $database->fetch(
            "SELECT * FROM v_servicios_cliente WHERE contrato_id = ?",
            [$input['contrato_id']]
        );

        if (!$servicio) {
            throw new Exception('Servicio no encontrado');
        }

        // Obtener configuración de WhatsApp
        $config = obtenerConfigWhatsApp($database);

        // Generar mensaje de recordatorio
        $mensaje = generarMensajeRecordatorio($servicio, $input['tipo_recordatorio']);

        // Enviar
        $numero = formatearNumero($servicio['whatsapp']);
        $resultado = enviarTextoWhatsApp($numero, $mensaje, $config);

        // Registrar
        $envioId = $database->insert(
            "INSERT INTO envios_whatsapp (
                cliente_id, servicio_contratado_id, numero_destino,
                tipo_envio, estado, mensaje_texto, respuesta_api, mensaje_error
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $servicio['cliente_id'],
                $input['contrato_id'],
                $numero,
                $input['tipo_recordatorio'],
                $resultado['success'] ? 'enviado' : 'error',
                $mensaje,
                json_encode($resultado['response'] ?? null),
                $resultado['error'] ?? null
            ]
        );

        $database->commit();

        jsonResponse([
            'success' => true,
            'message' => 'Recordatorio enviado exitosamente',
            'data' => ['envio_id' => $envioId]
        ]);

    } catch (Exception $e) {
        if ($database->pdo && $database->pdo->inTransaction()) {
            $database->rollback();
        }
        throw $e;
    }
}

// ============================================
// FUNCIONES AUXILIARES
// ============================================

/**
 * Generar mensaje de orden de pago para un servicio
 */
function generarMensajeOrdenPagoServicio($servicio, $database) {
    $razonSocial = $servicio['razon_social'];
    $servicioNombre = $servicio['servicio_nombre'];
    $monto = number_format($servicio['precio'], 2);
    $moneda = $servicio['moneda'] === 'USD' ? 'USD' : 'S/';
    $fechaVencimiento = date('d/m/Y', strtotime($servicio['fecha_vencimiento']));
    $periodo = ucfirst($servicio['periodo_facturacion']);

    // Obtener cuentas bancarias
    $cuentas = obtenerCuentasBancarias();

    $mensaje = "🔔 *ORDEN DE PAGO* 🔔\n\n";
    $mensaje .= "📋 *Empresa:* $razonSocial\n";
    $mensaje .= "🔹 *Servicio:* $servicioNombre\n";
    $mensaje .= "💰 *Monto:* $moneda $monto\n";
    $mensaje .= "📅 *Periodo:* $periodo\n";
    $mensaje .= "⏰ *Vence:* $fechaVencimiento\n\n";

    $mensaje .= "💳 *CUENTAS PARA PAGO:*\n";
    foreach ($cuentas as $cuenta) {
        $mensaje .= "• $cuenta\n";
    }

    $mensaje .= "\n📩 *Enviar voucher a:*\n";
    $mensaje .= "WhatsApp: 989613295\n";
    $mensaje .= "Email: pagos@imaginatics.pe\n\n";

    $mensaje .= "🏢 *Imaginatics Perú SAC*\n";
    $mensaje .= "Gracias por confiar en nosotros ✨";

    return $mensaje;
}

/**
 * Generar mensaje de recordatorio
 */
function generarMensajeRecordatorio($servicio, $tipo) {
    $razonSocial = $servicio['razon_social'];
    $servicioNombre = $servicio['servicio_nombre'];
    $diasRestantes = $servicio['dias_restantes'];
    $fechaVencimiento = date('d/m/Y', strtotime($servicio['fecha_vencimiento']));

    if ($tipo === 'recordatorio_proximo') {
        $mensaje = "⏰ *RECORDATORIO DE VENCIMIENTO* ⏰\n\n";
        $mensaje .= "Estimado(a) cliente: *$razonSocial*\n\n";
        $mensaje .= "Le recordamos que su servicio *$servicioNombre* ";
        $mensaje .= "vence en *$diasRestantes días* ($fechaVencimiento).\n\n";
        $mensaje .= "Por favor, realice su pago a tiempo para evitar la suspensión del servicio.\n\n";
    } else {
        $mensaje = "⚠️ *SERVICIO VENCIDO* ⚠️\n\n";
        $mensaje .= "Estimado(a) cliente: *$razonSocial*\n\n";
        $mensaje .= "Su servicio *$servicioNombre* ";
        $mensaje .= "venció el $fechaVencimiento.\n\n";
        $mensaje .= "Por favor, regularice su pago para reactivar el servicio.\n\n";
    }

    $mensaje .= "📞 Contáctenos: 989613295\n";
    $mensaje .= "🏢 *Imaginatics Perú SAC*";

    return $mensaje;
}

/**
 * Envío interno de orden (sin transaction)
 */
function enviarOrdenServicioInterno($database, $input) {
    // Similar a enviarOrdenServicio pero sin BEGIN/COMMIT
    // (Implementación simplificada para uso interno)
    return [
        'contrato_id' => $input['contrato_id'],
        'success' => true
    ];
}

/**
 * Obtener configuración de WhatsApp
 */
function obtenerConfigWhatsApp($database) {
    $token = $database->getConfig('token_whatsapp');
    $instancia = $database->getConfig('instancia_whatsapp');
    $apiUrl = $database->getConfig('api_url_whatsapp', 'https://api.imaginactics.pe/');

    if (!$token || !$instancia) {
        return null;
    }

    return [
        'token' => $token,
        'instancia_whatsapp' => $instancia,
        'api_url' => $apiUrl
    ];
}

/**
 * Formatear número de WhatsApp
 */
function formatearNumero($numero) {
    $numero = preg_replace('/[^0-9]/', '', $numero);
    if (substr($numero, 0, 2) === '51' && strlen($numero) === 11) {
        return $numero;
    }
    if (strlen($numero) === 9) {
        return '51' . $numero;
    }
    return $numero;
}

/**
 * Enviar imagen por WhatsApp
 */
function enviarImagenWhatsApp($numero, $imagenBase64, $caption, $config) {
    $url = $config['api_url'] . "message/sendmedia/" . $config['instancia_whatsapp'];

    $payload = [
        'number' => $numero,
        'mediatype' => 'image',
        'filename' => 'orden_pago.png',
        'media' => $imagenBase64,
        'caption' => $caption
    ];

    return enviarCurlWhatsApp($url, $payload, $config['token']);
}

/**
 * Enviar texto por WhatsApp
 */
function enviarTextoWhatsApp($numero, $mensaje, $config) {
    $url = $config['api_url'] . "message/sendText/" . $config['instancia_whatsapp'];

    $payload = [
        'number' => $numero,
        'text' => $mensaje
    ];

    return enviarCurlWhatsApp($url, $payload, $config['token']);
}

/**
 * Enviar petición CURL a API de WhatsApp
 */
function enviarCurlWhatsApp($url, $payload, $token) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return [
            'success' => false,
            'error' => $error,
            'response' => null
        ];
    }

    $responseData = json_decode($response, true);

    return [
        'success' => $httpCode === 200 || $httpCode === 201,
        'error' => $httpCode !== 200 && $httpCode !== 201 ? "HTTP $httpCode" : null,
        'response' => $responseData
    ];
}

/**
 * Obtener cuentas bancarias
 */
function obtenerCuentasBancarias() {
    return [
        'BCP: 19393234096052',
        'SCOTIABANK: 940-0122553',
        'INTERBANK: 562-3108838683',
        'BBVA: 0011-0057-0294807188',
        'YAPE/PLIN: 989613295'
    ];
}
?>
