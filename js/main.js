// ============================================
// MAIN.JS - FUNCIONES PRINCIPALES Y UI
// Sistema RUC Consultor - Imaginatics Peru SAC
// ============================================

// ============================================
// INICIALIZACIÓN DEL SISTEMA
// ============================================

document.addEventListener('DOMContentLoaded', function () {
    inicializarSistema();
    configurarEventListeners();
    cargarDatosIniciales();
});

function inicializarSistema() {
    console.log('Sistema RUC Consultor - Imaginatics Peru SAC');
    console.log('Funcionalidades disponibles:');
    console.log('- Consulta RUC con cache');
    console.log('- Persistencia en MySQL');
    console.log('- Historial de envios');
    console.log('- Reportes y estadisticas');
    console.log('- Vista previa con Canvas');
    console.log('- Carga automatica de logo y mascota');

    configurarFechaDefecto();
    verificarImagenes();
    mostrarFechaActual();
}

function configurarFechaDefecto() {
    const fechaInput = document.getElementById('fechaVencimiento');
    if (fechaInput) {
        const hoy = obtenerFechaPeru();
        hoy.setDate(hoy.getDate() + CONFIG.FECHA_DIAS_DEFECTO);
        fechaInput.value = formatearFechaISO(hoy);
    }

    // Configurar días de anticipación por defecto
    const diasInput = document.getElementById('diasAnticipacion');
    if (diasInput) {
        diasInput.value = CONFIG.ANTICIPACION_DEFECTO;
    }
}

function mostrarFechaActual() {
    const fechaElemento = document.getElementById('fecha-actual');
    if (fechaElemento) {
        const hoy = obtenerFechaPeru();
        fechaElemento.textContent = hoy.toLocaleDateString('es-PE', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }
}

function cargarDatosIniciales() {
    cargarClientesDesdeDB().then(() => {
        console.log('Aplicacion cargada - Conectada a MySQL');
    }).catch(() => {
        console.log('MySQL no disponible - Modo local activo');
    });
}

// ============================================
// EVENT LISTENERS
// ============================================

function configurarEventListeners() {
    // Validación en tiempo real para RUC
    const rucInput = document.getElementById('ruc');
    if (rucInput) {
        rucInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                consultarRUC();
            }
        });

        rucInput.addEventListener('input', function (e) {
            this.value = this.value.replace(/[^0-9]/g, '');
            if (this.value.length === 11) {
                this.style.borderColor = '#28a745';
            } else {
                this.style.borderColor = '#ced4da';
            }
        });
    }

    // Validación para WhatsApp
    const whatsappInput = document.getElementById('whatsapp');
    if (whatsappInput) {
        whatsappInput.addEventListener('input', function (e) {
            this.value = this.value.replace(/[^0-9]/g, '');
            if (this.value.length === 9) {
                this.style.borderColor = '#28a745';
            } else {
                this.style.borderColor = '#ced4da';
            }
        });
    }

    // Validación para monto
    const montoInput = document.getElementById('monto');
    if (montoInput) {
        montoInput.addEventListener('input', function (e) {
            const valor = parseFloat(this.value);
            if (isNaN(valor) || valor < 0) {
                this.style.borderColor = '#dc3545';
            } else {
                this.style.borderColor = '#28a745';
            }
        });
    }

    // Event listeners para archivos CSV
    const csvInput = document.getElementById('csvFile');
    if (csvInput) {
        csvInput.addEventListener('change', cargarCSV);
    }

    // Event listener para el filtro de búsqueda
    const searchFilter = document.getElementById('searchFilter');
    if (searchFilter) {
        searchFilter.addEventListener('input', function(e) {
            filtrarClientes(this.value);
        });
    }
}

// ============================================
// FUNCIONES DE VALIDACIÓN
// ============================================

function validarRUC(ruc) {
    const rucLimpio = ruc.replace(/[^0-9]/g, '');

    if (rucLimpio.length !== 11) {
        return { valido: false, mensaje: 'El RUC debe tener 11 digitos' };
    }

    if (!/^\d+$/.test(rucLimpio)) {
        return { valido: false, mensaje: 'El RUC debe contener solo numeros' };
    }

    return { valido: true, ruc: rucLimpio };
}

// ============================================
// FUNCIONES DE UI - GESTIÓN DE CLIENTES
// ============================================

function mostrarRazonSocial(razonSocial) {
    const display = document.getElementById('razonSocialDisplay');
    const text = document.getElementById('razonSocialText');
    const editField = document.getElementById('razonSocial');
    const editContainer = document.getElementById('razonSocialEdit');

    if (display && text) {
        text.textContent = razonSocial;
        display.classList.remove('hidden');
    }

    // Si el campo editable está visible (modo edición), actualizarlo también
    if (editContainer && !editContainer.classList.contains('hidden') && editField) {
        const razonSocialLimpia = razonSocial.split(' (')[0]; // Remover etiquetas como (Cache) o (API)
        editField.value = razonSocialLimpia;
    }
}

