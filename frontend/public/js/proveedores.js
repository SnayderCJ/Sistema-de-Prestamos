/**
 * Gestión de Proveedores
 */

let proveedores = [];
let proveedoresFiltrados = [];

document.addEventListener('DOMContentLoaded', () => {
    cargarProveedores();
    
    const formProveedor = document.getElementById('formProveedor');
    if (formProveedor) {
        formProveedor.addEventListener('submit', (e) => {
            e.preventDefault();
            guardarProveedor();
        });
    }
});

async function cargarProveedores() {
    try {
        UI.showLoading('Cargando proveedores...');
        const response = await api.get('/proveedores');
        
        if (response.success && response.items) {
            proveedores = response.items;
            proveedoresFiltrados = proveedores;
            mostrarProveedores();
        }
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al cargar proveedores: ' + error.message, 'danger');
    }
}

function mostrarProveedores() {
    const tbody = document.getElementById('tbodyProveedores');
    
    if (proveedoresFiltrados.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center">No hay proveedores registrados</td></tr>';
        return;
    }
    
    tbody.innerHTML = proveedoresFiltrados.map(prov => `
        <tr>
            <td><strong>${prov.nombre || '-'}</strong></td>
            <td>${prov.rnc || prov.cedula || '-'}</td>
            <td>${prov.telefono || '-'}</td>
            <td>${prov.email || '-'}</td>
            <td>${prov.total_compras || 0}</td>
            <td>
                <span class="badge badge-${prov.estado === 'activo' ? 'success' : 'secondary'}">
                    ${prov.estado || 'inactivo'}
                </span>
            </td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="editarProveedor(${prov.id})">Editar</button>
                <button class="btn btn-sm btn-danger" onclick="eliminarProveedor(${prov.id})">Eliminar</button>
            </td>
        </tr>
    `).join('');
}

function filtrarProveedores() {
    const busqueda = document.getElementById('buscarProveedor').value.toLowerCase();
    const estadoFiltro = document.getElementById('filtroEstado').value;
    
    proveedoresFiltrados = proveedores.filter(prov => {
        const matchBusqueda = !busqueda || 
            (prov.nombre && prov.nombre.toLowerCase().includes(busqueda)) ||
            (prov.cedula && prov.cedula.includes(busqueda)) ||
            (prov.rnc && prov.rnc.includes(busqueda));
        
        const matchEstado = !estadoFiltro || prov.estado === estadoFiltro;
        
        return matchBusqueda && matchEstado;
    });
    
    mostrarProveedores();
}

function abrirModalCrear() {
    document.getElementById('modalTitulo').textContent = 'Nuevo Proveedor';
    document.getElementById('formProveedor').reset();
    document.getElementById('proveedorId').value = '';
    document.getElementById('modalProveedor').classList.add('show');
}

function editarProveedor(id) {
    const proveedor = proveedores.find(p => p.id === id);
    if (!proveedor) return;
    
    document.getElementById('modalTitulo').textContent = 'Editar Proveedor';
    document.getElementById('proveedorId').value = proveedor.id;
    document.getElementById('nombre').value = proveedor.nombre || '';
    document.getElementById('cedula').value = proveedor.cedula || '';
    document.getElementById('rnc').value = proveedor.rnc || '';
    document.getElementById('telefono').value = proveedor.telefono || '';
    document.getElementById('email').value = proveedor.email || '';
    document.getElementById('direccion').value = proveedor.direccion || '';
    document.getElementById('contacto').value = proveedor.contacto || '';
    document.getElementById('estado').value = proveedor.estado || 'activo';
    
    document.getElementById('modalProveedor').classList.add('show');
}

async function guardarProveedor() {
    const id = document.getElementById('proveedorId').value;
    const data = {
        nombre: document.getElementById('nombre').value,
        cedula: document.getElementById('cedula').value,
        rnc: document.getElementById('rnc').value,
        telefono: document.getElementById('telefono').value,
        email: document.getElementById('email').value,
        direccion: document.getElementById('direccion').value,
        contacto: document.getElementById('contacto').value,
        estado: document.getElementById('estado').value
    };
    
    if (!data.nombre) {
        UI.showAlert('El nombre es requerido', 'danger');
        return;
    }
    
    try {
        UI.showLoading('Guardando proveedor...');
        
        let response;
        if (id) {
            response = await api.put(`/proveedores/${id}`, data);
        } else {
            response = await api.post('/proveedores', data);
        }
        
        if (response.success) {
            UI.showAlert(`Proveedor ${id ? 'actualizado' : 'creado'} correctamente`, 'success');
            cerrarModal('modalProveedor');
            cargarProveedores();
        }
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al guardar proveedor: ' + error.message, 'danger');
    }
}

async function eliminarProveedor(id) {
    const proveedor = proveedores.find(p => p.id === id);
    if (!proveedor) return;
    
    if (!confirm(`¿Está seguro de eliminar el proveedor "${proveedor.nombre}"?`)) {
        return;
    }
    
    try {
        UI.showLoading('Eliminando proveedor...');
        const response = await api.delete(`/proveedores/${id}`);
        
        if (response.success) {
            UI.showAlert('Proveedor eliminado correctamente', 'success');
            cargarProveedores();
        }
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al eliminar proveedor: ' + error.message, 'danger');
    }
}

