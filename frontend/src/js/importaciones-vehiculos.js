/**
 * Importaciones de Vehículos
 */

document.addEventListener('DOMContentLoaded', function() {
    cargarImportaciones();
    cargarVehiculos();
});

async function cargarImportaciones() {
    try {
        const response = await api.get('/importaciones-vehiculos');
        mostrarImportaciones(response);
    } catch (error) {
        console.error('Error:', error);
        UI.showAlert('Error al cargar importaciones', 'danger');
    }
}

function mostrarImportaciones(importaciones) {
    const tbody = document.getElementById('tablaImportaciones').querySelector('tbody');
    
    if (!importaciones || importaciones.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center">No hay importaciones registradas</td></tr>';
        return;
    }

    tbody.innerHTML = importaciones.map(i => `
        <tr>
            <td>${i.vehiculo_marca || ''} ${i.vehiculo_modelo || ''} - ${i.vehiculo_placa || ''}</td>
            <td>${i.puerto || ''}</td>
            <td>${formatDate(i.fecha_llegada || '')}</td>
            <td>${formatCurrency(i.costo_total || 0)}</td>
            <td><span class="badge badge-${getEstadoBadge(i.estado)}">${i.estado || 'en_proceso'}</span></td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="verImportacion(${i.id})">Ver</button>
            </td>
        </tr>
    `).join('');
}

function getEstadoBadge(estado) {
    const badges = {
        'en_proceso': 'warning',
        'despachado': 'info',
        'completado': 'success',
        'cancelado': 'danger'
    };
    return badges[estado] || 'secondary';
}

async function cargarVehiculos() {
    try {
        const response = await api.get('/vehiculos');
        const select = document.getElementById('vehiculoImportacion');
        select.innerHTML = '<option value="">Seleccionar...</option>' + 
            response.map(v => `<option value="${v.id}">${v.marca} ${v.modelo} - ${v.placa || 'Sin placa'}</option>`).join('');
    } catch (error) {
        console.error('Error cargando vehículos:', error);
    }
}

function abrirModalImportacion() {
    document.getElementById('modalImportacion').classList.add('active');
}

function cerrarModalImportacion() {
    document.getElementById('modalImportacion').classList.remove('active');
    document.getElementById('formImportacion').reset();
}

async function crearImportacion(event) {
    event.preventDefault();
    
    const data = {
        vehiculo_id: parseInt(document.getElementById('vehiculoImportacion').value),
        puerto: document.getElementById('puerto').value,
        fecha_llegada: document.getElementById('fechaLlegada').value,
        fecha_despacho: document.getElementById('fechaDespacho').value,
        costo_fob: parseFloat(document.getElementById('costoFOB').value),
        costo_flete: parseFloat(document.getElementById('costoFlete').value) || 0,
        seguro: parseFloat(document.getElementById('seguro').value) || 0,
        impuestos: parseFloat(document.getElementById('impuestos').value) || 0,
        numero_contenedor: document.getElementById('numeroContenedor').value,
        observaciones: document.getElementById('observacionesImportacion').value
    };

    try {
        UI.showLoading('Guardando importación...');
        const response = await api.post('/importaciones-vehiculos', data);
        
        if (response.success !== false) {
            UI.showAlert('Importación guardada exitosamente', 'success');
            cerrarModalImportacion();
            cargarImportaciones();
        } else {
            UI.showAlert(response.message || 'Error al guardar importación', 'danger');
        }
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        console.error('Error:', error);
        UI.showAlert(error.message || 'Error al guardar importación', 'danger');
    }
}

function verImportacion(id) {
    window.open(`/api/importaciones-vehiculos/${id}/pdf`, '_blank');
}

