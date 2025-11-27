/**
 * Gestión de Categorías de Artículos
 */

let categorias = [];

document.addEventListener('DOMContentLoaded', () => {
    cargarCategorias();
    cargarUsuario();
});

function cargarUsuario() {
    const user = auth.getCurrentUser();
    if (user) {
        document.getElementById('userName').textContent = `${user.nombre} ${user.apellido}`;
    }
}

async function cargarCategorias() {
    try {
        UI.showLoading('Cargando categorías...');
        const response = await api.get('/categorias-articulos');
        
        if (response.success) {
            categorias = response.data || response || [];
            mostrarCategorias();
        }
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al cargar categorías: ' + error.message, 'danger');
    }
}

function mostrarCategorias() {
    const tbody = document.getElementById('tbodyCategorias');
    
    if (categorias.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center">No hay categorías registradas</td></tr>';
        return;
    }
    
    tbody.innerHTML = categorias.map(cat => `
        <tr>
            <td><strong>${cat.nombre || '-'}</strong></td>
            <td>${cat.descripcion || '-'}</td>
            <td>${cat.total_articulos || 0}</td>
            <td>
                <span class="badge badge-${cat.estado === 'activo' ? 'success' : 'secondary'}">
                    ${cat.estado || 'inactivo'}
                </span>
            </td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="editarCategoria(${cat.id})">Editar</button>
                <button class="btn btn-sm btn-danger" onclick="eliminarCategoria(${cat.id})">Eliminar</button>
            </td>
        </tr>
    `).join('');
}

function abrirModalCrear() {
    document.getElementById('modalTitulo').textContent = 'Nueva Categoría';
    document.getElementById('formCategoria').reset();
    document.getElementById('categoriaId').value = '';
    document.getElementById('estado').value = 'activo';
    document.getElementById('modalCategoria').classList.add('show');
}

function editarCategoria(id) {
    const categoria = categorias.find(c => c.id === id);
    if (!categoria) return;
    
    document.getElementById('modalTitulo').textContent = 'Editar Categoría';
    document.getElementById('categoriaId').value = categoria.id;
    document.getElementById('nombre').value = categoria.nombre || '';
    document.getElementById('descripcion').value = categoria.descripcion || '';
    document.getElementById('estado').value = categoria.estado || 'activo';
    
    document.getElementById('modalCategoria').classList.add('show');
}

async function guardarCategoria() {
    const id = document.getElementById('categoriaId').value;
    const data = {
        nombre: document.getElementById('nombre').value,
        descripcion: document.getElementById('descripcion').value,
        estado: document.getElementById('estado').value
    };
    
    if (!data.nombre) {
        UI.showAlert('El nombre es requerido', 'danger');
        return;
    }
    
    try {
        UI.showLoading('Guardando categoría...');
        
        let response;
        if (id) {
            response = await api.put(`/categorias-articulos/${id}`, data);
        } else {
            response = await api.post('/categorias-articulos', data);
        }
        
        if (response.success) {
            UI.showAlert(`Categoría ${id ? 'actualizada' : 'creada'} correctamente`, 'success');
            cerrarModal('modalCategoria');
            cargarCategorias();
        }
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al guardar categoría: ' + error.message, 'danger');
    }
}

async function eliminarCategoria(id) {
    const categoria = categorias.find(c => c.id === id);
    if (!categoria) return;
    
    if (categoria.total_articulos > 0) {
        UI.showAlert('No se puede eliminar la categoría porque tiene artículos asociados', 'warning');
        return;
    }
    
    if (!confirm(`¿Está seguro de eliminar la categoría "${categoria.nombre}"?`)) {
        return;
    }
    
    try {
        UI.showLoading('Eliminando categoría...');
        const response = await api.delete(`/categorias-articulos/${id}`);
        
        if (response.success) {
            UI.showAlert('Categoría eliminada correctamente', 'success');
            cargarCategorias();
        }
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al eliminar categoría: ' + error.message, 'danger');
    }
}

