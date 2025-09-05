<?php
/**
 * CONFIGURACIÓN DE BASE DE DATOS
 * Imaginatics Perú SAC - Sistema RUC Consultor
 */

// Incluir configuraciones globales
require_once __DIR__ . '/init.php';

// Configuración de la base de datos
define('DB_HOST', 'mysql');
define('DB_NAME', 'imaginatics_ruc');
define('DB_USER', 'imaginatics'); // Cambiar por tu usuario
define('DB_PASS', 'imaginatics123');     // Cambiar por tu contraseña
define('DB_CHARSET', 'utf8mb4');

// Configuración de errores
define('DEBUG_MODE', true); // Cambiar a false en producción

// Configuración de CORS para permitir requests desde el frontend
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

/**
 * Clase para manejo de base de datos
 */
class Database {
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    private $charset = DB_CHARSET;
    public $pdo;

    /**
     * Conectar a la base de datos
     */
    public function connect() {
        $this->pdo = null;

        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . $this->charset;

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ];

            $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
            
            // Configurar zona horaria de MySQL a Lima, Perú
            $this->pdo->exec("SET time_zone = '-05:00'"); // UTC-5 para Perú

        } catch(PDOException $e) {
            if (DEBUG_MODE) {
                error_log("Error de conexión: " . $e->getMessage());
                die(json_encode([
                    'success' => false,
                    'error' => 'Error de conexión a la base de datos: ' . $e->getMessage()
                ]));
            } else {
                die(json_encode([
                    'success' => false,
                    'error' => 'Error de conexión a la base de datos'
                ]));
            }
        }

        return $this->pdo;
    }

    /**
     * Ejecutar una consulta preparada
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch(PDOException $e) {
            if (DEBUG_MODE) {
                error_log("Error en consulta: " . $e->getMessage() . " SQL: " . $sql);
            }
            throw $e;
        }
    }

    /**
     * Obtener una sola fila
     */
    public function fetch($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }

    /**
     * Obtener múltiples filas
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Insertar y obtener ID
     */
    public function insert($sql, $params = []) {
        $this->query($sql, $params);
        return $this->pdo->lastInsertId();
    }

    /**
     * Contar filas afectadas
     */
    public function rowCount($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }

    /**
     * Iniciar transacción
     */
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }

    /**
     * Confirmar transacción
     */
    public function commit() {
        return $this->pdo->commit();
    }

    /**
     * Revertir transacción
     */
    public function rollback() {
        return $this->pdo->rollback();
    }

    /**
     * Registrar log en sistema
     */
    public function log($nivel, $modulo, $mensaje, $datos_adicionales = null) {
        try {
            $sql = "INSERT INTO logs_sistema (nivel, modulo, mensaje, datos_adicionales) VALUES (?, ?, ?, ?)";
            $this->query($sql, [
                $nivel,
                $modulo,
                $mensaje,
                $datos_adicionales ? json_encode($datos_adicionales) : null
            ]);
        } catch(Exception $e) {
            error_log("Error al registrar log: " . $e->getMessage());
        }
    }

    /**
     * Obtener configuración del sistema
     */
    public function getConfig($clave, $default = null) {
        try {
            $config = $this->fetch("SELECT valor FROM configuracion WHERE clave = ?", [$clave]);
            return $config ? $config['valor'] : $default;
        } catch(Exception $e) {
            return $default;
        }
    }

    /**
     * Actualizar configuración del sistema
     */
    public function setConfig($clave, $valor, $descripcion = null) {
        try {
            $existe = $this->fetch("SELECT id FROM configuracion WHERE clave = ?", [$clave]);

            if ($existe) {
                $sql = "UPDATE configuracion SET valor = ?" .
                       ($descripcion ? ", descripcion = ?" : "") .
                       " WHERE clave = ?";
                $params = $descripcion ? [$valor, $descripcion, $clave] : [$valor, $clave];
            } else {
                $sql = "INSERT INTO configuracion (clave, valor" .
                       ($descripcion ? ", descripcion" : "") .
                       ") VALUES (?, ?" .
                       ($descripcion ? ", ?" : "") . ")";
                $params = $descripcion ? [$clave, $valor, $descripcion] : [$clave, $valor];
            }

            return $this->query($sql, $params);
        } catch(Exception $e) {
            throw $e;
        }
    }
}

/**
 * Función auxiliar para respuestas JSON
 */
function jsonResponse($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

/**
 * Función auxiliar para validar entrada
 */
function validateInput($data, $required_fields = []) {
    $errors = [];

    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || trim($data[$field]) === '') {
            $errors[] = "El campo '$field' es requerido";
        }
    }

    return $errors;
}

/**
 * Función auxiliar para validar RUC
 */
function validateRUC($ruc) {
    $ruc = preg_replace('/[^0-9]/', '', $ruc);

    if (strlen($ruc) !== 11) {
        return ['valid' => false, 'message' => 'El RUC debe tener 11 dígitos'];
    }

    if (!ctype_digit($ruc)) {
        return ['valid' => false, 'message' => 'El RUC debe contener solo números'];
    }

    return ['valid' => true, 'ruc' => $ruc];
}

/**
 * Función auxiliar para validar WhatsApp
 */
function validateWhatsApp($numero) {
    $numero = preg_replace('/[^0-9]/', '', $numero);

    // Remover código de país si está presente
    if (substr($numero, 0, 2) === '51' && strlen($numero) === 11) {
        $numero = substr($numero, 2);
    }

    if (strlen($numero) !== 9) {
        return ['valid' => false, 'message' => 'El número debe tener 9 dígitos'];
    }

    return ['valid' => true, 'numero' => '51' . $numero];
}

/**
 * Manejo global de errores
 */
set_error_handler(function($severity, $message, $file, $line) {
    if (DEBUG_MODE) {
        error_log("PHP Error: $message in $file on line $line");
    }
});

set_exception_handler(function($exception) {
    if (DEBUG_MODE) {
        jsonResponse([
            'success' => false,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ], 500);
    } else {
        jsonResponse([
            'success' => false,
            'error' => 'Error interno del servidor'
        ], 500);
    }
});
?>