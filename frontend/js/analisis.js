// ============================================
// Gestión de Análisis de Crédito
// ============================================

document.addEventListener('DOMContentLoaded', () => {
    cargarAnalisis();
    cargarPrestamos();
});

async function cargarAnalisis() {
    try {
        UI.showLoading();
        
        const filters = {};
        
        if (document.getElementById('filtroPrestamo').value) {
            filters.numero_prestamo = document.getElementById('filtroPrestamo').value;
        }
        
        if (document.getElementById('filtroCliente').value) {
            filters.cliente = document.getElementById('filtroCliente').value;
        }
        
        if (document.getElementById('filtroRecomendacion').value) {
            filters.recomendacion = document.getElementById('filtroRecomendacion').value;
        }
        
        const response = await api.get('/analisis', filters);
        
        if (response.success) {
            mostrarAnalisis(response.data.items || response.data);
        }
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al cargar análisis: ' + error.message, 'danger');
    }
}

function mostrarAnalisis(analisis) {
    const tbody = document.getElementById('tbodyAnalisis');
    if (!tbody) return;
    
    const columns = [
        { field: 'numero_prestamo', render: (item) => item.numero_prestamo || '-' },
        { 
            render: (item) => {
                const nombre = item.cliente_nombre || '';
                const apellido = item.cliente_apellido || '';
                return `${nombre} ${apellido}`.trim() || '-';
            }
        },
        { field: 'score_credito', render: (item) => item.score_credito || '-' },
        { render: (item) => item.capacidad_pago ? UI.formatCurrency(item.capacidad_pago) : '-' },
        { 
            render: (item) => item.ratio_deuda_ingresos 
                ? (item.ratio_deuda_ingresos * 100).toFixed(2) + '%' 
                : '-'
        },
        { 
            render: (item) => createBadge(item.recomendacion || 'N/A', item.recomendacion || 'info'),
            sanitize: false
        },
        { render: (item) => UI.formatDate(item.fecha_analisis) },
        {
            render: (item) => {
                return `<button class="btn btn-sm btn-primary" onclick="event.stopPropagation(); verDetalle(${item.id})">Ver</button>`;
            },
            sanitize: false
        }
    ];
    
    const dataWithOnclick = analisis.map(a => ({
        ...a,
        onclick: `verDetalle(${a.id})`
    }));
    
    renderSafeTable(tbody, dataWithOnclick, columns, 'No hay análisis');
}

function filtrarAnalisis() {
    cargarAnalisis();
}

async function cargarPrestamos() {
    try {
        const response = await api.get('/prestamos?estado=pendiente&per_page=1000');
        if (response.success) {
            const select = document.getElementById('prestamoId');
            select.innerHTML = '<option value="">Seleccionar préstamo...</option>' +
                response.data.items.map(p => 
                    `<option value="${p.id}">${p.numero_prestamo} - ${p.cliente_nombre} ${p.cliente_apellido}</option>`
                ).join('');
        }
    } catch (error) {
        console.error('Error cargando préstamos:', error);
    }
}

async function cargarDatosPrestamo() {
    const prestamoId = document.getElementById('prestamoId').value;
    if (!prestamoId) return;
    
    try {
        const response = await api.get(`/prestamos/${prestamoId}`);
        if (response.success) {
            const prestamo = response.data;
            // Auto-completar algunos campos si es posible
        }
    } catch (error) {
        console.error('Error cargando datos del préstamo:', error);
    }
}

function abrirModalCrear() {
    document.getElementById('modalCrearAnalisis').classList.add('show');
}

function cerrarModalCrear() {
    document.getElementById('modalCrearAnalisis').classList.remove('show');
    document.getElementById('formCrearAnalisis').reset();
}

async function crearAnalisis() {
    const form = document.getElementById('formCrearAnalisis');
    
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    try {
        UI.showLoading();
        
        const data = {
            prestamo_id: document.getElementById('prestamoId').value,
            score_credito: document.getElementById('scoreCredito').value || null,
            capacidad_pago: document.getElementById('capacidadPago').value || null,
            ratio_deuda_ingresos: document.getElementById('ratioDeudaIngresos').value || null,
            recomendacion: document.getElementById('recomendacion').value,
            comentarios: document.getElementById('comentarios').value || null
        };
        
        const response = await api.post('/analisis', data);
        
        if (response.success) {
            UI.showAlert('Análisis creado correctamente', 'success');
            cerrarModalCrear();
            cargarAnalisis();
        }
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al crear análisis: ' + error.message, 'danger');
    }
}

async function verDetalle(id) {
    try {
        UI.showLoading('Cargando detalle del análisis...');
        
        const response = await api.get(`/analisis/${id}`);
        
        if (response.success) {
            mostrarDetalleAnalisis(response.data);
            document.getElementById('modalDetalleAnalisis').classList.add('show');
        }
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al cargar el detalle: ' + error.message, 'danger');
    }
}

function mostrarDetalleAnalisis(analisis) {
    document.getElementById('detallePrestamo').textContent = analisis.numero_prestamo || '-';
    document.getElementById('detalleCliente').textContent = `${analisis.cliente_nombre || ''} ${analisis.cliente_apellido || ''}`;
    document.getElementById('detalleScore').textContent = analisis.score_credito || '-';
    document.getElementById('detalleCapacidadPago').textContent = analisis.capacidad_pago ? UI.formatCurrency(analisis.capacidad_pago) : '-';
    document.getElementById('detalleRatioDeuda').textContent = analisis.ratio_deuda_ingresos ? (analisis.ratio_deuda_ingresos * 100).toFixed(2) + '%' : '-';
    document.getElementById('detalleRecomendacion').textContent = analisis.recomendacion || '-';
    document.getElementById('detalleRecomendacion').className = `badge badge-${analisis.recomendacion || 'pendiente'}`;
    document.getElementById('detalleFecha').textContent = UI.formatDate(analisis.fecha_analisis);
    document.getElementById('detalleComentarios').textContent = analisis.comentarios || '-';
    document.getElementById('detalleIngresos').textContent = analisis.ingresos_mensuales ? UI.formatCurrency(analisis.ingresos_mensuales) : '-';
    document.getElementById('detalleDeudas').textContent = analisis.total_deudas ? UI.formatCurrency(analisis.total_deudas) : '-';
    document.getElementById('detalleHistorial').textContent = analisis.historial_pagos || '-';
}

function cerrarModalDetalle() {
    document.getElementById('modalDetalleAnalisis').classList.remove('show');
}

// La función logout() está definida globalmente en app.js


