/**
 * Empleados
 */

document.addEventListener('DOMContentLoaded', function() {
    cargarEmpleados();
    cargarDepartamentos();
});

async function cargarEmpleados() {
    try {
        const response = await api.get('/empleados');
        mostrarEmpleados(response);
    } catch (error) {
        console.error('Error:', error);
        UI.showAlert('Error al cargar empleados', 'danger');
    }
}

function mostrarEmpleados(empleados) {
    const tbody = document.getElementById('tablaEmpleados').querySelector('tbody');
    
    if (!empleados || empleados.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center">No hay empleados registrados</td></tr>';
        return;
    }

    tbody.innerHTML = empleados.map(e => `
        <tr>
            <td>${e.cedula || ''}</td>
            <td>${e.nombre || ''} ${e.apellido || ''}</td>
            <td>${e.departamento_nombre || ''}</td>
            <td>${e.cargo || ''}</td>
            <td>${e.telefono || ''}</td>
            <td><span class="badge badge-${e.estado === 'activo' ? 'success' : 'danger'}">${e.estado || 'activo'}</span></td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="editarEmpleado(${e.id})">Editar</button>
                <button class="btn btn-sm btn-danger" onclick="eliminarEmpleado(${e.id})">Eliminar</button>
            </td>
        </tr>
    `).join('');
}

async function cargarDepartamentos() {
    try {
        const response = await api.get('/departamentos');
        const select = document.getElementById('departamentoEmpleado');
        select.innerHTML = '<option value="">Seleccionar...</option>' + 
            response.map(d => `<option value="${d.id}">${d.nombre}</option>`).join('');
    } catch (error) {
        console.error('Error cargando departamentos:', error);
    }
}

function abrirModalEmpleado() {
    document.getElementById('tituloModalEmpleado').textContent = 'Nuevo Empleado';
    document.getElementById('empleadoId').value = '';
    document.getElementById('formEmpleado').reset();
    document.getElementById('modalEmpleado').classList.add('active');
}

function cerrarModalEmpleado() {
    document.getElementById('modalEmpleado').classList.remove('active');
    document.getElementById('formEmpleado').reset();
    document.getElementById('empleadoId').value = '';
}

async function guardarEmpleado(event) {
    event.preventDefault();
    
    const id = document.getElementById('empleadoId').value;
    const data = {
        cedula: document.getElementById('cedulaEmpleado').value,
        nss: document.getElementById('nssEmpleado').value,
        nombre: document.getElementById('nombreEmpleado').value,
        apellido: document.getElementById('apellidoEmpleado').value,
        departamento_id: parseInt(document.getElementById('departamentoEmpleado').value),
        cargo: document.getElementById('cargoEmpleado').value,
        telefono: document.getElementById('telefonoEmpleado').value,
        email: document.getElementById('emailEmpleado').value,
        fecha_ingreso: document.getElementById('fechaIngreso').value,
        salario_base: parseFloat(document.getElementById('salarioBase').value) || 0,
        direccion: document.getElementById('direccionEmpleado').value
    };

    try {
        UI.showLoading('Guardando empleado...');
        let response;
        if (id) {
            response = await api.put(`/empleados/${id}`, data);
        } else {
            response = await api.post('/empleados', data);
        }
        
        if (response.success !== false) {
            UI.showAlert(`Empleado ${id ? 'actualizado' : 'creado'} exitosamente`, 'success');
            cerrarModalEmpleado();
            cargarEmpleados();
        } else {
            UI.showAlert(response.message || 'Error al guardar empleado', 'danger');
        }
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        console.error('Error:', error);
        UI.showAlert(error.message || 'Error al guardar empleado', 'danger');
    }
}

async function editarEmpleado(id) {
    try {
        const empleado = await api.get(`/empleados/${id}`);
        document.getElementById('empleadoId').value = empleado.id;
        document.getElementById('cedulaEmpleado').value = empleado.cedula || '';
        document.getElementById('nssEmpleado').value = empleado.nss || '';
        document.getElementById('nombreEmpleado').value = empleado.nombre || '';
        document.getElementById('apellidoEmpleado').value = empleado.apellido || '';
        document.getElementById('departamentoEmpleado').value = empleado.departamento_id || '';
        document.getElementById('cargoEmpleado').value = empleado.cargo || '';
        document.getElementById('telefonoEmpleado').value = empleado.telefono || '';
        document.getElementById('emailEmpleado').value = empleado.email || '';
        document.getElementById('fechaIngreso').value = empleado.fecha_ingreso || '';
        document.getElementById('salarioBase').value = empleado.salario_base || '';
        document.getElementById('direccionEmpleado').value = empleado.direccion || '';
        
        document.getElementById('tituloModalEmpleado').textContent = 'Editar Empleado';
        document.getElementById('modalEmpleado').classList.add('active');
    } catch (error) {
        UI.showAlert('Error al cargar empleado', 'danger');
    }
}

async function eliminarEmpleado(id) {
    if (!confirm('¿Está seguro de eliminar este empleado?')) return;
    
    try {
        UI.showLoading('Eliminando empleado...');
        await api.delete(`/empleados/${id}`);
        UI.showAlert('Empleado eliminado exitosamente', 'success');
        cargarEmpleados();
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al eliminar empleado', 'danger');
    }
}

function filtrarEmpleados() {
    const busqueda = document.getElementById('buscarEmpleado').value.toLowerCase();
    const filas = document.querySelectorAll('#tablaEmpleados tbody tr');
    
    filas.forEach(fila => {
        const texto = fila.textContent.toLowerCase();
        fila.style.display = texto.includes(busqueda) ? '' : 'none';
    });
}

