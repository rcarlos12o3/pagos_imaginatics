// ============================================
// FUNCIONES DE WHATSAPP - CORREGIDAS
// Sistema RUC Consultor - Imaginatics Peru SAC
// ============================================

// Variables de configuración (se cargan desde BD)
let configWhatsApp = {
    token: null,
    instancia: null,
    apiUrl: null
};

// ============================================
// INICIALIZACIÓN Y CONFIGURACIÓN
// ============================================

// Cargar configuración de WhatsApp desde BD
async function cargarConfiguracionWhatsApp() {
    try {
        const response = await fetch(API_CLIENTES_BASE + '?action=get_config');
        const data = await response.json();

        if (data.success && data.config) {
            configWhatsApp.token = data.config.token_whatsapp;
            configWhatsApp.instancia = data.config.instancia_whatsapp;
            configWhatsApp.apiUrl = data.config.api_url_whatsapp || 'https://api.whatsapp.com/v1/'; // URL por defecto

            console.log('✅ Configuración de WhatsApp cargada');
            return true;
        } else {
            console.error('❌ Error cargando configuración WhatsApp:', data.error);
            return false;
        }
    } catch (error) {
        console.error('❌ Error de conexión cargando configuración:', error);
        return false;
    }
}

// ============================================
// FUNCIONES PRINCIPALES DE ENVÍO
// ============================================

// Enviar lote de órdenes de pago por WhatsApp
async function enviarLote() {
    if (clientes.length === 0) {
        alert('No hay clientes en la lista');
        return;
    }

    // Cargar configuración antes de enviar
    const configCargada = await cargarConfiguracionWhatsApp();
    if (!configCargada) {
        alert('❌ Error: No se pudo cargar la configuración de WhatsApp');
        return;
    }

    if (!confirm('¿Enviar ordenes de pago a ' + clientes.length + ' clientes por WhatsApp?')) {
        return;
    }

    await procesarEnvioReal(clientes, 'orden_pago', false);
}

// Enviar recordatorios de vencimiento
async function enviarRecordatorios() {
    if (clientesNotificar.length === 0) {
        alert('No hay clientes para notificar');
        return;
    }

    if (!confirm('¿Enviar recordatorios a ' + clientesNotificar.length + ' clientes?')) {
        return;
    }

    const diasAnticipacion = parseInt(document.getElementById('diasAnticipacion').value);

    try {
        // Obtener lista de clientes para recordatorios
        const response = await fetch(API_ENVIOS_BASE, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'enviar_recordatorios',
                dias_anticipacion: diasAnticipacion
            })
        });

        const data = await response.json();

        if (data.success && data.action === 'procesar_recordatorios_frontend') {
            // Procesar con JavaScript para mejor calidad
            await procesarRecordatoriosConImagenes(data.clientes);
        } else {
            alert('Error: ' + (data.error || 'Error desconocido'));
        }
    } catch (error) {
        console.error('Error enviando recordatorios:', error);
        alert('Error de conexion al enviar recordatorios');
    }
}

