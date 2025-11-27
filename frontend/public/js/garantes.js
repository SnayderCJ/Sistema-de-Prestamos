// Gestión de Garantes

let garantes = [];
let garanteActual = null;
let paginaActual = 1;
const porPagina = 20;

document.addEventListener('DOMContentLoaded', () => {
    cargarGarantes();
    cargarUsuario();
});

function cargarUsuario() {
    const user = auth.getCurrentUser();
    if (user) {
        document.getElementById('userName').textContent = `${user.nombre} ${user.apellido}`;
    }
}

async function cargarGarantes() {
    try {
        UI.showLoading('Cargando garantes...');
        
        const filtros = {};
        const estado = document.getElementById('filtroEstado').value;
        if (estado) filtros.estado = estado;
        
        const response = await api.get('/garantes', {
            page: paginaActual,
            per_page: porPagina,
            ...filtros
        });
        
        if (response.success) {
            garantes = response.data.items || response.data || [];
            mostrarGarantes(garantes);
            
            if (response.data.pagination) {
                mostrarPaginacion(response.data.pagination);
            }
        }
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al cargar garantes: ' + error.message, 'danger');
    }
}

function mostrarGarantes(lista) {
    const tbody = document.querySelector('#tablaGarantes tbody');
    
    if (!lista || lista.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center">No hay garantes registrados</td></tr>';
        return;
    }
    
    tbody.innerHTML = lista.map(garante => `
        <tr>
            <td>${garante.id || '-'}</td>
            <td>${garante.cedula || '-'}</td>
            <td>${garante.nombre || '-'}</td>
            <td>${garante.apellido || '-'}</td>
            <td>${garante.telefono || '-'}</td>
            <td>${garante.total_prestamos || 0}</td>
            <td>
                <span class="badge badge-${garante.estado === 'activo' ? 'success' : 'secondary'}">
                    ${garante.estado || 'activo'}
                </span>
            </td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="verDetalleGarante(${garante.id})">Ver</button>
                <button class="btn btn-sm btn-secondary" onclick="editarGarante(${garante.id})">Editar</button>
                <button class="btn btn-sm btn-danger" onclick="eliminarGarante(${garante.id})">Eliminar</button>
            </td>
        </tr>
    `).join('');
}

function mostrarPaginacion(pagination) {
    const paginacion = document.getElementById('paginacionGarantes');
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
    cargarGarantes();
}

function filtrarGarantes() {
    const busqueda = document.getElementById('buscarGarante').value.toLowerCase();
    
    if (!busqueda) {
        mostrarGarantes(garantes);
        return;
    }
    
    const filtrados = garantes.filter(g => 
        (g.nombre && g.nombre.toLowerCase().includes(busqueda)) ||
        (g.apellido && g.apellido.toLowerCase().includes(busqueda)) ||
        (g.cedula && g.cedula.includes(busqueda))
    );
    
    mostrarGarantes(filtrados);
}

function abrirModalCrearGarante() {
    garanteActual = null;
    document.getElementById('modalGaranteTitulo').textContent = 'Nuevo Garante';
    document.getElementById('formGarante').reset();
    document.getElementById('garanteId').value = '';
    document.getElementById('garanteEstado').value = 'activo';
    document.getElementById('modalGarante').style.display = 'block';
}

async function verDetalleGarante(id) {
    try {
        UI.showLoading('Cargando detalle...');
        
        const response = await api.get(`/garantes/${id}`);
        
        if (response.success) {
            garanteActual = response.data;
            mostrarDetalleGarante(garanteActual);
            document.getElementById('modalDetalleGarante').style.display = 'block';
        }
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al cargar detalle: ' + error.message, 'danger');
    }
}

