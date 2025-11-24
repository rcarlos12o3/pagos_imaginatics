<?php
/**
 * SCRIPT DE VERIFICACIÓN DE PRODUCCIÓN
 * NO modifica ningún dato, solo verifica estado actual
 */

require_once __DIR__ . '/config/database.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=================================\n";
echo "VERIFICACIÓN DE PRODUCCIÓN\n";
echo "=================================\n\n";

try {
    $db = new Database();
    $pdo = $db->connect();

    echo "✅ Conexión exitosa a base de datos\n\n";

    // Información de conexión
    echo "--- INFORMACIÓN DE CONEXIÓN ---\n";
    echo "Host: " . DB_HOST . "\n";
    echo "Base de datos: " . DB_NAME . "\n";
    echo "Usuario: " . DB_USER . "\n\n";

    // Contar datos existentes (SIN MODIFICAR)
    echo "--- DATOS EXISTENTES ---\n";

    $tablas = [
        'clientes' => 'Clientes registrados',
        'servicios_contratados' => 'Servicios contratados',
        'catalogo_servicios' => 'Servicios en catálogo',
        'envios_whatsapp' => 'Envíos realizados',
        'sesiones_envio' => 'Sesiones de envío',
        'cola_envios' => 'Trabajos en cola',
        'consultas_ruc' => 'Consultas RUC en cache',
        'configuracion' => 'Configuraciones',
        'logs_sistema' => 'Logs del sistema'
    ];

    foreach ($tablas as $tabla => $descripcion) {
        try {
            $result = $db->fetch("SELECT COUNT(*) as total FROM $tabla");
            echo "$descripcion: " . number_format($result['total']) . "\n";
        } catch (Exception $e) {
            echo "$descripcion: ⚠️ Tabla no existe o error\n";
        }
    }

    echo "\n--- SERVICIOS ACTIVOS ---\n";
    try {
        $servicios_activos = $db->fetch("SELECT COUNT(*) as total FROM servicios_contratados WHERE estado = 'activo'");
        echo "Servicios activos: " . $servicios_activos['total'] . "\n";
    } catch (Exception $e) {
        echo "⚠️ No se pudo verificar servicios activos\n";
    }

    echo "\n--- CONFIGURACIÓN WHATSAPP ---\n";
    $configs = ['token_whatsapp', 'instancia_whatsapp', 'api_url_whatsapp'];
    foreach ($configs as $config) {
        $valor = $db->getConfig($config);
        if ($valor) {
            // No mostrar el valor completo por seguridad
            $preview = substr($valor, 0, 10) . '...';
            echo "✅ $config: configurado ($preview)\n";
        } else {
            echo "❌ $config: NO configurado\n";
        }
    }

    echo "\n--- IMÁGENES REQUERIDAS ---\n";
    $imagenes = ['logo.png', 'mascota.png'];
    foreach ($imagenes as $img) {
        if (file_exists(__DIR__ . '/' . $img)) {
            $size = filesize(__DIR__ . '/' . $img);
            echo "✅ $img: existe (" . number_format($size/1024, 2) . " KB)\n";
        } else {
            echo "⚠️ $img: no encontrado\n";
        }
    }

    echo "\n--- ARCHIVOS CRÍTICOS ---\n";
    $archivos = [
        'api/clientes.php' => 'API Clientes',
        'api/envios.php' => 'API Envíos',
        'api/procesar_cola.php' => 'Worker de Cola',
        'js/modulo-envios.js' => 'Módulo Frontend Envíos'
    ];

    foreach ($archivos as $archivo => $descripcion) {
        if (file_exists(__DIR__ . '/' . $archivo)) {
            $size = filesize(__DIR__ . '/' . $archivo);
            echo "✅ $descripcion: " . number_format($size/1024, 2) . " KB\n";
        } else {
            echo "❌ $descripcion: NO ENCONTRADO\n";
        }
    }

    echo "\n=================================\n";
    echo "VERIFICACIÓN COMPLETADA\n";
    echo "=================================\n";

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
?>
