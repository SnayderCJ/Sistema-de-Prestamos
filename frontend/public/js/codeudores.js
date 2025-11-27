// Gestión de Codeudores y Fiadores

let codeudores = [];
let codeudorActual = null;
let tipoActual = 'codeudores';
let paginaActual = 1;
const porPagina = 20;

document.addEventListener('DOMContentLoaded', () => {
    cargarCodeudores();
    cargarPrestamos();
    cargarUsuario();
});

function cargarUsuario() {
    const user = auth.getCurrentUser();
    if (user) {
        document.getElementById('userName').textContent = `${user.nombre} ${user.apellido}`;
    }
}

async function cargarPrestamos() {
    try {
        const response = await api.get('/prestamos', { page: 1, per_page: 100 });
        
        if (response.success) {
            const prestamosList = response.data.items || response.data || [];
            const select = document.getElementById('filtroPrestamo');
            
            select.innerHTML = '<option value="">Todos los préstamos</option>' +
                prestamosList.map(p => 
                    `<option value="${p.id}">${p.numero_prestamo} - ${p.cliente_nombre || ''} ${p.cliente_apellido || ''}</option>`
                ).join('');
        }
    } catch (error) {
        console.error('Error cargando préstamos:', error);
    }
}

function cambiarTab(tipo) {
    tipoActual = tipo;
    
    // Actualizar tabs visuales
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    
    cargarCodeudores();
}

async function cargarCodeudores() {
    try {
        UI.showLoading('Cargando codeudores/fiadores...');
        
        const filtros = { tipo: tipoActual };
        const estado = document.getElementById('filtroEstado').value;
        if (estado) filtros.estado = estado;
        
        const prestamoId = document.getElementById('filtroPrestamo').value;
        if (prestamoId) filtros.prestamo_id = prestamoId;
        
        const response = await api.get('/codeudores', {
            page: paginaActual,
            per_page: porPagina,
            ...filtros
        });
        
        if (response.success) {
            codeudores = response.data.items || response.data || [];
            mostrarCodeudores(codeudores);
            
            if (response.data.pagination) {
                mostrarPaginacion(response.data.pagination);
            }
        }
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al cargar codeudores/fiadores: ' + error.message, 'danger');
    }
}

function mostrarCodeudores(lista) {
    const tbody = document.querySelector('#tablaCodeudores tbody');
    
    if (!lista || lista.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center">No hay codeudores/fiadores registrados</td></tr>';
        return;
    }
    
    tbody.innerHTML = lista.map(codeudor => `
        <tr>
            <td>${codeudor.id || '-'}</td>
            <td>${codeudor.cedula || '-'}</td>
            <td>${codeudor.nombre || ''} ${codeudor.apellido || ''}</td>
            <td>${codeudor.telefono || '-'}</td>
            <td>
                <span class="badge badge-${codeudor.tipo === 'codeudor' ? 'primary' : 'info'}">
                    ${codeudor.tipo === 'codeudor' ? 'Codeudor' : 'Fiador'}
                </span>
            </td>
            <td>${codeudor.total_prestamos || 0}</td>
            <td>
                <span class="badge badge-${codeudor.estado === 'activo' ? 'success' : 'secondary'}">
                    ${codeudor.estado || 'activo'}
                </span>
            </td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="verDetalleCodeudor(${codeudor.id})">Ver</button>
                <button class="btn btn-sm btn-secondary" onclick="editarCodeudor(${codeudor.id})">Editar</button>
                <button class="btn btn-sm btn-danger" onclick="eliminarCodeudor(${codeudor.id})">Eliminar</button>
            </td>
        </tr>
    `).join('');
}

function mostrarPaginacion(pagination) {
    const paginacion = document.getElementById('paginacionCodeudores');
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
    cargarCodeudores();
}

function filtrarCodeudores() {
    const busqueda = document.getElementById('buscarCodeudor').value.toLowerCase();
    
    if (!busqueda) {
        mostrarCodeudores(codeudores);
        return;
    }
    
    const filtrados = codeudores.filter(c => 
        (c.nombre && c.nombre.toLowerCase().includes(busqueda)) ||
        (c.apellido && c.apellido.toLowerCase().includes(busqueda)) ||
        (c.cedula && c.cedula.includes(busqueda))
    );
    
    mostrarCodeudores(filtrados);
}

function abrirModalCrearCodeudor() {
    codeudorActual = null;
    document.getElementById('modalCodeudorTitulo').textContent = 'Nuevo Codeudor/Fiador';
    document.getElementById('formCodeudor').reset();
    document.getElementById('codeudorId').value = '';
    document.getElementById('codeudorTipo').value = tipoActual === 'codeudores' ? 'codeudor' : 'fiador';
    document.getElementById('codeudorEstado').value = 'activo';
    document.getElementById('modalCodeudor').style.display = 'block';
}

async function verDetalleCodeudor(id) {
    try {
        UI.showLoading('Cargando detalle...');
        
        const response = await api.get(`/codeudores/${id}`);
        
        if (response.success) {
            codeudorActual = response.data;
            mostrarDetalleCodeudor(codeudorActual);
            document.getElementById('modalDetalleCodeudor').style.display = 'block';
        }
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al cargar detalle: ' + error.message, 'danger');
    }
}