function actualizarListaClientes(clientesFiltrados = null) {
    const lista = document.getElementById('clientList');
    const count = document.getElementById('clientCount');
    const clientesAMostrar = clientesFiltrados || clientes;

    if (count) {
        count.textContent = clientes.length;
    }

    if (!lista) return;

    if (clientes.length === 0) {
        lista.innerHTML = `
            <div style="padding: 40px; text-align: center; color: #6c757d;">
                No hay clientes agregados.<br>
                Agregue clientes usando el formulario o cargue un archivo CSV.
            </div>
        `;

        // Limpiar vista previa cuando no hay clientes
        const previewDiv = document.getElementById('previewCanvas');
        if (previewDiv) {
            previewDiv.innerHTML = `
                <span class="emoji">🖼️</span><br>
                ${CONFIG.MENSAJES.SIN_CLIENTES}
            `;
        }
        return;
    }

    if (clientesAMostrar.length === 0) {
        lista.innerHTML = `
            <div style="padding: 40px; text-align: center; color: #6c757d;">
                <span class="emoji">🔍</span><br>
                No se encontraron clientes con ese criterio de búsqueda.
            </div>
        `;
        return;
    }

    lista.innerHTML = clientesAMostrar.map((cliente, index) => {
        const indexOriginal = clientes.indexOf(cliente);
        const estadoPago = obtenerEstadoPago(cliente.fecha, cliente.tipo_servicio);
        return `
        <div class="client-item ${estadoPago.clase}" onclick="seleccionarCliente(${indexOriginal})" data-index="${indexOriginal}">
            <div class="client-name">
                <span class="client-id">#${cliente.id}</span>
                ${cliente.razonSocial}
            </div>
            <div class="client-info">
                <div><strong>RUC:</strong> ${cliente.ruc}</div>
                <div><strong>Monto:</strong> S/ ${cliente.monto}</div>
                <div><strong>Fecha:</strong> ${formatearFecha(cliente.fecha)}</div>
                <div><strong>WhatsApp:</strong> ${cliente.whatsapp}</div>
                <div><strong>Servicio:</strong> <span class="tipo-servicio">${cliente.tipo_servicio || 'anual'}</span></div>
            </div>
            <div class="client-actions" style="display: flex; gap: 8px; margin-top: 10px;">
                <button class="btn-detalle" onclick="event.stopPropagation(); verDetalleCliente(${cliente.id})" style="flex: 1; padding: 6px 10px; background: #2581c4; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 12px; transition: all 0.3s;">📊 Ver Detalle</button>
                <button class="btn-servicios" onclick="event.stopPropagation(); ServiciosUI.mostrarServiciosCliente(${cliente.id})" style="flex: 1; padding: 6px 10px; background: #f39325; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 12px; transition: all 0.3s;">🛠️ Servicios</button>
            </div>
        </div>
        `;
    }).join('');

    // Actualizar mensaje de vista previa
    if (clienteSeleccionado === -1) {
        const previewDiv = document.getElementById('previewCanvas');
        if (previewDiv) {
            previewDiv.innerHTML = `
                <span class="emoji">👆</span><br>
                ${CONFIG.MENSAJES.SELECCIONAR_CLIENTE}
            `;
        }
    }
}

// Función para filtrar clientes
function filtrarClientes(termino) {
    if (!termino.trim()) {
        actualizarListaClientes();
        return;
    }

    termino = termino.toLowerCase().trim();

    const clientesFiltrados = clientes.filter(cliente => {
        const id = cliente.id ? cliente.id.toString() : '';
        const ruc = cliente.ruc.toLowerCase();
        const razonSocial = cliente.razonSocial.toLowerCase();
        const whatsapp = cliente.whatsapp.toLowerCase();

        return id.includes(termino) ||
               ruc.includes(termino) ||
               razonSocial.includes(termino) ||
               whatsapp.includes(termino);
    });

    actualizarListaClientes(clientesFiltrados);
}

function seleccionarCliente(index) {
    document.querySelectorAll('.client-item').forEach(item => {
        item.classList.remove('selected');
    });

    const item = document.querySelector(`[data-index="${index}"]`);
    if (item) {
        item.classList.add('selected');
        clienteSeleccionado = index;

        const btnEliminar = document.getElementById('btnEliminarCliente');
        if (btnEliminar) {
            btnEliminar.disabled = false;
        }

        // Generar vista previa automáticamente al seleccionar cliente
        const cliente = clientes[index];
        crearVistaPreviaCanvas(cliente);
    }
}

function formatearFecha(fecha) {
    if (!fecha) return 'N/A';
    
    let fechaObj;
    
    // Intentar diferentes formatos
    if (typeof fecha === 'string') {
        // Si ya tiene formato de timestamp, usar directamente
        if (fecha.includes('T') || fecha.includes(' ')) {
            fechaObj = new Date(fecha);
        } else {
            // Formato ISO date (YYYY-MM-DD)
            fechaObj = new Date(fecha + 'T00:00:00');
        }
    } else {
        // Si es un objeto Date o timestamp
        fechaObj = new Date(fecha);
    }
    
    // Verificar si la fecha es válida
    if (isNaN(fechaObj.getTime())) {
        console.warn('Fecha inválida:', fecha);
        return 'Fecha inválida';
    }
    
    return fechaObj.toLocaleDateString('es-PE');
}

function convertirFechaATexto(fechaISO) {
    if (!fechaISO) return 'Fecha no disponible';
    
    let fecha;
    
    // Usar la misma lógica que formatearFecha
    if (typeof fechaISO === 'string') {
        if (fechaISO.includes('T') || fechaISO.includes(' ')) {
            fecha = new Date(fechaISO);
        } else {
            fecha = new Date(fechaISO + 'T00:00:00');
        }
    } else {
        fecha = new Date(fechaISO);
    }
    
    // Verificar si la fecha es válida
    if (isNaN(fecha.getTime())) {
        console.warn('Fecha inválida en convertirFechaATexto:', fechaISO);
        return 'Fecha inválida';
    }
    
    const dia = fecha.getDate();
    const mes = MESES[fecha.getMonth()];
    const año = fecha.getFullYear();

    return dia + ' de ' + mes + ' de ' + año;
}

// ============================================
// FUNCIONES DE UI - VISTA PREVIA CON CANVAS
// ============================================

function mostrarVistaPrevia() {
    if (clienteSeleccionado === -1) {
        alert('Seleccione un cliente para ver la vista previa');
        return;
    }

    const cliente = clientes[clienteSeleccionado];
    crearVistaPreviaCanvas(cliente);
}

function crearVistaPreviaCanvas(cliente) {
    const previewDiv = document.getElementById('previewCanvas');
    if (!previewDiv) return;

    // Crear canvas
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');

    // Configurar tamaño
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
    const fechaTexto = convertirFechaATexto(cliente.fecha);
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
    ctx.fillText('Cliente: ' + cliente.razonSocial, 50, 270);
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

    // Intentar cargar logo y mascota con tamaño optimizado
    cargarImagenEnCanvas(ctx, 'logo.png', 720, 40, 145, 80, 'LOGO', function () {
        cargarImagenEnCanvas(ctx, 'mascota.png', 650, 270, 200, 200, 'MASCOTA', function () {
            // Cuando ambas imágenes se procesen, mostrar vista previa
            mostrarCanvasEnVista(canvas);
        });
    });

    // Si las imágenes no cargan en 2 segundos, mostrar de todos modos
    setTimeout(() => {
        mostrarCanvasEnVista(canvas);
    }, CONFIG.TIMEOUT_IMAGEN);
}

