<?php
/**
 * Verificación de sesión para páginas protegidas
 * Incluir este archivo al inicio de cada página que requiere autenticación
 */

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

/**
 * Verificar si el usuario está autenticado
 */
function verificarSesion($redirigir = true) {
    try {
        // Verificar si hay sesión PHP activa
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['session_id'])) {
            if ($redirigir) {
                header('Location: /login.html');
                exit;
            }
            return false;
        }

        // Verificar sesión en base de datos
        $database = new Database();
        $database->connect();
        
        $sesion = $database->fetch(
            "SELECT s.*, u.activo 
             FROM sesiones s 
             JOIN usuarios u ON s.usuario_id = u.id 
             WHERE s.id = ? AND s.usuario_id = ? AND s.fecha_expiracion > NOW() AND u.activo = TRUE",
            [$_SESSION['session_id'], $_SESSION['user_id']]
        );

        if (!$sesion) {
            // Sesión inválida o expirada
            session_destroy();
            if ($redirigir) {
                header('Location: /login.html');
                exit;
            }
            return false;
        }

        // Actualizar última actividad
        $database->query(
            "UPDATE sesiones SET last_activity = ? WHERE id = ?",
            [time(), $_SESSION['session_id']]
        );

        return true;

    } catch (Exception $e) {
        error_log('Error verificando sesión: ' . $e->getMessage());
        if ($redirigir) {
            header('Location: /login.html');
            exit;
        }
        return false;
    }
}

/**
 * Obtener información del usuario actual
 */
function obtenerUsuarioActual() {
    if (!verificarSesion(false)) {
        return null;
    }

    try {
        $database = new Database();
        $database->connect();
        
        $usuario = $database->fetch(
            "SELECT id, celular, nombre, primera_vez FROM usuarios WHERE id = ?",
            [$_SESSION['user_id']]
        );

        return $usuario;

    } catch (Exception $e) {
        error_log('Error obteniendo usuario: ' . $e->getMessage());
        return null;
    }
}

/**
 * Middleware para APIs - devuelve JSON en lugar de redireccionar
 */
function verificarSesionAPI() {
    if (!verificarSesion(false)) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'No autenticado']);
        exit;
    }
}

// Auto-verificar si no se llama explícitamente
if (!defined('NO_AUTO_CHECK')) {
    verificarSesion();
}
?>