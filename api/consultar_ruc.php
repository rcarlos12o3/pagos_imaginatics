<?php
/**
 * API CONSULTAR RUC
 * Consulta RUC usando API externa y cache local
 * Imaginatics Perú SAC
 */

require_once '../config/database.php';

// Instanciar base de datos
$database = new Database();
$db = $database->connect();

// Solo permitir GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['success' => false, 'error' => 'Método no permitido'], 405);
}

// Obtener RUC de la URL
$ruc = $_GET['ruc'] ?? '';

if (empty($ruc)) {
    jsonResponse(['success' => false, 'error' => 'RUC requerido'], 400);
}

// Validar RUC
$rucValidation = validateRUC($ruc);
if (!$rucValidation['valid']) {
    jsonResponse(['success' => false, 'error' => $rucValidation['message']], 400);
}

$ruc = $rucValidation['ruc'];

try {
    // Verificar si tenemos una consulta reciente en cache (últimas 24 horas)
    $cache = $database->fetch(
        "SELECT * FROM consultas_ruc 
         WHERE ruc = ? AND estado_consulta = 'exitosa' 
         AND fecha_consulta > DATE_SUB(NOW(), INTERVAL 24 HOUR)
         ORDER BY fecha_consulta DESC LIMIT 1",
        [$ruc]
    );
    
    if ($cache) {
        // Devolver datos del cache
        $data = json_decode($cache['respuesta_api'], true);
        
        $database->log('info', 'consulta_ruc', 'Consulta desde cache', [
            'ruc' => $ruc,
            'cache_fecha' => $cache['fecha_consulta']
        ]);
        
        jsonResponse([
            'success' => true,
            'data' => $data,
            'source' => 'cache',
            'cache_date' => $cache['fecha_consulta']
        ]);
    }
    
    // No hay cache, consultar API externa
    $resultado = consultarRUCExterno($ruc, $database);
    
    if ($resultado['success']) {
        jsonResponse([
            'success' => true,
            'data' => $resultado['data'],
            'source' => 'api'
        ]);
    } else {
        jsonResponse([
            'success' => false,
            'error' => $resultado['error']
        ], $resultado['status_code'] ?? 500);
    }
    
} catch (Exception $e) {
    $database->log('error', 'consulta_ruc', $e->getMessage(), ['ruc' => $ruc]);
    jsonResponse(['success' => false, 'error' => 'Error interno del servidor'], 500);
}

/**
 * Consultar RUC en API externa
 */
