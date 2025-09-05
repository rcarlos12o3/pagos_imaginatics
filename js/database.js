// ============================================
// FUNCIONES DE BASE DE DATOS
// Sistema RUC Consultor - Imaginatics Peru SAC
// ============================================

// Cargar clientes desde la base de datos
async function cargarClientesDesdeDB() {
    try {
        const response = await fetch(API_CLIENTES_BASE + '?action=list&limit=100');
        const data = await response.json();

        if (data.success) {
            clientes = data.data.map(cliente => ({
                id: cliente.id,
                ruc: cliente.ruc,
                razonSocial: cliente.razon_social,
                monto: cliente.monto,
                fecha: cliente.fecha_vencimiento,
                whatsapp: cliente.whatsapp,
                tipo_servicio: cliente.tipo_servicio || 'anual',
                diasRestantes: cliente.dias_restantes,
                estadoVencimiento: cliente.estado_vencimiento
            }));

            actualizarListaClientes();

            if (clientes.length > 0) {
                habilitarBotones();
            }

            APP_STATE.conectadoBD = true;
            console.log('Conectado a MySQL - Clientes cargados:', clientes.length);
        } else {
            console.error('Error cargando clientes:', data.error);
        }
    } catch (error) {
        console.error('Error de conexion al cargar clientes:', error);
        APP_STATE.conectadoBD = false;
    }
}

// Consultar RUC usando la API
async function consultarRUC() {
    const rucInput = document.getElementById('ruc');
    const ruc = rucInput.value.trim();

    if (!ruc) {
        alert('Por favor ingrese un RUC');
        return;
    }

    const validacion = validarRUC(ruc);
    if (!validacion.valido) {
        alert(validacion.mensaje);
        return;
    }

    const btnConsultar = rucInput.nextElementSibling;
    const textoOriginal = btnConsultar.textContent;
    btnConsultar.textContent = CONFIG.MENSAJES.CONSULTANDO;
    btnConsultar.disabled = true;

    try {
        const response = await fetch(API_RUC_BASE + validacion.ruc);
        const data = await response.json();

        if (data.success && data.data) {
            const razonSocial = data.data.nombre_o_razon_social;
            mostrarRazonSocial(razonSocial);
            window.razonSocialActual = razonSocial;

            const fuente = data.source === 'cache' ? ' (Cache)' : ' (API)';
            mostrarRazonSocial(razonSocial + fuente);
        } else {
            throw new Error(data.error || 'RUC no encontrado o invalido');
        }

    } catch (error) {
        console.error('Error consultando RUC:', error);
        alert('Error al consultar RUC: ' + error.message);

        document.getElementById('razonSocialDisplay').classList.add('hidden');
        delete window.razonSocialActual;

    } finally {
        btnConsultar.textContent = textoOriginal;
        btnConsultar.disabled = false;
    }
}

// Agregar cliente a la base de datos
async function agregarCliente() {
    if (!window.razonSocialActual) {
        alert('Primero debe consultar un RUC valido');
        return;
    }

    const ruc = document.getElementById('ruc').value.trim();
    const monto = document.getElementById('monto').value.trim();
    const fecha = document.getElementById('fechaVencimiento').value;
    const whatsapp = document.getElementById('whatsapp').value.trim();
    const tipoServicio = document.getElementById('tipoServicio').value;

    if (!ruc || !monto || !fecha || !whatsapp || !tipoServicio) {
        alert('Todos los campos son obligatorios');
        return;
    }

    if (isNaN(parseFloat(monto)) || parseFloat(monto) <= 0) {
        alert('El monto debe ser un numero valido mayor a 0');
        return;
    }

    const whatsappLimpio = whatsapp.replace(/[^0-9]/g, '');
    if (whatsappLimpio.length !== 9) {
        alert('El numero de WhatsApp debe tener 9 digitos');
        return;
    }

    try {
        const clienteData = {
            ruc: ruc,
            razon_social: window.razonSocialActual.split(' (')[0],
            monto: parseFloat(monto).toFixed(2),
            fecha_vencimiento: fecha,
            whatsapp: whatsappLimpio,
            tipo_servicio: tipoServicio
        };

        const response = await fetch(API_CLIENTES_BASE, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(clienteData)
        });

        const data = await response.json();

        if (data.success) {
            await cargarClientesDesdeDB();
            limpiarFormulario();
            habilitarBotones();
            alert('Cliente agregado exitosamente. Total: ' + clientes.length + ' clientes');
        } else {
            alert('Error al agregar cliente: ' + data.error);
        }

    } catch (error) {
        console.error('Error agregando cliente:', error);
        alert('Error de conexion al agregar cliente');
    }
}

