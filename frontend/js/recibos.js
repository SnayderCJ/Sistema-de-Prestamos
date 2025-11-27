// Gestión de Recibos

let recibos = [];
let reciboActual = null;
let paginaActual = 1;
const porPagina = 20;

document.addEventListener('DOMContentLoaded', () => {
    cargarRecibos();
    cargarPrestamos();
    cargarUsuario();
});

function cargarUsuario() {
    const user = auth.getCurrentUser();
    if (user) {
        document.getElementById('userName').textContent = `${user.nombre} ${user.apellido}`;
    }
}

async function cargarPrestamos() {
    try {
        const response = await api.get('/prestamos', { page: 1, per_page: 100 });
        
        if (response.success) {
            const prestamosList = response.data.items || response.data || [];
            const select = document.getElementById('filtroPrestamo');
            
            select.innerHTML = '<option value="">Todos los préstamos</option>' +
                prestamosList.map(p => 
                    `<option value="${p.id}">${p.numero_prestamo} - ${p.cliente_nombre || ''} ${p.cliente_apellido || ''}</option>`
                ).join('');
        }
    } catch (error) {
        console.error('Error cargando préstamos:', error);
    }
}

async function cargarRecibos() {
    try {
        UI.showLoading('Cargando recibos...');
        
        const filtros = {};
        const prestamoId = document.getElementById('filtroPrestamo').value;
        if (prestamoId) filtros.prestamo_id = prestamoId;
        
        const fechaDesde = document.getElementById('filtroFechaDesde').value;
        if (fechaDesde) filtros.fecha_desde = fechaDesde;
        
        const fechaHasta = document.getElementById('filtroFechaHasta').value;
        if (fechaHasta) filtros.fecha_hasta = fechaHasta;
        
        const response = await api.get('/recibos', {
            page: paginaActual,
            per_page: porPagina,
            ...filtros
        });
        
        if (response.success) {
            recibos = response.data.items || response.data || [];
            mostrarRecibos(recibos);
            
            if (response.data.pagination) {
                mostrarPaginacion(response.data.pagination);
            }
        }
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al cargar recibos: ' + error.message, 'danger');
    }
}

function mostrarRecibos(lista) {
    const tbody = document.querySelector('#tablaRecibos tbody');
    
    if (!lista || lista.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center">No hay recibos registrados</td></tr>';
        return;
    }
    
    tbody.innerHTML = lista.map(recibo => `
        <tr>
            <td>${recibo.numero_recibo || recibo.numero || '-'}</td>
            <td>${formatDate(recibo.fecha_pago || recibo.fecha)}</td>
            <td>${recibo.numero_prestamo || '-'}</td>
            <td>${recibo.cliente_nombre || ''} ${recibo.cliente_apellido || ''}</td>
            <td>${formatCurrency(recibo.monto || recibo.monto_total || 0)}</td>
            <td>${recibo.metodo_pago || '-'}</td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="verDetalleRecibo(${recibo.id})">Ver</button>
                <button class="btn btn-sm btn-info" onclick="imprimirReciboDesdeLista(${recibo.id})">Imprimir</button>
                <button class="btn btn-sm btn-secondary" onclick="descargarReciboPDFDesdeLista(${recibo.id})">PDF</button>
            </td>
        </tr>
    `).join('');
}

function mostrarPaginacion(pagination) {
    const paginacion = document.getElementById('paginacionRecibos');
    if (!paginacion || !pagination) return;
    
    let html = '';
    
    if (pagination.has_prev) {
        html += `<button class="btn btn-sm" onclick="cambiarPagina(${pagination.page - 1})">Anterior</button>`;
    }
    
    html += `<span class="pagination-info">Página ${pagination.page} de ${pagination.total_pages}</span>`;
    
    if (pagination.has_next) {
        html += `<button class="btn btn-sm" onclick="cambiarPagina(${pagination.page + 1})">Siguiente</button>`;
    }
    
    paginacion.innerHTML = html;
}

function cambiarPagina(pagina) {
    paginaActual = pagina;
    cargarRecibos();
}

