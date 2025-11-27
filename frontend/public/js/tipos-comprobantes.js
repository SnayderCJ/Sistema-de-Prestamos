/**
 * Tipos de Comprobantes Fiscales
 */

document.addEventListener('DOMContentLoaded', function() {
    cargarTipos();
});

async function cargarTipos() {
    try {
        const response = await api.get('/tipos-comprobantes');
        mostrarTipos(response);
    } catch (error) {
        console.error('Error:', error);
        UI.showAlert('Error al cargar tipos de comprobantes', 'danger');
    }
}

function mostrarTipos(tipos) {
    const tbody = document.getElementById('tablaTipos').querySelector('tbody');
    
    if (!tipos || tipos.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center">No hay tipos registrados</td></tr>';
        return;
    }

    tbody.innerHTML = tipos.map(t => `
        <tr>
            <td><strong>${t.codigo || ''}</strong></td>
            <td>${t.nombre || ''}</td>
            <td>${t.descripcion || ''}</td>
            <td><span class="badge badge-${t.estado === 'activo' ? 'success' : 'danger'}">${t.estado || 'activo'}</span></td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="editarTipo(${t.id})">Editar</button>
                <button class="btn btn-sm btn-danger" onclick="eliminarTipo(${t.id})">Eliminar</button>
            </td>
        </tr>
    `).join('');
}

function abrirModalTipo() {
    document.getElementById('tituloModalTipo').textContent = 'Nuevo Tipo de Comprobante';
    document.getElementById('tipoId').value = '';
    document.getElementById('formTipo').reset();
    document.getElementById('modalTipo').classList.add('active');
}

function cerrarModalTipo() {
    document.getElementById('modalTipo').classList.remove('active');
    document.getElementById('formTipo').reset();
    document.getElementById('tipoId').value = '';
}

async function guardarTipo(event) {
    event.preventDefault();
    
    const id = document.getElementById('tipoId').value;
    const data = {
        codigo: document.getElementById('codigoTipo').value,
        nombre: document.getElementById('nombreTipo').value,
        descripcion: document.getElementById('descripcionTipo').value
    };

    try {
        UI.showLoading('Guardando tipo...');
        let response;
        if (id) {
            response = await api.put(`/tipos-comprobantes/${id}`, data);
        } else {
            response = await api.post('/tipos-comprobantes', data);
        }
        
        if (response.success !== false) {
            UI.showAlert(`Tipo ${id ? 'actualizado' : 'creado'} exitosamente`, 'success');
            cerrarModalTipo();
            cargarTipos();
        } else {
            UI.showAlert(response.message || 'Error al guardar tipo', 'danger');
        }
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        console.error('Error:', error);
        UI.showAlert(error.message || 'Error al guardar tipo', 'danger');
    }
}

async function editarTipo(id) {
    try {
        const tipo = await api.get(`/tipos-comprobantes/${id}`);
        document.getElementById('tipoId').value = tipo.id;
        document.getElementById('codigoTipo').value = tipo.codigo || '';
        document.getElementById('nombreTipo').value = tipo.nombre || '';
        document.getElementById('descripcionTipo').value = tipo.descripcion || '';
        
        document.getElementById('tituloModalTipo').textContent = 'Editar Tipo de Comprobante';
        document.getElementById('modalTipo').classList.add('active');
    } catch (error) {
        UI.showAlert('Error al cargar tipo', 'danger');
    }
}

async function eliminarTipo(id) {
    if (!confirm('¿Está seguro de eliminar este tipo de comprobante?')) return;
    
    try {
        UI.showLoading('Eliminando tipo...');
        await api.delete(`/tipos-comprobantes/${id}`);
        UI.showAlert('Tipo eliminado exitosamente', 'success');
        cargarTipos();
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al eliminar tipo', 'danger');
    }
}

