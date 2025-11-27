// Gestión de Contratos

let contratos = [];
let contratoActual = null;
let prestamos = [];
let paginaActual = 1;
const porPagina = 20;

document.addEventListener('DOMContentLoaded', () => {
    cargarContratos();
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
            prestamos = response.data.items || response.data || [];
            const select = document.getElementById('contratoPrestamoId');
            
            select.innerHTML = '<option value="">Seleccionar préstamo</option>' +
                prestamos.map(p => 
                    `<option value="${p.id}" data-monto="${p.monto_aprobado}">${p.numero_prestamo} - ${p.cliente_nombre || ''} ${p.cliente_apellido || ''}</option>`
                ).join('');
        }
    } catch (error) {
        console.error('Error cargando préstamos:', error);
    }
}

function cargarDatosPrestamo() {
    const prestamoId = document.getElementById('contratoPrestamoId').value;
    const option = document.getElementById('contratoPrestamoId').selectedOptions[0];
    
    if (option && option.dataset.monto) {
        document.getElementById('contratoMonto').value = option.dataset.monto;
    }
}

async function cargarContratos() {
    try {
        UI.showLoading('Cargando contratos...');
        
        const filtros = {};
        const estado = document.getElementById('filtroEstado').value;
        if (estado) filtros.estado = estado;
        
        const fechaDesde = document.getElementById('filtroFechaDesde').value;
        if (fechaDesde) filtros.fecha_desde = fechaDesde;
        
        const fechaHasta = document.getElementById('filtroFechaHasta').value;
        if (fechaHasta) filtros.fecha_hasta = fechaHasta;
        
        const response = await api.get('/contratos', {
            page: paginaActual,
            per_page: porPagina,
            ...filtros
        });
        
        if (response.success) {
            contratos = response.data.items || response.data || [];
            mostrarContratos(contratos);
            
            if (response.data.pagination) {
                mostrarPaginacion(response.data.pagination);
            }
        }
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al cargar contratos: ' + error.message, 'danger');
    }
}

function mostrarContratos(lista) {
    const tbody = document.querySelector('#tablaContratos tbody');
    
    if (!lista || lista.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center">No hay contratos registrados</td></tr>';
        return;
    }
    
    tbody.innerHTML = lista.map(contrato => `
        <tr>
            <td>${contrato.numero_contrato || '-'}</td>
            <td>${contrato.numero_prestamo || '-'}</td>
            <td>${contrato.cliente_nombre || ''} ${contrato.cliente_apellido || ''}</td>
            <td>${formatDate(contrato.fecha_inicio)}</td>
            <td>${formatDate(contrato.fecha_vencimiento)}</td>
            <td>${formatCurrency(contrato.monto || 0)}</td>
            <td>
                <span class="badge badge-${contrato.estado === 'vigente' ? 'success' : contrato.estado === 'vencido' ? 'danger' : 'secondary'}">
                    ${contrato.estado || 'vigente'}
                </span>
            </td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="verDetalleContrato(${contrato.id})">Ver</button>
                <button class="btn btn-sm btn-secondary" onclick="editarContrato(${contrato.id})">Editar</button>
                <button class="btn btn-sm btn-info" onclick="imprimirContratoDesdeLista(${contrato.id})">Imprimir</button>
            </td>
        </tr>
    `).join('');
}

function mostrarPaginacion(pagination) {
    const paginacion = document.getElementById('paginacionContratos');
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
    cargarContratos();
}

function filtrarContratos() {
    const busqueda = document.getElementById('buscarContrato').value.toLowerCase();
    
    if (!busqueda) {
        mostrarContratos(contratos);
        return;
    }
    
    const filtrados = contratos.filter(c => 
        (c.numero_contrato && c.numero_contrato.toLowerCase().includes(busqueda)) ||
        (c.numero_prestamo && c.numero_prestamo.toLowerCase().includes(busqueda)) ||
        (c.cliente_nombre && c.cliente_nombre.toLowerCase().includes(busqueda)) ||
        (c.cliente_apellido && c.cliente_apellido.toLowerCase().includes(busqueda))
    );
    
    mostrarContratos(filtrados);
}

function abrirModalCrearContrato() {
    contratoActual = null;
    document.getElementById('modalContratoTitulo').textContent = 'Nuevo Contrato';
    document.getElementById('formContrato').reset();
    document.getElementById('contratoId').value = '';
    document.getElementById('contratoEstado').value = 'vigente';
    document.getElementById('contratoFechaInicio').value = new Date().toISOString().split('T')[0];
    document.getElementById('modalContrato').style.display = 'block';
}