function filtrarRecibos() {
    const busqueda = document.getElementById('buscarRecibo').value.toLowerCase();
    
    if (!busqueda) {
        mostrarRecibos(recibos);
        return;
    }
    
    const filtrados = recibos.filter(r => 
        (r.numero_recibo && r.numero_recibo.toLowerCase().includes(busqueda)) ||
        (r.numero_prestamo && r.numero_prestamo.toLowerCase().includes(busqueda)) ||
        (r.cliente_nombre && r.cliente_nombre.toLowerCase().includes(busqueda)) ||
        (r.cliente_apellido && r.cliente_apellido.toLowerCase().includes(busqueda))
    );
    
    mostrarRecibos(filtrados);
}

async function verDetalleRecibo(id) {
    try {
        UI.showLoading('Cargando recibo...');
        
        const response = await api.get(`/recibos/${id}`);
        
        if (response.success) {
            reciboActual = response.data;
            mostrarDetalleRecibo(reciboActual);
            document.getElementById('modalDetalleRecibo').style.display = 'block';
        }
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al cargar recibo: ' + error.message, 'danger');
    }
}

function mostrarDetalleRecibo(recibo) {
    document.getElementById('reciboNumero').textContent = recibo.numero_recibo || recibo.numero || '-';
    document.getElementById('reciboFecha').textContent = formatDate(recibo.fecha_pago || recibo.fecha);
    document.getElementById('reciboClienteNombre').textContent = `${recibo.cliente_nombre || ''} ${recibo.cliente_apellido || ''}`;
    document.getElementById('reciboClienteCedula').textContent = recibo.cliente_cedula || '-';
    document.getElementById('reciboPrestamoNumero').textContent = recibo.numero_prestamo || '-';
    document.getElementById('reciboCuota').textContent = recibo.numero_cuota || '-';
    document.getElementById('reciboCapital').textContent = formatCurrency(recibo.capital || 0);
    document.getElementById('reciboInteres').textContent = formatCurrency(recibo.interes || 0);
    document.getElementById('reciboMora').textContent = formatCurrency(recibo.mora || 0);
    document.getElementById('reciboTotal').textContent = formatCurrency(recibo.monto || recibo.monto_total || 0);
    document.getElementById('reciboMetodoPago').textContent = recibo.metodo_pago || '-';
    document.getElementById('reciboComprobante').textContent = recibo.numero_comprobante || '-';
}

async function imprimirReciboDesdeLista(id) {
    await verDetalleRecibo(id);
    setTimeout(() => imprimirRecibo(), 500);
}

function imprimirRecibo() {
    if (!reciboActual) return;
    
    const contenido = document.getElementById('contenidoRecibo').innerHTML;
    const ventana = window.open('', '_blank');
    ventana.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Recibo ${reciboActual.numero_recibo || reciboActual.numero}</title>
            <style>
                body { font-family: Arial, sans-serif; padding: 20px; }
                .recibo-header { text-align: center; margin-bottom: 30px; }
                .recibo-section { margin-bottom: 20px; }
                table { width: 100%; border-collapse: collapse; }
                th, td { padding: 8px; border-bottom: 1px solid #ddd; }
            </style>
        </head>
        <body>
            ${contenido}
        </body>
        </html>
    `);
    ventana.document.close();
    ventana.print();
}

async function descargarReciboPDFDesdeLista(id) {
    await verDetalleRecibo(id);
    setTimeout(() => descargarReciboPDF(), 500);
}

async function descargarReciboPDF() {
    if (!reciboActual) return;
    
    try {
        await api.downloadFile(`/recibos/${reciboActual.id}/pdf`, {}, `recibo_${reciboActual.numero_recibo || reciboActual.id}.pdf`);
        UI.showAlert('Recibo descargado correctamente', 'success');
    } catch (error) {
        UI.showAlert('Error al descargar recibo: ' + error.message, 'danger');
    }
}

async function exportarRecibos(formato) {
    try {
        UI.showLoading('Generando exportación...');
        
        const filtros = {};
        const prestamoId = document.getElementById('filtroPrestamo').value;
        if (prestamoId) filtros.prestamo_id = prestamoId;
        
        const fechaDesde = document.getElementById('filtroFechaDesde').value;
        if (fechaDesde) filtros.fecha_desde = fechaDesde;
        
        const fechaHasta = document.getElementById('filtroFechaHasta').value;
        if (fechaHasta) filtros.fecha_hasta = fechaHasta;
        
        await api.downloadFile(`/recibos/exportar-${formato}`, filtros, `recibos_${new Date().toISOString().slice(0,10)}.${formato}`);
        UI.showAlert('Exportación completada', 'success');
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al exportar recibos: ' + error.message, 'danger');
    }
}

function cerrarModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

