<?php
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    $database = new Database();

    // Obtener configuración
    $token = $database->fetch("SELECT valor FROM configuracion WHERE clave = 'token_whatsapp'");
    $instancia = $database->fetch("SELECT valor FROM configuracion WHERE clave = 'instancia_whatsapp'");
    $url_base = $database->fetch("SELECT valor FROM configuracion WHERE clave = 'api_url_whatsapp'");

    $token_decoded = $token['valor'];
    $instancia_decoded = base64_decode($instancia['valor']);
    $url_base_value = $url_base['valor'];

    echo "=== CONFIGURACIÓN ACTUAL ===\n";
    echo "Token: " . substr($token_decoded, 0, 20) . "...\n";
    echo "Instancia (original): " . $instancia['valor'] . "\n";
    echo "Instancia (decodificada): " . $instancia_decoded . "\n";
    echo "URL Base: " . $url_base_value . "\n\n";

    // Probar diferentes URLs
    $urls_to_test = [
        $url_base_value . "message/sendtext/" . $instancia_decoded,
        "https://apiwsp.factiliza.com/v1/message/sendtext/" . $instancia_decoded,
        "https://api.factiliza.com/v1/message/sendtext/" . $instancia_decoded,
        "https://apiwsp.factiliza.com/message/sendtext/" . $instancia_decoded
    ];

    $test_payload = [
        'number' => '51989613295',  // Número completo con código país
        'text' => 'Test de conexión - Sistema Imaginatics'
    ];

    echo "=== PROBANDO URLs ===\n";

    foreach ($urls_to_test as $index => $url) {
        echo "\n--- Prueba " . ($index + 1) . " ---\n";
        echo "URL: " . $url . "\n";

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($test_payload),
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer " . $token_decoded,
                "Content-Type: application/json"
            ],
            CURLOPT_SSL_VERIFYPEER => false
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        echo "HTTP Code: " . $httpCode . "\n";
        if ($error) {
            echo "cURL Error: " . $error . "\n";
        }
        echo "Response: " . $response . "\n";

        if ($httpCode == 200) {
            echo "✅ ¡ESTA URL FUNCIONA!\n";
            break;
        } else {
            echo "❌ Error HTTP " . $httpCode . "\n";
        }
    }

    echo "\n=== PROBANDO DIFERENTES FORMATOS DE NÚMERO ===\n";

    $url_working = $urls_to_test[0]; // Usar la primera por defecto
    $numeros_to_test = [
        '989613295',      // Sin código país
        '51989613295',    // Con código país
        '+51989613295'    // Con + y código país
    ];

    foreach ($numeros_to_test as $numero) {
        echo "\n--- Probando número: " . $numero . " ---\n";

        $test_payload_num = [
            'number' => $numero,
            'text' => 'Test número - Sistema Imaginatics'
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url_working,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($test_payload_num),
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer " . $token_decoded,
                "Content-Type: application/json"
            ],
            CURLOPT_SSL_VERIFYPEER => false
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        echo "HTTP Code: " . $httpCode . "\n";
        echo "Response: " . $response . "\n";

        if ($httpCode == 200) {
            echo "✅ ¡ESTE FORMATO DE NÚMERO FUNCIONA!\n";
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>