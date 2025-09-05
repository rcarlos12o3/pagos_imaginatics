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
        const hoy = new Date();
        hoy.setDate(hoy.getDate() + CONFIG.FECHA_DIAS_DEFECTO);
        fechaInput.value = hoy.toISOString().split('T')[0];
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
        const hoy = new Date();
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

    if (display && text) {
        text.textContent = razonSocial;
        display.classList.remove('hidden');
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
        const estadoPago = obtenerEstadoPago(cliente.fecha);
        return `
        <div class="client-item ${estadoPago.clase}" onclick="seleccionarCliente(${indexOriginal})" data-index="${indexOriginal}">
            <div class="client-name">${cliente.razonSocial}</div>
            <div class="client-info">
                <div><strong>RUC:</strong> ${cliente.ruc}</div>
                <div><strong>Monto:</strong> S/ ${cliente.monto}</div>
                <div><strong>Fecha:</strong> ${formatearFecha(cliente.fecha)}</div>
                <div><strong>WhatsApp:</strong> ${cliente.whatsapp}</div>
                <div><strong>Servicio:</strong> <span class="tipo-servicio">${cliente.tipo_servicio || 'anual'}</span></div>
            </div>
            <div class="client-actions">
                <button class="btn-detalle" onclick="event.stopPropagation(); verDetalleCliente(${cliente.id})">📊 Ver Detalle</button>
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
        const ruc = cliente.ruc.toLowerCase();
        const razonSocial = cliente.razonSocial.toLowerCase();
        const whatsapp = cliente.whatsapp.toLowerCase();
        
        return ruc.includes(termino) || 
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
    const hoy = new Date().toLocaleDateString('es-PE');
    let textoResultado = 'Resumen de Vencimientos (al ' + hoy + '):\n\n';

    if (resultado.vencidos.length > 0) {
        textoResultado += 'VENCIDOS (' + resultado.vencidos.length + ' clientes):\n';
        resultado.vencidos.forEach(cliente => {
            const diasAtraso = Math.abs(cliente.dias_restantes);
            textoResultado += '• ' + cliente.razon_social.substring(0, 30) + ': ' + diasAtraso + ' dias de atraso\n';
        });
        textoResultado += '\n';
    }

    if (resultado.vence_hoy.length > 0) {
        textoResultado += 'VENCE HOY (' + resultado.vence_hoy.length + ' clientes):\n';
        resultado.vence_hoy.forEach(cliente => {
            textoResultado += '• ' + cliente.razon_social.substring(0, 30) + ': VENCE HOY\n';
        });
        textoResultado += '\n';
    }

    if (resultado.por_vencer.length > 0) {
        textoResultado += 'POR VENCER (' + resultado.por_vencer.length + ' clientes):\n';
        resultado.por_vencer.forEach(cliente => {
            textoResultado += '• ' + cliente.razon_social.substring(0, 30) + ': ' + cliente.dias_restantes + ' dias restantes\n';
        });
        textoResultado += '\n';
    }

    const notificationArea = document.getElementById('notificationArea');
    if (notificationArea) {
        notificationArea.textContent = textoResultado;
    }

    if (clientesNotificar.length > 0) {
        alert('Se encontraron ' + clientesNotificar.length + ' clientes que necesitan notificacion');
    } else {
        alert('Todos los clientes estan al dia!');
    }
}

// ============================================
// FUNCIONES DE UI - ESTADO DE PAGOS
// ============================================

function obtenerEstadoPago(fechaVencimiento) {
    const hoy = new Date();
    const fecha = new Date(fechaVencimiento);
    
    // Ajustar fecha para evitar problemas de zona horaria
    hoy.setHours(0, 0, 0, 0);
    fecha.setHours(0, 0, 0, 0);
    
    const diferenciaDias = Math.floor((fecha - hoy) / (1000 * 60 * 60 * 24));
    
    if (diferenciaDias < 0) {
        return { clase: 'vencido', estado: 'vencido' };
    } else if (diferenciaDias <= 7) {
        return { clase: 'proximo-vencer', estado: 'proximo_vencer' };
    } else {
        return { clase: 'al-dia', estado: 'al_dia' };
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
    const btnEnviarRecordatorios = document.getElementById('btnEnviarRecordatorios');

    if (btnEliminar) btnEliminar.disabled = clientes.length === 0;
    if (btnEditar) btnEditar.disabled = clientes.length === 0; // AGREGAR
    if (btnEnviarLote) btnEnviarLote.disabled = clientes.length === 0;
    if (btnEnviarRecordatorios) btnEnviarRecordatorios.disabled = clientesNotificar.length === 0;
}

function deshabilitarBotones() {
    const btnEliminar = document.getElementById('btnEliminarSeleccionado');
    const btnEditar = document.getElementById('btnEditarSeleccionado'); // AGREGAR
    const btnEnviarLote = document.getElementById('btnEnviarLote');
    const btnEnviarRecordatorios = document.getElementById('btnEnviarRecordatorios');

    if (btnEliminar) btnEliminar.disabled = true;
    if (btnEditar) btnEditar.disabled = true; // AGREGAR
    if (btnEnviarLote) btnEnviarLote.disabled = true;
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