// ============================================
// MÓDULO DE GESTIÓN DE SERVICIOS
// Sistema Multi-Servicio - Imaginatics Peru SAC
// ============================================

/**
 * API de Servicios
 */
const ServiciosAPI = {
    /**
     * Obtener catálogo de servicios
     */
    async obtenerCatalogo(filtros = {}) {
        try {
            const params = new URLSearchParams();
            if (filtros.categoria) params.append('categoria', filtros.categoria);
            if (filtros.activos !== undefined) params.append('activos', filtros.activos);

            const url = `${API_SERVICIOS_BASE}?action=catalogo&${params}`;
            const response = await fetch(url);
            const data = await response.json();

            if (!data.success) {
                throw new Error(data.error || 'Error al obtener catálogo');
            }

            return data.data;
        } catch (error) {
            console.error('Error en obtenerCatalogo:', error);
            mostrarError('Error al cargar catálogo de servicios');
            throw error;
        }
    },

    /**
     * Obtener servicios contratados por cliente
     */
    async obtenerServiciosCliente(clienteId) {
        try {
            const response = await fetch(`${API_SERVICIOS_BASE}?action=cliente&cliente_id=${clienteId}`);
            const data = await response.json();

            if (!data.success) {
                throw new Error(data.error || 'Error al obtener servicios del cliente');
            }

            return data.data;
        } catch (error) {
            console.error('Error en obtenerServiciosCliente:', error);
            mostrarError('Error al cargar servicios del cliente');
            throw error;
        }
    },

    /**
     * Obtener detalle de un contrato
     */
    async obtenerDetalleContrato(contratoId) {
        try {
            const response = await fetch(`${API_SERVICIOS_BASE}?action=contrato&id=${contratoId}`);
            const data = await response.json();

            if (!data.success) {
                throw new Error(data.error || 'Error al obtener detalle del contrato');
            }

            return data.data;
        } catch (error) {
            console.error('Error en obtenerDetalleContrato:', error);
            mostrarError('Error al cargar detalle del contrato');
            throw error;
        }
    },

    /**
     * Contratar un nuevo servicio
     */
    async contratarServicio(datosServicio) {
        try {
            console.log('📤 Enviando datos de contratación:', datosServicio);

            const response = await fetch(API_SERVICIOS_BASE, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'contratar',
                    ...datosServicio
                })
            });

            console.log('📥 Respuesta HTTP:', response.status, response.statusText);

            const data = await response.json();
            console.log('📥 Respuesta JSON:', data);

            if (!data.success) {
                throw new Error(data.error || 'Error al contratar servicio');
            }

            mostrarExito('Servicio contratado exitosamente');
            return data.data;
        } catch (error) {
            console.error('❌ Error en contratarServicio:', error);
            mostrarError('Error al contratar servicio: ' + (error.message || error));
            throw error;
        }
    },

    /**
     * Actualizar un servicio contratado
     */
    async actualizarServicio(contratoId, cambios) {
        try {
            const response = await fetch(API_SERVICIOS_BASE, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    contrato_id: contratoId,
                    ...cambios
                })
            });

            const data = await response.json();

            if (!data.success) {
                throw new Error(data.error || 'Error al actualizar servicio');
            }

            mostrarExito('Servicio actualizado exitosamente');
            return data.data;
        } catch (error) {
            console.error('Error en actualizarServicio:', error);
            mostrarError('Error al actualizar servicio');
            throw error;
        }
    },

    /**
     * Suspender servicio
     */
    async suspenderServicio(contratoId, motivo) {
        try {
            const response = await fetch(`${API_SERVICIOS_BASE}?action=suspender`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    contrato_id: contratoId,
                    motivo: motivo
                })
            });

            const data = await response.json();

            if (!data.success) {
                throw new Error(data.error || 'Error al suspender servicio');
            }

            mostrarExito('Servicio suspendido exitosamente');
            return data;
        } catch (error) {
            console.error('Error en suspenderServicio:', error);
            mostrarError('Error al suspender servicio');
            throw error;
        }
    },

    /**
     * Reactivar servicio
     */
    async reactivarServicio(contratoId, nuevaFechaVencimiento = null) {
        try {
            const response = await fetch(`${API_SERVICIOS_BASE}?action=reactivar`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    contrato_id: contratoId,
                    nueva_fecha_vencimiento: nuevaFechaVencimiento
                })
            });

            const data = await response.json();

            if (!data.success) {
                throw new Error(data.error || 'Error al reactivar servicio');
            }

            mostrarExito('Servicio reactivado exitosamente');
            return data;
        } catch (error) {
            console.error('Error en reactivarServicio:', error);
            mostrarError('Error al reactivar servicio');
            throw error;
        }
    },

    /**
     * Cancelar servicio
     */
    async cancelarServicio(contratoId, motivo) {
        try {
            const response = await fetch(API_SERVICIOS_BASE, {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    contrato_id: contratoId,
                    motivo: motivo
                })
            });

            const data = await response.json();

            if (!data.success) {
                throw new Error(data.error || 'Error al cancelar servicio');
            }

            mostrarExito('Servicio cancelado exitosamente');
            return data;
        } catch (error) {
            console.error('Error en cancelarServicio:', error);
            mostrarError('Error al cancelar servicio');
            throw error;
        }
    },

    /**
     * Obtener servicios por vencer
     */
    async obtenerServiciosPorVencer(dias = 7) {
        try {
            const response = await fetch(`${API_SERVICIOS_BASE}?action=por_vencer&dias=${dias}`);
            const data = await response.json();

            if (!data.success) {
                throw new Error(data.error || 'Error al obtener servicios por vencer');
            }

            return data.data;
        } catch (error) {
            console.error('Error en obtenerServiciosPorVencer:', error);
            mostrarError('Error al cargar servicios por vencer');
            throw error;
        }
    },

    /**
     * Obtener estadísticas de servicios
     */
    async obtenerEstadisticas() {
        try {
            const response = await fetch(`${API_SERVICIOS_BASE}?action=stats`);
            const data = await response.json();

            if (!data.success) {
                throw new Error(data.error || 'Error al obtener estadísticas');
            }

            return data.data;
        } catch (error) {
            console.error('Error en obtenerEstadisticas:', error);
            mostrarError('Error al cargar estadísticas');
            throw error;
        }
    }
};

/**
 * UI de Servicios - Componentes visuales
 */
