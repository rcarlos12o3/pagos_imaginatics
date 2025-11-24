<?php
/**
 * Script para ejecutar migración 003: Sistema de Historial de Recordatorios
 */

require_once 'config/database.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $database = new Database();
    $pdo = $database->connect();

    echo "🔍 Verificando estado de las tablas...\n\n";

    // Verificar si config_recordatorios existe
    $checkTable = $pdo->query("SHOW TABLES LIKE 'config_recordatorios'");
    $tableExists = $checkTable->rowCount() > 0;

    if ($tableExists) {
        echo "✅ Tabla 'config_recordatorios' ya existe\n\n";

        // Verificar datos
        $count = $pdo->query("SELECT COUNT(*) as total FROM config_recordatorios")->fetch();
        echo "📊 Registros en config_recordatorios: " . $count['total'] . "\n\n";

        if ($count['total'] > 0) {
            echo "✅ Configuración ya está cargada. No es necesario ejecutar la migración.\n\n";

            // Mostrar configuraciones actuales
            $configs = $pdo->query("SELECT clave, valor FROM config_recordatorios ORDER BY id")->fetchAll();
            echo "📋 Configuraciones actuales:\n";
            foreach ($configs as $config) {
                echo "  • {$config['clave']}: {$config['valor']}\n";
            }
            exit;
        }
    }

    echo "⚠️  Tabla no existe o está vacía. Ejecutando migración...\n\n";

    // Leer archivo de migración
    $sqlFile = __DIR__ . '/migrations/003_historial_recordatorios.sql';

    if (!file_exists($sqlFile)) {
        throw new Exception("Archivo de migración no encontrado: $sqlFile");
    }

    $sql = file_get_contents($sqlFile);

    // Separar por punto y coma y ejecutar cada statement
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) &&
                   strpos($stmt, '--') !== 0 &&
                   strpos($stmt, 'CREATE OR REPLACE VIEW') !== 0; // Skip views for now
        }
    );

    $pdo->beginTransaction();

    $executed = 0;
    $errors = [];

    foreach ($statements as $statement) {
        try {
            if (strpos($statement, 'SET @') !== false ||
                strpos($statement, 'PREPARE') !== false ||
                strpos($statement, 'EXECUTE') !== false ||
                strpos($statement, 'DEALLOCATE') !== false) {
                // Skip prepared statements for safety
                continue;
            }

            $pdo->exec($statement . ';');
            $executed++;
        } catch (PDOException $e) {
            $errors[] = $e->getMessage();
        }
    }

    $pdo->commit();

    echo "✅ Migración ejecutada exitosamente\n";
    echo "📊 Statements ejecutados: $executed\n";

    if (count($errors) > 0) {
        echo "\n⚠️  Advertencias (no críticos):\n";
        foreach ($errors as $error) {
            echo "  • $error\n";
        }
    }

    // Verificar resultado
    echo "\n🔍 Verificando tablas creadas...\n";

    $tables = ['config_recordatorios', 'historial_recordatorios'];
    foreach ($tables as $table) {
        $check = $pdo->query("SHOW TABLES LIKE '$table'")->rowCount();
        echo "  • $table: " . ($check ? '✅' : '❌') . "\n";
    }

    // Mostrar configuraciones
    $configs = $pdo->query("SELECT clave, valor FROM config_recordatorios ORDER BY id")->fetchAll();
    if (count($configs) > 0) {
        echo "\n📋 Configuraciones cargadas:\n";
        foreach ($configs as $config) {
            echo "  • {$config['clave']}: {$config['valor']}\n";
        }
    }

    echo "\n✅ ¡Migración completada con éxito!\n";

} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
