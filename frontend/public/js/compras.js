// Gestión de Compras

let compras = [];
let compraActual = null;
let articulosCompra = [];
let proveedores = [];
let articulosDisponibles = [];
let paginaActual = 1;
const porPagina = 20;

document.addEventListener('DOMContentLoaded', () => {
    cargarCompras();
    cargarProveedores();
    cargarArticulos();
    cargarUsuario();
});

function cargarUsuario() {
    const user = auth.getCurrentUser();
    if (user) {
        document.getElementById('userName').textContent = `${user.nombre} ${user.apellido}`;
    }
}

async function cargarProveedores() {
    try {
        const response = await api.get('/proveedores', { page: 1, per_page: 1000 });
        
        if (response.success) {
            proveedores = response.data.items || response.data || [];
            const select = document.getElementById('compraProveedorId');
            const selectFiltro = document.getElementById('filtroProveedor');
            
            const options = proveedores.map(p => 
                `<option value="${p.id}">${p.nombre} - ${p.cedula || p.rnc || ''}</option>`
            ).join('');
            select.innerHTML = '<option value="">Seleccionar proveedor</option>' + options;
            selectFiltro.innerHTML = '<option value="">Todos los proveedores</option>' + options;
        }
    } catch (error) {
        console.error('Error cargando proveedores:', error);
    }
}

async function cargarArticulos() {
    try {
        const response = await api.get('/articulos', { page: 1, per_page: 1000 });
        
        if (response.success) {
            articulosDisponibles = response.data.items || response.data || [];
        }
    } catch (error) {
        console.error('Error cargando artículos:', error);
    }
}

async function buscarArticulosCompra() {
    const busqueda = document.getElementById('buscarArticuloCompra').value.toLowerCase();
    const resultados = document.getElementById('resultadosArticulosCompra');
    
    if (!busqueda) {
        resultados.style.display = 'none';
        return;
    }
    
    const filtrados = articulosDisponibles.filter(a => 
        (a.codigo && a.codigo.toLowerCase().includes(busqueda)) ||
        (a.nombre && a.nombre.toLowerCase().includes(busqueda))
    ).slice(0, 10);
    
    if (filtrados.length > 0) {
        resultados.innerHTML = filtrados.map(art => `
            <div class="dropdown-item" onclick="agregarArticuloCompra(${art.id})">
                <strong>${art.codigo || ''}</strong> - ${art.nombre || ''}
                <br><small>Precio Compra: ${formatCurrency(art.precio_compra || 0)}</small>
            </div>
        `).join('');
        resultados.style.display = 'block';
    } else {
        resultados.style.display = 'none';
    }
}

function agregarArticuloCompra(articuloId) {
    const articulo = articulosDisponibles.find(a => a.id === articuloId);
    if (!articulo) return;
    
    const item = {
        id: articulo.id,
        codigo: articulo.codigo,
        nombre: articulo.nombre,
        cantidad: 1,
        precio_unitario: articulo.precio_compra || 0,
        descuento: 0,
        subtotal: articulo.precio_compra || 0
    };
    
    articulosCompra.push(item);
    document.getElementById('buscarArticuloCompra').value = '';
    document.getElementById('resultadosArticulosCompra').style.display = 'none';
    actualizarTablaArticulosCompra();
    calcularTotalesCompra();
}

function actualizarTablaArticulosCompra() {
    const tbody = document.getElementById('tbodyArticulosCompra');
    
    if (articulosCompra.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center">No hay artículos agregados</td></tr>';
        return;
    }
    
    tbody.innerHTML = articulosCompra.map((item, index) => `
        <tr>
            <td>${item.codigo || ''} - ${item.nombre || ''}</td>
            <td>
                <input type="number" class="form-control form-control-sm" 
                       value="${item.cantidad}" min="1" 
                       onchange="actualizarCantidadArticuloCompra(${index}, this.value)">
            </td>
            <td>
                <input type="number" class="form-control form-control-sm" 
                       value="${item.precio_unitario}" step="0.01" min="0"
                       onchange="actualizarPrecioArticuloCompra(${index}, this.value)">
            </td>
            <td>
                <input type="number" class="form-control form-control-sm" 
                       value="${item.descuento}" min="0" max="100" step="0.01"
                       onchange="actualizarDescuentoArticuloCompra(${index}, this.value)">
            </td>
            <td>${formatCurrency(item.subtotal)}</td>
            <td>
                <button class="btn btn-sm btn-danger" onclick="eliminarArticuloCompra(${index})">Eliminar</button>
            </td>
        </tr>
    `).join('');
}

