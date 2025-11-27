/**
 * Impuestos
 */

document.addEventListener('DOMContentLoaded', function() {
    cargarImpuestos();
});

async function cargarImpuestos() {
    try {
        const response = await api.get('/impuestos');
        mostrarImpuestos(response);
    } catch (error) {
        console.error('Error:', error);
        UI.showAlert('Error al cargar impuestos', 'danger');
    }
}

function mostrarImpuestos(impuestos) {
    const tbody = document.getElementById('tablaImpuestos').querySelector('tbody');
    
    if (!impuestos || impuestos.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center">No hay impuestos registrados</td></tr>';
        return;
    }

    tbody.innerHTML = impuestos.map(i => `
        <tr>
            <td><strong>${i.nombre || ''}</strong></td>
            <td><span class="badge badge-info">${i.tipo || ''}</span></td>
            <td>${i.porcentaje || 0}%</td>
            <td><span class="badge badge-${i.estado === 'activo' ? 'success' : 'danger'}">${i.estado || 'activo'}</span></td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="editarImpuesto(${i.id})">Editar</button>
                <button class="btn btn-sm btn-danger" onclick="eliminarImpuesto(${i.id})">Eliminar</button>
            </td>
        </tr>
    `).join('');
}

function abrirModalImpuesto() {
    document.getElementById('tituloModalImpuesto').textContent = 'Nuevo Impuesto';
    document.getElementById('impuestoId').value = '';
    document.getElementById('formImpuesto').reset();
    document.getElementById('modalImpuesto').classList.add('active');
}

function cerrarModalImpuesto() {
    document.getElementById('modalImpuesto').classList.remove('active');
    document.getElementById('formImpuesto').reset();
    document.getElementById('impuestoId').value = '';
}

async function guardarImpuesto(event) {
    event.preventDefault();
    
    const id = document.getElementById('impuestoId').value;
    const data = {
        nombre: document.getElementById('nombreImpuesto').value,
        tipo: document.getElementById('tipoImpuesto').value,
        porcentaje: parseFloat(document.getElementById('porcentajeImpuesto').value),
        descripcion: document.getElementById('descripcionImpuesto').value
    };

    try {
        UI.showLoading('Guardando impuesto...');
        let response;
        if (id) {
            response = await api.put(`/impuestos/${id}`, data);
        } else {
            response = await api.post('/impuestos', data);
        }
        
        if (response.success !== false) {
            UI.showAlert(`Impuesto ${id ? 'actualizado' : 'creado'} exitosamente`, 'success');
            cerrarModalImpuesto();
            cargarImpuestos();
        } else {
            UI.showAlert(response.message || 'Error al guardar impuesto', 'danger');
        }
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        console.error('Error:', error);
        UI.showAlert(error.message || 'Error al guardar impuesto', 'danger');
    }
}

async function editarImpuesto(id) {
    try {
        const impuesto = await api.get(`/impuestos/${id}`);
        document.getElementById('impuestoId').value = impuesto.id;
        document.getElementById('nombreImpuesto').value = impuesto.nombre || '';
        document.getElementById('tipoImpuesto').value = impuesto.tipo || '';
        document.getElementById('porcentajeImpuesto').value = impuesto.porcentaje || 0;
        document.getElementById('descripcionImpuesto').value = impuesto.descripcion || '';
        
        document.getElementById('tituloModalImpuesto').textContent = 'Editar Impuesto';
        document.getElementById('modalImpuesto').classList.add('active');
    } catch (error) {
        UI.showAlert('Error al cargar impuesto', 'danger');
    }
}

async function eliminarImpuesto(id) {
    if (!confirm('¿Está seguro de eliminar este impuesto?')) return;
    
    try {
        UI.showLoading('Eliminando impuesto...');
        await api.delete(`/impuestos/${id}`);
        UI.showAlert('Impuesto eliminado exitosamente', 'success');
        cargarImpuestos();
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al eliminar impuesto', 'danger');
    }
}

