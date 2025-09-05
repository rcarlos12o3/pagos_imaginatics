<?php
/**
 * API para gestión de pagos
 * Proporciona endpoints CRUD para historial de pagos
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

// Manejar peticiones OPTIONS para CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    $database = new Database();
    $database->connect(); // Asegurar conexión
    $action = $_REQUEST['action'] ?? '';

    switch ($action) {
        case 'list':
            listarPagos($database);
            break;
        
        case 'get':
            obtenerPago($database, $_GET['id'] ?? null);
            break;
        
        case 'create':
            $input = json_decode(file_get_contents('php://input'), true);
            crearPago($database, $input);
            break;
        
        case 'update':
            $input = json_decode(file_get_contents('php://input'), true);
            actualizarPago($database, $_GET['id'] ?? null, $input);
            break;
        
        case 'delete':
            eliminarPago($database, $_GET['id'] ?? null);
            break;
        
        case 'by_cliente':
            obtenerPagosPorCliente($database, $_GET['cliente_id'] ?? null);
            break;
        
        case 'estadisticas':
            obtenerEstadisticas($database);
            break;
        
        default:
            jsonResponse(['success' => false, 'error' => 'Acción no válida'], 400);
    }

} catch (Exception $e) {
    $database->log('error', 'pagos', 'Error en API de pagos: ' . $e->getMessage());
    jsonResponse(['success' => false, 'error' => 'Error del servidor: ' . $e->getMessage()], 500);
}

/**
 * Listar todos los pagos con información del cliente
 */