function actualizarCantidadArticuloCompra(index, cantidad) {
    const item = articulosCompra[index];
    item.cantidad = parseInt(cantidad) || 1;
    calcularSubtotalArticuloCompra(index);
    calcularTotalesCompra();
}

function actualizarPrecioArticuloCompra(index, precio) {
    const item = articulosCompra[index];
    item.precio_unitario = parseFloat(precio) || 0;
    calcularSubtotalArticuloCompra(index);
    calcularTotalesCompra();
}

function actualizarDescuentoArticuloCompra(index, descuento) {
    const item = articulosCompra[index];
    item.descuento = parseFloat(descuento) || 0;
    calcularSubtotalArticuloCompra(index);
    calcularTotalesCompra();
}

function calcularSubtotalArticuloCompra(index) {
    const item = articulosCompra[index];
    const subtotalSinDescuento = item.precio_unitario * item.cantidad;
    const descuentoMonto = (subtotalSinDescuento * item.descuento) / 100;
    item.subtotal = subtotalSinDescuento - descuentoMonto;
    actualizarTablaArticulosCompra();
}

function eliminarArticuloCompra(index) {
    articulosCompra.splice(index, 1);
    actualizarTablaArticulosCompra();
    calcularTotalesCompra();
}

function calcularTotalesCompra() {
    const subtotal = articulosCompra.reduce((sum, item) => sum + item.subtotal, 0);
    const descuentoGeneral = parseFloat(document.getElementById('compraDescuentoGeneral').value) || 0;
    const descuentoMonto = (subtotal * descuentoGeneral) / 100;
    const total = subtotal - descuentoMonto;
    
    document.getElementById('compraSubtotal').textContent = formatCurrency(subtotal);
    document.getElementById('compraDescuento').textContent = formatCurrency(descuentoMonto);
    document.getElementById('compraTotal').textContent = formatCurrency(total);
}

async function cargarCompras() {
    try {
        UI.showLoading('Cargando compras...');
        
        const filtros = {};
        const estado = document.getElementById('filtroEstado').value;
        if (estado) filtros.estado = estado;
        
        const proveedorId = document.getElementById('filtroProveedor').value;
        if (proveedorId) filtros.proveedor_id = proveedorId;
        
        const fechaDesde = document.getElementById('filtroFechaDesde').value;
        if (fechaDesde) filtros.fecha_desde = fechaDesde;
        
        const fechaHasta = document.getElementById('filtroFechaHasta').value;
        if (fechaHasta) filtros.fecha_hasta = fechaHasta;
        
        const response = await api.get('/compras', {
            page: paginaActual,
            per_page: porPagina,
            ...filtros
        });
        
        if (response.success) {
            compras = response.data.items || response.data || [];
            mostrarCompras(compras);
            
            if (response.data.pagination) {
                mostrarPaginacion(response.data.pagination);
            }
        }
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al cargar compras: ' + error.message, 'danger');
    }
}

function mostrarCompras(lista) {
    const tbody = document.querySelector('#tablaCompras tbody');
    
    if (!lista || lista.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center">No hay compras registradas</td></tr>';
        return;
    }
    
    tbody.innerHTML = lista.map(compra => `
        <tr>
            <td>${compra.numero_compra || compra.numero || '-'}</td>
            <td>${formatDate(compra.fecha || compra.fecha_compra)}</td>
            <td>${compra.proveedor_nombre || '-'}</td>
            <td>${compra.total_articulos || 0}</td>
            <td>${formatCurrency(compra.subtotal || 0)}</td>
            <td>${formatCurrency(compra.descuento || 0)}</td>
            <td>${formatCurrency(compra.total || compra.monto_total || 0)}</td>
            <td>
                <span class="badge badge-${compra.estado === 'completada' ? 'success' : compra.estado === 'cancelada' ? 'danger' : 'warning'}">
                    ${compra.estado || 'pendiente'}
                </span>
            </td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="verDetalleCompra(${compra.id})">Ver</button>
                ${compra.estado === 'pendiente' ? `<button class="btn btn-sm btn-danger" onclick="cancelarCompra(${compra.id})">Cancelar</button>` : ''}
            </td>
        </tr>
    `).join('');
}

function mostrarPaginacion(pagination) {
    const paginacion = document.getElementById('paginacionCompras');
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
    cargarCompras();
}

