<?php
/**
 * Test de Zona Horaria
 * Verifica que todo esté configurado en hora peruana
 */

require_once 'config/database.php';

header('Content-Type: text/plain; charset=UTF-8');

echo "=== TEST DE ZONA HORARIA ===\n\n";

// 1. Verificar zona horaria PHP
echo "1. ZONA HORARIA PHP:\n";
echo "   Configurado: " . date_default_timezone_get() . "\n";
echo "   Hora actual PHP: " . date('Y-m-d H:i:s') . "\n";
echo "   UTC actual: " . gmdate('Y-m-d H:i:s') . "\n\n";

// 2. Verificar zona horaria MySQL
try {
    $database = new Database();
    $db = $database->connect();
    
    echo "2. ZONA HORARIA MYSQL:\n";
    
    // Obtener configuración de zona horaria
    $result = $database->fetch("SELECT @@global.time_zone as global_tz, @@session.time_zone as session_tz");
    echo "   Global: " . $result['global_tz'] . "\n";
    echo "   Session: " . $result['session_tz'] . "\n";
    
    // Obtener hora actual de MySQL
    $result = $database->fetch("SELECT NOW() as mysql_now, UTC_TIMESTAMP() as utc_now");
    echo "   Hora MySQL (NOW()): " . $result['mysql_now'] . "\n";
    echo "   UTC MySQL: " . $result['utc_now'] . "\n\n";
    
    // 3. Comparar con hora de Lima
    echo "3. COMPARACIÓN:\n";
    $lima_tz = new DateTimeZone('America/Lima');
    $lima_time = new DateTime('now', $lima_tz);
    echo "   Hora real de Lima: " . $lima_time->format('Y-m-d H:i:s') . "\n";
    
    // Calcular diferencia
    $mysql_time = new DateTime($result['mysql_now']);
    $diff = $lima_time->getTimestamp() - $mysql_time->getTimestamp();
    
    if (abs($diff) < 60) { // Menos de 1 minuto de diferencia
        echo "   ✓ Las horas coinciden correctamente\n";
    } else {
        $horas_diff = abs($diff) / 3600;
        echo "   ✗ Diferencia de " . number_format($horas_diff, 1) . " horas\n";
    }
    
    // 4. Test de inserción
    echo "\n4. TEST DE INSERCIÓN:\n";
    $test_id = $database->insert(
        "INSERT INTO logs_sistema (nivel, modulo, mensaje) VALUES (?, ?, ?)",
        ['info', 'timezone_test', 'Test de zona horaria']
    );
    
    $result = $database->fetch(
        "SELECT fecha_log FROM logs_sistema WHERE id = ?",
        [$test_id]
    );
    echo "   Timestamp insertado: " . $result['fecha_log'] . "\n";
    
    // Limpiar test
    $database->query("DELETE FROM logs_sistema WHERE id = ?", [$test_id]);
    
    echo "\n✓ Test completado exitosamente\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>