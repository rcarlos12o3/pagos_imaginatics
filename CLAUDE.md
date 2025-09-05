# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Descripción del Proyecto

Este es un sistema de consulta RUC basado en PHP para Imaginatics Perú SAC que genera órdenes de pago y las envía vía WhatsApp. El sistema incluye:

- **Frontend**: Página HTML única con JavaScript vanilla
- **Backend**: APIs PHP con base de datos MySQL
- **Infraestructura**: Dockerizado con contenedores Apache/PHP y MySQL
- **Funciones principales**: Consulta RUC, gestión de clientes, generación de órdenes de pago, integración WhatsApp

## Comandos de Desarrollo

### Iniciar la Aplicación
```bash
# Iniciar contenedores
docker-compose up -d

# Acceder a la aplicación
# http://localhost:8080 - Aplicación principal
# http://localhost:8080/test_db.php - Test de conexión a base de datos
# http://localhost:8080/debug.html - Interfaz de debug
```

### Operaciones de Base de Datos
```bash
# Importar esquema de base de datos
docker exec -i imaginatics-mysql mysql -u imaginatics -pimaginations123 imaginatics_ruc < database.sql

# Acceder directamente a MySQL
docker exec -it imaginatics-mysql mysql -u imaginatics -pimaginations123 imaginatics_ruc
```

### Gestión de Contenedores
```bash
# Ver logs
docker logs imaginatics-web
docker logs imaginatics-mysql

# Detener contenedores
docker-compose down

# Reconstruir contenedores
docker-compose down && docker-compose up -d --build
```

## Arquitectura

### Capa de Base de Datos (config/database.php)
- **Clase Database**: Wrapper PDO con declaraciones preparadas, soporte de transacciones y logging
- **Funciones auxiliares**: Respuestas JSON, validación de entrada, validación RUC/WhatsApp
- **Gestión de configuración**: Almacén clave-valor para tokens y configuraciones
- **Manejo de errores**: Manejadores globales de excepciones y errores con modos debug

### Capa API (api/)
- **consultar_ruc.php**: Consulta RUC con cache de 24 horas, integración API externa
- **clientes.php**: Operaciones CRUD para gestión de clientes
- **envios.php**: Envío de mensajes WhatsApp y seguimiento de entregas
- **diagnostico_especifico.php**: Verificaciones de salud del sistema y diagnósticos
- **debug_whatsapp.php**: Testing y debugging de la API de WhatsApp

### Frontend (js/)
- **config.js**: Constantes de aplicación, endpoints API, colores corporativos, cuentas bancarias
- **main.js**: Funciones principales de UI, inicialización del sistema, manejo de fechas
- **database.js**: Operaciones de base de datos del lado cliente, wrappers CRUD
- **csv.js**: Funcionalidad de importación/exportación CSV
- **whatsapp.js**: Plantillas de mensajes, envío masivo, estado de entrega

### Esquema de Base de Datos
- **clientes**: Información de clientes con RUC, montos, fechas de vencimiento
- **envios_whatsapp**: Seguimiento de entrega de mensajes y estado
- **consultas_ruc**: Cache de consultas RUC y respuestas API
- **configuracion**: Configuraciones del sistema y tokens API
- **logs_sistema**: Logging de aplicación y auditoría

## Patrones Clave

### Acceso a Base de Datos
```php
$database = new Database();
$db = $database->connect();
$result = $database->fetch("SELECT * FROM clientes WHERE ruc = ?", [$ruc]);
```

### Respuestas API
```php
jsonResponse(['success' => true, 'data' => $data]);
jsonResponse(['success' => false, 'error' => 'Mensaje'], 400);
```

### Llamadas API Frontend
```javascript
fetch(API_CLIENTES_BASE + "?action=list")
    .then(response => response.json())
    .then(data => processResult(data));
```

### Gestión de Configuración
- Usar `$database->getConfig($key)` y `$database->setConfig($key, $value)` para configuraciones
- Tokens API almacenados en tabla de configuración de base de datos
- Colores de marca corporativa y cuentas bancarias definidos en js/config.js

### Generación de Imágenes Canvas
- Órdenes de pago generadas como imágenes canvas para compartir en WhatsApp
- Imágenes de logo y mascota cargadas dinámicamente
- Escalado responsivo y estilo corporativo

## Notas Importantes

- **Seguridad**: Usa declaraciones preparadas, validación de entrada y headers CORS
- **Cache**: Consultas RUC cacheadas por 24 horas para reducir llamadas API
- **Logging**: Todas las operaciones registradas en tabla logs_sistema
- **Manejo de Errores**: Toggle de modo debug para desarrollo vs producción
- **Integración WhatsApp**: Requiere tokens API válidos en configuración
- **Zona Horaria**: Configurada a America/Lima para operaciones peruanas