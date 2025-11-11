/**
 * Dashboard de Pagos Pendientes
 * Imaginatics Perú SAC
 *
 * Módulo para visualizar servicios próximos a vencer y vencidos
 */

const DashboardPagos = {
    filtroActual: 'todos',
    servicioFiltro: null,
    busqueda: '',

    /**
     * Abrir dashboard
     */
    async abrir() {
        await this.cargarDatos();
    },

    /**
     * Cargar datos del dashboard
     */
    async cargarDatos() {
        try {
            const params = new URLSearchParams({
                action: 'dashboard_pagos',
                filtro: this.filtroActual
            });

            if (this.servicioFiltro) {
                params.append('servicio_id', this.servicioFiltro);
            }

            if (this.busqueda) {
                params.append('busqueda', this.busqueda);
            }

            const response = await fetch(`${API_CLIENTES_BASE}?${params.toString()}`);
            const resultado = await response.json();

            if (!resultado.success) {
                throw new Error(resultado.error || 'Error al cargar dashboard');
            }

            this._renderizarDashboard(resultado.data);

        } catch (error) {
            console.error('Error al cargar dashboard:', error);
            mostrarError('Error al cargar el dashboard de pagos');
        }
    },

    /**
     * Renderizar dashboard completo
     */
    _renderizarDashboard(data) {
        const { metricas, servicios, catalogo } = data;

        const modalHTML = `
            <div class="modal-overlay" id="modalDashboardPagos" style="z-index: 10000;">
                <div class="modal-content modal-lg" style="max-width: 1200px;">
                    <div class="modal-header">
                        <h3>📊 Dashboard de Pagos Pendientes</h3>
                        <button class="close-btn" onclick="DashboardPagos.cerrar()">&times;</button>
                    </div>

                    <div class="modal-body" style="max-height: 85vh; overflow-y: auto;">

                        <!-- Métricas Principales -->
                        <div class="dashboard-metricas">
                            <div class="metrica-card urgente">
                                <div class="metrica-icono">⚠️</div>
                                <div class="metrica-contenido">
                                    <div class="metrica-valor">${metricas.muy_vencidos}</div>
                                    <div class="metrica-label">Muy Vencidos<br>(+30 días)</div>
                                </div>
                            </div>

                            <div class="metrica-card alerta">
                                <div class="metrica-icono">🔴</div>
                                <div class="metrica-contenido">
                                    <div class="metrica-valor">${metricas.vencidos}</div>
                                    <div class="metrica-label">Vencidos</div>
                                </div>
                            </div>

                            <div class="metrica-card advertencia">
                                <div class="metrica-icono">🟡</div>
                                <div class="metrica-contenido">
                                    <div class="metrica-valor">${metricas.proximos_vencer}</div>
                                    <div class="metrica-label">Próximos 7 días</div>
                                </div>
                            </div>

                            <div class="metrica-card info">
                                <div class="metrica-icono">👥</div>
                                <div class="metrica-contenido">
                                    <div class="metrica-valor">${metricas.clientes_afectados}</div>
                                    <div class="metrica-label">Clientes</div>
                                </div>
                            </div>
                        </div>

                        <!-- Montos Pendientes -->
                        <div class="dashboard-montos">
                            <div class="monto-card">
                                <h4>💰 Vencido por Cobrar</h4>
                                <div class="monto-detalle">
                                    <div>
                                        <span class="monto-moneda">PEN</span>
                                        <span class="monto-valor">S/ ${metricas.monto_vencido.PEN.toFixed(2)}</span>
                                    </div>
                                    <div>
                                        <span class="monto-moneda">USD</span>
                                        <span class="monto-valor">$ ${metricas.monto_vencido.USD.toFixed(2)}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="monto-card">
                                <h4>📅 Próximos 7 días</h4>
                                <div class="monto-detalle">
                                    <div>
                                        <span class="monto-moneda">PEN</span>
                                        <span class="monto-valor">S/ ${metricas.monto_proximo.PEN.toFixed(2)}</span>
                                    </div>
                                    <div>
                                        <span class="monto-moneda">USD</span>
                                        <span class="monto-valor">$ ${metricas.monto_proximo.USD.toFixed(2)}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Filtros -->
                        <div class="dashboard-filtros">
                            <div class="filtros-grupo">
                                <button class="filtro-btn ${this.filtroActual === 'todos' ? 'activo' : ''}"
                                        onclick="DashboardPagos.cambiarFiltro('todos')">
                                    📋 Todos
                                </button>
                                <button class="filtro-btn ${this.filtroActual === 'muy_vencido' ? 'activo' : ''}"
                                        onclick="DashboardPagos.cambiarFiltro('muy_vencido')">
                                    ⚠️ Muy Vencidos
                                </button>
                                <button class="filtro-btn ${this.filtroActual === 'vencido' ? 'activo' : ''}"
                                        onclick="DashboardPagos.cambiarFiltro('vencido')">
                                    🔴 Vencidos
                                </button>
                                <button class="filtro-btn ${this.filtroActual === 'proximo_vencer' ? 'activo' : ''}"
                                        onclick="DashboardPagos.cambiarFiltro('proximo_vencer')">
                                    🟡 Próximos a Vencer
                                </button>
                            </div>

                            <div class="filtros-grupo">
                                <select id="filtroServicio" onchange="DashboardPagos.cambiarServicio(this.value)"
                                        style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px;">
                                    <option value="">Todos los servicios</option>
                                    ${catalogo.map(s => `
                                        <option value="${s.id}" ${this.servicioFiltro == s.id ? 'selected' : ''}>
                                            ${s.nombre} (${s.categoria})
                                        </option>
                                    `).join('')}
                                </select>

                                <input type="text" id="busquedaCliente" placeholder="🔍 Buscar cliente..."
                                       value="${this.busqueda}"
                                       onkeyup="DashboardPagos.buscar(this.value)"
                                       style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; min-width: 250px;">
                            </div>
                        </div>

                        <!-- Lista de Servicios -->
                        <div class="dashboard-servicios">
                            ${servicios.length === 0 ? `
                                <div style="text-align: center; padding: 40px; color: #666;">
                                    <p style="font-size: 18px;">✅ No hay servicios pendientes con este filtro</p>
                                </div>
                            ` : servicios.map(s => this._renderizarServicioPendiente(s)).join('')}
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Insertar en el DOM
        document.body.insertAdjacentHTML('beforeend', modalHTML);

        // Cerrar con ESC
        document.addEventListener('keydown', this._handleEscKey);
    },

    /**
     * Renderizar tarjeta de servicio pendiente
     */
    _renderizarServicioPendiente(servicio) {
        const urgenciaClasses = {
            'muy_vencido': 'urgente',
            'vencido': 'alerta',
            'proximo_vencer': 'advertencia',
            'al_dia': 'ok'
        };

        const urgenciaTextos = {
            'muy_vencido': `⚠️ Vencido hace ${Math.abs(servicio.dias_para_vencer)} días`,
            'vencido': `🔴 Vencido hace ${Math.abs(servicio.dias_para_vencer)} días`,
            'proximo_vencer': `🟡 Vence en ${servicio.dias_para_vencer} días`,
            'al_dia': '✅ Al día'
        };

        const urgenciaClass = urgenciaClasses[servicio.urgencia] || 'ok';
        const urgenciaTexto = urgenciaTextos[servicio.urgencia] || '';

        return `
            <div class="servicio-pendiente ${urgenciaClass}">
                <div class="servicio-pendiente-header">
                    <div class="servicio-pendiente-info">
                        <strong>${servicio.razon_social}</strong>
                        <span class="ruc-badge">RUC: ${servicio.ruc}</span>
                    </div>
                    <div class="servicio-pendiente-urgencia">
                        <span class="urgencia-badge ${urgenciaClass}">${urgenciaTexto}</span>
                    </div>
                </div>

                <div class="servicio-pendiente-detalles">
                    <div class="detalle">
                        <span class="label">Servicio:</span>
                        <span class="value">${servicio.servicio_nombre}</span>
                    </div>
                    <div class="detalle">
                        <span class="label">Monto:</span>
                        <span class="value">${servicio.moneda === 'USD' ? '$' : 'S/'} ${parseFloat(servicio.precio).toFixed(2)}</span>
                    </div>
                    <div class="detalle">
                        <span class="label">Periodo:</span>
                        <span class="value">${this._formatearPeriodo(servicio.periodo_facturacion)}</span>
                    </div>
                    <div class="detalle">
                        <span class="label">Vencimiento:</span>
                        <span class="value ${servicio.urgencia === 'vencido' || servicio.urgencia === 'muy_vencido' ? 'text-danger' : ''}">
                            ${this._formatearFecha(servicio.fecha_vencimiento)}
                        </span>
                    </div>
                </div>

                <div class="servicio-pendiente-acciones">
                    <button class="btn-sm btn-primary" onclick="ServiciosUI.verDetalleServicio(${servicio.contrato_id})">
                        📊 Detalle
                    </button>
                    <button class="btn-sm btn-success" onclick="ServiciosUI.enviarOrdenPago(${servicio.contrato_id})">
                        📤 Enviar Orden
                    </button>
                    <button class="btn-sm" onclick="DashboardPagos.registrarPagoRapido(${servicio.cliente_id}, ${servicio.contrato_id})"
                            style="background: #28a745; color: white;">
                        💰 Registrar Pago
                    </button>
                </div>
            </div>
        `;
    },

    /**
     * Cambiar filtro activo
     */
    cambiarFiltro(filtro) {
        this.filtroActual = filtro;
        this.cerrar();
        this.abrir();
    },

    /**
     * Cambiar filtro de servicio
     */
    cambiarServicio(servicioId) {
        this.servicioFiltro = servicioId || null;
        this.cerrar();
        this.abrir();
    },

    /**
     * Buscar cliente
     */
    buscar(texto) {
        clearTimeout(this._busquedaTimeout);
        this._busquedaTimeout = setTimeout(() => {
            this.busqueda = texto.trim();
            this.cerrar();
            this.abrir();
        }, 500);
    },

    /**
     * Registrar pago rápido (abre el modal de pagos con el servicio preseleccionado)
     */
    async registrarPagoRapido(clienteId, contratoId) {
        this.cerrar();
        // Esperar un poco antes de abrir el modal de pagos
        setTimeout(() => {
            PagosMultiServicio.abrirModalPago(clienteId, [contratoId]);
        }, 300);
    },

    /**
     * Cerrar dashboard
     */
    cerrar() {
        const modal = document.getElementById('modalDashboardPagos');
        if (modal) {
            modal.remove();
        }
        document.removeEventListener('keydown', this._handleEscKey);
    },

    /**
     * Manejar tecla ESC
     */
    _handleEscKey(e) {
        if (e.key === 'Escape') {
            DashboardPagos.cerrar();
        }
    },

    /**
     * Formatear periodo
     */
    _formatearPeriodo(periodo) {
        const periodos = {
            'mensual': 'Mensual',
            'trimestral': 'Trimestral',
            'semestral': 'Semestral',
            'anual': 'Anual'
        };
        return periodos[periodo] || periodo;
    },

    /**
     * Formatear fecha
     */
    _formatearFecha(fecha) {
        if (!fecha) return 'N/A';
        const [y, m, d] = fecha.split('-');
        return `${d}/${m}/${y}`;
    }
};
