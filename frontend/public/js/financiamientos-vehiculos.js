/**
 * Financiamientos de Vehículos
 */

document.addEventListener('DOMContentLoaded', function() {
    cargarFinanciamientos();
    cargarClientes();
    cargarBancos();
    cargarMonedas();
});

async function cargarClientes() {
    try {
        const response = await fetch(`${API_BASE_URL}/clientes`, {
            headers: {
                'Authorization': `Bearer ${getToken()}`
            }
        });

        if (response.ok) {
            const data = await response.json();
            const clientes = data.data || data;
            const select = document.getElementById('clienteId');
            
            select.innerHTML = '<option value="">Seleccionar cliente</option>' +
                clientes.map(c => `
                    <option value="${c.id}">
                        ${c.cedula} - ${c.nombre} ${c.apellido}
                    </option>
                `).join('');
        }
    } catch (error) {
        console.error('Error cargando clientes:', error);
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
            const select = document.getElementById('bancoId');
            
            select.innerHTML = '<option value="">Seleccionar banco</option>' +
                bancos.map(b => `<option value="${b.id}">${b.nombre}</option>`).join('');
        }
    } catch (error) {
        console.error('Error cargando bancos:', error);
    }
}

async function cargarMonedas() {
    try {
        const response = await fetch(`${API_BASE_URL}/monedas`, {
            headers: {
                'Authorization': `Bearer ${getToken()}`
            }
        });

        if (response.ok) {
            const monedas = await response.json();
            const select = document.getElementById('monedaId');
            
            select.innerHTML = monedas.map(m => 
                `<option value="${m.id}">${m.codigo} - ${m.nombre}</option>`
            ).join('');
        }
    } catch (error) {
        console.error('Error cargando monedas:', error);
    }
}

function cambiarTab(tabName, buttonElement) {
    // Ocultar todos los tabs
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Mostrar tab seleccionado
    const tabContent = document.getElementById(`tab-${tabName}`);
    if (tabContent) {
        tabContent.classList.add('active');
    }
    
    // Activar botón
    if (buttonElement) {
        buttonElement.classList.add('active');
    }
}

function calcularCuota() {
    const valorFinanciado = parseFloat(document.getElementById('valorFinanciado').value) || 0;
    const montoInicial = parseFloat(document.getElementById('montoInicial').value) || 0;
    const monto = valorFinanciado - montoInicial;
    const plazo = parseInt(document.getElementById('plazoMeses').value) || 0;
    const tasa = parseFloat(document.getElementById('tasaInteres').value) || 0;

    if (monto > 0 && plazo > 0 && tasa >= 0) {
        const cuota = calcularCuotaMensual(monto, tasa, plazo);
        document.getElementById('cuotaMensual').value = cuota.toFixed(2);
    }
}

function calcularCuotaMensual(monto, tasaMensual, plazo) {
    if (tasaMensual == 0) {
        return monto / plazo;
    }
    
    const tasaDecimal = tasaMensual / 100;
    const factor = Math.pow(1 + tasaDecimal, plazo);
    const cuota = monto * (tasaDecimal * factor) / (factor - 1);
    
    return cuota;
}

async function cargarFinanciamientos() {
    try {
        const params = new URLSearchParams();
        if (document.getElementById('filtroTipo').value) {
            params.append('tipo_financiamiento', document.getElementById('filtroTipo').value);
        }
        if (document.getElementById('filtroEstado').value) {
            params.append('estado', document.getElementById('filtroEstado').value);
        }

        const response = await fetch(`${API_BASE_URL}/financiamientos-vehiculos?${params}`, {
            headers: {
                'Authorization': `Bearer ${getToken()}`
            }
        });

        if (response.ok) {
            const financiamientos = await response.json();
            mostrarFinanciamientos(financiamientos);
        } else {
            showAlert('Error al cargar los financiamientos', 'error');
        }
    } catch (error) {
        console.error('Error cargando financiamientos:', error);
        showAlert('Error al cargar los financiamientos', 'error');
    }
}

function mostrarFinanciamientos(financiamientos) {
    const tbody = document.getElementById('tablaFinanciamientos');
    
    if (financiamientos.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center">No hay financiamientos registrados</td></tr>';
        return;
    }

    tbody.innerHTML = financiamientos.map(f => `
        <tr>
            <td>${f.numero_financiamiento}</td>
            <td>${f.cliente_nombre || ''} ${f.cliente_apellido || ''}</td>
            <td>${f.marca} ${f.modelo} ${f.ano}</td>
            <td><span class="badge badge-info">${f.tipo_financiamiento}</span></td>
            <td>${formatCurrency(f.valor_financiado)}</td>
            <td>${formatCurrency(f.cuota_mensual)}</td>
            <td><span class="badge badge-${getEstadoBadge(f.estado)}">${f.estado}</span></td>
            <td>
                <button class="btn btn-sm btn-info" onclick="verDetalleFinanciamiento(${f.id})">Ver</button>
            </td>
        </tr>
    `).join('');
}

function getEstadoBadge(estado) {
    const badges = {
        'pendiente': 'warning',
        'aprobado': 'info',
        'vigente': 'success',
        'vencido': 'danger',
        'cancelado': 'secondary',
        'pagado': 'success'
    };
    return badges[estado] || 'secondary';
}

function nuevoFinanciamiento() {
    document.getElementById('formFinanciamiento').reset();
    cambiarTab('datos-cliente');
    document.getElementById('modalFinanciamiento').style.display = 'block';
    cargarClientes();
    cargarBancos();
    cargarMonedas();
}