function consultarRUCExterno($ruc, $database) {
    // Obtener token de configuración
    $token = $database->getConfig('token_ruc');
    
    if (!$token) {
        return [
            'success' => false,
            'error' => 'Token de API no configurado',
            'status_code' => 500
        ];
    }
    
    $url = "https://api.factiliza.com/v1/ruc/info/$ruc";
    
    // Configurar cURL
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer $token",
            "Content-Type: application/json",
            "User-Agent: Imaginatics-RUC-Consultor/1.0"
        ],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_FOLLOWLOCATION => true
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    // Obtener IP del cliente para logs
    $clientIP = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    if ($error) {
        // Error de conexión
        $database->query(
            "INSERT INTO consultas_ruc (ruc, respuesta_api, estado_consulta, ip_origen) VALUES (?, ?, ?, ?)",
            [$ruc, json_encode(['error' => $error]), 'error', $clientIP]
        );
        
        $database->log('error', 'consulta_ruc', 'Error cURL', [
            'ruc' => $ruc,
            'error' => $error
        ]);
        
        return [
            'success' => false,
            'error' => 'Error de conexión con la API',
            'status_code' => 500
        ];
    }
    
    $data = json_decode($response, true);
    
    if ($httpCode === 200 && $data && isset($data['success']) && $data['success']) {
        // Consulta exitosa
        $responseData = [
            'nombre_o_razon_social' => $data['data']['nombre_o_razon_social'] ?? '',
            'estado' => $data['data']['estado'] ?? '',
            'condicion' => $data['data']['condicion'] ?? '',
            'direccion' => $data['data']['direccion'] ?? '',
            'ubigeo' => $data['data']['ubigeo'] ?? '',
            'tipo_contribuyente' => $data['data']['tipo_contribuyente'] ?? '',
            'fecha_inscripcion' => $data['data']['fecha_inscripcion'] ?? '',
            'fecha_inicio_actividades' => $data['data']['fecha_inicio_actividades'] ?? '',
            'actividad_economica' => $data['data']['actividad_economica'] ?? []
        ];
        
        // Guardar en cache
        $database->query(
            "INSERT INTO consultas_ruc (ruc, respuesta_api, estado_consulta, ip_origen) VALUES (?, ?, ?, ?)",
            [$ruc, json_encode($responseData), 'exitosa', $clientIP]
        );
        
        $database->log('info', 'consulta_ruc', 'Consulta API exitosa', [
            'ruc' => $ruc,
            'razon_social' => $responseData['nombre_o_razon_social']
        ]);
        
        return [
            'success' => true,
            'data' => $responseData
        ];
        
    } else {
        // Error en la respuesta
        $estadoConsulta = 'error';
        $errorMessage = 'Error desconocido';
        
        switch ($httpCode) {
            case 400:
                $estadoConsulta = 'no_encontrado';
                $errorMessage = 'RUC no válido o no encontrado';
                break;
            case 401:
                $errorMessage = 'Token de acceso inválido';
                break;
            case 429:
                $errorMessage = 'Límite de consultas excedido. Intente más tarde';
                break;
            case 500:
                $errorMessage = 'Error interno del servidor de la API';
                break;
            default:
                if (isset($data['message'])) {
                    $errorMessage = $data['message'];
                }
        }
        
        // Guardar error en logs
        $database->query(
            "INSERT INTO consultas_ruc (ruc, respuesta_api, estado_consulta, ip_origen) VALUES (?, ?, ?, ?)",
            [$ruc, $response, $estadoConsulta, $clientIP]
        );
        
        $database->log('warning', 'consulta_ruc', 'Error en consulta API', [
            'ruc' => $ruc,
            'http_code' => $httpCode,
            'error' => $errorMessage
        ]);
        
        return [
            'success' => false,
            'error' => $errorMessage,
            'status_code' => $httpCode
        ];
    }
}

/**
 * Endpoint adicional para limpiar cache
 */
if (isset($_GET['action']) && $_GET['action'] === 'clear_cache') {
    if (!isset($_GET['ruc'])) {
        jsonResponse(['success' => false, 'error' => 'RUC requerido para limpiar cache'], 400);
    }
    
    try {
        $rucValidation = validateRUC($_GET['ruc']);
        if (!$rucValidation['valid']) {
            jsonResponse(['success' => false, 'error' => $rucValidation['message']], 400);
        }
        
        $deleted = $database->rowCount(
            "DELETE FROM consultas_ruc WHERE ruc = ?",
            [$rucValidation['ruc']]
        );
        
        $database->log('info', 'consulta_ruc', 'Cache limpiado', [
            'ruc' => $rucValidation['ruc'],
            'registros_eliminados' => $deleted
        ]);
        
        jsonResponse([
            'success' => true,
            'message' => "Cache limpiado para RUC {$rucValidation['ruc']}",
            'deleted_records' => $deleted
        ]);
        
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
    }
}

/**
 * Endpoint para estadísticas de consultas
 */
if (isset($_GET['action']) && $_GET['action'] === 'stats') {
    try {
        $stats = $database->fetch("
            SELECT 
                COUNT(*) as total_consultas,
                COUNT(DISTINCT ruc) as rucs_unicos,
                SUM(CASE WHEN estado_consulta = 'exitosa' THEN 1 ELSE 0 END) as exitosas,
                SUM(CASE WHEN estado_consulta = 'error' THEN 1 ELSE 0 END) as errores,
                SUM(CASE WHEN estado_consulta = 'no_encontrado' THEN 1 ELSE 0 END) as no_encontrados,
                COUNT(CASE WHEN fecha_consulta >= CURDATE() THEN 1 END) as consultas_hoy,
                COUNT(CASE WHEN fecha_consulta >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as consultas_semana
            FROM consultas_ruc
        ");
        
        // Top RUCs más consultados
        $topRucs = $database->fetchAll("
            SELECT ruc, COUNT(*) as consultas
            FROM consultas_ruc
            GROUP BY ruc
            ORDER BY consultas DESC
            LIMIT 10
        ");
        
        jsonResponse([
            'success' => true,
            'data' => [
                'resumen' => $stats,
                'top_rucs' => $topRucs
            ]
        ]);
        
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
    }
}
?>