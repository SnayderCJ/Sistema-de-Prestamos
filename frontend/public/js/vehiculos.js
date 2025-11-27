// Gestión de Vehículos

let vehiculos = [];
let vehiculoActual = null;
let paginaActual = 1;
const porPagina = 20;

document.addEventListener('DOMContentLoaded', () => {
    cargarVehiculos();
    cargarUsuario();
});

function cargarUsuario() {
    const user = auth.getCurrentUser();
    if (user) {
        document.getElementById('userName').textContent = `${user.nombre} ${user.apellido}`;
    }
}

async function cargarVehiculos() {
    try {
        UI.showLoading('Cargando vehículos...');
        
        const filtros = {};
        const estado = document.getElementById('filtroEstado').value;
        if (estado) filtros.estado = estado;
        
        const response = await api.get('/vehiculos', {
            page: paginaActual,
            per_page: porPagina,
            ...filtros
        });
        
        if (response.success) {
            vehiculos = response.data.items || response.data || [];
            mostrarVehiculos(vehiculos);
            
            if (response.data.pagination) {
                mostrarPaginacion(response.data.pagination);
            }
        }
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al cargar vehículos: ' + error.message, 'danger');
    }
}

function mostrarVehiculos(lista) {
    const tbody = document.querySelector('#tablaVehiculos tbody');
    
    if (!lista || lista.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center">No hay vehículos registrados</td></tr>';
        return;
    }
    
    tbody.innerHTML = lista.map(vehiculo => `
        <tr>
            <td>${vehiculo.placa || '-'}</td>
            <td>${vehiculo.marca || '-'}</td>
            <td>${vehiculo.modelo || '-'}</td>
            <td>${vehiculo.ano || '-'}</td>
            <td>${vehiculo.color || '-'}</td>
            <td>${vehiculo.numero_chasis || '-'}</td>
            <td>
                <span class="badge badge-${vehiculo.estado === 'disponible' ? 'success' : vehiculo.estado === 'financiado' ? 'warning' : 'danger'}">
                    ${vehiculo.estado || 'disponible'}
                </span>
            </td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="verDetalleVehiculo(${vehiculo.id})">Ver</button>
                <button class="btn btn-sm btn-secondary" onclick="editarVehiculo(${vehiculo.id})">Editar</button>
                <button class="btn btn-sm btn-danger" onclick="eliminarVehiculo(${vehiculo.id})">Eliminar</button>
            </td>
        </tr>
    `).join('');
}

function mostrarPaginacion(pagination) {
    const paginacion = document.getElementById('paginacionVehiculos');
    if (!paginacion || !pagination) return;
    
    let html = '';
    
    if (pagination.has_prev) {
        html += `<button class="btn btn-sm" onclick="cambiarPagina(${pagination.page - 1})">Anterior</button>`;
    }
    
    html += `<span class="pagination-info">Página ${pagination.page} de ${pagination.total_pages}</span>`;
    
    if (pagination.has_next) {
        html += `<button class="btn btn-sm" onclick="cambiarPagina(${pagination.page + 1})">Siguiente</button>`;
    }
    
    paginacion.innerHTML = html;
}

function cambiarPagina(pagina) {
    paginaActual = pagina;
    cargarVehiculos();
}

function filtrarVehiculos() {
    const busqueda = document.getElementById('buscarVehiculo').value.toLowerCase();
    
    if (!busqueda) {
        mostrarVehiculos(vehiculos);
        return;
    }
    
    const filtrados = vehiculos.filter(v => 
        (v.placa && v.placa.toLowerCase().includes(busqueda)) ||
        (v.marca && v.marca.toLowerCase().includes(busqueda)) ||
        (v.modelo && v.modelo.toLowerCase().includes(busqueda)) ||
        (v.numero_chasis && v.numero_chasis.toLowerCase().includes(busqueda))
    );
    
    mostrarVehiculos(filtrados);
}

function abrirModalCrearVehiculo() {
    vehiculoActual = null;
    document.getElementById('modalVehiculoTitulo').textContent = 'Nuevo Vehículo';
    document.getElementById('formVehiculo').reset();
    document.getElementById('vehiculoId').value = '';
    document.getElementById('vehiculoEstado').value = 'disponible';
    document.getElementById('modalVehiculo').style.display = 'block';
}

async function verDetalleVehiculo(id) {
    try {
        UI.showLoading('Cargando detalle...');
        
        const response = await api.get(`/vehiculos/${id}`);
        
        if (response.success) {
            vehiculoActual = response.data;
            mostrarDetalleVehiculo(vehiculoActual);
            document.getElementById('modalDetalleVehiculo').style.display = 'block';
        }
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al cargar detalle: ' + error.message, 'danger');
    }
}