function confirmarFinanciamiento() {
    const clienteId = document.getElementById('clienteId').value;
    const tipoFinanciamiento = document.getElementById('tipoFinanciamiento').value;
    const marca = document.getElementById('marca').value;
    const modelo = document.getElementById('modelo').value;
    const ano = document.getElementById('ano').value;
    const numeroChasis = document.getElementById('numeroChasis').value;
    const valorFinanciado = parseFloat(document.getElementById('valorFinanciado').value);

    if (!clienteId || !tipoFinanciamiento || !marca || !modelo || !ano || !numeroChasis || !valorFinanciado) {
        showAlert('Complete todos los campos requeridos', 'error');
        return;
    }

    const data = {
        cliente_id: clienteId,
        tipo_financiamiento: tipoFinanciamiento,
        marca: marca,
        modelo: modelo,
        ano: parseInt(ano),
        color: document.getElementById('color').value,
        numero_chasis: numeroChasis,
        numero_motor: document.getElementById('numeroMotor').value,
        numero_placa: document.getElementById('numeroPlaca').value,
        kilometraje: parseInt(document.getElementById('kilometraje').value) || null,
        valor_comercial: parseFloat(document.getElementById('valorComercial').value) || valorFinanciado,
        valor_financiado: valorFinanciado,
        monto_inicial: parseFloat(document.getElementById('montoInicial').value) || 0,
        plazo_meses: parseInt(document.getElementById('plazoMeses').value),
        tasa_interes: parseFloat(document.getElementById('tasaInteres').value),
        cuota_mensual: parseFloat(document.getElementById('cuotaMensual').value),
        seguro_afiliado: document.getElementById('seguroAfiliado').value,
        numero_poliza_seguro: document.getElementById('numeroPolizaSeguro').value,
        fecha_vencimiento_seguro: document.getElementById('fechaVencimientoSeguro').value,
        tasador: document.getElementById('tasador').value,
        fecha_tasacion: document.getElementById('fechaTasacion').value,
        valor_tasacion: parseFloat(document.getElementById('valorTasacion').value) || null,
        banco_id: document.getElementById('bancoId').value || null,
        moneda_id: document.getElementById('monedaId').value,
        observaciones: document.getElementById('observaciones').value
    };

    fetch(`${API_BASE_URL}/financiamientos-vehiculos`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${getToken()}`
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Financiamiento creado exitosamente', 'success');
            cerrarModal('modalFinanciamiento');
            cargarFinanciamientos();
        } else {
            showAlert(data.message || 'Error al crear el financiamiento', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error al crear el financiamiento', 'error');
    });
}

function filtrarFinanciamientos() {
    cargarFinanciamientos();
}

async function verDetalleFinanciamiento(id) {
    try {
        showLoading('Cargando detalle de financiamiento...');
        const response = await fetch(`${API_BASE_URL}/financiamientos-vehiculos/${id}`, {
            headers: {
                'Authorization': `Bearer ${getToken()}`
            }
        });

        if (response.ok) {
            const financiamiento = await response.json();
            mostrarDetalleFinanciamiento(financiamiento);
            document.getElementById('modalDetalleFinanciamiento').style.display = 'block';
        } else {
            showAlert('Error al cargar el detalle del financiamiento', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('Error al cargar el detalle del financiamiento', 'error');
    } finally {
        hideLoading();
    }
}

function mostrarDetalleFinanciamiento(financiamiento) {
    // Actualizar ambos elementos con el mismo ID (header y tabla)
    document.querySelectorAll('#detalleFinanciamientoId').forEach(el => {
        el.textContent = financiamiento.id;
    });
    document.getElementById('detalleFinanciamientoCliente').textContent = `${financiamiento.cliente_nombre || ''} ${financiamiento.cliente_apellido || ''}`;
    document.getElementById('detalleFinanciamientoBanco').textContent = financiamiento.banco_nombre || '-';
    document.getElementById('detalleFinanciamientoMoneda').textContent = financiamiento.moneda_codigo || '-';
    document.getElementById('detalleFinanciamientoMarca').textContent = financiamiento.marca || '-';
    document.getElementById('detalleFinanciamientoModelo').textContent = financiamiento.modelo || '-';
    document.getElementById('detalleFinanciamientoAno').textContent = financiamiento.ano || '-';
    document.getElementById('detalleFinanciamientoPlaca').textContent = financiamiento.placa || '-';
    document.getElementById('detalleFinanciamientoChasis').textContent = financiamiento.numero_chasis || '-';
    document.getElementById('detalleFinanciamientoMonto').textContent = formatCurrency(financiamiento.monto_financiamiento || 0);
    document.getElementById('detalleFinanciamientoTasa').textContent = `${financiamiento.tasa_interes || 0}%`;
    document.getElementById('detalleFinanciamientoPlazo').textContent = `${financiamiento.plazo_meses || 0} meses`;
    document.getElementById('detalleFinanciamientoCuota').textContent = formatCurrency(financiamiento.cuota_mensual || 0);
    document.getElementById('detalleFinanciamientoValorTasacion').textContent = formatCurrency(financiamiento.valor_tasacion || 0);
    document.getElementById('detalleFinanciamientoFechaTasacion').textContent = financiamiento.fecha_tasacion ? formatDate(financiamiento.fecha_tasacion) : '-';
    document.getElementById('detalleFinanciamientoObservaciones').textContent = financiamiento.observaciones || '-';
    document.getElementById('detalleFinanciamientoFecha').textContent = formatDate(financiamiento.fecha_financiamiento);
}

function cerrarModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

function cargarDatosCliente() {
    // Implementar si es necesario
}

