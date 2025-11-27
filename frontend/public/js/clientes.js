// ============================================
// Gestión de Clientes
// ============================================

document.addEventListener('DOMContentLoaded', () => {
    cargarClientes();
});

async function cargarClientes() {
    try {
        UI.showLoading();
        
        const search = document.getElementById('buscarCliente').value;
        const filters = {};
        
        if (search) {
            filters.search = search;
        }
        
        const response = await clientesService.getAll(filters);
        
        if (response.success) {
            mostrarClientes(response.data.items || response.data);
        }
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al cargar clientes: ' + error.message, 'danger');
    }
}

function mostrarClientes(clientes) {
    const tbody = document.getElementById('tbodyClientes');
    
    const columns = [
        { field: 'cedula' },
        { 
            render: (item) => `${item.nombre || ''} ${item.apellido || ''}`.trim() || '-'
        },
        { field: 'telefono', render: (item) => item.telefono || '-' },
        { field: 'total_prestamos', render: (item) => item.total_prestamos || 0 },
        { 
            render: (item) => {
                const estado = item.estado_credito || 'activo';
                return createBadge(estado, estado);
            },
            sanitize: false
        },
        {
            render: (item) => {
                return `
                    <button class="btn btn-sm btn-primary" onclick="event.stopPropagation(); verDetalle(${item.id})">Ver</button>
                    <button class="btn btn-sm btn-secondary" onclick="event.stopPropagation(); editarCliente(${item.id})">Editar</button>
                `;
            },
            sanitize: false
        }
    ];
    
    const dataWithOnclick = clientes.map(cliente => ({
        ...cliente,
        onclick: `verDetalle(${cliente.id})`
    }));
    
    renderSafeTable(tbody, dataWithOnclick, columns, 'No hay clientes');
}

function buscarClientes() {
    cargarClientes();
}

function abrirModalCrear() {
    document.getElementById('modalCrearCliente').classList.add('show');
}

function cerrarModalCrear() {
    document.getElementById('modalCrearCliente').classList.remove('show');
    document.getElementById('formCrearCliente').reset();
}

async function crearCliente() {
    const form = document.getElementById('formCrearCliente');
    
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    try {
        UI.showLoading();
        
        const data = {
            cedula: document.getElementById('cedula').value,
            nombre: document.getElementById('nombre').value,
            apellido: document.getElementById('apellido').value,
            telefono: document.getElementById('telefono').value,
            email: document.getElementById('email').value,
            direccion: document.getElementById('direccion').value,
            ingresos_mensuales: document.getElementById('ingresosMensuales').value
        };
        
        const response = await clientesService.create(data);
        
        if (response.success) {
            UI.showAlert('Cliente creado correctamente', 'success');
            cerrarModalCrear();
            cargarClientes();
        }
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al crear cliente: ' + error.message, 'danger');
    }
}

async function verDetalle(id) {
    try {
        UI.showLoading('Cargando detalle del cliente...');
        
        const response = await api.get(`/clientes/${id}`);
        
        if (response.success) {
            mostrarDetalleCliente(response.data);
            document.getElementById('modalDetalleCliente').classList.add('show');
        }
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al cargar el detalle: ' + error.message, 'danger');
    }
}

function mostrarDetalleCliente(cliente) {
    document.getElementById('detalleCedula').textContent = cliente.cedula || '-';
    document.getElementById('detalleNombre').textContent = `${cliente.nombre || ''} ${cliente.apellido || ''}`;
    document.getElementById('detalleEmail').textContent = cliente.email || '-';
    document.getElementById('detalleTelefono').textContent = cliente.telefono || '-';
    document.getElementById('detalleDireccion').textContent = cliente.direccion || '-';
    document.getElementById('detalleCiudad').textContent = cliente.ciudad || '-';
    document.getElementById('detalleProvincia').textContent = cliente.provincia || '-';
    document.getElementById('detalleOcupacion').textContent = cliente.ocupacion || '-';
    document.getElementById('detalleIngresos').textContent = UI.formatCurrency(cliente.ingresos_mensuales || 0);
    document.getElementById('detalleEstado').textContent = cliente.estado_credito || 'activo';
    document.getElementById('detalleEstado').className = `badge badge-${cliente.estado_credito || 'activo'}`;
    document.getElementById('detalleTotalPrestamos').textContent = cliente.total_prestamos || 0;
}

async function editarCliente(id) {
    try {
        UI.showLoading('Cargando datos del cliente...');
        
        const response = await api.get(`/clientes/${id}`);
        
        if (response.success) {
            const cliente = response.data;
            document.getElementById('editarClienteId').value = cliente.id;
            document.getElementById('editarCedula').value = cliente.cedula || '';
            document.getElementById('editarNombre').value = cliente.nombre || '';
            document.getElementById('editarApellido').value = cliente.apellido || '';
            document.getElementById('editarTelefono').value = cliente.telefono || '';
            document.getElementById('editarEmail').value = cliente.email || '';
            document.getElementById('editarDireccion').value = cliente.direccion || '';
            document.getElementById('editarIngresosMensuales').value = cliente.ingresos_mensuales || '';
            
            document.getElementById('modalEditarCliente').classList.add('show');
        }
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al cargar datos: ' + error.message, 'danger');
    }
}

async function actualizarCliente() {
    const form = document.getElementById('formEditarCliente');
    
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    try {
        UI.showLoading();
        
        const id = document.getElementById('editarClienteId').value;
        const data = {
            nombre: document.getElementById('editarNombre').value,
            apellido: document.getElementById('editarApellido').value,
            telefono: document.getElementById('editarTelefono').value,
            email: document.getElementById('editarEmail').value,
            direccion: document.getElementById('editarDireccion').value,
            ingresos_mensuales: document.getElementById('editarIngresosMensuales').value
        };
        
        const response = await api.put(`/clientes/${id}`, data);
        
        if (response.success) {
            UI.showAlert('Cliente actualizado correctamente', 'success');
            cerrarModalEditar();
            cargarClientes();
        }
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al actualizar cliente: ' + error.message, 'danger');
    }
}

function cerrarModalEditar() {
    document.getElementById('modalEditarCliente').classList.remove('show');
    document.getElementById('formEditarCliente').reset();
}

function cerrarModalDetalle() {
    document.getElementById('modalDetalleCliente').classList.remove('show');
}

// La función logout() está definida globalmente en app.js

// Funciones de exportación
async function exportarClientesCSV() {
    try {
        UI.showLoading('Generando reporte CSV...');
        
        const filters = {};
        const search = document.getElementById('buscarCliente').value;
        if (search) {
            filters.search = search;
        }
        
        const filename = `clientes_${new Date().toISOString().split('T')[0]}.csv`;
        
        await api.downloadFile('/exportacion/clientes', filters, filename);
        
        UI.showAlert('Reporte CSV generado exitosamente', 'success');
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al exportar CSV: ' + error.message, 'danger');
    }
}


