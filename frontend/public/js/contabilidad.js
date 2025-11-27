/**
 * Contabilidad - Gestión de Asientos Contables
 */

document.addEventListener('DOMContentLoaded', function() {
    cargarAsientos();
    
    // Establecer fecha actual por defecto
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('fechaAsiento').value = today;
});

function validarAsiento() {
    const debe = parseFloat(document.getElementById('debe').value) || 0;
    const haber = parseFloat(document.getElementById('haber').value) || 0;
    const alerta = document.getElementById('alertaAsiento');
    
    if (debe === 0 && haber === 0) {
        alerta.style.display = 'block';
    } else {
        alerta.style.display = 'none';
    }
}

async function cargarAsientos() {
    try {
        const params = new URLSearchParams();
        if (document.getElementById('fechaDesde').value) {
            params.append('fecha_desde', document.getElementById('fechaDesde').value);
        }
        if (document.getElementById('fechaHasta').value) {
            params.append('fecha_hasta', document.getElementById('fechaHasta').value);
        }
        if (document.getElementById('filtroCuenta').value) {
            params.append('cuenta_contable', document.getElementById('filtroCuenta').value);
        }
        if (document.getElementById('filtroTipo').value) {
            params.append('tipo', document.getElementById('filtroTipo').value);
        }

        const response = await fetch(`${API_BASE_URL}/contabilidad/asientos?${params}`, {
            headers: {
                'Authorization': `Bearer ${getToken()}`
            }
        });

        if (response.ok) {
            const asientos = await response.json();
            mostrarAsientos(asientos);
        } else {
            showAlert('Error al cargar los asientos', 'error');
        }
    } catch (error) {
        console.error('Error cargando asientos:', error);
        showAlert('Error al cargar los asientos', 'error');
    }
}

function mostrarAsientos(asientos) {
    const tbody = document.getElementById('tablaAsientos');
    
    if (asientos.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center">No hay asientos contables</td></tr>';
        return;
    }

    tbody.innerHTML = asientos.map(a => `
        <tr>
            <td><strong>${a.numero_asiento}</strong></td>
            <td>${formatDate(a.fecha)}</td>
            <td><span class="badge badge-info">${a.tipo}</span></td>
            <td>${a.concepto}</td>
            <td>${a.cuenta_contable}</td>
            <td>${a.debe > 0 ? formatCurrency(a.debe) : '-'}</td>
            <td>${a.haber > 0 ? formatCurrency(a.haber) : '-'}</td>
            <td>${a.usuario_nombre || '-'}</td>
            <td>
                <button class="btn btn-sm btn-info" onclick="verDetalleAsiento(${a.id})">Ver</button>
            </td>
        </tr>
    `).join('');
}

function nuevoAsiento() {
    document.getElementById('formAsiento').reset();
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('fechaAsiento').value = today;
    document.getElementById('debe').value = 0;
    document.getElementById('haber').value = 0;
    
    const alerta = document.getElementById('alertaAsiento');
    if (alerta) {
        alerta.style.display = 'none';
    }
    
    document.getElementById('modalAsiento').style.display = 'block';
    
    // Agregar event listeners para validación en tiempo real
    ['debe', 'haber'].forEach(id => {
        const field = document.getElementById(id);
        if (field) {
            field.addEventListener('input', validarAsiento);
            field.addEventListener('change', validarAsiento);
        }
    });
}

function confirmarAsiento() {
    const fecha = document.getElementById('fechaAsiento').value;
    const tipo = document.getElementById('tipoAsiento').value;
    const concepto = document.getElementById('concepto').value;
    const cuentaContable = document.getElementById('cuentaContable').value;
    const debe = parseFloat(document.getElementById('debe').value) || 0;
    const haber = parseFloat(document.getElementById('haber').value) || 0;
    const referencia = document.getElementById('referencia').value;

    if (!fecha || !tipo || !concepto || !cuentaContable) {
        showAlert('Complete todos los campos requeridos', 'error');
        return;
    }

    if (debe === 0 && haber === 0) {
        showAlert('Debe o Haber debe tener un valor mayor a 0', 'error');
        return;
    }

    const data = {
        fecha: fecha,
        tipo: tipo,
        concepto: concepto,
        cuenta_contable: cuentaContable,
        debe: debe,
        haber: haber,
        referencia: referencia || null
    };

    fetch(`${API_BASE_URL}/contabilidad/asiento`, {
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
                throw new Error(err.message || 'Error al crear el asiento');
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success !== false) {
            showAlert('Asiento contable creado exitosamente', 'success');
            cerrarModal('modalAsiento');
            cargarAsientos();
        } else {
            showAlert(data.message || 'Error al crear el asiento', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert(error.message || 'Error al crear el asiento', 'error');
    });
}

function filtrarAsientos() {
    cargarAsientos();
}

async function verDetalleAsiento(id) {
    try {
        showLoading('Cargando detalle de asiento...');
        const response = await fetch(`${API_BASE_URL}/contabilidad/asiento/${id}`, {
            headers: {
                'Authorization': `Bearer ${getToken()}`
            }
        });

        if (response.ok) {
            const asiento = await response.json();
            mostrarDetalleAsiento(asiento);
            document.getElementById('modalDetalleAsiento').style.display = 'block';
        } else {
            showAlert('Error al cargar el detalle del asiento', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('Error al cargar el detalle del asiento', 'error');
    } finally {
        hideLoading();
    }
}

function mostrarDetalleAsiento(asiento) {
    // Actualizar ambos elementos con el mismo ID (header y tabla)
    const numero = asiento.numero || asiento.id;
    document.querySelectorAll('#detalleAsientoNumero').forEach(el => {
        el.textContent = numero;
    });
    document.getElementById('detalleAsientoFecha').textContent = formatDate(asiento.fecha);
    document.getElementById('detalleAsientoTipo').textContent = asiento.tipo;
    document.getElementById('detalleAsientoConcepto').textContent = asiento.concepto || '-';
    document.getElementById('detalleAsientoCuenta').textContent = asiento.cuenta_contable || '-';
    document.getElementById('detalleAsientoDebe').textContent = formatCurrency(asiento.debe || 0);
    document.getElementById('detalleAsientoHaber').textContent = formatCurrency(asiento.haber || 0);
    document.getElementById('detalleAsientoReferencia').textContent = asiento.referencia || '-';
    document.getElementById('detalleAsientoUsuario').textContent = `${asiento.usuario_nombre || ''} ${asiento.usuario_apellido || ''}`;
}

