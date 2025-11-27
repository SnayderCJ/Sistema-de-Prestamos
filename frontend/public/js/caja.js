/**
 * Caja - Gestión de Caja
 */

let cajaAbierta = null;

// Cargar estado de caja al iniciar
document.addEventListener('DOMContentLoaded', function() {
    verificarCajaAbierta();
    cargarCajas();
});

async function verificarCajaAbierta() {
    try {
        const response = await fetch(`${API_BASE_URL}/caja/abierta`, {
            headers: {
                'Authorization': `Bearer ${getToken()}`
            }
        });

        if (response.ok) {
            cajaAbierta = await response.json();
            mostrarEstadoCaja(cajaAbierta);
            document.getElementById('btnAbrirCaja').style.display = 'none';
        } else if (response.status === 404) {
            document.getElementById('estadoCaja').style.display = 'none';
            document.getElementById('btnAbrirCaja').style.display = 'block';
        }
    } catch (error) {
        console.error('Error verificando caja:', error);
    }
}

function mostrarEstadoCaja(caja) {
    document.getElementById('estadoCaja').style.display = 'block';
    document.getElementById('montoInicial').textContent = formatCurrency(caja.monto_inicial);
    document.getElementById('montoEfectivo').textContent = formatCurrency(caja.monto_efectivo);
    document.getElementById('montoCheques').textContent = formatCurrency(caja.monto_cheques || 0);
    document.getElementById('montoTransferencias').textContent = formatCurrency(caja.monto_transferencias || 0);
    document.getElementById('fechaApertura').textContent = formatDate(caja.fecha_apertura);
    document.getElementById('usuarioCaja').textContent = `${caja.usuario_nombre || ''} ${caja.usuario_apellido || ''}`;
}

async function cargarCajas() {
    try {
        const params = new URLSearchParams();
        if (document.getElementById('fechaDesde').value) {
            params.append('fecha_desde', document.getElementById('fechaDesde').value);
        }
        if (document.getElementById('fechaHasta').value) {
            params.append('fecha_hasta', document.getElementById('fechaHasta').value);
        }
        if (document.getElementById('filtroEstado').value) {
            params.append('estado', document.getElementById('filtroEstado').value);
        }

        const response = await fetch(`${API_BASE_URL}/caja?${params}`, {
            headers: {
                'Authorization': `Bearer ${getToken()}`
            }
        });

        if (response.ok) {
            const cajas = await response.json();
            mostrarCajas(cajas);
        } else {
            showAlert('Error al cargar las cajas', 'error');
        }
    } catch (error) {
        console.error('Error cargando cajas:', error);
        showAlert('Error al cargar las cajas', 'error');
    }
}

function mostrarCajas(cajas) {
    const tbody = document.getElementById('tablaCajas');
    
    if (cajas.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center">No hay cajas registradas</td></tr>';
        return;
    }

    tbody.innerHTML = cajas.map(caja => `
        <tr>
            <td>${caja.id}</td>
            <td>${formatDate(caja.fecha_apertura)}</td>
            <td>${caja.fecha_cierre ? formatDate(caja.fecha_cierre) : '-'}</td>
            <td>${formatCurrency(caja.monto_inicial)}</td>
            <td>${caja.monto_final ? formatCurrency(caja.monto_final) : '-'}</td>
            <td><span class="badge ${caja.estado === 'abierta' ? 'badge-success' : 'badge-secondary'}">${caja.estado}</span></td>
            <td>${caja.usuario_nombre || ''} ${caja.usuario_apellido || ''}</td>
            <td>
                <button class="btn btn-sm btn-info" onclick="verDetalleCaja(${caja.id})">Ver</button>
            </td>
        </tr>
    `).join('');
}

function abrirCaja() {
    document.getElementById('formAbrirCaja').reset();
    document.getElementById('modalAbrirCaja').style.display = 'block';
}