// Eliminar cliente de la base de datos
async function eliminarClienteSeleccionado() {
    if (clienteSeleccionado === -1) return;

    const cliente = clientes[clienteSeleccionado];
    if (confirm('Eliminar cliente ' + cliente.razonSocial + '?')) {
        if (cliente.id) {
            try {
                const response = await fetch(API_CLIENTES_BASE, {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: cliente.id })
                });

                const data = await response.json();

                if (data.success) {
                    await cargarClientesDesdeDB();
                    alert('Cliente eliminado exitosamente');
                } else {
                    alert('Error al eliminar cliente');
                    return;
                }
            } catch (error) {
                alert('Error de conexion al eliminar cliente');
                return;
            }
        }

        clienteSeleccionado = -1;
        document.getElementById('btnEliminarCliente').disabled = true;

        if (clientes.length === 0) {
            deshabilitarBotones();
        }

        // Actualizar vista previa
        const canvas = document.getElementById('previewCanvas');
        if (clientes.length > 0) {
            canvas.innerHTML = `
                <span class="emoji">👆</span><br>
                Haga clic en un cliente para ver la vista previa
            `;
        } else {
            canvas.innerHTML = `
                <span class="emoji">🖼️</span><br>
                Agregue clientes para ver la vista previa
            `;
        }
    }
}

// Limpiar todos los clientes
async function limpiarLista() {
    if (clientes.length === 0) return;

    if (confirm('Eliminar todos los ' + clientes.length + ' clientes de la lista?')) {
        try {
            for (const cliente of clientes) {
                if (cliente.id) {
                    await fetch(API_CLIENTES_BASE, {
                        method: 'DELETE',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: cliente.id })
                    });
                }
            }

            await cargarClientesDesdeDB();
            clienteSeleccionado = -1;
            deshabilitarBotones();

            const canvas = document.getElementById('previewCanvas');
            canvas.innerHTML = `
                <span class="emoji">🖼️</span><br>
                Agregue clientes para ver la vista previa
            `;

            alert('Todos los clientes han sido eliminados');
        } catch (error) {
            console.error('Error limpiando lista:', error);
            alert('Error al limpiar la lista');
        }
    }
}

// Verificar vencimientos usando la API
async function verificarVencimientos() {
    if (clientes.length === 0) {
        alert('No hay clientes en la lista para verificar');
        return;
    }

    const diasAnticipacion = parseInt(document.getElementById('diasAnticipacion').value);
    if (isNaN(diasAnticipacion) || diasAnticipacion < 1) {
        alert('Dias de anticipacion debe ser un numero valido mayor a 0');
        return;
    }

    try {
        const response = await fetch(API_CLIENTES_BASE + '?action=vencimientos&dias=' + diasAnticipacion);
        const data = await response.json();

        if (data.success) {
            const resultado = data.data;
            clientesNotificar = [];

            // Procesar todos los tipos de vencimiento
            ['vencidos', 'vence_hoy', 'por_vencer'].forEach(tipo => {
                resultado[tipo].forEach(cliente => {
                    clientesNotificar.push({
                        cliente: {
                            id: cliente.id,
                            ruc: cliente.ruc,
                            razonSocial: cliente.razon_social,
                            monto: cliente.monto,
                            fecha: cliente.fecha_vencimiento,
                            whatsapp: cliente.whatsapp
                        },
                        diasDiferencia: tipo === 'vence_hoy' ? 0 : cliente.dias_restantes
                    });
                });
            });

            mostrarResultadosVencimientos(resultado);
            document.getElementById('btnEnviarRecordatorios').disabled = clientesNotificar.length === 0;
        } else {
            alert('Error al verificar vencimientos: ' + data.error);
        }
    } catch (error) {
        console.error('Error verificando vencimientos:', error);
        alert('Error de conexion al verificar vencimientos');
    }
}

/**
 * Editar cliente seleccionado
 */