function mostrarDetalleVehiculo(vehiculo) {
    document.getElementById('detalleVehiculoPlaca').textContent = vehiculo.placa || '-';
    document.getElementById('detalleVehiculoMarca').textContent = vehiculo.marca || '-';
    document.getElementById('detalleVehiculoModelo').textContent = vehiculo.modelo || '-';
    document.getElementById('detalleVehiculoAno').textContent = vehiculo.ano || '-';
    document.getElementById('detalleVehiculoColor').textContent = vehiculo.color || '-';
    document.getElementById('detalleVehiculoChasis').textContent = vehiculo.numero_chasis || '-';
    document.getElementById('detalleVehiculoMotor').textContent = vehiculo.numero_motor || '-';
    document.getElementById('detalleVehiculoValor').textContent = formatCurrency(vehiculo.valor_comercial || 0);
    document.getElementById('detalleVehiculoObservaciones').textContent = vehiculo.observaciones || '-';
    
    const estadoBadge = document.getElementById('detalleVehiculoEstado');
    estadoBadge.textContent = vehiculo.estado || 'disponible';
    estadoBadge.className = `badge badge-${vehiculo.estado === 'disponible' ? 'success' : vehiculo.estado === 'financiado' ? 'warning' : 'danger'}`;
}

async function editarVehiculo(id) {
    try {
        UI.showLoading('Cargando vehículo...');
        
        const response = await api.get(`/vehiculos/${id}`);
        
        if (response.success) {
            vehiculoActual = response.data;
            document.getElementById('modalVehiculoTitulo').textContent = 'Editar Vehículo';
            document.getElementById('vehiculoId').value = vehiculoActual.id;
            document.getElementById('vehiculoPlaca').value = vehiculoActual.placa || '';
            document.getElementById('vehiculoMarca').value = vehiculoActual.marca || '';
            document.getElementById('vehiculoModelo').value = vehiculoActual.modelo || '';
            document.getElementById('vehiculoAno').value = vehiculoActual.ano || '';
            document.getElementById('vehiculoColor').value = vehiculoActual.color || '';
            document.getElementById('vehiculoChasis').value = vehiculoActual.numero_chasis || '';
            document.getElementById('vehiculoMotor').value = vehiculoActual.numero_motor || '';
            document.getElementById('vehiculoValor').value = vehiculoActual.valor_comercial || '';
            document.getElementById('vehiculoEstado').value = vehiculoActual.estado || 'disponible';
            document.getElementById('vehiculoObservaciones').value = vehiculoActual.observaciones || '';
            
            document.getElementById('modalVehiculo').style.display = 'block';
        }
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al cargar vehículo: ' + error.message, 'danger');
    }
}

function editarVehiculoDesdeDetalle() {
    cerrarModal('modalDetalleVehiculo');
    if (vehiculoActual) {
        editarVehiculo(vehiculoActual.id);
    }
}

async function guardarVehiculo(event) {
    event.preventDefault();
    
    try {
        UI.showLoading('Guardando vehículo...');
        
        const data = {
            placa: document.getElementById('vehiculoPlaca').value,
            marca: document.getElementById('vehiculoMarca').value,
            modelo: document.getElementById('vehiculoModelo').value,
            ano: document.getElementById('vehiculoAno').value,
            color: document.getElementById('vehiculoColor').value,
            numero_chasis: document.getElementById('vehiculoChasis').value,
            numero_motor: document.getElementById('vehiculoMotor').value,
            valor_comercial: document.getElementById('vehiculoValor').value || null,
            estado: document.getElementById('vehiculoEstado').value,
            observaciones: document.getElementById('vehiculoObservaciones').value
        };
        
        const id = document.getElementById('vehiculoId').value;
        let response;
        
        if (id) {
            response = await api.put(`/vehiculos/${id}`, data);
        } else {
            response = await api.post('/vehiculos', data);
        }
        
        if (response.success) {
            UI.showAlert(id ? 'Vehículo actualizado correctamente' : 'Vehículo creado correctamente', 'success');
            cerrarModal('modalVehiculo');
            cargarVehiculos();
        }
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al guardar vehículo: ' + error.message, 'danger');
    }
}

async function eliminarVehiculo(id) {
    if (!confirm('¿Está seguro de eliminar este vehículo?')) {
        return;
    }
    
    try {
        UI.showLoading('Eliminando vehículo...');
        
        const response = await api.delete(`/vehiculos/${id}`);
        
        if (response.success) {
            UI.showAlert('Vehículo eliminado correctamente', 'success');
            cargarVehiculos();
        }
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al eliminar vehículo: ' + error.message, 'danger');
    }
}

function cerrarModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

