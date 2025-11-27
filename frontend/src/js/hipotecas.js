/**
 * Hipotecas
 */

document.addEventListener('DOMContentLoaded', function() {
    cargarHipotecas();
    cargarPrestamos();
});

async function cargarHipotecas() {
    try {
        const response = await api.get('/hipotecas');
        mostrarHipotecas(response);
    } catch (error) {
        console.error('Error:', error);
        UI.showAlert('Error al cargar hipotecas', 'danger');
    }
}

function mostrarHipotecas(hipotecas) {
    const tbody = document.getElementById('tablaHipotecas').querySelector('tbody');
    
    if (!hipotecas || hipotecas.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center">No hay hipotecas registradas</td></tr>';
        return;
    }

    tbody.innerHTML = hipotecas.map(h => `
        <tr>
            <td><strong>${h.numero_prestamo || ''}</strong></td>
            <td>${(h.cliente_nombre || '') + ' ' + (h.cliente_apellido || '')}</td>
            <td>${h.tipo_propiedad || ''} - ${h.direccion_propiedad || ''}</td>
            <td>${formatCurrency(h.valor_avaluo || 0)}</td>
            <td>${formatCurrency(h.monto_hipotecado || 0)}</td>
            <td><span class="badge badge-${h.estado === 'activa' ? 'success' : 'warning'}">${h.estado || 'activa'}</span></td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="verHipoteca(${h.id})">Ver</button>
                <button class="btn btn-sm btn-danger" onclick="cancelarHipoteca(${h.id})">Cancelar</button>
            </td>
        </tr>
    `).join('');
}

async function cargarPrestamos() {
    try {
        const response = await api.get('/prestamos?estado=activo');
        const select = document.getElementById('prestamoHipoteca');
        select.innerHTML = '<option value="">Seleccionar...</option>' + 
            response.map(p => `<option value="${p.id}">${p.numero_prestamo} - ${(p.cliente_nombre || '') + ' ' + (p.cliente_apellido || '')}</option>`).join('');
    } catch (error) {
        console.error('Error cargando préstamos:', error);
    }
}

function abrirModalHipoteca() {
    document.getElementById('modalHipoteca').classList.add('active');
}

function cerrarModalHipoteca() {
    document.getElementById('modalHipoteca').classList.remove('active');
    document.getElementById('formHipoteca').reset();
}

async function crearHipoteca(event) {
    event.preventDefault();
    
    const data = {
        prestamo_id: parseInt(document.getElementById('prestamoHipoteca').value),
        tipo_propiedad: document.getElementById('tipoPropiedad').value,
        direccion_propiedad: document.getElementById('direccionPropiedad').value,
        valor_avaluo: parseFloat(document.getElementById('valorAvaluo').value),
        monto_hipotecado: parseFloat(document.getElementById('montoHipotecado').value),
        numero_escritura: document.getElementById('numeroEscritura').value,
        fecha_escritura: document.getElementById('fechaEscritura').value,
        observaciones: document.getElementById('observacionesHipoteca').value
    };

    try {
        UI.showLoading('Guardando hipoteca...');
        const response = await api.post('/hipotecas', data);
        
        if (response.success !== false) {
            UI.showAlert('Hipoteca guardada exitosamente', 'success');
            cerrarModalHipoteca();
            cargarHipotecas();
        } else {
            UI.showAlert(response.message || 'Error al guardar hipoteca', 'danger');
        }
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        console.error('Error:', error);
        UI.showAlert(error.message || 'Error al guardar hipoteca', 'danger');
    }
}

function verHipoteca(id) {
    window.open(`/api/hipotecas/${id}/pdf`, '_blank');
}

async function cancelarHipoteca(id) {
    if (!confirm('¿Está seguro de cancelar esta hipoteca?')) return;
    
    try {
        UI.showLoading('Cancelando hipoteca...');
        await api.put(`/hipotecas/${id}/cancelar`);
        UI.showAlert('Hipoteca cancelada exitosamente', 'success');
        cargarHipotecas();
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al cancelar hipoteca', 'danger');
    }
}

