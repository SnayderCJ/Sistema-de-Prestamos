/**
 * Asientos Legales
 */

let partidasCount = 0;

document.addEventListener('DOMContentLoaded', function() {
    cargarAsientos();
});

async function cargarAsientos() {
    try {
        const filters = {
            tipo: document.getElementById('filtroTipo').value,
            fecha_desde: document.getElementById('filtroFechaDesde').value,
            fecha_hasta: document.getElementById('filtroFechaHasta').value
        };
        
        const params = new URLSearchParams();
        Object.keys(filters).forEach(key => {
            if (filters[key]) params.append(key, filters[key]);
        });
        
        const response = await api.get(`/legal?${params}`);
        mostrarAsientos(response);
    } catch (error) {
        console.error('Error:', error);
        UI.showAlert('Error al cargar asientos legales', 'danger');
    }
}

function mostrarAsientos(asientos) {
    const tbody = document.getElementById('tablaAsientos').querySelector('tbody');
    
    if (!asientos || asientos.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center">No hay asientos legales registrados</td></tr>';
        return;
    }

    tbody.innerHTML = asientos.map(a => `
        <tr>
            <td><strong>${a.numero_asiento || ''}</strong></td>
            <td><span class="badge badge-info">${a.tipo || ''}</span></td>
            <td>${formatDate(a.fecha || '')}</td>
            <td>${a.descripcion || ''}</td>
            <td>${formatCurrency(a.monto_total || 0)}</td>
            <td><span class="badge badge-${a.estado === 'aprobado' ? 'success' : 'warning'}">${a.estado || 'pendiente'}</span></td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="verAsiento(${a.id})">Ver</button>
                ${a.estado === 'pendiente' ? `
                    <button class="btn btn-sm btn-success" onclick="aprobarAsiento(${a.id})">Aprobar</button>
                ` : ''}
            </td>
        </tr>
    `).join('');
}

function abrirModalAsiento() {
    partidasCount = 0;
    document.getElementById('listaPartidas').innerHTML = '';
    agregarPartida();
    document.getElementById('modalAsiento').classList.add('active');
}

function cerrarModalAsiento() {
    document.getElementById('modalAsiento').classList.remove('active');
    document.getElementById('formAsiento').reset();
    partidasCount = 0;
    document.getElementById('listaPartidas').innerHTML = '';
}

function agregarPartida() {
    partidasCount++;
    const div = document.createElement('div');
    div.className = 'form-row partida-row';
    div.id = `partida-${partidasCount}`;
    div.innerHTML = `
        <div class="form-group">
            <label>Cuenta Contable</label>
            <input type="text" class="form-control" name="cuenta[]" required>
        </div>
        <div class="form-group">
            <label>Debe</label>
            <input type="number" class="form-control" name="debe[]" step="0.01" value="0">
        </div>
        <div class="form-group">
            <label>Haber</label>
            <input type="number" class="form-control" name="haber[]" step="0.01" value="0">
        </div>
        <div class="form-group">
            <label>&nbsp;</label>
            <button type="button" class="btn btn-sm btn-danger" onclick="eliminarPartida(${partidasCount})">Eliminar</button>
        </div>
    `;
    document.getElementById('listaPartidas').appendChild(div);
}

function eliminarPartida(id) {
    const partida = document.getElementById(`partida-${id}`);
    if (partida) {
        partida.remove();
    }
}

async function crearAsiento(event) {
    event.preventDefault();
    
    const partidas = [];
    const partidasRows = document.querySelectorAll('.partida-row');
    
    partidasRows.forEach(row => {
        const cuenta = row.querySelector('input[name="cuenta[]"]').value;
        const debe = parseFloat(row.querySelector('input[name="debe[]"]').value) || 0;
        const haber = parseFloat(row.querySelector('input[name="haber[]"]').value) || 0;
        
        if (cuenta && (debe > 0 || haber > 0)) {
            partidas.push({ cuenta, debe, haber });
        }
    });
    
    if (partidas.length === 0) {
        UI.showAlert('Debe agregar al menos una partida', 'warning');
        return;
    }
    
    const totalDebe = partidas.reduce((sum, p) => sum + p.debe, 0);
    const totalHaber = partidas.reduce((sum, p) => sum + p.haber, 0);
    
    if (Math.abs(totalDebe - totalHaber) > 0.01) {
        UI.showAlert('El debe y el haber deben ser iguales', 'warning');
        return;
    }
    
    const data = {
        tipo: document.getElementById('tipoAsiento').value,
        fecha: document.getElementById('fechaAsiento').value,
        descripcion: document.getElementById('descripcionAsiento').value,
        referencia_legal: document.getElementById('referenciaLegal').value,
        partidas: partidas
    };

    try {
        UI.showLoading('Guardando asiento legal...');
        const response = await api.post('/legal', data);
        
        if (response.success !== false) {
            UI.showAlert('Asiento legal creado exitosamente', 'success');
            cerrarModalAsiento();
            cargarAsientos();
        } else {
            UI.showAlert(response.message || 'Error al crear asiento legal', 'danger');
        }
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        console.error('Error:', error);
        UI.showAlert(error.message || 'Error al crear asiento legal', 'danger');
    }
}

function verAsiento(id) {
    window.open(`/api/legal/${id}/pdf`, '_blank');
}

async function aprobarAsiento(id) {
    if (!confirm('¿Aprobar este asiento legal?')) return;
    
    try {
        UI.showLoading('Aprobando asiento...');
        await api.put(`/legal/${id}/aprobar`);
        UI.showAlert('Asiento aprobado exitosamente', 'success');
        cargarAsientos();
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al aprobar asiento', 'danger');
    }
}

