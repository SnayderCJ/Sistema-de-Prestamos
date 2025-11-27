/**
 * Bancos
 */

document.addEventListener('DOMContentLoaded', function() {
    cargarBancos();
});

async function cargarBancos() {
    try {
        const response = await api.get('/bancos');
        mostrarBancos(response);
    } catch (error) {
        console.error('Error:', error);
        UI.showAlert('Error al cargar bancos', 'danger');
    }
}

function mostrarBancos(bancos) {
    const tbody = document.getElementById('tablaBancos').querySelector('tbody');
    
    if (!bancos || bancos.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center">No hay bancos registrados</td></tr>';
        return;
    }

    tbody.innerHTML = bancos.map(b => `
        <tr>
            <td><strong>${b.codigo || ''}</strong></td>
            <td>${b.nombre || ''}</td>
            <td>${b.swift || ''}</td>
            <td><span class="badge badge-${b.estado === 'activo' ? 'success' : 'danger'}">${b.estado || 'activo'}</span></td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="editarBanco(${b.id})">Editar</button>
                <button class="btn btn-sm btn-danger" onclick="eliminarBanco(${b.id})">Eliminar</button>
            </td>
        </tr>
    `).join('');
}

function abrirModalBanco() {
    document.getElementById('tituloModalBanco').textContent = 'Nuevo Banco';
    document.getElementById('bancoId').value = '';
    document.getElementById('formBanco').reset();
    document.getElementById('modalBanco').classList.add('active');
}

function cerrarModalBanco() {
    document.getElementById('modalBanco').classList.remove('active');
    document.getElementById('formBanco').reset();
    document.getElementById('bancoId').value = '';
}

async function guardarBanco(event) {
    event.preventDefault();
    
    const id = document.getElementById('bancoId').value;
    const data = {
        codigo: document.getElementById('codigoBanco').value,
        nombre: document.getElementById('nombreBanco').value,
        swift: document.getElementById('swiftBanco').value
    };

    try {
        UI.showLoading('Guardando banco...');
        let response;
        if (id) {
            response = await api.put(`/bancos/${id}`, data);
        } else {
            response = await api.post('/bancos', data);
        }
        
        if (response.success !== false) {
            UI.showAlert(`Banco ${id ? 'actualizado' : 'creado'} exitosamente`, 'success');
            cerrarModalBanco();
            cargarBancos();
        } else {
            UI.showAlert(response.message || 'Error al guardar banco', 'danger');
        }
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        console.error('Error:', error);
        UI.showAlert(error.message || 'Error al guardar banco', 'danger');
    }
}

async function editarBanco(id) {
    try {
        const banco = await api.get(`/bancos/${id}`);
        document.getElementById('bancoId').value = banco.id;
        document.getElementById('codigoBanco').value = banco.codigo || '';
        document.getElementById('nombreBanco').value = banco.nombre || '';
        document.getElementById('swiftBanco').value = banco.swift || '';
        
        document.getElementById('tituloModalBanco').textContent = 'Editar Banco';
        document.getElementById('modalBanco').classList.add('active');
    } catch (error) {
        UI.showAlert('Error al cargar banco', 'danger');
    }
}

async function eliminarBanco(id) {
    if (!confirm('¿Está seguro de eliminar este banco?')) return;
    
    try {
        UI.showLoading('Eliminando banco...');
        await api.delete(`/bancos/${id}`);
        UI.showAlert('Banco eliminado exitosamente', 'success');
        cargarBancos();
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al eliminar banco', 'danger');
    }
}

function filtrarBancos() {
    const busqueda = document.getElementById('buscarBanco').value.toLowerCase();
    const filas = document.querySelectorAll('#tablaBancos tbody tr');
    
    filas.forEach(fila => {
        const texto = fila.textContent.toLowerCase();
        fila.style.display = texto.includes(busqueda) ? '' : 'none';
    });
}

