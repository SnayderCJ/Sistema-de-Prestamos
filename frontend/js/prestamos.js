// ============================================
// Gestión de Préstamos
// ============================================

let currentPage = 1;
let perPage = 20;

// Cargar préstamos al iniciar
document.addEventListener('DOMContentLoaded', () => {
    cargarPrestamos();
    cargarClientes();
    cargarTasas();
});

async function cargarPrestamos(page = 1) {
    try {
        UI.showLoading();
        
        const filters = {
            page: page,
            per_page: perPage
        };
        
        if (document.getElementById('filtroEstado').value) {
            filters.estado = document.getElementById('filtroEstado').value;
        }
        
        if (document.getElementById('filtroCedula').value) {
            filters.cedula = document.getElementById('filtroCedula').value;
        }
        
        if (document.getElementById('filtroFechaDesde').value) {
            filters.fecha_desde = document.getElementById('filtroFechaDesde').value;
        }
        
        if (document.getElementById('filtroFechaHasta').value) {
            filters.fecha_hasta = document.getElementById('filtroFechaHasta').value;
        }
        
        const response = await prestamosService.getAll(page, filters);
        
        if (response.success) {
            mostrarPrestamos(response.data.items);
            mostrarPaginacion(response.data.pagination);
            currentPage = page;
        }
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al cargar préstamos: ' + error.message, 'danger');
    }
}

function mostrarPrestamos(prestamos) {
    const tbody = document.getElementById('tbodyPrestamos');
    
    if (prestamos.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center">No hay préstamos</td></tr>';
        return;
    }
    
    tbody.innerHTML = prestamos.map(prestamo => `
        <tr onclick="verDetalle(${prestamo.id})">
            <td>${prestamo.numero_prestamo}</td>
            <td>${prestamo.cliente_nombre} ${prestamo.cliente_apellido}</td>
            <td>${UI.formatCurrency(prestamo.monto_aprobado)}</td>
            <td>${UI.formatCurrency(prestamo.cuota_mensual)}</td>
            <td><span class="badge badge-${prestamo.estado}">${prestamo.estado}</span></td>
            <td>${UI.formatDate(prestamo.fecha_creacion)}</td>
            <td>
                <div class="btn-group">
                    <button class="btn btn-sm btn-primary" onclick="event.stopPropagation(); verDetalle(${prestamo.id})" title="Ver Detalle">👁️</button>
                    ${prestamo.estado === 'pendiente' ? `<button class="btn btn-sm btn-success" onclick="event.stopPropagation(); aprobarPrestamo(${prestamo.id})" title="Aprobar">✓</button>` : ''}
                    ${prestamo.estado === 'activo' || prestamo.estado === 'vigente' || prestamo.estado === 'vencido' ? `<button class="btn btn-sm btn-info" onclick="event.stopPropagation(); enviarRecordatorio(${prestamo.id})" title="Enviar Recordatorio">📱</button>` : ''}
                </div>
            </td>
        </tr>
    `).join('');
}

function mostrarPaginacion(pagination) {
    const paginationDiv = document.getElementById('pagination');
    
    if (!pagination || pagination.total_pages <= 1) {
        paginationDiv.innerHTML = '';
        return;
    }
    
    let html = '';
    
    if (pagination.has_prev) {
        html += `<div class="pagination-item" onclick="cargarPrestamos(${pagination.page - 1})">‹</div>`;
    }
    
    for (let i = 1; i <= pagination.total_pages; i++) {
        if (i === pagination.page) {
            html += `<div class="pagination-item active">${i}</div>`;
        } else if (i === 1 || i === pagination.total_pages || (i >= pagination.page - 2 && i <= pagination.page + 2)) {
            html += `<div class="pagination-item" onclick="cargarPrestamos(${i})">${i}</div>`;
        } else if (i === pagination.page - 3 || i === pagination.page + 3) {
            html += `<div class="pagination-item disabled">...</div>`;
        }
    }
    
    if (pagination.has_next) {
        html += `<div class="pagination-item" onclick="cargarPrestamos(${pagination.page + 1})">›</div>`;
    }
    
    paginationDiv.innerHTML = html;
}

function filtrarPrestamos() {
    cargarPrestamos(1);
}

async function cargarClientes() {
    try {
        const response = await api.get('/clientes?per_page=1000');
        if (response.success) {
            const select = document.getElementById('clienteId');
            select.innerHTML = '<option value="">Seleccionar cliente...</option>' +
                response.data.items.map(cliente => 
                    `<option value="${cliente.id}">${cliente.nombre} ${cliente.apellido} - ${cliente.cedula}</option>`
                ).join('');
        }
    } catch (error) {
        console.error('Error cargando clientes:', error);
    }
}

async function cargarTasas() {
    try {
        const response = await api.get('/tasas');
        if (response.success) {
            const select = document.getElementById('tasaInteresId');
            select.innerHTML = '<option value="">Seleccionar tasa...</option>' +
                response.data.map(tasa => 
                    `<option value="${tasa.id}">${tasa.nombre} (${tasa.tasa_mensual}% mensual)</option>`
                ).join('');
        }
    } catch (error) {
        console.error('Error cargando tasas:', error);
    }
}

function abrirModalCrear() {
    document.getElementById('modalCrearPrestamo').classList.add('show');
}

function cerrarModalCrear() {
    document.getElementById('modalCrearPrestamo').classList.remove('show');
    document.getElementById('formCrearPrestamo').reset();
}