function mostrarDetalleGarante(garante) {
    document.getElementById('detalleGaranteCedula').textContent = garante.cedula || '-';
    document.getElementById('detalleGaranteNombre').textContent = garante.nombre || '-';
    document.getElementById('detalleGaranteApellido').textContent = garante.apellido || '-';
    document.getElementById('detalleGaranteTelefono').textContent = garante.telefono || '-';
    document.getElementById('detalleGaranteEmail').textContent = garante.email || '-';
    document.getElementById('detalleGaranteDireccion').textContent = garante.direccion || '-';
    document.getElementById('detalleGaranteOcupacion').textContent = garante.ocupacion || '-';
    document.getElementById('detalleGaranteIngresos').textContent = formatCurrency(garante.ingresos_mensuales || 0);
    
    const estadoBadge = document.getElementById('detalleGaranteEstado');
    estadoBadge.textContent = garante.estado || 'activo';
    estadoBadge.className = `badge badge-${garante.estado === 'activo' ? 'success' : 'secondary'}`;
    
    // Cargar préstamos garantizados
    if (garante.prestamos && garante.prestamos.length > 0) {
        const tbody = document.getElementById('tablaPrestamosGarantizados');
        tbody.innerHTML = garante.prestamos.map(p => `
            <tr>
                <td>${p.numero_prestamo || '-'}</td>
                <td>${p.cliente_nombre || ''} ${p.cliente_apellido || ''}</td>
                <td>${formatCurrency(p.monto_aprobado || 0)}</td>
                <td><span class="badge badge-${p.estado || 'pendiente'}">${p.estado || 'pendiente'}</span></td>
            </tr>
        `).join('');
    } else {
        document.getElementById('tablaPrestamosGarantizados').innerHTML = 
            '<tr><td colspan="4" class="text-center">No hay préstamos garantizados</td></tr>';
    }
}

async function editarGarante(id) {
    try {
        UI.showLoading('Cargando garante...');
        
        const response = await api.get(`/garantes/${id}`);
        
        if (response.success) {
            garanteActual = response.data;
            document.getElementById('modalGaranteTitulo').textContent = 'Editar Garante';
            document.getElementById('garanteId').value = garanteActual.id;
            document.getElementById('garanteCedula').value = garanteActual.cedula || '';
            document.getElementById('garanteNombre').value = garanteActual.nombre || '';
            document.getElementById('garanteApellido').value = garanteActual.apellido || '';
            document.getElementById('garanteTelefono').value = garanteActual.telefono || '';
            document.getElementById('garanteEmail').value = garanteActual.email || '';
            document.getElementById('garanteDireccion').value = garanteActual.direccion || '';
            document.getElementById('garanteOcupacion').value = garanteActual.ocupacion || '';
            document.getElementById('garanteIngresos').value = garanteActual.ingresos_mensuales || '';
            document.getElementById('garanteEstado').value = garanteActual.estado || 'activo';
            
            document.getElementById('modalGarante').style.display = 'block';
        }
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al cargar garante: ' + error.message, 'danger');
    }
}

function editarGaranteDesdeDetalle() {
    cerrarModal('modalDetalleGarante');
    if (garanteActual) {
        editarGarante(garanteActual.id);
    }
}

async function guardarGarante(event) {
    event.preventDefault();
    
    try {
        UI.showLoading('Guardando garante...');
        
        const data = {
            cedula: document.getElementById('garanteCedula').value,
            nombre: document.getElementById('garanteNombre').value,
            apellido: document.getElementById('garanteApellido').value,
            telefono: document.getElementById('garanteTelefono').value,
            email: document.getElementById('garanteEmail').value,
            direccion: document.getElementById('garanteDireccion').value,
            ocupacion: document.getElementById('garanteOcupacion').value,
            ingresos_mensuales: document.getElementById('garanteIngresos').value || null,
            estado: document.getElementById('garanteEstado').value
        };
        
        const id = document.getElementById('garanteId').value;
        let response;
        
        if (id) {
            response = await api.put(`/garantes/${id}`, data);
        } else {
            response = await api.post('/garantes', data);
        }
        
        if (response.success) {
            UI.showAlert(id ? 'Garante actualizado correctamente' : 'Garante creado correctamente', 'success');
            cerrarModal('modalGarante');
            cargarGarantes();
        }
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al guardar garante: ' + error.message, 'danger');
    }
}

async function eliminarGarante(id) {
    if (!confirm('¿Está seguro de eliminar este garante?')) {
        return;
    }
    
    try {
        UI.showLoading('Eliminando garante...');
        
        const response = await api.delete(`/garantes/${id}`);
        
        if (response.success) {
            UI.showAlert('Garante eliminado correctamente', 'success');
            cargarGarantes();
        }
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al eliminar garante: ' + error.message, 'danger');
    }
}

function validarCedulaGarante() {
    const cedula = document.getElementById('garanteCedula').value;
    const errorDiv = document.getElementById('errorCedulaGarante');
    
    if (!cedula) {
        errorDiv.style.display = 'none';
        return;
    }
    
    if (!validateCedula(cedula)) {
        errorDiv.textContent = 'Cédula inválida';
        errorDiv.style.display = 'block';
        return false;
    }
    
    errorDiv.style.display = 'none';
    return true;
}

function cerrarModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