function editarClienteSeleccionado() {
    if (clienteSeleccionado === -1) {
        alert('Debe seleccionar un cliente primero');
        return;
    }

    if (clienteSeleccionado >= clientes.length) {
        alert('Cliente seleccionado no válido');
        return;
    }

    const cliente = clientes[clienteSeleccionado];

    // Cargar datos en el formulario con los nombres CORRECTOS
    document.getElementById('ruc').value = cliente.ruc || '';
    document.getElementById('razonSocial').value = cliente.razonSocial || ''; // ← CORREGIDO
    document.getElementById('monto').value = cliente.monto || '';

    // CORREGIR FECHA: usar el campo 'fecha'
    let fechaFormateada = cliente.fecha || ''; // ← CORREGIDO
    if (fechaFormateada && fechaFormateada.includes('/')) {
        // Si viene en formato dd/mm/yyyy, convertir a yyyy-mm-dd
        const partes = fechaFormateada.split('/');
        if (partes.length === 3) {
            const dia = partes[0].padStart(2, '0');
            const mes = partes[1].padStart(2, '0');
            const año = partes[2];
            fechaFormateada = `${año}-${mes}-${dia}`;
        }
    }
    // Si ya viene en formato yyyy-mm-dd, usarlo directamente
    document.getElementById('fechaVencimiento').value = fechaFormateada;

    // CORREGIR WHATSAPP: Quitar prefijo +51 si existe
    let whatsappLimpio = cliente.whatsapp || '';
    if (whatsappLimpio.startsWith('51') && whatsappLimpio.length === 11) {
        whatsappLimpio = whatsappLimpio.substring(2);
    } else if (whatsappLimpio.startsWith('+51')) {
        whatsappLimpio = whatsappLimpio.substring(3);
    }
    document.getElementById('whatsapp').value = whatsappLimpio;

    document.getElementById('tipoServicio').value = cliente.tipo_servicio || 'anual';

    // Establecer razón social actual para validación
    window.razonSocialActual = cliente.razonSocial; // ← CORREGIDO

    // Mostrar el campo de razón social editable
    document.getElementById('razonSocialEdit').classList.remove('hidden');
    document.getElementById('razonSocialDisplay').classList.add('hidden');

    // Cambiar el botón de agregar por actualizar
    const btnAgregar = document.querySelector('button[onclick="agregarCliente()"]');

    if (btnAgregar) {
        btnAgregar.textContent = '📝 Actualizar Cliente';
        btnAgregar.setAttribute('onclick', `actualizarCliente(${cliente.id})`);
        btnAgregar.style.backgroundColor = '#28a745';
    }

    // Scroll al formulario
    document.getElementById('ruc').scrollIntoView({ behavior: 'smooth' });

    alert(`Editando: ${cliente.razonSocial}\nModifique los campos y presione "Actualizar Cliente"`);

    console.log('✅ Editando cliente:', cliente.razonSocial);
    console.log('✅ Fecha cargada:', fechaFormateada);
    console.log('✅ WhatsApp cargado:', whatsappLimpio);
}

/**
 * Actualizar cliente existente
 */
async function actualizarCliente(clienteId) {
    const ruc = document.getElementById('ruc').value.trim();
    const razonSocial = document.getElementById('razonSocial').value.trim();
    const monto = document.getElementById('monto').value.trim();
    const fecha = document.getElementById('fechaVencimiento').value;
    const whatsapp = document.getElementById('whatsapp').value.trim();
    const tipoServicio = document.getElementById('tipoServicio').value;

    // Validaciones
    if (!ruc || !razonSocial || !monto || !fecha || !whatsapp || !tipoServicio) {
        alert('Todos los campos son obligatorios');
        return;
    }

    if (isNaN(parseFloat(monto)) || parseFloat(monto) <= 0) {
        alert('El monto debe ser un número válido mayor a 0');
        return;
    }

    const whatsappLimpio = whatsapp.replace(/[^0-9]/g, '');
    if (whatsappLimpio.length !== 9) {
        alert('El número de WhatsApp debe tener 9 dígitos');
        return;
    }

    if (!confirm('¿Confirma que desea actualizar este cliente?')) {
        return;
    }

    try {
        const clienteData = {
            id: clienteId,
            ruc: ruc,
            razon_social: razonSocial,
            monto: parseFloat(monto).toFixed(2),
            fecha_vencimiento: fecha,
            whatsapp: whatsappLimpio,
            tipo_servicio: tipoServicio
        };

        const response = await fetch(API_CLIENTES_BASE, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(clienteData)
        });

        const data = await response.json();

        if (data.success) {
            await cargarClientesDesdeDB();
            cancelarEdicion();
            habilitarBotones();

            // Mantener la selección del cliente editado
            const nuevoIndex = clientes.findIndex(c => c.id === clienteId);
            if (nuevoIndex !== -1) {
                seleccionarCliente(nuevoIndex);
            }

            alert('Cliente actualizado exitosamente');
        } else {
            alert('Error al actualizar cliente: ' + data.error);
        }

    } catch (error) {
        console.error('Error actualizando cliente:', error);
        alert('Error de conexión al actualizar cliente');
    }
}