async function procesarRecordatoriosConImagenes(clientes) {
    console.log(`🎨 Procesando ${clientes.length} recordatorios con imágenes HD...`);

    let exitosos = 0;
    let errores = 0;

    for (const cliente of clientes) {
        try {
            console.log(`📤 Generando recordatorio para: ${cliente.razon_social}`);
            console.log('Datos del cliente:', cliente); // Debug para ver la estructura

            // USAR EL CAMPO CORRECTO
            const diasRestantes = parseInt(cliente.dias_restantes);

            // Generar canvas con colores y logo/mascota
            const canvas = await crearCanvasRecordatorioConColores(cliente, diasRestantes);
            const imagenBase64 = canvasToBase64(canvas);

            console.log('Enviando imagen con datos:', {
                cliente_id: cliente.id,
                dias_restantes: diasRestantes,
                imagen_length: imagenBase64.length
            });

            // Enviar imagen
            const resultadoImagen = await fetch(API_ENVIOS_BASE, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'generar_imagen_recordatorio',
                    cliente_id: cliente.id,
                    dias_restantes: diasRestantes,  // ← CORREGIDO: usar la variable
                    imagen_base64: imagenBase64
                })
            });

            const dataImagen = await resultadoImagen.json();
            console.log('Respuesta imagen completa:', dataImagen);
            if (!dataImagen.success) {
                console.error('Errores específicos:', dataImagen.errors);
                console.error('Error mensaje:', dataImagen.error);
            }

            if (dataImagen.success) {
                // Esperar antes del texto
                await sleep(2000);

                // Generar mensaje
                const mensaje = generarMensajeRecordatorio(cliente, diasRestantes);

                // Enviar texto
                const resultadoTexto = await fetch(API_ENVIOS_BASE, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'enviar_texto',
                        cliente_id: cliente.id,
                        numero: cliente.whatsapp,
                        mensaje: mensaje
                    })
                });

                const dataTexto = await resultadoTexto.json();
                console.log('Respuesta texto:', dataTexto);

                if (dataTexto.success) {
                    exitosos++;
                    console.log(`✅ Recordatorio completo para: ${cliente.razon_social}`);
                } else {
                    errores++;
                    console.error(`❌ Error texto para ${cliente.razon_social}:`, dataTexto.error);
                }
            } else {
                errores++;
                console.error(`❌ Error imagen para ${cliente.razon_social}:`, dataImagen.error);
            }

        } catch (error) {
            errores++;
            console.error(`❌ Error con ${cliente.razon_social}:`, error);
        }

        // Pausa entre envíos
        await sleep(3000);
    }

    alert(`📊 Recordatorios procesados:\n✅ Exitosos: ${exitosos}\n❌ Errores: ${errores}`);
}

// ============================================
// PROCESAMIENTO DE ENVÍOS
// ============================================

// Procesar envío real usando las APIs
async function procesarEnvioReal(lista, accion, esRecordatorio = false) {
    const progressBar = document.getElementById('progressBar');
    const progressText = document.getElementById('progressText');

    // Verificar elementos de progreso
    if (!progressBar || !progressText) {
        console.warn('Elementos de progreso no encontrados, continuando sin barra de progreso');
    }

    // Deshabilitar botones durante el envío
    document.getElementById('btnEnviarLote').disabled = true;
    document.getElementById('btnEnviarRecordatorios').disabled = true;
    APP_STATE.enviandoLote = true;

    try {
        if (progressText) {
            progressText.textContent = 'Iniciando envio masivo...';
            progressBar.style.width = '5%';
        }

        // Preparar datos para envío
        const clientesParaEnvio = lista.map(cliente => ({
            id: cliente.id || cliente.cliente?.id,
            ruc: cliente.ruc || cliente.cliente?.ruc,
            razon_social: cliente.razonSocial || cliente.cliente?.razonSocial,
            monto: cliente.monto || cliente.cliente?.monto,
            fecha_vencimiento: cliente.fecha || cliente.cliente?.fecha,
            whatsapp: cliente.whatsapp || cliente.cliente?.whatsapp
        }));

        // NUEVA SECCIÓN: Generar vistas previas para todos los clientes
        if (progressText) {
            progressText.textContent = 'Generando vistas previas con logos...';
            progressBar.style.width = '10%';
        }

        console.log('🎨 Generando vistas previas para todos los clientes...');
        for (let i = 0; i < clientesParaEnvio.length; i++) {
            const cliente = clientesParaEnvio[i];

            // Buscar el índice del cliente en la lista global 'clientes'
            const clienteIndex = clientes.findIndex(c => c.id === cliente.id);
            if (clienteIndex !== -1) {
                console.log(`Generando vista previa para: ${cliente.razon_social}`);

                // Seleccionar cliente para generar vista previa
                seleccionarCliente(clienteIndex);

                // Esperar que se genere la vista previa con imágenes
                await sleep(2000);
            }

            // Actualizar progreso de generación
            const progresoGeneracion = 10 + ((i / clientesParaEnvio.length) * 15);
            if (progressBar) {
                progressBar.style.width = progresoGeneracion + '%';
            }
        }

        if (progressText) {
            progressText.textContent = 'Iniciando envío masivo...';
            progressBar.style.width = '25%';
        }

        // Enviar por lotes pequeños para no saturar la API
        const LOTE_SIZE = 3;
        let exitosos = 0;
        let errores = [];

        for (let i = 0; i < clientesParaEnvio.length; i += LOTE_SIZE) {
            const lote = clientesParaEnvio.slice(i, i + LOTE_SIZE);

            // Actualizar progreso (ahora empieza desde 25%)
            const progresoBase = 25 + ((i / clientesParaEnvio.length) * 65);
            if (progressBar) {
                progressBar.style.width = progresoBase + '%';
                progressText.textContent = `Enviando lote ${Math.floor(i / LOTE_SIZE) + 1} de ${Math.ceil(clientesParaEnvio.length / LOTE_SIZE)}...`;
            }

            // Procesar lote
            const resultadoLote = await enviarLoteWhatsApp(lote, accion);
            exitosos += resultadoLote.exitosos;
            errores = errores.concat(resultadoLote.errores);

            // Pausa entre lotes
            if (i + LOTE_SIZE < clientesParaEnvio.length) {
                if (progressText) progressText.textContent = `Esperando antes del siguiente lote...`;
                await sleep(3000);
            }
        }

        // Completar progreso
        if (progressBar) {
            progressBar.style.width = '100%';
            progressText.textContent = 'Envio completado';
        }

        // Mostrar resultados finales
        mostrarResultadosEnvio(exitosos, errores.length, clientesParaEnvio.length, accion === 'orden_pago' ? 'ordenes de pago' : 'recordatorios');

    } catch (error) {
        console.error('Error procesando envio:', error);
        alert('Error de conexion durante el envio: ' + error.message);
    } finally {
        // Restaurar interfaz
        setTimeout(() => {
            if (progressBar) {
                progressBar.style.width = '0%';
                progressText.textContent = '';
            }
            document.getElementById('btnEnviarLote').disabled = false;
            document.getElementById('btnEnviarRecordatorios').disabled = clientesNotificar.length === 0;
            APP_STATE.enviandoLote = false;
        }, 3000);
    }
}

