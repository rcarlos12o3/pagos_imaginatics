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
        case 'list_all':
            getClientesAll($database);
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
        case 'historial_servicio':
            getHistorialPagosServicio($database, $_GET['contrato_id'] ?? null);
            break;
        case 'dashboard_pagos':
            getDashboardPagosPendientes($database);
            break;
        case 'analizar_envios_pendientes':
            analizarEnviosPendientes($database);
            break;
        case 'estadisticas_recordatorios':
            getEstadisticasRecordatorios($database);
            break;
        case 'historial_recordatorios':
            getHistorialRecordatorios($database);
            break;
        case 'obtener_config_recordatorios':
            getConfigRecordatorios($database);
            break;
        case 'detalle_estado_recordatorios':
            getDetalleEstadoRecordatorios($database);
            break;
        default:
            jsonResponse(['success' => false, 'error' => 'Acción no válida'], 400);
    }
}

/**
 * Obtener lista de clientes (ADAPTADO A MULTI-SERVICIO)
 */
function getClientes($database) {
    try {
        $page = $_GET['page'] ?? 1;
        $limit = $_GET['limit'] ?? 50;
        $offset = ($page - 1) * $limit;

        // Consulta adaptada: usar resumen financiero de servicios contratados
        $sql = "SELECT
                    c.*,
                    r.total_servicios,
                    r.servicios_activos,
                    r.servicios_suspendidos,
                    r.monto_servicios_activos,
                    r.monto_pen,
                    r.monto_usd,
                    r.proximo_vencimiento,
                    r.periodo_proximo_vencimiento,
                    r.facturas_pendientes,
                    r.saldo_pendiente,
                    DATEDIFF(r.proximo_vencimiento, CURDATE()) as dias_restantes,
                    (SELECT MAX(hp.fecha_pago) FROM historial_pagos hp WHERE hp.cliente_id = c.id) as ultimo_pago,
                    CASE
                        WHEN r.servicios_activos = 0 THEN 'SIN_SERVICIOS'
                        WHEN r.proximo_vencimiento IS NULL THEN 'SIN_SERVICIOS'
                        WHEN DATEDIFF(r.proximo_vencimiento, CURDATE()) < 0 THEN 'VENCIDO'
                        WHEN DATEDIFF(r.proximo_vencimiento, CURDATE()) = 0 THEN 'VENCE_HOY'
                        WHEN DATEDIFF(r.proximo_vencimiento, CURDATE()) <= 3 THEN 'POR_VENCER'
                        ELSE 'AL_DIA'
                    END as estado_vencimiento
                FROM clientes c
                LEFT JOIN v_resumen_financiero_cliente r ON c.id = r.cliente_id
                WHERE c.activo = TRUE
                ORDER BY r.proximo_vencimiento ASC, c.fecha_creacion DESC
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
 * Obtener lista de TODOS los clientes (incluyendo deshabilitados)
 */
function getClientesAll($database) {
    try {
        $page = $_GET['page'] ?? 1;
        $limit = $_GET['limit'] ?? 1000; // Límite alto para mostrar todos
        $offset = ($page - 1) * $limit;

        // Consulta SIN filtro de activo
        $sql = "SELECT
                    c.*,
                    r.total_servicios,
                    r.servicios_activos,
                    r.servicios_suspendidos,
                    r.monto_servicios_activos,
                    r.proximo_vencimiento,
                    r.facturas_pendientes,
                    r.saldo_pendiente,
                    DATEDIFF(r.proximo_vencimiento, CURDATE()) as dias_restantes,
                    (SELECT MAX(hp.fecha_pago) FROM historial_pagos hp WHERE hp.cliente_id = c.id) as ultimo_pago,
                    CASE
                        WHEN r.servicios_activos = 0 THEN 'SIN_SERVICIOS'
                        WHEN r.proximo_vencimiento IS NULL THEN 'SIN_SERVICIOS'
                        WHEN DATEDIFF(r.proximo_vencimiento, CURDATE()) < 0 THEN 'VENCIDO'
                        WHEN DATEDIFF(r.proximo_vencimiento, CURDATE()) = 0 THEN 'VENCE_HOY'
                        WHEN DATEDIFF(r.proximo_vencimiento, CURDATE()) <= 3 THEN 'POR_VENCER'
                        ELSE 'AL_DIA'
                    END as estado_vencimiento
                FROM clientes c
                LEFT JOIN v_resumen_financiero_cliente r ON c.id = r.cliente_id
                ORDER BY c.activo DESC, r.proximo_vencimiento ASC, c.fecha_creacion DESC
                LIMIT ? OFFSET ?";

        $clientes = $database->fetchAll($sql, [$limit, $offset]);

        // Contar total (incluyendo deshabilitados)
        $total = $database->fetch("SELECT COUNT(*) as total FROM clientes")['total'];

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
 * Obtener un cliente específico (ADAPTADO A MULTI-SERVICIO)
 */
function getCliente($database, $id) {
    if (!$id) {
        jsonResponse(['success' => false, 'error' => 'ID requerido'], 400);
    }

    try {
        // Obtener datos básicos del cliente
        $sql = "SELECT c.*, r.*
                FROM clientes c
                LEFT JOIN v_resumen_financiero_cliente r ON c.id = r.cliente_id
                WHERE c.id = ? AND c.activo = TRUE";

        $cliente = $database->fetch($sql, [$id]);

        if (!$cliente) {
            jsonResponse(['success' => false, 'error' => 'Cliente no encontrado'], 404);
        }

        // Obtener servicios contratados del cliente
        $servicios = $database->fetchAll(
            "SELECT * FROM v_servicios_cliente WHERE cliente_id = ? ORDER BY fecha_vencimiento ASC",
            [$id]
        );

        // Decodificar configuración JSON de cada servicio
        foreach ($servicios as &$servicio) {
            if ($servicio['configuracion']) {
                $servicio['configuracion'] = json_decode($servicio['configuracion'], true);
            }
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

        $cliente['servicios_contratados'] = $servicios;
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
        // Solo incluir clientes que YA recibieron orden de pago para el periodo de vencimiento actual
        // Los recordatorios solo se envían si previamente se envió la orden de pago
        // Validación: debe existir una orden enviada DESPUÉS del último pago registrado
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
                 AND ew.fecha_envio >= COALESCE(
                     (SELECT MAX(hp.fecha_pago) FROM historial_pagos hp WHERE hp.cliente_id = c.id),
                     DATE_SUB(c.fecha_vencimiento, INTERVAL
                         CASE
                             WHEN c.tipo_servicio = 'mensual' THEN 1
                             WHEN c.tipo_servicio = 'trimestral' THEN 3
                             WHEN c.tipo_servicio = 'semestral' THEN 6
                             ELSE 12
                         END MONTH)
                 )) as orden_pago_enviada
                FROM clientes c
                WHERE c.activo = TRUE
                AND DATEDIFF(c.fecha_vencimiento, CURDATE()) <= ?
                AND EXISTS (
                    SELECT 1 FROM envios_whatsapp ew
                    WHERE ew.cliente_id = c.id
                    AND ew.tipo_envio = 'orden_pago'
                    AND ew.fecha_envio >= COALESCE(
                        (SELECT MAX(hp.fecha_pago) FROM historial_pagos hp WHERE hp.cliente_id = c.id),
                        DATE_SUB(c.fecha_vencimiento, INTERVAL
                            CASE
                                WHEN c.tipo_servicio = 'mensual' THEN 1
                                WHEN c.tipo_servicio = 'trimestral' THEN 3
                                WHEN c.tipo_servicio = 'semestral' THEN 6
                                ELSE 12
                            END MONTH)
                    )
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
    // Si se envía con FormData, los datos vienen en $_POST
    if (empty($input) && !empty($_POST)) {
        $input = $_POST;
    }

    $action = $input['action'] ?? $_GET['action'] ?? 'create';

    switch ($action) {
        case 'create':
            createCliente($database, $input);
            break;
        case 'update':
            updateCliente($database, $input);
            break;
        case 'delete':
            deleteCliente($database, $input);
            break;
        case 'enable':
            enableCliente($database, $input);
            break;
        case 'registrar_pago':
            registrarPago($database, $input);
            break;
        case 'actualizar_config_recordatorios':
            actualizarConfigRecordatorios($database, $input);
            break;
        default:
            jsonResponse(['success' => false, 'error' => 'Acción no válida'], 400);
    }
}

/**
 * Crear nuevo cliente
 */
function createCliente($database, $input) {
    // Validar campos requeridos (solo datos básicos del cliente)
    $required = ['ruc', 'razon_social', 'whatsapp'];
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
                    whatsapp = ?,
                    email = ?,
                    direccion = ?,
                    estado_sunat = ?,
                    fecha_actualizacion = NOW()
                WHERE id = ?",
                [
                    trim($input['razon_social']),
                    $whatsappValidation['numero'],
                    $input['email'] ?? null,
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
                ruc, razon_social, whatsapp, email, direccion, estado_sunat
            ) VALUES (?, ?, ?, ?, ?, ?)";

    $clienteId = $database->insert($sql, [
        $rucValidation['ruc'],
        trim($input['razon_social']),
        $whatsappValidation['numero'],
        $input['email'] ?? null,
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
 * Actualizar cliente existente (llamado desde POST)
 */
function updateCliente($database, $input) {
    // Validar ID
    if (!isset($input['id']) || empty($input['id'])) {
        jsonResponse(['success' => false, 'error' => 'ID requerido'], 400);
    }

    // Validar campos requeridos (solo datos básicos)
    $required = ['ruc', 'razon_social', 'whatsapp'];
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

    try {
        // Verificar que el cliente existe
        $cliente = $database->fetch(
            "SELECT * FROM clientes WHERE id = ? AND activo = TRUE",
            [$input['id']]
        );

        if (!$cliente) {
            jsonResponse(['success' => false, 'error' => 'Cliente no encontrado'], 404);
        }

        // Actualizar cliente
        $sql = "UPDATE clientes SET
                    ruc = ?,
                    razon_social = ?,
                    whatsapp = ?,
                    email = ?,
                    direccion = ?,
                    fecha_actualizacion = NOW()
                WHERE id = ?";

        $database->query($sql, [
            $rucValidation['ruc'],
            trim($input['razon_social']),
            $whatsappValidation['numero'],
            $input['email'] ?? null,
            $input['direccion'] ?? null,
            $input['id']
        ]);

        // Log del sistema
        $database->log('info', 'clientes', 'Cliente actualizado', [
            'id' => $input['id'],
            'ruc' => $rucValidation['ruc']
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
 * Eliminar cliente (desactivar)
 */
function deleteCliente($database, $input) {
    // Validar ID
    if (!isset($input['id']) || empty($input['id'])) {
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

        // Desactivar cliente (soft delete)
        $database->query(
            "UPDATE clientes SET activo = FALSE, fecha_actualizacion = NOW() WHERE id = ?",
            [$input['id']]
        );

        // Log del sistema
        $database->log('info', 'clientes', 'Cliente deshabilitado', [
            'id' => $input['id'],
            'ruc' => $cliente['ruc']
        ]);

        jsonResponse([
            'success' => true,
            'message' => 'Cliente deshabilitado exitosamente'
        ]);

    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Habilitar cliente (reactivar después de soft delete)
 */
function enableCliente($database, $input) {
    // Validar ID
    if (!isset($input['id']) || empty($input['id'])) {
        jsonResponse(['success' => false, 'error' => 'ID requerido'], 400);
    }

    try {
        // Verificar que el cliente existe y está deshabilitado
        $cliente = $database->fetch(
            "SELECT * FROM clientes WHERE id = ? AND activo = FALSE",
            [$input['id']]
        );

        if (!$cliente) {
            jsonResponse(['success' => false, 'error' => 'Cliente no encontrado o ya está habilitado'], 404);
        }

        // Reactivar cliente
        $database->query(
            "UPDATE clientes SET activo = TRUE, fecha_actualizacion = NOW() WHERE id = ?",
            [$input['id']]
        );

        // Log del sistema
        $database->log('info', 'clientes', 'Cliente habilitado', [
            'id' => $input['id'],
            'ruc' => $cliente['ruc']
        ]);

        jsonResponse([
            'success' => true,
            'message' => 'Cliente habilitado exitosamente'
        ]);

    } catch (Exception $e) {
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
 * Registrar pago de un cliente (ADAPTADO A MULTI-SERVICIO)
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

        // NUEVO: servicios_pagados es un array de IDs de servicios_contratados
        $serviciosPagados = $input['servicios_pagados'] ?? [];

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

        // Si no se especificaron servicios, buscar servicios activos del cliente
        if (empty($serviciosPagados)) {
            $servicios = $database->fetchAll(
                "SELECT id, periodo_facturacion, fecha_vencimiento
                 FROM servicios_contratados
                 WHERE cliente_id = ? AND estado = 'activo'
                 ORDER BY fecha_vencimiento ASC",
                [$clienteId]
            );
            $serviciosPagados = array_column($servicios, 'id');
        }

        // Registrar el pago con servicios pagados
        $pagoId = $database->insert(
            "INSERT INTO historial_pagos (
                cliente_id, monto_pagado, fecha_pago, metodo_pago,
                numero_operacion, banco, observaciones, registrado_por,
                servicios_pagados
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $clienteId, $montoPagado, $fechaPago, $metodoPago,
                $numeroOperacion, $banco, $observaciones, 'Sistema',
                json_encode($serviciosPagados)
            ]
        );

        // Actualizar cada servicio pagado
        $serviciosActualizados = [];
        foreach ($serviciosPagados as $servicioId) {
            // Obtener datos del servicio
            $servicio = $database->fetch(
                "SELECT * FROM servicios_contratados WHERE id = ? AND cliente_id = ?",
                [$servicioId, $clienteId]
            );

            if (!$servicio) {
                continue; // Saltar si el servicio no existe o no pertenece al cliente
            }

            // Calcular nueva fecha de vencimiento
            $fechaVencimientoActual = new DateTime($servicio['fecha_vencimiento']);
            $periodo = $servicio['periodo_facturacion'];

            switch ($periodo) {
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

            // Actualizar el servicio
            $database->query(
                "UPDATE servicios_contratados
                 SET fecha_vencimiento = ?,
                     fecha_ultima_factura = ?,
                     fecha_proximo_pago = ?,
                     estado = 'activo',
                     fecha_actualizacion = NOW()
                 WHERE id = ?",
                [$nuevaFechaVencimiento, $fechaPago, $nuevaFechaVencimiento, $servicioId]
            );

            $serviciosActualizados[] = [
                'servicio_id' => $servicioId,
                'nueva_fecha_vencimiento' => $nuevaFechaVencimiento
            ];
        }

        $database->commit();

        // Log del registro de pago
        $database->log('info', 'pagos', 'Pago registrado exitosamente (multi-servicio)', [
            'cliente_id' => $clienteId,
            'monto' => $montoPagado,
            'metodo' => $metodoPago,
            'servicios_actualizados' => count($serviciosActualizados)
        ]);

        jsonResponse([
            'success' => true,
            'message' => 'Pago registrado exitosamente',
            'data' => [
                'pago_id' => $pagoId,
                'servicios_actualizados' => $serviciosActualizados
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

/**
 * Obtener historial de pagos de un servicio específico
 */
function getHistorialPagosServicio($database, $contrato_id) {
    if (!$contrato_id) {
        jsonResponse(['success' => false, 'error' => 'ID de contrato requerido'], 400);
    }

    try {
        // Verificar que el servicio existe
        $servicio = $database->fetch(
            "SELECT sc.*, cs.nombre as servicio_nombre, c.razon_social, c.ruc
             FROM servicios_contratados sc
             JOIN catalogo_servicios cs ON sc.servicio_id = cs.id
             JOIN clientes c ON sc.cliente_id = c.id
             WHERE sc.id = ?",
            [$contrato_id]
        );

        if (!$servicio) {
            jsonResponse(['success' => false, 'error' => 'Servicio no encontrado'], 404);
        }

        // Obtener todos los pagos que incluyen este servicio
        $pagos = $database->fetchAll(
            "SELECT
                hp.id,
                hp.monto_pagado,
                hp.fecha_pago,
                hp.metodo_pago,
                hp.numero_operacion,
                hp.banco,
                hp.observaciones,
                hp.registrado_por,
                hp.fecha_registro,
                hp.servicios_pagados
             FROM historial_pagos hp
             WHERE hp.cliente_id = ?
             AND JSON_CONTAINS(hp.servicios_pagados, ?, '$')
             ORDER BY hp.fecha_pago DESC",
            [$servicio['cliente_id'], $contrato_id]
        );

        // Decodificar servicios_pagados para cada pago
        foreach ($pagos as &$pago) {
            if ($pago['servicios_pagados']) {
                $pago['servicios_pagados'] = json_decode($pago['servicios_pagados'], true);
            }
        }

        // Calcular estadísticas
        $total_pagado = 0;
        $cantidad_pagos = count($pagos);

        foreach ($pagos as $pago) {
            // Si el pago incluye múltiples servicios, calcular proporción
            if (is_array($pago['servicios_pagados']) && count($pago['servicios_pagados']) > 1) {
                // Dividir el monto entre los servicios pagados
                $total_pagado += $pago['monto_pagado'] / count($pago['servicios_pagados']);
            } else {
                $total_pagado += $pago['monto_pagado'];
            }
        }

        $promedio_pago = $cantidad_pagos > 0 ? $total_pagado / $cantidad_pagos : 0;

        jsonResponse([
            'success' => true,
            'data' => [
                'servicio' => $servicio,
                'pagos' => $pagos,
                'estadisticas' => [
                    'total_pagado' => round($total_pagado, 2),
                    'cantidad_pagos' => $cantidad_pagos,
                    'promedio_pago' => round($promedio_pago, 2),
                    'primer_pago' => $cantidad_pagos > 0 ? $pagos[count($pagos) - 1]['fecha_pago'] : null,
                    'ultimo_pago' => $cantidad_pagos > 0 ? $pagos[0]['fecha_pago'] : null
                ]
            ]
        ]);

    } catch (Exception $e) {
        $database->log('error', 'historial_servicio', 'Error obteniendo historial: ' . $e->getMessage());
        jsonResponse(['success' => false, 'error' => 'Error interno: ' . $e->getMessage()], 500);
    }
}

/**
 * Dashboard de Pagos Pendientes
 * Obtiene servicios próximos a vencer, vencidos y métricas financieras
 */
function getDashboardPagosPendientes($database) {
    try {
        $filtro = $_GET['filtro'] ?? 'todos'; // todos, proximo_vencer, vencido, muy_vencido
        $servicio_id = $_GET['servicio_id'] ?? null;
        $busqueda = $_GET['busqueda'] ?? '';

        // Fechas de referencia
        $hoy = date('Y-m-d');
        $proximos_7_dias = date('Y-m-d', strtotime('+7 days'));
        $hace_30_dias = date('Y-m-d', strtotime('-30 days'));

        // Query base
        $query = "
            SELECT
                sc.id as contrato_id,
                sc.cliente_id,
                c.razon_social,
                c.ruc,
                c.whatsapp,
                cs.nombre as servicio_nombre,
                cs.categoria as servicio_categoria,
                sc.precio,
                sc.moneda,
                sc.periodo_facturacion,
                sc.fecha_vencimiento,
                sc.estado,
                sc.notas,
                DATEDIFF(sc.fecha_vencimiento, CURDATE()) as dias_para_vencer,
                CASE
                    WHEN sc.fecha_vencimiento < CURDATE() AND DATEDIFF(CURDATE(), sc.fecha_vencimiento) > 30 THEN 'muy_vencido'
                    WHEN sc.fecha_vencimiento < CURDATE() THEN 'vencido'
                    WHEN DATEDIFF(sc.fecha_vencimiento, CURDATE()) <= 7 THEN 'proximo_vencer'
                    ELSE 'al_dia'
                END as urgencia
            FROM servicios_contratados sc
            JOIN clientes c ON sc.cliente_id = c.id
            JOIN catalogo_servicios cs ON sc.servicio_id = cs.id
            WHERE sc.estado IN ('activo', 'vencido')
            AND c.activo = TRUE
        ";

        $params = [];

        // Aplicar filtros
        if ($filtro !== 'todos') {
            if ($filtro === 'proximo_vencer') {
                $query .= " AND sc.fecha_vencimiento BETWEEN ? AND ? AND sc.fecha_vencimiento >= CURDATE()";
                $params[] = $hoy;
                $params[] = $proximos_7_dias;
            } elseif ($filtro === 'vencido') {
                $query .= " AND sc.fecha_vencimiento < ? AND DATEDIFF(?, sc.fecha_vencimiento) <= 30";
                $params[] = $hoy;
                $params[] = $hoy;
            } elseif ($filtro === 'muy_vencido') {
                $query .= " AND sc.fecha_vencimiento < ? AND DATEDIFF(?, sc.fecha_vencimiento) > 30";
                $params[] = $hoy;
                $params[] = $hoy;
            }
        }

        // Filtro por servicio
        if ($servicio_id) {
            $query .= " AND sc.servicio_id = ?";
            $params[] = $servicio_id;
        }

        // Búsqueda por cliente
        if ($busqueda) {
            $query .= " AND (c.razon_social LIKE ? OR c.ruc LIKE ?)";
            $params[] = "%{$busqueda}%";
            $params[] = "%{$busqueda}%";
        }

        $query .= " ORDER BY
            CASE
                WHEN sc.fecha_vencimiento < CURDATE() THEN 1
                WHEN DATEDIFF(sc.fecha_vencimiento, CURDATE()) <= 7 THEN 2
                ELSE 3
            END,
            sc.fecha_vencimiento ASC";

        $servicios = $database->fetchAll($query, $params);

        // Calcular métricas
        $metricas = $database->fetch("
            SELECT
                COUNT(CASE WHEN sc.fecha_vencimiento BETWEEN ? AND ? AND sc.fecha_vencimiento >= CURDATE() THEN 1 END) as proximos_vencer,
                COUNT(CASE WHEN sc.fecha_vencimiento < ? AND DATEDIFF(?, sc.fecha_vencimiento) <= 30 THEN 1 END) as vencidos,
                COUNT(CASE WHEN sc.fecha_vencimiento < ? AND DATEDIFF(?, sc.fecha_vencimiento) > 30 THEN 1 END) as muy_vencidos,
                SUM(CASE WHEN sc.fecha_vencimiento < ? THEN sc.precio ELSE 0 END) as monto_vencido_pen,
                SUM(CASE WHEN sc.fecha_vencimiento BETWEEN ? AND ? AND sc.moneda = 'PEN' THEN sc.precio ELSE 0 END) as monto_proximo_pen,
                SUM(CASE WHEN sc.fecha_vencimiento < ? AND sc.moneda = 'USD' THEN sc.precio ELSE 0 END) as monto_vencido_usd,
                SUM(CASE WHEN sc.fecha_vencimiento BETWEEN ? AND ? AND sc.moneda = 'USD' THEN sc.precio ELSE 0 END) as monto_proximo_usd,
                COUNT(DISTINCT sc.cliente_id) as clientes_afectados
            FROM servicios_contratados sc
            JOIN clientes c ON sc.cliente_id = c.id
            WHERE sc.estado IN ('activo', 'vencido')
            AND c.activo = TRUE
        ", [
            $hoy, $proximos_7_dias,  // proximos_vencer
            $hoy, $hoy,              // vencidos
            $hoy, $hoy,              // muy_vencidos
            $hoy,                    // monto_vencido_pen
            $hoy, $proximos_7_dias,  // monto_proximo_pen
            $hoy,                    // monto_vencido_usd
            $hoy, $proximos_7_dias   // monto_proximo_usd
        ]);

        // Listado de servicios para filtro
        $catalogo = $database->fetchAll("
            SELECT DISTINCT cs.id, cs.nombre, cs.categoria
            FROM catalogo_servicios cs
            JOIN servicios_contratados sc ON cs.id = sc.servicio_id
            WHERE sc.estado IN ('activo', 'vencido')
            ORDER BY cs.nombre
        ");

        jsonResponse([
            'success' => true,
            'data' => [
                'servicios' => $servicios,
                'metricas' => [
                    'proximos_vencer' => (int)$metricas['proximos_vencer'],
                    'vencidos' => (int)$metricas['vencidos'],
                    'muy_vencidos' => (int)$metricas['muy_vencidos'],
                    'clientes_afectados' => (int)$metricas['clientes_afectados'],
                    'monto_vencido' => [
                        'PEN' => round((float)$metricas['monto_vencido_pen'], 2),
                        'USD' => round((float)$metricas['monto_vencido_usd'], 2)
                    ],
                    'monto_proximo' => [
                        'PEN' => round((float)$metricas['monto_proximo_pen'], 2),
                        'USD' => round((float)$metricas['monto_proximo_usd'], 2)
                    ]
                ],
                'catalogo' => $catalogo
            ]
        ]);

    } catch (Exception $e) {
        $database->log('error', 'dashboard_pagos', 'Error: ' . $e->getMessage());
        jsonResponse(['success' => false, 'error' => 'Error interno: ' . $e->getMessage()], 500);
    }
}

/**
 * Analizar envíos pendientes según reglas de negocio
 * Determina qué empresas deben recibir órdenes de pago según:
 * - Periodicidad y ventana ideal de envío
 * - Estado actual (dentro/fuera del plazo)
 * - Órdenes atrasadas de periodos anteriores
 */
function analizarEnviosPendientes($database) {
    try {
        $hoy = new DateTime('now', new DateTimeZone('America/Lima'));

        // Obtener todos los servicios activos con datos del cliente
        $sql = "SELECT
                    sc.id as contrato_id,
                    sc.cliente_id,
                    c.razon_social,
                    c.ruc,
                    c.whatsapp,
                    sc.servicio_id,
                    cs.nombre as servicio_nombre,
                    sc.precio,
                    sc.moneda,
                    sc.periodo_facturacion,
                    sc.fecha_inicio,
                    sc.fecha_vencimiento,
                    sc.fecha_ultima_factura,
                    sc.estado,
                    DATEDIFF(sc.fecha_vencimiento, CURDATE()) as dias_para_vencer
                FROM servicios_contratados sc
                JOIN clientes c ON sc.cliente_id = c.id
                JOIN catalogo_servicios cs ON sc.servicio_id = cs.id
                WHERE sc.estado = 'activo' AND c.activo = TRUE
                ORDER BY dias_para_vencer ASC";

        $servicios = $database->fetchAll($sql);

        $resultados = [];

        foreach ($servicios as $servicio) {
            $analisis = analizarServicio($servicio, $hoy, $database);

            // Solo incluir si está en ventana de envío o fuera del plazo (pero aún válido)
            if ($analisis['debe_enviarse']) {
                $resultados[] = $analisis;
            }
        }

        jsonResponse([
            'success' => true,
            'data' => [
                'servicios' => $resultados,
                'total' => count($resultados),
                'fecha_analisis' => $hoy->format('Y-m-d H:i:s')
            ]
        ]);

    } catch (Exception $e) {
        $database->log('error', 'analizar_envios', 'Error: ' . $e->getMessage());
        jsonResponse(['success' => false, 'error' => 'Error interno: ' . $e->getMessage()], 500);
    }
}

/**
 * Analizar un servicio individual y determinar si debe recibir orden
 */
function analizarServicio($servicio, $hoy, $database) {
    $periodo = $servicio['periodo_facturacion'];
    $fechaVencimiento = new DateTime($servicio['fecha_vencimiento'], new DateTimeZone('America/Lima'));
    $fechaInicio = new DateTime($servicio['fecha_inicio'], new DateTimeZone('America/Lima'));

    // Calcular días hasta el vencimiento
    $diasHastaVencer = $fechaVencimiento->diff($hoy)->days;
    if ($fechaVencimiento < $hoy) {
        $diasHastaVencer = -$diasHastaVencer; // Negativo si ya venció
    }

    // Obtener días de anticipación desde tabla de reglas (centralizado)
    $regla = $database->fetch(
        "SELECT dias_anticipacion_op, dias_urgente, dias_critico, max_dias_mora
         FROM reglas_recordatorio_periodicidad
         WHERE periodo_facturacion = ?",
        [$periodo]
    );
    $diasAnticipacion = $regla ? (int)$regla['dias_anticipacion_op'] : 7; // Default 7 si no existe

    // Calcular fecha ideal de envío
    $fechaIdealEnvio = clone $fechaVencimiento;
    $fechaIdealEnvio->sub(new DateInterval("P{$diasAnticipacion}D"));

    // Determinar estado
    $estado = '';
    $debeEnviarse = false;

    if ($diasHastaVencer < 0) {
        // Ya venció - SIEMPRE debe enviarse (es orden atrasada)
        $estado = 'fuera_del_plazo';
        $debeEnviarse = true;
    } elseif ($hoy >= $fechaIdealEnvio && $hoy <= $fechaVencimiento) {
        // Estamos dentro de la ventana ideal
        $estado = 'dentro_del_plazo_ideal';
        $debeEnviarse = true;
    } elseif ($hoy > $fechaVencimiento) {
        // Ya pasó el vencimiento (mismo caso que diasHastaVencer < 0)
        $estado = 'fuera_del_plazo';
        $debeEnviarse = true;
    } else {
        // Aún no llega la fecha ideal
        $estado = 'pendiente';
        $debeEnviarse = false;
    }

    // Verificar si ya se envió orden para este periodo
    $yaEnviado = $database->fetch("
        SELECT COUNT(*) as total
        FROM envios_whatsapp
        WHERE cliente_id = ?
        AND tipo_envio = 'orden_pago'
        AND fecha_envio >= ?
        AND estado = 'enviado'
    ", [$servicio['cliente_id'], $servicio['fecha_ultima_factura'] ?? $fechaInicio->format('Y-m-d')]);

    if ($yaEnviado && $yaEnviado['total'] > 0) {
        $debeEnviarse = false;
        $estado = 'ya_enviado';
    }

    // Calcular siguiente vencimiento
    $siguienteVencimiento = calcularSiguienteVencimiento($fechaVencimiento, $periodo);

    // Construir explicación del cálculo
    $explicacion = construirExplicacion($periodo, $fechaInicio, $fechaVencimiento, $fechaIdealEnvio, $siguienteVencimiento, $diasAnticipacion);

    // Buscar órdenes atrasadas (periodos anteriores sin envío)
    $ordenesAtrasadas = buscarOrdenesAtrasadas($servicio, $database);

    return [
        'contrato_id' => $servicio['contrato_id'],
        'cliente_id' => $servicio['cliente_id'],
        'empresa' => $servicio['razon_social'],
        'ruc' => $servicio['ruc'],
        'whatsapp' => $servicio['whatsapp'],
        'servicio_nombre' => $servicio['servicio_nombre'],
        'periodicidad' => $periodo,
        'precio' => $servicio['precio'],
        'moneda' => $servicio['moneda'],
        'fecha_inicio' => $fechaInicio->format('d/m/Y'),
        'fecha_vencimiento_periodo_actual' => $fechaVencimiento->format('d/m/Y'),
        'fecha_ideal_envio' => $fechaIdealEnvio->format('d/m/Y'),
        'dias_anticipacion' => $diasAnticipacion,
        'dias_hasta_vencer' => $diasHastaVencer,
        'estado' => $estado,
        'debe_enviarse' => $debeEnviarse,
        'ordenes_atrasadas' => $ordenesAtrasadas,
        'siguiente_vencimiento' => $siguienteVencimiento->format('d/m/Y'),
        'explicacion' => $explicacion
    ];
}

/**
 * Calcular siguiente vencimiento según periodicidad
 */
function calcularSiguienteVencimiento($fechaVencimiento, $periodo) {
    $siguiente = clone $fechaVencimiento;

    switch ($periodo) {
        case 'mensual':
            // Último día del mes siguiente
            $siguiente->modify('last day of next month');
            break;
        case 'trimestral':
            $siguiente->add(new DateInterval('P3M'));
            $siguiente->modify('-1 day');
            break;
        case 'semestral':
            $siguiente->add(new DateInterval('P6M'));
            $siguiente->modify('-1 day');
            break;
        case 'anual':
            $siguiente->add(new DateInterval('P1Y'));
            $siguiente->modify('-1 day');
            break;
    }

    return $siguiente;
}

/**
 * Construir explicación detallada del cálculo
 */
function construirExplicacion($periodo, $fechaInicio, $fechaVencimiento, $fechaIdealEnvio, $siguienteVencimiento, $diasAnticipacion) {
    $explicaciones = [
        'mensual' => "Servicio mensual. Vence el último día del mes. Debe enviarse {$diasAnticipacion} días antes.",
        'trimestral' => "Servicio trimestral. Vence 3 meses después del inicio, el día anterior. Debe enviarse {$diasAnticipacion} días antes.",
        'semestral' => "Servicio semestral. Vence 6 meses después del inicio, el día anterior. Debe enviarse {$diasAnticipacion} días antes.",
        'anual' => "Servicio anual. Vence 12 meses después del inicio, el día anterior. Debe enviarse {$diasAnticipacion} días antes."
    ];

    $base = $explicaciones[$periodo] ?? '';

    return [
        'regla' => $base,
        'fecha_inicio' => $fechaInicio->format('d/m/Y'),
        'vencimiento_actual' => $fechaVencimiento->format('d/m/Y'),
        'fecha_ideal_envio' => $fechaIdealEnvio->format('d/m/Y'),
        'siguiente_vencimiento' => $siguienteVencimiento->format('d/m/Y')
    ];
}

/**
 * Buscar órdenes atrasadas (periodos anteriores sin envío)
 */
function buscarOrdenesAtrasadas($servicio, $database) {
    // Por ahora devolvemos vacío, esto se puede implementar más adelante
    // Requeriría revisar historial de envíos vs periodos transcurridos
    return [];
}

// ============================================
// API ENDPOINTS PARA DASHBOARD DE RECORDATORIOS
// ============================================

/**
 * Obtener estadísticas para el dashboard de recordatorios
 */
function getEstadisticasRecordatorios($database) {
    try {
        // Obtener configuración del sistema desde config_recordatorios
        $configSistemaActivo = $database->fetch(
            "SELECT valor FROM config_recordatorios WHERE clave = 'recordatorios_automaticos_activos'"
        );
        $sistemaActivo = ($configSistemaActivo && $configSistemaActivo['valor'] === 'true');

        // Contar servicios vencidos (fecha_vencimiento < HOY)
        $vencidos = $database->fetch(
            "SELECT COUNT(*) as total
             FROM servicios_contratados
             WHERE activo = 1 AND fecha_vencimiento < CURDATE()"
        );

        // Contar servicios que vencen hoy
        $venceHoy = $database->fetch(
            "SELECT COUNT(*) as total
             FROM servicios_contratados
             WHERE activo = 1 AND DATE(fecha_vencimiento) = CURDATE()"
        );

        // Contar servicios por vencer (próximos 7 días)
        $porVencer = $database->fetch(
            "SELECT COUNT(*) as total
             FROM servicios_contratados
             WHERE activo = 1
             AND fecha_vencimiento > CURDATE()
             AND fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)"
        );

        // Contar recordatorios enviados hoy
        $enviadosHoy = $database->fetch(
            "SELECT COUNT(*) as total
             FROM historial_recordatorios
             WHERE DATE(fecha_envio) = CURDATE()
             AND estado_envio = 'enviado'"
        );

        jsonResponse([
            'success' => true,
            'data' => [
                'sistema_activo' => $sistemaActivo,
                'vencidos' => (int)($vencidos['total'] ?? 0),
                'vence_hoy' => (int)($venceHoy['total'] ?? 0),
                'por_vencer' => (int)($porVencer['total'] ?? 0),
                'enviados_hoy' => (int)($enviadosHoy['total'] ?? 0)
            ]
        ]);
    } catch (Exception $e) {
        $database->log('error', 'recordatorios', 'Error al obtener estadísticas: ' . $e->getMessage());
        jsonResponse(['success' => false, 'error' => 'Error al obtener estadísticas'], 500);
    }
}

/**
 * Obtener historial de recordatorios enviados
 */
function getHistorialRecordatorios($database) {
    try {
        $limit = $_GET['limit'] ?? 50;

        $historial = $database->fetchAll(
            "SELECT
                hr.*,
                c.razon_social,
                c.ruc
             FROM historial_recordatorios hr
             INNER JOIN clientes c ON hr.cliente_id = c.id
             ORDER BY hr.fecha_envio DESC
             LIMIT ?",
            [$limit]
        );

        jsonResponse([
            'success' => true,
            'data' => $historial
        ]);
    } catch (Exception $e) {
        $database->log('error', 'recordatorios', 'Error al obtener historial: ' . $e->getMessage());
        jsonResponse(['success' => false, 'error' => 'Error al obtener historial'], 500);
    }
}

/**
 * Obtener configuración de recordatorios
 */
function getConfigRecordatorios($database) {
    try {
        $config = $database->fetchAll(
            "SELECT clave, valor, descripcion
             FROM config_recordatorios
             ORDER BY id"
        );

        jsonResponse([
            'success' => true,
            'data' => $config
        ]);
    } catch (Exception $e) {
        $database->log('error', 'recordatorios', 'Error al obtener configuración: ' . $e->getMessage());
        jsonResponse(['success' => false, 'error' => 'Error al obtener configuración'], 500);
    }
}

/**
 * Actualizar configuración de recordatorios
 */
function actualizarConfigRecordatorios($database, $input) {
    try {
        if (!isset($input['config']) || !is_array($input['config'])) {
            jsonResponse(['success' => false, 'error' => 'Configuración inválida'], 400);
            return;
        }

        $config = $input['config'];
        $actualizados = 0;

        foreach ($config as $clave => $valor) {
            $result = $database->query(
                "UPDATE config_recordatorios SET valor = ? WHERE clave = ?",
                [$valor, $clave]
            );

            if ($result) {
                $actualizados++;
            }
        }

        $database->log('info', 'recordatorios', "Configuración actualizada: $actualizados parámetros");

        jsonResponse([
            'success' => true,
            'message' => "Se actualizaron $actualizados parámetros correctamente",
            'actualizados' => $actualizados
        ]);
    } catch (Exception $e) {
        $database->log('error', 'recordatorios', 'Error al actualizar configuración: ' . $e->getMessage());
        jsonResponse(['success' => false, 'error' => 'Error al actualizar configuración'], 500);
    }
}

/**
 * Obtener detalle del estado del sistema de recordatorios
 */
function getDetalleEstadoRecordatorios($database) {
    try {
        // Obtener configuración desde config_recordatorios
        $config = $database->fetchAll("SELECT clave, valor FROM config_recordatorios");
        $configMap = [];
        foreach ($config as $item) {
            $configMap[$item['clave']] = $item['valor'];
        }

        $sistemaActivo = ($configMap['recordatorios_automaticos_activos'] === 'true');
        $horaEnvio = $configMap['hora_envio_automatico'] ?? '09:00';
        $diasMinimos = $configMap['dias_minimos_entre_recordatorios'] ?? '3';
        $maxPorMes = $configMap['max_recordatorios_mes'] ?? '8';

        // Obtener última ejecución del log
        $ultimaEjecucion = $database->fetch(
            "SELECT MAX(fecha_log) as ultima
             FROM logs_sistema
             WHERE modulo = 'recordatorios_auto'
             AND mensaje LIKE '%completada%'"
        );

        $ultimaEjecucionTexto = 'Sin registros';
        if ($ultimaEjecucion && $ultimaEjecucion['ultima']) {
            $fecha = new DateTime($ultimaEjecucion['ultima']);
            $ultimaEjecucionTexto = $fecha->format('d/m/Y H:i:s');
        }

        // Calcular próxima ejecución (hoy o mañana a la hora configurada)
        $ahora = new DateTime();
        $horaConfigParts = explode(':', $horaEnvio);
        $proximaEjecucion = new DateTime();
        $proximaEjecucion->setTime((int)$horaConfigParts[0], (int)$horaConfigParts[1]);

        // Si ya pasó la hora de hoy, la próxima es mañana
        if ($proximaEjecucion < $ahora) {
            $proximaEjecucion->modify('+1 day');
        }

        jsonResponse([
            'success' => true,
            'data' => [
                'sistema_activo' => $sistemaActivo,
                'ultima_ejecucion' => $ultimaEjecucionTexto,
                'proxima_ejecucion' => $proximaEjecucion->format('d/m/Y H:i:s'),
                'hora_envio' => $horaEnvio,
                'dias_minimos' => $diasMinimos,
                'max_por_mes' => $maxPorMes
            ]
        ]);
    } catch (Exception $e) {
        $database->log('error', 'recordatorios', 'Error al obtener detalle del estado: ' . $e->getMessage());
        jsonResponse(['success' => false, 'error' => 'Error al obtener detalle del estado'], 500);
    }
}
?>