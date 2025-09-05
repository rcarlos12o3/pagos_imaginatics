// ============================================
// CSV.JS - Manejo de archivos CSV
// Sistema RUC Consultor - Imaginatics Peru SAC
// ============================================
let procesandoCSV = false;
/**
 * Descargar plantilla CSV
 */
function descargarPlantilla() {
    const contenido = 'RUC|RAZON_SOCIAL|MONTO|VENCIMIENTO|NUMERO|TIPO_SERVICIO\n' +
        '20123456789|EMPRESA EJEMPLO SAC|1500.00|15/12/2025|987654321|mensual\n' +
        '20987654321|OTRA EMPRESA EIRL|2500.50|20/12/2025|912345678|trimestral\n' +
        '20555666777|TERCERA EMPRESA SRL|850.00|25/12/2025|998877665|anual';

    const blob = new Blob([contenido], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', 'plantilla_clientes.csv');
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

/**
 * Cargar archivo CSV
 */
function cargarCSV(event) {
    const file = event.target.files[0];
    if (!file) return;

    // AGREGAR ESTA PROTECCIÓN:
    if (procesandoCSV) {
        console.log('Ya se está procesando un archivo CSV, ignorando...');
        return;
    }

    procesandoCSV = true; // Marcar como procesando

    const reader = new FileReader();
    reader.onload = function (e) {
        const contenido = e.target.result;
        console.log('Archivo CSV leído:', file.name);

        try {
            // Procesar contenido del CSV
            procesarContenidoCSV(contenido, file.name);
        } catch (error) {
            console.error('Error procesando CSV:', error);
            alert('Error al procesar el archivo CSV: ' + error.message);
        } finally {
            // LIBERAR EL LOCK AL TERMINAR (exitoso o con error)
            setTimeout(() => {
                procesandoCSV = false;
                // Limpiar el input para permitir cargar el mismo archivo otra vez
                event.target.value = '';
            }, 1000);
        }
    };

    reader.onerror = function (error) {
        console.error('Error leyendo archivo:', error);
        alert('Error al leer el archivo');
        procesandoCSV = false; // Liberar en caso de error
        event.target.value = '';
    };

    reader.readAsText(file, 'UTF-8');
}

/**
 * Procesar el contenido del CSV
 */
function procesarContenidoCSV(contenido, nombreArchivo) {
    console.log('Procesando contenido CSV...');

    // Dividir en líneas
    const lineas = contenido.split('\n').filter(linea => linea.trim() !== '');

    if (lineas.length < 2) {
        alert('El archivo CSV debe tener al menos una línea de encabezados y una de datos');
        return;
    }

    // Obtener encabezados (primera línea)
    const encabezados = lineas[0].split('|').map(h => h.trim());
    console.log('Encabezados encontrados:', encabezados);

    // Validar encabezados esperados
    const encabezadosEsperados = ['RUC', 'RAZON_SOCIAL', 'MONTO', 'VENCIMIENTO', 'NUMERO', 'TIPO_SERVICIO'];
    const encabezadosValidos = encabezadosEsperados.every(h => encabezados.includes(h));

    if (!encabezadosValidos) {
        alert(`El archivo CSV debe tener estos encabezados: ${encabezadosEsperados.join(', ')}\nEncabezados encontrados: ${encabezados.join(', ')}`);
        return;
    }

    // Procesar datos (líneas restantes)
    const clientesCSV = [];
    const errores = [];

    for (let i = 1; i < lineas.length; i++) {
        const linea = lineas[i].trim();
        if (!linea) continue;

        const campos = linea.split('|').map(c => c.trim());

        if (campos.length !== encabezados.length) {
            errores.push(`Línea ${i + 1}: Número incorrecto de campos (esperado: ${encabezados.length}, encontrado: ${campos.length})`);
            continue;
        }

        try {
            const cliente = {};
            encabezados.forEach((encabezado, index) => {
                cliente[encabezado] = campos[index];
            });

            // Validar datos básicos
            const validacion = validarClienteCSV(cliente, i + 1);
            if (validacion.valido) {
                clientesCSV.push(validacion.cliente);
            } else {
                errores.push(`Línea ${i + 1}: ${validacion.error}`);
            }

        } catch (error) {
            errores.push(`Línea ${i + 1}: Error procesando datos - ${error.message}`);
        }
    }

    // Mostrar resultados
    console.log('Clientes válidos encontrados:', clientesCSV.length);
    console.log('Errores encontrados:', errores.length);

    if (errores.length > 0) {
        console.warn('Errores en el CSV:', errores);
        const mostrarErrores = errores.slice(0, 10).join('\n') + (errores.length > 10 ? '\n... y más errores' : '');
        if (!confirm(`Se encontraron ${errores.length} errores en el archivo:\n\n${mostrarErrores}\n\n¿Continuar con los ${clientesCSV.length} clientes válidos?`)) {
            return;
        }
    }

    if (clientesCSV.length === 0) {
        alert('No se encontraron clientes válidos en el archivo CSV');
        return;
    }

    // Confirmar importación
    if (confirm(`¿Importar ${clientesCSV.length} clientes desde ${nombreArchivo}?`)) {
        importarClientesCSV(clientesCSV);
    }
}

/**
 * Validar cada cliente del CSV
 */
function validarClienteCSV(cliente, numeroLinea) {
    const errores = [];

    // Validar RUC
    const rucValidacion = validarRUC(cliente.RUC);
    if (!rucValidacion.valido) {
        errores.push(`RUC inválido: ${rucValidacion.mensaje}`);
    }

    // Validar razón social
    if (!cliente.RAZON_SOCIAL || cliente.RAZON_SOCIAL.length < 3) {
        errores.push('Razón social muy corta o vacía');
    }

    // Validar monto
    const monto = parseFloat(cliente.MONTO.replace(',', '.'));
    if (isNaN(monto) || monto <= 0) {
        errores.push('Monto inválido');
    }

    // Validar fecha
    const fecha = parsearFechaCSV(cliente.VENCIMIENTO);
    if (!fecha) {
        errores.push('Fecha de vencimiento inválida');
    }

    // Validar WhatsApp
    const whatsapp = cliente.NUMERO.replace(/[^0-9]/g, '');
    if (whatsapp.length !== 9) {
        errores.push('Número de WhatsApp debe tener 9 dígitos');
    }

    // AGREGAR VALIDACIÓN DE TIPO_SERVICIO
    const tiposPermitidos = ['mensual', 'trimestral', 'semestral', 'anual'];
    if (!cliente.TIPO_SERVICIO || !tiposPermitidos.includes(cliente.TIPO_SERVICIO.toLowerCase())) {
        errores.push('Tipo de servicio debe ser: mensual, trimestral, semestral o anual');
    }

    if (errores.length > 0) {
        return {
            valido: false,
            error: errores.join(', ')
        };
    }

    return {
        valido: true,
        cliente: {
            ruc: rucValidacion.ruc,
            razon_social: cliente.RAZON_SOCIAL.trim(),
            monto: monto.toFixed(2),
            fecha_vencimiento: fecha,
            whatsapp: whatsapp,
            tipo_servicio: cliente.TIPO_SERVICIO.toLowerCase()
        }
    };
}

/**
 * Parsear fechas del CSV
 */
function parsearFechaCSV(fechaStr) {
    if (!fechaStr) return null;

    // Intentar diferentes formatos: dd/mm/yyyy, dd-mm-yyyy, yyyy-mm-dd
    const formatos = [
        /^(\d{1,2})\/(\d{1,2})\/(\d{4})$/, // dd/mm/yyyy
        /^(\d{1,2})-(\d{1,2})-(\d{4})$/,   // dd-mm-yyyy
        /^(\d{4})-(\d{1,2})-(\d{1,2})$/    // yyyy-mm-dd
    ];

    for (let i = 0; i < formatos.length; i++) {
        const match = fechaStr.match(formatos[i]);
        if (match) {
            let año, mes, dia;

            if (i === 2) { // yyyy-mm-dd
                año = parseInt(match[1]);
                mes = parseInt(match[2]);
                dia = parseInt(match[3]);
            } else { // dd/mm/yyyy o dd-mm-yyyy
                dia = parseInt(match[1]);
                mes = parseInt(match[2]);
                año = parseInt(match[3]);
            }

            // Validar fecha
            const fecha = new Date(año, mes - 1, dia);
            if (fecha.getFullYear() === año && fecha.getMonth() === mes - 1 && fecha.getDate() === dia) {
                return formatearFechaISO(fecha); // Formato yyyy-mm-dd
            }
        }
    }

    return null;
}

/**
 * Importar clientes a la base de datos
 */
async function importarClientesCSV(clientesCSV) {
    console.log('Iniciando importación de', clientesCSV.length, 'clientes...');

    let exitosos = 0;
    let errores = 0;
    let duplicados = 0;

    // Mostrar progreso
    const total = clientesCSV.length;

    for (let i = 0; i < clientesCSV.length; i++) {
        const cliente = clientesCSV[i];

        try {
            const response = await fetch(API_CLIENTES_BASE, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(cliente)
            });

            const data = await response.json();

            if (data.success) {
                exitosos++;
                console.log(`✅ Cliente importado: ${cliente.razon_social}`);
            } else {
                if (data.error && data.error.includes('Ya existe')) {
                    duplicados++;
                    console.warn(`⚠️ Cliente duplicado: ${cliente.razon_social}`);
                } else {
                    errores++;
                    console.error(`❌ Error importando ${cliente.razon_social}:`, data.error);
                }
            }

        } catch (error) {
            errores++;
            console.error(`❌ Error de conexión con ${cliente.razon_social}:`, error);
        }

        // Mostrar progreso cada 10 clientes
        if ((i + 1) % 10 === 0 || i === clientesCSV.length - 1) {
            console.log(`Progreso: ${i + 1}/${total} clientes procesados`);
        }

        // Pausa pequeña para no saturar el servidor
        if (i < clientesCSV.length - 1) {
            await new Promise(resolve => setTimeout(resolve, 100));
        }
    }

    // Mostrar resumen
    const mensaje = `Importación completada:
✅ Exitosos: ${exitosos}
⚠️ Duplicados: ${duplicados}
❌ Errores: ${errores}
📊 Total procesados: ${total}`;

    alert(mensaje);
    console.log(mensaje);

    // Recargar lista de clientes
    if (exitosos > 0) {
        await cargarClientesDesdeDB();
        habilitarBotones();
    }
    // Liberar el lock después de completar la importación
    setTimeout(() => {
        procesandoCSV = false;
    }, 500);
}