// Enviar un lote específico por WhatsApp
async function enviarLoteWhatsApp(lote, accion) {
    let exitosos = 0;
    let errores = [];

    for (const cliente of lote) {
        try {
            console.log(`🎨 Generando imagen HD para: ${cliente.razon_social}`);

            // GENERAR CANVAS DE ALTA RESOLUCIÓN (sin escalar)
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');

            // Tamaño completo (igual que en main.js)
            canvas.width = 915;
            canvas.height = 550;

            // Fondo blanco
            ctx.fillStyle = CONFIG.COLORES.FONDO_BLANCO;
            ctx.fillRect(0, 0, canvas.width, canvas.height);

            // Configurar fuentes
            ctx.textAlign = 'left';
            ctx.textBaseline = 'top';

            // Título principal
            ctx.fillStyle = CONFIG.COLORES.PRIMARIO;
            ctx.font = 'bold 28px Arial';
            ctx.fillText('IMAGINATICS PERU SAC', 50, 40);

            // Línea separadora
            ctx.strokeStyle = CONFIG.COLORES.PRIMARIO;
            ctx.lineWidth = 3;
            ctx.beginPath();
            ctx.moveTo(50, 80);
            ctx.lineTo(865, 80);
            ctx.stroke();

            // Texto principal
            ctx.fillStyle = CONFIG.COLORES.TEXTO_PRINCIPAL;
            ctx.font = '24px Arial';
            ctx.fillText('Queremos recordarte que tiene 1', 50, 120);
            ctx.fillText('orden de pago que vence el dia', 50, 150);

            // Fecha destacada
            const fechaTexto = convertirFechaATexto(cliente.fecha_vencimiento);
            ctx.fillStyle = CONFIG.COLORES.SECUNDARIO;
            ctx.font = 'bold 32px Arial';
            const fechaWidth = ctx.measureText(fechaTexto).width;
            const centerX = (canvas.width - fechaWidth) / 2;
            ctx.fillText(fechaTexto, centerX, 200);

            // Marco para la fecha
            ctx.strokeStyle = CONFIG.COLORES.SECUNDARIO;
            ctx.lineWidth = 2;
            ctx.strokeRect(centerX - 20, 195, fechaWidth + 40, 45);

            // Información del cliente
            ctx.fillStyle = CONFIG.COLORES.TEXTO_SECUNDARIO;
            ctx.font = '18px Arial';
            ctx.fillText('Cliente: ' + cliente.razon_social, 50, 270);
            ctx.fillText('RUC: ' + cliente.ruc, 50, 295);
            ctx.fillText('Monto a pagar: S/ ' + cliente.monto, 50, 320);

            // Cuentas bancarias
            ctx.fillStyle = CONFIG.COLORES.PRIMARIO;
            ctx.font = 'bold 20px Arial';
            ctx.fillText('Realice su pago a las siguientes cuentas:', 50, 360);

            ctx.fillStyle = CONFIG.COLORES.TEXTO_SECUNDARIO;
            ctx.font = '16px Arial';
            CONFIG.CUENTAS_BANCARIAS.forEach((cuenta, index) => {
                ctx.fillText(cuenta, 50, 390 + (index * 25));
            });

            // CARGAR IMÁGENES DE FORMA SÍNCRONA
            const imagenCanvas = await cargarImagenesEnCanvasSync(ctx);

            // Convertir a base64
            const imagenBase64 = canvasToBase64(canvas);

            console.log(`📤 Enviando imagen HD para: ${cliente.razon_social}`);

            // Enviar imagen
            const resultadoImagen = await enviarImagenWhatsApp(cliente, imagenBase64);

            if (!resultadoImagen.success) {
                errores.push(`${cliente.razon_social}: Error enviando imagen - ${resultadoImagen.error}`);
                continue;
            }

            // Esperar antes de enviar texto
            await sleep(2000);

            // Generar y enviar texto
            const mensaje = generarMensajeOrdenPago(cliente);
            const resultadoTexto = await enviarTextoWhatsApp(cliente, mensaje);

            if (resultadoTexto.success) {
                exitosos++;
                console.log(`✅ Enviado completo HD a ${cliente.razon_social}`);
            } else {
                errores.push(`${cliente.razon_social}: Error enviando texto - ${resultadoTexto.error}`);
            }

            // Registrar en base de datos
            await registrarEnvioEnBD(cliente, accion, resultadoImagen.success && resultadoTexto.success);

        } catch (error) {
            console.error(`Error procesando ${cliente.razon_social}:`, error);
            errores.push(`${cliente.razon_social}: ${error.message}`);
        }

        // Pausa entre envíos individuales
        await sleep(1500);
    }

    return { exitosos, errores };
}