function cargarImagenEnCanvas(ctx, rutaImagen, x, y, width, height, textoFallback, callback) {
    const img = new Image();

    img.onload = function () {
        // Redimensionar imagen si es muy grande
        let maxWidth = width;
        let maxHeight = height;

        // Si la imagen es muy grande, reducir el tamaño máximo
        if (img.width > CONFIG.MAX_TAMAÑO_IMAGEN || img.height > 600) {
            maxWidth = Math.min(width, 120);
            maxHeight = Math.min(height, 120);
        }

        // Calcular escala para mantener aspecto
        const imgAspect = img.width / img.height;
        const boxAspect = maxWidth / maxHeight;

        let drawWidth, drawHeight, drawX, drawY;

        if (imgAspect > boxAspect) {
            drawWidth = maxWidth;
            drawHeight = maxWidth / imgAspect;
            drawX = x + (width - drawWidth) / 2;
            drawY = y + (height - drawHeight) / 2;
        } else {
            drawWidth = maxHeight * imgAspect;
            drawHeight = maxHeight;
            drawX = x + (width - drawWidth) / 2;
            drawY = y + (height - drawHeight) / 2;
        }

        // Fondo blanco para las imágenes
        ctx.fillStyle = CONFIG.COLORES.FONDO_BLANCO;
        ctx.fillRect(x, y, width, height);
        ctx.strokeStyle = CONFIG.COLORES.BORDE_SUTIL;
        ctx.lineWidth = 1;
        ctx.strokeRect(x, y, width, height);

        // Dibujar imagen redimensionada
        ctx.drawImage(img, drawX, drawY, drawWidth, drawHeight);

        if (callback) callback();
    };

    img.onerror = function () {
        // Si la imagen no se carga, mostrar placeholder
        ctx.fillStyle = CONFIG.COLORES.FONDO_GRIS;
        ctx.fillRect(x, y, width, height);
        ctx.strokeStyle = CONFIG.COLORES.PRIMARIO;
        ctx.lineWidth = 2;
        ctx.strokeRect(x, y, width, height);
        ctx.fillStyle = CONFIG.COLORES.PRIMARIO;
        ctx.font = '14px Arial';
        ctx.textAlign = 'center';
        ctx.fillText(textoFallback, x + width / 2, y + height / 2);
        ctx.textAlign = 'left';

        if (callback) callback();
    };

    img.src = rutaImagen;
}

function mostrarCanvasEnVista(canvas) {
    const previewDiv = document.getElementById('previewCanvas');
    if (!previewDiv) return;

    // Redimensionar para vista previa
    const previewCanvas = document.createElement('canvas');
    const previewCtx = previewCanvas.getContext('2d');

    const scale = CONFIG.ESCALA_VISTA_PREVIA;
    previewCanvas.width = canvas.width * scale;
    previewCanvas.height = canvas.height * scale;

    previewCtx.drawImage(canvas, 0, 0, previewCanvas.width, previewCanvas.height);

    // Limpiar y mostrar
    previewDiv.innerHTML = '';
    previewCanvas.style.border = '1px solid #ddd';
    previewCanvas.style.borderRadius = '8px';
    previewCanvas.style.boxShadow = '0 2px 8px rgba(0,0,0,0.1)';
    previewCanvas.style.maxWidth = '100%';
    previewCanvas.style.height = 'auto';
    previewDiv.appendChild(previewCanvas);
}

// ============================================
// FUNCIONES DE UI - VENCIMIENTOS
// ============================================

