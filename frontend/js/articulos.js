// Gestión de Artículos

let articulos = [];
let articuloActual = null;
let categorias = [];
let paginaActual = 1;
const porPagina = 20;

document.addEventListener('DOMContentLoaded', () => {
    cargarArticulos();
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
        const response = await api.get('/categorias-articulos', { page: 1, per_page: 1000 });
        
        if (response.success) {
            categorias = response.data.items || response.data || [];
            const select = document.getElementById('articuloCategoria');
            const selectFiltro = document.getElementById('filtroCategoria');
            
            const options = categorias.map(c => `<option value="${c.id}">${c.nombre}</option>`).join('');
            select.innerHTML = '<option value="">Seleccionar categoría</option>' + options;
            selectFiltro.innerHTML = '<option value="">Todas las categorías</option>' + options;
        }
    } catch (error) {
        console.error('Error cargando categorías:', error);
    }
}

function calcularPreciosVenta() {
    const precioCompra = parseFloat(document.getElementById('articuloPrecioCompra').value) || 0;
    const utilidadContado = parseFloat(document.getElementById('articuloUtilidadContado').value) || 0;
    const utilidadCredito = parseFloat(document.getElementById('articuloUtilidadCredito').value) || 0;
    
    const precioVentaContado = precioCompra * (1 + utilidadContado / 100);
    const precioVentaCredito = precioCompra * (1 + utilidadCredito / 100);
    
    document.getElementById('articuloPrecioVentaContado').value = precioVentaContado.toFixed(2);
    document.getElementById('articuloPrecioVentaCredito').value = precioVentaCredito.toFixed(2);
}

function actualizarPrecioManual(tipo) {
    // Si se ingresa un precio manual, se usa ese en lugar del calculado
    const campoManual = tipo === 'contado' 
        ? document.getElementById('articuloPrecioVentaContadoManual')
        : document.getElementById('articuloPrecioVentaCreditoManual');
    
    const campoCalculado = tipo === 'contado'
        ? document.getElementById('articuloPrecioVentaContado')
        : document.getElementById('articuloPrecioVentaCredito');
    
    if (campoManual.value) {
        campoCalculado.value = campoManual.value;
    } else {
        calcularPreciosVenta();
    }
}

async function cargarArticulos() {
    try {
        UI.showLoading('Cargando artículos...');
        
        const filtros = {};
        const estado = document.getElementById('filtroEstado').value;
        if (estado) filtros.estado = estado;
        
        const categoriaId = document.getElementById('filtroCategoria').value;
        if (categoriaId) filtros.categoria_id = categoriaId;
        
        const response = await api.get('/articulos', {
            page: paginaActual,
            per_page: porPagina,
            ...filtros
        });
        
        if (response.success) {
            articulos = response.data.items || response.data || [];
            mostrarArticulos(articulos);
            
            if (response.data.pagination) {
                mostrarPaginacion(response.data.pagination);
            }
        }
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al cargar artículos: ' + error.message, 'danger');
    }
}

function mostrarArticulos(lista) {
    const tbody = document.querySelector('#tablaArticulos tbody');
    
    if (!lista || lista.length === 0) {
        tbody.innerHTML = '<tr><td colspan="10" class="text-center">No hay artículos registrados</td></tr>';
        return;
    }
    
    tbody.innerHTML = lista.map(articulo => `
        <tr>
            <td>${articulo.codigo || '-'}</td>
            <td>${articulo.nombre || '-'}</td>
            <td>${articulo.categoria_nombre || '-'}</td>
            <td>${articulo.stock || 0}</td>
            <td>${formatCurrency(articulo.precio_compra || 0)}</td>
            <td>
                <small>Contado: ${articulo.utilidad_contado || 0}%</small><br>
                <small>Crédito: ${articulo.utilidad_credito || 0}%</small>
            </td>
            <td>${formatCurrency(articulo.precio_venta_contado || articulo.precio_venta || 0)}</td>
            <td>${formatCurrency(articulo.precio_venta_credito || articulo.precio_venta || 0)}</td>
            <td>
                <span class="badge badge-${articulo.estado === 'activo' ? 'success' : articulo.estado === 'agotado' ? 'warning' : 'secondary'}">
                    ${articulo.estado || 'activo'}
                </span>
            </td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="verDetalleArticulo(${articulo.id})">Ver</button>
                <button class="btn btn-sm btn-secondary" onclick="editarArticulo(${articulo.id})">Editar</button>
                <button class="btn btn-sm btn-danger" onclick="eliminarArticulo(${articulo.id})">Eliminar</button>
            </td>
        </tr>
    `).join('');
}

