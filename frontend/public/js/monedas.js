/**
 * Monedas
 */

document.addEventListener('DOMContentLoaded', function() {
    cargarMonedas();
});

async function cargarMonedas() {
    try {
        const response = await api.get('/monedas');
        mostrarMonedas(response);
    } catch (error) {
        console.error('Error:', error);
        UI.showAlert('Error al cargar monedas', 'danger');
    }
}

function mostrarMonedas(monedas) {
    const tbody = document.getElementById('tablaMonedas').querySelector('tbody');
    
    if (!monedas || monedas.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center">No hay monedas registradas</td></tr>';
        return;
    }

    tbody.innerHTML = monedas.map(m => `
        <tr>
            <td><strong>${m.codigo || ''}</strong></td>
            <td>${m.nombre || ''}</td>
            <td>${m.simbolo || ''}</td>
            <td>${m.tasa_cambio || 1}</td>
            <td><span class="badge badge-${m.estado === 'activa' ? 'success' : 'danger'}">${m.estado || 'activa'}</span></td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="editarMoneda(${m.id})">Editar</button>
                <button class="btn btn-sm btn-danger" onclick="eliminarMoneda(${m.id})">Eliminar</button>
            </td>
        </tr>
    `).join('');
}

function abrirModalMoneda() {
    document.getElementById('tituloModalMoneda').textContent = 'Nueva Moneda';
    document.getElementById('monedaId').value = '';
    document.getElementById('formMoneda').reset();
    document.getElementById('modalMoneda').classList.add('active');
}

function cerrarModalMoneda() {
    document.getElementById('modalMoneda').classList.remove('active');
    document.getElementById('formMoneda').reset();
    document.getElementById('monedaId').value = '';
}

async function guardarMoneda(event) {
    event.preventDefault();
    
    const id = document.getElementById('monedaId').value;
    const data = {
        codigo: document.getElementById('codigoMoneda').value.toUpperCase(),
        nombre: document.getElementById('nombreMoneda').value,
        simbolo: document.getElementById('simboloMoneda').value,
        tasa_cambio: parseFloat(document.getElementById('tasaCambio').value)
    };

    try {
        UI.showLoading('Guardando moneda...');
        let response;
        if (id) {
            response = await api.put(`/monedas/${id}`, data);
        } else {
            response = await api.post('/monedas', data);
        }
        
        if (response.success !== false) {
            UI.showAlert(`Moneda ${id ? 'actualizada' : 'creada'} exitosamente`, 'success');
            cerrarModalMoneda();
            cargarMonedas();
        } else {
            UI.showAlert(response.message || 'Error al guardar moneda', 'danger');
        }
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        console.error('Error:', error);
        UI.showAlert(error.message || 'Error al guardar moneda', 'danger');
    }
}

async function editarMoneda(id) {
    try {
        const moneda = await api.get(`/monedas/${id}`);
        document.getElementById('monedaId').value = moneda.id;
        document.getElementById('codigoMoneda').value = moneda.codigo || '';
        document.getElementById('nombreMoneda').value = moneda.nombre || '';
        document.getElementById('simboloMoneda').value = moneda.simbolo || '';
        document.getElementById('tasaCambio').value = moneda.tasa_cambio || 1;
        
        document.getElementById('tituloModalMoneda').textContent = 'Editar Moneda';
        document.getElementById('modalMoneda').classList.add('active');
    } catch (error) {
        UI.showAlert('Error al cargar moneda', 'danger');
    }
}

async function eliminarMoneda(id) {
    if (!confirm('¿Está seguro de eliminar esta moneda?')) return;
    
    try {
        UI.showLoading('Eliminando moneda...');
        await api.delete(`/monedas/${id}`);
        UI.showAlert('Moneda eliminada exitosamente', 'success');
        cargarMonedas();
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al eliminar moneda', 'danger');
    }
}

