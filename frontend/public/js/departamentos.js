/**
 * Departamentos
 */

document.addEventListener('DOMContentLoaded', function() {
    cargarDepartamentos();
});

async function cargarDepartamentos() {
    try {
        const response = await api.get('/departamentos');
        mostrarDepartamentos(response);
    } catch (error) {
        console.error('Error:', error);
        UI.showAlert('Error al cargar departamentos', 'danger');
    }
}

function mostrarDepartamentos(departamentos) {
    const tbody = document.getElementById('tablaDepartamentos').querySelector('tbody');
    
    if (!departamentos || departamentos.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center">No hay departamentos registrados</td></tr>';
        return;
    }

    tbody.innerHTML = departamentos.map(d => `
        <tr>
            <td><strong>${d.nombre || ''}</strong></td>
            <td>${d.descripcion || ''}</td>
            <td>${d.cantidad_empleados || 0}</td>
            <td><span class="badge badge-${d.estado === 'activo' ? 'success' : 'danger'}">${d.estado || 'activo'}</span></td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="editarDepartamento(${d.id})">Editar</button>
                <button class="btn btn-sm btn-danger" onclick="eliminarDepartamento(${d.id})">Eliminar</button>
            </td>
        </tr>
    `).join('');
}

function abrirModalDepartamento() {
    document.getElementById('tituloModalDepartamento').textContent = 'Nuevo Departamento';
    document.getElementById('departamentoId').value = '';
    document.getElementById('formDepartamento').reset();
    document.getElementById('modalDepartamento').classList.add('active');
}

function cerrarModalDepartamento() {
    document.getElementById('modalDepartamento').classList.remove('active');
    document.getElementById('formDepartamento').reset();
    document.getElementById('departamentoId').value = '';
}

async function guardarDepartamento(event) {
    event.preventDefault();
    
    const id = document.getElementById('departamentoId').value;
    const data = {
        nombre: document.getElementById('nombreDepartamento').value,
        descripcion: document.getElementById('descripcionDepartamento').value
    };

    try {
        UI.showLoading('Guardando departamento...');
        let response;
        if (id) {
            response = await api.put(`/departamentos/${id}`, data);
        } else {
            response = await api.post('/departamentos', data);
        }
        
        if (response.success !== false) {
            UI.showAlert(`Departamento ${id ? 'actualizado' : 'creado'} exitosamente`, 'success');
            cerrarModalDepartamento();
            cargarDepartamentos();
        } else {
            UI.showAlert(response.message || 'Error al guardar departamento', 'danger');
        }
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        console.error('Error:', error);
        UI.showAlert(error.message || 'Error al guardar departamento', 'danger');
    }
}

async function editarDepartamento(id) {
    try {
        const departamento = await api.get(`/departamentos/${id}`);
        document.getElementById('departamentoId').value = departamento.id;
        document.getElementById('nombreDepartamento').value = departamento.nombre || '';
        document.getElementById('descripcionDepartamento').value = departamento.descripcion || '';
        
        document.getElementById('tituloModalDepartamento').textContent = 'Editar Departamento';
        document.getElementById('modalDepartamento').classList.add('active');
    } catch (error) {
        UI.showAlert('Error al cargar departamento', 'danger');
    }
}

async function eliminarDepartamento(id) {
    if (!confirm('¿Está seguro de eliminar este departamento?')) return;
    
    try {
        UI.showLoading('Eliminando departamento...');
        await api.delete(`/departamentos/${id}`);
        UI.showAlert('Departamento eliminado exitosamente', 'success');
        cargarDepartamentos();
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al eliminar departamento', 'danger');
    }
}

