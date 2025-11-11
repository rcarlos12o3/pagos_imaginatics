<?php
/**
 * API SERVICIOS
 * Gestión de catálogo y servicios contratados
 * Imaginatics Perú SAC - Sistema Multi-Servicio
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
    $database->log('error', 'servicios_api', $e->getMessage());
    jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
}

/**
 * Manejar peticiones GET
 */
function handleGet($database) {
    $action = $_GET['action'] ?? 'catalogo';

    switch ($action) {
        case 'catalogo':
            getCatalogo($database);
            break;
        case 'cliente':
            getServiciosCliente($database, $_GET['cliente_id'] ?? null);
            break;
        case 'contrato':
            getContratoDetalle($database, $_GET['id'] ?? null);
            break;
        case 'por_vencer':
            getServiciosPorVencer($database, $_GET['dias'] ?? 7);
            break;
        case 'categorias':
            getCategorias($database);
            break;
        case 'stats':
            getEstadisticas($database);
            break;
        default:
            jsonResponse(['success' => false, 'error' => 'Acción no válida'], 400);
    }
}

/**
 * Obtener catálogo de servicios disponibles
 */
function getCatalogo($database) {
    try {
        $categoria = $_GET['categoria'] ?? null;
        $activos_solo = isset($_GET['activos']) ? (bool)$_GET['activos'] : true;

        $sql = "SELECT
                    cs.*,
                    (SELECT COUNT(*) FROM servicios_contratados sc
                     WHERE sc.servicio_id = cs.id AND sc.estado = 'activo') as total_contratados
                FROM catalogo_servicios cs
                WHERE 1=1";

        $params = [];

        if ($activos_solo) {
            $sql .= " AND cs.activo = TRUE";
        }

        if ($categoria) {
            $sql .= " AND cs.categoria = ?";
            $params[] = $categoria;
        }

        $sql .= " ORDER BY cs.orden_visualizacion ASC, cs.nombre ASC";

        $servicios = $database->fetchAll($sql, $params);

        // Decodificar JSON de periodos_disponibles y configuracion_default
        foreach ($servicios as $key => $servicio) {
            if (!empty($servicio['periodos_disponibles'])) {
                $servicios[$key]['periodos_disponibles'] = json_decode($servicio['periodos_disponibles'], true);
            }
            if (!empty($servicio['configuracion_default'])) {
                $servicios[$key]['configuracion_default'] = json_decode($servicio['configuracion_default'], true);
            }
        }

        jsonResponse([
            'success' => true,
            'data' => $servicios,
            'total' => count($servicios)
        ]);

    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Obtener servicios contratados por un cliente
 */
function getServiciosCliente($database, $cliente_id) {
    if (!$cliente_id) {
        jsonResponse(['success' => false, 'error' => 'ID de cliente requerido'], 400);
    }

    try {
        // Usar vista optimizada
        $sql = "SELECT * FROM v_servicios_cliente WHERE cliente_id = ? ORDER BY fecha_vencimiento ASC";
        $servicios = $database->fetchAll($sql, [$cliente_id]);

        // Decodificar configuración JSON
        foreach ($servicios as &$servicio) {
            if ($servicio['configuracion']) {
                $servicio['configuracion'] = json_decode($servicio['configuracion'], true);
            }
        }

        // Obtener resumen financiero
        $resumen = $database->fetch(
            "SELECT * FROM v_resumen_financiero_cliente WHERE cliente_id = ?",
            [$cliente_id]
        );

        jsonResponse([
            'success' => true,
            'data' => [
                'servicios' => $servicios,
                'resumen' => $resumen
            ]
        ]);

    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Obtener detalle de un contrato específico
 */
function getContratoDetalle($database, $contrato_id) {
    if (!$contrato_id) {
        jsonResponse(['success' => false, 'error' => 'ID de contrato requerido'], 400);
    }

    try {
        $sql = "SELECT
                    sc.*,
                    cs.nombre as servicio_nombre,
                    cs.descripcion as servicio_descripcion,
                    cs.categoria,
                    c.ruc,
                    c.razon_social,
                    c.whatsapp,
                    c.email,
                    DATEDIFF(sc.fecha_vencimiento, CURDATE()) as dias_restantes
                FROM servicios_contratados sc
                INNER JOIN catalogo_servicios cs ON sc.servicio_id = cs.id
                INNER JOIN clientes c ON sc.cliente_id = c.id
                WHERE sc.id = ?";

        $contrato = $database->fetch($sql, [$contrato_id]);

        if (!$contrato) {
            jsonResponse(['success' => false, 'error' => 'Contrato no encontrado'], 404);
        }

        // Decodificar configuración
        if ($contrato['configuracion']) {
            $contrato['configuracion'] = json_decode($contrato['configuracion'], true);
        }

        // Obtener historial de envíos relacionados
        $envios = $database->fetchAll(
            "SELECT * FROM envios_whatsapp
             WHERE servicio_contratado_id = ?
             ORDER BY fecha_envio DESC
             LIMIT 10",
            [$contrato_id]
        );

        $contrato['historial_envios'] = $envios;

        jsonResponse([
            'success' => true,
            'data' => $contrato
        ]);

    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Obtener servicios próximos a vencer
 */
function getServiciosPorVencer($database, $dias) {
    try {
        $sql = "SELECT * FROM v_servicios_por_vencer
                WHERE dias_restantes <= ?
                ORDER BY fecha_vencimiento ASC";

        $servicios = $database->fetchAll($sql, [$dias]);

        // Agrupar por cliente
        $por_cliente = [];
        foreach ($servicios as $servicio) {
            $cliente_id = $servicio['cliente_id'];
            if (!isset($por_cliente[$cliente_id])) {
                $por_cliente[$cliente_id] = [
                    'cliente_id' => $cliente_id,
                    'ruc' => $servicio['ruc'],
                    'razon_social' => $servicio['razon_social'],
                    'whatsapp' => $servicio['whatsapp'],
                    'servicios' => []
                ];
            }
            $por_cliente[$cliente_id]['servicios'][] = $servicio;
        }

        jsonResponse([
            'success' => true,
            'data' => [
                'total_servicios' => count($servicios),
                'total_clientes' => count($por_cliente),
                'por_cliente' => array_values($por_cliente),
                'listado_completo' => $servicios
            ]
        ]);

    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Obtener categorías disponibles
 */
function getCategorias($database) {
    try {
        $sql = "SELECT
                    categoria,
                    COUNT(*) as total_servicios,
                    SUM(CASE WHEN activo = TRUE THEN 1 ELSE 0 END) as servicios_activos,
                    MIN(precio_base) as precio_minimo,
                    MAX(precio_base) as precio_maximo
                FROM catalogo_servicios
                GROUP BY categoria
                ORDER BY categoria";

        $categorias = $database->fetchAll($sql);

        jsonResponse([
            'success' => true,
            'data' => $categorias
        ]);

    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Obtener estadísticas generales
 */
function getEstadisticas($database) {
    try {
        // Estadísticas del catálogo
        $catalogo = $database->fetch("
            SELECT
                COUNT(*) as total_servicios,
                SUM(CASE WHEN activo = TRUE THEN 1 ELSE 0 END) as servicios_activos,
                COUNT(DISTINCT categoria) as total_categorias
            FROM catalogo_servicios
        ");

        // Estadísticas de contratos
        $contratos = $database->fetch("
            SELECT
                COUNT(*) as total_contratos,
                SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) as contratos_activos,
                SUM(CASE WHEN estado = 'suspendido' THEN 1 ELSE 0 END) as contratos_suspendidos,
                SUM(CASE WHEN estado = 'vencido' THEN 1 ELSE 0 END) as contratos_vencidos,
                SUM(CASE WHEN estado = 'cancelado' THEN 1 ELSE 0 END) as contratos_cancelados,
                COUNT(DISTINCT cliente_id) as clientes_con_servicios
            FROM servicios_contratados
        ");

        // Ingresos estimados (solo contratos activos)
        $ingresos = $database->fetch("
            SELECT
                SUM(CASE WHEN periodo_facturacion = 'mensual' THEN precio ELSE 0 END) as mensual_pen,
                SUM(CASE WHEN periodo_facturacion = 'trimestral' THEN precio/3 ELSE 0 END) as trimestral_mensual_pen,
                SUM(CASE WHEN periodo_facturacion = 'semestral' THEN precio/6 ELSE 0 END) as semestral_mensual_pen,
                SUM(CASE WHEN periodo_facturacion = 'anual' THEN precio/12 ELSE 0 END) as anual_mensual_pen
            FROM servicios_contratados
            WHERE estado = 'activo' AND moneda = 'PEN'
        ");

        $ingreso_mensual_estimado =
            ($ingresos['mensual_pen'] ?? 0) +
            ($ingresos['trimestral_mensual_pen'] ?? 0) +
            ($ingresos['semestral_mensual_pen'] ?? 0) +
            ($ingresos['anual_mensual_pen'] ?? 0);

        jsonResponse([
            'success' => true,
            'data' => [
                'catalogo' => $catalogo,
                'contratos' => $contratos,
                'ingreso_mensual_estimado' => round($ingreso_mensual_estimado, 2)
            ]
        ]);

    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Manejar peticiones POST (crear/contratar)
 */
function handlePost($database, $input) {
    $action = $_GET['action'] ?? 'contratar';

    switch ($action) {
        case 'contratar':
            contratarServicio($database, $input);
            break;
        case 'suspender':
            suspenderServicio($database, $input);
            break;
        case 'reactivar':
            reactivarServicio($database, $input);
            break;
        default:
            jsonResponse(['success' => false, 'error' => 'Acción no válida'], 400);
    }
}

/**
 * Contratar un nuevo servicio para un cliente
 */
function contratarServicio($database, $input) {
    // Validar campos requeridos
    $required = ['cliente_id', 'servicio_id', 'precio', 'periodo_facturacion', 'fecha_inicio', 'fecha_vencimiento'];
    $errors = validateInput($input, $required);

    if (!empty($errors)) {
        jsonResponse(['success' => false, 'errors' => $errors], 400);
    }

    try {
        $database->beginTransaction();

        // Verificar que el cliente existe
        $cliente = $database->fetch(
            "SELECT id, razon_social FROM clientes WHERE id = ? AND activo = TRUE",
            [$input['cliente_id']]
        );

        if (!$cliente) {
            throw new Exception('Cliente no encontrado o inactivo');
        }

        // Verificar que el servicio existe
        $servicio = $database->fetch(
            "SELECT id, nombre, moneda FROM catalogo_servicios WHERE id = ?",
            [$input['servicio_id']]
        );

        if (!$servicio) {
            throw new Exception('Servicio no encontrado');
        }

        // Validar periodo
        $periodos_validos = ['mensual', 'trimestral', 'semestral', 'anual'];
        if (!in_array($input['periodo_facturacion'], $periodos_validos)) {
            throw new Exception('Periodo de facturación no válido');
        }

        // Validar moneda
        $moneda = $input['moneda'] ?? $servicio['moneda'];
        if (!in_array($moneda, ['PEN', 'USD'])) {
            throw new Exception('Moneda no válida');
        }

        // Insertar servicio contratado
        $sql = "INSERT INTO servicios_contratados (
                    cliente_id, servicio_id, precio, moneda, periodo_facturacion,
                    fecha_inicio, fecha_vencimiento, fecha_proximo_pago,
                    estado, auto_renovacion, configuracion, notas, usuario_creacion
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $contrato_id = $database->insert($sql, [
            $input['cliente_id'],
            $input['servicio_id'],
            $input['precio'],
            $moneda,
            $input['periodo_facturacion'],
            $input['fecha_inicio'],
            $input['fecha_vencimiento'],
            $input['fecha_vencimiento'], // fecha_proximo_pago
            'activo',
            $input['auto_renovacion'] ?? true,
            isset($input['configuracion']) ? json_encode($input['configuracion']) : null,
            $input['notas'] ?? null,
            $input['usuario_creacion'] ?? 'Sistema'
        ]);

        $database->commit();

        // Log del sistema
        $database->log('info', 'servicios', 'Servicio contratado', [
            'contrato_id' => $contrato_id,
            'cliente_id' => $input['cliente_id'],
            'servicio_id' => $input['servicio_id'],
            'precio' => $input['precio'],
            'moneda' => $moneda
        ]);

        // Obtener el contrato creado con todos los datos
        $contrato = $database->fetch("SELECT * FROM v_servicios_cliente WHERE contrato_id = ?", [$contrato_id]);

        jsonResponse([
            'success' => true,
            'message' => 'Servicio contratado exitosamente',
            'data' => $contrato
        ], 201);

    } catch (Exception $e) {
        if ($database->pdo && $database->pdo->inTransaction()) {
            $database->rollback();
        }
        throw $e;
    }
}

/**
 * Suspender un servicio contratado
 */
function suspenderServicio($database, $input) {
    if (!isset($input['contrato_id'])) {
        jsonResponse(['success' => false, 'error' => 'ID de contrato requerido'], 400);
    }

    try {
        $database->beginTransaction();

        // Verificar que el contrato existe y está activo
        $contrato = $database->fetch(
            "SELECT * FROM servicios_contratados WHERE id = ?",
            [$input['contrato_id']]
        );

        if (!$contrato) {
            throw new Exception('Contrato no encontrado');
        }

        if ($contrato['estado'] !== 'activo') {
            throw new Exception('Solo se pueden suspender contratos activos');
        }

        // Suspender el servicio
        $database->query(
            "UPDATE servicios_contratados
             SET estado = 'suspendido',
                 motivo_suspension = ?,
                 fecha_actualizacion = NOW()
             WHERE id = ?",
            [
                $input['motivo'] ?? 'Suspendido manualmente',
                $input['contrato_id']
            ]
        );

        $database->commit();

        // Log
        $database->log('info', 'servicios', 'Servicio suspendido', [
            'contrato_id' => $input['contrato_id'],
            'motivo' => $input['motivo'] ?? 'No especificado'
        ]);

        jsonResponse([
            'success' => true,
            'message' => 'Servicio suspendido exitosamente'
        ]);

    } catch (Exception $e) {
        if ($database->pdo && $database->pdo->inTransaction()) {
            $database->rollback();
        }
        throw $e;
    }
}

/**
 * Reactivar un servicio suspendido
 */
function reactivarServicio($database, $input) {
    if (!isset($input['contrato_id'])) {
        jsonResponse(['success' => false, 'error' => 'ID de contrato requerido'], 400);
    }

    try {
        $database->beginTransaction();

        // Verificar que el contrato existe
        $contrato = $database->fetch(
            "SELECT * FROM servicios_contratados WHERE id = ?",
            [$input['contrato_id']]
        );

        if (!$contrato) {
            throw new Exception('Contrato no encontrado');
        }

        if (!in_array($contrato['estado'], ['suspendido', 'vencido'])) {
            throw new Exception('Solo se pueden reactivar contratos suspendidos o vencidos');
        }

        // Reactivar el servicio
        $nueva_fecha_vencimiento = $input['nueva_fecha_vencimiento'] ?? $contrato['fecha_vencimiento'];

        $database->query(
            "UPDATE servicios_contratados
             SET estado = 'activo',
                 motivo_suspension = NULL,
                 fecha_vencimiento = ?,
                 fecha_actualizacion = NOW()
             WHERE id = ?",
            [$nueva_fecha_vencimiento, $input['contrato_id']]
        );

        $database->commit();

        // Log
        $database->log('info', 'servicios', 'Servicio reactivado', [
            'contrato_id' => $input['contrato_id'],
            'nueva_fecha_vencimiento' => $nueva_fecha_vencimiento
        ]);

        jsonResponse([
            'success' => true,
            'message' => 'Servicio reactivado exitosamente'
        ]);

    } catch (Exception $e) {
        if ($database->pdo && $database->pdo->inTransaction()) {
            $database->rollback();
        }
        throw $e;
    }
}

/**
 * Manejar peticiones PUT (actualizar)
 */
function handlePut($database, $input) {
    if (!isset($input['contrato_id'])) {
        jsonResponse(['success' => false, 'error' => 'ID de contrato requerido'], 400);
    }

    try {
        $database->beginTransaction();

        // Verificar que el contrato existe
        $contrato = $database->fetch(
            "SELECT * FROM servicios_contratados WHERE id = ?",
            [$input['contrato_id']]
        );

        if (!$contrato) {
            throw new Exception('Contrato no encontrado');
        }

        // Construir consulta dinámica
        $updates = [];
        $params = [];

        if (isset($input['precio'])) {
            $updates[] = "precio = ?";
            $params[] = $input['precio'];
        }

        if (isset($input['moneda']) && in_array($input['moneda'], ['PEN', 'USD'])) {
            $updates[] = "moneda = ?";
            $params[] = $input['moneda'];
        }

        if (isset($input['periodo_facturacion'])) {
            $periodos_validos = ['mensual', 'trimestral', 'semestral', 'anual'];
            if (!in_array($input['periodo_facturacion'], $periodos_validos)) {
                throw new Exception('Periodo de facturación no válido');
            }
            $updates[] = "periodo_facturacion = ?";
            $params[] = $input['periodo_facturacion'];
        }

        if (isset($input['fecha_vencimiento'])) {
            $updates[] = "fecha_vencimiento = ?";
            $params[] = $input['fecha_vencimiento'];
        }

        if (isset($input['auto_renovacion'])) {
            $updates[] = "auto_renovacion = ?";
            $params[] = (bool)$input['auto_renovacion'];
        }

        if (isset($input['configuracion'])) {
            $updates[] = "configuracion = ?";
            $params[] = json_encode($input['configuracion']);
        }

        if (isset($input['notas'])) {
            $updates[] = "notas = ?";
            $params[] = $input['notas'];
        }

        if (empty($updates)) {
            throw new Exception('No hay campos para actualizar');
        }

        // Agregar ID al final
        $params[] = $input['contrato_id'];

        $sql = "UPDATE servicios_contratados SET " . implode(', ', $updates) . ", fecha_actualizacion = NOW() WHERE id = ?";
        $database->query($sql, $params);

        $database->commit();

        // Log
        $database->log('info', 'servicios', 'Servicio actualizado', [
            'contrato_id' => $input['contrato_id'],
            'campos_actualizados' => array_keys($input)
        ]);

        // Obtener contrato actualizado
        $contratoActualizado = $database->fetch("SELECT * FROM v_servicios_cliente WHERE contrato_id = ?", [$input['contrato_id']]);

        jsonResponse([
            'success' => true,
            'message' => 'Servicio actualizado exitosamente',
            'data' => $contratoActualizado
        ]);

    } catch (Exception $e) {
        if ($database->pdo && $database->pdo->inTransaction()) {
            $database->rollback();
        }
        throw $e;
    }
}

/**
 * Manejar peticiones DELETE (cancelar)
 */
function handleDelete($database, $input) {
    if (!isset($input['contrato_id'])) {
        jsonResponse(['success' => false, 'error' => 'ID de contrato requerido'], 400);
    }

    try {
        $database->beginTransaction();

        // Verificar que el contrato existe
        $contrato = $database->fetch(
            "SELECT * FROM servicios_contratados WHERE id = ?",
            [$input['contrato_id']]
        );

        if (!$contrato) {
            throw new Exception('Contrato no encontrado');
        }

        // Cancelar (no eliminar físicamente)
        $database->query(
            "UPDATE servicios_contratados
             SET estado = 'cancelado',
                 motivo_suspension = ?,
                 fecha_actualizacion = NOW()
             WHERE id = ?",
            [
                $input['motivo'] ?? 'Cancelado manualmente',
                $input['contrato_id']
            ]
        );

        $database->commit();

        // Log
        $database->log('info', 'servicios', 'Servicio cancelado', [
            'contrato_id' => $input['contrato_id'],
            'motivo' => $input['motivo'] ?? 'No especificado'
        ]);

        jsonResponse([
            'success' => true,
            'message' => 'Servicio cancelado exitosamente'
        ]);

    } catch (Exception $e) {
        if ($database->pdo && $database->pdo->inTransaction()) {
            $database->rollback();
        }
        throw $e;
    }
}
?>
