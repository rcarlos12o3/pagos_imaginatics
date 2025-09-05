// ============================================
// CONFIGURACION Y VARIABLES GLOBALES
// Sistema RUC Consultor - Imaginatics Peru SAC
// ============================================

// Variables globales
let clientes = [];
let clienteSeleccionado = -1;
let clientesNotificar = [];

// URLs de las APIs
const API_RUC_BASE = "api/consultar_ruc.php?ruc=";
const API_CLIENTES_BASE = "api/clientes.php";
const API_ENVIOS_BASE = "api/envios.php";

// Configuracion de la aplicacion
const CONFIG = {
    FECHA_DIAS_DEFECTO: 7,          // Días por defecto para vencimiento
    ANTICIPACION_DEFECTO: 3,         // Días de anticipación para recordatorios
    TIMEOUT_IMAGEN: 5000,           // Timeout para carga de imágenes en ms
    ESCALA_VISTA_PREVIA: 0.4,       // Escala para vista previa
    MAX_TAMAÑO_IMAGEN: 800,         // Tamaño máximo de imagen antes de redimensionar

    // Mensajes de la aplicación
    MENSAJES: {
        SIN_CLIENTES: 'Agregue clientes para ver la vista previa',
        SELECCIONAR_CLIENTE: '👆 Haga clic en un cliente para ver la vista previa',
        PROCESANDO: 'Procesando...',
        ENVIANDO: 'Enviando...',
        CONSULTANDO: 'Consultando...'
    },

    // Colores corporativos
    COLORES: {
        PRIMARIO: '#2581c4',
        SECUNDARIO: '#f39325',
        TEXTO_PRINCIPAL: '#333333',
        TEXTO_SECUNDARIO: '#666666',
        FONDO_BLANCO: '#FFFFFF',
        FONDO_GRIS: '#f8f9fa',
        BORDE_SUTIL: '#e9ecef'
    },

    // Cuentas bancarias para las órdenes de pago
    CUENTAS_BANCARIAS: [
        'BCP: 19393234096052',
        'SCOTIABANK: 940-0122553',
        'INTERBANK: 562-3108838683',
        'BBVA: 0011-0057-0294807188',
        'YAPE/PLIN: 989613295'
    ]
};

// Meses en español para conversión de fechas
const MESES = [
    'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
    'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
];

// Estado de la aplicación
const APP_STATE = {
    conectadoBD: false,
    imagenesVerificadas: false,
    enviandoLote: false
};