function confirmarAbrirCaja() {
    const montoInicial = parseFloat(document.getElementById('montoInicialInput').value);
    const observaciones = document.getElementById('observacionesApertura').value;

    if (!montoInicial || montoInicial < 0) {
        showAlert('El monto inicial debe ser mayor a 0', 'error');
        return;
    }

    fetch(`${API_BASE_URL}/caja/abrir`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${getToken()}`
        },
        body: JSON.stringify({
            monto_inicial: montoInicial,
            observaciones: observaciones
        })
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(err => {
                throw new Error(err.message || 'Error al abrir la caja');
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success !== false) {
            showAlert('Caja abierta exitosamente', 'success');
            cerrarModal('modalAbrirCaja');
            verificarCajaAbierta();
            cargarCajas();
        } else {
            showAlert(data.message || 'Error al abrir la caja', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert(error.message || 'Error al abrir la caja', 'error');
    });
}

function cerrarCaja() {
    if (!cajaAbierta) {
        showAlert('No hay caja abierta', 'error');
        return;
    }

    document.getElementById('formCerrarCaja').reset();
    document.getElementById('montoFinalEfectivo').value = cajaAbierta.monto_efectivo || 0;
    document.getElementById('modalCerrarCaja').style.display = 'block';
}

function confirmarCerrarCaja() {
    if (!cajaAbierta) {
        showAlert('No hay caja abierta', 'error');
        return;
    }

    const montoFinal = parseFloat(document.getElementById('montoFinalEfectivo').value);
    const montoCheques = parseFloat(document.getElementById('montoChequesCierre').value) || 0;
    const montoTransferencias = parseFloat(document.getElementById('montoTransferenciasCierre').value) || 0;
    const montoTarjetas = parseFloat(document.getElementById('montoTarjetasCierre').value) || 0;
    const observaciones = document.getElementById('observacionesCierre').value;

    if (!montoFinal || montoFinal < 0) {
        showAlert('El monto final debe ser mayor a 0', 'error');
        return;
    }

    fetch(`${API_BASE_URL}/caja/cerrar?id=${cajaAbierta.id}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${getToken()}`
        },
        body: JSON.stringify({
            monto_final: montoFinal,
            monto_efectivo: montoFinal,
            monto_cheques: montoCheques,
            monto_transferencias: montoTransferencias,
            monto_tarjetas: montoTarjetas,
            observaciones: observaciones
        })
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(err => {
                throw new Error(err.message || 'Error al cerrar la caja');
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success !== false) {
            showAlert('Caja cerrada exitosamente', 'success');
            cerrarModal('modalCerrarCaja');
            cajaAbierta = null;
            verificarCajaAbierta();
            cargarCajas();
        } else {
            showAlert(data.message || 'Error al cerrar la caja', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert(error.message || 'Error al cerrar la caja', 'error');
    });
}

function filtrarCajas() {
    cargarCajas();
}

async function verDetalleCaja(id) {
    try {
        showLoading('Cargando detalle de caja...');
        const response = await fetch(`${API_BASE_URL}/caja/${id}`, {
            headers: {
                'Authorization': `Bearer ${getToken()}`
            }
        });

        if (response.ok) {
            const caja = await response.json();
            mostrarDetalleCaja(caja);
            document.getElementById('modalDetalleCaja').style.display = 'block';
        } else {
            showAlert('Error al cargar el detalle de la caja', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('Error al cargar el detalle de la caja', 'error');
    } finally {
        hideLoading();
    }
}

function mostrarDetalleCaja(caja) {
    document.getElementById('detalleCajaId').textContent = caja.id;
    document.getElementById('detalleFechaApertura').textContent = formatDate(caja.fecha_apertura);
    document.getElementById('detalleFechaCierre').textContent = caja.fecha_cierre ? formatDate(caja.fecha_cierre) : '-';
    document.getElementById('detalleMontoInicial').textContent = formatCurrency(caja.monto_inicial);
    document.getElementById('detalleMontoFinal').textContent = caja.monto_final ? formatCurrency(caja.monto_final) : '-';
    document.getElementById('detalleMontoEfectivo').textContent = formatCurrency(caja.monto_efectivo || 0);
    document.getElementById('detalleMontoCheques').textContent = formatCurrency(caja.monto_cheques || 0);
    document.getElementById('detalleMontoTransferencias').textContent = formatCurrency(caja.monto_transferencias || 0);
    document.getElementById('detalleMontoTarjetas').textContent = formatCurrency(caja.monto_tarjetas || 0);
    document.getElementById('detalleEstado').textContent = caja.estado;
    document.getElementById('detalleEstado').className = `badge ${caja.estado === 'abierta' ? 'badge-success' : 'badge-secondary'}`;
    document.getElementById('detalleUsuario').textContent = `${caja.usuario_nombre || ''} ${caja.usuario_apellido || ''}`;
    document.getElementById('detalleObservaciones').textContent = caja.observaciones || '-';
    
    if (caja.monto_final && caja.monto_inicial) {
        const diferencia = caja.monto_final - caja.monto_inicial;
        document.getElementById('detalleDiferencia').textContent = formatCurrency(diferencia);
        document.getElementById('detalleDiferencia').className = diferencia >= 0 ? 'text-success' : 'text-danger';
    } else {
        document.getElementById('detalleDiferencia').textContent = '-';
    }
}

