<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->connect();

    echo "<h2>✅ Conexión exitosa a MySQL</h2>";

    // Probar consulta
    $result = $database->fetch("SELECT COUNT(*) as total FROM clientes");
    echo "<p>📊 Clientes en BD: " . $result['total'] . "</p>";

    // Probar configuración
    $empresa = $database->getConfig('empresa_nombre');
    echo "<p>🏢 Empresa: " . $empresa . "</p>";

    // Verificar tokens
    $tokenRuc = $database->getConfig('token_ruc');
    echo "<p>🔑 Token RUC configurado: " . (strlen($tokenRuc) > 0 ? "SÍ" : "NO") . "</p>";

    $tokenWA = $database->getConfig('token_whatsapp');
    echo "<p>📱 Token WhatsApp configurado: " . (strlen($tokenWA) > 0 ? "SÍ" : "NO") . "</p>";

    echo "<hr>";
    echo "<p><a href='index.html'>🚀 Ir a la aplicación principal</a></p>";

} catch (Exception $e) {
    echo "<h2>❌ Error de conexión:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<hr>";
    echo "<h3>🔧 Posibles soluciones:</h3>";
    echo "<ul>";
    echo "<li>Verificar que config/database.php tenga las credenciales correctas</li>";
    echo "<li>Verificar que el container MySQL esté corriendo</li>";
    echo "<li>Verificar que la BD 'imaginatics_ruc' exista</li>";
    echo "</ul>";
}
?>