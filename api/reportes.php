<?php
/**
 * API REPORTES Y ESTADÍSTICAS
 * Sistema Multi-Servicio
 * Imaginatics Perú SAC
 */

require_once '../config/database.php';

// Instanciar base de datos
$database = new Database();
$db = $database->connect();

// Obtener método HTTP
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method !== 'GET') {
        jsonResponse(['success' => false, 'error' => 'Solo se permiten peticiones GET'], 405);
    }

    $action = $_GET['action'] ?? 'dashboard';

    switch ($action) {
        case 'dashboard':
            getDashboard($database);
            break;
        case 'ingresos_mensuales':
            getIngresosMensuales($database);
            break;
        case 'servicios_categoria':
            getServiciosPorCategoria($database);
            break;
        case 'clientes_top':
            getClientesTop($database);
            break;
        case 'servicios_vencidos':
            getServiciosVencidos($database);
            break;
        case 'proyeccion_ingresos':
            getProyeccionIngresos($database);
            break;
        case 'historial_facturacion':
            getHistorialFacturacion($database);
            break;
        case 'metricas_conversion':
            getMetricasConversion($database);
            break;
        case 'reporte_completo':
            getReporteCompleto($database);
            break;
        default:
            jsonResponse(['success' => false, 'error' => 'Acción no válida'], 400);
    }
} catch (Exception $e) {
    $database->log('error', 'reportes_api', $e->getMessage());
    jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
}

/**
 * Dashboard principal con métricas clave
 */
