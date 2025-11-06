<?php
/**
 * PROCESADOR DE COLA DE ENVÍOS
 * Este script procesa envíos pendientes en background
 * Puede ejecutarse vía cron job o manualmente
 * Imaginatics Perú SAC
 */

require_once __DIR__ . '/../config/database.php';

// Configuración
define('MAX_TRABAJOS_POR_EJECUCION', 50);
define('PAUSA_ENTRE_MENSAJES', [10, 20]); // segundos [min, max]
define('PAUSA_ENTRE_CLIENTES', [30, 60]); // segundos [min, max]
define('TIMEOUT_PROCESAMIENTO', 7200); // 2 horas máximo

// Para ejecución CLI
$esCLI = php_sapi_name() === 'cli';

// Instanciar base de datos
$database = new Database();
$db = $database->connect();

// Log de inicio
$database->log('info', 'cola_procesador', 'Iniciando procesamiento de cola');

try {
    // Buscar sesiones pendientes o con trabajos sin terminar
    $sesionesPendientes = $database->fetchAll("
        SELECT s.*
        FROM sesiones_envio s
        WHERE s.estado IN ('pendiente', 'procesando')
        AND (
            s.fecha_inicio IS NULL
            OR TIMESTAMPDIFF(SECOND, s.fecha_inicio, NOW()) < ?
        )
        ORDER BY s.fecha_creacion ASC
        LIMIT 5
    ", [TIMEOUT_PROCESAMIENTO]);

    if (empty($sesionesPendientes)) {
        log_mensaje('✅ No hay sesiones pendientes para procesar');
        $database->log('info', 'cola_procesador', 'No hay sesiones pendientes');
        exit(0);
    }

    foreach ($sesionesPendientes as $sesion) {
        log_mensaje("📦 Procesando sesión #{$sesion['id']} - Tipo: {$sesion['tipo_envio']}");

        // Marcar sesión como procesando
        if ($sesion['estado'] === 'pendiente') {
            $database->query("
                UPDATE sesiones_envio
                SET estado = 'procesando', fecha_inicio = NOW()
                WHERE id = ?
            ", [$sesion['id']]);
        }

        // Obtener trabajos pendientes de esta sesión
        $trabajos = $database->fetchAll("
            SELECT * FROM cola_envios
            WHERE sesion_id = ?
            AND estado IN ('pendiente', 'error')
            AND intentos < max_intentos
            AND (fecha_programada IS NULL OR fecha_programada <= NOW())
            ORDER BY prioridad DESC, fecha_creacion ASC
            LIMIT ?
        ", [$sesion['id'], MAX_TRABAJOS_POR_EJECUCION]);

        if (empty($trabajos)) {
            log_mensaje("  ✅ Sesión #{$sesion['id']} completada");

            // Marcar sesión como completada
            $database->query("
                UPDATE sesiones_envio
                SET estado = 'completado', fecha_finalizacion = NOW()
                WHERE id = ?
            ", [$sesion['id']]);

            continue;
        }

        log_mensaje("  📋 {$trabajos[0]['count(*)']} trabajos pendientes en sesión #{$sesion['id']}");

        // Procesar cada trabajo
        $procesados = 0;
        foreach ($trabajos as $index => $trabajo) {
            try {
                log_mensaje("  [{$index + 1}/{$trabajos[0]['count(*)']}] Procesando: {$trabajo['razon_social']}");

                // Marcar como procesando
                $database->query("
                    UPDATE cola_envios
                    SET estado = 'procesando',
                        fecha_procesamiento = NOW(),
                        intentos = intentos + 1
                    WHERE id = ?
                ", [$trabajo['id']]);

                // Procesar el envío
                $resultado = procesarEnvio($trabajo, $database);

                if ($resultado['success']) {
                    // Éxito
                    $database->query("
                        UPDATE cola_envios
                        SET estado = 'enviado',
                            fecha_envio = NOW(),
                            envio_whatsapp_id = ?,
                            respuesta_api = ?,
                            mensaje_error = NULL
                        WHERE id = ?
                    ", [
                        $resultado['envio_whatsapp_id'] ?? null,
                        json_encode($resultado['respuesta'] ?? []),
                        $trabajo['id']
                    ]);

                    log_mensaje("    ✅ Enviado exitosamente");
                    $procesados++;
                } else {
                    // Error
                    $nuevoEstado = $trabajo['intentos'] + 1 >= $trabajo['max_intentos'] ? 'error' : 'pendiente';

                    $database->query("
                        UPDATE cola_envios
                        SET estado = ?,
                            mensaje_error = ?,
                            respuesta_api = ?
                        WHERE id = ?
                    ", [
                        $nuevoEstado,
                        $resultado['error'],
                        json_encode($resultado['respuesta'] ?? []),
                        $trabajo['id']
                    ]);

                    log_mensaje("    ❌ Error: {$resultado['error']}");
                }

                // Pausa entre clientes (solo si no es el último)
                if ($index < count($trabajos) - 1) {
                    $pausa = rand(PAUSA_ENTRE_CLIENTES[0], PAUSA_ENTRE_CLIENTES[1]);
                    log_mensaje("    ⏱️  Pausa {$pausa}s (modo cauteloso)");
                    sleep($pausa);
                }

            } catch (Exception $e) {
                log_mensaje("    ⚠️  Excepción: {$e->getMessage()}");

                $database->query("
                    UPDATE cola_envios
                    SET estado = 'error',
                        mensaje_error = ?
                    WHERE id = ?
                ", [$e->getMessage(), $trabajo['id']]);
            }
        }

        log_mensaje("  📊 Sesión #{$sesion['id']}: {$procesados} enviados exitosamente");
    }

    log_mensaje('✅ Procesamiento completado');
    $database->log('info', 'cola_procesador', 'Procesamiento completado exitosamente');

} catch (Exception $e) {
    log_mensaje("❌ Error fatal: {$e->getMessage()}");
    $database->log('error', 'cola_procesador', 'Error fatal: ' . $e->getMessage());
    exit(1);
}

/**
 * Procesar un envío individual
 */
function procesarEnvio($trabajo, $database) {
    try {
        // Obtener configuración de WhatsApp
        $config = obtenerConfigWhatsApp($database);
        if (!$config) {
            return [
                'success' => false,
                'error' => 'Configuración de WhatsApp no encontrada'
            ];
        }

        $numero = formatearNumero($trabajo['whatsapp']);

        // 1. Enviar imagen (si existe)
        if (!empty($trabajo['imagen_base64'])) {
            log_mensaje("      📷 Enviando imagen...");

            $urlImagen = $config['api_url'] . "message/sendmedia/" . $config['instancia_whatsapp'];
            $payloadImagen = [
                'number' => $numero,
                'mediatype' => 'image',
                'filename' => "envio_{$trabajo['cliente_id']}.png",
                'media' => $trabajo['imagen_base64'],
                'caption' => '📄 Imaginatics Peru SAC'
            ];

            $resultadoImagen = enviarCurlWhatsApp($urlImagen, $payloadImagen, $config['token']);

            if (!$resultadoImagen['success']) {
                return [
                    'success' => false,
                    'error' => 'Error enviando imagen: ' . $resultadoImagen['error'],
                    'respuesta' => $resultadoImagen['response']
                ];
            }

            log_mensaje("      ✅ Imagen enviada");

            // Pausa entre imagen y texto
            $pausa = rand(PAUSA_ENTRE_MENSAJES[0], PAUSA_ENTRE_MENSAJES[1]);
            log_mensaje("      ⏱️  Pausa {$pausa}s antes de texto");
            sleep($pausa);
        }

        // 2. Enviar texto
        log_mensaje("      💬 Enviando texto...");

        $urlTexto = $config['api_url'] . "message/sendtext/" . $config['instancia_whatsapp'];
        $payloadTexto = [
            'number' => $numero,
            'text' => $trabajo['mensaje_texto']
        ];

        $resultadoTexto = enviarCurlWhatsApp($urlTexto, $payloadTexto, $config['token']);

        if (!$resultadoTexto['success']) {
            return [
                'success' => false,
                'error' => 'Error enviando texto: ' . $resultadoTexto['error'],
                'respuesta' => $resultadoTexto['response']
            ];
        }

        log_mensaje("      ✅ Texto enviado");

        // 3. Registrar en envios_whatsapp
        $envioId = $database->insert("
            INSERT INTO envios_whatsapp
            (cliente_id, numero_destino, tipo_envio, estado, respuesta_api, imagen_generada, mensaje_texto)
            VALUES (?, ?, ?, 'enviado', ?, ?, ?)
        ", [
            $trabajo['cliente_id'],
            $numero,
            $trabajo['tipo_envio'],
            json_encode($resultadoTexto['response']),
            !empty($trabajo['imagen_base64']) ? 1 : 0,
            $trabajo['mensaje_texto']
        ]);

        return [
            'success' => true,
            'envio_whatsapp_id' => $envioId,
            'respuesta' => $resultadoTexto['response']
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => 'Excepción: ' . $e->getMessage()
        ];
    }
}

/**
 * Obtener configuración de WhatsApp
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
        'error' => $httpCode >= 400 ? "HTTP $httpCode: " . ($data['message'] ?? 'Error desconocido') : null
    ];
}

/**
 * Función auxiliar para logging
 */
function log_mensaje($mensaje) {
    global $esCLI;

    $timestamp = date('Y-m-d H:i:s');
    $output = "[$timestamp] $mensaje";

    if ($esCLI) {
        echo $output . PHP_EOL;
    } else {
        error_log($output);
    }
}