function mostrarPaginacion(pagination) {
    const paginacion = document.getElementById('paginacionArticulos');
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
    cargarArticulos();
}

function filtrarArticulos() {
    const busqueda = document.getElementById('buscarArticulo').value.toLowerCase();
    
    if (!busqueda) {
        mostrarArticulos(articulos);
        return;
    }
    
    const filtrados = articulos.filter(a => 
        (a.codigo && a.codigo.toLowerCase().includes(busqueda)) ||
        (a.nombre && a.nombre.toLowerCase().includes(busqueda))
    );
    
    mostrarArticulos(filtrados);
}

function abrirModalCrearArticulo() {
    articuloActual = null;
    document.getElementById('modalArticuloTitulo').textContent = 'Nuevo Artículo';
    document.getElementById('formArticulo').reset();
    document.getElementById('articuloId').value = '';
    document.getElementById('articuloUtilidadContado').value = '30';
    document.getElementById('articuloUtilidadCredito').value = '40';
    document.getElementById('articuloEstado').value = 'activo';
    document.getElementById('articuloStock').value = '0';
    calcularPreciosVenta();
    document.getElementById('modalArticulo').style.display = 'block';
}

async function verDetalleArticulo(id) {
    try {
        UI.showLoading('Cargando detalle...');
        
        const response = await api.get(`/articulos/${id}`);
        
        if (response.success) {
            articuloActual = response.data;
            mostrarDetalleArticulo(articuloActual);
            document.getElementById('modalDetalleArticulo').style.display = 'block';
        }
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al cargar detalle: ' + error.message, 'danger');
    }
}

function mostrarDetalleArticulo(articulo) {
    document.getElementById('detalleArticuloCodigo').textContent = articulo.codigo || '-';
    document.getElementById('detalleArticuloNombre').textContent = articulo.nombre || '-';
    document.getElementById('detalleArticuloCategoria').textContent = articulo.categoria_nombre || '-';
    document.getElementById('detalleArticuloStock').textContent = articulo.stock || 0;
    document.getElementById('detalleArticuloPrecioCompra').textContent = formatCurrency(articulo.precio_compra || 0);
    document.getElementById('detalleArticuloUtilidadContado').textContent = `${articulo.utilidad_contado || 0}%`;
    document.getElementById('detalleArticuloUtilidadCredito').textContent = `${articulo.utilidad_credito || 0}%`;
    document.getElementById('detalleArticuloPrecioVentaContado').textContent = formatCurrency(articulo.precio_venta_contado || articulo.precio_venta || 0);
    document.getElementById('detalleArticuloPrecioVentaCredito').textContent = formatCurrency(articulo.precio_venta_credito || articulo.precio_venta || 0);
    document.getElementById('detalleArticuloDescripcion').textContent = articulo.descripcion || '-';
    
    const estadoBadge = document.getElementById('detalleArticuloEstado');
    estadoBadge.textContent = articulo.estado || 'activo';
    estadoBadge.className = `badge badge-${articulo.estado === 'activo' ? 'success' : articulo.estado === 'agotado' ? 'warning' : 'secondary'}`;
}

