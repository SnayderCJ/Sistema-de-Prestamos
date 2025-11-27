// ============================================
// Gestión de Rutas
// ============================================

document.addEventListener('DOMContentLoaded', () => {
    cargarRutas();
    cargarCobradores();
});

async function cargarRutas() {
    try {
        UI.showLoading();
        
        const filters = {};
        
        if (document.getElementById('filtroEstado').value) {
            filters.estado = document.getElementById('filtroEstado').value;
        }
        
        if (document.getElementById('filtroFechaDesde').value) {
            filters.fecha_desde = document.getElementById('filtroFechaDesde').value;
        }
        
        if (document.getElementById('filtroFechaHasta').value) {
            filters.fecha_hasta = document.getElementById('filtroFechaHasta').value;
        }
        
        const response = await api.get('/rutas', filters);
        
        if (response.success) {
            mostrarRutas(response.data.items || response.data);
        }
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al cargar rutas: ' + error.message, 'danger');
    }
}

function mostrarRutas(rutas) {
    const tbody = document.getElementById('tbodyRutas');
    
    const columns = [
        { field: 'nombre_ruta' },
        { 
            render: (item) => {
                const nombre = item.supervisor_nombre || '';
                const apellido = item.supervisor_apellido || '';
                return `${nombre} ${apellido}`.trim() || '-';
            }
        },
        { 
            render: (item) => {
                const nombre = item.cobrador_nombre || '';
                const apellido = item.cobrador_apellido || '';
                return `${nombre} ${apellido}`.trim() || '-';
            }
        },
        { render: (item) => UI.formatDate(item.fecha_ruta) },
        { 
            render: (item) => createBadge(item.estado || 'N/A', item.estado || 'info'),
            sanitize: false
        },
        { field: 'total_visitas', render: (item) => item.total_visitas || 0 },
        {
            render: (item) => {
                return `<button class="btn btn-sm btn-primary" onclick="event.stopPropagation(); verDetalle(${item.id})">Ver</button>`;
            },
            sanitize: false
        }
    ];
    
    const dataWithOnclick = rutas.map(ruta => ({
        ...ruta,
        onclick: `verDetalle(${ruta.id})`
    }));
    
    renderSafeTable(tbody, dataWithOnclick, columns, 'No hay rutas');
                <button class="btn btn-sm btn-primary" onclick="event.stopPropagation(); verDetalle(${ruta.id})">Ver</button>
            </td>
        </tr>
    `).join('');
}

function filtrarRutas() {
    cargarRutas();
}

async function cargarCobradores() {
    try {
        const response = await api.get('/usuarios?rol=cobrador');
        if (response.success) {
            const select = document.getElementById('cobradorId');
            select.innerHTML = '<option value="">Seleccionar cobrador...</option>' +
                response.data.items.map(cobrador => 
                    `<option value="${cobrador.id}">${cobrador.nombre} ${cobrador.apellido}</option>`
                ).join('');
        }
    } catch (error) {
        console.error('Error cargando cobradores:', error);
    }
}

function abrirModalCrear() {
    document.getElementById('modalCrearRuta').classList.add('show');
}

function cerrarModalCrear() {
    document.getElementById('modalCrearRuta').classList.remove('show');
    document.getElementById('formCrearRuta').reset();
}

async function crearRuta() {
    const form = document.getElementById('formCrearRuta');
    
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    try {
        UI.showLoading();
        
        const data = {
            nombre_ruta: document.getElementById('nombreRuta').value,
            fecha_ruta: document.getElementById('fechaRuta').value,
            cobrador_id: document.getElementById('cobradorId').value || null
        };
        
        const response = await api.post('/rutas', data);
        
        if (response.success) {
            UI.showAlert('Ruta creada correctamente', 'success');
            cerrarModalCrear();
            cargarRutas();
        }
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al crear ruta: ' + error.message, 'danger');
    }
}

async function verDetalle(id) {
    try {
        UI.showLoading('Cargando detalle de la ruta...');
        
        const response = await api.get(`/rutas/${id}`);
        
        if (response.success) {
            mostrarDetalleRuta(response.data);
            document.getElementById('modalDetalleRuta').classList.add('show');
        }
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al cargar el detalle: ' + error.message, 'danger');
    }
}

function mostrarDetalleRuta(ruta) {
    document.getElementById('detalleNombreRuta').textContent = ruta.nombre_ruta || '-';
    document.getElementById('detalleSupervisor').textContent = `${ruta.supervisor_nombre || ''} ${ruta.supervisor_apellido || ''}`;
    document.getElementById('detalleCobrador').textContent = `${ruta.cobrador_nombre || ''} ${ruta.cobrador_apellido || ''}`;
    document.getElementById('detalleFecha').textContent = UI.formatDate(ruta.fecha_ruta);
    document.getElementById('detalleEstado').textContent = ruta.estado || '-';
    document.getElementById('detalleEstado').className = `badge badge-${ruta.estado || 'pendiente'}`;
    document.getElementById('detalleTotalVisitas').textContent = ruta.total_visitas || 0;
}

function cerrarModalDetalle() {
    document.getElementById('modalDetalleRuta').classList.remove('show');
}

// La función logout() está definida globalmente en app.js


