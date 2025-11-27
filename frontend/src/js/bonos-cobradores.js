/**
 * Bonos de Cobradores
 */

document.addEventListener('DOMContentLoaded', function() {
    cargarBonos();
    cargarCobradores();
    
    document.getElementById('montoCobrado').addEventListener('input', calcularBono);
    document.getElementById('porcentajeBono').addEventListener('input', calcularBono);
});

function calcularBono() {
    const monto = parseFloat(document.getElementById('montoCobrado').value) || 0;
    const porcentaje = parseFloat(document.getElementById('porcentajeBono').value) || 0;
    const bono = (monto * porcentaje) / 100;
    document.getElementById('bonoCalculado').value = formatCurrency(bono);
}

async function cargarBonos() {
    try {
        const filters = {
            cobrador_id: document.getElementById('filtroCobrador').value,
            mes: document.getElementById('filtroMes').value
        };
        
        const params = new URLSearchParams();
        Object.keys(filters).forEach(key => {
            if (filters[key]) params.append(key, filters[key]);
        });
        
        const response = await api.get(`/bonos-cobradores?${params}`);
        mostrarBonos(response);
    } catch (error) {
        console.error('Error:', error);
        UI.showAlert('Error al cargar bonos', 'danger');
    }
}

function mostrarBonos(bonos) {
    const tbody = document.getElementById('tablaBonos').querySelector('tbody');
    
    if (!bonos || bonos.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center">No hay bonos registrados</td></tr>';
        return;
    }

    tbody.innerHTML = bonos.map(b => `
        <tr>
            <td>${(b.cobrador_nombre || '') + ' ' + (b.cobrador_apellido || '')}</td>
            <td>${b.mes || ''}</td>
            <td>${formatCurrency(b.monto_cobrado || 0)}</td>
            <td>${b.porcentaje || 0}%</td>
            <td>${formatCurrency(b.monto_bono || 0)}</td>
            <td><span class="badge badge-${b.estado === 'pagado' ? 'success' : 'warning'}">${b.estado || 'pendiente'}</span></td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="editarBono(${b.id})">Editar</button>
                <button class="btn btn-sm btn-danger" onclick="eliminarBono(${b.id})">Eliminar</button>
            </td>
        </tr>
    `).join('');
}

async function cargarCobradores() {
    try {
        const response = await api.get('/empleados?rol=cobrador');
        const selectFiltro = document.getElementById('filtroCobrador');
        const selectModal = document.getElementById('cobradorBono');
        
        const options = response.map(e => `<option value="${e.id}">${e.nombre} ${e.apellido}</option>`).join('');
        
        selectFiltro.innerHTML = '<option value="">Todos</option>' + options;
        selectModal.innerHTML = '<option value="">Seleccionar...</option>' + options;
    } catch (error) {
        console.error('Error cargando cobradores:', error);
    }
}

function abrirModalBono() {
    document.getElementById('modalBono').classList.add('active');
}

function cerrarModalBono() {
    document.getElementById('modalBono').classList.remove('active');
    document.getElementById('formBono').reset();
    document.getElementById('bonoCalculado').value = '';
}

async function crearBono(event) {
    event.preventDefault();
    
    const data = {
        cobrador_id: parseInt(document.getElementById('cobradorBono').value),
        mes: document.getElementById('mesBono').value,
        monto_cobrado: parseFloat(document.getElementById('montoCobrado').value),
        porcentaje: parseFloat(document.getElementById('porcentajeBono').value),
        observaciones: document.getElementById('observacionesBono').value
    };

    try {
        UI.showLoading('Guardando bono...');
        const response = await api.post('/bonos-cobradores', data);
        
        if (response.success !== false) {
            UI.showAlert('Bono guardado exitosamente', 'success');
            cerrarModalBono();
            cargarBonos();
        } else {
            UI.showAlert(response.message || 'Error al guardar bono', 'danger');
        }
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        console.error('Error:', error);
        UI.showAlert(error.message || 'Error al guardar bono', 'danger');
    }
}

async function editarBono(id) {
    try {
        const bono = await api.get(`/bonos-cobradores/${id}`);
        document.getElementById('cobradorBono').value = bono.cobrador_id;
        document.getElementById('mesBono').value = bono.mes;
        document.getElementById('montoCobrado').value = bono.monto_cobrado;
        document.getElementById('porcentajeBono').value = bono.porcentaje;
        document.getElementById('observacionesBono').value = bono.observaciones || '';
        calcularBono();
        
        document.getElementById('formBono').onsubmit = async (e) => {
            e.preventDefault();
            await actualizarBono(id);
        };
        
        document.getElementById('modalBono').classList.add('active');
    } catch (error) {
        UI.showAlert('Error al cargar bono', 'danger');
    }
}

async function actualizarBono(id) {
    const data = {
        cobrador_id: parseInt(document.getElementById('cobradorBono').value),
        mes: document.getElementById('mesBono').value,
        monto_cobrado: parseFloat(document.getElementById('montoCobrado').value),
        porcentaje: parseFloat(document.getElementById('porcentajeBono').value),
        observaciones: document.getElementById('observacionesBono').value
    };

    try {
        UI.showLoading('Actualizando bono...');
        const response = await api.put(`/bonos-cobradores/${id}`, data);
        
        if (response.success !== false) {
            UI.showAlert('Bono actualizado exitosamente', 'success');
            cerrarModalBono();
            cargarBonos();
        } else {
            UI.showAlert(response.message || 'Error al actualizar bono', 'danger');
        }
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert(error.message || 'Error al actualizar bono', 'danger');
    }
}

async function eliminarBono(id) {
    if (!confirm('¿Está seguro de eliminar este bono?')) return;
    
    try {
        UI.showLoading('Eliminando bono...');
        await api.delete(`/bonos-cobradores/${id}`);
        UI.showAlert('Bono eliminado exitosamente', 'success');
        cargarBonos();
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al eliminar bono', 'danger');
    }
}