function getDashboard($database) {
    try {
        // Métricas de clientes
        $clientes = $database->fetch("
            SELECT
                COUNT(*) as total_clientes,
                (SELECT COUNT(DISTINCT cliente_id) FROM servicios_contratados WHERE estado = 'activo') as clientes_con_servicios_activos,
                (SELECT COUNT(*) FROM clientes WHERE activo = TRUE AND fecha_creacion >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) as clientes_nuevos_mes
            FROM clientes WHERE activo = TRUE
        ");

        // Métricas de servicios
        $servicios = $database->fetch("
            SELECT
                COUNT(*) as total_contratos,
                SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) as activos,
                SUM(CASE WHEN estado = 'vencido' THEN 1 ELSE 0 END) as vencidos,
                SUM(CASE WHEN estado = 'suspendido' THEN 1 ELSE 0 END) as suspendidos,
                SUM(CASE WHEN estado = 'cancelado' THEN 1 ELSE 0 END) as cancelados
            FROM servicios_contratados
        ");

        // Ingresos proyectados (mensual)
        $ingresos = $database->fetch("
            SELECT
                SUM(CASE
                    WHEN periodo_facturacion = 'mensual' THEN precio
                    WHEN periodo_facturacion = 'trimestral' THEN precio / 3
                    WHEN periodo_facturacion = 'semestral' THEN precio / 6
                    WHEN periodo_facturacion = 'anual' THEN precio / 12
                END) as ingreso_mensual_pen,
                SUM(CASE
                    WHEN moneda = 'USD' AND periodo_facturacion = 'mensual' THEN precio
                    WHEN moneda = 'USD' AND periodo_facturacion = 'trimestral' THEN precio / 3
                    WHEN moneda = 'USD' AND periodo_facturacion = 'semestral' THEN precio / 6
                    WHEN moneda = 'USD' AND periodo_facturacion = 'anual' THEN precio / 12
                END) as ingreso_mensual_usd
            FROM servicios_contratados
            WHERE estado = 'activo'
        ");

        // Servicios por vencer (próximos 7 días)
        $porVencer = $database->fetch("
            SELECT COUNT(*) as total
            FROM servicios_contratados
            WHERE estado = 'activo'
            AND DATEDIFF(fecha_vencimiento, CURDATE()) BETWEEN 0 AND 7
        ");

        // Pagos del mes
        $pagosMes = $database->fetch("
            SELECT
                COUNT(*) as cantidad_pagos,
                SUM(monto_pagado) as total_pagado
            FROM historial_pagos
            WHERE MONTH(fecha_pago) = MONTH(CURDATE())
            AND YEAR(fecha_pago) = YEAR(CURDATE())
        ");

        // Tasa de renovación (últimos 30 días)
        $renovacion = $database->fetch("
            SELECT
                COUNT(*) as servicios_vencidos,
                SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) as renovados
            FROM servicios_contratados
            WHERE fecha_vencimiento BETWEEN DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND CURDATE()
        ");

        $tasaRenovacion = $renovacion['servicios_vencidos'] > 0
            ? round(($renovacion['renovados'] / $renovacion['servicios_vencidos']) * 100, 2)
            : 0;

        jsonResponse([
            'success' => true,
            'data' => [
                'clientes' => $clientes,
                'servicios' => $servicios,
                'ingresos_proyectados' => [
                    'mensual_pen' => round($ingresos['ingreso_mensual_pen'] ?? 0, 2),
                    'mensual_usd' => round($ingresos['ingreso_mensual_usd'] ?? 0, 2),
                    'anual_pen' => round(($ingresos['ingreso_mensual_pen'] ?? 0) * 12, 2),
                    'anual_usd' => round(($ingresos['ingreso_mensual_usd'] ?? 0) * 12, 2)
                ],
                'alertas' => [
                    'servicios_por_vencer' => $porVencer['total'],
                    'servicios_vencidos' => $servicios['vencidos'],
                    'tasa_renovacion' => $tasaRenovacion
                ],
                'pagos_mes' => $pagosMes
            ]
        ]);

    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Reporte de ingresos mensuales (últimos 12 meses)
 */
function getIngresosMensuales($database) {
    try {
        $sql = "
            SELECT
                DATE_FORMAT(fecha_pago, '%Y-%m') as mes,
                COUNT(*) as cantidad_pagos,
                SUM(monto_pagado) as total_ingreso,
                AVG(monto_pagado) as promedio_pago
            FROM historial_pagos
            WHERE fecha_pago >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(fecha_pago, '%Y-%m')
            ORDER BY mes DESC
        ";

        $ingresos = $database->fetchAll($sql);

        // Calcular totales
        $totalGeneral = array_sum(array_column($ingresos, 'total_ingreso'));
        $promedioMensual = count($ingresos) > 0 ? $totalGeneral / count($ingresos) : 0;

        jsonResponse([
            'success' => true,
            'data' => [
                'ingresos_por_mes' => $ingresos,
                'total_periodo' => round($totalGeneral, 2),
                'promedio_mensual' => round($promedioMensual, 2)
            ]
        ]);

    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Servicios agrupados por categoría
 */
function getServiciosPorCategoria($database) {
    try {
        $sql = "
            SELECT
                cs.categoria,
                COUNT(sc.id) as total_contratos,
                SUM(CASE WHEN sc.estado = 'activo' THEN 1 ELSE 0 END) as contratos_activos,
                SUM(CASE WHEN sc.estado = 'activo' THEN sc.precio ELSE 0 END) as ingreso_mensual_pen,
                MIN(sc.precio) as precio_minimo,
                MAX(sc.precio) as precio_maximo,
                AVG(sc.precio) as precio_promedio
            FROM servicios_contratados sc
            INNER JOIN catalogo_servicios cs ON sc.servicio_id = cs.id
            WHERE sc.moneda = 'PEN'
            GROUP BY cs.categoria
            ORDER BY contratos_activos DESC
        ";

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
 * Top clientes por ingresos
 */
function getClientesTop($database) {
    try {
        $limit = $_GET['limit'] ?? 20;

        $sql = "
            SELECT
                c.id,
                c.ruc,
                c.razon_social,
                c.whatsapp,
                COUNT(sc.id) as total_servicios,
                SUM(CASE WHEN sc.estado = 'activo' THEN 1 ELSE 0 END) as servicios_activos,
                SUM(CASE WHEN sc.estado = 'activo' THEN sc.precio ELSE 0 END) as ingreso_mensual,
                (SELECT SUM(monto_pagado) FROM historial_pagos WHERE cliente_id = c.id) as total_pagado_historico,
                (SELECT MAX(fecha_pago) FROM historial_pagos WHERE cliente_id = c.id) as ultimo_pago
            FROM clientes c
            LEFT JOIN servicios_contratados sc ON c.id = sc.cliente_id
            WHERE c.activo = TRUE
            GROUP BY c.id, c.ruc, c.razon_social, c.whatsapp
            ORDER BY ingreso_mensual DESC
            LIMIT ?
        ";

        $clientes = $database->fetchAll($sql, [$limit]);

        jsonResponse([
            'success' => true,
            'data' => $clientes
        ]);

    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Servicios vencidos con detalle
 */
function getServiciosVencidos($database) {
    try {
        $sql = "
            SELECT
                sc.*,
                c.ruc,
                c.razon_social,
                c.whatsapp,
                cs.nombre as servicio_nombre,
                cs.categoria,
                ABS(DATEDIFF(CURDATE(), sc.fecha_vencimiento)) as dias_vencido
            FROM servicios_contratados sc
            INNER JOIN clientes c ON sc.cliente_id = c.id
            INNER JOIN catalogo_servicios cs ON sc.servicio_id = cs.id
            WHERE sc.estado = 'vencido'
            AND c.activo = TRUE
            ORDER BY sc.fecha_vencimiento ASC
        ";

        $serviciosVencidos = $database->fetchAll($sql);

        // Calcular impacto financiero
        $impactoTotal = array_sum(array_column($serviciosVencidos, 'precio'));

        jsonResponse([
            'success' => true,
            'data' => [
                'total_vencidos' => count($serviciosVencidos),
                'impacto_financiero' => round($impactoTotal, 2),
                'servicios' => $serviciosVencidos
            ]
        ]);

    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Proyección de ingresos (próximos 6 meses)
 */
function getProyeccionIngresos($database) {
    try {
        $proyeccion = [];

        for ($i = 0; $i < 6; $i++) {
            $fechaInicio = date('Y-m-01', strtotime("+$i months"));
            $fechaFin = date('Y-m-t', strtotime("+$i months"));

            // Servicios que vencen en ese mes
            $ingresosMes = $database->fetch("
                SELECT
                    SUM(precio) as ingreso_proyectado_pen,
                    COUNT(*) as servicios_a_renovar
                FROM servicios_contratados
                WHERE estado = 'activo'
                AND fecha_vencimiento BETWEEN ? AND ?
                AND moneda = 'PEN'
            ", [$fechaInicio, $fechaFin]);

            $proyeccion[] = [
                'mes' => date('Y-m', strtotime("+$i months")),
                'mes_nombre' => date('F Y', strtotime("+$i months")),
                'ingreso_proyectado' => round($ingresosMes['ingreso_proyectado_pen'] ?? 0, 2),
                'servicios_a_renovar' => $ingresosMes['servicios_a_renovar'] ?? 0
            ];
        }

        jsonResponse([
            'success' => true,
            'data' => $proyeccion
        ]);

    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Historial de facturación
 */
function getHistorialFacturacion($database) {
    try {
        $meses = $_GET['meses'] ?? 6;

        $sql = "
            SELECT
                DATE_FORMAT(hp.fecha_pago, '%Y-%m') as mes,
                COUNT(DISTINCT hp.cliente_id) as clientes_pagaron,
                COUNT(hp.id) as total_pagos,
                SUM(hp.monto_pagado) as total_facturado,
                AVG(hp.monto_pagado) as ticket_promedio,
                (SELECT COUNT(*) FROM servicios_contratados sc
                 WHERE JSON_CONTAINS(hp.servicios_pagados, CAST(sc.id AS JSON))) as servicios_pagados_total
            FROM historial_pagos hp
            WHERE hp.fecha_pago >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
            GROUP BY DATE_FORMAT(hp.fecha_pago, '%Y-%m')
            ORDER BY mes DESC
        ";

        $historial = $database->fetchAll($sql, [$meses]);

        jsonResponse([
            'success' => true,
            'data' => $historial
        ]);

    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Métricas de conversión y retención
 */
function getMetricasConversion($database) {
    try {
        // Tasa de activación de servicios
        $activacion = $database->fetch("
            SELECT
                COUNT(*) as total_servicios_creados,
                SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) as servicios_activos,
                SUM(CASE WHEN estado = 'cancelado' THEN 1 ELSE 0 END) as servicios_cancelados
            FROM servicios_contratados
            WHERE fecha_creacion >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
        ");

        $tasaActivacion = $activacion['total_servicios_creados'] > 0
            ? round(($activacion['servicios_activos'] / $activacion['total_servicios_creados']) * 100, 2)
            : 0;

        $tasaCancelacion = $activacion['total_servicios_creados'] > 0
            ? round(($activacion['servicios_cancelados'] / $activacion['total_servicios_creados']) * 100, 2)
            : 0;

        // Tiempo promedio hasta primer pago
        $tiempoPago = $database->fetch("
            SELECT AVG(DATEDIFF(hp.fecha_pago, sc.fecha_inicio)) as dias_promedio
            FROM servicios_contratados sc
            INNER JOIN historial_pagos hp ON JSON_CONTAINS(hp.servicios_pagados, CAST(sc.id AS JSON))
            WHERE sc.fecha_creacion >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
        ");

        // Lifetime value promedio
        $ltv = $database->fetch("
            SELECT AVG(total_pagado) as ltv_promedio
            FROM (
                SELECT cliente_id, SUM(monto_pagado) as total_pagado
                FROM historial_pagos
                GROUP BY cliente_id
            ) as pagos_por_cliente
        ");

        jsonResponse([
            'success' => true,
            'data' => [
                'tasa_activacion' => $tasaActivacion,
                'tasa_cancelacion' => $tasaCancelacion,
                'dias_promedio_primer_pago' => round($tiempoPago['dias_promedio'] ?? 0, 1),
                'lifetime_value_promedio' => round($ltv['ltv_promedio'] ?? 0, 2)
            ]
        ]);

    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Reporte completo (todas las métricas)
 */
function getReporteCompleto($database) {
    try {
        // Obtener todas las métricas
        $dashboard = getDashboardData($database);
        $ingresosMensuales = getIngresosMensualesData($database);
        $categorias = getServiciosPorCategoriaData($database);
        $clientesTop = getClientesTopData($database, 10);
        $proyeccion = getProyeccionIngresosData($database);
        $metricas = getMetricasConversionData($database);

        jsonResponse([
            'success' => true,
            'data' => [
                'dashboard' => $dashboard,
                'ingresos_mensuales' => $ingresosMensuales,
                'servicios_por_categoria' => $categorias,
                'top_10_clientes' => $clientesTop,
                'proyeccion_6_meses' => $proyeccion,
                'metricas_conversion' => $metricas,
                'fecha_generacion' => date('Y-m-d H:i:s')
            ]
        ]);

    } catch (Exception $e) {
        throw $e;
    }
}

// Funciones auxiliares para reporte completo
function getDashboardData($database) {
    // Implementación simplificada
    return [];
}

function getIngresosMensualesData($database) {
    return [];
}

function getServiciosPorCategoriaData($database) {
    return [];
}

function getClientesTopData($database, $limit) {
    return [];
}

function getProyeccionIngresosData($database) {
    return [];
}

function getMetricasConversionData($database) {
    return [];
}
?>
