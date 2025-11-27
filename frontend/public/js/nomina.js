/**
 * Nómina - Gestión de Nómina
 */

let calculosNomina = null;

document.addEventListener('DOMContentLoaded', function() {
    cargarNomina();
    cargarEmpleados();
    
    // Establecer período actual por defecto
    const today = new Date();
    const periodo = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}`;
    document.getElementById('filtroPeriodo').value = periodo;
});

async function cargarEmpleados() {
    try {
        const response = await fetch(`${API_BASE_URL}/empleados`, {
            headers: {
                'Authorization': `Bearer ${getToken()}`
            }
        });

        if (response.ok) {
            const empleados = await response.json();
            const select = document.getElementById('empleadoId');
            const filtro = document.getElementById('filtroEmpleado');
            
            const options = empleados.map(e => `
                <option value="${e.id}" data-salario="${e.salario_base || 0}">
                    ${e.nombre || ''} ${e.apellido || ''} - ${e.cargo || ''}
                </option>
            `).join('');
            
            if (select) {
                select.innerHTML = '<option value="">Seleccionar empleado</option>' + options;
                select.addEventListener('change', calcularNomina);
            }
            if (filtro) {
                filtro.innerHTML = '<option value="">Todos los empleados</option>' + options;
            }
        }
    } catch (error) {
        console.error('Error cargando empleados:', error);
    }
}

async function calcularNomina() {
    const empleadoId = document.getElementById('empleadoId').value;
    const periodo = document.getElementById('periodo').value;
    
    if (!empleadoId || !periodo) {
        // Calcular con datos del formulario directamente
        actualizarResumen();
        return;
    }
    
    try {
        const response = await fetch(`${API_BASE_URL}/nomina/calcular?empleado_id=${empleadoId}&periodo=${periodo}`, {
            headers: {
                'Authorization': `Bearer ${getToken()}`
            }
        });

        if (response.ok) {
            calculosNomina = await response.json();
            actualizarResumen();
        } else {
            // Si falla, calcular con datos del formulario
            actualizarResumen();
        }
    } catch (error) {
        console.error('Error calculando nómina:', error);
        // Calcular con datos del formulario
        actualizarResumen();
    }
}

function actualizarResumen() {
    const empleadoId = document.getElementById('empleadoId').value;
    if (!empleadoId) return;
    
    // Obtener salario base del empleado seleccionado
    const select = document.getElementById('empleadoId');
    const option = select.options[select.selectedIndex];
    const salarioBase = parseFloat(option.getAttribute('data-salario')) || 0;
    
    const horasExtras = parseFloat(document.getElementById('horasExtras').value) || 0;
    const bonos = parseFloat(document.getElementById('bonos').value) || 0;
    const comisiones = parseFloat(document.getElementById('comisiones').value) || 0;
    const otrosIngresos = parseFloat(document.getElementById('otrosIngresos').value) || 0;
    const otrosDescuentos = parseFloat(document.getElementById('otrosDescuentos').value) || 0;
    
    // Calcular horas extras (1.35x según código laboral RD)
    const horasMensuales = 160; // 40 horas semanales * 4 semanas
    const valorHora = salarioBase / horasMensuales;
    const valorHoraExtra = valorHora * 1.35;
    const montoHorasExtras = horasExtras * valorHoraExtra;
    
    const totalIngresos = salarioBase + montoHorasExtras + bonos + comisiones + otrosIngresos;
    
    // Descuentos según leyes RD
    const limiteMaximo = 50000; // Límite máximo para AFP/ARS en RD
    const baseCalculo = Math.min(totalIngresos, limiteMaximo);
    const afp = baseCalculo * 0.0287; // 2.87%
    const ars = baseCalculo * 0.0304; // 3.04%
    const isr = calcularISR(totalIngresos);
    const totalDescuentos = afp + ars + isr + otrosDescuentos;
    const netoPagar = totalIngresos - totalDescuentos;
    
    document.getElementById('resumenSalarioBase').textContent = formatCurrency(salarioBase);
    document.getElementById('resumenTotalIngresos').textContent = formatCurrency(totalIngresos);
    document.getElementById('resumenTotalDescuentos').textContent = formatCurrency(totalDescuentos);
    document.getElementById('resumenNetoPagar').textContent = formatCurrency(netoPagar);
    document.getElementById('resumenAFP').textContent = formatCurrency(afp);
    document.getElementById('resumenARS').textContent = formatCurrency(ars);
    document.getElementById('resumenISR').textContent = formatCurrency(isr);
}

function calcularISR(salario) {
    // ISR según tabla de retención RD 2024
    if (salario <= 416220) {
        return 0; // Exento
    } else if (salario <= 624329) {
        return (salario - 416220) * 0.15;
    } else if (salario <= 867123) {
        return 31216.35 + (salario - 624329) * 0.20;
    } else {
        return 79776.35 + (salario - 867123) * 0.25;
    }
}

async function cargarNomina() {
    try {
        const params = new URLSearchParams();
        if (document.getElementById('filtroEmpleado').value) {
            params.append('empleado_id', document.getElementById('filtroEmpleado').value);
        }
        if (document.getElementById('filtroPeriodo').value) {
            params.append('periodo', document.getElementById('filtroPeriodo').value);
        }
        if (document.getElementById('filtroEstado').value) {
            params.append('estado', document.getElementById('filtroEstado').value);
        }

        const response = await fetch(`${API_BASE_URL}/nomina?${params}`, {
            headers: {
                'Authorization': `Bearer ${getToken()}`
            }
        });

        if (response.ok) {
            const nominas = await response.json();
            mostrarNomina(nominas);
        } else {
            showAlert('Error al cargar la nómina', 'error');
        }
    } catch (error) {
        console.error('Error cargando nómina:', error);
        showAlert('Error al cargar la nómina', 'error');
    }
}

function mostrarNomina(nominas) {
    const tbody = document.getElementById('tablaNomina');
    
    if (nominas.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center">No hay registros de nómina</td></tr>';
        return;
    }

    tbody.innerHTML = nominas.map(n => `
        <tr>
            <td>${n.empleado_nombre || ''} ${n.empleado_apellido || ''}</td>
            <td>${n.periodo}</td>
            <td>${formatDate(n.fecha_pago)}</td>
            <td>${formatCurrency(n.salario_base)}</td>
            <td>${formatCurrency(n.total_ingresos)}</td>
            <td>${formatCurrency(n.total_descuentos)}</td>
            <td><strong>${formatCurrency(n.neto_pagar)}</strong></td>
            <td><span class="badge badge-${n.estado === 'pagado' ? 'success' : 'warning'}">${n.estado}</span></td>
            <td>
                <button class="btn btn-sm btn-info" onclick="verDetalleNomina(${n.id})">Ver</button>
            </td>
        </tr>
    `).join('');
}

function procesarNomina() {
    document.getElementById('formNomina').reset();
    calculosNomina = null;
    
    const today = new Date();
    const periodo = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}`;
    document.getElementById('periodo').value = periodo;
    document.getElementById('fechaPago').value = today.toISOString().split('T')[0];
    
    document.getElementById('modalNomina').style.display = 'block';
    cargarEmpleados();
    
    // Agregar event listeners para cálculo en tiempo real
    ['horasExtras', 'bonos', 'comisiones', 'otrosIngresos', 'otrosDescuentos'].forEach(id => {
        const field = document.getElementById(id);
        if (field) {
            field.addEventListener('input', actualizarResumen);
            field.addEventListener('change', actualizarResumen);
        }
    });
}

