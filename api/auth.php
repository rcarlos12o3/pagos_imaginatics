<?php
/**
 * API de Autenticación
 * Maneja login, logout, recuperación de contraseñas y sesiones
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar peticiones OPTIONS para CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../config/database.php';

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    $database = new Database();
    $database->connect();
    $action = $_REQUEST['action'] ?? '';

    switch ($action) {
        case 'login':
            $input = json_decode(file_get_contents('php://input'), true);
            login($database, $input);
            break;
        
        case 'logout':
            logout($database);
            break;
        
        case 'forgot_password':
            $input = json_decode(file_get_contents('php://input'), true);
            forgotPassword($database, $input);
            break;
        
        case 'change_password':
            $input = json_decode(file_get_contents('php://input'), true);
            changePassword($database, $input);
            break;
        
        case 'check_session':
            checkSession($database);
            break;
        
        case 'first_time_setup':
            $input = json_decode(file_get_contents('php://input'), true);
            firstTimeSetup($database, $input);
            break;
        
        default:
            jsonResponse(['success' => false, 'error' => 'Acción no válida'], 400);
    }

} catch (Exception $e) {
    $database->log('error', 'auth', 'Error en API de autenticación: ' . $e->getMessage());
    jsonResponse(['success' => false, 'error' => 'Error del servidor: ' . $e->getMessage()], 500);
}

/**
 * Login de usuario
 */