async function crearPrestamo() {
    const form = document.getElementById('formCrearPrestamo');
    
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    try {
        UI.showLoading();
        
        const data = {
            cliente_id: document.getElementById('clienteId').value,
            monto_solicitado: document.getElementById('montoSolicitado').value,
            plazo_meses: document.getElementById('plazoMeses').value,
            tasa_interes_id: document.getElementById('tasaInteresId').value,
            garantia_tipo: document.getElementById('garantiaTipo').value,
            observaciones: document.getElementById('observaciones').value
        };
        
        const response = await prestamosService.create(data);
        
        if (response.success) {
            UI.showAlert('Préstamo creado correctamente', 'success');
            cerrarModalCrear();
            cargarPrestamos(currentPage);
        }
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al crear préstamo: ' + error.message, 'danger');
    }
}

async function aprobarPrestamo(id) {
    if (!confirm('¿Está seguro de aprobar este préstamo?')) {
        return;
    }
    
    try {
        UI.showLoading();
        
        const response = await prestamosService.update(id, { estado: 'aprobado' });
        
        if (response.success) {
            UI.showAlert('Préstamo aprobado correctamente', 'success');
            cargarPrestamos(currentPage);
        }
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al aprobar préstamo: ' + error.message, 'danger');
    }
}

async function verDetalle(id) {
    try {
        UI.showLoading('Cargando detalle del préstamo...');
        
        const response = await prestamosService.getById(id);
        
        if (response.success) {
            mostrarDetallePrestamo(response.data);
            document.getElementById('modalDetallePrestamo').classList.add('show');
        }
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al cargar el detalle: ' + error.message, 'danger');
    }
}

function mostrarDetallePrestamo(prestamo) {
    document.getElementById('detalleNumero').textContent = prestamo.numero_prestamo || '-';
    document.getElementById('detalleCliente').textContent = `${prestamo.cliente_nombre || ''} ${prestamo.cliente_apellido || ''}`;
    document.getElementById('detalleCedula').textContent = prestamo.cliente_cedula || '-';
    document.getElementById('detalleMonto').textContent = UI.formatCurrency(prestamo.monto_aprobado || 0);
    document.getElementById('detalleCuota').textContent = UI.formatCurrency(prestamo.cuota_mensual || 0);
    document.getElementById('detallePlazo').textContent = `${prestamo.plazo_meses || 0} meses`;
    document.getElementById('detalleTasa').textContent = `${prestamo.tasa_mensual || 0}%`;
    document.getElementById('detalleEstado').textContent = prestamo.estado || '-';
    document.getElementById('detalleEstado').className = `badge badge-${prestamo.estado || 'pendiente'}`;
    document.getElementById('detalleFechaCreacion').textContent = UI.formatDate(prestamo.fecha_creacion);
    document.getElementById('detalleFechaAprobacion').textContent = prestamo.fecha_aprobacion ? UI.formatDate(prestamo.fecha_aprobacion) : '-';
    document.getElementById('detalleSaldo').textContent = UI.formatCurrency(prestamo.saldo_actual || 0);
    document.getElementById('detalleGarantia').textContent = prestamo.garantia_tipo || '-';
    document.getElementById('detalleObservaciones').textContent = prestamo.observaciones || '-';
}

function cerrarModalDetalle() {
    document.getElementById('modalDetallePrestamo').classList.remove('show');
}

// La función logout() está definida globalmente en app.js

// Funciones de exportación
async function exportarPrestamosPDF() {
    try {
        UI.showLoading('Generando reporte PDF...');
        
        const filters = obtenerFiltros();
        const filename = `prestamos_${new Date().toISOString().split('T')[0]}.pdf`;
        
        await api.downloadFile('/reportes/exportar-pdf', {
            tipo: 'prestamos',
            ...filters
        }, filename);
        
        UI.showAlert('Reporte PDF generado exitosamente', 'success');
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al exportar PDF: ' + error.message, 'danger');
    }
}

async function exportarPrestamosExcel() {
    try {
        UI.showLoading('Generando reporte Excel...');
        
        const filters = obtenerFiltros();
        const filename = `prestamos_${new Date().toISOString().split('T')[0]}.xlsx`;
        
        await api.downloadFile('/reportes/exportar-excel', {
            tipo: 'prestamos',
            ...filters
        }, filename);
        
        UI.showAlert('Reporte Excel generado exitosamente', 'success');
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al exportar Excel: ' + error.message, 'danger');
    }
}

async function exportarPrestamosCSV() {
    try {
        UI.showLoading('Generando reporte CSV...');
        
        const filters = obtenerFiltros();
        const filename = `prestamos_${new Date().toISOString().split('T')[0]}.csv`;
        
        await api.downloadFile('/exportacion/prestamos', filters, filename);
        
        UI.showAlert('Reporte CSV generado exitosamente', 'success');
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al exportar CSV: ' + error.message, 'danger');
    }
}

function obtenerFiltros() {
    const filters = {};
    
    if (document.getElementById('filtroEstado').value) {
        filters.estado = document.getElementById('filtroEstado').value;
    }
    
    if (document.getElementById('filtroCedula').value) {
        filters.cedula = document.getElementById('filtroCedula').value;
    }
    
    if (document.getElementById('filtroFechaDesde').value) {
        filters.fecha_desde = document.getElementById('filtroFechaDesde').value;
    }
    
    if (document.getElementById('filtroFechaHasta').value) {
        filters.fecha_hasta = document.getElementById('filtroFechaHasta').value;
    }
    
    return filters;
}


