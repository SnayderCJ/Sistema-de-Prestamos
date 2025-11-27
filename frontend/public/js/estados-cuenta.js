/**
 * Estados de Cuenta
 */

document.addEventListener('DOMContentLoaded', function() {
    cargarEstadosCuenta();
    cargarClientes();
    
    document.getElementById('clienteEstado').addEventListener('change', function() {
        cargarPrestamosCliente(this.value);
    });
});

async function cargarEstadosCuenta() {
    try {
        const filters = {
            cliente_id: document.getElementById('filtroCliente').value,
            prestamo_id: document.getElementById('filtroPrestamo').value,
            fecha_desde: document.getElementById('filtroFechaDesde').value,
            fecha_hasta: document.getElementById('filtroFechaHasta').value
        };
        
        const params = new URLSearchParams();
        Object.keys(filters).forEach(key => {
            if (filters[key]) params.append(key, filters[key]);
        });
        
        const response = await api.get(`/estados-cuenta?${params}`);
        mostrarEstadosCuenta(response);
    } catch (error) {
        console.error('Error:', error);
        UI.showAlert('Error al cargar estados de cuenta', 'danger');
    }
}

function mostrarEstadosCuenta(estados) {
    const tbody = document.getElementById('tablaEstadosCuenta').querySelector('tbody');
    
    if (!estados || estados.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center">No hay estados de cuenta generados</td></tr>';
        return;
    }

    tbody.innerHTML = estados.map(e => `
        <tr>
            <td>${e.id || ''}</td>
            <td>${(e.cliente_nombre || '') + ' ' + (e.cliente_apellido || '')}</td>
            <td>${e.numero_prestamo || ''}</td>
            <td>${formatDate(e.fecha_generacion || '')}</td>
            <td>${formatDate(e.fecha_desde || '')} - ${formatDate(e.fecha_hasta || '')}</td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="verEstadoCuenta(${e.id})">Ver</button>
                <button class="btn btn-sm btn-success" onclick="imprimirEstadoCuenta(${e.id})">Imprimir</button>
            </td>
        </tr>
    `).join('');
}

async function cargarClientes() {
    try {
        const response = await api.get('/clientes');
        const selectFiltro = document.getElementById('filtroCliente');
        const selectModal = document.getElementById('clienteEstado');
        
        const options = '<option value="">Seleccionar...</option>' + 
            response.map(c => `<option value="${c.id}">${c.nombre} ${c.apellido}</option>`).join('');
        
        selectFiltro.innerHTML = '<option value="">Todos</option>' + 
            response.map(c => `<option value="${c.id}">${c.nombre} ${c.apellido}</option>`).join('');
        selectModal.innerHTML = options;
    } catch (error) {
        console.error('Error cargando clientes:', error);
    }
}

async function cargarPrestamosCliente(clienteId) {
    if (!clienteId) {
        document.getElementById('prestamoEstado').innerHTML = '<option value="">Seleccionar cliente primero</option>';
        return;
    }
    
    try {
        const response = await api.get(`/prestamos?cliente_id=${clienteId}`);
        const select = document.getElementById('prestamoEstado');
        select.innerHTML = '<option value="">Seleccionar...</option>' + 
            response.map(p => `<option value="${p.id}">${p.numero_prestamo} - ${formatCurrency(p.monto_aprobado || 0)}</option>`).join('');
    } catch (error) {
        console.error('Error cargando préstamos:', error);
    }
}

function generarEstadoCuenta() {
    document.getElementById('modalEstadoCuenta').classList.add('active');
}

function cerrarModalEstado() {
    document.getElementById('modalEstadoCuenta').classList.remove('active');
    document.getElementById('formEstadoCuenta').reset();
}

async function crearEstadoCuenta(event) {
    event.preventDefault();
    
    const data = {
        cliente_id: parseInt(document.getElementById('clienteEstado').value),
        prestamo_id: parseInt(document.getElementById('prestamoEstado').value),
        fecha_desde: document.getElementById('fechaDesdeEstado').value,
        fecha_hasta: document.getElementById('fechaHastaEstado').value
    };

    try {
        UI.showLoading('Generando estado de cuenta...');
        const response = await api.post('/estados-cuenta', data);
        
        if (response.success !== false) {
            UI.showAlert('Estado de cuenta generado exitosamente', 'success');
            cerrarModalEstado();
            cargarEstadosCuenta();
        } else {
            UI.showAlert(response.message || 'Error al generar estado de cuenta', 'danger');
        }
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        console.error('Error:', error);
        UI.showAlert(error.message || 'Error al generar estado de cuenta', 'danger');
    }
}

function verEstadoCuenta(id) {
    window.open(`/api/estados-cuenta/${id}/pdf`, '_blank');
}

function imprimirEstadoCuenta(id) {
    window.open(`/api/estados-cuenta/${id}/pdf`, '_blank').print();
}