/**
 * Cancelar edición y volver al modo agregar
 */
function cancelarEdicion() {
    // Limpiar formulario
    document.getElementById('ruc').value = '';
    document.getElementById('razonSocial').value = '';
    document.getElementById('monto').value = '';
    document.getElementById('fechaVencimiento').value = '';
    document.getElementById('whatsapp').value = '';
    document.getElementById('tipoServicio').value = 'anual';
    window.razonSocialActual = null;

    // OCULTAR campo de razón social editable
    document.getElementById('razonSocialEdit').classList.add('hidden');
    document.getElementById('razonSocialDisplay').classList.add('hidden');

    // Restaurar botón de agregar
    const btnActualizar = document.querySelector('button[onclick*="actualizarCliente"]');
    const btnCancelar = document.getElementById('btnCancelar');

    if (btnActualizar) {
        btnActualizar.textContent = 'Agregar Cliente';
        btnActualizar.setAttribute('onclick', 'agregarCliente()');
        btnActualizar.style.backgroundColor = '';
    }

    // Ocultar botón cancelar
    if (btnCancelar) {
        btnCancelar.style.display = 'none';
    }

    // Restaurar apariencia del formulario
    const formulario = document.querySelector('.form-container, .formulario, form');
    if (formulario) {
        formulario.style.backgroundColor = '';
        formulario.style.border = '';
    }
}

/**
 * Ver detalle completo del cliente
 */
