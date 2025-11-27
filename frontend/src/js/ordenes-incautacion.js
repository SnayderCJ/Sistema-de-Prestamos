/**
 * Órdenes de Incautación
 */

document.addEventListener('DOMContentLoaded', function() {
    cargarOrdenes();
    cargarPrestamos();
});

async function cargarOrdenes() {
    try {
        const response = await api.get('/ordenes-incautacion');
        mostrarOrdenes(response);
    } catch (error) {
        console.error('Error:', error);
        UI.showAlert('Error al cargar órdenes', 'danger');
    }
}

function mostrarOrdenes(ordenes) {
    const tbody = document.getElementById('tablaOrdenes').querySelector('tbody');
    
    if (!ordenes || ordenes.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center">No hay órdenes registradas</td></tr>';
        return;
    }

    tbody.innerHTML = ordenes.map(o => `
        <tr>
            <td><strong>${o.numero_orden || ''}</strong></td>
            <td>${o.numero_prestamo || ''}</td>
            <td>${(o.cliente_nombre || '') + ' ' + (o.cliente_apellido || '')}</td>
            <td>${formatDate(o.fecha_orden || '')}</td>
            <td><span class="badge badge-${getEstadoBadge(o.estado)}">${o.estado || 'pendiente'}</span></td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="verOrden(${o.id})">Ver</button>
                ${o.estado === 'pendiente' ? `
                    <button class="btn btn-sm btn-success" onclick="ejecutarOrden(${o.id})">Ejecutar</button>
                    <button class="btn btn-sm btn-danger" onclick="cancelarOrden(${o.id})">Cancelar</button>
                ` : ''}
            </td>
        </tr>
    `).join('');
}

function getEstadoBadge(estado) {
    const badges = {
        'pendiente': 'warning',
        'ejecutada': 'success',
        'cancelada': 'danger'
    };
    return badges[estado] || 'secondary';
}

async function cargarPrestamos() {
    try {
        const response = await api.get('/prestamos?estado=vencido,activo');
        const select = document.getElementById('prestamoOrden');
        select.innerHTML = '<option value="">Seleccionar...</option>' + 
            response.map(p => `<option value="${p.id}">${p.numero_prestamo} - ${(p.cliente_nombre || '') + ' ' + (p.cliente_apellido || '')}</option>`).join('');
    } catch (error) {
        console.error('Error cargando préstamos:', error);
    }
}

function abrirModalOrden() {
    document.getElementById('modalOrden').classList.add('active');
}

function cerrarModalOrden() {
    document.getElementById('modalOrden').classList.remove('active');
    document.getElementById('formOrden').reset();
}

async function crearOrden(event) {
    event.preventDefault();
    
    const data = {
        prestamo_id: parseInt(document.getElementById('prestamoOrden').value),
        fecha_orden: document.getElementById('fechaOrden').value,
        motivo: document.getElementById('motivoOrden').value,
        bienes: document.getElementById('bienesOrden').value,
        observaciones: document.getElementById('observacionesOrden').value
    };

    try {
        UI.showLoading('Guardando orden...');
        const response = await api.post('/ordenes-incautacion', data);
        
        if (response.success !== false) {
            UI.showAlert('Orden creada exitosamente', 'success');
            cerrarModalOrden();
            cargarOrdenes();
        } else {
            UI.showAlert(response.message || 'Error al crear orden', 'danger');
        }
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        console.error('Error:', error);
        UI.showAlert(error.message || 'Error al crear orden', 'danger');
    }
}

function verOrden(id) {
    window.open(`/api/ordenes-incautacion/${id}/pdf`, '_blank');
}

async function ejecutarOrden(id) {
    if (!confirm('¿Marcar esta orden como ejecutada?')) return;
    
    try {
        UI.showLoading('Actualizando orden...');
        await api.put(`/ordenes-incautacion/${id}/ejecutar`);
        UI.showAlert('Orden marcada como ejecutada', 'success');
        cargarOrdenes();
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al actualizar orden', 'danger');
    }
}

async function cancelarOrden(id) {
    if (!confirm('¿Está seguro de cancelar esta orden?')) return;
    
    try {
        UI.showLoading('Cancelando orden...');
        await api.put(`/ordenes-incautacion/${id}/cancelar`);
        UI.showAlert('Orden cancelada exitosamente', 'success');
        cargarOrdenes();
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al cancelar orden', 'danger');
    }
}