// ============================================
// ENVÍO A API DE WHATSAPP
// ============================================

// Enviar imagen por WhatsApp
async function enviarImagenWhatsApp(cliente, imagenBase64) {
    try {
        const response = await fetch(API_ENVIOS_BASE, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'enviar_imagen',
                cliente_id: cliente.id,
                numero: cliente.whatsapp,
                imagen_base64: imagenBase64,
                filename: `orden_pago_${cliente.ruc}.png`
            })
        });

        const data = await response.json();
        return {
            success: data.success,
            error: data.error || null
        };

    } catch (error) {
        return {
            success: false,
            error: 'Error de conexion: ' + error.message
        };
    }
}

// Enviar texto por WhatsApp
async function enviarTextoWhatsApp(cliente, mensaje) {
    try {
        const response = await fetch(API_ENVIOS_BASE, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'enviar_texto',
                cliente_id: cliente.id,
                numero: cliente.whatsapp,
                mensaje: mensaje
            })
        });

        const data = await response.json();
        return {
            success: data.success,
            error: data.error || null
        };

    } catch (error) {
        return {
            success: false,
            error: 'Error de conexion: ' + error.message
        };
    }
}

// ============================================
// GENERACIÓN DE CONTENIDO
// ============================================

// Generar mensaje de orden de pago
function generarMensajeOrdenPago(cliente) {
    // Normalizamos el tipo de servicio (quitamos espacios y pasamos a minúsculas)
    let tipo = (cliente.tipo_servicio || '').toLowerCase().trim();
    // Determinar el texto según el tipo de servicio
    let periodoTexto;
    switch (cliente.tipo_servicio) {
        case 'Mensual':
            periodoTexto = 'un mes más trabajando juntos';
            break;
        case 'Trimestral':
            periodoTexto = 'un trimestre más trabajando juntos';
            break;
        case 'Semestral':
            periodoTexto = 'un semestre más trabajando juntos';
            break;
        case 'Anual':
            periodoTexto = 'un año más trabajando juntos';
            break;
        default:
            periodoTexto = 'continuar trabajando juntos';
    }
    return `Hola ${cliente.razon_social},

Estando próximo a cumplir ${periodoTexto}. Queremos recordarte que tiene una orden de Pago próximo a vencer por **S/ ${cliente.monto}** con Imaginatics Perú.

Nada mejor que llevar tus cuentas al día, por eso no olvides realizar el pago, evite los cortes de sistema. ¡Que tengas un feliz día!

📅 Fecha de vencimiento: ${formatearFecha(cliente.fecha_vencimiento)}

💳 Cuentas para pago:
${CONFIG.CUENTAS_BANCARIAS.map(cuenta => `• ${cuenta}`).join('\n')}

PD: No se olvide confirmar su pago.

Saludos,
Equipo de Cobranza - Imaginatics Peru SAC`;
}