function mostrarResultadosVencimientos(resultado) {
    const totalClientes = resultado.vencidos.length + resultado.vence_hoy.length + resultado.por_vencer.length;

    if (totalClientes === 0) {
        alert('ℹ️ No hay clientes para recordatorios de vencimiento.\n\n' +
              '📋 Los recordatorios solo se envían a clientes que:\n' +
              '• Ya recibieron su orden de pago este mes\n' +
              '• Están próximos a vencer o vencidos\n\n' +
              '💡 Primero envíe las órdenes de pago (Sección 5: Envío en Lote)');
        return;
    }

    const modal = document.getElementById('modalVencimientos');
    const totalSpan = document.getElementById('totalClientesVencimiento');
    const container = document.getElementById('listaVencimientosContainer');

    // Actualizar total
    totalSpan.textContent = totalClientes;

    let html = '';

    // Sección de VENCIDOS
    if (resultado.vencidos.length > 0) {
        html += `
            <div style="margin-bottom: 20px;">
                <div style="background: #ff6b6b; color: white; padding: 12px; border-radius: 8px 8px 0 0; font-weight: bold;">
                    🚨 VENCIDOS (${resultado.vencidos.length})
                </div>
                <div style="border: 2px solid #ff6b6b; border-top: none; border-radius: 0 0 8px 8px; overflow: hidden;">
        `;

        resultado.vencidos.forEach((cliente, index) => {
            const diasAtraso = Math.abs(cliente.dias_restantes);
            const clienteIndex = clientes.findIndex(c => c.id === cliente.id);
            const clienteLocal = clientes[clienteIndex];
            const estaExcluido = clienteLocal && clienteLocal.excluidoNotificaciones;

            html += `
                <div style="padding: 12px; border-bottom: 1px solid #f8f9fa; ${index % 2 === 0 ? 'background: #fff5f5;' : 'background: white;'}">
                    <div style="display: grid; grid-template-columns: 1fr auto; gap: 10px; align-items: start;">
                        <div>
                            <div style="font-weight: bold; color: #c92a2a; margin-bottom: 4px;">${cliente.razon_social}</div>
                            <div style="font-size: 13px; color: #666;">
                                RUC: ${cliente.ruc} •
                                WhatsApp: ${cliente.whatsapp ? (cliente.whatsapp.startsWith('51') ? '+' + cliente.whatsapp : '+51' + cliente.whatsapp) : 'No registrado'} •
                                Monto: S/ ${cliente.monto} •
                                <strong style="color: #c92a2a;">${diasAtraso} día${diasAtraso !== 1 ? 's' : ''} de atraso</strong>
                            </div>
                            ${estaExcluido && clienteLocal.motivoExclusionNotif ? `<div style="font-size: 11px; color: #856404; margin-top: 4px; font-style: italic; background: #fff3cd; padding: 4px 8px; border-radius: 4px;">💬 ${clienteLocal.motivoExclusionNotif}</div>` : ''}
                        </div>
                        <div>
                            ${!estaExcluido ?
                                `<button onclick="excluirClienteNotificaciones(${clienteIndex})" class="btn btn-warning" style="padding: 6px 12px; font-size: 11px; white-space: nowrap;" title="Excluir de notificaciones">🔕 Excluir</button>` :
                                `<button onclick="incluirClienteNotificaciones(${clienteIndex})" class="btn btn-success" style="padding: 6px 12px; font-size: 11px; white-space: nowrap;" title="Incluir en notificaciones">✅ Incluir</button>`
                            }
                        </div>
                    </div>
                </div>
            `;
        });

        html += '</div></div>';
    }

    // Sección de VENCE HOY
    if (resultado.vence_hoy.length > 0) {
        html += `
            <div style="margin-bottom: 20px;">
                <div style="background: #ff8800; color: white; padding: 12px; border-radius: 8px 8px 0 0; font-weight: bold;">
                    ⏰ VENCE HOY (${resultado.vence_hoy.length})
                </div>
                <div style="border: 2px solid #ff8800; border-top: none; border-radius: 0 0 8px 8px; overflow: hidden;">
        `;

        resultado.vence_hoy.forEach((cliente, index) => {
            const clienteIndex = clientes.findIndex(c => c.id === cliente.id);
            const clienteLocal = clientes[clienteIndex];
            const estaExcluido = clienteLocal && clienteLocal.excluidoNotificaciones;

            html += `
                <div style="padding: 12px; border-bottom: 1px solid #f8f9fa; ${index % 2 === 0 ? 'background: #fff4e6;' : 'background: white;'}">
                    <div style="display: grid; grid-template-columns: 1fr auto; gap: 10px; align-items: start;">
                        <div>
                            <div style="font-weight: bold; color: #d9480f; margin-bottom: 4px;">${cliente.razon_social}</div>
                            <div style="font-size: 13px; color: #666;">
                                RUC: ${cliente.ruc} •
                                WhatsApp: ${cliente.whatsapp ? (cliente.whatsapp.startsWith('51') ? '+' + cliente.whatsapp : '+51' + cliente.whatsapp) : 'No registrado'} •
                                Monto: S/ ${cliente.monto} •
                                <strong style="color: #d9480f;">ÚLTIMO DÍA</strong>
                            </div>
                            ${estaExcluido && clienteLocal.motivoExclusionNotif ? `<div style="font-size: 11px; color: #856404; margin-top: 4px; font-style: italic; background: #fff3cd; padding: 4px 8px; border-radius: 4px;">💬 ${clienteLocal.motivoExclusionNotif}</div>` : ''}
                        </div>
                        <div>
                            ${!estaExcluido ?
                                `<button onclick="excluirClienteNotificaciones(${clienteIndex})" class="btn btn-warning" style="padding: 6px 12px; font-size: 11px; white-space: nowrap;" title="Excluir de notificaciones">🔕 Excluir</button>` :
                                `<button onclick="incluirClienteNotificaciones(${clienteIndex})" class="btn btn-success" style="padding: 6px 12px; font-size: 11px; white-space: nowrap;" title="Incluir en notificaciones">✅ Incluir</button>`
                            }
                        </div>
                    </div>
                </div>
            `;
        });

        html += '</div></div>';
    }

    // Sección de POR VENCER
    if (resultado.por_vencer.length > 0) {
        html += `
            <div style="margin-bottom: 20px;">
                <div style="background: #fab005; color: white; padding: 12px; border-radius: 8px 8px 0 0; font-weight: bold;">
                    ⚠️ POR VENCER (${resultado.por_vencer.length})
                </div>
                <div style="border: 2px solid #fab005; border-top: none; border-radius: 0 0 8px 8px; overflow: hidden;">
        `;

        resultado.por_vencer.forEach((cliente, index) => {
            const clienteIndex = clientes.findIndex(c => c.id === cliente.id);
            const clienteLocal = clientes[clienteIndex];
            const estaExcluido = clienteLocal && clienteLocal.excluidoNotificaciones;

            html += `
                <div style="padding: 12px; border-bottom: 1px solid #f8f9fa; ${index % 2 === 0 ? 'background: #fffae6;' : 'background: white;'}">
                    <div style="display: grid; grid-template-columns: 1fr auto; gap: 10px; align-items: start;">
                        <div>
                            <div style="font-weight: bold; color: #e67700; margin-bottom: 4px;">${cliente.razon_social}</div>
                            <div style="font-size: 13px; color: #666;">
                                RUC: ${cliente.ruc} •
                                WhatsApp: ${cliente.whatsapp ? (cliente.whatsapp.startsWith('51') ? '+' + cliente.whatsapp : '+51' + cliente.whatsapp) : 'No registrado'} •
                                Monto: S/ ${cliente.monto} •
                                <strong style="color: #e67700;">${cliente.dias_restantes} día${cliente.dias_restantes !== 1 ? 's' : ''} restantes</strong>
                            </div>
                            ${estaExcluido && clienteLocal.motivoExclusionNotif ? `<div style="font-size: 11px; color: #856404; margin-top: 4px; font-style: italic; background: #fff3cd; padding: 4px 8px; border-radius: 4px;">💬 ${clienteLocal.motivoExclusionNotif}</div>` : ''}
                        </div>
                        <div>
                            ${!estaExcluido ?
                                `<button onclick="excluirClienteNotificaciones(${clienteIndex})" class="btn btn-warning" style="padding: 6px 12px; font-size: 11px; white-space: nowrap;" title="Excluir de notificaciones">🔕 Excluir</button>` :
                                `<button onclick="incluirClienteNotificaciones(${clienteIndex})" class="btn btn-success" style="padding: 6px 12px; font-size: 11px; white-space: nowrap;" title="Incluir en notificaciones">✅ Incluir</button>`
                            }
                        </div>
                    </div>
                </div>
            `;
        });

        html += '</div></div>';
    }

    // Resumen
    html += `
        <div style="background: #f8f9fa; border-radius: 8px; padding: 15px; border-left: 4px solid #fab005;">
            <h3 style="color: #495057; margin-bottom: 10px;">📊 Resumen</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px;">
                <div style="text-align: center; padding: 10px; background: white; border-radius: 5px;">
                    <div style="font-size: 24px; font-weight: bold; color: #ff6b6b;">${resultado.vencidos.length}</div>
                    <div style="font-size: 12px; color: #666;">Vencidos</div>
                </div>
                <div style="text-align: center; padding: 10px; background: white; border-radius: 5px;">
                    <div style="font-size: 24px; font-weight: bold; color: #ff8800;">${resultado.vence_hoy.length}</div>
                    <div style="font-size: 12px; color: #666;">Vence Hoy</div>
                </div>
                <div style="text-align: center; padding: 10px; background: white; border-radius: 5px;">
                    <div style="font-size: 24px; font-weight: bold; color: #fab005;">${resultado.por_vencer.length}</div>
                    <div style="font-size: 12px; color: #666;">Por Vencer</div>
                </div>
            </div>
        </div>
    `;

    container.innerHTML = html;
    modal.style.display = 'flex';
}