async function editarArticulo(id) {
    try {
        UI.showLoading('Cargando artículo...');
        
        const response = await api.get(`/articulos/${id}`);
        
        if (response.success) {
            articuloActual = response.data;
            document.getElementById('modalArticuloTitulo').textContent = 'Editar Artículo';
            document.getElementById('articuloId').value = articuloActual.id;
            document.getElementById('articuloCodigo').value = articuloActual.codigo || '';
            document.getElementById('articuloNombre').value = articuloActual.nombre || '';
            document.getElementById('articuloCategoria').value = articuloActual.categoria_id || '';
            document.getElementById('articuloStock').value = articuloActual.stock || 0;
            document.getElementById('articuloPrecioCompra').value = articuloActual.precio_compra || '';
            document.getElementById('articuloUtilidadContado').value = articuloActual.utilidad_contado || 30;
            document.getElementById('articuloUtilidadCredito').value = articuloActual.utilidad_credito || 40;
            document.getElementById('articuloDescripcion').value = articuloActual.descripcion || '';
            document.getElementById('articuloEstado').value = articuloActual.estado || 'activo';
            
            // Precios manuales si existen
            document.getElementById('articuloPrecioVentaContadoManual').value = articuloActual.precio_venta_contado_manual || '';
            document.getElementById('articuloPrecioVentaCreditoManual').value = articuloActual.precio_venta_credito_manual || '';
            
            calcularPreciosVenta();
            
            document.getElementById('modalArticulo').style.display = 'block';
        }
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al cargar artículo: ' + error.message, 'danger');
    }
}

function editarArticuloDesdeDetalle() {
    cerrarModal('modalDetalleArticulo');
    if (articuloActual) {
        editarArticulo(articuloActual.id);
    }
}

async function guardarArticulo(event) {
    event.preventDefault();
    
    try {
        UI.showLoading('Guardando artículo...');
        
        const precioVentaContadoManual = document.getElementById('articuloPrecioVentaContadoManual').value;
        const precioVentaCreditoManual = document.getElementById('articuloPrecioVentaCreditoManual').value;
        
        const data = {
            codigo: document.getElementById('articuloCodigo').value,
            nombre: document.getElementById('articuloNombre').value,
            categoria_id: document.getElementById('articuloCategoria').value,
            stock: parseInt(document.getElementById('articuloStock').value) || 0,
            precio_compra: parseFloat(document.getElementById('articuloPrecioCompra').value),
            utilidad_contado: parseFloat(document.getElementById('articuloUtilidadContado').value),
            utilidad_credito: parseFloat(document.getElementById('articuloUtilidadCredito').value),
            precio_venta_contado: precioVentaContadoManual || parseFloat(document.getElementById('articuloPrecioVentaContado').value),
            precio_venta_credito: precioVentaCreditoManual || parseFloat(document.getElementById('articuloPrecioVentaCredito').value),
            descripcion: document.getElementById('articuloDescripcion').value,
            estado: document.getElementById('articuloEstado').value
        };
        
        const id = document.getElementById('articuloId').value;
        let response;
        
        if (id) {
            response = await api.put(`/articulos/${id}`, data);
        } else {
            response = await api.post('/articulos', data);
        }
        
        if (response.success) {
            UI.showAlert(id ? 'Artículo actualizado correctamente' : 'Artículo creado correctamente', 'success');
            cerrarModal('modalArticulo');
            cargarArticulos();
        }
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al guardar artículo: ' + error.message, 'danger');
    }
}

async function eliminarArticulo(id) {
    if (!confirm('¿Está seguro de eliminar este artículo?')) {
        return;
    }
    
    try {
        UI.showLoading('Eliminando artículo...');
        
        const response = await api.delete(`/articulos/${id}`);
        
        if (response.success) {
            UI.showAlert('Artículo eliminado correctamente', 'success');
            cargarArticulos();
        }
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al eliminar artículo: ' + error.message, 'danger');
    }
}

function cerrarModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