// Registrar envío en base de datos
async function registrarEnvioEnBD(cliente, tipoEnvio, exitoso) {
    try {
        await fetch(API_ENVIOS_BASE, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'registrar_envio',
                cliente_id: cliente.id,
                tipo_envio: tipoEnvio,
                estado: exitoso ? 'enviado' : 'error'
            })
        });
    } catch (error) {
        console.error('Error registrando envio en BD:', error);
    }
}

// ============================================
// UTILIDADES
// ============================================

// Crear canvas para orden de pago (versión simplificada para envío)
function crearCanvasOrdenPago(cliente) {
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');

    canvas.width = 915;
    canvas.height = 550;

    // Fondo blanco
    ctx.fillStyle = CONFIG.COLORES.FONDO_BLANCO;
    ctx.fillRect(0, 0, canvas.width, canvas.height);

    // Título principal
    ctx.fillStyle = CONFIG.COLORES.PRIMARIO;
    ctx.font = 'bold 28px Arial';
    ctx.textAlign = 'left';
    ctx.textBaseline = 'top';
    ctx.fillText('IMAGINATICS PERU SAC', 50, 40);

    // Línea separadora
    ctx.strokeStyle = CONFIG.COLORES.PRIMARIO;
    ctx.lineWidth = 3;
    ctx.beginPath();
    ctx.moveTo(50, 80);
    ctx.lineTo(865, 80);
    ctx.stroke();

    // Texto principal
    ctx.fillStyle = CONFIG.COLORES.TEXTO_PRINCIPAL;
    ctx.font = '24px Arial';
    ctx.fillText('Queremos recordarte que tiene 1', 50, 120);
    ctx.fillText('orden de pago que vence el dia', 50, 150);

    // Fecha destacada
    const fechaTexto = convertirFechaATexto(cliente.fecha_vencimiento);
    ctx.fillStyle = CONFIG.COLORES.SECUNDARIO;
    ctx.font = 'bold 32px Arial';
    const fechaWidth = ctx.measureText(fechaTexto).width;
    const centerX = (canvas.width - fechaWidth) / 2;
    ctx.fillText(fechaTexto, centerX, 200);

    // Marco para la fecha
    ctx.strokeStyle = CONFIG.COLORES.SECUNDARIO;
    ctx.lineWidth = 2;
    ctx.strokeRect(centerX - 20, 195, fechaWidth + 40, 45);

    // Información del cliente
    ctx.fillStyle = CONFIG.COLORES.TEXTO_SECUNDARIO;
    ctx.font = '18px Arial';
    ctx.fillText('Cliente: ' + cliente.razon_social, 50, 270);
    ctx.fillText('RUC: ' + cliente.ruc, 50, 295);
    ctx.fillText('Monto a pagar: S/ ' + cliente.monto, 50, 320);

    // Cuentas bancarias
    ctx.fillStyle = CONFIG.COLORES.PRIMARIO;
    ctx.font = 'bold 20px Arial';
    ctx.fillText('Realice su pago a las siguientes cuentas:', 50, 360);

    ctx.fillStyle = CONFIG.COLORES.TEXTO_SECUNDARIO;
    ctx.font = '16px Arial';
    CONFIG.CUENTAS_BANCARIAS.forEach((cuenta, index) => {
        ctx.fillText(cuenta, 50, 390 + (index * 25));
    });

    return canvas;
}

// Convertir canvas a base64
function canvasToBase64(canvas) {
    return canvas.toDataURL('image/png').split(',')[1];
}

