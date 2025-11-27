/**
 * Cheques Empresariales
 */

document.addEventListener('DOMContentLoaded', function() {
    cargarCheques();
    cargarBancos();
});

async function cargarCheques() {
    try {
        const filters = {
            banco_id: document.getElementById('filtroBanco').value,
            estado: document.getElementById('filtroEstado').value
        };
        
        const params = new URLSearchParams();
        Object.keys(filters).forEach(key => {
            if (filters[key]) params.append(key, filters[key]);
        });
        
        const response = await api.get(`/cheques-empresariales?${params}`);
        mostrarCheques(response);
    } catch (error) {
        console.error('Error:', error);
        UI.showAlert('Error al cargar cheques', 'danger');
    }
}

function mostrarCheques(cheques) {
    const tbody = document.getElementById('tablaCheques').querySelector('tbody');
    
    if (!cheques || cheques.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center">No hay cheques registrados</td></tr>';
        return;
    }

    tbody.innerHTML = cheques.map(c => `
        <tr>
            <td><strong>${c.numero_cheque || ''}</strong></td>
            <td>${c.banco_nombre || ''}</td>
            <td>${c.numero_cuenta || ''}</td>
            <td>${c.beneficiario || ''}</td>
            <td>${formatCurrency(c.monto || 0)}</td>
            <td>${formatDate(c.fecha_emision || '')}</td>
            <td><span class="badge badge-${getEstadoBadge(c.estado)}">${c.estado || 'pendiente'}</span></td>
            <td>
                ${c.estado === 'pendiente' ? `
                    <button class="btn btn-sm btn-success" onclick="marcarCobrado(${c.id})">Marcar Cobrado</button>
                    <button class="btn btn-sm btn-danger" onclick="anularCheque(${c.id})">Anular</button>
                ` : ''}
                <button class="btn btn-sm btn-primary" onclick="verCheque(${c.id})">Ver</button>
            </td>
        </tr>
    `).join('');
}

function getEstadoBadge(estado) {
    const badges = {
        'pendiente': 'warning',
        'cobrado': 'success',
        'anulado': 'danger'
    };
    return badges[estado] || 'secondary';
}

async function cargarBancos() {
    try {
        const response = await api.get('/bancos');
        const selectFiltro = document.getElementById('filtroBanco');
        const selectModal = document.getElementById('bancoCheque');
        
        const options = response.map(b => `<option value="${b.id}">${b.nombre}</option>`).join('');
        
        selectFiltro.innerHTML = '<option value="">Todos</option>' + options;
        selectModal.innerHTML = '<option value="">Seleccionar...</option>' + options;
    } catch (error) {
        console.error('Error cargando bancos:', error);
    }
}

function abrirModalCheque() {
    document.getElementById('modalCheque').classList.add('active');
}

function cerrarModalCheque() {
    document.getElementById('modalCheque').classList.remove('active');
    document.getElementById('formCheque').reset();
}

async function crearCheque(event) {
    event.preventDefault();
    
    const data = {
        banco_id: parseInt(document.getElementById('bancoCheque').value),
        numero_cuenta: document.getElementById('numeroCuenta').value,
        numero_cheque: document.getElementById('numeroCheque').value,
        beneficiario: document.getElementById('beneficiario').value,
        monto: parseFloat(document.getElementById('montoCheque').value),
        fecha_emision: document.getElementById('fechaEmision').value,
        concepto: document.getElementById('conceptoCheque').value
    };

    try {
        UI.showLoading('Guardando cheque...');
        const response = await api.post('/cheques-empresariales', data);
        
        if (response.success !== false) {
            UI.showAlert('Cheque guardado exitosamente', 'success');
            cerrarModalCheque();
            cargarCheques();
        } else {
            UI.showAlert(response.message || 'Error al guardar cheque', 'danger');
        }
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        console.error('Error:', error);
        UI.showAlert(error.message || 'Error al guardar cheque', 'danger');
    }
}

async function marcarCobrado(id) {
    if (!confirm('¿Marcar este cheque como cobrado?')) return;
    
    try {
        UI.showLoading('Actualizando cheque...');
        await api.put(`/cheques-empresariales/${id}/cobrar`);
        UI.showAlert('Cheque marcado como cobrado', 'success');
        cargarCheques();
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al actualizar cheque', 'danger');
    }
}

async function anularCheque(id) {
    if (!confirm('¿Está seguro de anular este cheque?')) return;
    
    try {
        UI.showLoading('Anulando cheque...');
        await api.put(`/cheques-empresariales/${id}/anular`);
        UI.showAlert('Cheque anulado exitosamente', 'success');
        cargarCheques();
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al anular cheque', 'danger');
    }
}

function verCheque(id) {
    window.open(`/api/cheques-empresariales/${id}/pdf`, '_blank');
}

