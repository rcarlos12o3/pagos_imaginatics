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

    // Obtener lista de clientes que ya recibieron orden de pago este mes
    let clientesYaEnviados = [];
    try {
        const response = await fetch(API_ENVIOS_BASE + '?action=enviados_mes_actual');
        const data = await response.json();
        if (data.success) {
            clientesYaEnviados = data.data.map(e => e.cliente_id);
        }
    } catch (error) {
        console.error('Error obteniendo envíos del mes:', error);
    }

    // Filtrar solo clientes que necesitan orden de pago (próximos a vencer, no vencidos) y no excluidos
    const clientesParaEnviar = clientes.filter(cliente => {
        // Verificar si está excluido manualmente
        if (cliente.excluidoEnvio) {
            return false;
        }

        // Verificar si ya se envió este mes
        if (clientesYaEnviados.includes(cliente.id)) {
            return false;
        }

        const hoy = obtenerFechaPeru();
        const fecha = new Date(cliente.fecha);
        hoy.setHours(0, 0, 0, 0);
        fecha.setHours(0, 0, 0, 0);
        const diferenciaDias = Math.floor((fecha - hoy) / (1000 * 60 * 60 * 24));

        // Determinar días de anticipación según tipo de servicio
        // Pagos anuales: 30 días de anticipación
        // Pagos trimestrales y semestrales: 15 días de anticipación
        // Pagos mensuales: 7 días de anticipación
        const tipoServicio = (cliente.tipo_servicio || 'anual').toLowerCase();
        let diasAnticipacion;
        if (tipoServicio === 'anual') {
            diasAnticipacion = 30;
        } else if (tipoServicio === 'trimestral' || tipoServicio === 'semestral') {
            diasAnticipacion = 15;
        } else {
            diasAnticipacion = 7;
        }

        // Solo enviar si vence HOY o en los próximos N días (NO enviar si ya está vencido)
        return diferenciaDias >= 0 && diferenciaDias <= diasAnticipacion;
    });

    const clientesExcluidos = clientes.filter(c => c.excluidoEnvio).length;
    const clientesYaEnviadosMes = clientes.filter(c => clientesYaEnviados.includes(c.id)).length;

    if (clientesParaEnviar.length === 0) {
        let mensaje = '❌ No hay clientes disponibles para envío.\n\n';

        if (clientesExcluidos > 0) {
            mensaje += `• ${clientesExcluidos} excluido${clientesExcluidos !== 1 ? 's' : ''} manualmente\n`;
        }

        if (clientesYaEnviadosMes > 0) {
            mensaje += `• ${clientesYaEnviadosMes} ya recibió orden de pago este mes\n`;
        }

        mensaje += '\nLas órdenes de pago solo se envían a clientes que:\n';
        mensaje += '• Vencen HOY o en los próximos días (30 días anuales, 15 trimestrales/semestrales, 7 mensuales)\n';
        mensaje += '• NO han recibido orden de pago este mes\n';
        mensaje += '• NO están vencidos (para vencidos use Recordatorios)';

        alert(mensaje);
        return;
    }

    // Cargar configuración antes de enviar
    const configCargada = await cargarConfiguracionWhatsApp();
    if (!configCargada) {
        alert('❌ Error: No se pudo cargar la configuración de WhatsApp');
        return;
    }

    // Verificar horario laboral
    const checkHorario = verificarHorarioEnvio();
    if (checkHorario.advertencia) {
        if (!confirm(checkHorario.mensaje)) {
            return;
        }
    }

    // Calcular tiempo estimado (modo cauteloso con pausas aleatorias)
    const tiempoEstimadoMin = Math.ceil(clientesParaEnviar.length * 40 / 60); // ~40 segundos por cliente mínimo
    const tiempoEstimadoMax = Math.ceil(clientesParaEnviar.length * 80 / 60); // ~80 segundos por cliente máximo

    let mensaje = `¿Enviar órdenes de pago a ${clientesParaEnviar.length} cliente${clientesParaEnviar.length !== 1 ? 's' : ''} por WhatsApp?\n\n`;
    mensaje += `⏱️ Tiempo estimado: ${tiempoEstimadoMin}-${tiempoEstimadoMax} minutos\n`;
    mensaje += `🤖 Modo CAUTELOSO: pausas 10-20s entre mensajes, 30-60s entre clientes`;

    const totalExcluidos = clientes.length - clientesParaEnviar.length;
    if (totalExcluidos > 0) {
        mensaje += `\n\n(Se excluyen ${totalExcluidos} cliente${totalExcluidos !== 1 ? 's' : ''})`;
    }

    if (!confirm(mensaje)) {
        return;
    }

    await procesarEnvioReal(clientesParaEnviar, 'orden_pago', false);
}