const ServiciosUI = {
    /**
     * Renderizar tarjeta de servicio
     */
    renderizarTarjetaServicio(servicio) {
        const estadoClass = this.obtenerClaseEstado(servicio.estado_vencimiento || servicio.estado);
        const estadoTexto = this.obtenerTextoEstado(servicio.estado_vencimiento || servicio.estado);
        const monedaSimbolo = servicio.moneda === 'USD' ? '$' : 'S/';
        const estado = servicio.estado || 'activo';

        // Determinar clase CSS según estado
        let claseEstadoCSS = '';
        if (estado === 'suspendido') claseEstadoCSS = 'suspendido';
        else if (estado === 'vencido') claseEstadoCSS = 'vencido';
        else if (estado === 'cancelado') claseEstadoCSS = 'cancelado';

        return `
            <div class="servicio-tarjeta ${claseEstadoCSS}" data-contrato-id="${servicio.contrato_id || servicio.id}">
                <div class="servicio-header">
                    <div class="servicio-nombre">
                        <strong>${servicio.servicio_nombre || servicio.nombre}</strong>
                        <span class="servicio-categoria">${servicio.categoria}</span>
                    </div>
                    <span class="badge ${estadoClass}">${estadoTexto}</span>
                </div>

                <div class="servicio-detalles">
                    <div class="detalle-item">
                        <span class="label">Precio:</span>
                        <span class="value">${monedaSimbolo} ${parseFloat(servicio.precio).toFixed(2)}</span>
                    </div>
                    <div class="detalle-item">
                        <span class="label">Periodo:</span>
                        <span class="value">${this.capitalize(servicio.periodo_facturacion)}</span>
                    </div>
                    <div class="detalle-item">
                        <span class="label">Vencimiento:</span>
                        <span class="value">${this.formatearFecha(servicio.fecha_vencimiento)}</span>
                    </div>
                    ${servicio.dias_restantes !== undefined ? `
                    <div class="detalle-item">
                        <span class="label">Días restantes:</span>
                        <span class="value ${servicio.dias_restantes < 0 ? 'text-danger' : ''}">${servicio.dias_restantes}</span>
                    </div>
                    ` : ''}
                </div>

                <div class="servicio-acciones">
                    ${this.renderizarBotonesAccion(servicio)}
                </div>
            </div>
        `;
    },

    /**
     * Renderizar botones de acción según el estado del servicio
     */
    renderizarBotonesAccion(servicio) {
        const contratoId = servicio.contrato_id || servicio.id;
        const estado = servicio.estado || 'activo';

        if (estado === 'activo') {
            return `
                <button class="btn-sm btn-primary" onclick="ServiciosUI.verDetalleServicio(${contratoId})" title="Ver detalles">
                    📊 Detalle
                </button>
                <button class="btn-sm" onclick="ServiciosUI.editarServicio(${contratoId})" title="Editar" style="background: #17a2b8; color: white;">
                    ✏️ Editar
                </button>
                <button class="btn-sm btn-success" onclick="ServiciosUI.enviarOrdenPago(${contratoId})" title="Enviar orden">
                    📤 Enviar
                </button>
                <button class="btn-sm btn-warning" onclick="ServiciosUI.suspenderServicio(${contratoId})" title="Suspender">
                    ⏸️ Suspender
                </button>
            `;
        } else if (estado === 'vencido') {
            // Servicios vencidos: pueden recibir orden de pago, suspender o cancelar
            return `
                <button class="btn-sm btn-primary" onclick="ServiciosUI.verDetalleServicio(${contratoId})" title="Ver detalles">
                    📊 Detalle
                </button>
                <button class="btn-sm" onclick="ServiciosUI.editarServicio(${contratoId})" title="Editar" style="background: #17a2b8; color: white;">
                    ✏️ Editar
                </button>
                <button class="btn-sm btn-success" onclick="ServiciosUI.enviarOrdenPago(${contratoId})" title="Enviar orden de pago">
                    📤 Enviar Orden
                </button>
                <button class="btn-sm btn-warning" onclick="ServiciosUI.suspenderServicio(${contratoId})" title="Suspender">
                    ⏸️ Suspender
                </button>
            `;
        } else if (estado === 'suspendido') {
            return `
                <button class="btn-sm btn-primary" onclick="ServiciosUI.verDetalleServicio(${contratoId})">
                    📊 Detalle
                </button>
                <button class="btn-sm" onclick="ServiciosUI.editarServicio(${contratoId})" title="Editar" style="background: #17a2b8; color: white;">
                    ✏️ Editar
                </button>
                <button class="btn-sm btn-success" onclick="ServiciosUI.reactivarServicio(${contratoId})" title="Reactivar">
                    ▶️ Reactivar
                </button>
                <button class="btn-sm btn-danger" onclick="ServiciosUI.confirmarCancelacion(${contratoId})">
                    ❌ Cancelar
                </button>
            `;
        } else if (estado === 'cancelado') {
            return `
                <button class="btn-sm btn-primary" onclick="ServiciosUI.verDetalleServicio(${contratoId})">
                    📊 Historial
                </button>
                <span style="color: #6c757d; font-size: 12px; font-style: italic;">Servicio cancelado</span>
            `;
        }

        return '';
    },

    /**
     * Mostrar servicios de un cliente en modal
     */
    async mostrarServiciosCliente(clienteId) {
        try {
            const resultado = await ServiciosAPI.obtenerServiciosCliente(clienteId);

            if (!resultado.servicios || resultado.servicios.length === 0) {
                mostrarInfo('El cliente no tiene servicios contratados');
                return;
            }

            const serviciosHTML = resultado.servicios
                .map(servicio => this.renderizarTarjetaServicio(servicio))
                .join('');

            const resumen = resultado.resumen || {};

            const modalHTML = `
                <div class="modal-overlay" id="modalServiciosCliente" data-cliente-id="${clienteId}">
                    <div class="modal-content modal-lg">
                        <div class="modal-header">
                            <h3>Servicios Contratados</h3>
                            <button class="close-btn" onclick="ServiciosUI.cerrarModal()">×</button>
                        </div>
                        <div class="modal-body">
                            <div class="resumen-financiero">
                                <div class="resumen-item">
                                    <span class="label">Total Servicios:</span>
                                    <span class="value">${resumen.total_servicios || 0}</span>
                                </div>
                                <div class="resumen-item">
                                    <span class="label">Servicios Activos:</span>
                                    <span class="value">${resumen.servicios_activos || 0}</span>
                                </div>
                                <div class="resumen-item">
                                    <span class="label">Monto Mensual:</span>
                                    <span class="value">S/ ${parseFloat(resumen.monto_servicios_activos || 0).toFixed(2)}</span>
                                </div>
                            </div>
                            <div class="servicios-lista">
                                ${serviciosHTML}
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-secondary" onclick="ServiciosUI.cerrarModal()">Cerrar</button>
                            <button class="btn btn-success" onclick="PagosMultiServicio.abrirModalPago(${clienteId})" style="background: #28a745;">
                                💰 Registrar Pago
                            </button>
                            <button class="btn btn-primary" onclick="ServiciosUI.agregarServicio(${clienteId})">
                                ➕ Agregar Servicio
                            </button>
                        </div>
                    </div>
                </div>
            `;

            document.body.insertAdjacentHTML('beforeend', modalHTML);
        } catch (error) {
            console.error('Error al mostrar servicios:', error);
        }
    },

    /**
     * Ver detalle de un servicio con historial de pagos
     */
    async verDetalleServicio(contratoId) {
        try {
            // Obtener historial del servicio
            const response = await fetch(`${API_CLIENTES_BASE}?action=historial_servicio&contrato_id=${contratoId}`);
            const resultado = await response.json();

            if (!resultado.success) {
                throw new Error(resultado.error || 'Error al cargar historial');
            }

            const { servicio, pagos, estadisticas } = resultado.data;

            // Renderizar modal con historial
            this._renderizarModalDetalle(servicio, pagos, estadisticas);

        } catch (error) {
            console.error('Error al ver detalle:', error);
            mostrarError('Error al cargar historial del servicio');
        }
    },

    /**
     * Renderizar modal de detalle con historial
     */
    _renderizarModalDetalle(servicio, pagos, stats) {
        const monedaSimbolo = servicio.moneda === 'USD' ? '$' : 'S/';
        const estadoClass = this.obtenerClaseEstado(servicio.estado);
        const estadoTexto = this.obtenerTextoEstado(servicio.estado);
        const estadoBadge = `<span class="badge ${estadoClass}">${estadoTexto}</span>`;

        const modalHTML = `
            <div class="modal-overlay" id="modalDetalleServicio">
                <div class="modal-content modal-lg">
                    <div class="modal-header">
                        <h3>📊 Detalle del Servicio</h3>
                        <button class="close-btn" onclick="ServiciosUI.cerrarModalDetalle()">&times;</button>
                    </div>
                    <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                        <!-- Información del Servicio -->
                        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 25px; border-radius: 12px; color: white; margin-bottom: 25px;">
                            <h2 style="margin: 0 0 10px 0; font-size: 24px;">${servicio.servicio_nombre}</h2>
                            <p style="margin: 0; opacity: 0.9;">${servicio.razon_social} - RUC: ${servicio.ruc}</p>
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-top: 20px;">
                                <div>
                                    <div style="opacity: 0.8; font-size: 13px;">Precio</div>
                                    <div style="font-size: 28px; font-weight: 700;">${monedaSimbolo} ${parseFloat(servicio.precio).toFixed(2)}</div>
                                </div>
                                <div>
                                    <div style="opacity: 0.8; font-size: 13px;">Periodo</div>
                                    <div style="font-size: 20px; font-weight: 600; text-transform: capitalize;">${servicio.periodo_facturacion}</div>
                                </div>
                                <div>
                                    <div style="opacity: 0.8; font-size: 13px;">Vencimiento</div>
                                    <div style="font-size: 18px; font-weight: 600;">${this.formatearFecha(servicio.fecha_vencimiento)}</div>
                                </div>
                                <div>
                                    <div style="opacity: 0.8; font-size: 13px;">Estado</div>
                                    <div style="font-size: 18px; font-weight: 600;">${estadoBadge}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Estadísticas de Pagos -->
                        <div style="background: #f8f9fa; padding: 20px; border-radius: 12px; margin-bottom: 25px;">
                            <h4 style="margin: 0 0 15px 0; color: #2581c4;">💰 Estadísticas de Pagos</h4>
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 15px;">
                                <div style="background: white; padding: 15px; border-radius: 8px; border-left: 4px solid #28a745;">
                                    <div style="color: #666; font-size: 12px; margin-bottom: 5px;">Total Pagado</div>
                                    <div style="font-size: 22px; font-weight: 700; color: #28a745;">${monedaSimbolo} ${stats.total_pagado.toFixed(2)}</div>
                                </div>
                                <div style="background: white; padding: 15px; border-radius: 8px; border-left: 4px solid #2581c4;">
                                    <div style="color: #666; font-size: 12px; margin-bottom: 5px;">Cantidad de Pagos</div>
                                    <div style="font-size: 22px; font-weight: 700; color: #2581c4;">${stats.cantidad_pagos}</div>
                                </div>
                                <div style="background: white; padding: 15px; border-radius: 8px; border-left: 4px solid #ffc107;">
                                    <div style="color: #666; font-size: 12px; margin-bottom: 5px;">Promedio</div>
                                    <div style="font-size: 22px; font-weight: 700; color: #ffc107;">${monedaSimbolo} ${stats.promedio_pago.toFixed(2)}</div>
                                </div>
                                <div style="background: white; padding: 15px; border-radius: 8px; border-left: 4px solid #6c757d;">
                                    <div style="color: #666; font-size: 12px; margin-bottom: 5px;">Último Pago</div>
                                    <div style="font-size: 16px; font-weight: 700; color: #6c757d;">${stats.ultimo_pago ? this.formatearFecha(stats.ultimo_pago) : 'N/A'}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Historial de Pagos -->
                        <div>
                            <h4 style="margin: 0 0 15px 0; color: #2581c4;">📜 Historial de Pagos</h4>
                            ${pagos.length > 0 ? this._renderizarTimelinePagos(pagos, monedaSimbolo) : '<p style="text-align: center; color: #999; padding: 40px;">No hay pagos registrados para este servicio</p>'}
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" onclick="ServiciosUI.cerrarModalDetalle()">Cerrar</button>
                        ${servicio.estado !== 'cancelado' ? `
                            <button class="btn btn-primary" onclick="ServiciosUI.editarServicio(${servicio.id})" style="background: #17a2b8;">
                                ✏️ Editar Servicio
                            </button>
                        ` : ''}
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHTML);
    },

    /**
     * Renderizar timeline de pagos
     */
    _renderizarTimelinePagos(pagos, monedaSimbolo) {
        return `
            <div class="timeline-pagos">
                ${pagos.map(pago => `
                    <div class="timeline-item">
                        <div class="timeline-marker"></div>
                        <div class="timeline-content">
                            <div class="timeline-header">
                                <span class="timeline-fecha">${this.formatearFecha(pago.fecha_pago)}</span>
                                <span class="timeline-monto">${monedaSimbolo} ${parseFloat(pago.monto_pagado).toFixed(2)}</span>
                            </div>
                            <div class="timeline-body">
                                <div class="timeline-metodo">
                                    <strong>Método:</strong> ${this.capitalize(pago.metodo_pago)}
                                    ${pago.banco ? `<span style="color: #666;"> • ${pago.banco}</span>` : ''}
                                </div>
                                ${pago.numero_operacion ? `
                                    <div class="timeline-operacion">
                                        <strong>N° Operación:</strong> ${pago.numero_operacion}
                                    </div>
                                ` : ''}
                                ${pago.observaciones ? `
                                    <div class="timeline-observaciones">
                                        <strong>Observaciones:</strong> ${pago.observaciones}
                                    </div>
                                ` : ''}
                                ${pago.servicios_pagados && pago.servicios_pagados.length > 1 ? `
                                    <div class="timeline-multi" style="margin-top: 8px; padding: 8px; background: #fff3cd; border-radius: 6px; font-size: 13px;">
                                        ⚠️ Este pago incluyó ${pago.servicios_pagados.length} servicios
                                    </div>
                                ` : ''}
                            </div>
                            <div class="timeline-footer">
                                <small style="color: #999;">Registrado por: ${pago.registrado_por || 'Sistema'} • ${this.formatearFechaHora(pago.fecha_registro)}</small>
                            </div>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
    },

    /**
     * Cerrar modal de detalle
     */
    cerrarModalDetalle() {
        const modal = document.getElementById('modalDetalleServicio');
        if (modal) modal.remove();
    },

    /**
     * Editar servicio contratado
     */
    async editarServicio(contratoId) {
        try {
            // Obtener datos actuales del servicio usando el endpoint de historial
            const response = await fetch(`${API_CLIENTES_BASE}?action=historial_servicio&contrato_id=${contratoId}`);
            const resultado = await response.json();

            if (!resultado.success) {
                throw new Error(resultado.error || 'Error al cargar servicio');
            }

            const datos = resultado.data.servicio;
            this._renderizarModalEdicion(datos);

        } catch (error) {
            console.error('Error al editar servicio:', error);
            mostrarError('Error al cargar datos del servicio');
        }
    },

    /**
     * Renderizar modal de edición
     */
    _renderizarModalEdicion(servicio) {
        const modalHTML = `
            <div class="modal-overlay" id="modalEditarServicio">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>✏️ Editar Servicio</h3>
                        <button class="close-btn" onclick="ServiciosUI.cerrarModalEdicion()">&times;</button>
                    </div>
                    <div class="modal-body">
                        <form id="formEditarServicio">
                            <input type="hidden" id="edit_contrato_id" value="${servicio.id}">

                            <div class="form-group">
                                <label>Servicio</label>
                                <input type="text" value="${servicio.servicio_nombre}" readonly style="background: #f8f9fa;">
                                <small class="text-muted">No se puede cambiar el tipo de servicio</small>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                <div class="form-group">
                                    <label for="edit_precio">Precio *</label>
                                    <div class="input-group">
                                        <span id="edit_moneda_label">${servicio.moneda === 'USD' ? '$' : 'S/'}</span>
                                        <input type="number" id="edit_precio" step="0.01" min="0"
                                               value="${servicio.precio}" required>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="edit_moneda">Moneda *</label>
                                    <select id="edit_moneda" required onchange="ServiciosUI.actualizarMonedaLabel()">
                                        <option value="PEN" ${servicio.moneda === 'PEN' ? 'selected' : ''}>PEN (Soles)</option>
                                        <option value="USD" ${servicio.moneda === 'USD' ? 'selected' : ''}>USD (Dólares)</option>
                                    </select>
                                </div>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                <div class="form-group">
                                    <label for="edit_periodo">Periodo de Facturación *</label>
                                    <select id="edit_periodo" required>
                                        <option value="mensual" ${servicio.periodo_facturacion === 'mensual' ? 'selected' : ''}>Mensual</option>
                                        <option value="trimestral" ${servicio.periodo_facturacion === 'trimestral' ? 'selected' : ''}>Trimestral</option>
                                        <option value="semestral" ${servicio.periodo_facturacion === 'semestral' ? 'selected' : ''}>Semestral</option>
                                        <option value="anual" ${servicio.periodo_facturacion === 'anual' ? 'selected' : ''}>Anual</option>
                                    </select>
                                    <small class="text-muted">⚠️ Cambiar el periodo no afecta la fecha de vencimiento actual</small>
                                </div>

                                <div class="form-group">
                                    <label for="edit_fecha_vencimiento">Fecha de Vencimiento *</label>
                                    <input type="date" id="edit_fecha_vencimiento"
                                           value="${servicio.fecha_vencimiento}" required>
                                    <small class="text-muted">Puedes ajustar la fecha manualmente</small>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="edit_notas">Notas</label>
                                <textarea id="edit_notas" rows="3" placeholder="Notas adicionales...">${servicio.notas || ''}</textarea>
                            </div>

                            <div style="background: #fff3cd; padding: 15px; border-radius: 8px; border-left: 4px solid #ffc107; margin-top: 20px;">
                                <strong>⚠️ Importante:</strong>
                                <ul style="margin: 10px 0 0 20px; padding: 0;">
                                    <li>Los cambios se aplicarán inmediatamente</li>
                                    <li>El historial de pagos se mantiene intacto</li>
                                    <li>La próxima renovación usará el nuevo periodo</li>
                                </ul>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" onclick="ServiciosUI.cerrarModalEdicion()">Cancelar</button>
                        <button class="btn btn-primary" onclick="ServiciosUI.guardarEdicionServicio()">
                            💾 Guardar Cambios
                        </button>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHTML);
    },

    /**
     * Actualizar label de moneda en edición
     */
    actualizarMonedaLabel() {
        const moneda = document.getElementById('edit_moneda').value;
        const label = document.getElementById('edit_moneda_label');
        label.textContent = moneda === 'USD' ? '$' : 'S/';
    },

    /**
     * Guardar edición de servicio
     */
    async guardarEdicionServicio() {
        const form = document.getElementById('formEditarServicio');

        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const datos = {
            contrato_id: parseInt(document.getElementById('edit_contrato_id').value),
            precio: parseFloat(document.getElementById('edit_precio').value),
            moneda: document.getElementById('edit_moneda').value,
            periodo_facturacion: document.getElementById('edit_periodo').value,
            fecha_vencimiento: document.getElementById('edit_fecha_vencimiento').value,
            notas: document.getElementById('edit_notas').value || null
        };

        if (!confirm('¿Confirmar cambios en el servicio?')) {
            return;
        }

        try {
            const response = await fetch(API_SERVICIOS_BASE, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(datos)
            });

            const resultado = await response.json();

            if (resultado.success) {
                mostrarExito('Servicio actualizado exitosamente');
                this.cerrarModalEdicion();

                // Cerrar modal de detalle si existe
                this.cerrarModalDetalle();

                // Cerrar y reabrir modal de servicios del cliente
                const modalServicios = document.getElementById('modalServiciosCliente');
                if (modalServicios) {
                    const clienteId = modalServicios.dataset.clienteId;
                    modalServicios.remove();

                    setTimeout(() => {
                        this.mostrarServiciosCliente(clienteId);
                    }, 300);
                }
            } else {
                throw new Error(resultado.error || 'Error al actualizar servicio');
            }

        } catch (error) {
            console.error('Error:', error);
            mostrarError('Error al guardar cambios: ' + error.message);
        }
    },

    /**
     * Cerrar modal de edición
     */
    cerrarModalEdicion() {
        const modal = document.getElementById('modalEditarServicio');
        if (modal) modal.remove();
    },

    formatearFechaHora(fechaHora) {
        if (!fechaHora) return 'N/A';
        const d = new Date(fechaHora);
        return d.toLocaleString('es-PE', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    },

    /**
     * Enviar orden de pago para un servicio
     */
    async enviarOrdenPago(contratoId) {
        try {
            if (!confirm('¿Desea enviar la orden de pago para este servicio?')) {
                return;
            }

            const response = await fetch(API_ENVIOS_MULTISERVICIO_BASE, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'enviar_orden_servicio',
                    contrato_id: contratoId
                })
            });

            const data = await response.json();

            if (data.success) {
                mostrarExito('Orden de pago enviada exitosamente');
            } else {
                throw new Error(data.error || 'Error al enviar orden');
            }
        } catch (error) {
            console.error('Error al enviar orden:', error);
            mostrarError('Error al enviar orden de pago');
        }
    },

    /**
     * Agregar nuevo servicio a cliente
     */
    async agregarServicio(clienteId) {
        try {
            // Cargar catálogo de servicios activos
            const catalogo = await ServiciosAPI.obtenerCatalogo({ activos: 1 });

            if (!catalogo || catalogo.length === 0) {
                mostrarError('No hay servicios disponibles en el catálogo');
                return;
            }

            // Mostrar modal de contratación
            this.mostrarModalContratacion(clienteId, catalogo);
        } catch (error) {
            console.error('Error al cargar catálogo:', error);
            mostrarError('Error al cargar catálogo de servicios');
        }
    },

    /**
     * Mostrar modal de contratación de servicio
     */
    mostrarModalContratacion(clienteId, catalogo) {
        // Agrupar servicios por categoría
        const categorias = {};
        catalogo.forEach(servicio => {
            if (!categorias[servicio.categoria]) {
                categorias[servicio.categoria] = [];
            }
            categorias[servicio.categoria].push(servicio);
        });

        // Generar opciones de servicios agrupadas por categoría
        let opcionesHTML = '<option value="">Seleccione un servicio</option>';
        for (const [categoria, servicios] of Object.entries(categorias)) {
            opcionesHTML += `<optgroup label="${this.capitalize(categoria)}">`;
            servicios.forEach(servicio => {
                // Asegurar que periodos_disponibles sea un array
                let periodos = servicio.periodos_disponibles;
                if (typeof periodos === 'string') {
                    try {
                        periodos = JSON.parse(periodos);
                    } catch (e) {
                        console.error('Error parsing periodos:', e);
                        periodos = [];
                    }
                }
                if (!Array.isArray(periodos)) {
                    periodos = [];
                }

                const periodosTexto = periodos.join(', ');
                const periodosJSON = JSON.stringify(periodos);

                opcionesHTML += `
                    <option value="${servicio.id}"
                            data-precio="${servicio.precio_base}"
                            data-moneda="${servicio.moneda}"
                            data-periodos='${periodosJSON}'>
                        ${servicio.nombre} - ${servicio.moneda} ${parseFloat(servicio.precio_base).toFixed(2)} (${periodosTexto})
                    </option>
                `;
            });
            opcionesHTML += '</optgroup>';
        }

        const modalHTML = `
            <div class="modal-overlay" id="modalContratarServicio">
                <div class="modal-content modal-lg">
                    <div class="modal-header">
                        <h3>Contratar Nuevo Servicio</h3>
                        <button class="close-btn" onclick="ServiciosUI.cerrarModalContratacion()">×</button>
                    </div>
                    <div class="modal-body">
                        <form id="formContratarServicio" onsubmit="event.preventDefault(); ServiciosUI.procesarContratacion(${clienteId});">
                            <div class="form-group">
                                <label for="servicioSelect">Servicio *</label>
                                <select id="servicioSelect" required onchange="ServiciosUI.actualizarFormularioServicio()">
                                    ${opcionesHTML}
                                </select>
                                <small class="text-muted">Seleccione el servicio que desea contratar</small>
                            </div>

                            <div class="form-group" id="periodoGroup" style="display: none;">
                                <label for="periodoSelect">Periodo de Facturación *</label>
                                <select id="periodoSelect" required onchange="ServiciosUI.calcularFechaVencimiento()">
                                    <option value="">Seleccione periodo</option>
                                </select>
                            </div>

                            <div class="form-group" id="precioGroup" style="display: none;">
                                <label for="precioInput">Precio</label>
                                <div class="input-group">
                                    <span id="monedaLabel" style="padding: 10px; background: #e9ecef; border: 1px solid #ced4da; border-radius: 5px 0 0 5px;">S/</span>
                                    <input type="number" id="precioInput" step="0.01" min="0" placeholder="0.00"
                                           style="border-radius: 0 5px 5px 0;" onchange="ServiciosUI.marcarPrecioPersonalizado()">
                                </div>
                                <small class="text-muted">Precio base del servicio (puede personalizarlo)</small>
                            </div>

                            <div class="form-group" id="fechaInicioGroup" style="display: none;">
                                <label for="fechaInicioInput">Fecha de Inicio *</label>
                                <input type="date" id="fechaInicioInput" required onchange="ServiciosUI.calcularFechaVencimiento()">
                                <small class="text-muted">Fecha en que inicia el servicio</small>
                            </div>

                            <div class="form-group" id="fechaVencimientoGroup" style="display: none;">
                                <label for="fechaVencimientoInput">Fecha de Vencimiento</label>
                                <input type="date" id="fechaVencimientoInput" readonly style="background: #f8f9fa;">
                                <small class="text-muted">Calculada automáticamente según el periodo</small>
                            </div>

                            <div class="form-group" id="notasGroup" style="display: none;">
                                <label for="notasInput">Notas (opcional)</label>
                                <textarea id="notasInput" rows="3" placeholder="Notas adicionales sobre este servicio..."></textarea>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" onclick="ServiciosUI.cerrarModalContratacion()">Cancelar</button>
                                <button type="submit" class="btn btn-primary" id="btnContratarServicio" disabled>
                                    Contratar Servicio
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHTML);

        // Configurar fecha de inicio por defecto (hoy)
        const hoy = new Date().toISOString().split('T')[0];
        document.getElementById('fechaInicioInput').value = hoy;
    },

    /**
     * Actualizar formulario cuando se selecciona un servicio
     */
    actualizarFormularioServicio() {
        const select = document.getElementById('servicioSelect');
        const option = select.options[select.selectedIndex];

        if (!option.value) {
            // Ocultar todos los campos
            document.getElementById('periodoGroup').style.display = 'none';
            document.getElementById('precioGroup').style.display = 'none';
            document.getElementById('fechaInicioGroup').style.display = 'none';
            document.getElementById('fechaVencimientoGroup').style.display = 'none';
            document.getElementById('notasGroup').style.display = 'none';
            document.getElementById('btnContratarServicio').disabled = true;
            return;
        }

        // Obtener datos del servicio
        const precio = option.dataset.precio;
        const moneda = option.dataset.moneda;
        const periodos = JSON.parse(option.dataset.periodos || '[]');

        // Actualizar precio
        document.getElementById('monedaLabel').textContent = moneda === 'USD' ? '$' : 'S/';
        document.getElementById('precioInput').value = parseFloat(precio).toFixed(2);
        document.getElementById('precioInput').dataset.precioBase = precio;
        document.getElementById('precioInput').dataset.moneda = moneda;

        // Actualizar periodos disponibles
        const periodoSelect = document.getElementById('periodoSelect');
        periodoSelect.innerHTML = '<option value="">Seleccione periodo</option>';

        periodos.forEach(periodo => {
            const option = document.createElement('option');
            option.value = periodo;
            option.textContent = this.capitalize(periodo);
            periodoSelect.appendChild(option);
        });

        // Mostrar todos los campos
        document.getElementById('periodoGroup').style.display = 'block';
        document.getElementById('precioGroup').style.display = 'block';
        document.getElementById('fechaInicioGroup').style.display = 'block';
        document.getElementById('fechaVencimientoGroup').style.display = 'block';
        document.getElementById('notasGroup').style.display = 'block';
    },

    /**
     * Calcular fecha de vencimiento según periodo
     */
    calcularFechaVencimiento() {
        const fechaInicio = document.getElementById('fechaInicioInput').value;
        const periodo = document.getElementById('periodoSelect').value;

        if (!fechaInicio || !periodo) {
            return;
        }

        const fecha = new Date(fechaInicio + 'T00:00:00');

        switch (periodo) {
            case 'mensual':
                fecha.setMonth(fecha.getMonth() + 1);
                break;
            case 'trimestral':
                fecha.setMonth(fecha.getMonth() + 3);
                break;
            case 'semestral':
                fecha.setMonth(fecha.getMonth() + 6);
                break;
            case 'anual':
                fecha.setFullYear(fecha.getFullYear() + 1);
                break;
        }

        const fechaVencimiento = fecha.toISOString().split('T')[0];
        document.getElementById('fechaVencimientoInput').value = fechaVencimiento;

        // Habilitar botón de contratar
        document.getElementById('btnContratarServicio').disabled = false;
    },

    /**
     * Marcar precio como personalizado
     */
    marcarPrecioPersonalizado() {
        const input = document.getElementById('precioInput');
        const precioBase = parseFloat(input.dataset.precioBase);
        const precioActual = parseFloat(input.value);

        if (precioActual !== precioBase) {
            input.style.borderColor = '#f39325';
            input.style.borderWidth = '2px';
        } else {
            input.style.borderColor = '';
            input.style.borderWidth = '';
        }
    },

    /**
     * Procesar contratación de servicio
     */
    async procesarContratacion(clienteId) {
        try {
            const servicioSelect = document.getElementById('servicioSelect');
            const servicioId = servicioSelect.value;
            const periodo = document.getElementById('periodoSelect').value;
            const precio = parseFloat(document.getElementById('precioInput').value);
            const moneda = document.getElementById('precioInput').dataset.moneda;
            const fechaInicio = document.getElementById('fechaInicioInput').value;
            const fechaVencimiento = document.getElementById('fechaVencimientoInput').value;
            const notas = document.getElementById('notasInput').value;
            const precioBase = parseFloat(document.getElementById('precioInput').dataset.precioBase);

            // Validar datos
            if (!servicioId || !periodo || !precio || !fechaInicio || !fechaVencimiento) {
                mostrarError('Por favor complete todos los campos requeridos');
                return;
            }

            // Deshabilitar botón
            const btnContratar = document.getElementById('btnContratarServicio');
            btnContratar.disabled = true;
            btnContratar.textContent = 'Contratando...';

            // Preparar datos
            const datos = {
                cliente_id: clienteId,
                servicio_id: servicioId,
                periodo_facturacion: periodo,
                precio: precio,  // Cambio: API espera 'precio' no 'precio_acordado'
                moneda: moneda,
                fecha_inicio: fechaInicio,
                fecha_vencimiento: fechaVencimiento,
                notas: notas || null,
                precio_personalizado: precio !== precioBase ? 1 : 0
            };

            // Enviar al API
            const resultado = await ServiciosAPI.contratarServicio(datos);

            if (resultado) {
                // Cerrar modal
                this.cerrarModalContratacion();

                // Recargar servicios del cliente
                await this.mostrarServiciosCliente(clienteId);

                mostrarExito('Servicio contratado exitosamente');
            }
        } catch (error) {
            console.error('Error al contratar servicio:', error);
            mostrarError('Error al contratar servicio');

            // Rehabilitar botón
            const btnContratar = document.getElementById('btnContratarServicio');
            btnContratar.disabled = false;
            btnContratar.textContent = 'Contratar Servicio';
        }
    },

    /**
     * Cerrar modal de contratación
     */
    cerrarModalContratacion() {
        const modal = document.getElementById('modalContratarServicio');
        if (modal) {
            modal.remove();
        }
    },

    /**
     * Suspender servicio
     */
    async suspenderServicio(contratoId) {
        try {
            // Mostrar modal para ingresar motivo
            const motivo = await this.mostrarModalMotivo('Suspender Servicio', 'Indique el motivo de la suspensión:');

            if (!motivo) {
                return; // Usuario canceló
            }

            // Confirmar acción
            if (!confirm('¿Está seguro que desea suspender este servicio?\n\nEl servicio dejará de estar activo pero no se eliminará.')) {
                return;
            }

            const resultado = await ServiciosAPI.suspenderServicio(contratoId, motivo);

            if (resultado) {
                mostrarExito('Servicio suspendido exitosamente');

                // Recargar servicios del cliente
                const tarjeta = document.querySelector(`[data-contrato-id="${contratoId}"]`);
                if (tarjeta) {
                    const modal = document.getElementById('modalServiciosCliente');
                    if (modal) {
                        const clienteId = modal.dataset.clienteId;
                        if (clienteId) {
                            this.cerrarModal();
                            await this.mostrarServiciosCliente(clienteId);
                        }
                    }
                }
            }
        } catch (error) {
            console.error('Error al suspender servicio:', error);
        }
    },

    /**
     * Reactivar servicio
     */
    async reactivarServicio(contratoId) {
        try {
            // Preguntar si desea extender la fecha de vencimiento
            const extender = confirm('¿Desea extender la fecha de vencimiento del servicio?\n\n• SÍ: Se agregará un periodo adicional\n• NO: Se mantendrá la fecha original');

            let nuevaFecha = null;
            if (extender) {
                // Mostrar modal para seleccionar nueva fecha
                nuevaFecha = await this.mostrarModalFecha('Seleccione la nueva fecha de vencimiento:');
                if (!nuevaFecha) {
                    return; // Usuario canceló
                }
            }

            const resultado = await ServiciosAPI.reactivarServicio(contratoId, nuevaFecha);

            if (resultado) {
                mostrarExito('Servicio reactivado exitosamente');

                // Recargar servicios
                const tarjeta = document.querySelector(`[data-contrato-id="${contratoId}"]`);
                if (tarjeta) {
                    const modal = document.getElementById('modalServiciosCliente');
                    if (modal) {
                        const clienteId = modal.dataset.clienteId;
                        if (clienteId) {
                            this.cerrarModal();
                            await this.mostrarServiciosCliente(clienteId);
                        }
                    }
                }
            }
        } catch (error) {
            console.error('Error al reactivar servicio:', error);
        }
    },

    /**
     * Confirmar cancelación de servicio
     */
    async confirmarCancelacion(contratoId) {
        try {
            // Advertencia
            const confirmar = confirm('⚠️ ADVERTENCIA: CANCELACIÓN DEFINITIVA\n\n' +
                'Esta acción cancelará el servicio de forma PERMANENTE.\n\n' +
                '• El servicio no podrá reactivarse\n' +
                '• Se mantendrá en el historial\n' +
                '• No se podrán enviar más órdenes de pago\n\n' +
                '¿Está seguro que desea continuar?');

            if (!confirmar) {
                return;
            }

            // Mostrar modal para ingresar motivo
            const motivo = await this.mostrarModalMotivo('Cancelar Servicio', 'Indique el motivo de la cancelación:');

            if (!motivo) {
                return; // Usuario canceló
            }

            const resultado = await ServiciosAPI.cancelarServicio(contratoId, motivo);

            if (resultado) {
                mostrarExito('Servicio cancelado exitosamente');

                // Recargar servicios
                const tarjeta = document.querySelector(`[data-contrato-id="${contratoId}"]`);
                if (tarjeta) {
                    const modal = document.getElementById('modalServiciosCliente');
                    if (modal) {
                        const clienteId = modal.dataset.clienteId;
                        if (clienteId) {
                            this.cerrarModal();
                            await this.mostrarServiciosCliente(clienteId);
                        }
                    }
                }
            }
        } catch (error) {
            console.error('Error al cancelar servicio:', error);
        }
    },

    /**
     * Mostrar modal para ingresar motivo
     */
    mostrarModalMotivo(titulo, mensaje) {
        return new Promise((resolve) => {
            const modalHTML = `
                <div class="modal-overlay" id="modalMotivo">
                    <div class="modal-content" style="max-width: 500px;">
                        <div class="modal-header">
                            <h3>${titulo}</h3>
                            <button class="close-btn" onclick="document.getElementById('modalMotivo').remove(); window.modalMotivoResolve(null);">×</button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label>${mensaje}</label>
                                <textarea id="motivoInput" rows="4" placeholder="Escriba el motivo..." required style="width: 100%; padding: 10px; border: 1px solid #ced4da; border-radius: 6px;"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-secondary" onclick="document.getElementById('modalMotivo').remove(); window.modalMotivoResolve(null);">
                                Cancelar
                            </button>
                            <button class="btn btn-primary" onclick="
                                const motivo = document.getElementById('motivoInput').value.trim();
                                if (!motivo) {
                                    alert('Por favor ingrese un motivo');
                                    return;
                                }
                                document.getElementById('modalMotivo').remove();
                                window.modalMotivoResolve(motivo);
                            ">
                                Confirmar
                            </button>
                        </div>
                    </div>
                </div>
            `;

            document.body.insertAdjacentHTML('beforeend', modalHTML);

            // Guardar resolve en window para acceder desde onclick
            window.modalMotivoResolve = resolve;

            // Focus en textarea
            setTimeout(() => {
                document.getElementById('motivoInput').focus();
            }, 100);
        });
    },

    /**
     * Mostrar modal para seleccionar fecha
     */
    mostrarModalFecha(mensaje) {
        return new Promise((resolve) => {
            const hoy = new Date().toISOString().split('T')[0];

            const modalHTML = `
                <div class="modal-overlay" id="modalFecha">
                    <div class="modal-content" style="max-width: 400px;">
                        <div class="modal-header">
                            <h3>Nueva Fecha de Vencimiento</h3>
                            <button class="close-btn" onclick="document.getElementById('modalFecha').remove(); window.modalFechaResolve(null);">×</button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label>${mensaje}</label>
                                <input type="date" id="fechaInput" min="${hoy}" required style="width: 100%; padding: 10px; border: 1px solid #ced4da; border-radius: 6px;">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-secondary" onclick="document.getElementById('modalFecha').remove(); window.modalFechaResolve(null);">
                                Cancelar
                            </button>
                            <button class="btn btn-primary" onclick="
                                const fecha = document.getElementById('fechaInput').value;
                                if (!fecha) {
                                    alert('Por favor seleccione una fecha');
                                    return;
                                }
                                document.getElementById('modalFecha').remove();
                                window.modalFechaResolve(fecha);
                            ">
                                Confirmar
                            </button>
                        </div>
                    </div>
                </div>
            `;

            document.body.insertAdjacentHTML('beforeend', modalHTML);

            // Guardar resolve en window
            window.modalFechaResolve = resolve;
        });
    },

    /**
     * Cerrar modal
     */
    cerrarModal() {
        const modal = document.getElementById('modalServiciosCliente');
        if (modal) {
            modal.remove();
        }
    },

    // Utilidades
    obtenerClaseEstado(estado) {
        const clases = {
            'activo': 'badge-success',
            'AL_DIA': 'badge-success',
            'vencido': 'badge-danger',
            'VENCIDO': 'badge-danger',
            'suspendido': 'badge-warning',
            'POR_VENCER': 'badge-warning',
            'VENCE_HOY': 'badge-danger',
            'cancelado': 'badge-secondary',
            'INACTIVO': 'badge-secondary'
        };
        return clases[estado] || 'badge-secondary';
    },

    obtenerTextoEstado(estado) {
        const textos = {
            'activo': 'Activo',
            'AL_DIA': 'Al Día',
            'vencido': 'Vencido',
            'VENCIDO': 'Vencido',
            'suspendido': 'Suspendido',
            'POR_VENCER': 'Por Vencer',
            'VENCE_HOY': 'Vence Hoy',
            'cancelado': 'Cancelado',
            'INACTIVO': 'Inactivo'
        };
        return textos[estado] || estado;
    },

    formatearFecha(fecha) {
        if (!fecha) return '-';
        const d = new Date(fecha + 'T00:00:00');
        return d.toLocaleDateString('es-PE');
    },

    capitalize(str) {
        if (!str) return '';
        return str.charAt(0).toUpperCase() + str.slice(1);
    }
};

// ============================================
// FUNCIONES AUXILIARES DE UI
// ============================================

function mostrarExito(mensaje) {
    alert('✅ ' + mensaje);
}

function mostrarError(mensaje) {
    alert('❌ ' + mensaje);
}

function mostrarInfo(mensaje) {
    alert('ℹ️ ' + mensaje);
}

// ============================================
// MÓDULO DE PAGOS MULTI-SERVICIO
// ============================================

const PagosMultiServicio = {
    /**
     * Abrir modal de registro de pago para múltiples servicios
     */
    async abrirModalPago(clienteId, serviciosPreseleccionados = []) {
        try {
            // Obtener servicios del cliente
            const resultado = await ServiciosAPI.obtenerServiciosCliente(clienteId);

            if (!resultado.servicios || resultado.servicios.length === 0) {
                mostrarInfo('El cliente no tiene servicios contratados');
                return;
            }

            // Filtrar servicios pagables (activo o vencido)
            const serviciosPagables = resultado.servicios.filter(s =>
                s.estado === 'activo' || s.estado === 'vencido'
            );

            if (serviciosPagables.length === 0) {
                mostrarInfo('Este cliente no tiene servicios que requieran pago');
                return;
            }

            // Obtener datos del cliente
            const cliente = await this._obtenerCliente(clienteId);

            // Renderizar modal
            this._renderizarModal(cliente, serviciosPagables, serviciosPreseleccionados);

        } catch (error) {
            console.error('Error al abrir modal de pago:', error);
            mostrarError('Error al cargar información: ' + error.message);
        }
    },

    async _obtenerCliente(clienteId) {
        const response = await fetch(`${API_CLIENTES_BASE}?action=get&id=${clienteId}`);
        const result = await response.json();

        if (!result.success) {
            throw new Error('Cliente no encontrado');
        }

        return result.data;
    },

    _renderizarModal(cliente, servicios, serviciosPreseleccionados = []) {
        const modalHtml = `
            <div class="modal-overlay" id="modalPagoMultiServicio" style="z-index: 10000;">
                <div class="modal-content modal-lg">
                    <div class="modal-header">
                        <h3>💰 Registrar Pago - ${cliente.razon_social}</h3>
                        <button class="close-btn" onclick="PagosMultiServicio.cerrarModal()">&times;</button>
                    </div>
                    <div class="modal-body">
                        <form id="formPagoMultiServicio">
                            <input type="hidden" name="cliente_id" value="${cliente.id}">

                            <!-- Lista de Servicios -->
                            <div class="form-group">
                                <label>
                                    <strong>Seleccionar Servicios a Pagar:</strong>
                                    <small style="color: #666; display: block; margin-top: 5px;">
                                        Marca los servicios que se cubrirán con este pago
                                    </small>
                                </label>
                                <div class="servicios-pago-container">
                                    ${this._renderizarServicios(servicios, serviciosPreseleccionados)}
                                </div>
                            </div>

                            <!-- Resumen -->
                            <div id="resumenPagoMulti" style="display: none;"></div>

                            <!-- Datos del Pago -->
                            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
                                <div class="form-group">
                                    <label for="monto_pagado_multi">Monto Pagado *</label>
                                    <div class="input-group">
                                        <span>S/</span>
                                        <input type="number" id="monto_pagado_multi" name="monto_pagado"
                                               step="0.01" min="0.01" required placeholder="0.00">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="fecha_pago_multi">Fecha de Pago *</label>
                                    <input type="date" id="fecha_pago_multi" name="fecha_pago"
                                           value="${new Date().toISOString().split('T')[0]}" required>
                                </div>
                            </div>

                            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
                                <div class="form-group">
                                    <label for="metodo_pago_multi">Método de Pago *</label>
                                    <select id="metodo_pago_multi" name="metodo_pago" required>
                                        <option value="">Seleccionar...</option>
                                        <option value="transferencia">Transferencia</option>
                                        <option value="deposito">Depósito</option>
                                        <option value="yape">Yape</option>
                                        <option value="plin">Plin</option>
                                        <option value="efectivo">Efectivo</option>
                                        <option value="otro">Otro</option>
                                    </select>
                                </div>

                                <div class="form-group" id="grupoBanco">
                                    <label for="banco_multi" id="labelBanco">Banco</label>
                                    <select id="banco_multi" name="banco">
                                        <option value="">Seleccionar banco...</option>
                                        <option value="BCP">BCP - Banco de Crédito del Perú</option>
                                        <option value="BBVA">BBVA</option>
                                        <option value="Interbank">Interbank</option>
                                        <option value="Scotiabank">Scotiabank</option>
                                        <option value="Banco de la Nación">Banco de la Nación</option>
                                        <option value="Banco Pichincha">Banco Pichincha</option>
                                        <option value="Banbif">Banbif</option>
                                        <option value="Otro">Otro</option>
                                    </select>
                                    <small class="text-muted" id="ayudaBanco"></small>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="numero_operacion_multi" id="labelNumeroOperacion">Número de Operación</label>
                                <input type="text" id="numero_operacion_multi" name="numero_operacion"
                                       placeholder="Número de referencia (opcional)">
                                <small class="text-muted" id="ayudaNumeroOperacion"></small>
                            </div>

                            <div class="form-group">
                                <label for="observaciones_multi">Observaciones</label>
                                <textarea id="observaciones_multi" name="observaciones" rows="3"
                                          placeholder="Notas adicionales..."></textarea>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="PagosMultiServicio.cerrarModal()">
                            Cancelar
                        </button>
                        <button type="button" class="btn btn-primary" onclick="PagosMultiServicio.procesarPago()" id="btnProcesarPago">
                            💰 Registrar Pago
                        </button>
                    </div>
                </div>
            </div>
        `;

        // Insertar modal
        const modalExistente = document.getElementById('modalPagoMultiServicio');
        if (modalExistente) modalExistente.remove();

        document.body.insertAdjacentHTML('beforeend', modalHtml);

        // Event listeners
        this._configurarEventos();

        // Si hay servicios preseleccionados, actualizar resumen
        if (serviciosPreseleccionados.length > 0) {
            this._actualizarResumen();
        }
    },

    _renderizarServicios(servicios, serviciosPreseleccionados = []) {
        return servicios.map(s => {
            const contratoId = s.contrato_id || s.id;
            const isChecked = serviciosPreseleccionados.includes(contratoId);

            return `
                <label class="servicio-pago-checkbox">
                    <input type="checkbox" name="servicios[]" value="${contratoId}"
                           data-precio="${s.precio}" data-nombre="${s.servicio_nombre || s.nombre}"
                           data-periodo="${s.periodo_facturacion}" data-moneda="${s.moneda}"
                           ${isChecked ? 'checked' : ''}>
                    <div class="servicio-pago-info">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <strong>${s.servicio_nombre || s.nombre}</strong>
                            ${this._getBadge(s.estado_vencimiento || s.estado)}
                        </div>
                        <div style="font-size: 13px; color: #666; margin-top: 5px;">
                            <span>${s.moneda} ${parseFloat(s.precio).toFixed(2)}</span> •
                            <span style="text-transform: capitalize;">${s.periodo_facturacion}</span> •
                            <span>Vence: ${this._formatFecha(s.fecha_vencimiento)}</span>
                        </div>
                    </div>
                </label>
            `;
        }).join('');
    },

    _configurarEventos() {
        // Actualizar resumen al seleccionar servicios
        document.querySelectorAll('input[name="servicios[]"]').forEach(cb => {
            cb.addEventListener('change', () => this._actualizarResumen());
        });

        // Mostrar/ocultar campo banco según método de pago
        const metodoPago = document.getElementById('metodo_pago_multi');
        const grupoBanco = document.getElementById('grupoBanco');
        const bancoSelect = document.getElementById('banco_multi');

        metodoPago.addEventListener('change', (e) => {
            const metodo = e.target.value;
            const labelNumOp = document.getElementById('labelNumeroOperacion');
            const ayudaNumOp = document.getElementById('ayudaNumeroOperacion');
            const inputNumOp = document.getElementById('numero_operacion_multi');
            const labelBanco = document.getElementById('labelBanco');
            const ayudaBanco = document.getElementById('ayudaBanco');

            // Configurar según método de pago
            if (metodo === 'transferencia' || metodo === 'deposito') {
                // Transferencia / Depósito
                grupoBanco.style.display = 'block';
                labelBanco.textContent = 'Banco *';
                ayudaBanco.textContent = 'Banco donde se realizó la operación';
                bancoSelect.setAttribute('required', 'required');
                bancoSelect.value = '';
                labelNumOp.textContent = 'Número de Operación *';
                inputNumOp.setAttribute('required', 'required');
                ayudaNumOp.textContent = 'Código de la transacción bancaria';

            } else if (metodo === 'yape') {
                // Yape - BCP por defecto
                grupoBanco.style.display = 'block';
                labelBanco.textContent = 'Banco';
                ayudaBanco.textContent = 'Yape por defecto usa BCP (puedes cambiarlo)';
                bancoSelect.removeAttribute('required');
                bancoSelect.value = 'BCP';
                labelNumOp.textContent = 'Número de Operación Yape';
                inputNumOp.removeAttribute('required');
                ayudaNumOp.textContent = 'Código de confirmación de Yape (opcional)';

            } else if (metodo === 'plin') {
                // Plin - Interbank por defecto
                grupoBanco.style.display = 'block';
                labelBanco.textContent = 'Banco';
                ayudaBanco.textContent = 'Plin por defecto usa Interbank (puedes cambiarlo)';
                bancoSelect.removeAttribute('required');
                bancoSelect.value = 'Interbank';
                labelNumOp.textContent = 'Número de Operación Plin';
                inputNumOp.removeAttribute('required');
                ayudaNumOp.textContent = 'Código de confirmación de Plin (opcional)';

            } else if (metodo === 'efectivo') {
                // Efectivo - No requiere banco
                grupoBanco.style.display = 'none';
                bancoSelect.value = '';
                labelNumOp.textContent = 'Recibo / Referencia';
                inputNumOp.removeAttribute('required');
                ayudaNumOp.textContent = 'Número de recibo si aplica (opcional)';

            } else {
                // Otro
                grupoBanco.style.display = 'none';
                bancoSelect.value = '';
                labelNumOp.textContent = 'Número de Operación';
                inputNumOp.removeAttribute('required');
                ayudaNumOp.textContent = '';
            }
        });

        // Ocultar inicialmente el campo banco
        grupoBanco.style.display = 'none';
    },

    _actualizarResumen() {
        const checkboxes = document.querySelectorAll('input[name="servicios[]"]:checked');
        const resumenDiv = document.getElementById('resumenPagoMulti');

        if (checkboxes.length === 0) {
            resumenDiv.style.display = 'none';
            return;
        }

        let totalPEN = 0;
        let items = [];

        checkboxes.forEach(cb => {
            const precio = parseFloat(cb.dataset.precio);
            const nombre = cb.dataset.nombre;
            const periodo = cb.dataset.periodo;
            const moneda = cb.dataset.moneda;

            if (moneda === 'PEN') totalPEN += precio;

            items.push({ nombre, precio, periodo, moneda });
        });

        const html = `
            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 20px 0;">
                <h4 style="margin-top: 0; color: #2581c4;">📊 Resumen del Pago</h4>
                <table style="width: 100%; font-size: 14px;">
                    <thead>
                        <tr style="border-bottom: 2px solid #dee2e6;">
                            <th style="text-align: left; padding: 8px;">Servicio</th>
                            <th style="text-align: center; padding: 8px;">Periodo</th>
                            <th style="text-align: right; padding: 8px;">Monto</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${items.map(i => `
                            <tr>
                                <td style="padding: 8px;">${i.nombre}</td>
                                <td style="text-align: center; padding: 8px; text-transform: capitalize;">${i.periodo}</td>
                                <td style="text-align: right; padding: 8px; font-weight: 600;">${i.moneda} ${i.precio.toFixed(2)}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                    <tfoot>
                        <tr style="border-top: 2px solid #dee2e6; font-weight: 700;">
                            <td colspan="2" style="padding: 12px 8px;">TOTAL</td>
                            <td style="text-align: right; padding: 12px 8px; color: #2581c4; font-size: 16px;">
                                S/ ${totalPEN.toFixed(2)}
                            </td>
                        </tr>
                    </tfoot>
                </table>
                <div style="background: #fff3cd; padding: 10px; border-radius: 6px; border-left: 4px solid #ffc107; margin-top: 10px;">
                    <strong>⚠️ Importante:</strong> Se renovarán ${checkboxes.length} servicio(s) automáticamente
                </div>
            </div>
        `;

        resumenDiv.innerHTML = html;
        resumenDiv.style.display = 'block';

        // Sugerir monto
        document.getElementById('monto_pagado_multi').value = totalPEN.toFixed(2);
    },

    async procesarPago() {
        const form = document.getElementById('formPagoMultiServicio');

        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        // Validar servicios seleccionados
        const checkboxes = document.querySelectorAll('input[name="servicios[]"]:checked');
        if (checkboxes.length === 0) {
            mostrarError('Debes seleccionar al menos un servicio');
            return;
        }

        const serviciosPagados = Array.from(checkboxes).map(cb => parseInt(cb.value));

        const datos = {
            cliente_id: parseInt(form.cliente_id.value),
            servicios_pagados: serviciosPagados,
            monto_pagado: parseFloat(document.getElementById('monto_pagado_multi').value),
            fecha_pago: document.getElementById('fecha_pago_multi').value,
            metodo_pago: document.getElementById('metodo_pago_multi').value,
            numero_operacion: document.getElementById('numero_operacion_multi').value || null,
            banco: document.getElementById('banco_multi').value || null,
            observaciones: document.getElementById('observaciones_multi').value || null
        };

        if (!confirm(`¿Confirmar pago?\n\n• Monto: S/ ${datos.monto_pagado.toFixed(2)}\n• Servicios: ${serviciosPagados.length}\n\nSe renovarán las fechas de vencimiento.`)) {
            return;
        }

        try {
            const btn = document.getElementById('btnProcesarPago');
            btn.disabled = true;
            btn.innerHTML = '<span class="loading-spinner"></span> Procesando...';

            const response = await fetch(`${API_CLIENTES_BASE}?action=registrar_pago`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(datos)
            });

            const resultado = await response.json();

            if (resultado.success) {
                mostrarExito(`Pago registrado. ${resultado.data.servicios_actualizados.length} servicio(s) renovado(s)`);
                this.cerrarModal();

                // Cerrar modal de servicios si existe para forzar recarga
                const modalServiciosCliente = document.getElementById('modalServiciosCliente');
                if (modalServiciosCliente) {
                    modalServiciosCliente.remove();
                }

                // Reabrir modal de servicios actualizado
                setTimeout(() => {
                    if (typeof ServiciosUI !== 'undefined' && ServiciosUI.mostrarServiciosCliente) {
                        ServiciosUI.mostrarServiciosCliente(datos.cliente_id);
                    }
                }, 300);

                // Recargar tabla de clientes si existe
                if (typeof cargarClientes === 'function') {
                    cargarClientes();
                }
            } else {
                throw new Error(resultado.error || 'Error al registrar pago');
            }

        } catch (error) {
            console.error('Error:', error);
            mostrarError('Error al registrar pago: ' + error.message);

            const btn = document.getElementById('btnProcesarPago');
            btn.disabled = false;
            btn.innerHTML = '💰 Registrar Pago';
        }
    },

    cerrarModal() {
        const modal = document.getElementById('modalPagoMultiServicio');
        if (modal) modal.remove();
    },

    _getBadge(estado) {
        const badges = {
            'AL_DIA': '<span class="badge badge-success">Al Día</span>',
            'POR_VENCER': '<span class="badge badge-warning">Por Vencer</span>',
            'VENCE_HOY': '<span class="badge badge-warning">Vence Hoy</span>',
            'VENCIDO': '<span class="badge badge-danger">Vencido</span>',
            'activo': '<span class="badge badge-success">Activo</span>',
            'vencido': '<span class="badge badge-danger">Vencido</span>'
        };
        return badges[estado] || '';
    },

    _formatFecha(fecha) {
        if (!fecha) return 'N/A';
        const d = new Date(fecha + 'T00:00:00');
        return d.toLocaleDateString('es-PE', { year: 'numeric', month: 'short', day: 'numeric' });
    }
};

// Exponer globalmente
window.PagosMultiServicio = PagosMultiServicio;
