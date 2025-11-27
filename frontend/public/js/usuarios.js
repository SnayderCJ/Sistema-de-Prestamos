// ============================================
// Gestión de Usuarios
// ============================================

let usuarioEditando = null;

document.addEventListener('DOMContentLoaded', () => {
    cargarUsuarios();
    cargarSucursales();
});

async function cargarUsuarios() {
    try {
        UI.showLoading();
        
        const filters = {};
        
        if (document.getElementById('filtroRol').value) {
            filters.rol = document.getElementById('filtroRol').value;
        }
        
        if (document.getElementById('buscarUsuario').value) {
            filters.search = document.getElementById('buscarUsuario').value;
        }
        
        const response = await api.get('/usuarios', filters);
        
        if (response.success) {
            mostrarUsuarios(response.data.items || response.data);
        }
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al cargar usuarios: ' + error.message, 'danger');
    }
}

function mostrarUsuarios(usuarios) {
    const tbody = document.getElementById('tbodyUsuarios');
    if (!tbody) return;
    
    const columns = [
        { field: 'cedula' },
        { 
            render: (item) => {
                const nombre = item.nombre || '';
                const apellido = item.apellido || '';
                return `${nombre} ${apellido}`.trim() || '-';
            }
        },
        { field: 'email' },
        { field: 'telefono', render: (item) => item.telefono || '-' },
        { 
            render: (item) => createBadge(item.rol || 'N/A', item.rol || 'info'),
            sanitize: false
        },
        { field: 'sucursal_nombre', render: (item) => item.sucursal_nombre || '-' },
        { 
            render: (item) => {
                const estado = item.activo ? 'Activo' : 'Inactivo';
                const tipo = item.activo ? 'success' : 'secondary';
                return createBadge(estado, tipo);
            },
            sanitize: false
        },
        {
            render: (item) => {
                return `
                    <button class="btn btn-sm btn-primary" onclick="editarUsuario(${item.id})">Editar</button>
                    <button class="btn btn-sm btn-danger" onclick="eliminarUsuario(${item.id})">Eliminar</button>
                `;
            },
            sanitize: false
        }
    ];
    
    renderSafeTable(tbody, usuarios, columns, 'No hay usuarios');
}

function filtrarUsuarios() {
    cargarUsuarios();
}

async function cargarSucursales() {
    try {
        const response = await api.get('/sucursales');
        if (response.success) {
            const select = document.getElementById('sucursalId');
            select.innerHTML = '<option value="">Seleccionar...</option>' +
                response.data.map(s => 
                    `<option value="${s.id}">${s.nombre}</option>`
                ).join('');
        }
    } catch (error) {
        console.error('Error cargando sucursales:', error);
    }
}

function abrirModalCrear() {
    usuarioEditando = null;
    document.getElementById('modalUsuarioTitulo').textContent = 'Nuevo Usuario';
    document.getElementById('passwordRequired').style.display = 'inline';
    document.getElementById('password').required = true;
    document.getElementById('formCrearUsuario').reset();
    document.getElementById('modalCrearUsuario').classList.add('show');
}

function cerrarModalCrear() {
    document.getElementById('modalCrearUsuario').classList.remove('show');
    document.getElementById('formCrearUsuario').reset();
    usuarioEditando = null;
}

async function editarUsuario(id) {
    try {
        UI.showLoading();
        
        const response = await api.get(`/usuarios/${id}`);
        
        if (response.success) {
            usuarioEditando = response.data;
            document.getElementById('modalUsuarioTitulo').textContent = 'Editar Usuario';
            document.getElementById('passwordRequired').style.display = 'none';
            document.getElementById('password').required = false;
            
            document.getElementById('cedula').value = usuarioEditando.cedula;
            document.getElementById('nombre').value = usuarioEditando.nombre;
            document.getElementById('apellido').value = usuarioEditando.apellido;
            document.getElementById('email').value = usuarioEditando.email;
            document.getElementById('telefono').value = usuarioEditando.telefono || '';
            document.getElementById('rol').value = usuarioEditando.rol;
            document.getElementById('sucursalId').value = usuarioEditando.sucursal_id || '';
            document.getElementById('activo').value = usuarioEditando.activo ? '1' : '0';
            
            document.getElementById('modalCrearUsuario').classList.add('show');
        }
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al cargar usuario: ' + error.message, 'danger');
    }
}

async function guardarUsuario() {
    const form = document.getElementById('formCrearUsuario');
    
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
            email: document.getElementById('email').value,
            telefono: document.getElementById('telefono').value || null,
            rol: document.getElementById('rol').value,
            sucursal_id: document.getElementById('sucursalId').value || null,
            activo: document.getElementById('activo').value === '1'
        };
        
        const password = document.getElementById('password').value;
        if (password) {
            data.password = password;
        }
        
        let response;
        if (usuarioEditando) {
            response = await api.put(`/usuarios/${usuarioEditando.id}`, data);
        } else {
            if (!password) {
                UI.showAlert('La contraseña es requerida para nuevos usuarios', 'danger');
                UI.hideLoading();
                return;
            }
            response = await api.post('/usuarios', data);
        }
        
        if (response.success) {
            UI.showAlert(usuarioEditando ? 'Usuario actualizado correctamente' : 'Usuario creado correctamente', 'success');
            cerrarModalCrear();
            cargarUsuarios();
        }
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al guardar usuario: ' + error.message, 'danger');
    }
}

async function eliminarUsuario(id) {
    if (!confirm('¿Está seguro de eliminar este usuario?')) {
        return;
    }
    
    try {
        UI.showLoading();
        
        const response = await api.delete(`/usuarios/${id}`);
        
        if (response.success) {
            UI.showAlert('Usuario eliminado correctamente', 'success');
            cargarUsuarios();
        }
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al eliminar usuario: ' + error.message, 'danger');
    }
}

// La función logout() está definida globalmente en app.js


