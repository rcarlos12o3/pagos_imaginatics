/**
 * MÓDULO DE ENVÍOS INTELIGENTE
 * Sistema que analiza y determina qué empresas deben recibir órdenes
 * según reglas de negocio y periodicidad
 */

const ModuloEnvios = {
    serviciosPendientes: [],
    serviciosSeleccionados: [],

    /**
     * Inicializar módulo de envíos
     */
    async init() {
        console.log('🤖 Inicializando Módulo de Envíos Inteligente...');
        await this.analizarEnvios();
    },

    /**
     * Analizar envíos pendientes desde la API
     */
    async analizarEnvios() {
        try {
            // Mostrar loader
            document.getElementById('loader-analisis').style.display = 'block';
            document.getElementById('lista-deben-enviarse').style.display = 'none';
            document.getElementById('sin-envios-pendientes').style.display = 'none';
            document.getElementById('resumen-analisis').style.display = 'none';

            console.log('📊 Solicitando análisis de envíos a la API...');

            const response = await fetch(API_CLIENTES_BASE + '?action=analizar_envios_pendientes');
            const data = await response.json();

            if (!data.success) {
                throw new Error(data.error || 'Error en el análisis');
            }

            console.log('✅ Análisis recibido:', data);

            this.serviciosPendientes = data.data.servicios;
            this.renderizarAnalisis();

        } catch (error) {
            console.error('❌ Error en análisis:', error);
            document.getElementById('loader-analisis').innerHTML = `
                <div style="text-align: center; padding: 40px;">
                    <div style="font-size: 48px;">❌</div>
                    <p style="color: #e74c3c; margin-top: 10px;">Error al analizar envíos</p>
                    <p style="color: #666;">${error.message}</p>
                    <button class="btn btn-primary" onclick="ModuloEnvios.analizarEnvios()">
                        🔄 Reintentar
                    </button>
                </div>
            `;
        }
    },

    /**
     * Renderizar resultados del análisis
     */
    renderizarAnalisis() {
        document.getElementById('loader-analisis').style.display = 'none';

        if (this.serviciosPendientes.length === 0) {
            // No hay envíos pendientes
            document.getElementById('sin-envios-pendientes').style.display = 'block';
            document.getElementById('resumen-analisis').style.display = 'none';
            return;
        }

        // Mostrar resumen
        document.getElementById('resumen-analisis').style.display = 'block';
        document.getElementById('total-deben-enviarse').textContent = this.serviciosPendientes.length;
        document.getElementById('total-pendientes').textContent = '0'; // TODO: calcular
        document.getElementById('total-enviados').textContent = '0'; // TODO: calcular

        // Renderizar lista de empresas
        this.renderizarListaEmpresas();
    },

    /**
     * Renderizar lista de empresas que deben recibir órdenes
     */
    renderizarListaEmpresas() {
        const container = document.getElementById('empresas-deben-enviarse');
        container.innerHTML = '';

        this.serviciosPendientes.forEach((servicio, index) => {
            const card = this.crearTarjetaServicio(servicio, index);
            container.appendChild(card);
        });

        document.getElementById('lista-deben-enviarse').style.display = 'block';
    },

    /**
     * Crear tarjeta de servicio estilo Apple
     */
    crearTarjetaServicio(servicio, index) {
        const card = document.createElement('div');
        card.className = 'servicio-card';
        card.style.cssText = `
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        `;

        // Badge de estado
        const estadoBadge = servicio.estado === 'dentro_del_plazo_ideal'
            ? '<span style="background: #34c759; color: white; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600;">✓ DENTRO DEL PLAZO</span>'
            : '<span style="background: #ff3b30; color: white; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600;">⚠ FUERA DEL PLAZO</span>';

        card.innerHTML = `
            <div style="display: flex; align-items: start; gap: 15px;">
                <!-- Checkbox -->
                <input type="checkbox"
                       id="check-servicio-${index}"
                       data-index="${index}"
                       onchange="ModuloEnvios.toggleSeleccion(${index})"
                       style="width: 20px; height: 20px; cursor: pointer; margin-top: 5px;">

                <!-- Contenido -->
                <div style="flex: 1;">
                    <!-- Header con empresa y estado -->
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <div>
                            <h4 style="margin: 0; font-size: 18px; color: #1d1d1f; font-weight: 600;">
                                ${servicio.empresa}
                            </h4>
                            <p style="margin: 4px 0 0 0; color: #86868b; font-size: 14px;">
                                ${servicio.servicio_nombre} • ${servicio.periodicidad.toUpperCase()}
                            </p>
                        </div>
                        ${estadoBadge}
                    </div>

                    <!-- Información en grid -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; margin-top: 15px;">
                        <div style="background: #f5f5f7; padding: 12px; border-radius: 8px;">
                            <div style="font-size: 12px; color: #86868b; margin-bottom: 4px;">💰 Monto</div>
                            <div style="font-size: 18px; font-weight: 600; color: #1d1d1f;">
                                ${servicio.moneda} ${parseFloat(servicio.precio).toFixed(2)}
                            </div>
                        </div>

                        <div style="background: #f5f5f7; padding: 12px; border-radius: 8px;">
                            <div style="font-size: 12px; color: #86868b; margin-bottom: 4px;">📅 Vencimiento</div>
                            <div style="font-size: 16px; font-weight: 600; color: #1d1d1f;">
                                ${servicio.fecha_vencimiento_periodo_actual}
                            </div>
                            <div style="font-size: 12px; color: #86868b; margin-top: 2px;">
                                ${servicio.dias_hasta_vencer < 0 ?
                                    `Venció hace ${Math.abs(servicio.dias_hasta_vencer)} días` :
                                    `Vence en ${servicio.dias_hasta_vencer} días`}
                            </div>
                        </div>

                        <div style="background: #f5f5f7; padding: 12px; border-radius: 8px;">
                            <div style="font-size: 12px; color: #86868b; margin-bottom: 4px;">📤 Fecha ideal envío</div>
                            <div style="font-size: 16px; font-weight: 600; color: #1d1d1f;">
                                ${servicio.fecha_ideal_envio}
                            </div>
                            <div style="font-size: 12px; color: #86868b; margin-top: 2px;">
                                ${servicio.dias_anticipacion} días antes
                            </div>
                        </div>
                    </div>

                    <!-- Explicación expandible -->
                    <details style="margin-top: 15px;">
                        <summary style="cursor: pointer; color: #007aff; font-size: 14px; font-weight: 500;">
                            Ver detalles y explicación
                        </summary>
                        <div style="margin-top: 10px; padding: 12px; background: #f9f9f9; border-radius: 8px; font-size: 13px; line-height: 1.6;">
                            <p style="margin: 0 0 8px 0;"><strong>Regla:</strong> ${servicio.explicacion.regla}</p>
                            <p style="margin: 0 0 4px 0;"><strong>RUC:</strong> ${servicio.ruc}</p>
                            <p style="margin: 0 0 4px 0;"><strong>WhatsApp:</strong> ${servicio.whatsapp}</p>
                            <p style="margin: 0 0 4px 0;"><strong>Fecha inicio contrato:</strong> ${servicio.fecha_inicio}</p>
                            <p style="margin: 0;"><strong>Siguiente vencimiento:</strong> ${servicio.siguiente_vencimiento}</p>
                        </div>
                    </details>
                </div>
            </div>
        `;

        // Hover effect
        card.addEventListener('mouseenter', () => {
            card.style.boxShadow = '0 4px 16px rgba(0,0,0,0.1)';
            card.style.transform = 'translateY(-2px)';
        });
        card.addEventListener('mouseleave', () => {
            card.style.boxShadow = '0 2px 8px rgba(0,0,0,0.05)';
            card.style.transform = 'translateY(0)';
        });

        return card;
    },

    /**
     * Toggle selección de un servicio
     */
    toggleSeleccion(index) {
        const checkbox = document.getElementById(`check-servicio-${index}`);
        const servicio = this.serviciosPendientes[index];

        if (checkbox.checked) {
            if (!this.serviciosSeleccionados.includes(servicio.contrato_id)) {
                this.serviciosSeleccionados.push(servicio.contrato_id);
            }
        } else {
            this.serviciosSeleccionados = this.serviciosSeleccionados.filter(id => id !== servicio.contrato_id);
        }

        this.actualizarBotonEnviar();
    },

    /**
     * Seleccionar todas las empresas
     */
    seleccionarTodas() {
        this.serviciosPendientes.forEach((servicio, index) => {
            const checkbox = document.getElementById(`check-servicio-${index}`);
            if (checkbox) {
                checkbox.checked = true;
                if (!this.serviciosSeleccionados.includes(servicio.contrato_id)) {
                    this.serviciosSeleccionados.push(servicio.contrato_id);
                }
            }
        });
        this.actualizarBotonEnviar();
    },

    /**
     * Deseleccionar todas las empresas
     */
    deseleccionarTodas() {
        this.serviciosPendientes.forEach((servicio, index) => {
            const checkbox = document.getElementById(`check-servicio-${index}`);
            if (checkbox) {
                checkbox.checked = false;
            }
        });
        this.serviciosSeleccionados = [];
        this.actualizarBotonEnviar();
    },

    /**
     * Actualizar estado del botón de enviar
     */
    actualizarBotonEnviar() {
        const btn = document.getElementById('btn-enviar-seleccionadas');
        if (this.serviciosSeleccionados.length > 0) {
            btn.disabled = false;
            btn.textContent = `📤 Enviar ${this.serviciosSeleccionados.length} Orden${this.serviciosSeleccionados.length > 1 ? 'es' : ''} Seleccionada${this.serviciosSeleccionados.length > 1 ? 's' : ''}`;
        } else {
            btn.disabled = true;
            btn.textContent = '📤 Enviar Órdenes Seleccionadas';
        }
    },

    /**
     * Enviar órdenes seleccionadas (REAL - conectado con sistema de cola)
     */
    async enviarSeleccionadas() {
        if (this.serviciosSeleccionados.length === 0) {
            alert('⚠️ Por favor selecciona al menos una empresa');
            return;
        }

        // Confirmación
        const serviciosAEnviar = this.serviciosPendientes.filter(s =>
            this.serviciosSeleccionados.includes(s.contrato_id)
        );

        const nombres = serviciosAEnviar.map(s => s.empresa).join('\n• ');
        const montoTotal = serviciosAEnviar.reduce((sum, s) => sum + parseFloat(s.precio), 0);

        if (!confirm(`📤 ¿Enviar órdenes de pago a las siguientes empresas?\n\n• ${nombres}\n\n✅ Total: ${serviciosAEnviar.length} empresa${serviciosAEnviar.length > 1 ? 's' : ''}\n💰 Monto total: S/ ${montoTotal.toFixed(2)}\n\n⚠️ Los envíos se procesarán automáticamente en segundo plano.`)) {
            return;
        }

        try {
            // Mostrar progress
            document.getElementById('progress-envio').style.display = 'block';
            const progressBar = document.getElementById('progressBar');
            const progressText = document.getElementById('progressText');

            progressBar.style.width = '10%';
            progressText.textContent = 'Preparando envíos...';

            console.log('📦 Preparando envío a cola...');

            // Generar imágenes para todos los servicios
            const clientesConImagenes = [];
            for (let i = 0; i < serviciosAEnviar.length; i++) {
                const servicio = serviciosAEnviar[i];

                progressText.textContent = `Generando imagen ${i + 1} de ${serviciosAEnviar.length}: ${servicio.empresa}...`;
                progressBar.style.width = (10 + ((i / serviciosAEnviar.length) * 40)) + '%';

                console.log(`  📸 Generando imagen para: ${servicio.empresa}`);

                // Convertir fecha de dd/mm/yyyy a yyyy-mm-dd para el canvas
                const fechaVencimientoISO = this.convertirFechaAISO(servicio.fecha_vencimiento_periodo_actual);

                // Crear objeto cliente compatible con generarCanvasOrdenPago
                const clienteData = {
                    id: servicio.cliente_id,
                    ruc: servicio.ruc,
                    razonSocial: servicio.empresa,
                    whatsapp: servicio.whatsapp,
                    monto: servicio.precio,
                    fecha: fechaVencimientoISO,
                    fecha_vencimiento: fechaVencimientoISO,
                    tipo_servicio: servicio.periodicidad
                };

                // Generar canvas con imagen
                const canvas = await generarCanvasOrdenPago(clienteData);
                const imagenBase64 = canvasToBase64(canvas);

                clientesConImagenes.push({
                    id: servicio.cliente_id,
                    contrato_id: servicio.contrato_id,
                    ruc: servicio.ruc,
                    razon_social: servicio.empresa,
                    whatsapp: servicio.whatsapp,
                    monto: servicio.precio,
                    fecha_vencimiento: fechaVencimientoISO, // Usar fecha ya convertida
                    tipo_servicio: servicio.periodicidad,
                    imagen_base64: imagenBase64,
                    dias_restantes: servicio.dias_hasta_vencer
                });
            }

            progressText.textContent = 'Enviando trabajos a la cola...';
            progressBar.style.width = '60%';

            // Crear sesión en la cola
            console.log('🚀 Creando sesión en cola...');
            const response = await fetch(API_ENVIOS_BASE, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'crear_sesion_cola',
                    tipo_envio: 'orden_pago',
                    clientes: clientesConImagenes,
                    configuracion: {
                        creado_desde: 'modulo_envios_inteligente',
                        timestamp: new Date().toISOString(),
                        servicios_ids: serviciosAEnviar.map(s => s.contrato_id)
                    }
                })
            });

            const data = await response.json();

            progressBar.style.width = '100%';

            if (data.success) {
                const sesionId = data.data.sesion_id;
                const trabajos = data.data.trabajos_agregados;

                console.log(`✅ Sesión creada: #${sesionId} con ${trabajos} trabajos`);

                progressText.textContent = '✅ Trabajos agregados a la cola';

                alert(`✅ ¡Envío agregado a la cola exitosamente!

📦 Sesión: #${sesionId}
📋 Trabajos: ${trabajos}

Los envíos serán procesados automáticamente en segundo plano.
Puedes cerrar el navegador sin preocupación.

Ve al historial de envíos para monitorear el progreso.`);

                // Redirigir al historial después de 2 segundos
                setTimeout(() => {
                    window.location.href = 'historial_envios.php?sesion=' + sesionId;
                }, 2000);

            } else {
                throw new Error(data.error || 'Error desconocido al crear sesión');
            }

        } catch (error) {
            console.error('❌ Error enviando:', error);
            alert('❌ Error al enviar órdenes: ' + error.message);

            // Ocultar progress en caso de error
            document.getElementById('progress-envio').style.display = 'none';
        }
    },

    /**
     * Sleep helper
     */
    sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    },

    /**
     * Convertir fecha de formato dd/mm/yyyy a yyyy-mm-dd
     */
    convertirFechaAISO(fechaDDMMYYYY) {
        // Formato entrada: "13/11/2025"
        // Formato salida: "2025-11-13"
        const partes = fechaDDMMYYYY.split('/');
        if (partes.length === 3) {
            const [dia, mes, año] = partes;
            return `${año}-${mes.padStart(2, '0')}-${dia.padStart(2, '0')}`;
        }
        return fechaDDMMYYYY; // Devolver original si no se puede convertir
    }
};

// Auto-inicializar cuando se navega a la página
window.addEventListener('load', () => {
    console.log('🚀 Módulo de Envíos cargado');
});