function filtrarCompras() {
    const busqueda = document.getElementById('buscarCompra').value.toLowerCase();
    
    if (!busqueda) {
        mostrarCompras(compras);
        return;
    }
    
    const filtrados = compras.filter(c => 
        (c.numero_compra && c.numero_compra.toLowerCase().includes(busqueda)) ||
        (c.proveedor_nombre && c.proveedor_nombre.toLowerCase().includes(busqueda))
    );
    
    mostrarCompras(filtrados);
}

function abrirModalNuevaCompra() {
    articulosCompra = [];
    document.getElementById('formCompra').reset();
    document.getElementById('compraDescuentoGeneral').value = '0';
    actualizarTablaArticulosCompra();
    calcularTotalesCompra();
    document.getElementById('modalCompra').style.display = 'block';
}

async function verDetalleCompra(id) {
    try {
        UI.showLoading('Cargando detalle...');
        
        const response = await api.get(`/compras/${id}`);
        
        if (response.success) {
            compraActual = response.data;
            mostrarDetalleCompra(compraActual);
            document.getElementById('modalDetalleCompra').style.display = 'block';
        }
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al cargar detalle: ' + error.message, 'danger');
    }
}

function mostrarDetalleCompra(compra) {
    document.getElementById('detalleCompraNumero').textContent = compra.numero_compra || compra.numero || '-';
    document.getElementById('detalleCompraFecha').textContent = formatDate(compra.fecha || compra.fecha_compra);
    document.getElementById('detalleCompraProveedor').textContent = compra.proveedor_nombre || '-';
    document.getElementById('detalleCompraNumeroFactura').textContent = compra.numero_factura || '-';
    document.getElementById('detalleCompraSubtotal').textContent = formatCurrency(compra.subtotal || 0);
    document.getElementById('detalleCompraDescuento').textContent = formatCurrency(compra.descuento || 0);
    document.getElementById('detalleCompraTotal').textContent = formatCurrency(compra.total || compra.monto_total || 0);
    
    const estadoBadge = document.getElementById('detalleCompraEstado');
    estadoBadge.textContent = compra.estado || 'pendiente';
    estadoBadge.className = `badge badge-${compra.estado === 'completada' ? 'success' : compra.estado === 'cancelada' ? 'danger' : 'warning'}`;
    
    // Mostrar artículos
    if (compra.articulos && compra.articulos.length > 0) {
        const tbody = document.getElementById('detalleCompraArticulos');
        tbody.innerHTML = compra.articulos.map(a => `
            <tr>
                <td>${a.codigo || ''} - ${a.nombre || ''}</td>
                <td>${a.cantidad || 0}</td>
                <td>${formatCurrency(a.precio_unitario || 0)}</td>
                <td>${a.descuento || 0}%</td>
                <td>${formatCurrency(a.subtotal || 0)}</td>
            </tr>
        `).join('');
    } else {
        document.getElementById('detalleCompraArticulos').innerHTML = 
            '<tr><td colspan="5" class="text-center">No hay artículos</td></tr>';
    }
}

async function guardarCompra(event) {
    event.preventDefault();
    
    if (articulosCompra.length === 0) {
        UI.showAlert('Debe agregar al menos un artículo', 'warning');
        return;
    }
    
    try {
        UI.showLoading('Guardando compra...');
        
        const data = {
            proveedor_id: document.getElementById('compraProveedorId').value,
            numero_factura: document.getElementById('compraNumeroFactura').value,
            descuento_general: parseFloat(document.getElementById('compraDescuentoGeneral').value) || 0,
            observaciones: document.getElementById('compraObservaciones').value,
            articulos: articulosCompra.map(item => ({
                articulo_id: item.id,
                cantidad: item.cantidad,
                precio_unitario: item.precio_unitario,
                descuento: item.descuento
            }))
        };
        
        const response = await api.post('/compras', data);
        
        if (response.success) {
            UI.showAlert('Compra creada correctamente', 'success');
            cerrarModal('modalCompra');
            cargarCompras();
        }
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al guardar compra: ' + error.message, 'danger');
    }
}

async function cancelarCompra(id) {
    if (!confirm('¿Está seguro de cancelar esta compra?')) {
        return;
    }
    
    try {
        UI.showLoading('Cancelando compra...');
        
        const response = await api.put(`/compras/${id}`, { estado: 'cancelada' });
        
        if (response.success) {
            UI.showAlert('Compra cancelada correctamente', 'success');
            cargarCompras();
        }
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al cancelar compra: ' + error.message, 'danger');
    }
}

function imprimirCompra() {
    if (!compraActual) return;
    
    window.print();
}

function cerrarModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

