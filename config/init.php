<?php
/**
 * ARCHIVO DE INICIALIZACIÓN
 * Configuraciones globales para todo el sistema
 * Imaginatics Perú SAC
 */

// Configurar zona horaria de Perú (UTC-5)
date_default_timezone_set('America/Lima');

// Configurar locale para formato de fechas en español
setlocale(LC_TIME, 'es_PE.UTF-8', 'es_PE', 'spanish');

// Configurar charset
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');

// Función helper para obtener fecha/hora actual en zona peruana
function fechaHoraPeru($formato = 'Y-m-d H:i:s') {
    return date($formato);
}

// Función para convertir timestamp UTC a hora peruana
function convertirUTCaPeru($fechaUTC, $formato = 'Y-m-d H:i:s') {
    $fecha = new DateTime($fechaUTC, new DateTimeZone('UTC'));
    $fecha->setTimezone(new DateTimeZone('America/Lima'));
    return $fecha->format($formato);
}

// Función para obtener el timestamp actual para MySQL
function nowPeru() {
    return date('Y-m-d H:i:s');
}
?>