function confirmarProcesarNomina() {
    const empleadoId = document.getElementById('empleadoId').value;
    const periodo = document.getElementById('periodo').value;
    const fechaPago = document.getElementById('fechaPago').value;

    if (!empleadoId || !periodo || !fechaPago) {
        showAlert('Complete todos los campos requeridos', 'error');
        return;
    }

    const data = {
        empleado_id: empleadoId,
        periodo: periodo,
        fecha_pago: fechaPago,
        horas_extras: parseFloat(document.getElementById('horasExtras').value) || 0,
        bonos: parseFloat(document.getElementById('bonos').value) || 0,
        comisiones: parseFloat(document.getElementById('comisiones').value) || 0,
        otros_ingresos: parseFloat(document.getElementById('otrosIngresos').value) || 0,
        otros_descuentos: parseFloat(document.getElementById('otrosDescuentos').value) || 0
    };

    fetch(`${API_BASE_URL}/nomina`, {
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
                throw new Error(err.message || 'Error al procesar la nómina');
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success !== false) {
            showAlert('Nómina procesada exitosamente', 'success');
            cerrarModal('modalNomina');
            cargarNomina();
        } else {
            showAlert(data.message || 'Error al procesar la nómina', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert(error.message || 'Error al procesar la nómina', 'error');
    });
}

function filtrarNomina() {
    cargarNomina();
}

async function verDetalleNomina(id) {
    try {
        showLoading('Cargando detalle de nómina...');
        const response = await fetch(`${API_BASE_URL}/nomina/${id}`, {
            headers: {
                'Authorization': `Bearer ${getToken()}`
            }
        });

        if (response.ok) {
            const nomina = await response.json();
            mostrarDetalleNomina(nomina);
            document.getElementById('modalDetalleNomina').style.display = 'block';
        } else {
            showAlert('Error al cargar el detalle de la nómina', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('Error al cargar el detalle de la nómina', 'error');
    } finally {
        hideLoading();
    }
}

function mostrarDetalleNomina(nomina) {
    // Actualizar ambos elementos con el mismo ID (header y tabla)
    document.querySelectorAll('#detalleNominaId').forEach(el => {
        el.textContent = nomina.id;
    });
    document.getElementById('detalleNominaEmpleado').textContent = `${nomina.empleado_nombre || ''} ${nomina.empleado_apellido || ''}`;
    document.getElementById('detalleNominaPeriodo').textContent = nomina.periodo || '-';
    document.getElementById('detalleNominaFecha').textContent = formatDate(nomina.fecha_procesamiento);
    document.getElementById('detalleNominaSalarioBase').textContent = formatCurrency(nomina.salario_base || 0);
    document.getElementById('detalleNominaHorasExtras').textContent = nomina.horas_extras || 0;
    document.getElementById('detalleNominaMontoHorasExtras').textContent = formatCurrency(nomina.monto_horas_extras || 0);
    document.getElementById('detalleNominaBonificaciones').textContent = formatCurrency(nomina.bonificaciones || 0);
    document.getElementById('detalleNominaDescuentos').textContent = formatCurrency(nomina.descuentos || 0);
    document.getElementById('detalleNominaAFP').textContent = formatCurrency(nomina.afp || 0);
    document.getElementById('detalleNominaARS').textContent = formatCurrency(nomina.ars || 0);
    document.getElementById('detalleNominaISR').textContent = formatCurrency(nomina.isr || 0);
    document.getElementById('detalleNominaTotalDescuentos').textContent = formatCurrency(nomina.total_descuentos || 0);
    document.getElementById('detalleNominaTotalBruto').textContent = formatCurrency(nomina.total_bruto || 0);
    document.getElementById('detalleNominaTotalNeto').textContent = formatCurrency(nomina.total_neto || 0);
    document.getElementById('detalleNominaUsuario').textContent = `${nomina.usuario_nombre || ''} ${nomina.usuario_apellido || ''}`;
}

