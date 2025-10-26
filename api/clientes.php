<?php
/**
 * API CLIENTES
 * Manejo de clientes - CRUD completo
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
        case 'PUT':
            handlePut($database, $input);
            break;
        case 'DELETE':
            handleDelete($database, $input);
            break;
        default:
            jsonResponse(['success' => false, 'error' => 'Método no permitido'], 405);
    }
} catch (Exception $e) {
    $database->log('error', 'clientes_api', $e->getMessage());
    jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
}

/**
 * Manejar peticiones GET
 */
function handleGet($database) {
    $action = $_GET['action'] ?? 'list';

    switch ($action) {
        case 'list':
            getClientes($database);
            break;
        case 'get':
            getCliente($database, $_GET['id'] ?? null);
            break;
        case 'search':
            searchClientes($database, $_GET['q'] ?? '');
            break;
        case 'vencimientos':
            getVencimientos($database, $_GET['dias'] ?? 3);
            break;
        case 'stats':
            getEstadisticas($database);
            break;
        case 'get_config':
            getConfiguracionWhatsApp($database);
            break;
        case 'dashboard':
            getDashboard($database);
            break;
        case 'estadisticas':
            getEstadisticas($database);
            break;
        default:
            jsonResponse(['success' => false, 'error' => 'Acción no válida'], 400);
    }
}

/**
 * Obtener lista de clientes
 */