async function verDetalleCliente(clienteId) {
    try {
        const response = await fetch(API_CLIENTES_BASE + `?action=get&id=${clienteId}`);
        const data = await response.json();

        if (data.success) {
            mostrarDetalleClienteEnModal(data.data);
        } else {
            alert('Error cargando detalle: ' + data.error);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error de conexión');
    }
}

/**
 * Mostrar detalle del cliente en modal
 */
function mostrarDetalleClienteEnModal(cliente) {
    const modal = document.createElement('div');
    modal.className = 'modal-overlay';
    modal.innerHTML = `
        <div class="modal-content detalle-cliente">
            <div class="modal-header">
                <h2>📊 Detalle del Cliente</h2>
                <button class="modal-close" onclick="this.closest('.modal-overlay').remove()">×</button>
            </div>
            <div class="modal-body">
                <div class="cliente-datos">
                    <h3>Información General</h3>
                    <div class="info-grid">
                        <div><strong>Razón Social:</strong> ${cliente.razon_social}</div>
                        <div><strong>RUC:</strong> ${cliente.ruc}</div>
                        <div><strong>Monto:</strong> S/ ${cliente.monto}</div>
                        <div><strong>Vencimiento:</strong> ${formatearFecha(cliente.fecha_vencimiento)}</div>
                        <div><strong>WhatsApp:</strong> ${cliente.whatsapp}</div>
                        <div><strong>Servicio:</strong> ${cliente.tipo_servicio || 'anual'}</div>
                        <div><strong>Estado:</strong> <span class="estado ${cliente.estado_vencimiento.toLowerCase()}">${cliente.estado_vencimiento}</span></div>
                        <div><strong>Días restantes:</strong> ${cliente.dias_restantes}</div>
                    </div>
                </div>

                <div class="historial-envios">
                    <h3>📱 Historial de Envíos WhatsApp</h3>
                    ${cliente.historial_envios.length > 0 ?
            '<div class="tabla-envios">' +
            cliente.historial_envios.map(envio => `
                            <div class="envio-item">
                                <span class="fecha">${formatearFecha(envio.fecha_envio)}</span>
                                <span class="tipo">${envio.tipo_envio}</span>
                                <span class="estado ${envio.estado}">${envio.estado}</span>
                            </div>
                        `).join('') +
            '</div>'
            : '<p class="no-data">No hay envíos registrados</p>'
        }
                </div>

                <div class="historial-pagos">
                    <h3>💰 Historial de Pagos</h3>
                    ${cliente.historial_pagos.length > 0 ?
            '<div class="tabla-pagos">' +
            cliente.historial_pagos.map(pago => `
                            <div class="pago-item">
                                <span class="fecha">${formatearFecha(pago.fecha_pago)}</span>
                                <span class="monto">S/ ${pago.monto_pagado}</span>
                                <span class="metodo">${pago.metodo_pago}</span>
                                <span class="operacion">${pago.numero_operacion || 'N/A'}</span>
                            </div>
                        `).join('') +
            '</div>'
            : '<p class="no-data">No hay pagos registrados</p>'
        }
                </div>

                <div class="acciones-cliente">
                    <button class="btn btn-success" onclick="registrarPago(${cliente.id})">💰 Registrar Pago</button>
                    <button class="btn btn-primary" onclick="enviarMensajePersonalizado(${cliente.id})">📱 Enviar Mensaje</button>
                </div>
            </div>
        </div>
    `;

    document.body.appendChild(modal);
}

/**
 * Registrar pago de un cliente
 */
function registrarPago(clienteId) {
    // Obtener los datos del cliente
    const cliente = clientes.find(c => c.id == clienteId);
    if (!cliente) {
        alert('Cliente no encontrado');
        return;
    }

    const modal = document.createElement('div');
    modal.className = 'modal-overlay';
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h2>💰 Registrar Pago</h2>
                <button class="modal-close" onclick="this.closest('.modal-overlay').remove()">×</button>
            </div>
            <div class="modal-body">
                <div class="cliente-info-pago">
                    <h3>Cliente: ${cliente.razonSocial}</h3>
                    <p><strong>RUC:</strong> ${cliente.ruc}</p>
                    <p><strong>Monto actual:</strong> S/ ${cliente.monto}</p>
                    <p><strong>Servicio:</strong> ${cliente.tipo_servicio || 'anual'}</p>
                </div>

                <form id="formRegistrarPago">
                    <div class="form-group">
                        <label for="montoPagado">Monto Pagado (S/): <span style="color: red;">*</span></label>
                        <input type="number" id="montoPagado" step="0.01" min="0" value="${cliente.monto}" required>
                    </div>

                    <div class="form-group">
                        <label for="fechaPago">Fecha de Pago: <span style="color: red;">*</span></label>
                        <input type="date" id="fechaPago" value="${formatearFechaISO()}" required>
                    </div>

                    <div class="form-group">
                        <label for="metodoPago">Método de Pago: <span style="color: red;">*</span></label>
                        <select id="metodoPago" required>
                            <option value="">Seleccionar método</option>
                            <option value="transferencia">Transferencia Bancaria</option>
                            <option value="deposito">Depósito</option>
                            <option value="yape">Yape</option>
                            <option value="plin">Plin</option>
                            <option value="efectivo">Efectivo</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="numeroOperacion">N° Operación/Voucher:</label>
                        <input type="text" id="numeroOperacion" placeholder="Opcional">
                    </div>

                    <div class="form-group">
                        <label for="banco">Banco:</label>
                        <select id="banco">
                            <option value="">Seleccionar banco (opcional)</option>
                            <option value="Scotiabank">Scotiabank</option>
                            <option value="BCP">BCP</option>
                            <option value="Interbank">Interbank</option>
                            <option value="BBVA">BBVA</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="observaciones">Observaciones:</label>
                        <textarea id="observaciones" rows="3" placeholder="Observaciones adicionales"></textarea>
                    </div>

                    <div class="nueva-fecha-info">
                        <div class="form-group">
                            <label>
                                <input type="radio" name="fechaOption" value="automatica" checked> 
                                Calcular automáticamente: <span id="nuevaFechaVencimiento">Calculando...</span>
                            </label>
                        </div>
                        <div class="form-group">
                            <label>
                                <input type="radio" name="fechaOption" value="manual"> 
                                Ingresar fecha manualmente:
                            </label>
                            <input type="date" id="fechaVencimientoManual" style="margin-left: 20px; display: none;">
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-success">💰 Registrar Pago</button>
                        <button type="button" class="btn btn-secondary" onclick="this.closest('.modal-overlay').remove()">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    `;

    document.body.appendChild(modal);

    // Calcular nueva fecha basada en la fecha de vencimiento actual
    const nuevaFechaSpan = document.getElementById('nuevaFechaVencimiento');
    
    function calcularNuevaFecha() {
        // Validar que la fecha de vencimiento existe y es válida
        if (!cliente.fecha) {
            nuevaFechaSpan.textContent = 'Fecha de vencimiento no disponible';
            nuevaFechaSpan.style.color = 'red';
            return;
        }

        // Usar la fecha de vencimiento actual del cliente
        const fechaVencimientoActual = new Date(cliente.fecha + 'T00:00:00');
        
        // Validar que la fecha creada es válida
        if (isNaN(fechaVencimientoActual.getTime())) {
            nuevaFechaSpan.textContent = 'Fecha de vencimiento inválida';
            nuevaFechaSpan.style.color = 'red';
            return;
        }

        const diaOriginal = fechaVencimientoActual.getDate();
        const tipoServicio = cliente.tipo_servicio || 'anual';
        
        // Función auxiliar para añadir meses manteniendo el día original o el último día del mes
        function addMonthsKeepingDay(date, months) {
            const newDate = new Date(date);
            const targetMonth = newDate.getMonth() + months;
            
            // Establecer el nuevo mes
            newDate.setMonth(targetMonth);
            
            // Si el día cambió (porque el mes no tiene ese día), ajustar al último día del mes anterior
            if (newDate.getDate() !== diaOriginal) {
                newDate.setDate(0); // Ir al último día del mes anterior
            }
            
            return newDate;
        }
        
        let nuevaFecha;
        
        // Añadir el periodo correspondiente
        switch (tipoServicio) {
            case 'mensual':
                nuevaFecha = addMonthsKeepingDay(fechaVencimientoActual, 1);
                break;
            case 'trimestral':
                nuevaFecha = addMonthsKeepingDay(fechaVencimientoActual, 3);
                break;
            case 'semestral':
                nuevaFecha = addMonthsKeepingDay(fechaVencimientoActual, 6);
                break;
            case 'anual':
            default:
                nuevaFecha = addMonthsKeepingDay(fechaVencimientoActual, 12);
                break;
        }
        
        nuevaFechaSpan.textContent = nuevaFecha.toLocaleDateString('es-PE');
    }
    
    calcularNuevaFecha(); // Mostrar la nueva fecha calculada

    // Manejar cambio entre fecha automática y manual
    const radioButtons = document.querySelectorAll('input[name="fechaOption"]');
    const fechaManualInput = document.getElementById('fechaVencimientoManual');
    
    radioButtons.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'manual') {
                fechaManualInput.style.display = 'inline-block';
                fechaManualInput.required = true;
            } else {
                fechaManualInput.style.display = 'none';
                fechaManualInput.required = false;
                fechaManualInput.value = '';
            }
        });
    });

    // Manejar envío del formulario
    document.getElementById('formRegistrarPago').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Determinar la nueva fecha de vencimiento
        let nuevaFechaVencimiento = null;
        const fechaOption = document.querySelector('input[name="fechaOption"]:checked').value;
        
        if (fechaOption === 'manual') {
            const fechaManual = document.getElementById('fechaVencimientoManual').value;
            if (!fechaManual) {
                alert('Por favor, ingrese la nueva fecha de vencimiento manual.');
                return;
            }
            nuevaFechaVencimiento = fechaManual;
        } else {
            // Recalcular la fecha automática para enviar al servidor
            if (cliente.fecha) {
                const fechaVencimientoActual = new Date(cliente.fecha + 'T00:00:00');
                if (!isNaN(fechaVencimientoActual.getTime())) {
                    const tipoServicio = cliente.tipo_servicio || 'anual';
                    const mesesAAgregar = tipoServicio === 'mensual' ? 1 : 
                                         tipoServicio === 'trimestral' ? 3 : 
                                         tipoServicio === 'semestral' ? 6 : 12;
                    
                    const nuevaFecha = new Date(fechaVencimientoActual);
                    nuevaFecha.setMonth(nuevaFecha.getMonth() + mesesAAgregar);
                    nuevaFechaVencimiento = nuevaFecha.toISOString().split('T')[0];
                }
            }
            
            if (!nuevaFechaVencimiento) {
                alert('No se pudo calcular la nueva fecha de vencimiento. Use la opción manual.');
                return;
            }
        }

        const formData = {
            cliente_id: clienteId,
            monto_pagado: document.getElementById('montoPagado').value,
            fecha_pago: document.getElementById('fechaPago').value,
            metodo_pago: document.getElementById('metodoPago').value,
            numero_operacion: document.getElementById('numeroOperacion').value || null,
            banco: document.getElementById('banco').value || null,
            observaciones: document.getElementById('observaciones').value || null,
            nueva_fecha_vencimiento: nuevaFechaVencimiento
        };

        try {
            const response = await fetch(API_CLIENTES_BASE + '?action=registrar_pago', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            });

            const data = await response.json();

            if (data.success) {
                alert('Pago registrado exitosamente\n\nNueva fecha de vencimiento: ' + formatearFecha(data.data.nueva_fecha_vencimiento));
                modal.remove();
                
                // Recargar la lista de clientes para mostrar el estado actualizado
                await cargarClientesDesdeDB();
                
                // Cerrar el modal de detalle si está abierto
                const modalDetalle = document.querySelector('.modal-overlay .detalle-cliente');
                if (modalDetalle) {
                    modalDetalle.closest('.modal-overlay').remove();
                }
            } else {
                alert('Error al registrar pago: ' + (data.error || data.errors?.join(', ')));
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error de conexión al registrar pago');
        }
    });
}

/**
 * Enviar mensaje personalizado a un cliente (función stub)
 */
async function enviarMensajePersonalizado(clienteId) {
    // Buscar el cliente en la lista local
    const cliente = clientes.find(c => c.id == clienteId);
    if (!cliente) {
        alert('Cliente no encontrado');
        return;
    }

    // Debug: ver qué campos tiene el cliente
    console.log('Cliente para envío:', cliente);
    console.log('Campos disponibles:', Object.keys(cliente));

    // Confirmar envío
    const nombreCliente = cliente.razonSocial || cliente.razon_social;
    const confirmar = confirm(`¿Enviar orden de pago a ${nombreCliente}?\n\nNúmero: +51${cliente.whatsapp}\nMonto: S/ ${cliente.monto}`);
    if (!confirmar) return;

    try {
        // Mostrar progreso
        const progressDiv = document.createElement('div');
        progressDiv.style.cssText = 'position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 20px rgba(0,0,0,0.3); z-index: 10000; text-align: center;';
        progressDiv.innerHTML = `
            <div style="color: #2581c4; font-weight: bold; margin-bottom: 15px;">📤 Enviando orden de pago...</div>
            <div style="color: #666;">Generando imagen personalizada</div>
        `;
        document.body.appendChild(progressDiv);

        // Generar canvas de la orden de pago
        const canvas = await generarCanvasOrdenPago(cliente);
        const imagenBase64 = canvasToBase64(canvas);

        // Actualizar progreso
        progressDiv.innerHTML = `
            <div style="color: #2581c4; font-weight: bold; margin-bottom: 15px;">📤 Enviando orden de pago...</div>
            <div style="color: #666;">Enviando imagen por WhatsApp</div>
        `;

        // Enviar imagen
        const fechaVencimiento = cliente.fecha || cliente.fecha_vencimiento;
        const responseImagen = await fetch(API_ENVIOS_BASE, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'generar_imagen_recordatorio',
                cliente_id: cliente.id,
                dias_restantes: calcularDiasRestantes(fechaVencimiento),
                imagen_base64: imagenBase64
            })
        });

        const dataImagen = await responseImagen.json();
        
        if (dataImagen.success) {
            // Esperar un poco antes del texto
            await new Promise(resolve => setTimeout(resolve, 2000));

            // Actualizar progreso
            progressDiv.innerHTML = `
                <div style="color: #2581c4; font-weight: bold; margin-bottom: 15px;">📤 Enviando orden de pago...</div>
                <div style="color: #666;">Enviando mensaje de texto</div>
            `;

            // Generar mensaje personalizado
            const mensaje = generarMensajeOrdenPago(cliente);

            // Enviar texto
            const responseTexto = await fetch(API_ENVIOS_BASE, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'enviar_texto',
                    cliente_id: cliente.id,
                    numero: cliente.whatsapp,
                    mensaje: mensaje
                })
            });

            const dataTexto = await responseTexto.json();
            
            // Remover progreso
            document.body.removeChild(progressDiv);

            if (dataTexto.success) {
                alert(`✅ Orden de pago enviada exitosamente a ${nombreCliente}`);
                // Cerrar modal de detalle si está abierto
                const modal = document.querySelector('.modal-overlay');
                if (modal) modal.remove();
            } else {
                alert(`❌ Error enviando mensaje: ${dataTexto.error}`);
            }
        } else {
            document.body.removeChild(progressDiv);
            alert(`❌ Error enviando imagen: ${dataImagen.error}`);
        }

    } catch (error) {
        // Remover progreso si hay error
        const progressDiv = document.querySelector('div[style*="position: fixed"]');
        if (progressDiv) document.body.removeChild(progressDiv);
        
        console.error('Error enviando mensaje:', error);
        alert('❌ Error de conexión al enviar mensaje');
    }
}

// Función auxiliar para generar el canvas de orden de pago
async function generarCanvasOrdenPago(cliente) {
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
    const fechaTexto = convertirFechaATexto(cliente.fecha || cliente.fecha_vencimiento);
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
    ctx.fillText('Cliente: ' + (cliente.razonSocial || cliente.razon_social), 50, 270);
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

    // Cargar imágenes si están disponibles
    await new Promise((resolve) => {
        let imagenesRestantes = 2;
        const decrementarContador = () => {
            imagenesRestantes--;
            if (imagenesRestantes === 0) resolve();
        };

        // Logo
        const logo = new Image();
        logo.onload = function() {
            ctx.drawImage(logo, 720, 40, 145, 80);
            decrementarContador();
        };
        logo.onerror = decrementarContador;
        logo.src = 'logo.png';

        // Mascota
        const mascota = new Image();
        mascota.onload = function() {
            ctx.drawImage(mascota, 650, 270, 200, 200);
            decrementarContador();
        };
        mascota.onerror = decrementarContador;
        mascota.src = 'mascota.png';

        // Timeout por si las imágenes no cargan
        setTimeout(resolve, 2000);
    });

    return canvas;
}

// Función auxiliar para generar mensaje de orden de pago
function generarMensajeOrdenPago(cliente) {
    const fechaVencimiento = cliente.fecha || cliente.fecha_vencimiento;
    const diasRestantes = calcularDiasRestantes(fechaVencimiento);
    const fechaFormateada = formatearFecha(fechaVencimiento);
    const nombreCliente = cliente.razonSocial || cliente.razon_social;
    
    let mensaje = `🏢 *IMAGINATICS PERU SAC*\n\n`;
    mensaje += `Estimado(a) cliente,\n\n`;
    mensaje += `Recordamos que tiene una orden de pago pendiente:\n\n`;
    mensaje += `📋 *Detalles:*\n`;
    mensaje += `• Cliente: ${nombreCliente}\n`;
    mensaje += `• RUC: ${cliente.ruc}\n`;
    mensaje += `• Monto: S/ ${cliente.monto}\n`;
    mensaje += `• Vencimiento: ${fechaFormateada}\n`;
    mensaje += `• Servicio: ${cliente.tipo_servicio || 'anual'}\n\n`;
    
    if (diasRestantes <= 0) {
        mensaje += `⚠️ *ATENCIÓN:* Esta orden ya está vencida.\n\n`;
    } else if (diasRestantes <= 3) {
        mensaje += `⚠️ *URGENTE:* Vence en ${diasRestantes} día(s).\n\n`;
    } else {
        mensaje += `📅 Vence en ${diasRestantes} día(s).\n\n`;
    }
    
    mensaje += `💰 *Cuentas para pago:*\n`;
    CONFIG.CUENTAS_BANCARIAS.forEach(cuenta => {
        mensaje += `• ${cuenta}\n`;
    });
    
    mensaje += `\n¡Gracias por su confianza! 🤝`;
    
    return mensaje;
}

// Función auxiliar para calcular días restantes
function calcularDiasRestantes(fechaVencimiento) {
    const hoy = obtenerFechaPeru();
    const fecha = new Date(fechaVencimiento);
    
    hoy.setHours(0, 0, 0, 0);
    fecha.setHours(0, 0, 0, 0);
    
    return Math.ceil((fecha - hoy) / (1000 * 60 * 60 * 24));
}

// Función auxiliar para convertir canvas a base64
function canvasToBase64(canvas) {
    return canvas.toDataURL('image/png').split(',')[1];
}