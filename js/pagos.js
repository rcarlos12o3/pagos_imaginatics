/**
 * Gestión de Pagos - JavaScript
 */

// Variables globales
let pagosData = [];
let pagosDataFiltrada = [];
let clientesData = [];
let paginaActual = 1;
const pagosPorPagina = 10;

// URLs de la API
const API_PAGOS = '/api/pagos.php';
const API_CLIENTES = '/api/clientes.php';

// Inicialización
document.addEventListener('DOMContentLoaded', () => {
    cargarClientes();
    cargarPagos();
    cargarEstadisticas();
    
    // Configurar formulario
    document.getElementById('formPago').addEventListener('submit', guardarPago);
    
    // Establecer fecha actual por defecto
    document.getElementById('fechaPago').value = formatearFechaISO();
    
    // Establecer mes y año actuales en filtros
    const fechaActual = obtenerFechaPeru();
    document.getElementById('filtroMes').value = fechaActual.getMonth() + 1;
    document.getElementById('filtroAnio').value = fechaActual.getFullYear();
    
    // Configurar eventos para cerrar modales al hacer clic fuera
    document.getElementById('modalPago').addEventListener('click', (e) => {
        if (e.target.id === 'modalPago') {
            cerrarModal();
        }
    });
    
    document.getElementById('modalDetalles').addEventListener('click', (e) => {
        if (e.target.id === 'modalDetalles') {
            cerrarModalDetalles();
        }
    });
});

/**
 * Cargar lista de clientes para el select
 */