function getClientes($database) {
    try {
        $page = $_GET['page'] ?? 1;
        $limit = $_GET['limit'] ?? 50;
        $offset = ($page - 1) * $limit;

        // Consulta con paginación y verificación de pagos
        $sql = "SELECT c.*,
                DATEDIFF(c.fecha_vencimiento, CURDATE()) as dias_restantes,
                (SELECT MAX(hp.fecha_pago) FROM historial_pagos hp WHERE hp.cliente_id = c.id) as ultimo_pago,
                CASE
                    -- Si existe un pago en el periodo actual, está PAGADO
                    WHEN EXISTS (
                        SELECT 1 FROM historial_pagos hp 
                        WHERE hp.cliente_id = c.id 
                        AND (
                            (c.tipo_servicio = 'mensual' AND hp.fecha_pago >= DATE_SUB(c.fecha_vencimiento, INTERVAL 1 MONTH))
                            OR (c.tipo_servicio = 'trimestral' AND hp.fecha_pago >= DATE_SUB(c.fecha_vencimiento, INTERVAL 3 MONTH))
                            OR (c.tipo_servicio = 'semestral' AND hp.fecha_pago >= DATE_SUB(c.fecha_vencimiento, INTERVAL 6 MONTH))
                            OR ((c.tipo_servicio = 'anual' OR c.tipo_servicio IS NULL) AND hp.fecha_pago >= DATE_SUB(c.fecha_vencimiento, INTERVAL 12 MONTH))
                        )
                    ) THEN 'PAGADO'
                    WHEN DATEDIFF(c.fecha_vencimiento, CURDATE()) < 0 THEN 'VENCIDO'
                    WHEN DATEDIFF(c.fecha_vencimiento, CURDATE()) = 0 THEN 'VENCE_HOY'
                    WHEN DATEDIFF(c.fecha_vencimiento, CURDATE()) <= 3 THEN 'POR_VENCER'
                    ELSE 'AL_DIA'
                END as estado_vencimiento
                FROM clientes c
                WHERE c.activo = TRUE
                ORDER BY c.fecha_vencimiento ASC, c.fecha_creacion DESC
                LIMIT ? OFFSET ?";

        $clientes = $database->fetchAll($sql, [$limit, $offset]);

        // Contar total
        $total = $database->fetch("SELECT COUNT(*) as total FROM clientes WHERE activo = TRUE")['total'];

        jsonResponse([
            'success' => true,
            'data' => $clientes,
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
 * Obtener un cliente específico
 */
function getCliente($database, $id) {
    if (!$id) {
        jsonResponse(['success' => false, 'error' => 'ID requerido'], 400);
    }

    try {
        $sql = "SELECT c.*,
                DATEDIFF(c.fecha_vencimiento, CURDATE()) as dias_restantes,
                (SELECT MAX(hp.fecha_pago) FROM historial_pagos hp WHERE hp.cliente_id = c.id) as ultimo_pago,
                CASE
                    -- Si existe un pago en el periodo actual, está PAGADO
                    WHEN EXISTS (
                        SELECT 1 FROM historial_pagos hp 
                        WHERE hp.cliente_id = c.id 
                        AND (
                            (c.tipo_servicio = 'mensual' AND hp.fecha_pago >= DATE_SUB(c.fecha_vencimiento, INTERVAL 1 MONTH))
                            OR (c.tipo_servicio = 'trimestral' AND hp.fecha_pago >= DATE_SUB(c.fecha_vencimiento, INTERVAL 3 MONTH))
                            OR (c.tipo_servicio = 'semestral' AND hp.fecha_pago >= DATE_SUB(c.fecha_vencimiento, INTERVAL 6 MONTH))
                            OR ((c.tipo_servicio = 'anual' OR c.tipo_servicio IS NULL) AND hp.fecha_pago >= DATE_SUB(c.fecha_vencimiento, INTERVAL 12 MONTH))
                        )
                    ) THEN 'PAGADO'
                    WHEN DATEDIFF(c.fecha_vencimiento, CURDATE()) < 0 THEN 'VENCIDO'
                    WHEN DATEDIFF(c.fecha_vencimiento, CURDATE()) = 0 THEN 'VENCE_HOY'
                    WHEN DATEDIFF(c.fecha_vencimiento, CURDATE()) <= 3 THEN 'POR_VENCER'
                    ELSE 'AL_DIA'
                END as estado_vencimiento
                FROM clientes c
                WHERE c.id = ? AND c.activo = TRUE";

        $cliente = $database->fetch($sql, [$id]);

        if (!$cliente) {
            jsonResponse(['success' => false, 'error' => 'Cliente no encontrado'], 404);
        }

        // Obtener historial de envíos
        $envios = $database->fetchAll(
            "SELECT * FROM envios_whatsapp WHERE cliente_id = ? ORDER BY fecha_envio DESC LIMIT 10",
            [$id]
        );

        // Obtener historial de pagos
        $pagos = $database->fetchAll(
            "SELECT * FROM historial_pagos WHERE cliente_id = ? ORDER BY fecha_pago DESC",
            [$id]
        );

        $cliente['historial_envios'] = $envios;
        $cliente['historial_pagos'] = $pagos;

        jsonResponse(['success' => true, 'data' => $cliente]);

    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Buscar clientes
 */
function searchClientes($database, $query) {
    if (strlen($query) < 2) {
        jsonResponse(['success' => false, 'error' => 'Consulta muy corta'], 400);
    }

    try {
        $sql = "SELECT c.*,
                DATEDIFF(c.fecha_vencimiento, CURDATE()) as dias_restantes,
                (SELECT MAX(hp.fecha_pago) FROM historial_pagos hp WHERE hp.cliente_id = c.id) as ultimo_pago,
                CASE
                    -- Si existe un pago en el periodo actual, está PAGADO
                    WHEN EXISTS (
                        SELECT 1 FROM historial_pagos hp 
                        WHERE hp.cliente_id = c.id 
                        AND (
                            (c.tipo_servicio = 'mensual' AND hp.fecha_pago >= DATE_SUB(c.fecha_vencimiento, INTERVAL 1 MONTH))
                            OR (c.tipo_servicio = 'trimestral' AND hp.fecha_pago >= DATE_SUB(c.fecha_vencimiento, INTERVAL 3 MONTH))
                            OR (c.tipo_servicio = 'semestral' AND hp.fecha_pago >= DATE_SUB(c.fecha_vencimiento, INTERVAL 6 MONTH))
                            OR ((c.tipo_servicio = 'anual' OR c.tipo_servicio IS NULL) AND hp.fecha_pago >= DATE_SUB(c.fecha_vencimiento, INTERVAL 12 MONTH))
                        )
                    ) THEN 'PAGADO'
                    WHEN DATEDIFF(c.fecha_vencimiento, CURDATE()) < 0 THEN 'VENCIDO'
                    WHEN DATEDIFF(c.fecha_vencimiento, CURDATE()) = 0 THEN 'VENCE_HOY'
                    WHEN DATEDIFF(c.fecha_vencimiento, CURDATE()) <= 3 THEN 'POR_VENCER'
                    ELSE 'AL_DIA'
                END as estado_vencimiento
                FROM clientes c
                WHERE c.activo = TRUE
                AND (c.ruc LIKE ? OR c.razon_social LIKE ?)
                ORDER BY c.fecha_vencimiento ASC
                LIMIT 20";

        $searchTerm = "%$query%";
        $clientes = $database->fetchAll($sql, [$searchTerm, $searchTerm]);

        jsonResponse(['success' => true, 'data' => $clientes]);

    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Obtener clientes con vencimientos próximos
 */
function getVencimientos($database, $dias) {
    try {
        // Solo incluir clientes que YA recibieron orden de pago en el periodo actual
        // Los recordatorios solo se envían si previamente se envió la orden de pago
        $sql = "SELECT c.*,
                DATEDIFF(c.fecha_vencimiento, CURDATE()) as dias_restantes,
                (SELECT MAX(hp.fecha_pago) FROM historial_pagos hp WHERE hp.cliente_id = c.id) as ultimo_pago,
                CASE
                    WHEN DATEDIFF(c.fecha_vencimiento, CURDATE()) < 0 THEN 'VENCIDO'
                    WHEN DATEDIFF(c.fecha_vencimiento, CURDATE()) = 0 THEN 'VENCE_HOY'
                    ELSE 'POR_VENCER'
                END as estado_vencimiento,
                ABS(DATEDIFF(c.fecha_vencimiento, CURDATE())) as dias_absolutos,
                (SELECT COUNT(*) FROM envios_whatsapp ew
                 WHERE ew.cliente_id = c.id
                 AND ew.tipo_envio = 'orden_pago'
                 AND MONTH(ew.fecha_envio) = MONTH(CURDATE())
                 AND YEAR(ew.fecha_envio) = YEAR(CURDATE())) as orden_pago_enviada
                FROM clientes c
                WHERE c.activo = TRUE
                AND DATEDIFF(c.fecha_vencimiento, CURDATE()) <= ?
                AND EXISTS (
                    SELECT 1 FROM envios_whatsapp ew
                    WHERE ew.cliente_id = c.id
                    AND ew.tipo_envio = 'orden_pago'
                    AND MONTH(ew.fecha_envio) = MONTH(CURDATE())
                    AND YEAR(ew.fecha_envio) = YEAR(CURDATE())
                )
                ORDER BY c.fecha_vencimiento ASC";

        $clientes = $database->fetchAll($sql, [$dias]);

        // Agrupar por estado (sin categoria PAGADOS)
        $resultado = [
            'vencidos' => [],
            'vence_hoy' => [],
            'por_vencer' => [],
            'total' => count($clientes)
        ];

        foreach ($clientes as $cliente) {
            switch ($cliente['estado_vencimiento']) {
                case 'VENCIDO':
                    $resultado['vencidos'][] = $cliente;
                    break;
                case 'VENCE_HOY':
                    $resultado['vence_hoy'][] = $cliente;
                    break;
                case 'POR_VENCER':
                    $resultado['por_vencer'][] = $cliente;
                    break;
            }
        }

        jsonResponse(['success' => true, 'data' => $resultado]);

    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Obtener estadísticas generales
 */
function getEstadisticas($database) {
    try {
        // Conteo por estado
        $stats = $database->fetch("
            SELECT
                COUNT(*) as total_clientes,
                SUM(CASE WHEN DATEDIFF(fecha_vencimiento, CURDATE()) < 0 THEN 1 ELSE 0 END) as vencidos,
                SUM(CASE WHEN DATEDIFF(fecha_vencimiento, CURDATE()) = 0 THEN 1 ELSE 0 END) as vence_hoy,
                SUM(CASE WHEN DATEDIFF(fecha_vencimiento, CURDATE()) BETWEEN 1 AND 3 THEN 1 ELSE 0 END) as por_vencer,
                SUM(CASE WHEN DATEDIFF(fecha_vencimiento, CURDATE()) > 3 THEN 1 ELSE 0 END) as al_dia,
                SUM(monto) as monto_total,
                AVG(monto) as monto_promedio
            FROM clientes
            WHERE activo = TRUE
        ");

        // Envíos del mes
        $enviosDelMes = $database->fetch("
            SELECT
                COUNT(*) as total_envios,
                SUM(CASE WHEN estado = 'enviado' THEN 1 ELSE 0 END) as exitosos,
                SUM(CASE WHEN estado = 'error' THEN 1 ELSE 0 END) as fallidos
            FROM envios_whatsapp
            WHERE MONTH(fecha_envio) = MONTH(CURDATE())
            AND YEAR(fecha_envio) = YEAR(CURDATE())
        ");

        // Pagos del mes
        $pagosDelMes = $database->fetch("
            SELECT
                COUNT(*) as cantidad_pagos,
                SUM(monto_pagado) as total_pagado
            FROM historial_pagos
            WHERE MONTH(fecha_pago) = MONTH(CURDATE())
            AND YEAR(fecha_pago) = YEAR(CURDATE())
        ");

        jsonResponse([
            'success' => true,
            'data' => [
                'clientes' => $stats,
                'envios_mes' => $enviosDelMes,
                'pagos_mes' => $pagosDelMes
            ]
        ]);

    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Manejar peticiones POST (crear cliente)
 */
function handlePost($database, $input) {
    $action = $_GET['action'] ?? 'create';
    
    switch ($action) {
        case 'create':
            createCliente($database, $input);
            break;
        case 'registrar_pago':
            registrarPago($database, $input);
            break;
        default:
            jsonResponse(['success' => false, 'error' => 'Acción no válida'], 400);
    }
}

/**
 * Crear nuevo cliente
 */
function createCliente($database, $input) {
    // Validar campos requeridos
    $required = ['ruc', 'razon_social', 'monto', 'fecha_vencimiento', 'whatsapp'];
    $errors = validateInput($input, $required);

    if (!empty($errors)) {
        jsonResponse(['success' => false, 'errors' => $errors], 400);
    }

    // Validar RUC
    $rucValidation = validateRUC($input['ruc']);
    if (!$rucValidation['valid']) {
        jsonResponse(['success' => false, 'error' => $rucValidation['message']], 400);
    }

    // Validar WhatsApp
    $whatsappValidation = validateWhatsApp($input['whatsapp']);
    if (!$whatsappValidation['valid']) {
        jsonResponse(['success' => false, 'error' => $whatsappValidation['message']], 400);
    }

    // Validar monto
    if (!is_numeric($input['monto']) || $input['monto'] <= 0) {
        jsonResponse(['success' => false, 'error' => 'El monto debe ser un número positivo'], 400);
    }

    // Agregar validación del tipo_servicio
        $tiposPermitidos = ['mensual', 'trimestral', 'semestral', 'anual'];
    if (isset($input['tipo_servicio']) && !in_array($input['tipo_servicio'], $tiposPermitidos)) {
        jsonResponse(['success' => false, 'error' => 'Tipo de servicio debe ser: mensual, trimestral, semestral o anual'], 400);
    }

    try {
    // Verificar si el RUC ya existe (activo o inactivo)
    $existente = $database->fetch(
        "SELECT id, activo FROM clientes WHERE ruc = ?",
        [$rucValidation['ruc']]
    );

    if ($existente) {
        if ($existente['activo'] == 1) {
            // Cliente activo, es duplicado real
            jsonResponse(['success' => false, 'error' => 'Ya existe un cliente activo con este RUC'], 409);
        } else {
            // Cliente inactivo, reactivarlo actualizando sus datos
            $database->query(
                "UPDATE clientes SET
                    activo = TRUE,
                    razon_social = ?,
                    monto = ?,
                    fecha_vencimiento = ?,
                    whatsapp = ?,
                    tipo_servicio = ?,
                    direccion = ?,
                    estado_sunat = ?,
                    fecha_actualizacion = NOW()
                WHERE id = ?",
                [
                    trim($input['razon_social']),
                    $input['monto'],
                    $input['fecha_vencimiento'],
                    $whatsappValidation['numero'],
                    $input['tipo_servicio'] ?? 'anual',
                    $input['direccion'] ?? null,
                    $input['estado_sunat'] ?? null,
                    $existente['id']
                ]
            );

            // Log del sistema
            $database->log('info', 'clientes', 'Cliente reactivado', [
                'cliente_id' => $existente['id'],
                'ruc' => $rucValidation['ruc']
            ]);

            // Obtener cliente reactivado
            $cliente = $database->fetch(
                "SELECT * FROM clientes WHERE id = ?",
                [$existente['id']]
            );

            jsonResponse([
                'success' => true,
                'message' => 'Cliente reactivado exitosamente',
                'data' => $cliente
            ], 200);
            return; // Importante: salir de la función aquí
        }
    }



    // Si no existe, crear nuevo cliente
    $sql = "INSERT INTO clientes (
                ruc, razon_social, monto, fecha_vencimiento, whatsapp, tipo_servicio,
                direccion, estado_sunat
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $clienteId = $database->insert($sql, [
        $rucValidation['ruc'],
        trim($input['razon_social']),
        $input['monto'],
        $input['fecha_vencimiento'],
        $whatsappValidation['numero'],
        $input['tipo_servicio'] ?? 'anual',
        $input['direccion'] ?? null,
        $input['estado_sunat'] ?? null
    ]);

    // Log del sistema
    $database->log('info', 'clientes', 'Cliente creado', [
        'cliente_id' => $clienteId,
        'ruc' => $rucValidation['ruc']
    ]);

    // Obtener cliente creado
    $cliente = $database->fetch(
        "SELECT * FROM clientes WHERE id = ?",
        [$clienteId]
    );

    jsonResponse([
        'success' => true,
        'message' => 'Cliente creado exitosamente',
        'data' => $cliente
    ], 201);

} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
        jsonResponse(['success' => false, 'error' => 'Ya existe un cliente con este RUC'], 409);
    }
    throw $e;
}
}

/**
 * Manejar peticiones PUT (actualizar cliente)
 */
function handlePut($database, $input) {
    if (!isset($input['id'])) {
        jsonResponse(['success' => false, 'error' => 'ID requerido'], 400);
    }

    try {
        // Verificar que el cliente existe
        $cliente = $database->fetch(
            "SELECT * FROM clientes WHERE id = ? AND activo = TRUE",
            [$input['id']]
        );

        if (!$cliente) {
            jsonResponse(['success' => false, 'error' => 'Cliente no encontrado'], 404);
        }

        // Construir consulta dinámica
        $updates = [];
        $params = [];

        if (isset($input['ruc'])) {
            $rucValidation = validateRUC($input['ruc']);
            if (!$rucValidation['valid']) {
                jsonResponse(['success' => false, 'error' => $rucValidation['message']], 400);
            }
            $updates[] = "ruc = ?";
            $params[] = $rucValidation['ruc'];
        }

        if (isset($input['razon_social'])) {
            $updates[] = "razon_social = ?";
            $params[] = trim($input['razon_social']);
        }

        if (isset($input['monto'])) {
            if (!is_numeric($input['monto']) || $input['monto'] <= 0) {
                jsonResponse(['success' => false, 'error' => 'El monto debe ser un número positivo'], 400);
            }
            $updates[] = "monto = ?";
            $params[] = $input['monto'];
        }

        if (isset($input['fecha_vencimiento'])) {
            $updates[] = "fecha_vencimiento = ?";
            $params[] = $input['fecha_vencimiento'];
        }

        if (isset($input['whatsapp'])) {
            $whatsappValidation = validateWhatsApp($input['whatsapp']);
            if (!$whatsappValidation['valid']) {
                jsonResponse(['success' => false, 'error' => $whatsappValidation['message']], 400);
            }
            $updates[] = "whatsapp = ?";
            $params[] = $whatsappValidation['numero'];
        }

        if (isset($input['direccion'])) {
            $updates[] = "direccion = ?";
            $params[] = $input['direccion'];
        }

        if (isset($input['estado_sunat'])) {
            $updates[] = "estado_sunat = ?";
            $params[] = $input['estado_sunat'];
        }

        if (isset($input['tipo_servicio'])) {
    $tiposPermitidos = ['mensual', 'trimestral', 'semestral', 'anual'];
    if (!in_array($input['tipo_servicio'], $tiposPermitidos)) {
        jsonResponse(['success' => false, 'error' => 'Tipo de servicio debe ser: mensual, trimestral, semestral o anual'], 400);
    }
    $updates[] = "tipo_servicio = ?";
    $params[] = $input['tipo_servicio'];
}

        if (empty($updates)) {
            jsonResponse(['success' => false, 'error' => 'No hay campos para actualizar'], 400);
        }

        // Agregar ID al final
        $params[] = $input['id'];

        $sql = "UPDATE clientes SET " . implode(', ', $updates) . " WHERE id = ?";
        $database->query($sql, $params);

        // Log del sistema
        $database->log('info', 'clientes', 'Cliente actualizado', [
            'cliente_id' => $input['id'],
            'campos_actualizados' => array_keys($input)
        ]);

        // Obtener cliente actualizado
        $clienteActualizado = $database->fetch(
            "SELECT * FROM clientes WHERE id = ?",
            [$input['id']]
        );

        jsonResponse([
            'success' => true,
            'message' => 'Cliente actualizado exitosamente',
            'data' => $clienteActualizado
        ]);

    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Manejar peticiones DELETE (eliminar cliente)
 */
function handleDelete($database, $input) {
    if (!isset($input['id'])) {
        jsonResponse(['success' => false, 'error' => 'ID requerido'], 400);
    }

    try {
        // Verificar que el cliente existe
        $cliente = $database->fetch(
            "SELECT * FROM clientes WHERE id = ? AND activo = TRUE",
            [$input['id']]
        );

        if (!$cliente) {
            jsonResponse(['success' => false, 'error' => 'Cliente no encontrado'], 404);
        }

        // Eliminar lógicamente (cambiar activo a FALSE)
        $database->query(
            "UPDATE clientes SET activo = FALSE WHERE id = ?",
            [$input['id']]
        );

        // Log del sistema
        $database->log('info', 'clientes', 'Cliente eliminado', [
            'cliente_id' => $input['id'],
            'ruc' => $cliente['ruc']
        ]);

        jsonResponse([
            'success' => true,
            'message' => 'Cliente eliminado exitosamente'
        ]);

    } catch (Exception $e) {
        throw $e;
    }
}

function getConfiguracionWhatsApp($database) {
    try {
        $configuraciones = [];

        // Token WhatsApp
        $token = $database->fetch("SELECT valor FROM configuracion WHERE clave = 'token_whatsapp'");
        if ($token) {
            $configuraciones['token_whatsapp'] = $token['valor'];
        }

        // Instancia WhatsApp (SIN decodificar)
        $instancia = $database->fetch("SELECT valor FROM configuracion WHERE clave = 'instancia_whatsapp'");
        if ($instancia) {
            $configuraciones['instancia_whatsapp'] = $instancia['valor']; // ← Sin base64_decode()
        }

        // URL API
        $url = $database->fetch("SELECT valor FROM configuracion WHERE clave = 'api_url_whatsapp'");
        if ($url) {
            $configuraciones['api_url_whatsapp'] = $url['valor'];
        }

        jsonResponse([
            'success' => true,
            'config' => $configuraciones
        ]);

    } catch (Exception $e) {
        jsonResponse([
            'success' => false,
            'error' => 'Error obteniendo configuración: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Registrar pago de un cliente
 */
function registrarPago($database, $input) {
    try {
        // Validar datos requeridos
        $errors = validateInput($input, ['cliente_id', 'monto_pagado', 'fecha_pago', 'metodo_pago']);
        if (!empty($errors)) {
            jsonResponse(['success' => false, 'errors' => $errors], 400);
        }

        $clienteId = $input['cliente_id'];
        $montoPagado = floatval($input['monto_pagado']);
        $fechaPago = $input['fecha_pago'];
        $metodoPago = $input['metodo_pago'];
        $numeroOperacion = $input['numero_operacion'] ?? null;
        $banco = $input['banco'] ?? null;
        $observaciones = $input['observaciones'] ?? null;

        // Validar que el cliente existe
        $cliente = $database->fetch("SELECT * FROM clientes WHERE id = ? AND activo = TRUE", [$clienteId]);
        if (!$cliente) {
            jsonResponse(['success' => false, 'error' => 'Cliente no encontrado'], 404);
        }

        // Validar monto
        if ($montoPagado <= 0) {
            jsonResponse(['success' => false, 'error' => 'El monto debe ser mayor a 0'], 400);
        }

        // Validar método de pago
        $metodosValidos = ['transferencia', 'deposito', 'yape', 'plin', 'efectivo', 'otro'];
        if (!in_array($metodoPago, $metodosValidos)) {
            jsonResponse(['success' => false, 'error' => 'Método de pago inválido'], 400);
        }

        $database->beginTransaction();

        // Registrar el pago
        $pagoId = $database->insert(
            "INSERT INTO historial_pagos (cliente_id, monto_pagado, fecha_pago, metodo_pago, numero_operacion, banco, observaciones, registrado_por) 
             VALUES (?, ?, ?, ?, ?, ?, ?, 'Sistema')",
            [$clienteId, $montoPagado, $fechaPago, $metodoPago, $numeroOperacion, $banco, $observaciones]
        );

        // Usar fecha personalizada si se proporciona, sino calcular automáticamente
        if (!empty($input['nueva_fecha_vencimiento'])) {
            // Validar formato de fecha personalizada
            $fechaPersonalizada = DateTime::createFromFormat('Y-m-d', $input['nueva_fecha_vencimiento']);
            if (!$fechaPersonalizada) {
                throw new Exception('Formato de fecha de vencimiento inválido');
            }
            $nuevaFechaVencimiento = $input['nueva_fecha_vencimiento'];
        } else {
            // Calcular nueva fecha de vencimiento basada en la fecha actual de vencimiento
            // Se mantiene el día pero se avanza al siguiente periodo
            $fechaVencimientoActual = new DateTime($cliente['fecha_vencimiento']);
            $tipoServicio = $cliente['tipo_servicio'] ?? 'anual';
            
            // Añadir el periodo correspondiente a la fecha de vencimiento actual
            switch ($tipoServicio) {
                case 'mensual':
                    $fechaVencimientoActual->add(new DateInterval('P1M'));
                    break;
                case 'trimestral':
                    $fechaVencimientoActual->add(new DateInterval('P3M'));
                    break;
                case 'semestral':
                    $fechaVencimientoActual->add(new DateInterval('P6M'));
                    break;
                case 'anual':
                default:
                    $fechaVencimientoActual->add(new DateInterval('P1Y'));
                    break;
            }
            
            $nuevaFechaVencimiento = $fechaVencimientoActual->format('Y-m-d');
        }
        
        // Actualizar la fecha de vencimiento del cliente
        $database->query(
            "UPDATE clientes SET fecha_vencimiento = ?, fecha_actualizacion = NOW() WHERE id = ?",
            [$nuevaFechaVencimiento, $clienteId]
        );

        $database->commit();

        // Log del registro de pago
        $database->log('info', 'pagos', 'Pago registrado exitosamente', [
            'cliente_id' => $clienteId,
            'monto' => $montoPagado,
            'metodo' => $metodoPago,
            'nueva_fecha_vencimiento' => $nuevaFechaVencimiento
        ]);

        jsonResponse([
            'success' => true,
            'message' => 'Pago registrado exitosamente',
            'data' => [
                'pago_id' => $pagoId,
                'nueva_fecha_vencimiento' => $nuevaFechaVencimiento
            ]
        ]);

    } catch (Exception $e) {
        if ($database->pdo && $database->pdo->inTransaction()) {
            $database->rollback();
        }
        $database->log('error', 'pagos', 'Error registrando pago: ' . $e->getMessage());
        jsonResponse(['success' => false, 'error' => 'Error interno: ' . $e->getMessage()], 500);
    }
}

/**
 * Calcular nueva fecha de vencimiento según tipo de servicio
 */
function calcularNuevaFechaVencimiento($fechaPago, $tipoServicio) {
    $fecha = new DateTime($fechaPago);
    
    switch ($tipoServicio) {
        case 'mensual':
            $fecha->add(new DateInterval('P1M'));
            break;
        case 'trimestral':
            $fecha->add(new DateInterval('P3M'));
            break;
        case 'semestral':
            $fecha->add(new DateInterval('P6M'));
            break;
        case 'anual':
        default:
            $fecha->add(new DateInterval('P1Y'));
            break;
    }
    
    return $fecha->format('Y-m-d');
}
?>