/**
 * Reenganche de Préstamos
 */

document.addEventListener('DOMContentLoaded', function() {
    cargarPrestamosElegibles();
    cargarTasas();
});

async function cargarPrestamosElegibles() {
    try {
        const response = await api.get('/reenganche/elegibles');
        mostrarPrestamosElegibles(response);
    } catch (error) {
        console.error('Error:', error);
        UI.showAlert('Error al cargar préstamos elegibles', 'danger');
    }
}

function mostrarPrestamosElegibles(prestamos) {
    const tbody = document.getElementById('tablaReenganches').querySelector('tbody');
    
    if (!prestamos || prestamos.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center">No hay préstamos elegibles para reenganche</td></tr>';
        return;
    }

    tbody.innerHTML = prestamos.map(p => `
        <tr>
            <td><strong>${p.numero_prestamo || ''}</strong></td>
            <td>${(p.cliente_nombre || '') + ' ' + (p.cliente_apellido || '')}</td>
            <td>${formatCurrency(p.monto_aprobado || 0)}</td>
            <td>${formatCurrency(p.saldo_actual || 0)}</td>
            <td>${p.cuotas_pagadas || 0} / ${p.plazo_meses || 0}</td>
            <td><span class="badge badge-${p.estado === 'activo' ? 'success' : 'warning'}">${p.estado || ''}</span></td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="iniciarReenganche(${p.id}, '${p.numero_prestamo || ''}')">
                    Reenganchar
                </button>
            </td>
        </tr>
    `).join('');
}

async function cargarTasas() {
    try {
        const response = await api.get('/tasas');
        const select = document.getElementById('tasaInteresReenganche');
        select.innerHTML = '<option value="">Seleccionar...</option>' + 
            response.map(t => `<option value="${t.id}">${t.nombre} (${t.tasa_anual}%)</option>`).join('');
    } catch (error) {
        console.error('Error cargando tasas:', error);
    }
}

function iniciarReenganche(prestamoId, numeroPrestamo) {
    document.getElementById('prestamoIdReenganche').value = prestamoId;
    document.getElementById('prestamoOriginal').value = numeroPrestamo;
    document.getElementById('modalReenganche').classList.add('active');
}

function abrirModalReenganche() {
    document.getElementById('modalReenganche').classList.add('active');
}

function cerrarModalReenganche() {
    document.getElementById('modalReenganche').classList.remove('active');
    document.getElementById('formReenganche').reset();
}

async function crearReenganche(event) {
    event.preventDefault();
    
    const data = {
        prestamo_original_id: document.getElementById('prestamoIdReenganche').value,
        nuevo_monto: parseFloat(document.getElementById('nuevoMonto').value),
        nuevo_plazo: parseInt(document.getElementById('nuevoPlazo').value),
        tasa_interes_id: parseInt(document.getElementById('tasaInteresReenganche').value),
        observaciones: document.getElementById('observacionesReenganche').value
    };

    try {
        UI.showLoading('Creando reenganche...');
        const response = await api.post('/reenganche', data);
        
        if (response.success !== false) {
            UI.showAlert('Reenganche creado exitosamente', 'success');
            cerrarModalReenganche();
            cargarPrestamosElegibles();
        } else {
            UI.showAlert(response.message || 'Error al crear reenganche', 'danger');
        }
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        console.error('Error:', error);
        UI.showAlert(error.message || 'Error al crear reenganche', 'danger');
    }
}

function filtrarReenganches() {
    const busqueda = document.getElementById('buscarReenganche').value.toLowerCase();
    const filas = document.querySelectorAll('#tablaReenganches tbody tr');
    
    filas.forEach(fila => {
        const texto = fila.textContent.toLowerCase();
        fila.style.display = texto.includes(busqueda) ? '' : 'none';
    });
}