function listarPagos($database) {
    try {
        $page = $_GET['page'] ?? 1;
        $limit = $_GET['limit'] ?? 50;
        $offset = ($page - 1) * $limit;
        $mes = $_GET['mes'] ?? null;
        $anio = $_GET['anio'] ?? null;
        
        $whereClause = "1=1";
        $params = [];
        
        if ($mes && $anio) {
            $whereClause .= " AND MONTH(hp.fecha_pago) = ? AND YEAR(hp.fecha_pago) = ?";
            $params[] = $mes;
            $params[] = $anio;
        } elseif ($anio) {
            $whereClause .= " AND YEAR(hp.fecha_pago) = ?";
            $params[] = $anio;
        }
        
        // Obtener pagos con información del cliente
        $sql = "SELECT hp.*, 
                c.razon_social, 
                c.ruc, 
                c.whatsapp,
                c.tipo_servicio
                FROM historial_pagos hp
                INNER JOIN clientes c ON hp.cliente_id = c.id
                WHERE $whereClause
                ORDER BY hp.fecha_pago DESC, hp.id DESC
                LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $pagos = $database->fetchAll($sql, $params);
        
        // Contar total
        $countSql = "SELECT COUNT(*) as total 
                     FROM historial_pagos hp
                     INNER JOIN clientes c ON hp.cliente_id = c.id
                     WHERE $whereClause";
        
        $countParams = array_slice($params, 0, -2); // Excluir limit y offset
        $total = $database->fetch($countSql, $countParams)['total'];
        
        // Calcular estadísticas
        $statsSql = "SELECT 
                     COUNT(*) as total_pagos,
                     SUM(hp.monto_pagado) as monto_total,
                     AVG(hp.monto_pagado) as monto_promedio
                     FROM historial_pagos hp
                     INNER JOIN clientes c ON hp.cliente_id = c.id
                     WHERE $whereClause";
        
        $stats = $database->fetch($statsSql, $countParams);
        
        jsonResponse([
            'success' => true,
            'data' => $pagos,
            'pagination' => [
                'page' => (int)$page,
                'limit' => (int)$limit,
                'total' => (int)$total,
                'pages' => ceil($total / $limit)
            ],
            'estadisticas' => $stats
        ]);
        
    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Obtener un pago específico
 */
function obtenerPago($database, $id) {
    if (!$id) {
        jsonResponse(['success' => false, 'error' => 'ID requerido'], 400);
    }
    
    try {
        $sql = "SELECT hp.*, 
                c.razon_social, 
                c.ruc, 
                c.whatsapp,
                c.tipo_servicio,
                c.monto as monto_servicio
                FROM historial_pagos hp
                INNER JOIN clientes c ON hp.cliente_id = c.id
                WHERE hp.id = ?";
        
        $pago = $database->fetch($sql, [$id]);
        
        if (!$pago) {
            jsonResponse(['success' => false, 'error' => 'Pago no encontrado'], 404);
        }
        
        jsonResponse(['success' => true, 'data' => $pago]);
        
    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Crear un nuevo pago
 */
function crearPago($database, $input) {
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
        
        // Actualizar fecha de vencimiento del cliente si es necesario
        if (isset($input['actualizar_vencimiento']) && $input['actualizar_vencimiento']) {
            $fechaVencimientoActual = new DateTime($cliente['fecha_vencimiento']);
            $tipoServicio = $cliente['tipo_servicio'] ?? 'anual';
            
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
            
            $database->query(
                "UPDATE clientes SET fecha_vencimiento = ?, fecha_actualizacion = NOW() WHERE id = ?",
                [$nuevaFechaVencimiento, $clienteId]
            );
        }
        
        $database->commit();
        
        // Log del registro de pago
        $database->log('info', 'pagos', 'Pago creado exitosamente', [
            'pago_id' => $pagoId,
            'cliente_id' => $clienteId,
            'monto' => $montoPagado
        ]);
        
        jsonResponse([
            'success' => true,
            'message' => 'Pago registrado exitosamente',
            'data' => ['id' => $pagoId]
        ]);
        
    } catch (Exception $e) {
        if ($database->pdo && $database->pdo->inTransaction()) {
            $database->rollback();
        }
        throw $e;
    }
}

/**
 * Actualizar un pago existente
 */
function actualizarPago($database, $id, $input) {
    if (!$id) {
        jsonResponse(['success' => false, 'error' => 'ID requerido'], 400);
    }
    
    try {
        // Verificar que el pago existe
        $pago = $database->fetch("SELECT * FROM historial_pagos WHERE id = ?", [$id]);
        if (!$pago) {
            jsonResponse(['success' => false, 'error' => 'Pago no encontrado'], 404);
        }
        
        // Construir query de actualización
        $updates = [];
        $params = [];
        
        if (isset($input['monto_pagado'])) {
            $monto = floatval($input['monto_pagado']);
            if ($monto <= 0) {
                jsonResponse(['success' => false, 'error' => 'El monto debe ser mayor a 0'], 400);
            }
            $updates[] = "monto_pagado = ?";
            $params[] = $monto;
        }
        
        if (isset($input['fecha_pago'])) {
            $updates[] = "fecha_pago = ?";
            $params[] = $input['fecha_pago'];
        }
        
        if (isset($input['metodo_pago'])) {
            $metodosValidos = ['transferencia', 'deposito', 'yape', 'plin', 'efectivo', 'otro'];
            if (!in_array($input['metodo_pago'], $metodosValidos)) {
                jsonResponse(['success' => false, 'error' => 'Método de pago inválido'], 400);
            }
            $updates[] = "metodo_pago = ?";
            $params[] = $input['metodo_pago'];
        }
        
        if (isset($input['numero_operacion'])) {
            $updates[] = "numero_operacion = ?";
            $params[] = $input['numero_operacion'];
        }
        
        if (isset($input['banco'])) {
            $updates[] = "banco = ?";
            $params[] = $input['banco'];
        }
        
        if (isset($input['observaciones'])) {
            $updates[] = "observaciones = ?";
            $params[] = $input['observaciones'];
        }
        
        if (empty($updates)) {
            jsonResponse(['success' => false, 'error' => 'No hay cambios para actualizar'], 400);
        }
        
        $params[] = $id;
        
        $sql = "UPDATE historial_pagos SET " . implode(", ", $updates) . " WHERE id = ?";
        $database->query($sql, $params);
        
        // Log de la actualización
        $database->log('info', 'pagos', 'Pago actualizado', ['pago_id' => $id]);
        
        jsonResponse([
            'success' => true,
            'message' => 'Pago actualizado exitosamente'
        ]);
        
    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Eliminar un pago
 */
function eliminarPago($database, $id) {
    if (!$id) {
        jsonResponse(['success' => false, 'error' => 'ID requerido'], 400);
    }
    
    try {
        // Verificar que el pago existe y obtener información del cliente
        $pago = $database->fetch("
            SELECT hp.*, c.tipo_servicio, c.fecha_vencimiento 
            FROM historial_pagos hp
            INNER JOIN clientes c ON hp.cliente_id = c.id
            WHERE hp.id = ?", [$id]);
        
        if (!$pago) {
            jsonResponse(['success' => false, 'error' => 'Pago no encontrado'], 404);
        }
        
        $database->beginTransaction();
        
        // Eliminar el pago
        $database->query("DELETE FROM historial_pagos WHERE id = ?", [$id]);
        
        // Revertir la fecha de vencimiento del cliente
        // Calcular la fecha anterior (restar el periodo correspondiente)
        $fechaVencimientoActual = new DateTime($pago['fecha_vencimiento']);
        $tipoServicio = $pago['tipo_servicio'] ?? 'anual';
        
        switch ($tipoServicio) {
            case 'mensual':
                $fechaVencimientoActual->sub(new DateInterval('P1M'));
                break;
            case 'trimestral':
                $fechaVencimientoActual->sub(new DateInterval('P3M'));
                break;
            case 'semestral':
                $fechaVencimientoActual->sub(new DateInterval('P6M'));
                break;
            case 'anual':
            default:
                $fechaVencimientoActual->sub(new DateInterval('P1Y'));
                break;
        }
        
        $fechaAnterior = $fechaVencimientoActual->format('Y-m-d');
        
        // Solo revertir si no hay otros pagos más recientes
        $pagosPosteriores = $database->fetch("
            SELECT COUNT(*) as cantidad 
            FROM historial_pagos 
            WHERE cliente_id = ? AND fecha_pago >= ?
            ORDER BY fecha_pago DESC", 
            [$pago['cliente_id'], $pago['fecha_pago']]
        );
        
        if ($pagosPosteriores['cantidad'] == 0) {
            // No hay pagos posteriores, revertir la fecha
            $database->query(
                "UPDATE clientes SET fecha_vencimiento = ?, fecha_actualizacion = NOW() WHERE id = ?",
                [$fechaAnterior, $pago['cliente_id']]
            );
            
            $mensaje = 'Pago eliminado y fecha de vencimiento revertida exitosamente';
        } else {
            // Hay pagos posteriores, recalcular la fecha basada en el último pago
            $ultimoPago = $database->fetch("
                SELECT fecha_pago 
                FROM historial_pagos 
                WHERE cliente_id = ? 
                ORDER BY fecha_pago DESC 
                LIMIT 1", 
                [$pago['cliente_id']]
            );
            
            if ($ultimoPago) {
                // Calcular fecha de vencimiento basada en el último pago restante
                $fechaUltimoPago = new DateTime($ultimoPago['fecha_pago']);
                
                switch ($tipoServicio) {
                    case 'mensual':
                        $fechaUltimoPago->add(new DateInterval('P1M'));
                        break;
                    case 'trimestral':
                        $fechaUltimoPago->add(new DateInterval('P3M'));
                        break;
                    case 'semestral':
                        $fechaUltimoPago->add(new DateInterval('P6M'));
                        break;
                    case 'anual':
                    default:
                        $fechaUltimoPago->add(new DateInterval('P1Y'));
                        break;
                }
                
                $nuevaFechaVencimiento = $fechaUltimoPago->format('Y-m-d');
                
                $database->query(
                    "UPDATE clientes SET fecha_vencimiento = ?, fecha_actualizacion = NOW() WHERE id = ?",
                    [$nuevaFechaVencimiento, $pago['cliente_id']]
                );
                
                $mensaje = 'Pago eliminado y fecha de vencimiento recalculada exitosamente';
            } else {
                $mensaje = 'Pago eliminado exitosamente';
            }
        }
        
        $database->commit();
        
        // Log de la eliminación
        $database->log('info', 'pagos', 'Pago eliminado con reversión de fecha', [
            'pago_id' => $id,
            'cliente_id' => $pago['cliente_id'],
            'monto' => $pago['monto_pagado'],
            'fecha_anterior' => $fechaAnterior
        ]);
        
        jsonResponse([
            'success' => true,
            'message' => $mensaje
        ]);
        
    } catch (Exception $e) {
        if ($database->pdo && $database->pdo->inTransaction()) {
            $database->rollback();
        }
        throw $e;
    }
}

/**
 * Obtener pagos de un cliente específico
 */
function obtenerPagosPorCliente($database, $clienteId) {
    if (!$clienteId) {
        jsonResponse(['success' => false, 'error' => 'ID de cliente requerido'], 400);
    }
    
    try {
        $sql = "SELECT hp.*, 
                c.razon_social, 
                c.ruc
                FROM historial_pagos hp
                INNER JOIN clientes c ON hp.cliente_id = c.id
                WHERE hp.cliente_id = ?
                ORDER BY hp.fecha_pago DESC, hp.id DESC";
        
        $pagos = $database->fetchAll($sql, [$clienteId]);
        
        // Calcular estadísticas del cliente
        $statsSql = "SELECT 
                     COUNT(*) as total_pagos,
                     SUM(monto_pagado) as monto_total,
                     AVG(monto_pagado) as monto_promedio,
                     MAX(fecha_pago) as ultimo_pago,
                     MIN(fecha_pago) as primer_pago
                     FROM historial_pagos
                     WHERE cliente_id = ?";
        
        $stats = $database->fetch($statsSql, [$clienteId]);
        
        jsonResponse([
            'success' => true,
            'data' => $pagos,
            'estadisticas' => $stats
        ]);
        
    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Obtener estadísticas generales de pagos
 */
function obtenerEstadisticas($database) {
    try {
        $periodo = $_GET['periodo'] ?? 'mes'; // mes, anio, todo
        $whereClause = "1=1";
        
        if ($periodo === 'mes') {
            $whereClause = "MONTH(fecha_pago) = MONTH(CURDATE()) AND YEAR(fecha_pago) = YEAR(CURDATE())";
        } elseif ($periodo === 'anio') {
            $whereClause = "YEAR(fecha_pago) = YEAR(CURDATE())";
        }
        
        // Estadísticas generales
        $statsSql = "SELECT 
                     COUNT(*) as total_pagos,
                     SUM(monto_pagado) as monto_total,
                     AVG(monto_pagado) as monto_promedio,
                     MAX(monto_pagado) as monto_maximo,
                     MIN(monto_pagado) as monto_minimo
                     FROM historial_pagos
                     WHERE $whereClause";
        
        $stats = $database->fetch($statsSql);
        
        // Pagos por método
        $metodosSql = "SELECT 
                       metodo_pago,
                       COUNT(*) as cantidad,
                       SUM(monto_pagado) as total
                       FROM historial_pagos
                       WHERE $whereClause
                       GROUP BY metodo_pago
                       ORDER BY total DESC";
        
        $metodos = $database->fetchAll($metodosSql);
        
        // Pagos por banco
        $bancosSql = "SELECT 
                      banco,
                      COUNT(*) as cantidad,
                      SUM(monto_pagado) as total
                      FROM historial_pagos
                      WHERE $whereClause AND banco IS NOT NULL
                      GROUP BY banco
                      ORDER BY total DESC";
        
        $bancos = $database->fetchAll($bancosSql);
        
        // Top clientes que más pagan
        $topClientesSql = "SELECT 
                           c.razon_social,
                           c.ruc,
                           COUNT(hp.id) as cantidad_pagos,
                           SUM(hp.monto_pagado) as total_pagado
                           FROM historial_pagos hp
                           INNER JOIN clientes c ON hp.cliente_id = c.id
                           WHERE $whereClause
                           GROUP BY hp.cliente_id
                           ORDER BY total_pagado DESC
                           LIMIT 10";
        
        $topClientes = $database->fetchAll($topClientesSql);
        
        jsonResponse([
            'success' => true,
            'data' => [
                'general' => $stats,
                'por_metodo' => $metodos,
                'por_banco' => $bancos,
                'top_clientes' => $topClientes,
                'periodo' => $periodo
            ]
        ]);
        
    } catch (Exception $e) {
        throw $e;
    }
}