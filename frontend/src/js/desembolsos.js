/**
 * Desembolsos - Gestión de Desembolsos
 */

document.addEventListener('DOMContentLoaded', function() {
    cargarDesembolsos();
    cargarPrestamosAprobados();
    cargarBancos();
});

async function cargarPrestamosAprobados() {
    try {
        const response = await fetch(`${API_BASE_URL}/prestamos?estado=aprobado`, {
            headers: {
                'Authorization': `Bearer ${getToken()}`
            }
        });

        if (response.ok) {
            const data = await response.json();
            const prestamos = data.data || data;
            const select = document.getElementById('prestamoId');
            
            select.innerHTML = '<option value="">Seleccionar préstamo</option>' +
                prestamos.map(p => `
                    <option value="${p.id}" data-monto="${p.monto_aprobado}">
                        ${p.numero_prestamo} - ${p.cliente_nombre} ${p.cliente_apellido} - ${formatCurrency(p.monto_aprobado)}
                    </option>
                `).join('');
        }
    } catch (error) {
        console.error('Error cargando préstamos:', error);
    }
}

async function cargarBancos() {
    try {
        const response = await fetch(`${API_BASE_URL}/bancos`, {
            headers: {
                'Authorization': `Bearer ${getToken()}`
            }
        });

        if (response.ok) {
            const bancos = await response.json();
            const selectBanco = document.getElementById('bancoDesembolso');
            const selectCheque = document.getElementById('bancoCheque');
            
            const options = bancos.map(b => `<option value="${b.id}">${b.nombre}</option>`).join('');
            selectBanco.innerHTML = '<option value="">Seleccionar banco</option>' + options;
            selectCheque.innerHTML = '<option value="">Seleccionar banco</option>' + options;
        }
    } catch (error) {
        console.error('Error cargando bancos:', error);
    }
}

function cargarDatosPrestamo() {
    const select = document.getElementById('prestamoId');
    const option = select.options[select.selectedIndex];
    const montoAprobado = option.getAttribute('data-monto');
    
    document.getElementById('montoAprobado').textContent = `Monto aprobado: ${formatCurrency(montoAprobado)}`;
    document.getElementById('montoDesembolso').value = montoAprobado;
    document.getElementById('montoDesembolso').max = montoAprobado;
}

function toggleCamposDesembolso() {
    const tipo = document.getElementById('tipoDesembolso').value;
    
    const camposTransferencia = document.getElementById('camposTransferencia');
    const camposCheque = document.getElementById('camposCheque');
    
    if (camposTransferencia) {
        camposTransferencia.style.display = 
            (tipo === 'transferencia' || tipo === 'tarjeta') ? 'block' : 'none';
    }
    
    if (camposCheque) {
        camposCheque.style.display = tipo === 'cheque' ? 'block' : 'none';
    }
}

async function cargarDesembolsos() {
    try {
        const params = new URLSearchParams();
        if (document.getElementById('filtroPrestamo').value) {
            params.append('prestamo_id', document.getElementById('filtroPrestamo').value);
        }
        if (document.getElementById('fechaDesde').value) {
            params.append('fecha_desde', document.getElementById('fechaDesde').value);
        }
        if (document.getElementById('fechaHasta').value) {
            params.append('fecha_hasta', document.getElementById('fechaHasta').value);
        }

        const response = await fetch(`${API_BASE_URL}/desembolsos?${params}`, {
            headers: {
                'Authorization': `Bearer ${getToken()}`
            }
        });

        if (response.ok) {
            const desembolsos = await response.json();
            mostrarDesembolsos(desembolsos);
        } else {
            showAlert('Error al cargar los desembolsos', 'error');
        }
    } catch (error) {
        console.error('Error cargando desembolsos:', error);
        showAlert('Error al cargar los desembolsos', 'error');
    }
}

