// ============================================
// Gestión de Pagos
// ============================================

document.addEventListener('DOMContentLoaded', () => {
    cargarPagos();
    cargarPrestamos();
});

async function cargarPagos() {
    try {
        UI.showLoading();
        
        const filters = {};
        
        if (document.getElementById('filtroPrestamo').value) {
            filters.numero_prestamo = document.getElementById('filtroPrestamo').value;
        }
        
        if (document.getElementById('filtroFechaDesde').value) {
            filters.fecha_desde = document.getElementById('filtroFechaDesde').value;
        }
        
        if (document.getElementById('filtroFechaHasta').value) {
            filters.fecha_hasta = document.getElementById('filtroFechaHasta').value;
        }
        
        const response = await api.get('/pagos', filters);
        
        if (response.success) {
            mostrarPagos(response.data.items || response.data);
        }
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al cargar pagos: ' + error.message, 'danger');
    }
}

function mostrarPagos(pagos) {
    const tbody = document.getElementById('tbodyPagos');
    
    const columns = [
        { field: 'numero_recibo' },
        { field: 'numero_prestamo', render: (item) => item.numero_prestamo || '-' },
        { 
            render: (item) => {
                const nombre = item.cliente_nombre || '';
                const apellido = item.cliente_apellido || '';
                return `${nombre} ${apellido}`.trim() || '-';
            }
        },
        { render: (item) => UI.formatCurrency(item.monto) },
        { field: 'metodo_pago' },
        { render: (item) => UI.formatDate(item.fecha_pago) },
        {
            render: (item) => {
                return `
                    <div class="btn-group">
                        <button class="btn btn-sm btn-primary" onclick="imprimirRecibo(${item.id})" title="Imprimir Recibo">📄</button>
                        <button class="btn btn-sm btn-success" onclick="enviarNotificacionPago(${item.id})" title="Enviar Notificación">📱</button>
                    </div>
                `;
            },
            sanitize: false
        }
    ];
    
    renderSafeTable(tbody, pagos, columns, 'No hay pagos');
}

function filtrarPagos() {
    cargarPagos();
}

async function cargarPrestamos() {
    try {
        const response = await api.get('/prestamos?estado=vigente&per_page=1000');
        if (response.success) {
            const select = document.getElementById('prestamoId');
            select.innerHTML = '<option value="">Seleccionar préstamo...</option>' +
                response.data.items.map(prestamo => 
                    `<option value="${prestamo.id}">${prestamo.numero_prestamo} - ${prestamo.cliente_nombre} ${prestamo.cliente_apellido}</option>`
                ).join('');
        }
    } catch (error) {
        console.error('Error cargando préstamos:', error);
    }
}

async function cargarCuotas() {
    const prestamoId = document.getElementById('prestamoId').value;
    
    if (!prestamoId) {
        document.getElementById('cuotaId').innerHTML = '<option value="">Todas las cuotas</option>';
        return;
    }
    
    try {
        const response = await api.get(`/prestamos/${prestamoId}/cuotas`);
        if (response.success) {
            const select = document.getElementById('cuotaId');
            select.innerHTML = '<option value="">Todas las cuotas</option>' +
                response.data.map(cuota => 
                    `<option value="${cuota.id}">Cuota ${cuota.numero_cuota} - ${UI.formatCurrency(cuota.monto_cuota)}</option>`
                ).join('');
        }
    } catch (error) {
        console.error('Error cargando cuotas:', error);
    }
}

function abrirModalCrear() {
    document.getElementById('modalCrearPago').classList.add('show');
}

function cerrarModalCrear() {
    document.getElementById('modalCrearPago').classList.remove('show');
    document.getElementById('formCrearPago').reset();
}

async function registrarPago() {
    const form = document.getElementById('formCrearPago');
    
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    try {
        UI.showLoading();
        
        const data = {
            prestamo_id: document.getElementById('prestamoId').value,
            cuota_id: document.getElementById('cuotaId').value || null,
            monto: document.getElementById('monto').value,
            metodo_pago: document.getElementById('metodoPago').value,
            numero_comprobante: document.getElementById('numeroComprobante').value || null
        };
        
        const response = await api.post('/pagos', data);
        
        if (response.success) {
            UI.showAlert('Pago registrado correctamente', 'success');
            cerrarModalCrear();
            cargarPagos();
        }
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al registrar pago: ' + error.message, 'danger');
    }
}

async function imprimirRecibo(pagoId) {
    try {
        const response = await api.get(`/recibos?pago_id=${pagoId}`);
        if (response.success && response.data.length > 0) {
            const reciboId = response.data[0].id;
            window.open(`/api/recibos/imprimir?id=${reciboId}`, '_blank');
        } else {
            UI.showAlert('Recibo no encontrado', 'warning');
        }
    } catch (error) {
        UI.showAlert('Error al imprimir recibo: ' + error.message, 'danger');
    }
}

async function enviarNotificacionPago(pagoId) {
    if (!confirm('¿Enviar notificación de pago por WhatsApp y Email?')) return;
    
    try {
        UI.showLoading('Enviando notificaciones...');
        const response = await api.post('/whatsapp/notificacion-pago', { pago_id: pagoId });
        
        if (response.success !== false) {
            UI.showAlert('Notificaciones enviadas exitosamente', 'success');
        } else {
            UI.showAlert(response.message || 'Error al enviar notificaciones', 'danger');
        }
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        console.error('Error:', error);
        UI.showAlert('Error al enviar notificaciones: ' + error.message, 'danger');
    }
}

// La función logout() está definida globalmente en app.js

// Funciones de exportación
async function exportarPagosPDF() {
    try {
        UI.showLoading('Generando reporte PDF...');
        
        const filters = obtenerFiltros();
        const filename = `pagos_${new Date().toISOString().split('T')[0]}.pdf`;
        
        await api.downloadFile('/reportes/exportar-pdf', {
            tipo: 'cobros',
            ...filters
        }, filename);
        
        UI.showAlert('Reporte PDF generado exitosamente', 'success');
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al exportar PDF: ' + error.message, 'danger');
    }
}

async function exportarPagosExcel() {
    try {
        UI.showLoading('Generando reporte Excel...');
        
        const filters = obtenerFiltros();
        const filename = `pagos_${new Date().toISOString().split('T')[0]}.xlsx`;
        
        await api.downloadFile('/reportes/exportar-excel', {
            tipo: 'cobros',
            ...filters
        }, filename);
        
        UI.showAlert('Reporte Excel generado exitosamente', 'success');
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al exportar Excel: ' + error.message, 'danger');
    }
}

async function exportarPagosCSV() {
    try {
        UI.showLoading('Generando reporte CSV...');
        
        const filters = obtenerFiltros();
        const filename = `pagos_${new Date().toISOString().split('T')[0]}.csv`;
        
        await api.downloadFile('/exportacion/pagos', filters, filename);
        
        UI.showAlert('Reporte CSV generado exitosamente', 'success');
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al exportar CSV: ' + error.message, 'danger');
    }
}

function obtenerFiltros() {
    const filters = {};
    
    if (document.getElementById('filtroPrestamo').value) {
        filters.numero_prestamo = document.getElementById('filtroPrestamo').value;
    }
    
    if (document.getElementById('filtroFechaDesde').value) {
        filters.fecha_desde = document.getElementById('filtroFechaDesde').value;
    }
    
    if (document.getElementById('filtroFechaHasta').value) {
        filters.fecha_hasta = document.getElementById('filtroFechaHasta').value;
    }
    
    return filters;
}