// Mostrar resultados del envío
function mostrarResultadosEnvio(exitosos, errores, total, tipo) {
    let mensaje = `📊 Envio de ${tipo} completado:\n\n`;
    mensaje += `✅ Exitosos: ${exitosos}/${total}\n`;
    mensaje += `❌ Errores: ${errores}/${total}\n\n`;

    if (exitosos > 0) {
        mensaje += `🎉 Se enviaron ${exitosos} mensajes correctamente.\n\n`;
    }

    if (errores > 0) {
        mensaje += `⚠️ Se registraron ${errores} errores durante el envio.\n`;
        mensaje += `Revise el historial de envios para mas detalles.\n\n`;
    }

    if (exitosos === total) {
        mensaje += `✨ ¡Envio completado con exito!`;
    } else if (exitosos > 0) {
        mensaje += `🔄 Envio parcialmente exitoso.`;
    } else {
        mensaje += `❌ El envio no se completo. Verifique la conexion y tokens.`;
    }

    alert(mensaje);
}

// Función auxiliar para pausas
function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

// Función para cargar imágenes de forma síncrona
function cargarImagenesEnCanvasSync(ctx) {
    return new Promise((resolve) => {
        let imagenesRestantes = 2; // logo y mascota

        function imagenCargada() {
            imagenesRestantes--;
            if (imagenesRestantes === 0) {
                resolve();
            }
        }

        // Cargar logo
        const logo = new Image();
        logo.onload = function () {
            ctx.drawImage(logo, 720, 40, 145, 80);
            imagenCargada();
        };
        logo.onerror = imagenCargada; // Si falla, continúa
        logo.src = 'logo.png';

        // Cargar mascota
        const mascota = new Image();
        mascota.onload = function () {
            ctx.drawImage(mascota, 650, 270, 200, 200);
            imagenCargada();
        };
        mascota.onerror = imagenCargada; // Si falla, continúa
        mascota.src = 'mascota.png';

        // Timeout por si las imágenes no cargan
        setTimeout(() => {
            resolve();
        }, 3000);
    });
}

// Función para generar canvas de recordatorio con colores según estado
function crearCanvasRecordatorio(cliente, diasRestantes) {
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');

    canvas.width = 915;
    canvas.height = 550;

    // Determinar colores y mensaje según estado
    let colorPrincipal, colorFondo, titulo, diasTexto, emoji;

    if (diasRestantes < 0) {  // Vencido
        colorPrincipal = "#FF4444";
        colorFondo = "#FFE6E6";
        titulo = "PAGO VENCIDO";
        diasTexto = `${Math.abs(diasRestantes)} días de atraso`;
        emoji = "🚨";
    } else if (diasRestantes === 0) {  // Vence hoy
        colorPrincipal = "#FF8800";
        colorFondo = "#FFF4E6";
        titulo = "PAGO VENCE HOY";
        diasTexto = "Último día para pagar";
        emoji = "⏰";
    } else {  // Por vencer
        colorPrincipal = "#FFB800";
        colorFondo = "#FFFAE6";
        titulo = "RECORDATORIO DE PAGO";
        diasTexto = `${diasRestantes} días restantes`;
        emoji = "⚠️";
    }

    // Fondo blanco
    ctx.fillStyle = "#FFFFFF";
    ctx.fillRect(0, 0, canvas.width, canvas.height);

    // Borde de alerta
    ctx.strokeStyle = colorPrincipal;
    ctx.lineWidth = 5;
    ctx.strokeRect(10, 10, canvas.width - 20, canvas.height - 20);

    ctx.lineWidth = 2;
    ctx.strokeRect(15, 15, canvas.width - 30, canvas.height - 30);

    // Título de alerta con fondo
    ctx.font = 'bold 40px Arial';
    ctx.textAlign = 'center';
    const titleText = `${emoji} ${titulo}`;
    const titleWidth = ctx.measureText(titleText).width;
    const centerX = canvas.width / 2;

    // Fondo para el título
    ctx.fillStyle = colorFondo;
    ctx.fillRect(centerX - titleWidth / 2 - 20, 160, titleWidth + 40, 50);
    ctx.strokeStyle = colorPrincipal;
    ctx.lineWidth = 2;
    ctx.strokeRect(centerX - titleWidth / 2 - 20, 160, titleWidth + 40, 50);

    // Texto del título
    ctx.fillStyle = colorPrincipal;
    ctx.fillText(titleText, centerX, 190);

    // Información del cliente
    ctx.textAlign = 'left';
    ctx.font = '28px Arial';
    ctx.fillStyle = '#333333';
    ctx.fillText(`Cliente: ${cliente.razon_social}`, 50, 240);

    ctx.font = '24px Arial';
    ctx.fillStyle = '#666666';
    ctx.fillText(`Fecha de vencimiento: ${formatearFecha(cliente.fecha_vencimiento)}`, 50, 280);

    // Destacar días restantes/atraso
    ctx.font = 'bold 36px Arial';
    ctx.textAlign = 'center';
    const diasWidth = ctx.measureText(diasTexto).width;

    // Fondo destacado para días
    ctx.fillStyle = colorPrincipal;
    ctx.fillRect(centerX - diasWidth / 2 - 15, 315, diasWidth + 30, 45);

    // Texto en blanco
    ctx.fillStyle = 'white';
    ctx.fillText(diasTexto, centerX, 345);

    // Cuentas bancarias
    ctx.textAlign = 'left';
    ctx.font = 'bold 24px Arial';
    ctx.fillStyle = colorPrincipal;
    ctx.fillText('Realice su pago a las siguientes cuentas:', 50, 380);

    ctx.font = '18px Arial';
    ctx.fillStyle = '#666666';
    CONFIG.CUENTAS_BANCARIAS.forEach((cuenta, index) => {
        ctx.fillText(cuenta, 50, 410 + (index * 25));
    });

    return canvas;
}

