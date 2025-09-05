<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$host = 'mysql';
$dbname = 'imaginatics_ruc';
$username = 'imaginatics';
$password = 'imaginatics123';

$diagnostico = [
    'timestamp' => date('Y-m-d H:i:s'),
    'bd_conexion' => '❌',
    'configuracion_whatsapp' => [],
    'ultimos_envios' => [],
    'errores_envios' => [],
    'estadisticas_envios' => [],
    'logs_sistema' => [],
    'resumen_envios' => [],
    'problemas_detectados' => []
];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $diagnostico['bd_conexion'] = '✅ Conectado correctamente';

    // 1. CONFIGURACIÓN DE WHATSAPP
    $stmt = $pdo->query("SELECT * FROM configuracion");
    $todas_configs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $diagnostico['todas_configuraciones'] = $todas_configs;

    // Buscar configuraciones de WhatsApp específicas
    $configs_whatsapp = [];
    foreach ($todas_configs as $config) {
        $nombre = strtolower($config['nombre'] ?? '');
        if (strpos($nombre, 'whatsapp') !== false ||
            strpos($nombre, 'token') !== false ||
            strpos($nombre, 'instance') !== false ||
            strpos($nombre, 'api') !== false) {
            $configs_whatsapp[] = [
                'nombre' => $config['nombre'],
                'valor_length' => strlen($config['valor'] ?? ''),
                'valor_preview' => substr($config['valor'] ?? '', 0, 20) . '...',
                'activo' => $config['activo'] ?? null,
                'descripcion' => $config['descripcion'] ?? null
            ];
        }
    }
    $diagnostico['configuracion_whatsapp'] = $configs_whatsapp;

    // 2. ESTRUCTURA Y DATOS DE ENVÍOS
    $stmt = $pdo->query("DESCRIBE envios_whatsapp");
    $diagnostico['estructura_envios'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Últimos envíos
    $stmt = $pdo->query("SELECT * FROM envios_whatsapp ORDER BY created_at DESC LIMIT 10");
    $diagnostico['ultimos_envios'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Envíos con error
    $stmt = $pdo->query("SELECT * FROM envios_whatsapp WHERE estado = 'error' ORDER BY created_at DESC LIMIT 5");
    $diagnostico['errores_envios'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Estadísticas por estado
    $stmt = $pdo->query("SELECT estado, COUNT(*) as cantidad FROM envios_whatsapp GROUP BY estado");
    $diagnostico['estadisticas_envios'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. LOGS DEL SISTEMA
    $stmt = $pdo->query("SELECT * FROM logs_sistema WHERE mensaje LIKE '%whatsapp%' OR mensaje LIKE '%envio%' OR nivel = 'ERROR' ORDER BY fecha DESC LIMIT 10");
    $diagnostico['logs_sistema'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. VISTA DE RESUMEN
    try {
        $stmt = $pdo->query("SELECT * FROM v_resumen_envios");
        $diagnostico['resumen_envios'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $diagnostico['resumen_envios'] = "Error: " . $e->getMessage();
    }

    // 5. ANÁLISIS DE PROBLEMAS
    $problemas = [];

    // Verificar si hay configuración de WhatsApp
    if (empty($configs_whatsapp)) {
        $problemas[] = "❌ No se encontró configuración de WhatsApp en la tabla 'configuracion'";
    }

    // Verificar tokens específicos
    $tokens_necesarios = ['whatsapp_token', 'whatsapp_instance_id', 'api_url_whatsapp'];
    $tokens_encontrados = [];
    foreach ($todas_configs as $config) {
        if (in_array($config['nombre'], $tokens_necesarios)) {
            $tokens_encontrados[] = $config['nombre'];
        }
    }

    foreach ($tokens_necesarios as $token) {
        if (!in_array($token, $tokens_encontrados)) {
            $problemas[] = "⚠️ Falta configuración: $token";
        }
    }

    // Verificar errores recientes
    $stmt = $pdo->query("SELECT COUNT(*) as errores_recientes FROM envios_whatsapp WHERE estado = 'error' AND created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)");
    $errores_recientes = $stmt->fetch(PDO::FETCH_ASSOC)['errores_recientes'];

    if ($errores_recientes > 0) {
        $problemas[] = "🚨 $errores_recientes envíos fallaron en las últimas 24 horas";
    }

    // Verificar si hay envíos exitosos
    $stmt = $pdo->query("SELECT COUNT(*) as exitosos FROM envios_whatsapp WHERE estado = 'enviado'");
    $exitosos = $stmt->fetch(PDO::FETCH_ASSOC)['exitosos'];

    if ($exitosos == 0) {
        $problemas[] = "❌ No hay ningún envío exitoso registrado";
    } else {
        $problemas[] = "✅ Hay $exitosos envíos exitosos registrados";
    }

    $diagnostico['problemas_detectados'] = $problemas;

    // 6. RECOMENDACIONES
    $recomendaciones = [];

    if (empty($configs_whatsapp)) {
        $recomendaciones[] = "Insertar configuración de WhatsApp en tabla 'configuracion'";
    }

    if ($errores_recientes > 0) {
        $recomendaciones[] = "Revisar logs de errores y verificar tokens de WhatsApp";
    }

    $diagnostico['recomendaciones'] = $recomendaciones;

} catch (Exception $e) {
    $diagnostico['bd_conexion'] = '❌ Error: ' . $e->getMessage();
    $diagnostico['error_general'] = $e->getMessage();
}

echo json_encode($diagnostico, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>