function mostrarDetalleCodeudor(codeudor) {
    const tipoBadge = document.getElementById('detalleCodeudorTipo');
    tipoBadge.textContent = codeudor.tipo === 'codeudor' ? 'Codeudor' : 'Fiador';
    tipoBadge.className = `badge badge-${codeudor.tipo === 'codeudor' ? 'primary' : 'info'}`;
    
    document.getElementById('detalleCodeudorCedula').textContent = codeudor.cedula || '-';
    document.getElementById('detalleCodeudorNombre').textContent = codeudor.nombre || '-';
    document.getElementById('detalleCodeudorApellido').textContent = codeudor.apellido || '-';
    document.getElementById('detalleCodeudorTelefono').textContent = codeudor.telefono || '-';
    document.getElementById('detalleCodeudorEmail').textContent = codeudor.email || '-';
    document.getElementById('detalleCodeudorDireccion').textContent = codeudor.direccion || '-';
    document.getElementById('detalleCodeudorOcupacion').textContent = codeudor.ocupacion || '-';
    document.getElementById('detalleCodeudorIngresos').textContent = formatCurrency(codeudor.ingresos_mensuales || 0);
    document.getElementById('detalleCodeudorRelacion').textContent = codeudor.relacion_cliente || '-';
    
    const estadoBadge = document.getElementById('detalleCodeudorEstado');
    estadoBadge.textContent = codeudor.estado || 'activo';
    estadoBadge.className = `badge badge-${codeudor.estado === 'activo' ? 'success' : 'secondary'}`;
    
    // Cargar préstamos asociados
    if (codeudor.prestamos && codeudor.prestamos.length > 0) {
        const tbody = document.getElementById('tablaPrestamosCodeudor');
        tbody.innerHTML = codeudor.prestamos.map(p => `
            <tr>
                <td>${p.numero_prestamo || '-'}</td>
                <td>${p.cliente_nombre || ''} ${p.cliente_apellido || ''}</td>
                <td>${formatCurrency(p.monto_aprobado || 0)}</td>
                <td><span class="badge badge-${p.estado || 'pendiente'}">${p.estado || 'pendiente'}</span></td>
            </tr>
        `).join('');
    } else {
        document.getElementById('tablaPrestamosCodeudor').innerHTML = 
            '<tr><td colspan="4" class="text-center">No hay préstamos asociados</td></tr>';
    }
}

async function editarCodeudor(id) {
    try {
        UI.showLoading('Cargando codeudor/fiador...');
        
        const response = await api.get(`/codeudores/${id}`);
        
        if (response.success) {
            codeudorActual = response.data;
            document.getElementById('modalCodeudorTitulo').textContent = 'Editar Codeudor/Fiador';
            document.getElementById('codeudorId').value = codeudorActual.id;
            document.getElementById('codeudorTipo').value = codeudorActual.tipo || 'codeudor';
            document.getElementById('codeudorCedula').value = codeudorActual.cedula || '';
            document.getElementById('codeudorNombre').value = codeudorActual.nombre || '';
            document.getElementById('codeudorApellido').value = codeudorActual.apellido || '';
            document.getElementById('codeudorTelefono').value = codeudorActual.telefono || '';
            document.getElementById('codeudorEmail').value = codeudorActual.email || '';
            document.getElementById('codeudorDireccion').value = codeudorActual.direccion || '';
            document.getElementById('codeudorOcupacion').value = codeudorActual.ocupacion || '';
            document.getElementById('codeudorIngresos').value = codeudorActual.ingresos_mensuales || '';
            document.getElementById('codeudorRelacion').value = codeudorActual.relacion_cliente || '';
            document.getElementById('codeudorEstado').value = codeudorActual.estado || 'activo';
            
            document.getElementById('modalCodeudor').style.display = 'block';
        }
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al cargar codeudor/fiador: ' + error.message, 'danger');
    }
}

function editarCodeudorDesdeDetalle() {
    cerrarModal('modalDetalleCodeudor');
    if (codeudorActual) {
        editarCodeudor(codeudorActual.id);
    }
}

async function guardarCodeudor(event) {
    event.preventDefault();
    
    try {
        UI.showLoading('Guardando codeudor/fiador...');
        
        const data = {
            tipo: document.getElementById('codeudorTipo').value,
            cedula: document.getElementById('codeudorCedula').value,
            nombre: document.getElementById('codeudorNombre').value,
            apellido: document.getElementById('codeudorApellido').value,
            telefono: document.getElementById('codeudorTelefono').value,
            email: document.getElementById('codeudorEmail').value,
            direccion: document.getElementById('codeudorDireccion').value,
            ocupacion: document.getElementById('codeudorOcupacion').value,
            ingresos_mensuales: document.getElementById('codeudorIngresos').value || null,
            relacion_cliente: document.getElementById('codeudorRelacion').value,
            estado: document.getElementById('codeudorEstado').value
        };
        
        const id = document.getElementById('codeudorId').value;
        let response;
        
        if (id) {
            response = await api.put(`/codeudores/${id}`, data);
        } else {
            response = await api.post('/codeudores', data);
        }
        
        if (response.success) {
            UI.showAlert(id ? 'Codeudor/Fiador actualizado correctamente' : 'Codeudor/Fiador creado correctamente', 'success');
            cerrarModal('modalCodeudor');
            cargarCodeudores();
        }
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al guardar codeudor/fiador: ' + error.message, 'danger');
    }
}

async function eliminarCodeudor(id) {
    if (!confirm('¿Está seguro de eliminar este codeudor/fiador?')) {
        return;
    }
    
    try {
        UI.showLoading('Eliminando codeudor/fiador...');
        
        const response = await api.delete(`/codeudores/${id}`);
        
        if (response.success) {
            UI.showAlert('Codeudor/Fiador eliminado correctamente', 'success');
            cargarCodeudores();
        }
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al eliminar codeudor/fiador: ' + error.message, 'danger');
    }
}

function validarCedulaCodeudor() {
    const cedula = document.getElementById('codeudorCedula').value;
    const errorDiv = document.getElementById('errorCedulaCodeudor');
    
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