async function verDetalleContrato(id) {
    try {
        UI.showLoading('Cargando detalle...');
        
        const response = await api.get(`/contratos/${id}`);
        
        if (response.success) {
            contratoActual = response.data;
            mostrarDetalleContrato(contratoActual);
            document.getElementById('modalDetalleContrato').style.display = 'block';
        }
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al cargar detalle: ' + error.message, 'danger');
    }
}

function mostrarDetalleContrato(contrato) {
    document.getElementById('detalleContratoNumero').textContent = contrato.numero_contrato || '-';
    document.getElementById('detalleContratoPrestamo').textContent = contrato.numero_prestamo || '-';
    document.getElementById('detalleContratoCliente').textContent = `${contrato.cliente_nombre || ''} ${contrato.cliente_apellido || ''}`;
    document.getElementById('detalleContratoTipo').textContent = contrato.tipo || '-';
    document.getElementById('detalleContratoFechaInicio').textContent = formatDate(contrato.fecha_inicio);
    document.getElementById('detalleContratoFechaVencimiento').textContent = formatDate(contrato.fecha_vencimiento);
    document.getElementById('detalleContratoMonto').textContent = formatCurrency(contrato.monto || 0);
    document.getElementById('detalleContratoDescripcion').textContent = contrato.descripcion || '-';
    
    const estadoBadge = document.getElementById('detalleContratoEstado');
    estadoBadge.textContent = contrato.estado || 'vigente';
    estadoBadge.className = `badge badge-${contrato.estado === 'vigente' ? 'success' : contrato.estado === 'vencido' ? 'danger' : 'secondary'}`;
}

async function editarContrato(id) {
    try {
        UI.showLoading('Cargando contrato...');
        
        const response = await api.get(`/contratos/${id}`);
        
        if (response.success) {
            contratoActual = response.data;
            document.getElementById('modalContratoTitulo').textContent = 'Editar Contrato';
            document.getElementById('contratoId').value = contratoActual.id;
            document.getElementById('contratoPrestamoId').value = contratoActual.prestamo_id || '';
            document.getElementById('contratoFechaInicio').value = contratoActual.fecha_inicio ? contratoActual.fecha_inicio.split('T')[0] : '';
            document.getElementById('contratoFechaVencimiento').value = contratoActual.fecha_vencimiento ? contratoActual.fecha_vencimiento.split('T')[0] : '';
            document.getElementById('contratoTipo').value = contratoActual.tipo || 'prestamo';
            document.getElementById('contratoDescripcion').value = contratoActual.descripcion || '';
            document.getElementById('contratoMonto').value = contratoActual.monto || '';
            document.getElementById('contratoEstado').value = contratoActual.estado || 'vigente';
            
            document.getElementById('modalContrato').style.display = 'block';
        }
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al cargar contrato: ' + error.message, 'danger');
    }
}

async function guardarContrato(event) {
    event.preventDefault();
    
    try {
        UI.showLoading('Guardando contrato...');
        
        const data = {
            prestamo_id: document.getElementById('contratoPrestamoId').value,
            fecha_inicio: document.getElementById('contratoFechaInicio').value,
            fecha_vencimiento: document.getElementById('contratoFechaVencimiento').value,
            tipo: document.getElementById('contratoTipo').value,
            descripcion: document.getElementById('contratoDescripcion').value,
            monto: document.getElementById('contratoMonto').value || null,
            estado: document.getElementById('contratoEstado').value
        };
        
        const id = document.getElementById('contratoId').value;
        let response;
        
        if (id) {
            response = await api.put(`/contratos/${id}`, data);
        } else {
            response = await api.post('/contratos', data);
        }
        
        if (response.success) {
            UI.showAlert(id ? 'Contrato actualizado correctamente' : 'Contrato creado correctamente', 'success');
            cerrarModal('modalContrato');
            cargarContratos();
        }
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al guardar contrato: ' + error.message, 'danger');
    }
}

async function imprimirContratoDesdeLista(id) {
    await verDetalleContrato(id);
    setTimeout(() => imprimirContrato(), 500);
}

function imprimirContrato() {
    if (!contratoActual) return;
    
    window.print();
}

function cerrarModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