function login($database, $input) {
    try {
        // Validar datos requeridos
        $errors = validateInput($input, ['celular', 'password']);
        if (!empty($errors)) {
            jsonResponse(['success' => false, 'errors' => $errors], 400);
        }

        $celular = preg_replace('/\D/', '', $input['celular']); // Solo dígitos
        $password = $input['password'];

        // Buscar usuario
        $usuario = $database->fetch(
            "SELECT * FROM usuarios WHERE celular = ? AND activo = TRUE", 
            [$celular]
        );

        if (!$usuario) {
            jsonResponse(['success' => false, 'error' => 'Número de celular no registrado'], 404);
        }

        // Verificar si está bloqueado
        if ($usuario['bloqueado_hasta'] && new DateTime($usuario['bloqueado_hasta']) > new DateTime()) {
            jsonResponse(['success' => false, 'error' => 'Usuario bloqueado temporalmente. Intente más tarde.'], 423);
        }

        // Verificar contraseña
        if (!password_verify($password, $usuario['password_hash'])) {
            // Incrementar intentos fallidos
            $intentosFallidos = $usuario['intentos_fallidos'] + 1;
            $bloqueadoHasta = null;
            
            if ($intentosFallidos >= 5) {
                $bloqueadoHasta = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            }
            
            $database->query(
                "UPDATE usuarios SET intentos_fallidos = ?, bloqueado_hasta = ? WHERE id = ?",
                [$intentosFallidos, $bloqueadoHasta, $usuario['id']]
            );
            
            jsonResponse(['success' => false, 'error' => 'Contraseña incorrecta'], 401);
        }

        // Login exitoso - limpiar intentos fallidos
        $database->query(
            "UPDATE usuarios SET intentos_fallidos = 0, bloqueado_hasta = NULL, ultimo_acceso = NOW() WHERE id = ?",
            [$usuario['id']]
        );

        // Crear sesión
        createSession($database, $usuario);

        // Log del login
        $database->log('info', 'auth', 'Login exitoso', [
            'usuario_id' => $usuario['id'],
            'celular' => $celular,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);

        jsonResponse([
            'success' => true,
            'message' => 'Login exitoso',
            'user' => [
                'id' => $usuario['id'],
                'celular' => $usuario['celular'],
                'nombre' => $usuario['nombre'],
                'primera_vez' => (bool)$usuario['primera_vez']
            ],
            'primera_vez' => (bool)$usuario['primera_vez']
        ]);

    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Logout de usuario
 */
function logout($database) {
    try {
        if (isset($_SESSION['user_id'])) {
            // Eliminar sesión de la base de datos
            $database->query(
                "DELETE FROM sesiones WHERE usuario_id = ?",
                [$_SESSION['user_id']]
            );

            // Log del logout
            $database->log('info', 'auth', 'Logout exitoso', [
                'usuario_id' => $_SESSION['user_id']
            ]);
        }

        // Destruir sesión PHP
        session_destroy();

        jsonResponse([
            'success' => true,
            'message' => 'Logout exitoso'
        ]);

    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Recuperar contraseña (generar nueva y enviar por WhatsApp)
 */
function forgotPassword($database, $input) {
    try {
        $errors = validateInput($input, ['celular']);
        if (!empty($errors)) {
            jsonResponse(['success' => false, 'errors' => $errors], 400);
        }

        $celular = preg_replace('/\D/', '', $input['celular']);

        // Buscar usuario
        $usuario = $database->fetch(
            "SELECT * FROM usuarios WHERE celular = ? AND activo = TRUE", 
            [$celular]
        );

        if (!$usuario) {
            jsonResponse(['success' => false, 'error' => 'Número de celular no registrado'], 404);
        }

        // Generar nueva contraseña temporal
        $nuevaPassword = generateRandomPassword();
        $passwordHash = password_hash($nuevaPassword, PASSWORD_DEFAULT);

        // Actualizar en base de datos
        $database->query(
            "UPDATE usuarios SET password_hash = ?, primera_vez = TRUE WHERE id = ?",
            [$passwordHash, $usuario['id']]
        );

        // Enviar por WhatsApp
        $mensaje = "🔐 *Nueva contraseña para acceder al sistema*\n\n";
        $mensaje .= "Celular: " . $celular . "\n";
        $mensaje .= "Contraseña: *" . $nuevaPassword . "*\n\n";
        $mensaje .= "⚠️ Por seguridad, cambia tu contraseña después del primer acceso.\n\n";
        $mensaje .= "Sistema de Gestión - Imaginatics Perú";

        $envioExitoso = enviarWhatsApp($database, $usuario['id'], $celular, $mensaje, 'recuperacion_password');

        // Log de recuperación
        $database->log('info', 'auth', 'Recuperación de contraseña', [
            'usuario_id' => $usuario['id'],
            'celular' => $celular,
            'envio_exitoso' => $envioExitoso
        ]);

        jsonResponse([
            'success' => true,
            'message' => 'Nueva contraseña enviada por WhatsApp'
        ]);

    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Cambiar contraseña (primera vez o cambio manual)
 */
function changePassword($database, $input) {
    try {
        if (!isset($_SESSION['user_id'])) {
            jsonResponse(['success' => false, 'error' => 'No autenticado'], 401);
        }

        $errors = validateInput($input, ['nueva_password']);
        if (!empty($errors)) {
            jsonResponse(['success' => false, 'errors' => $errors], 400);
        }

        $nuevaPassword = $input['nueva_password'];

        // Validar longitud mínima
        if (strlen($nuevaPassword) < 6) {
            jsonResponse(['success' => false, 'error' => 'La contraseña debe tener al menos 6 caracteres'], 400);
        }

        $passwordHash = password_hash($nuevaPassword, PASSWORD_DEFAULT);

        // Actualizar contraseña
        $database->query(
            "UPDATE usuarios SET password_hash = ?, primera_vez = FALSE WHERE id = ?",
            [$passwordHash, $_SESSION['user_id']]
        );

        // Log del cambio
        $database->log('info', 'auth', 'Contraseña cambiada', [
            'usuario_id' => $_SESSION['user_id']
        ]);

        jsonResponse([
            'success' => true,
            'message' => 'Contraseña actualizada exitosamente'
        ]);

    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Setup inicial (primera vez que se loguea un usuario)
 */
function firstTimeSetup($database, $input) {
    try {
        if (!isset($_SESSION['user_id'])) {
            jsonResponse(['success' => false, 'error' => 'No autenticado'], 401);
        }

        // El usuario puede cambiar su contraseña en el primer acceso
        if (isset($input['nueva_password']) && $input['nueva_password']) {
            $nuevaPassword = $input['nueva_password'];
            
            if (strlen($nuevaPassword) < 6) {
                jsonResponse(['success' => false, 'error' => 'La contraseña debe tener al menos 6 caracteres'], 400);
            }

            $passwordHash = password_hash($nuevaPassword, PASSWORD_DEFAULT);
            
            $database->query(
                "UPDATE usuarios SET password_hash = ?, primera_vez = FALSE WHERE id = ?",
                [$passwordHash, $_SESSION['user_id']]
            );
        } else {
            // Solo marcar como no primera vez
            $database->query(
                "UPDATE usuarios SET primera_vez = FALSE WHERE id = ?",
                [$_SESSION['user_id']]
            );
        }

        jsonResponse([
            'success' => true,
            'message' => 'Configuración inicial completada'
        ]);

    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Verificar sesión activa
 */
function checkSession($database) {
    try {
        if (!isset($_SESSION['user_id'])) {
            jsonResponse(['success' => false, 'error' => 'No autenticado'], 401);
        }

        // Verificar si la sesión sigue siendo válida en base de datos
        $sesion = $database->fetch(
            "SELECT s.*, u.celular, u.nombre, u.primera_vez 
             FROM sesiones s 
             JOIN usuarios u ON s.usuario_id = u.id 
             WHERE s.usuario_id = ? AND s.fecha_expiracion > NOW() 
             ORDER BY s.fecha_expiracion DESC LIMIT 1",
            [$_SESSION['user_id']]
        );

        if (!$sesion) {
            session_destroy();
            jsonResponse(['success' => false, 'error' => 'Sesión expirada'], 401);
        }

        jsonResponse([
            'success' => true,
            'user' => [
                'id' => $_SESSION['user_id'],
                'celular' => $sesion['celular'],
                'nombre' => $sesion['nombre'],
                'primera_vez' => (bool)$sesion['primera_vez']
            ]
        ]);

    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Crear sesión de usuario
 */
function createSession($database, $usuario) {
    try {
        // Generar ID de sesión único
        $sessionId = bin2hex(random_bytes(32));
        
        // Fecha de expiración (24 horas)
        $fechaExpiracion = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        // Limpiar sesiones expiradas del usuario
        $database->query(
            "DELETE FROM sesiones WHERE usuario_id = ? OR fecha_expiracion < NOW()",
            [$usuario['id']]
        );
        
        // Crear nueva sesión en base de datos
        $database->insert(
            "INSERT INTO sesiones (id, usuario_id, ip_address, user_agent, payload, last_activity, fecha_expiracion) 
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [
                $sessionId,
                $usuario['id'],
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                json_encode(['login_time' => time()]),
                time(),
                $fechaExpiracion
            ]
        );
        
        // Establecer variables de sesión PHP
        $_SESSION['user_id'] = $usuario['id'];
        $_SESSION['session_id'] = $sessionId;
        $_SESSION['celular'] = $usuario['celular'];
        $_SESSION['nombre'] = $usuario['nombre'];
        
    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Enviar mensaje por WhatsApp
 */
function enviarWhatsApp($database, $clienteId, $numero, $mensaje, $tipo = 'auth') {
    try {
        // Aquí integraremos con la API de WhatsApp existente
        // Por ahora, simplemente registramos el envío
        
        $database->insert(
            "INSERT INTO envios_whatsapp (cliente_id, numero_destino, tipo_envio, mensaje_texto, estado, respuesta_api) 
             VALUES (?, ?, ?, ?, ?, ?)",
            [$clienteId, $numero, $tipo, $mensaje, 'enviado', 'Enviado via sistema de auth']
        );
        
        // TODO: Integrar con API real de WhatsApp
        // Por ahora retornamos true
        return true;
        
    } catch (Exception $e) {
        $database->log('error', 'auth', 'Error enviando WhatsApp: ' . $e->getMessage());
        return false;
    }
}

/**
 * Generar contraseña aleatoria
 */
function generateRandomPassword($length = 8) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $password = '';
    
    for ($i = 0; $i < $length; $i++) {
        $password .= $characters[rand(0, strlen($characters) - 1)];
    }
    
    return $password;
}