function cerrarModalVencimientos(event) {
    if (!event || event.target.id === 'modalVencimientos') {
        const modal = document.getElementById('modalVencimientos');
        modal.style.display = 'none';
    }
}

// ============================================
// FUNCIONES DE UI - ESTADO DE PAGOS
// ============================================

function obtenerEstadoPago(fechaVencimiento, tipoServicio = 'anual') {
    const hoy = obtenerFechaPeru();
    const fecha = new Date(fechaVencimiento);

    // Ajustar fecha para evitar problemas de zona horaria
    hoy.setHours(0, 0, 0, 0);
    fecha.setHours(0, 0, 0, 0);

    const diferenciaDias = Math.floor((fecha - hoy) / (1000 * 60 * 60 * 24));

    // Determinar días de anticipación según tipo de servicio
    // Anuales: 30 días, Trimestrales/Semestrales: 15 días, Mensuales: 7 días
    const tipo = (tipoServicio || 'anual').toLowerCase();
    let diasAnticipacion;
    if (tipo === 'anual') {
        diasAnticipacion = 30;
    } else if (tipo === 'trimestral' || tipo === 'semestral') {
        diasAnticipacion = 15;
    } else {
        diasAnticipacion = 7;
    }

    if (diferenciaDias < 0) {
        return {
            clase: 'vencido',
            estado: 'vencido',
            texto: `VENCIDO (${Math.abs(diferenciaDias)} día${Math.abs(diferenciaDias) !== 1 ? 's' : ''})`
        };
    } else if (diferenciaDias === 0) {
        return {
            clase: 'proximo-vencer',
            estado: 'vence_hoy',
            texto: 'VENCE HOY'
        };
    } else if (diferenciaDias <= diasAnticipacion) {
        return {
            clase: 'proximo-vencer',
            estado: 'proximo_vencer',
            texto: `PRÓXIMO (${diferenciaDias} día${diferenciaDias !== 1 ? 's' : ''})`
        };
    } else {
        return {
            clase: 'al-dia',
            estado: 'al_dia',
            texto: `AL DÍA (${diferenciaDias} día${diferenciaDias !== 1 ? 's' : ''})`
        };
    }
}

// ============================================
// FUNCIONES DE UI - UTILIDADES
// ============================================

function limpiarFormulario() {
    const campos = ['ruc', 'monto', 'whatsapp'];
    campos.forEach(campo => {
        const elemento = document.getElementById(campo);
        if (elemento) {
            elemento.value = '';
            elemento.style.borderColor = '#ced4da';
        }
    });

    const display = document.getElementById('razonSocialDisplay');
    if (display) {
        display.classList.add('hidden');
    }

    delete window.razonSocialActual;

    const rucInput = document.getElementById('ruc');
    if (rucInput) {
        rucInput.focus();
    }
}

// Actualizar la función que habilita/deshabilita botones
function habilitarBotones() {
    const btnEliminar = document.getElementById('btnEliminarSeleccionado');
    const btnEditar = document.getElementById('btnEditarSeleccionado'); // AGREGAR
    const btnEnviarLote = document.getElementById('btnEnviarLote');
    const btnVerListaEnvio = document.getElementById('btnVerListaEnvio');
    const btnEnviarRecordatorios = document.getElementById('btnEnviarRecordatorios');

    if (btnEliminar) btnEliminar.disabled = clientes.length === 0;
    if (btnEditar) btnEditar.disabled = clientes.length === 0; // AGREGAR
    if (btnEnviarLote) btnEnviarLote.disabled = clientes.length === 0;
    if (btnVerListaEnvio) btnVerListaEnvio.disabled = clientes.length === 0;
    if (btnEnviarRecordatorios) btnEnviarRecordatorios.disabled = clientesNotificar.length === 0;
}

function deshabilitarBotones() {
    const btnEliminar = document.getElementById('btnEliminarSeleccionado');
    const btnEditar = document.getElementById('btnEditarSeleccionado'); // AGREGAR
    const btnEnviarLote = document.getElementById('btnEnviarLote');
    const btnVerListaEnvio = document.getElementById('btnVerListaEnvio');
    const btnEnviarRecordatorios = document.getElementById('btnEnviarRecordatorios');

    if (btnEliminar) btnEliminar.disabled = true;
    if (btnEditar) btnEditar.disabled = true; // AGREGAR
    if (btnEnviarLote) btnEnviarLote.disabled = true;
    if (btnVerListaEnvio) btnVerListaEnvio.disabled = true;
    if (btnEnviarRecordatorios) btnEnviarRecordatorios.disabled = true;
}

// ============================================
// VERIFICACIÓN DE IMÁGENES
// ============================================

function verificarImagenes() {
    verificarImagen('logo.png', 'logoStatus', 'Logo');
    verificarImagen('mascota.png', 'mascotaStatus', 'Mascota');
    APP_STATE.imagenesVerificadas = true;
}

