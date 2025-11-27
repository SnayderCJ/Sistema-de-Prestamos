// ============================================
// Gestión de Tasas de Interés
// ============================================

let tasaEditando = null;

document.addEventListener('DOMContentLoaded', () => {
    cargarTasas();
});

async function cargarTasas() {
    try {
        UI.showLoading();
        
        const response = await api.get('/tasas');
        
        if (response.success) {
            mostrarTasas(response.data);
        }
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al cargar tasas: ' + error.message, 'danger');
    }
}

function mostrarTasas(tasas) {
    const tbody = document.getElementById('tbodyTasas');
    if (!tbody) return;
    
    const columns = [
        { field: 'codigo' },
        { field: 'nombre' },
        { field: 'tipo_tasa' },
        { render: (item) => `${item.tasa_mensual || 0}%` },
        { render: (item) => `${item.tasa_anual || 0}%` },
        { render: (item) => item.monto_minimo ? UI.formatCurrency(item.monto_minimo) : '-' },
        { render: (item) => item.monto_maximo ? UI.formatCurrency(item.monto_maximo) : '-' },
        { field: 'plazo_minimo', render: (item) => item.plazo_minimo || '-' },
        { field: 'plazo_maximo', render: (item) => item.plazo_maximo || '-' },
        { 
            render: (item) => {
                const estado = item.activa ? 'Activa' : 'Inactiva';
                const tipo = item.activa ? 'success' : 'secondary';
                return createBadge(estado, tipo);
            },
            sanitize: false
        },
        {
            render: (item) => {
                return `
                    <button class="btn btn-sm btn-primary" onclick="editarTasa(${item.id})">Editar</button>
                    <button class="btn btn-sm btn-danger" onclick="eliminarTasa(${item.id})">Eliminar</button>
                `;
            },
            sanitize: false
        }
    ];
    
    renderSafeTable(tbody, tasas, columns, 'No hay tasas');
}

function abrirModalCrear() {
    tasaEditando = null;
    document.getElementById('modalTasaTitulo').textContent = 'Nueva Tasa de Interés';
    document.getElementById('formCrearTasa').reset();
    document.getElementById('modalCrearTasa').classList.add('show');
}

function cerrarModalCrear() {
    document.getElementById('modalCrearTasa').classList.remove('show');
    document.getElementById('formCrearTasa').reset();
    tasaEditando = null;
}

async function editarTasa(id) {
    try {
        UI.showLoading();
        
        const response = await api.get(`/tasas/${id}`);
        
        if (response.success) {
            tasaEditando = response.data;
            document.getElementById('modalTasaTitulo').textContent = 'Editar Tasa de Interés';
            
            document.getElementById('codigo').value = tasaEditando.codigo;
            document.getElementById('nombre').value = tasaEditando.nombre;
            document.getElementById('tipoTasa').value = tasaEditando.tipo_tasa;
            document.getElementById('tasaMensual').value = tasaEditando.tasa_mensual;
            document.getElementById('tasaAnual').value = tasaEditando.tasa_anual;
            document.getElementById('montoMinimo').value = tasaEditando.monto_minimo || '';
            document.getElementById('montoMaximo').value = tasaEditando.monto_maximo || '';
            document.getElementById('plazoMinimo').value = tasaEditando.plazo_minimo || '';
            document.getElementById('plazoMaximo').value = tasaEditando.plazo_maximo || '';
            document.getElementById('activa').value = tasaEditando.activa ? '1' : '0';
            document.getElementById('descripcion').value = tasaEditando.descripcion || '';
            
            document.getElementById('modalCrearTasa').classList.add('show');
        }
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al cargar tasa: ' + error.message, 'danger');
    }
}

async function guardarTasa() {
    const form = document.getElementById('formCrearTasa');
    
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    try {
        UI.showLoading();
        
        const data = {
            codigo: document.getElementById('codigo').value,
            nombre: document.getElementById('nombre').value,
            tipo_tasa: document.getElementById('tipoTasa').value,
            tasa_mensual: document.getElementById('tasaMensual').value,
            tasa_anual: document.getElementById('tasaAnual').value,
            monto_minimo: document.getElementById('montoMinimo').value || null,
            monto_maximo: document.getElementById('montoMaximo').value || null,
            plazo_minimo: document.getElementById('plazoMinimo').value || null,
            plazo_maximo: document.getElementById('plazoMaximo').value || null,
            activa: document.getElementById('activa').value === '1',
            descripcion: document.getElementById('descripcion').value || null
        };
        
        let response;
        if (tasaEditando) {
            response = await api.put(`/tasas/${tasaEditando.id}`, data);
        } else {
            response = await api.post('/tasas', data);
        }
        
        if (response.success) {
            UI.showAlert(tasaEditando ? 'Tasa actualizada correctamente' : 'Tasa creada correctamente', 'success');
            cerrarModalCrear();
            cargarTasas();
        }
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al guardar tasa: ' + error.message, 'danger');
    }
}

async function eliminarTasa(id) {
    if (!confirm('¿Está seguro de eliminar esta tasa?')) {
        return;
    }
    
    try {
        UI.showLoading();
        
        const response = await api.delete(`/tasas/${id}`);
        
        if (response.success) {
            UI.showAlert('Tasa eliminada correctamente', 'success');
            cargarTasas();
        }
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al eliminar tasa: ' + error.message, 'danger');
    }
}

// La función logout() está definida globalmente en app.js


