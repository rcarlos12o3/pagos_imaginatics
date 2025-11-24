<?php
/**
 * SCRIPT PARA CORREGIR CODIFICACIÓN UTF-8
 * Convierte datos con double-encoding a UTF-8 correcto
 * SEGURO: Solo corrige la codificación, NO elimina datos
 */

require_once __DIR__ . '/config/database.php';

header('Content-Type: text/html; charset=UTF-8');

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Fix Encoding</title>";
echo "<style>body{font-family:Arial;padding:20px;} .ok{color:green;} .error{color:red;}</style></head><body>";
echo "<h1>Corrigiendo Codificación UTF-8</h1>";

try {
    $db = new Database();
    $pdo = $db->connect();

    // Función para detectar y corregir double-encoding
    function fixDoubleEncoding($str) {
        // Si tiene caracteres como Ã³ (ó mal codificada), corregir
        if (strpos($str, 'Ã') !== false) {
            // Convertir de UTF-8 mal interpretado a ISO-8859-1 y luego a UTF-8
            $fixed = utf8_encode(utf8_decode($str));
            return $fixed;
        }
        return $str;
    }

    echo "<h2>Corrigiendo tabla: catalogo_servicios</h2>";

    // Obtener todos los servicios
    $servicios = $pdo->query("SELECT id, nombre, descripcion FROM catalogo_servicios")->fetchAll();

    $corregidos = 0;
    foreach ($servicios as $servicio) {
        $nombreOriginal = $servicio['nombre'];
        $nombreCorregido = fixDoubleEncoding($nombreOriginal);

        $descOriginal = $servicio['descripcion'] ?: '';
        $descCorregido = fixDoubleEncoding($descOriginal);

        if ($nombreOriginal !== $nombreCorregido || $descOriginal !== $descCorregido) {
            $stmt = $pdo->prepare("UPDATE catalogo_servicios SET nombre = ?, descripcion = ? WHERE id = ?");
            $stmt->execute([$nombreCorregido, $descCorregido, $servicio['id']]);

            echo "<div class='ok'>✓ ID {$servicio['id']}: <strong>{$nombreCorregido}</strong></div>";
            $corregidos++;
        }
    }

    echo "<p class='ok'><strong>Total corregidos: {$corregidos}</strong></p>";

    // Verificar resultados
    echo "<h2>Verificación de Resultados</h2>";
    echo "<table border='1' cellpadding='10' style='border-collapse:collapse;'>";
    echo "<tr><th>ID</th><th>Nombre del Servicio</th></tr>";

    $servicios = $pdo->query("SELECT id, nombre FROM catalogo_servicios LIMIT 10")->fetchAll();
    foreach ($servicios as $s) {
        $clase = (strpos($s['nombre'], 'Ã') !== false) ? 'error' : 'ok';
        echo "<tr class='$clase'><td>{$s['id']}</td><td>{$s['nombre']}</td></tr>";
    }
    echo "</table>";

    echo "<h2 class='ok'>✅ Proceso Completado</h2>";
    echo "<p><a href='/api/clientes.php?action=list&limit=5'>Ver API de Clientes</a></p>";
    echo "<p><a href='/test_utf8.php'>Ver Test UTF-8</a></p>";

} catch (Exception $e) {
    echo "<div class='error'>❌ Error: {$e->getMessage()}</div>";
}

echo "</body></html>";
?>