function verificarImagen(ruta, elementoId, nombre) {
    const img = new Image();
    const elemento = document.getElementById(elementoId);

    if (!elemento) return;

    img.onload = function () {
        elemento.className = 'status-info status-success';
        const tamaño = img.width + 'x' + img.height;
        elemento.innerHTML = '<span class="emoji">✅</span> ' + nombre + ': Encontrado (' + tamaño + ')';
    };

    img.onerror = function () {
        elemento.className = 'status-info status-error';
        elemento.innerHTML = '<span class="emoji">❌</span> ' + nombre + ': No encontrado';
    };

    img.src = ruta;
}

// ============================================
// AUTENTICACIÓN
// ============================================

async function logout() {
    if (confirm('¿Estás seguro de que deseas cerrar sesión?')) {
        try {
            const response = await fetch('/api/auth.php?action=logout', {
                method: 'POST'
            });

            const data = await response.json();

            if (data.success) {
                window.location.href = 'login.html';
            } else {
                console.error('Error al cerrar sesión:', data.error);
                // Redireccionar de todas formas por seguridad
                window.location.href = 'login.html';
            }
        } catch (error) {
            console.error('Error de conexión:', error);
            // Redireccionar de todas formas por seguridad
            window.location.href = 'login.html';
        }
    }
}

// ============================================
// MODAL DE LISTA DE ENVÍO
// ============================================

