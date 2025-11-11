<?php
/**
 * Script de Diagnóstico - Sistema de Servicios
 * Verifica el estado de la base de datos y configuración
 */

require_once 'config/database.php';

header('Content-Type: text/html; charset=UTF-8');

echo "<h1>Diagnóstico del Sistema de Servicios</h1>";
echo "<style>
    body { font-family: 'Segoe UI', sans-serif; padding: 20px; background: #f5f5f5; }
    .test { background: white; margin: 10px 0; padding: 15px; border-radius: 8px; border-left: 4px solid #2581c4; }
    .success { border-left-color: #28a745; }
    .error { border-left-color: #dc3545; }
    .warning { border-left-color: #ffc107; }
    pre { background: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; margin: 10px 0; }
    th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
    th { background: #2581c4; color: white; }
</style>";

$database = new Database();

// Test 1: Conexión a la base de datos
echo "<div class='test'>";
echo "<h2>1. Conexión a Base de Datos</h2>";
try {
    $db = $database->connect();
    if ($db) {
        echo "<p style='color: green;'>✅ Conexión exitosa</p>";
    } else {
        echo "<p style='color: red;'>❌ Error de conexión</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test 2: Verificar tabla catalogo_servicios
echo "<div class='test'>";
echo "<h2>2. Tabla catalogo_servicios</h2>";
try {
    $result = $database->query("SHOW TABLES LIKE 'catalogo_servicios'");
    if ($result && $result->rowCount() > 0) {
        echo "<p style='color: green;'>✅ Tabla existe</p>";

        // Contar registros
        $count = $database->fetch("SELECT COUNT(*) as total FROM catalogo_servicios");
        echo "<p><strong>Total de servicios:</strong> " . $count['total'] . "</p>";

        $activos = $database->fetch("SELECT COUNT(*) as total FROM catalogo_servicios WHERE activo = 1");
        echo "<p><strong>Servicios activos:</strong> " . $activos['total'] . "</p>";

        if ($count['total'] == 0) {
            echo "<p style='color: orange;'>⚠️ ADVERTENCIA: La tabla está vacía. Necesitas ejecutar el script de población.</p>";
            echo "<pre>mysql -u imaginatics -p imaginatics_ruc < migrations/002_poblar_catalogo_servicios.sql</pre>";
        }
    } else {
        echo "<p style='color: red;'>❌ La tabla no existe. Ejecuta el script de migración:</p>";
        echo "<pre>mysql -u imaginatics -p imaginatics_ruc < migrations/001_multi_servicio_schema.sql</pre>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test 3: Verificar estructura de catalogo_servicios
echo "<div class='test'>";
echo "<h2>3. Estructura de catalogo_servicios</h2>";
try {
    $columns = $database->fetchAll("DESCRIBE catalogo_servicios");
    if ($columns) {
        echo "<p style='color: green;'>✅ Estructura correcta</p>";
        echo "<table>";
        echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Default</th></tr>";
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td>" . $col['Field'] . "</td>";
            echo "<td>" . $col['Type'] . "</td>";
            echo "<td>" . $col['Null'] . "</td>";
            echo "<td>" . $col['Key'] . "</td>";
            echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test 4: Listar servicios del catálogo
echo "<div class='test'>";
echo "<h2>4. Servicios en el Catálogo</h2>";
try {
    $servicios = $database->fetchAll("SELECT id, nombre, categoria, precio_base, moneda, periodos_disponibles, activo FROM catalogo_servicios ORDER BY categoria, nombre");

    if ($servicios && count($servicios) > 0) {
        echo "<p style='color: green;'>✅ " . count($servicios) . " servicios encontrados</p>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Nombre</th><th>Categoría</th><th>Precio</th><th>Moneda</th><th>Periodos</th><th>Activo</th></tr>";
        foreach ($servicios as $s) {
            $activo_badge = $s['activo'] ? '<span style="color: green;">✓</span>' : '<span style="color: red;">✗</span>';
            $periodos = json_decode($s['periodos_disponibles'], true);
            echo "<tr>";
            echo "<td>" . $s['id'] . "</td>";
            echo "<td>" . $s['nombre'] . "</td>";
            echo "<td>" . $s['categoria'] . "</td>";
            echo "<td>" . number_format($s['precio_base'], 2) . "</td>";
            echo "<td>" . $s['moneda'] . "</td>";
            echo "<td>" . ($periodos ? implode(', ', $periodos) : 'N/A') . "</td>";
            echo "<td>" . $activo_badge . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>⚠️ No hay servicios en el catálogo</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test 5: Probar endpoint del API
echo "<div class='test'>";
echo "<h2>5. Prueba del Endpoint API</h2>";
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

    echo "<p style='color: green;'>✅ Consulta ejecutada correctamente</p>";
    echo "<p><strong>Servicios devueltos:</strong> " . count($servicios) . "</p>";

    if (count($servicios) > 0) {
        echo "<p><strong>Ejemplo de servicio (JSON):</strong></p>";
        echo "<pre>" . json_encode($servicios[0], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
    }

} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error al ejecutar consulta: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test 6: Verificar tabla servicios_contratados
echo "<div class='test'>";
echo "<h2>6. Tabla servicios_contratados</h2>";
try {
    $result = $database->query("SHOW TABLES LIKE 'servicios_contratados'");
    if ($result && $result->rowCount() > 0) {
        echo "<p style='color: green;'>✅ Tabla existe</p>";

        $count = $database->fetch("SELECT COUNT(*) as total FROM servicios_contratados");
        echo "<p><strong>Total de servicios contratados:</strong> " . $count['total'] . "</p>";

        $activos = $database->fetch("SELECT COUNT(*) as total FROM servicios_contratados WHERE estado = 'activo'");
        echo "<p><strong>Servicios activos:</strong> " . $activos['total'] . "</p>";
    } else {
        echo "<p style='color: red;'>❌ La tabla no existe</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test 7: Verificar permisos de archivo
echo "<div class='test'>";
echo "<h2>7. Permisos del API</h2>";
$api_file = __DIR__ . '/api/servicios.php';
if (file_exists($api_file)) {
    echo "<p style='color: green;'>✅ Archivo api/servicios.php existe</p>";
    echo "<p><strong>Permisos:</strong> " . substr(sprintf('%o', fileperms($api_file)), -4) . "</p>";

    if (is_readable($api_file)) {
        echo "<p style='color: green;'>✅ Archivo es legible</p>";
    } else {
        echo "<p style='color: red;'>❌ Archivo NO es legible</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Archivo api/servicios.php NO existe</p>";
}
echo "</div>";

// Test 8: Resumen y recomendaciones
echo "<div class='test'>";
echo "<h2>8. Resumen y Recomendaciones</h2>";

$errores = [];
$advertencias = [];

// Verificar si hay servicios
try {
    $count = $database->fetch("SELECT COUNT(*) as total FROM catalogo_servicios WHERE activo = 1");
    if ($count['total'] == 0) {
        $errores[] = "No hay servicios activos en el catálogo";
    }
} catch (Exception $e) {
    $errores[] = "No se pudo verificar el catálogo de servicios";
}

if (count($errores) > 0) {
    echo "<h3 style='color: red;'>❌ Errores Encontrados:</h3>";
    echo "<ul>";
    foreach ($errores as $error) {
        echo "<li>" . $error . "</li>";
    }
    echo "</ul>";

    echo "<h3>Solución:</h3>";
    echo "<ol>";
    echo "<li>Abre una terminal</li>";
    echo "<li>Ejecuta el script de población:</li>";
    echo "<pre>mysql -u imaginatics -pimaginations123 imaginatics_ruc < migrations/002_poblar_catalogo_servicios.sql</pre>";
    echo "<li>Recarga esta página para verificar</li>";
    echo "</ol>";
} else {
    echo "<p style='color: green; font-size: 18px;'>✅ Sistema correctamente configurado</p>";
    echo "<p>El catálogo de servicios está listo para usar.</p>";
}

echo "</div>";

echo "<hr>";
echo "<p><small>Diagnóstico ejecutado: " . date('Y-m-d H:i:s') . "</small></p>";
?>