// Enviar recordatorios de vencimiento
async function enviarRecordatorios() {
    if (clientesNotificar.length === 0) {
        alert('No hay clientes para notificar');
        return;
    }

    // Verificar horario laboral
    const checkHorario = verificarHorarioEnvio();
    if (checkHorario.advertencia) {
        if (!confirm(checkHorario.mensaje)) {
            return;
        }
    }

    // Calcular tiempo estimado (modo cauteloso)
    const tiempoEstimadoMin = Math.ceil(clientesNotificar.length * 40 / 60);
    const tiempoEstimadoMax = Math.ceil(clientesNotificar.length * 80 / 60);

    let mensaje = `¿Enviar recordatorios a ${clientesNotificar.length} cliente${clientesNotificar.length !== 1 ? 's' : ''}?\n\n`;
    mensaje += `⏱️ Tiempo estimado: ${tiempoEstimadoMin}-${tiempoEstimadoMax} minutos\n`;
    mensaje += `🤖 Modo CAUTELOSO: pausas 10-20s entre mensajes, 30-60s entre clientes`;

    if (!confirm(mensaje)) {
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
            // Procesar con JavaScript para mejor calidad (modo cauteloso)
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
    console.log(`🎨 Procesando ${clientes.length} recordatorios con imágenes HD (MODO CAUTELOSO)...`);

    const progressBar = document.getElementById('progressBar');
    const progressText = document.getElementById('progressText');

    let exitosos = 0;
    let errores = 0;

    // Deshabilitar botones
    document.getElementById('btnEnviarRecordatorios').disabled = true;

    for (let i = 0; i < clientes.length; i++) {
        const cliente = clientes[i];

        try {
            // Actualizar progreso
            const progreso = ((i / clientes.length) * 100).toFixed(0);
            if (progressBar) {
                progressBar.style.width = progreso + '%';
                progressText.textContent = `Enviando recordatorio ${i + 1} de ${clientes.length}: ${cliente.razon_social}...`;
            }

            console.log(`📤 Generando recordatorio para: ${cliente.razon_social}`);
            console.log('Datos del cliente:', cliente);

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
                    numero_destino: cliente.whatsapp,
                    dias_restantes: diasRestantes,
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
                // Pausa aleatoria antes de enviar texto (10-20 segundos) - modo cauteloso
                if (progressText) progressText.textContent = `Esperando antes de enviar texto (comportamiento humano)...`;
                await sleepRandom(10000, 20000);

                // Generar mensaje
                const mensaje = generarMensajeRecordatorio(cliente, diasRestantes);

                // Enviar texto
                const tipoEnvio = diasRestantes < 0 ? 'recordatorio_vencido' : 'recordatorio_proximo';
                const resultadoTexto = await fetch(API_ENVIOS_BASE, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'enviar_texto',
                        cliente_id: cliente.id,
                        numero: cliente.whatsapp,
                        mensaje: mensaje,
                        tipo_envio: tipoEnvio
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

        // Pausa aleatoria entre clientes (30-60 segundos) - modo cauteloso
        if (i < clientes.length - 1) {
            if (progressText) progressText.textContent = `Pausa entre clientes (modo cauteloso)...`;
            await sleepRandom(30000, 60000);
        }
    }

    // Completar progreso
    if (progressBar) {
        progressBar.style.width = '100%';
        progressText.textContent = 'Recordatorios completados';
    }

    alert(`📊 Recordatorios procesados:\n✅ Exitosos: ${exitosos}\n❌ Errores: ${errores}`);

    // Restaurar interfaz
    setTimeout(() => {
        if (progressBar) {
            progressBar.style.width = '0%';
            progressText.textContent = '';
        }
        document.getElementById('btnEnviarRecordatorios').disabled = clientesNotificar.length === 0;
    }, 3000);
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

        // Enviar de uno en uno para simular comportamiento humano
        let exitosos = 0;
        let errores = [];

        for (let i = 0; i < clientesParaEnvio.length; i++) {
            const cliente = clientesParaEnvio[i];

            // Actualizar progreso (ahora empieza desde 25%)
            const progresoBase = 25 + ((i / clientesParaEnvio.length) * 65);
            if (progressBar) {
                progressBar.style.width = progresoBase + '%';
                progressText.textContent = `Enviando ${i + 1} de ${clientesParaEnvio.length}: ${cliente.razon_social}...`;
            }

            // Procesar cliente individual
            const resultadoCliente = await enviarClienteWhatsApp(cliente, accion);
            if (resultadoCliente.success) {
                exitosos++;
            } else {
                errores.push(resultadoCliente.error);
            }

            // Pausa aleatoria entre clientes (30-60 segundos) - comportamiento humano cauteloso
            if (i < clientesParaEnvio.length - 1) {
                if (progressText) progressText.textContent = `Pausa entre clientes (modo cauteloso)...`;
                await sleepRandom(30000, 60000);
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

// Enviar a un cliente individual por WhatsApp (comportamiento humano)
async function enviarClienteWhatsApp(cliente, accion) {
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
            return {
                success: false,
                error: `${cliente.razon_social}: Error enviando imagen - ${resultadoImagen.error}`
            };
        }

        // Pausa aleatoria antes de enviar texto (10-20 segundos) - simula escritura humana
        await sleepRandom(10000, 20000);

        // Generar y enviar texto
        const mensaje = generarMensajeOrdenPago(cliente);
        const resultadoTexto = await enviarTextoWhatsApp(cliente, mensaje);

        if (!resultadoTexto.success) {
            return {
                success: false,
                error: `${cliente.razon_social}: Error enviando texto - ${resultadoTexto.error}`
            };
        }

        console.log(`✅ Enviado completo HD a ${cliente.razon_social}`);

        // Registrar en base de datos
        await registrarEnvioEnBD(cliente, accion, true);

        return { success: true };

    } catch (error) {
        console.error(`Error procesando ${cliente.razon_social}:`, error);
        return {
            success: false,
            error: `${cliente.razon_social}: ${error.message}`
        };
    }
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
    let tipo = (cliente.tipo_servicio || 'anual').toLowerCase().trim();
    // Determinar el texto según el tipo de servicio
    let periodoTexto;
    switch (tipo) {
        case 'mensual':
            periodoTexto = 'un mes más trabajando juntos';
            break;
        case 'trimestral':
            periodoTexto = 'un trimestre más trabajando juntos';
            break;
        case 'semestral':
            periodoTexto = 'un semestre más trabajando juntos';
            break;
        case 'anual':
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

// Función para generar tiempo de espera aleatorio (más humano)
function sleepRandom(minMs, maxMs) {
    const randomTime = Math.floor(Math.random() * (maxMs - minMs + 1)) + minMs;
    console.log(`⏱️ Esperando ${(randomTime / 1000).toFixed(1)}s (comportamiento humano)`);
    return new Promise(resolve => setTimeout(resolve, randomTime));
}

// Verificar si es horario laboral (8am - 8pm hora de Perú)
function esHorarioLaboral() {
    const ahora = obtenerFechaPeru();
    const hora = ahora.getHours();
    return hora >= 8 && hora < 20;
}

// Obtener mensaje de advertencia de horario
function verificarHorarioEnvio() {
    const ahora = obtenerFechaPeru();
    const hora = ahora.getHours();

    if (hora < 8) {
        return {
            advertencia: true,
            mensaje: `⚠️ ADVERTENCIA: Son las ${hora}:${ahora.getMinutes().toString().padStart(2, '0')} AM\n\nEnviar mensajes antes de las 8:00 AM puede parecer spam.\n\n¿Desea continuar de todas formas?`
        };
    } else if (hora >= 20) {
        return {
            advertencia: true,
            mensaje: `⚠️ ADVERTENCIA: Son las ${hora}:${ahora.getMinutes().toString().padStart(2, '0')}\n\nEnviar mensajes después de las 8:00 PM puede ser molesto para los clientes.\n\n¿Desea continuar de todas formas?`
        };
    }

    return { advertencia: false };
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