async function mostrarListaEnvio() {
    if (clientes.length === 0) {
        alert('No hay clientes en la lista para enviar');
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

    // Filtrar solo clientes que necesitan orden de pago (próximos a vencer, no vencidos)
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
        // Anuales: 30 días, Trimestrales/Semestrales: 15 días, Mensuales: 7 días
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

    // Contar excluidos
    const clientesExcluidosManual = clientes.filter(c => c.excluidoEnvio).length;
    const clientesYaEnviadosMes = clientes.filter(c => clientesYaEnviados.includes(c.id)).length;
    const clientesVencidos = clientes.filter(c => {
        if (c.excluidoEnvio) return false;
        const hoy = obtenerFechaPeru();
        const fecha = new Date(c.fecha);
        hoy.setHours(0, 0, 0, 0);
        fecha.setHours(0, 0, 0, 0);
        const diferenciaDias = Math.floor((fecha - hoy) / (1000 * 60 * 60 * 24));
        return diferenciaDias < 0;
    }).length;
    const clientesAlDia = clientes.filter(c => {
        if (c.excluidoEnvio) return false;
        const hoy = obtenerFechaPeru();
        const fecha = new Date(c.fecha);
        hoy.setHours(0, 0, 0, 0);
        fecha.setHours(0, 0, 0, 0);
        const diferenciaDias = Math.floor((fecha - hoy) / (1000 * 60 * 60 * 24));

        // Determinar días de anticipación según tipo de servicio
        // Anuales: 30 días, Trimestrales/Semestrales: 15 días, Mensuales: 7 días
        const tipoServicio = (c.tipo_servicio || 'anual').toLowerCase();
        let diasAnticipacion;
        if (tipoServicio === 'anual') {
            diasAnticipacion = 30;
        } else if (tipoServicio === 'trimestral' || tipoServicio === 'semestral') {
            diasAnticipacion = 15;
        } else {
            diasAnticipacion = 7;
        }

        return diferenciaDias > diasAnticipacion;
    }).length;

    if (clientesParaEnviar.length === 0) {
        let mensaje = '❌ No hay clientes disponibles para envío.\n\n';

        if (clientesExcluidosManual > 0) {
            mensaje += `• ${clientesExcluidosManual} excluido${clientesExcluidosManual !== 1 ? 's' : ''} manualmente\n`;
        }
        if (clientesYaEnviadosMes > 0) {
            mensaje += `• ${clientesYaEnviadosMes} ya recibió orden de pago este mes\n`;
        }
        if (clientesVencidos > 0) {
            mensaje += `• ${clientesVencidos} vencido${clientesVencidos !== 1 ? 's' : ''} (use Recordatorios)\n`;
        }
        if (clientesAlDia > 0) {
            mensaje += `• ${clientesAlDia} al día (vencen en más de 7 días)\n`;
        }

        mensaje += '\nLas órdenes de pago solo se envían a clientes que:\n';
        mensaje += '• Vencen HOY o en los próximos días (30 días anuales, 15 trimestrales/semestrales, 7 mensuales)\n';
        mensaje += '• NO han recibido orden de pago este mes\n';
        mensaje += '• NO están vencidos';

        alert(mensaje);
        return;
    }

    const modal = document.getElementById('modalListaEnvio');
    const totalSpan = document.getElementById('totalClientesEnvio');
    const container = document.getElementById('listaEnvioContainer');

    // Actualizar total
    totalSpan.textContent = clientesParaEnviar.length;

    // Mensaje informativo si hay clientes excluidos
    let html = '';
    if (clientesExcluidosManual > 0 || clientesYaEnviadosMes > 0 || clientesVencidos > 0 || clientesAlDia > 0) {
        html += `<div style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 8px; padding: 12px; margin-bottom: 15px; border-left: 4px solid #ffc107;">`;
        html += `<strong>ℹ️ Información:</strong> `;
        const mensajes = [];
        if (clientesExcluidosManual > 0) {
            mensajes.push(`${clientesExcluidosManual} excluido${clientesExcluidosManual !== 1 ? 's' : ''} manualmente`);
        }
        if (clientesYaEnviadosMes > 0) {
            mensajes.push(`${clientesYaEnviadosMes} ya enviado${clientesYaEnviadosMes !== 1 ? 's' : ''} este mes`);
        }
        if (clientesVencidos > 0) {
            mensajes.push(`${clientesVencidos} vencido${clientesVencidos !== 1 ? 's' : ''}`);
        }
        if (clientesAlDia > 0) {
            mensajes.push(`${clientesAlDia} al día`);
        }
        html += mensajes.join(' • ');
        html += `</div>`;
    }

    html += '<div style="border: 1px solid #e9ecef; border-radius: 8px; overflow: hidden;">';

    clientesParaEnviar.forEach((cliente, index) => {
        const estadoPago = obtenerEstadoPago(cliente.fecha, cliente.tipo_servicio);
        const clienteIndex = clientes.findIndex(c => c.id === cliente.id);

        html += `
            <div id="cliente-envio-${clienteIndex}" style="padding: 15px; border-bottom: 1px solid #f8f9fa; ${index % 2 === 0 ? 'background: #f8f9fa;' : 'background: white;'}">
                <div style="display: grid; grid-template-columns: 30px 1fr auto; gap: 10px; align-items: start;">
                    <div style="font-weight: bold; color: #2581c4; font-size: 18px;">${index + 1}.</div>
                    <div>
                        <div style="font-weight: bold; color: #333; font-size: 16px; margin-bottom: 8px;">
                            ${cliente.razonSocial}
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 8px; font-size: 14px; color: #666;">
                            <div><strong>RUC:</strong> ${cliente.ruc}</div>
                            <div><strong>WhatsApp:</strong> ${cliente.whatsapp.startsWith('51') ? '+' + cliente.whatsapp : '+51' + cliente.whatsapp}</div>
                            <div><strong>Monto:</strong> S/ ${cliente.monto}</div>
                            <div><strong>Vencimiento:</strong> ${formatearFecha(cliente.fecha)}</div>
                            <div><strong>Servicio:</strong> ${cliente.tipo_servicio || 'Anual'}</div>
                            <div><span class="estado ${estadoPago.clase.replace('client-item ', '')}">${estadoPago.texto}</span></div>
                        </div>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 5px;">
                        <button onclick="excluirClienteEnvio(${clienteIndex})"
                                class="btn btn-secondary"
                                style="padding: 6px 12px; font-size: 11px; white-space: nowrap;"
                                title="Excluir del envío en lote">
                            🚫 Excluir Envío
                        </button>
                        <button onclick="excluirClienteNotificaciones(${clienteIndex})"
                                class="btn btn-warning"
                                style="padding: 6px 12px; font-size: 11px; white-space: nowrap; ${cliente.excluidoNotificaciones ? 'opacity: 0.5;' : ''}"
                                title="Excluir de notificaciones de vencimiento"
                                ${cliente.excluidoNotificaciones ? 'disabled' : ''}>
                            🔕 Excluir Notif.
                        </button>
                    </div>
                </div>
            </div>
        `;
    });

    html += '</div>';

    // Mostrar clientes excluidos manualmente (si los hay)
    const clientesExcluidos = clientes.filter(c => c.excluidoEnvio);
    if (clientesExcluidos.length > 0) {
        html += `
            <div style="margin-top: 20px; border: 1px solid #e9ecef; border-radius: 8px; overflow: hidden;">
                <div style="background: #f8f9fa; padding: 12px; border-bottom: 1px solid #e9ecef; font-weight: bold; color: #666;">
                    🚫 Clientes Excluidos Manualmente (${clientesExcluidos.length})
                </div>
        `;

        clientesExcluidos.forEach((cliente, index) => {
            const clienteIndex = clientes.findIndex(c => c.id === cliente.id);
            const estadoPago = obtenerEstadoPago(cliente.fecha, cliente.tipo_servicio);

            html += `
                <div id="cliente-excluido-${clienteIndex}" style="padding: 12px; border-bottom: 1px solid #f8f9fa; background: #fff; opacity: 0.7;">
                    <div style="display: grid; grid-template-columns: 1fr auto; gap: 10px; align-items: start;">
                        <div>
                            <div style="font-weight: bold; color: #666; font-size: 14px; margin-bottom: 4px;">
                                ${cliente.razonSocial}
                            </div>
                            <div style="font-size: 12px; color: #999;">
                                RUC: ${cliente.ruc} • Vencimiento: ${formatearFecha(cliente.fecha)} • ${estadoPago.texto}
                            </div>
                            ${cliente.motivoExclusion ? `<div style="font-size: 12px; color: #666; margin-top: 4px; font-style: italic;">💬 ${cliente.motivoExclusion}</div>` : ''}
                        </div>
                        <div>
                            <button onclick="incluirClienteEnvio(${clienteIndex})"
                                    class="btn btn-success"
                                    style="padding: 4px 10px; font-size: 11px;"
                                    title="Volver a incluir en envío">
                                ✅ Incluir
                            </button>
                        </div>
                    </div>
                </div>
            `;
        });

        html += '</div>';
    }

    // Mostrar clientes excluidos de notificaciones (si los hay)
    const clientesExcluidosNotif = clientes.filter(c => c.excluidoNotificaciones);
    if (clientesExcluidosNotif.length > 0) {
        html += `
            <div style="margin-top: 20px; border: 1px solid #e9ecef; border-radius: 8px; overflow: hidden;">
                <div style="background: #fff3cd; padding: 12px; border-bottom: 1px solid #e9ecef; font-weight: bold; color: #856404;">
                    🔕 Clientes Excluidos de Notificaciones (${clientesExcluidosNotif.length})
                </div>
        `;

        clientesExcluidosNotif.forEach((cliente, index) => {
            const clienteIndex = clientes.findIndex(c => c.id === cliente.id);
            const estadoPago = obtenerEstadoPago(cliente.fecha, cliente.tipo_servicio);

            html += `
                <div id="cliente-excluido-notif-${clienteIndex}" style="padding: 12px; border-bottom: 1px solid #f8f9fa; background: #fffbf0; opacity: 0.8;">
                    <div style="display: grid; grid-template-columns: 1fr auto; gap: 10px; align-items: start;">
                        <div>
                            <div style="font-weight: bold; color: #856404; font-size: 14px; margin-bottom: 4px;">
                                ${cliente.razonSocial}
                            </div>
                            <div style="font-size: 12px; color: #999;">
                                RUC: ${cliente.ruc} • Vencimiento: ${formatearFecha(cliente.fecha)} • ${estadoPago.texto}
                            </div>
                            ${cliente.motivoExclusionNotif ? `<div style="font-size: 12px; color: #856404; margin-top: 4px; font-style: italic;">💬 ${cliente.motivoExclusionNotif}</div>` : ''}
                        </div>
                        <div>
                            <button onclick="incluirClienteNotificaciones(${clienteIndex})"
                                    class="btn btn-success"
                                    style="padding: 4px 10px; font-size: 11px;"
                                    title="Volver a incluir en notificaciones">
                                ✅ Incluir
                            </button>
                        </div>
                    </div>
                </div>
            `;
        });

        html += '</div>';
    }

    // Agregar resumen por estado (solo de los que se enviarán)
    const resumen = obtenerResumenEstados(clientesParaEnviar);
    const clientesExcluidosNotifCount = clientesExcluidosNotif.length;
    html += `
        <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #2581c4;">
            <h3 style="color: #2581c4; margin-bottom: 10px;">📊 Resumen de Envío</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px;">
                <div style="text-align: center; padding: 10px; background: white; border-radius: 5px;">
                    <div style="font-size: 24px; font-weight: bold; color: #F28B82;">${resumen.vencidos}</div>
                    <div style="font-size: 12px; color: #666;">Vencidos</div>
                </div>
                <div style="text-align: center; padding: 10px; background: white; border-radius: 5px;">
                    <div style="font-size: 24px; font-weight: bold; color: #FFF176;">${resumen.proximosVencer}</div>
                    <div style="font-size: 12px; color: #666;">Próximos a Vencer</div>
                </div>
                <div style="text-align: center; padding: 10px; background: white; border-radius: 5px;">
                    <div style="font-size: 24px; font-weight: bold; color: #6c757d;">${clientesExcluidosManual}</div>
                    <div style="font-size: 12px; color: #666;">Excluidos Envío</div>
                </div>
                <div style="text-align: center; padding: 10px; background: white; border-radius: 5px;">
                    <div style="font-size: 24px; font-weight: bold; color: #ffc107;">${clientesExcluidosNotifCount}</div>
                    <div style="font-size: 12px; color: #666;">Excluidos Notif.</div>
                </div>
                <div style="text-align: center; padding: 10px; background: white; border-radius: 5px;">
                    <div style="font-size: 24px; font-weight: bold; color: #81C784;">${clientesAlDia}</div>
                    <div style="font-size: 12px; color: #666;">Al Día</div>
                </div>
            </div>
        </div>
    `;

    container.innerHTML = html;
    modal.style.display = 'flex';
}

function cerrarModalListaEnvio(event) {
    // Si se hace clic en el overlay o se llama directamente
    if (!event || event.target.id === 'modalListaEnvio') {
        const modal = document.getElementById('modalListaEnvio');
        modal.style.display = 'none';
    }
}

function obtenerResumenEstados(listaClientes = clientes) {
    let vencidos = 0;
    let proximosVencer = 0;
    let alDia = 0;

    listaClientes.forEach(cliente => {
        const estado = obtenerEstadoPago(cliente.fecha, cliente.tipo_servicio);
        if (estado.clase.includes('vencido')) {
            vencidos++;
        } else if (estado.clase.includes('proximo-vencer')) {
            proximosVencer++;
        } else if (estado.clase.includes('al-dia')) {
            alDia++;
        }
    });

    return { vencidos, proximosVencer, alDia };
}

// Excluir cliente del envío
function excluirClienteEnvio(index) {
    const cliente = clientes[index];

    const motivo = prompt(`¿Por qué desea excluir a "${cliente.razonSocial}" del envío?\n\nEjemplos:\n• Ya pagó\n• Confirmó que pagará mañana\n• Solicitó extensión\n\n(Opcional, puede dejar en blanco)`);

    // Si cancela, no hacer nada
    if (motivo === null) return;

    // Marcar como excluido (solo en memoria para este envío)
    clientes[index].excluidoEnvio = true;
    clientes[index].motivoExclusion = motivo.trim() || 'Sin motivo especificado';
    clientes[index].fechaExclusion = new Date().toISOString();

    // Refrescar el modal
    mostrarListaEnvio();
}

// Incluir cliente de nuevo en el envío
function incluirClienteEnvio(index) {
    const cliente = clientes[index];

    if (confirm(`¿Volver a incluir a "${cliente.razonSocial}" en el envío?`)) {
        // Quitar marca de excluido
        delete clientes[index].excluidoEnvio;
        delete clientes[index].motivoExclusion;
        delete clientes[index].fechaExclusion;

        // Actualizar en base de datos si tiene ID
        if (cliente.id) {
            actualizarClienteEnDB(cliente);
        }

        // Refrescar el modal
        mostrarListaEnvio();
    }
}

// Excluir cliente de notificaciones de vencimiento
function excluirClienteNotificaciones(index) {
    const cliente = clientes[index];

    const motivo = prompt(`¿Por qué desea excluir a "${cliente.razonSocial}" de las notificaciones de vencimiento?\n\nEjemplos:\n• Ya pagó\n• Confirmó que pagará mañana\n• Solicitó extensión\n\n(Opcional, puede dejar en blanco)`);

    // Si cancela, no hacer nada
    if (motivo === null) return;

    // Marcar como excluido de notificaciones
    clientes[index].excluidoNotificaciones = true;
    clientes[index].motivoExclusionNotif = motivo.trim() || 'Sin motivo especificado';
    clientes[index].fechaExclusionNotif = new Date().toISOString();

    // Refrescar ambos modales si están abiertos
    const modalEnvio = document.getElementById('modalListaEnvio');
    const modalVencimientos = document.getElementById('modalVencimientos');

    if (modalEnvio && modalEnvio.style.display === 'flex') {
        mostrarListaEnvio();
    }

    if (modalVencimientos && modalVencimientos.style.display === 'flex') {
        // Volver a verificar vencimientos para actualizar la vista
        verificarVencimientos();
    }
}

// Incluir cliente de nuevo en las notificaciones
function incluirClienteNotificaciones(index) {
    const cliente = clientes[index];

    if (confirm(`¿Volver a incluir a "${cliente.razonSocial}" en las notificaciones?`)) {
        // Quitar marca de excluido
        delete clientes[index].excluidoNotificaciones;
        delete clientes[index].motivoExclusionNotif;
        delete clientes[index].fechaExclusionNotif;

        // Actualizar en base de datos si tiene ID
        if (cliente.id) {
            actualizarClienteEnDB(cliente);
        }

        // Refrescar ambos modales si están abiertos
        const modalEnvio = document.getElementById('modalListaEnvio');
        const modalVencimientos = document.getElementById('modalVencimientos');

        if (modalEnvio && modalEnvio.style.display === 'flex') {
            mostrarListaEnvio();
        }

        if (modalVencimientos && modalVencimientos.style.display === 'flex') {
            // Volver a verificar vencimientos para actualizar la vista
            verificarVencimientos();
        }
    }
}