async function cargarClientes() {
    try {
        const response = await fetch(`${API_CLIENTES}?action=list&limit=1000`);
        const data = await response.json();
        
        if (data.success) {
            clientesData = data.data;
            const select = document.getElementById('clienteId');
            
            // Limpiar y agregar opciones
            select.innerHTML = '<option value="">Seleccionar cliente...</option>';
            
            clientesData.forEach(cliente => {
                const option = document.createElement('option');
                option.value = cliente.id;
                option.textContent = `${cliente.razon_social} (RUC: ${cliente.ruc})`;
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error cargando clientes:', error);
        mostrarAlerta('Error al cargar clientes', 'error');
    }
}

/**
 * Cargar lista de pagos
 */
async function cargarPagos() {
    try {
        mostrarLoading();
        
        const mes = document.getElementById('filtroMes').value;
        const anio = document.getElementById('filtroAnio').value;
        
        let url = `${API_PAGOS}?action=list&limit=1000`;
        if (mes) url += `&mes=${mes}`;
        if (anio) url += `&anio=${anio}`;
        
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.success) {
            pagosData = data.data;
            pagosDataFiltrada = [...pagosData];
            filtrarPagosLocal();
            mostrarPagos();
        } else {
            throw new Error(data.error || 'Error al cargar pagos');
        }
    } catch (error) {
        console.error('Error cargando pagos:', error);
        mostrarAlerta('Error al cargar pagos', 'error');
        ocultarLoading();
    }
}

/**
 * Cargar estadísticas
 */
async function cargarEstadisticas() {
    try {
        const response = await fetch(`${API_PAGOS}?action=estadisticas&periodo=mes`);
        const data = await response.json();
        
        if (data.success) {
            const stats = data.data.general;
            
            document.getElementById('totalRecaudado').textContent = 
                `S/ ${parseFloat(stats.monto_total || 0).toFixed(2)}`;
            document.getElementById('cantidadPagos').textContent = 
                stats.total_pagos || 0;
            document.getElementById('pagoPromedio').textContent = 
                `S/ ${parseFloat(stats.monto_promedio || 0).toFixed(2)}`;
            document.getElementById('mayorPago').textContent = 
                `S/ ${parseFloat(stats.monto_maximo || 0).toFixed(2)}`;
        }
    } catch (error) {
        console.error('Error cargando estadísticas:', error);
    }
}

/**
 * Mostrar pagos en la tabla
 */
function mostrarPagos() {
    const container = document.getElementById('tablaPagosContainer');
    
    if (pagosDataFiltrada.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <p>No se encontraron pagos</p>
                <button class="btn btn-primary" onclick="mostrarModalNuevoPago()">
                    Registrar primer pago
                </button>
            </div>
        `;
        document.getElementById('paginacion').innerHTML = '';
        return;
    }
    
    // Calcular paginación
    const inicio = (paginaActual - 1) * pagosPorPagina;
    const fin = inicio + pagosPorPagina;
    const pagosPagina = pagosDataFiltrada.slice(inicio, fin);
    
    // Crear tabla
    let html = `
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Fecha</th>
                    <th>Cliente</th>
                    <th>RUC</th>
                    <th>Monto</th>
                    <th>Método</th>
                    <th>N° Operación</th>
                    <th>Banco</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
    `;
    
    pagosPagina.forEach(pago => {
        html += `
            <tr>
                <td>#${pago.id}</td>
                <td>${formatearFecha(pago.fecha_pago)}</td>
                <td>${pago.razon_social}</td>
                <td>${pago.ruc}</td>
                <td><strong>S/ ${parseFloat(pago.monto_pagado).toFixed(2)}</strong></td>
                <td><span class="metodo-badge metodo-${pago.metodo_pago}">${pago.metodo_pago}</span></td>
                <td>${pago.numero_operacion || '-'}</td>
                <td>${pago.banco || '-'}</td>
                <td class="actions-cell">
                    <button class="btn-icon btn-view" onclick="verPago(${pago.id})" title="Ver detalles">👁️</button>
                    <button class="btn-icon btn-edit" onclick="editarPago(${pago.id})" title="Editar">✏️</button>
                    <button class="btn-icon btn-delete" onclick="eliminarPago(${pago.id})" title="Eliminar">🗑️</button>
                </td>
            </tr>
        `;
    });
    
    html += `
            </tbody>
        </table>
    `;
    
    container.innerHTML = html;
    mostrarPaginacion();
}

/**
 * Mostrar paginación
 */
function mostrarPaginacion() {
    const totalPaginas = Math.ceil(pagosDataFiltrada.length / pagosPorPagina);
    const container = document.getElementById('paginacion');
    
    if (totalPaginas <= 1) {
        container.innerHTML = '';
        return;
    }
    
    let html = '';
    
    // Botón anterior
    html += `<button onclick="cambiarPagina(${paginaActual - 1})" ${paginaActual === 1 ? 'disabled' : ''}>←</button>`;
    
    // Páginas
    for (let i = 1; i <= totalPaginas; i++) {
        if (i === 1 || i === totalPaginas || (i >= paginaActual - 2 && i <= paginaActual + 2)) {
            html += `<button onclick="cambiarPagina(${i})" class="${i === paginaActual ? 'active' : ''}">${i}</button>`;
        } else if (i === paginaActual - 3 || i === paginaActual + 3) {
            html += `<button disabled>...</button>`;
        }
    }
    
    // Botón siguiente
    html += `<button onclick="cambiarPagina(${paginaActual + 1})" ${paginaActual === totalPaginas ? 'disabled' : ''}>→</button>`;
    
    container.innerHTML = html;
}

/**
 * Cambiar página
 */
function cambiarPagina(pagina) {
    paginaActual = pagina;
    mostrarPagos();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

/**
 * Filtrar pagos
 */
function filtrarPagos() {
    cargarPagos();
}

/**
 * Filtrar pagos localmente
 */
function filtrarPagosLocal() {
    const metodo = document.getElementById('filtroMetodo').value;
    const banco = document.getElementById('filtroBanco').value;
    const busqueda = document.getElementById('busqueda').value.toLowerCase();
    
    pagosDataFiltrada = pagosData.filter(pago => {
        let cumple = true;
        
        if (metodo && pago.metodo_pago !== metodo) cumple = false;
        if (banco && pago.banco !== banco) cumple = false;
        
        if (busqueda) {
            const busquedaEncontrada = 
                pago.razon_social.toLowerCase().includes(busqueda) ||
                pago.ruc.includes(busqueda) ||
                (pago.numero_operacion && pago.numero_operacion.toLowerCase().includes(busqueda));
            
            if (!busquedaEncontrada) cumple = false;
        }
        
        return cumple;
    });
    
    paginaActual = 1;
}

/**
 * Buscar pagos
 */
function buscarPagos() {
    filtrarPagosLocal();
    mostrarPagos();
}

/**
 * Mostrar modal de nuevo pago
 */
function mostrarModalNuevoPago() {
    document.getElementById('modalTitulo').textContent = 'Nuevo Pago';
    document.getElementById('formPago').reset();
    document.getElementById('pagoId').value = '';
    document.getElementById('fechaPago').value = formatearFechaISO();
    document.getElementById('actualizarVencimiento').checked = true;
    document.getElementById('modalPago').classList.add('active');
}

/**
 * Ver detalles de pago
 */
async function verPago(id) {
    try {
        const response = await fetch(`${API_PAGOS}?action=get&id=${id}`);
        const data = await response.json();
        
        if (data.success) {
            const pago = data.data;
            
            // Actualizar título del modal
            document.getElementById('modalDetallesTitulo').textContent = `Detalles del Pago #${id}`;
            
            // Crear el contenido HTML del modal
            let contenido = `
                <div class="cliente-info">
                    <h3>👤 ${pago.razon_social}</h3>
                    <p><strong>RUC:</strong> ${pago.ruc}</p>
                    <p><strong>WhatsApp:</strong> +51${pago.whatsapp}</p>
                    <p><strong>Tipo de Servicio:</strong> ${pago.tipo_servicio || 'anual'}</p>
                </div>
                
                <div class="detalles-pago">
                    <div class="detalle-item">
                        <div class="detalle-label">💰 Monto Pagado:</div>
                        <div class="detalle-valor monto">S/ ${parseFloat(pago.monto_pagado).toFixed(2)}</div>
                    </div>
                    
                    <div class="detalle-item">
                        <div class="detalle-label">📅 Fecha de Pago:</div>
                        <div class="detalle-valor">${formatearFecha(pago.fecha_pago)}</div>
                    </div>
                    
                    <div class="detalle-item">
                        <div class="detalle-label">💳 Método de Pago:</div>
                        <div class="detalle-valor metodo">${pago.metodo_pago}</div>
                    </div>
            `;
            
            if (pago.numero_operacion) {
                contenido += `
                    <div class="detalle-item">
                        <div class="detalle-label">🧾 N° Operación:</div>
                        <div class="detalle-valor">${pago.numero_operacion}</div>
                    </div>
                `;
            }
            
            if (pago.banco) {
                contenido += `
                    <div class="detalle-item">
                        <div class="detalle-label">🏦 Banco:</div>
                        <div class="detalle-valor">${pago.banco}</div>
                    </div>
                `;
            }
            
            if (pago.observaciones) {
                contenido += `
                    <div class="detalle-item">
                        <div class="detalle-label">📝 Observaciones:</div>
                        <div class="detalle-valor">${pago.observaciones}</div>
                    </div>
                `;
            }
            
            contenido += `
                    <div class="detalle-item">
                        <div class="detalle-label">👤 Registrado por:</div>
                        <div class="detalle-valor">${pago.registrado_por || 'Sistema'}</div>
                    </div>
            `;
            
            if (pago.fecha_registro) {
                contenido += `
                    <div class="detalle-item">
                        <div class="detalle-label">🕐 Fecha de Registro:</div>
                        <div class="detalle-valor">${formatearFechaHora(pago.fecha_registro)}</div>
                    </div>
                `;
            }
            
            contenido += `</div>`;
            
            // Insertar contenido en el modal
            document.getElementById('detallesPagoContent').innerHTML = contenido;
            
            // Mostrar el modal
            document.getElementById('modalDetalles').classList.add('active');
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarAlerta('Error al obtener detalles del pago', 'error');
    }
}

/**
 * Editar pago
 */
async function editarPago(id) {
    try {
        const response = await fetch(`${API_PAGOS}?action=get&id=${id}`);
        const data = await response.json();
        
        if (data.success) {
            const pago = data.data;
            
            document.getElementById('modalTitulo').textContent = 'Editar Pago #' + id;
            document.getElementById('pagoId').value = id;
            document.getElementById('clienteId').value = pago.cliente_id;
            document.getElementById('clienteId').disabled = true; // No permitir cambiar cliente
            document.getElementById('montoPagado').value = pago.monto_pagado;
            document.getElementById('fechaPago').value = pago.fecha_pago;
            document.getElementById('metodoPago').value = pago.metodo_pago;
            document.getElementById('numeroOperacion').value = pago.numero_operacion || '';
            document.getElementById('banco').value = pago.banco || '';
            document.getElementById('observaciones').value = pago.observaciones || '';
            document.getElementById('actualizarVencimiento').checked = false;
            document.getElementById('actualizarVencimiento').parentElement.style.display = 'none';
            
            document.getElementById('modalPago').classList.add('active');
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarAlerta('Error al cargar pago', 'error');
    }
}

/**
 * Guardar pago (crear o editar)
 */
async function guardarPago(e) {
    e.preventDefault();
    
    const pagoId = document.getElementById('pagoId').value;
    const isEdit = pagoId !== '';
    
    const formData = {
        cliente_id: document.getElementById('clienteId').value,
        monto_pagado: document.getElementById('montoPagado').value,
        fecha_pago: document.getElementById('fechaPago').value,
        metodo_pago: document.getElementById('metodoPago').value,
        numero_operacion: document.getElementById('numeroOperacion').value || null,
        banco: document.getElementById('banco').value || null,
        observaciones: document.getElementById('observaciones').value || null
    };
    
    if (!isEdit) {
        formData.actualizar_vencimiento = document.getElementById('actualizarVencimiento').checked;
    }
    
    try {
        let url, method;
        
        if (isEdit) {
            url = `${API_PAGOS}?action=update&id=${pagoId}`;
            method = 'PUT';
        } else {
            url = `${API_PAGOS}?action=create`;
            method = 'POST';
        }
        
        const response = await fetch(url, {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            mostrarAlerta(isEdit ? 'Pago actualizado exitosamente' : 'Pago registrado exitosamente', 'success');
            cerrarModal();
            cargarPagos();
            cargarEstadisticas();
        } else {
            throw new Error(data.error || data.errors?.join(', '));
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarAlerta('Error al guardar pago: ' + error.message, 'error');
    }
}

/**
 * Eliminar pago
 */
async function eliminarPago(id) {
    if (!confirm('¿Está seguro de eliminar este pago?\n\nEsta acción no se puede deshacer.')) {
        return;
    }
    
    try {
        const response = await fetch(`${API_PAGOS}?action=delete&id=${id}`, {
            method: 'DELETE'
        });
        
        const data = await response.json();
        
        if (data.success) {
            mostrarAlerta('Pago eliminado exitosamente', 'success');
            cargarPagos();
            cargarEstadisticas();
        } else {
            throw new Error(data.error);
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarAlerta('Error al eliminar pago', 'error');
    }
}

/**
 * Abrir modal de exportación
 */
function exportarPagos() {
    // Limpiar fechas previas
    document.getElementById('exportarFechaDesde').value = '';
    document.getElementById('exportarFechaHasta').value = '';

    document.getElementById('modalExportar').classList.add('active');
}

/**
 * Cerrar modal de exportación
 */
function cerrarModalExportar() {
    document.getElementById('modalExportar').classList.remove('active');
}

/**
 * Confirmar y realizar exportación
 */
async function confirmarExportacion() {
    try {
        const tipoFecha = document.getElementById('exportarTipoFecha').value;
        const fechaDesde = document.getElementById('exportarFechaDesde').value;
        const fechaHasta = document.getElementById('exportarFechaHasta').value;

        // Cargar TODOS los pagos sin filtros para la exportación
        console.log('🔍 Cargando todos los pagos para exportar...');
        const response = await fetch(`${API_PAGOS}?action=list&limit=10000`);
        const data = await response.json();

        if (!data.success) {
            throw new Error(data.error || 'Error al cargar pagos para exportar');
        }

        let todosPagos = data.data;
        console.log(`📊 Total de pagos cargados: ${todosPagos.length}`);

        // Filtrar pagos según rango de fechas
        let pagosFiltrados = todosPagos;

        if (fechaDesde || fechaHasta) {
            console.log(`🔎 Filtrando por rango: ${fechaDesde || 'inicio'} - ${fechaHasta || 'fin'}, tipo: ${tipoFecha}`);
            pagosFiltrados = todosPagos.filter(pago => {
                // Usar la fecha según el tipo seleccionado (formato: YYYY-MM-DD)
                const fechaStr = tipoFecha === 'vencimiento'
                    ? pago.fecha_vencimiento
                    : pago.fecha_pago;

                // Comparar fechas como strings (YYYY-MM-DD se compara correctamente)
                let cumpleDesde = fechaDesde ? fechaStr >= fechaDesde : true;
                let cumpleHasta = fechaHasta ? fechaStr <= fechaHasta : true;

                return cumpleDesde && cumpleHasta;
            });
            console.log(`✅ Pagos después del filtro: ${pagosFiltrados.length}`);
        }

        if (pagosFiltrados.length === 0) {
            alert('No hay pagos para exportar con los filtros seleccionados.');
            return;
        }

        // Preparar datos para exportar
        const datosExportar = pagosFiltrados.map(pago => ({
            ID: pago.id,
            Fecha: pago.fecha_pago,
            Cliente: pago.razon_social,
            RUC: pago.ruc,
            Monto: pago.monto_pagado,
            Metodo: pago.metodo_pago,
            NumeroOperacion: pago.numero_operacion || '',
            Banco: pago.banco || '',
            Observaciones: pago.observaciones || ''
        }));

        // Convertir a CSV
        const csv = convertirACSV(datosExportar);

        // Construir nombre de archivo con rango de fechas
        let nombreArchivo = 'pagos';
        if (fechaDesde && fechaHasta) {
            nombreArchivo += `_${fechaDesde}_a_${fechaHasta}`;
        } else if (fechaDesde) {
            nombreArchivo += `_desde_${fechaDesde}`;
        } else if (fechaHasta) {
            nombreArchivo += `_hasta_${fechaHasta}`;
        } else {
            nombreArchivo += `_todos_${formatearFechaISO()}`;
        }
        nombreArchivo += '.csv';

        // Descargar archivo
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);

        link.setAttribute('href', url);
        link.setAttribute('download', nombreArchivo);
        link.style.visibility = 'hidden';

        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        mostrarAlerta(`${pagosFiltrados.length} pagos exportados exitosamente`, 'success');
        cerrarModalExportar();

    } catch (error) {
        console.error('Error:', error);
        mostrarAlerta('Error al exportar pagos', 'error');
    }
}

/**
 * Convertir datos a CSV
 */
function convertirACSV(datos) {
    if (datos.length === 0) return '';
    
    const headers = Object.keys(datos[0]);
    const csvHeaders = headers.join(',');
    
    const csvRows = datos.map(row => {
        return headers.map(header => {
            const value = row[header];
            return typeof value === 'string' && value.includes(',') 
                ? `"${value}"` 
                : value;
        }).join(',');
    });
    
    return csvHeaders + '\n' + csvRows.join('\n');
}

/**
 * Cerrar modal
 */
function cerrarModal() {
    document.getElementById('modalPago').classList.remove('active');
    document.getElementById('clienteId').disabled = false;
    document.getElementById('actualizarVencimiento').parentElement.style.display = 'block';
}

/**
 * Cerrar modal de detalles
 */
function cerrarModalDetalles() {
    document.getElementById('modalDetalles').classList.remove('active');
}

/**
 * Mostrar loading
 */
function mostrarLoading() {
    document.getElementById('tablaPagosContainer').innerHTML = `
        <div class="loading">
            <div class="spinner"></div>
            <p>Cargando pagos...</p>
        </div>
    `;
}

/**
 * Ocultar loading
 */
function ocultarLoading() {
    const container = document.getElementById('tablaPagosContainer');
    if (container.querySelector('.loading')) {
        container.innerHTML = '';
    }
}

/**
 * Mostrar alerta
 */
function mostrarAlerta(mensaje, tipo = 'info', titulo = '') {
    const container = document.getElementById('alertContainer');
    
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${tipo} show`;
    
    if (titulo) {
        alertDiv.innerHTML = `<strong>${titulo}</strong><br>${mensaje}`;
    } else {
        alertDiv.innerHTML = mensaje;
    }
    
    container.appendChild(alertDiv);
    
    // Auto-ocultar después de 5 segundos
    setTimeout(() => {
        alertDiv.remove();
    }, tipo === 'info' ? 10000 : 5000);
}

/**
 * Formatear fecha
 */
function formatearFecha(fecha) {
    if (!fecha) return '-';
    const date = new Date(fecha + 'T00:00:00');
    return date.toLocaleDateString('es-PE');
}

/**
 * Formatear fecha y hora
 */
function formatearFechaHora(fechaHora) {
    if (!fechaHora) return '-';
    const date = new Date(fechaHora);
    return date.toLocaleString('es-PE');
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