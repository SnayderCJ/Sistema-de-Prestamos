/**
 * Gestión de Socios de Cooperativa
 */

let cooperativaId = null;
let socios = [];

document.addEventListener('DOMContentLoaded', function() {
    // Obtener ID de cooperativa de la URL
    const urlParams = new URLSearchParams(window.location.search);
    cooperativaId = urlParams.get('cooperativa_id');
    
    if (!cooperativaId) {
        showAlert('ID de cooperativa no especificado', 'error');
        window.location.href = 'cooperativas.html';
        return;
    }
    
    cargarDatos();
    
    document.getElementById('formSocio').addEventListener('submit', function(e) {
        e.preventDefault();
        guardarSocio();
    });
    
    document.getElementById('formApartacion').addEventListener('submit', function(e) {
        e.preventDefault();
        guardarApartacion();
    });
    
    document.getElementById('formDistribucion').addEventListener('submit', function(e) {
        e.preventDefault();
        guardarDistribucion();
    });
});

async function cargarDatos() {
    await Promise.all([
        cargarCooperativa(),
        cargarSocios(),
        cargarEstadisticas()
    ]);
}

async function cargarCooperativa() {
    try {
        const response = await fetch(`${API_BASE_URL}/cooperativas/${cooperativaId}`, {
            headers: {
                'Authorization': `Bearer ${getToken()}`
            }
        });

        if (response.ok) {
            const cooperativa = await response.json();
            document.getElementById('tituloCooperativa').textContent = `🤝 Socios - ${cooperativa.nombre}`;
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

async function cargarSocios() {
    try {
        const response = await fetch(`${API_BASE_URL}/cooperativas/socios/${cooperativaId}`, {
            headers: {
                'Authorization': `Bearer ${getToken()}`
            }
        });

        if (response.ok) {
            socios = await response.json();
            mostrarSocios(socios);
            actualizarSelectSocios();
        } else {
            showAlert('Error al cargar los socios', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('Error al cargar los socios', 'error');
    }
}

function mostrarSocios(sociosList) {
    const tbody = document.getElementById('tablaSocios');
    
    if (sociosList.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center">No hay socios registrados</td></tr>';
        return;
    }

    tbody.innerHTML = sociosList.map(s => `
        <tr>
            <td>${s.cedula}</td>
            <td><strong>${s.nombre} ${s.apellido}</strong></td>
            <td>${formatCurrency(s.total_apartaciones || 0)}</td>
            <td>${s.porcentaje_utilidad ? s.porcentaje_utilidad + '%' : '-'}</td>
            <td>${s.ultima_apartacion ? formatDate(s.ultima_apartacion) : '-'}</td>
            <td>
                <span class="badge badge-${s.activo ? 'success' : 'secondary'}">
                    ${s.activo ? 'Activo' : 'Inactivo'}
                </span>
            </td>
            <td>
                <button class="btn btn-sm btn-info" onclick="verApartaciones(${s.id})">Apartaciones</button>
                <button class="btn btn-sm btn-warning" onclick="editarSocio(${s.id})">Editar</button>
            </td>
        </tr>
    `).join('');
}

function actualizarSelectSocios() {
    const select = document.getElementById('apartacionSocioId');
    select.innerHTML = '<option value="">Seleccionar socio</option>' +
        socios.filter(s => s.activo).map(s => `
            <option value="${s.id}">${s.cedula} - ${s.nombre} ${s.apellido}</option>
        `).join('');
}

async function cargarEstadisticas() {
    try {
        const response = await fetch(`${API_BASE_URL}/cooperativas/${cooperativaId}`, {
            headers: {
                'Authorization': `Bearer ${getToken()}`
            }
        });

        if (response.ok) {
            const cooperativa = await response.json();
            document.getElementById('totalSocios').textContent = cooperativa.total_socios || 0;
            document.getElementById('totalApartaciones').textContent = formatCurrency(cooperativa.total_apartaciones || 0);
            
            const sociosConUtilidad = socios.filter(s => s.porcentaje_utilidad && s.porcentaje_utilidad > 0).length;
            document.getElementById('sociosConUtilidad').textContent = sociosConUtilidad;
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

function abrirModalSocio() {
    document.getElementById('formSocio').reset();
    document.getElementById('socioFechaIngreso').value = new Date().toISOString().split('T')[0];
    document.getElementById('modalSocio').style.display = 'block';
}

async function guardarSocio() {
    const data = {
        cedula: document.getElementById('socioCedula').value,
        nombre: document.getElementById('socioNombre').value,
        apellido: document.getElementById('socioApellido').value,
        telefono: document.getElementById('socioTelefono').value,
        email: document.getElementById('socioEmail').value,
        direccion: document.getElementById('socioDireccion').value,
        fecha_ingreso: document.getElementById('socioFechaIngreso').value,
        porcentaje_utilidad: document.getElementById('socioPorcentajeUtilidad').value || null,
        observaciones: document.getElementById('socioObservaciones').value
    };

    if (!data.cedula || !data.nombre) {
        showAlert('Cédula y nombre son requeridos', 'error');
        return;
    }

    try {
        showLoading('Guardando socio...');
        const response = await fetch(`${API_BASE_URL}/cooperativas/socio/${cooperativaId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${getToken()}`
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (response.ok) {
            showAlert('Socio agregado correctamente', 'success');
            cerrarModal('modalSocio');
            cargarDatos();
        } else {
            showAlert(result.message || 'Error al guardar el socio', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('Error al guardar el socio', 'error');
    } finally {
        hideLoading();
    }
}

function abrirModalApartacion() {
    if (socios.length === 0) {
        showAlert('Debe agregar al menos un socio primero', 'warning');
        return;
    }
    document.getElementById('formApartacion').reset();
    document.getElementById('apartacionFecha').value = new Date().toISOString().split('T')[0];
    document.getElementById('modalApartacion').style.display = 'block';
}

async function guardarApartacion() {
    const data = {
        socio_id: parseInt(document.getElementById('apartacionSocioId').value),
        fecha_apartacion: document.getElementById('apartacionFecha').value,
        monto: parseFloat(document.getElementById('apartacionMonto').value),
        tipo_apartacion: document.getElementById('apartacionTipo').value,
        metodo_pago: document.getElementById('apartacionMetodoPago').value,
        numero_comprobante: document.getElementById('apartacionComprobante').value,
        observaciones: document.getElementById('apartacionObservaciones').value
    };

    if (!data.socio_id || !data.monto || data.monto <= 0) {
        showAlert('Complete todos los campos requeridos', 'error');
        return;
    }

    try {
        showLoading('Registrando apartación...');
        const response = await fetch(`${API_BASE_URL}/cooperativas/apartacion/${cooperativaId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${getToken()}`
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (response.ok && result.success) {
            showAlert('Apartación registrada correctamente', 'success');
            cerrarModal('modalApartacion');
            cargarDatos();
        } else {
            showAlert(result.message || 'Error al registrar la apartación', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('Error al registrar la apartación', 'error');
    } finally {
        hideLoading();
    }
}

function abrirModalDistribucion() {
    document.getElementById('formDistribucion').reset();
    const hoy = new Date();
    const mes = String(hoy.getMonth() + 1).padStart(2, '0');
    document.getElementById('distribucionPeriodo').value = `${hoy.getFullYear()}-${mes}`;
    document.getElementById('resultadoDistribucion').style.display = 'none';
    document.getElementById('modalDistribucion').style.display = 'block';
}

async function calcularDistribucion() {
    const periodo = document.getElementById('distribucionPeriodo').value;
    const monto = parseFloat(document.getElementById('distribucionMonto').value);
    const metodo = document.getElementById('distribucionMetodo').value;

    if (!periodo || !monto || monto <= 0) {
        showAlert('Complete período y monto', 'error');
        return;
    }

    try {
        showLoading('Calculando distribución...');
        const response = await fetch(`${API_BASE_URL}/cooperativas/calcular-distribucion/${cooperativaId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${getToken()}`
            },
            body: JSON.stringify({
                periodo: periodo,
                monto_total_utilidad: monto,
                metodo_distribucion: metodo
            })
        });

        const result = await response.json();

        if (response.ok) {
            mostrarResultadoDistribucion(result);
        } else {
            showAlert(result.message || 'Error al calcular la distribución', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('Error al calcular la distribución', 'error');
    } finally {
        hideLoading();
    }
}

function mostrarResultadoDistribucion(distribucion) {
    const tbody = document.getElementById('tablaDistribucion');
    
    if (!distribucion.detalles || distribucion.detalles.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center">No hay detalles de distribución</td></tr>';
        return;
    }

    tbody.innerHTML = distribucion.detalles.map(d => `
        <tr>
            <td><strong>${d.socio_nombre} ${d.socio_apellido}</strong><br><small>${d.cedula}</small></td>
            <td>${formatCurrency(d.monto_apartaciones_periodo || 0)}</td>
            <td>${d.porcentaje_asignado ? d.porcentaje_asignado.toFixed(2) + '%' : '-'}</td>
            <td><strong>${formatCurrency(d.monto_utilidad || 0)}</strong></td>
        </tr>
    `).join('');
    
    // Agregar total
    const total = distribucion.detalles.reduce((sum, d) => sum + parseFloat(d.monto_utilidad || 0), 0);
    tbody.innerHTML += `
        <tr class="table-info">
            <td colspan="3"><strong>TOTAL</strong></td>
            <td><strong>${formatCurrency(total)}</strong></td>
        </tr>
    `;
    
    document.getElementById('resultadoDistribucion').style.display = 'block';
}

async function guardarDistribucion() {
    const periodo = document.getElementById('distribucionPeriodo').value;
    const monto = parseFloat(document.getElementById('distribucionMonto').value);
    const metodo = document.getElementById('distribucionMetodo').value;

    if (!periodo || !monto || monto <= 0) {
        showAlert('Complete período y monto', 'error');
        return;
    }

    try {
        showLoading('Guardando distribución...');
        const response = await fetch(`${API_BASE_URL}/cooperativas/calcular-distribucion/${cooperativaId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${getToken()}`
            },
            body: JSON.stringify({
                periodo: periodo,
                monto_total_utilidad: monto,
                metodo_distribucion: metodo
            })
        });

        const result = await response.json();

        if (response.ok) {
            showAlert('Distribución guardada correctamente', 'success');
            cerrarModal('modalDistribucion');
            window.location.href = `cooperativa-distribuciones.html?cooperativa_id=${cooperativaId}`;
        } else {
            showAlert(result.message || 'Error al guardar la distribución', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('Error al guardar la distribución', 'error');
    } finally {
        hideLoading();
    }
}

async function verApartaciones(socioId) {
    window.location.href = `cooperativa-apartaciones.html?socio_id=${socioId}`;
}

async function editarSocio(socioId) {
    const socio = socios.find(s => s.id === socioId);
    if (!socio) return;
    
    document.getElementById('socioCedula').value = socio.cedula;
    document.getElementById('socioNombre').value = socio.nombre;
    document.getElementById('socioApellido').value = socio.apellido || '';
    document.getElementById('socioTelefono').value = socio.telefono || '';
    document.getElementById('socioEmail').value = socio.email || '';
    document.getElementById('socioDireccion').value = socio.direccion || '';
    document.getElementById('socioFechaIngreso').value = socio.fecha_ingreso || '';
    document.getElementById('socioPorcentajeUtilidad').value = socio.porcentaje_utilidad || '';
    document.getElementById('socioObservaciones').value = socio.observaciones || '';
    
    document.getElementById('modalSocio').style.display = 'block';
    // TODO: Implementar actualización de socio
}

