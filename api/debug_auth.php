<?php
/**
 * API DEBUG para visualizar credenciales de autenticación
 * SOLO PARA DESARROLLO - Remover en producción
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

try {
    $database = new Database();
    $database->connect();
    $action = $_REQUEST['action'] ?? '';

    switch ($action) {
        case 'get_users':
            // Mostrar usuarios y sus credenciales temporales
            getUsers($database);
            break;
        
        case 'reset_password':
            // Resetear contraseña a "password"
            $input = json_decode(file_get_contents('php://input'), true);
            resetPassword($database, $input);
            break;
        
        case 'get_whatsapp_messages':
            // Mostrar mensajes enviados por WhatsApp
            getWhatsAppMessages($database);
            break;
        
        case 'debug_users':
            // Debug completo de usuarios
            debugUsers($database);
            break;
        
        case 'unlock_user':
            // Desbloquear usuario
            $input = json_decode(file_get_contents('php://input'), true);
            unlockUser($database, $input);
            break;
        
        default:
            jsonResponse(['success' => false, 'error' => 'Acción no válida'], 400);
    }

} catch (Exception $e) {
    jsonResponse(['success' => false, 'error' => 'Error del servidor: ' . $e->getMessage()], 500);
}

function getUsers($database) {
    $usuarios = $database->fetchAll("SELECT id, celular, nombre, primera_vez, ultimo_acceso FROM usuarios WHERE activo = TRUE");
    
    jsonResponse([
        'success' => true,
        'usuarios' => $usuarios,
        'nota' => 'Contraseña temporal para todos: password'
    ]);
}

function resetPassword($database, $input) {
    if (!isset($input['celular'])) {
        jsonResponse(['success' => false, 'error' => 'Celular requerido'], 400);
    }
    
    $celular = preg_replace('/\D/', '', $input['celular']);
    
    // Debug: verificar qué usuario estamos buscando
    $usuario = $database->fetch("SELECT * FROM usuarios WHERE celular = ?", [$celular]);
    
    if (!$usuario) {
        jsonResponse([
            'success' => false, 
            'error' => 'Usuario no encontrado',
            'debug' => [
                'celular_buscado' => $celular,
                'celular_original' => $input['celular']
            ]
        ], 404);
    }
    
    $passwordHash = password_hash('password', PASSWORD_DEFAULT);
    
    $affected = $database->query(
        "UPDATE usuarios SET password_hash = ?, primera_vez = TRUE, intentos_fallidos = 0, bloqueado_hasta = NULL WHERE celular = ?",
        [$passwordHash, $celular]
    );
    
    if ($affected > 0) {
        jsonResponse([
            'success' => true,
            'message' => 'Contraseña reseteada a: password',
            'celular' => $celular
        ]);
    } else {
        jsonResponse(['success' => false, 'error' => 'Error actualizando usuario'], 500);
    }
}

function getWhatsAppMessages($database) {
    $mensajes = $database->fetchAll(
        "SELECT * FROM envios_whatsapp 
         WHERE tipo_envio IN ('auth', 'recuperacion_password') 
         ORDER BY fecha_envio DESC 
         LIMIT 10"
    );
    
    jsonResponse([
        'success' => true,
        'mensajes' => $mensajes
    ]);
}

function debugUsers($database) {
    $usuarios = $database->fetchAll("SELECT * FROM usuarios");
    $totalUsers = count($usuarios);
    $activeUsers = $database->fetch("SELECT COUNT(*) as count FROM usuarios WHERE activo = TRUE")['count'];
    
    jsonResponse([
        'success' => true,
        'debug_info' => [
            'total_usuarios' => $totalUsers,
            'usuarios_activos' => $activeUsers,
            'usuarios_completos' => $usuarios
        ]
    ]);
}

function unlockUser($database, $input) {
    if (!isset($input['celular'])) {
        jsonResponse(['success' => false, 'error' => 'Celular requerido'], 400);
    }
    
    $celular = preg_replace('/\D/', '', $input['celular']);
    
    $affected = $database->query(
        "UPDATE usuarios SET intentos_fallidos = 0, bloqueado_hasta = NULL WHERE celular = ?",
        [$celular]
    );
    
    if ($affected > 0) {
        jsonResponse([
            'success' => true,
            'message' => 'Usuario desbloqueado exitosamente',
            'celular' => $celular
        ]);
    } else {
        jsonResponse(['success' => false, 'error' => 'Usuario no encontrado'], 404);
    }
}

// Usar la función jsonResponse del database.php
?>