// Función para generar canvas de recordatorio con colores (NUEVA)
function crearCanvasRecordatorioConColores(cliente, diasRestantes) {
    return new Promise((resolve) => {
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');

        canvas.width = 915;
        canvas.height = 550;

        // Determinar colores según estado
        let colorPrincipal, colorFondo, titulo, diasTexto, emoji;

        if (diasRestantes < 0) {  // Vencido
            colorPrincipal = "#FF4444";
            colorFondo = "#FFE6E6";
            titulo = "PAGO VENCIDO";
            diasTexto = `${Math.abs(diasRestantes)} días de atraso`;
            emoji = "🚨";
        } else if (diasRestantes === 0) {  // Vence hoy
            colorPrincipal = "#FF8800";
            colorFondo = "#FFF4E6";
            titulo = "PAGO VENCE HOY";
            diasTexto = "Último día para pagar";
            emoji = "⏰";
        } else {  // Por vencer
            colorPrincipal = "#FFB800";
            colorFondo = "#FFFAE6";
            titulo = "RECORDATORIO DE PAGO";
            diasTexto = `${diasRestantes} días restantes`;
            emoji = "⚠️";
        }

        // Fondo blanco
        ctx.fillStyle = "#FFFFFF";
        ctx.fillRect(0, 0, canvas.width, canvas.height);

        // Bordes de alerta
        ctx.strokeStyle = colorPrincipal;
        ctx.lineWidth = 5;
        ctx.strokeRect(10, 10, canvas.width - 20, canvas.height - 20);
        ctx.lineWidth = 2;
        ctx.strokeRect(15, 15, canvas.width - 30, canvas.height - 30);

        // Logo empresa
        ctx.fillStyle = colorPrincipal;
        ctx.font = 'bold 24px Arial';
        ctx.textAlign = 'left';
        ctx.fillText('IMAGINATICS PERU SAC', 50, 70);

        // Título con fondo
        ctx.font = 'bold 32px Arial';
        ctx.textAlign = 'center';
        const titleText = `${emoji} ${titulo}`;
        const titleWidth = ctx.measureText(titleText).width;
        const centerX = canvas.width / 2;

        // Fondo para el título
        ctx.fillStyle = colorFondo;
        ctx.fillRect(centerX - titleWidth / 2 - 20, 140, titleWidth + 40, 50);
        ctx.strokeStyle = colorPrincipal;
        ctx.lineWidth = 2;
        ctx.strokeRect(centerX - titleWidth / 2 - 20, 140, titleWidth + 40, 50);

        // Texto del título
        ctx.fillStyle = colorPrincipal;
        ctx.fillText(titleText, centerX, 170);

        // Información del cliente
        ctx.textAlign = 'left';
        ctx.font = '22px Arial';
        ctx.fillStyle = '#333333';
        ctx.fillText(`Cliente: ${cliente.razon_social}`, 50, 220);

        ctx.font = '18px Arial';
        ctx.fillStyle = '#666666';
        ctx.fillText(`RUC: ${cliente.ruc}`, 50, 250);
        ctx.fillText(`Monto: S/ ${cliente.monto}`, 50, 275);
        ctx.fillText(`Fecha vencimiento: ${formatearFecha(cliente.fecha_vencimiento)}`, 50, 300);

        // Destacar días restantes/atraso
        ctx.font = 'bold 28px Arial';
        ctx.textAlign = 'center';
        const diasWidth = ctx.measureText(diasTexto).width;

        // Fondo destacado para días
        ctx.fillStyle = colorPrincipal;
        ctx.fillRect(centerX - diasWidth / 2 - 20, 330, diasWidth + 40, 40);

        // Texto en blanco
        ctx.fillStyle = 'white';
        ctx.fillText(diasTexto, centerX, 355);

        // Cuentas bancarias
        ctx.textAlign = 'left';
        ctx.font = 'bold 18px Arial';
        ctx.fillStyle = colorPrincipal;
        ctx.fillText('Realice su pago a las siguientes cuentas:', 50, 400);

        ctx.font = '16px Arial';
        ctx.fillStyle = '#666666';
        CONFIG.CUENTAS_BANCARIAS.forEach((cuenta, index) => {
            ctx.fillText(cuenta, 50, 425 + (index * 20));
        });

        // Cargar imágenes de forma asíncrona
        let imagenesRestantes = 2;
        function imagenCargada() {
            imagenesRestantes--;
            if (imagenesRestantes === 0) {
                resolve(canvas);
            }
        }

        // Cargar logo
        const logo = new Image();
        logo.onload = function () {
            // Dibujar logo más pequeño
            ctx.drawImage(logo, canvas.width - 200, 30, 150, 80);
            imagenCargada();
        };
        logo.onerror = imagenCargada;
        logo.src = 'logo.png';

        // Cargar mascota
        const mascota = new Image();
        mascota.onload = function () {
            // Dibujar mascota
            ctx.drawImage(mascota, canvas.width - 250, canvas.height - 250, 200, 200);
            imagenCargada();
        };
        mascota.onerror = imagenCargada;
        mascota.src = 'mascota.png';

        // Timeout por si las imágenes no cargan
        setTimeout(() => {
            resolve(canvas);
        }, 3000);
    });
}

