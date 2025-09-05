USE imaginatics_ruc;

-- Resetear Admin 1: desbloquear y resetear contraseña a "password"
UPDATE usuarios 
SET password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
    intentos_fallidos = 0, 
    bloqueado_hasta = NULL,
    primera_vez = TRUE
WHERE celular = '989613295';

-- Mostrar resultado
SELECT id, celular, nombre, intentos_fallidos, bloqueado_hasta, primera_vez 
FROM usuarios 
WHERE celular = '989613295';