function mostrarDesembolsos(desembolsos) {
    const tbody = document.getElementById('tablaDesembolsos');
    
    if (desembolsos.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center">No hay desembolsos registrados</td></tr>';
        return;
    }

    tbody.innerHTML = desembolsos.map(d => `
        <tr>
            <td>${d.id}</td>
            <td>${d.numero_prestamo || '-'}</td>
            <td>${formatDate(d.fecha_desembolso)}</td>
            <td><span class="badge badge-info">${d.tipo_desembolso}</span></td>
            <td>${formatCurrency(d.monto)}</td>
            <td>${d.banco || '-'}</td>
            <td>${d.usuario_nombre || ''} ${d.usuario_apellido || ''}</td>
            <td>
                <button class="btn btn-sm btn-info" onclick="verDetalleDesembolso(${d.id})">Ver</button>
            </td>
        </tr>
    `).join('');
}

function nuevoDesembolso() {
    document.getElementById('formDesembolso').reset();
    document.getElementById('modalDesembolso').style.display = 'block';
    cargarPrestamosAprobados();
}

function confirmarDesembolso() {
    const prestamoId = document.getElementById('prestamoId').value;
    const tipoDesembolso = document.getElementById('tipoDesembolso').value;
    const monto = parseFloat(document.getElementById('montoDesembolso').value);
    const observaciones = document.getElementById('observacionesDesembolso').value;

    if (!prestamoId || !tipoDesembolso || !monto || monto <= 0) {
        showAlert('Complete todos los campos requeridos', 'error');
        return;
    }

    const data = {
        prestamo_id: prestamoId,
        tipo_desembolso: tipoDesembolso,
        monto: monto,
        observaciones: observaciones
    };

    if (tipoDesembolso === 'transferencia' || tipoDesembolso === 'tarjeta') {
        data.banco = document.getElementById('bancoDesembolso').value;
        data.numero_cuenta = document.getElementById('numeroCuenta').value;
    }

    if (tipoDesembolso === 'cheque') {
        data.numero_comprobante = document.getElementById('numeroComprobante').value;
        data.banco = document.getElementById('bancoCheque').value;
    }

    fetch(`${API_BASE_URL}/desembolsos`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${getToken()}`
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(err => {
                throw new Error(err.message || 'Error al realizar el desembolso');
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success !== false) {
            showAlert('Desembolso realizado exitosamente', 'success');
            cerrarModal('modalDesembolso');
            cargarDesembolsos();
        } else {
            showAlert(data.message || 'Error al realizar el desembolso', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert(error.message || 'Error al realizar el desembolso', 'error');
    });
}

function filtrarDesembolsos() {
    cargarDesembolsos();
}

async function verDetalleDesembolso(id) {
    try {
        showLoading('Cargando detalle de desembolso...');
        const response = await fetch(`${API_BASE_URL}/desembolsos/${id}`, {
            headers: {
                'Authorization': `Bearer ${getToken()}`
            }
        });

        if (response.ok) {
            const desembolso = await response.json();
            mostrarDetalleDesembolso(desembolso);
            document.getElementById('modalDetalleDesembolso').style.display = 'block';
        } else {
            showAlert('Error al cargar el detalle del desembolso', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('Error al cargar el detalle del desembolso', 'error');
    } finally {
        hideLoading();
    }
}

function mostrarDetalleDesembolso(desembolso) {
    // Actualizar ambos elementos con el mismo ID (header y tabla)
    document.querySelectorAll('#detalleDesembolsoId').forEach(el => {
        el.textContent = desembolso.id;
    });
    document.getElementById('detalleNumeroPrestamo').textContent = desembolso.numero_prestamo || '-';
    document.getElementById('detalleFechaDesembolso').textContent = formatDate(desembolso.fecha_desembolso);
    document.getElementById('detalleTipoDesembolso').textContent = desembolso.tipo_desembolso;
    document.getElementById('detalleMonto').textContent = formatCurrency(desembolso.monto);
    document.getElementById('detalleBanco').textContent = desembolso.banco || '-';
    document.getElementById('detalleNumeroCuenta').textContent = desembolso.numero_cuenta || '-';
    document.getElementById('detalleNumeroComprobante').textContent = desembolso.numero_comprobante || '-';
    document.getElementById('detalleUsuario').textContent = `${desembolso.usuario_nombre || ''} ${desembolso.usuario_apellido || ''}`;
    document.getElementById('detalleObservaciones').textContent = desembolso.observaciones || '-';
    document.getElementById('detalleCliente').textContent = `${desembolso.cliente_nombre || ''} ${desembolso.cliente_apellido || ''}`;
}

function cerrarModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