// Función para generar mensaje de recordatorio (AGREGAR en whatsapp.js)
function generarMensajeRecordatorio(cliente, diasRestantes) {
    const fechaFormateada = formatearFecha(cliente.fecha_vencimiento);

    if (diasRestantes < 0) {
        const diasAtraso = Math.abs(diasRestantes);
        return `🚨 PAGO VENCIDO - ${cliente.razon_social}

Su orden de pago tiene ${diasAtraso} días de atraso (venció el ${fechaFormateada}).

Para evitar suspensión del servicio, le solicitamos regularizar su pago a la brevedad.

💰 Monto: S/ ${cliente.monto}
📅 Fecha de vencimiento: ${fechaFormateada}

¡Contacte con nosotros para coordinar su pago!

Saludos,
Equipo de Cobranza - Imaginatics Peru SAC`;

    } else if (diasRestantes === 0) {
        return `⏰ ÚLTIMO DÍA - ${cliente.razon_social}

Su orden de pago VENCE HOY (${fechaFormateada}).

No pierda la oportunidad de mantener su servicio activo.

💰 Monto: S/ ${cliente.monto}

¡Realice su pago hoy mismo!

Saludos,
Equipo de Cobranza - Imaginatics Peru SAC`;

    } else {
        return `⚠️ RECORDATORIO - ${cliente.razon_social}

Su orden de pago vence en ${diasRestantes} días (${fechaFormateada}).

Mantenga sus cuentas al día para evitar interrupciones.

💰 Monto: S/ ${cliente.monto}
📅 Fecha de vencimiento: ${fechaFormateada}

¡Gracias por su atención!

Saludos,
Equipo de Cobranza - Imaginatics Peru SAC`;
    }
}