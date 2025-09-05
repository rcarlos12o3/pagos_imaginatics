<?php
/**
 * API ENVÍOS WHATSAPP
 * Manejo de envíos masivos y recordatorios
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
    $database->log('error', 'envios_api', $e->getMessage());
    jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
}

/**
 * Manejar peticiones GET
 */
function handleGet($database) {
    $action = $_GET['action'] ?? 'list';

    switch ($action) {
        case 'list':
            getEnvios($database);
            break;
        case 'stats':
            getEstadisticasEnvios($database);
            break;
        case 'history':
            getHistorialCliente($database, $_GET['cliente_id'] ?? null);
            break;
        default:
            jsonResponse(['success' => false, 'error' => 'Acción no válida'], 400);
    }
}

/**
 * Obtener lista de envíos
 */
function getEnvios($database) {
    try {
        $page = $_GET['page'] ?? 1;
        $limit = $_GET['limit'] ?? 50;
        $offset = ($page - 1) * $limit;

        $sql = "SELECT e.*, c.ruc, c.razon_social, c.whatsapp
                FROM envios_whatsapp e
                INNER JOIN clientes c ON e.cliente_id = c.id
                ORDER BY e.fecha_envio DESC
                LIMIT ? OFFSET ?";

        $envios = $database->fetchAll($sql, [$limit, $offset]);

        // Contar total
        $total = $database->fetch("SELECT COUNT(*) as total FROM envios_whatsapp")['total'];

        jsonResponse([
            'success' => true,
            'data' => $envios,
            'pagination' => [
                'page' => (int)$page,
                'limit' => (int)$limit,
                'total' => (int)$total,
                'pages' => ceil($total / $limit)
            ]
        ]);

    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Obtener estadísticas de envíos
 */
function getEstadisticasEnvios($database) {
    try {
        // Estadísticas generales
        $stats = $database->fetch("
            SELECT
                COUNT(*) as total_envios,
                SUM(CASE WHEN estado = 'enviado' THEN 1 ELSE 0 END) as exitosos,
                SUM(CASE WHEN estado = 'error' THEN 1 ELSE 0 END) as fallidos,
                SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                COUNT(CASE WHEN fecha_envio >= CURDATE() THEN 1 END) as envios_hoy,
                COUNT(CASE WHEN fecha_envio >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as envios_semana
            FROM envios_whatsapp
        ");

        // Por tipo de envío
        $porTipo = $database->fetchAll("
            SELECT
                tipo_envio,
                COUNT(*) as cantidad,
                SUM(CASE WHEN estado = 'enviado' THEN 1 ELSE 0 END) as exitosos
            FROM envios_whatsapp
            GROUP BY tipo_envio
        ");

        // Envíos por día (últimos 7 días)
        $porDia = $database->fetchAll("
            SELECT
                DATE(fecha_envio) as fecha,
                COUNT(*) as total,
                SUM(CASE WHEN estado = 'enviado' THEN 1 ELSE 0 END) as exitosos
            FROM envios_whatsapp
            WHERE fecha_envio >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            GROUP BY DATE(fecha_envio)
            ORDER BY fecha DESC
        ");

        jsonResponse([
            'success' => true,
            'data' => [
                'resumen' => $stats,
                'por_tipo' => $porTipo,
                'por_dia' => $porDia
            ]
        ]);

    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Obtener historial de envíos de un cliente
 */
function getHistorialCliente($database, $clienteId) {
    if (!$clienteId) {
        jsonResponse(['success' => false, 'error' => 'Cliente ID requerido'], 400);
    }

    try {
        $envios = $database->fetchAll(
            "SELECT * FROM envios_whatsapp WHERE cliente_id = ? ORDER BY fecha_envio DESC",
            [$clienteId]
        );

        jsonResponse(['success' => true, 'data' => $envios]);

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
        case 'enviar_lote':
            enviarLote($database, $input);
            break;
        case 'enviar_recordatorios':
            enviarRecordatorios($database, $input);
            break;
        case 'enviar_individual':
            enviarIndividual($database, $input);
            break;
        case 'enviar_imagen':
            enviarImagenWhatsApp($database, $input);
            break;
        case 'enviar_texto':
            enviarTextoWhatsApp($database, $input);
            break;
        case 'test_connection':
            testConexionWhatsApp($database);
            break;
        case 'generar_imagen_recordatorio':  // ← AGREGAR ESTA LÍNEA
            generarImagenRecordatorioEndpoint($database, $input);  // ← Y ESTA
            break;  // ← Y ESTA
        default:
            jsonResponse(['success' => false, 'error' => 'Acción no válida'], 400);
    }
}

/**
 * Enviar imagen por WhatsApp
 */
function enviarImagenWhatsApp($database, $input) {
    $required = ['cliente_id', 'numero', 'imagen_base64'];
    $errors = validateInputLocal($input, $required);

    if (!empty($errors)) {
        jsonResponse(['success' => false, 'errors' => $errors], 400);
    }

    try {
        // Obtener configuración de WhatsApp
        $config = obtenerConfigWhatsApp($database);
        if (!$config) {
            jsonResponse(['success' => false, 'error' => 'Configuración de WhatsApp no encontrada'], 500);
        }

        // Formatear número
        $numero = formatearNumero($input['numero']);

        // URL para enviar imagen
        $url = $config['api_url'] . "message/sendmedia/" . $config['instancia_whatsapp'];

        $payload = [
    'number' => $numero,
    'mediatype' => 'image',
    'filename' => 'orden_pago_' . $input['cliente_id'] . '.png',
    'media' => $input['imagen_base64'],
    'caption' => $input['caption'] ?? '📄 Orden de Pago - Imaginatics Peru SAC'
];

        $resultado = enviarCurlWhatsApp($url, $payload, $config['token']);

        // Registrar en BD
        $database->insert(
            "INSERT INTO envios_whatsapp (cliente_id, numero_destino, tipo_envio, estado, respuesta_api, mensaje_error, imagen_generada) VALUES (?, ?, ?, ?, ?, ?, ?)",
            [
                $input['cliente_id'],
                $numero,
                'orden_pago',
                $resultado['success'] ? 'enviado' : 'error',
                json_encode($resultado['response']),
                $resultado['error'],
                true
            ]
        );

        jsonResponse([
            'success' => $resultado['success'],
            'response' => $resultado['response'],
            'error' => $resultado['error']
        ]);

    } catch (Exception $e) {
        jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
    }
}

/**
 * Enviar texto por WhatsApp
 */
function enviarTextoWhatsApp($database, $input) {
    $required = ['cliente_id', 'numero', 'mensaje'];
    $errors = validateInputLocal($input, $required);

    if (!empty($errors)) {
        jsonResponse(['success' => false, 'errors' => $errors], 400);
    }

    try {
        // Obtener configuración de WhatsApp
        $config = obtenerConfigWhatsApp($database);
        if (!$config) {
            jsonResponse(['success' => false, 'error' => 'Configuración de WhatsApp no encontrada'], 500);
        }

        // Formatear número
        $numero = formatearNumero($input['numero']);

        // URL para enviar texto
        $url = $config['api_url'] . "message/sendtext/" . $config['instancia_whatsapp'];

        $payload = [
            'number' => $numero,
            'text' => $input['mensaje']
        ];

        $resultado = enviarCurlWhatsApp($url, $payload, $config['token']);

        // Registrar en BD
        $database->insert(
            "INSERT INTO envios_whatsapp (cliente_id, numero_destino, tipo_envio, estado, respuesta_api, mensaje_error, mensaje_texto) VALUES (?, ?, ?, ?, ?, ?, ?)",
            [
                $input['cliente_id'],
                $numero,
                'orden_pago',
                $resultado['success'] ? 'enviado' : 'error',
                json_encode($resultado['response']),
                $resultado['error'],
                $input['mensaje']
            ]
        );

        jsonResponse([
            'success' => $resultado['success'],
            'response' => $resultado['response'],
            'error' => $resultado['error']
        ]);

    } catch (Exception $e) {
        jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
    }
}

/**
 * Probar conexión con WhatsApp
 */
function testConexionWhatsApp($database) {
    try {
        $config = obtenerConfigWhatsApp($database);
        if (!$config) {
            jsonResponse(['success' => false, 'error' => 'Configuración de WhatsApp no encontrada'], 500);
        }

        $url = $config['api_url'] . "message/sendtext/" . $config['instancia_whatsapp'];

        $payload = [
            'number' => '51999999999',
            'text' => 'Test de conexión - Sistema Imaginatics'
        ];

        $resultado = enviarCurlWhatsApp($url, $payload, $config['token']);

        jsonResponse([
            'success' => true,
            'config_loaded' => true,
            'api_test' => $resultado,
            'message' => 'Test de conexión completado'
        ]);

    } catch (Exception $e) {
        jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
    }
}

/**
 * Enviar lote de órdenes de pago
 */
function enviarLote($database, $input) {
    if (!isset($input['clientes']) || !is_array($input['clientes'])) {
        jsonResponse(['success' => false, 'error' => 'Lista de clientes requerida'], 400);
    }

    $clientes = $input['clientes'];
    $resultados = [];
    $exitosos = 0;
    $errores = 0;

    try {
        $database->beginTransaction();

        foreach ($clientes as $clienteData) {
            if (!isset($clienteData['id'])) {
                $resultados[] = [
                    'cliente' => $clienteData,
                    'success' => false,
                    'error' => 'ID de cliente no proporcionado'
                ];
                $errores++;
                continue;
            }

            // Obtener datos completos del cliente
            $cliente = $database->fetch(
                "SELECT * FROM clientes WHERE id = ? AND activo = TRUE",
                [$clienteData['id']]
            );

            if (!$cliente) {
                $resultados[] = [
                    'cliente' => $clienteData,
                    'success' => false,
                    'error' => 'Cliente no encontrado'
                ];
                $errores++;
                continue;
            }

            // Generar mensaje
            $mensaje = generarMensajeOrdenPago($cliente);

            // Enviar por WhatsApp
            $resultadoEnvio = enviarWhatsAppSimple(
                $cliente['whatsapp'],
                $mensaje,
                $database
            );

            // Registrar envío en BD
            $envioId = $database->insert(
                "INSERT INTO envios_whatsapp (cliente_id, numero_destino, tipo_envio, mensaje_texto, estado, respuesta_api, mensaje_error, imagen_generada) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $cliente['id'],
                    formatearNumero($cliente['whatsapp']),
                    'orden_pago',
                    $mensaje,
                    $resultadoEnvio['success'] ? 'enviado' : 'error',
                    $resultadoEnvio['respuesta'] ?? null,
                    $resultadoEnvio['error'] ?? null,
                    true
                ]
            );

            $resultados[] = [
                'cliente' => $cliente,
                'envio_id' => $envioId,
                'success' => $resultadoEnvio['success'],
                'error' => $resultadoEnvio['error'] ?? null
            ];

            if ($resultadoEnvio['success']) {
                $exitosos++;
            } else {
                $errores++;
            }

            // Pausa entre envíos
            usleep(2000000);
        }

        $database->commit();

        jsonResponse([
            'success' => true,
            'message' => 'Lote procesado',
            'data' => [
                'total' => count($clientes),
                'exitosos' => $exitosos,
                'errores' => $errores,
                'resultados' => $resultados
            ]
        ]);

    } catch (Exception $e) {
        $database->rollback();
        throw $e;
    }
}

/**
 * Enviar recordatorios de vencimiento
 */
function enviarRecordatorios($database, $input) {
    $diasAnticipacion = $input['dias_anticipacion'] ?? 3;

    try {
        $sql = "SELECT c.*, DATEDIFF(c.fecha_vencimiento, CURDATE()) as dias_restantes
                FROM clientes c
                WHERE c.activo = TRUE
                AND DATEDIFF(c.fecha_vencimiento, CURDATE()) <= ?
                ORDER BY c.fecha_vencimiento ASC";

        $clientes = $database->fetchAll($sql, [$diasAnticipacion]);

        if (empty($clientes)) {
            jsonResponse([
                'success' => true,
                'message' => 'No hay clientes que requieran recordatorio',
                'data' => ['total' => 0, 'exitosos' => 0, 'errores' => 0]
            ]);
        }

        // DEVOLVER LISTA PARA QUE JAVASCRIPT PROCESE
        jsonResponse([
            'success' => true,
            'action' => 'procesar_recordatorios_frontend',
            'clientes' => $clientes,
            'message' => 'Clientes listos para procesar recordatorios'
        ]);

    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Generar imagen de recordatorio con colores según estado
 */
function generarImagenRecordatorio($cliente, $diasRestantes) {
    // AGREGAR ESTAS LÍNEAS DE DEBUG AL INICIO:
    error_log("=== DEBUG RECORDATORIO ===");
    error_log("Input recibido: " . json_encode($input));
    error_log("Campos requeridos buscando: cliente_id, dias_restantes, imagen_base64");
    error_log("cliente_id existe: " . (isset($input['cliente_id']) ? 'SI' : 'NO'));
    error_log("dias_restantes existe: " . (isset($input['dias_restantes']) ? 'SI' : 'NO'));
    error_log("imagen_base64 existe: " . (isset($input['imagen_base64']) ? 'SI' : 'NO'));

    $required = ['cliente_id', 'dias_restantes', 'imagen_base64'];
    $errors = validateInputLocal($input, $required);

    if (!empty($errors)) {
        error_log("Errores de validación: " . json_encode($errors));
        jsonResponse(['success' => false, 'errors' => $errors], 400);
    }

    try {
        // Determinar colores y textos según estado
        if ($diasRestantes < 0) {  // Vencido
            $colorPrincipal = '#FF4444';
            $colorFondo = '#FFE6E6';
            $titulo = 'PAGO VENCIDO';
            $diasTexto = abs($diasRestantes) . ' días de atraso';
            $emoji = '🚨';
        } elseif ($diasRestantes == 0) {  // Vence hoy
            $colorPrincipal = '#FF8800';
            $colorFondo = '#FFF4E6';
            $titulo = 'PAGO VENCE HOY';
            $diasTexto = 'Último día para pagar';
            $emoji = '⏰';
        } else {  // Por vencer
            $colorPrincipal = '#FFB800';
            $colorFondo = '#FFFAE6';
            $titulo = 'RECORDATORIO DE PAGO';
            $diasTexto = $diasRestantes . ' días restantes';
            $emoji = '⚠️';
        }

        // Crear imagen
        $width = 915;
        $height = 550;
        $img = imagecreatetruecolor($width, $height);

        // Definir colores
        $blanco = imagecolorallocate($img, 255, 255, 255);
        $colorPpal = hexToRgb($colorPrincipal);
        $colorPpalImg = imagecolorallocate($img, $colorPpal[0], $colorPpal[1], $colorPpal[2]);
        $colorFondoRgb = hexToRgb($colorFondo);
        $colorFondoImg = imagecolorallocate($img, $colorFondoRgb[0], $colorFondoRgb[1], $colorFondoRgb[2]);
        $negro = imagecolorallocate($img, 51, 51, 51);
        $gris = imagecolorallocate($img, 102, 102, 102);

        // Fondo blanco
        imagefill($img, 0, 0, $blanco);

        // Bordes de alerta
        imagesetthickness($img, 5);
        imagerectangle($img, 10, 10, $width-11, $height-11, $colorPpalImg);
        imagesetthickness($img, 2);
        imagerectangle($img, 15, 15, $width-16, $height-16, $colorPpalImg);

        // Fondo para título
        imagefilledrectangle($img, 150, 160, 765, 210, $colorFondoImg);
        imagerectangle($img, 150, 160, 765, 210, $colorPpalImg);

        // NOTA: Para producción necesitarás una fuente TTF
        // Por ahora usamos funciones básicas de texto

        // Título (simulando el emoji con texto)
        $titleText = "*** $titulo ***";
        imagestring($img, 5, 200, 175, $titleText, $colorPpalImg);

        // Logo área (placeholder)
        imagestring($img, 3, 50, 40, "IMAGINATICS PERU SAC", $colorPpalImg);

        // Información del cliente
        imagestring($img, 4, 50, 240, "Cliente: " . substr($cliente['razon_social'], 0, 50), $negro);

        $fechaFormateada = date('d/m/Y', strtotime($cliente['fecha_vencimiento']));
        imagestring($img, 3, 50, 270, "Fecha vencimiento: $fechaFormateada", $gris);
        imagestring($img, 3, 50, 290, "Monto: S/ " . $cliente['monto'], $gris);

        // Días destacados con fondo
        imagefilledrectangle($img, 250, 320, 665, 365, $colorPpalImg);
        imagestring($img, 5, 300, 335, $diasTexto, $blanco);

        // Cuentas bancarias
        imagestring($img, 4, 50, 390, "Cuentas para pago:", $colorPpalImg);
        imagestring($img, 2, 50, 415, "BCP: 19393234096052", $gris);
        imagestring($img, 2, 50, 430, "SCOTIABANK: 940-0122553", $gris);
        imagestring($img, 2, 50, 445, "INTERBANK: 562-3108838683", $gris);
        imagestring($img, 2, 50, 460, "YAPE/PLIN: 989613295", $gris);

        // Convertir a base64
        ob_start();
        imagepng($img);
        $imageData = ob_get_contents();
        ob_end_clean();
        imagedestroy($img);

        return base64_encode($imageData);

    } catch (Exception $e) {
        error_log("Error generando imagen: " . $e->getMessage());
        return null;
    }
}

/**
 * Convertir color hex a RGB
 */
function hexToRgb($hex) {
    $hex = str_replace('#', '', $hex);
    return [
        hexdec(substr($hex, 0, 2)),
        hexdec(substr($hex, 2, 2)),
        hexdec(substr($hex, 4, 2))
    ];
}

/**
 * Enviar mensaje individual
 */
function enviarIndividual($database, $input) {
    $required = ['cliente_id', 'tipo_envio'];
    $errors = validateInputLocal($input, $required);

    if (!empty($errors)) {
        jsonResponse(['success' => false, 'errors' => $errors], 400);
    }

    try {
        $cliente = $database->fetch(
            "SELECT * FROM clientes WHERE id = ? AND activo = TRUE",
            [$input['cliente_id']]
        );

        if (!$cliente) {
            jsonResponse(['success' => false, 'error' => 'Cliente no encontrado'], 404);
        }

        $mensaje = '';
        switch ($input['tipo_envio']) {
            case 'orden_pago':
                $mensaje = generarMensajeOrdenPago($cliente);
                break;
            case 'recordatorio_vencido':
            case 'recordatorio_proximo':
                $diasRestantes = isset($input['dias_restantes']) ? $input['dias_restantes'] :
                    floor((strtotime($cliente['fecha_vencimiento']) - time()) / (60 * 60 * 24));
                $mensaje = generarMensajeRecordatorio($cliente, $diasRestantes);
                break;
            default:
                $mensaje = $input['mensaje_personalizado'] ?? '';
        }

        if (empty($mensaje)) {
            jsonResponse(['success' => false, 'error' => 'No se pudo generar el mensaje'], 400);
        }

        $resultadoEnvio = enviarWhatsAppSimple($cliente['whatsapp'], $mensaje, $database);

        $envioId = $database->insert(
            "INSERT INTO envios_whatsapp (cliente_id, numero_destino, tipo_envio, mensaje_texto, estado, respuesta_api, mensaje_error, imagen_generada) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $cliente['id'],
                formatearNumero($cliente['whatsapp']),
                $input['tipo_envio'],
                $mensaje,
                $resultadoEnvio['success'] ? 'enviado' : 'error',
                $resultadoEnvio['respuesta'] ?? null,
                $resultadoEnvio['error'] ?? null,
                false
            ]
        );

        jsonResponse([
            'success' => $resultadoEnvio['success'],
            'message' => $resultadoEnvio['success'] ? 'Mensaje enviado exitosamente' : 'Error al enviar mensaje',
            'data' => [
                'envio_id' => $envioId,
                'cliente' => $cliente,
                'error' => $resultadoEnvio['error'] ?? null
            ]
        ]);

    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Función simplificada para enviar WhatsApp
 */
function enviarWhatsAppSimple($numero, $mensaje, $database) {
    $config = obtenerConfigWhatsApp($database);

    if (!$config) {
        return [
            'success' => false,
            'error' => 'Configuración de WhatsApp incompleta'
        ];
    }

    $numeroFormateado = formatearNumero($numero);
    $url = $config['api_url'] . "message/sendtext/" . $config['instancia_whatsapp'];

    $payload = [
        'number' => $numeroFormateado,
        'text' => $mensaje
    ];

    return enviarCurlWhatsApp($url, $payload, $config['token']);
}

/**
 * Obtener configuración de WhatsApp desde BD
 */
function obtenerConfigWhatsApp($database) {
    try {
        $token = $database->fetch("SELECT valor FROM configuracion WHERE clave = 'token_whatsapp'");
        $instancia = $database->fetch("SELECT valor FROM configuracion WHERE clave = 'instancia_whatsapp'");
        $url = $database->fetch("SELECT valor FROM configuracion WHERE clave = 'api_url_whatsapp'");

        if (!$token || !$instancia || !$url) {
            return null;
        }

        return [
            'token' => $token['valor'],
            'instancia_whatsapp' => $instancia['valor'],
            'api_url' => $url['valor']
        ];
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Formatear número de teléfono
 */
function formatearNumero($numero) {
    $numeroLimpio = preg_replace('/[^0-9]/', '', $numero);

    if (strlen($numeroLimpio) === 9) {
        return '51' . $numeroLimpio;
    }

    return $numeroLimpio;
}

/**
 * Enviar petición cURL a WhatsApp
 */
function enviarCurlWhatsApp($url, $payload, $token) {
    // AGREGAR ESTAS LÍNEAS DE DEBUG AL INICIO:
    error_log("=== DEBUG WHATSAPP ===");
    error_log("URL construida: " . $url);
    error_log("Token (primeros 20 chars): " . substr($token, 0, 20) . "...");
    error_log("Payload enviado: " . json_encode($payload));

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer $token",
            "Content-Type: application/json"
        ],
        CURLOPT_SSL_VERIFYPEER => false
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    // AGREGAR ESTAS LÍNEAS DESPUÉS DE curl_close:
    error_log("HTTP Code recibido: " . $httpCode);
    error_log("Response recibida: " . $response);
    error_log("cURL Error: " . ($error ?: 'Ninguno'));
    error_log("=== FIN DEBUG ===");

    if ($error) {
        return [
            'success' => false,
            'error' => "Error cURL: $error",
            'response' => null
        ];
    }

    $data = json_decode($response, true);

    return [
        'success' => $httpCode >= 200 && $httpCode < 300,
        'response' => $data,
        'error' => $httpCode >= 400 ? "HTTP $httpCode: " . ($data['message'] ?? 'Error desconocido') : null,
        'respuesta' => json_encode($data)
    ];
}

/**
 * Generar mensaje de orden de pago
 */
function generarMensajeOrdenPago($cliente) {
     switch($cliente['tipo_servicio']) {
        case 'mensual':
            $periodoTexto = 'un mes más trabajando juntos';
            break;
        case 'trimestral':
            $periodoTexto = 'un trimestre más trabajando juntos';
            break;
        case 'semestral':
            $periodoTexto = 'un semestre más trabajando juntos';
            break;
        case 'anual':
        default:
            $periodoTexto = 'un año más trabajando juntos';
    }
    return "Hola {$cliente['razon_social']},

Estando próximo a cumplir {$periodoTexto}. Queremos recordarte que tiene una orden de Pago próximo a vencer por **S/ {$cliente['monto']}** con Imaginatics Perú.

Nada mejor que llevar tus cuentas al día, por eso no olvides realizar el pago, evite los cortes de sistema. ¡Que tengas un feliz día!

PD: No se olvide confirmar su pago.

Saludos,
Equipo de Cobranza de Imaginatics Peru SAC";
}

/**
 * Generar mensaje de recordatorio según días restantes
 */
function generarMensajeRecordatorio($cliente, $diasRestantes) {
    $fechaFormateada = date('d/m/Y', strtotime($cliente['fecha_vencimiento']));

    if ($diasRestantes < 0) {
        $diasAtraso = abs($diasRestantes);
        return "🚨 PAGO VENCIDO - {$cliente['razon_social']}

Su orden de pago tiene $diasAtraso días de atraso (venció el $fechaFormateada).

Para evitar suspensión del servicio, le solicitamos regularizar su pago a la brevedad.

💰 Monto: S/ {$cliente['monto']}
📅 Fecha de vencimiento: $fechaFormateada

¡Contacte con nosotros para coordinar su pago!

Saludos,
Equipo de Cobranza - Imaginatics Peru SAC";

    } elseif ($diasRestantes === 0) {
        return "⏰ ÚLTIMO DÍA - {$cliente['razon_social']}

Su orden de pago VENCE HOY ($fechaFormateada).

No pierda la oportunidad de mantener su servicio activo.

💰 Monto: S/ {$cliente['monto']}

¡Realice su pago hoy mismo!

Saludos,
Equipo de Cobranza - Imaginatics Peru SAC";

    } else {
        return "⚠️ RECORDATORIO - {$cliente['razon_social']}

Su orden de pago vence en $diasRestantes días ($fechaFormateada).

Mantenga sus cuentas al día para evitar interrupciones.

💰 Monto: S/ {$cliente['monto']}
📅 Fecha de vencimiento: $fechaFormateada

¡Gracias por su atención!

Saludos,
Equipo de Cobranza - Imaginatics Peru SAC";
    }
}

/**
 * Validar entrada de datos (función local para evitar conflictos)
 */
function validateInputLocal($input, $required) {
    $errors = [];
    foreach ($required as $field) {
        // NUEVA VALIDACIÓN: Solo verificar si existe y no es null o string vacío
        if (!array_key_exists($field, $input) || $input[$field] === null || $input[$field] === '') {
            $errors[] = "Campo requerido: $field";
        }
    }
    return $errors;
}

/**
 * Enviar imagen directamente por WhatsApp
 */
function enviarImagenRecordatorioWhatsApp($cliente, $diasRestantes, $database) {
    $config = obtenerConfigWhatsApp($database);
    if (!$config) return false;

    $imagenBase64 = generarImagenRecordatorio($cliente, $diasRestantes, $database);
    if (!$imagenBase64) return false;

    $numero = formatearNumero($cliente['whatsapp']);
    $url = $config['api_url'] . "message/sendmedia/" . $config['instancia_whatsapp'];

    $payload = [
        'number' => $numero,
        'mediatype' => 'image',
        'filename' => 'recordatorio_' . $cliente['id'] . '.png',
        'media' => $imagenBase64,
        'caption' => '📄 Recordatorio de Pago - Imaginatics Peru SAC'
    ];

    return enviarCurlWhatsApp($url, $payload, $config['token']);
}

/**
 * Generar imagen de recordatorio desde frontend
 */
function generarImagenRecordatorioEndpoint($database, $input) {
    $required = ['cliente_id', 'dias_restantes', 'imagen_base64'];
    $errors = validateInputLocal($input, $required);

    if (!empty($errors)) {
        jsonResponse(['success' => false, 'errors' => $errors], 400);
    }

    try {
        // Obtener cliente
        $cliente = $database->fetch(
            "SELECT * FROM clientes WHERE id = ? AND activo = TRUE",
            [$input['cliente_id']]
        );

        if (!$cliente) {
            jsonResponse(['success' => false, 'error' => 'Cliente no encontrado'], 404);
        }

        // Enviar imagen
        $config = obtenerConfigWhatsApp($database);
        $numero = formatearNumero($cliente['whatsapp']);
        $url = $config['api_url'] . "message/sendmedia/" . $config['instancia_whatsapp'];

        $payload = [
            'number' => $numero,
            'mediatype' => 'image',
            'filename' => 'recordatorio_' . $cliente['id'] . '.png',
            'media' => $input['imagen_base64'],
            'caption' => '📄 Recordatorio de Pago - Imaginatics Peru SAC'
        ];

        $resultado = enviarCurlWhatsApp($url, $payload, $config['token']);

        // Registrar en BD
        $tipoEnvio = $input['dias_restantes'] < 0 ? 'recordatorio_vencido' : 'recordatorio_proximo';

        $database->insert(
            "INSERT INTO envios_whatsapp (cliente_id, numero_destino, tipo_envio, estado, respuesta_api, mensaje_error, imagen_generada) VALUES (?, ?, ?, ?, ?, ?, ?)",
            [
                $input['cliente_id'],
                $numero,  // Agregar el número de destino
                $tipoEnvio,
                $resultado['success'] ? 'enviado' : 'error',
                json_encode($resultado['response']),
                $resultado['error'],
                1
            ]
        );

        jsonResponse([
            'success' => $resultado['success'],
            'response' => $resultado['response'],
            'error' => $resultado['error']
        ]);

    } catch (Exception $e) {
        jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
    }
}

// ← Aquí va el
?>