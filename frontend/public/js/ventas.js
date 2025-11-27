// Gestión de Ventas

let ventas = [];
let ventaActual = null;
let articulosVenta = [];
let clientesVenta = [];
let articulosDisponibles = [];
let paginaActual = 1;
const porPagina = 20;

document.addEventListener('DOMContentLoaded', () => {
    cargarVentas();
    cargarClientes();
    cargarArticulos();
    cargarUsuario();
});

function cargarUsuario() {
    const user = auth.getCurrentUser();
    if (user) {
        document.getElementById('userName').textContent = `${user.nombre} ${user.apellido}`;
    }
}

async function cargarClientes() {
    try {
        const response = await api.get('/clientes', { page: 1, per_page: 1000 });
        
        if (response.success) {
            clientesVenta = response.data.items || response.data || [];
            const select = document.getElementById('ventaClienteId');
            
            select.innerHTML = '<option value="">Seleccionar cliente</option>' +
                clientesVenta.map(c => 
                    `<option value="${c.id}">${c.nombre} ${c.apellido} - ${c.cedula}</option>`
                ).join('');
        }
    } catch (error) {
        console.error('Error cargando clientes:', error);
    }
}

async function cargarArticulos() {
    try {
        const response = await api.get('/articulos', { page: 1, per_page: 1000, estado: 'activo' });
        
        if (response.success) {
            articulosDisponibles = response.data.items || response.data || [];
        }
    } catch (error) {
        console.error('Error cargando artículos:', error);
    }
}

function cargarDatosCliente() {
    const clienteId = document.getElementById('ventaClienteId').value;
    // Aquí se pueden cargar datos adicionales del cliente si es necesario
}

async function buscarArticulosVenta() {
    const busqueda = document.getElementById('buscarArticuloVenta').value.toLowerCase();
    const resultados = document.getElementById('resultadosArticulos');
    
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
            <div class="dropdown-item" onclick="agregarArticuloVenta(${art.id})">
                <strong>${art.codigo || ''}</strong> - ${art.nombre || ''}
                <br><small>Precio: ${formatCurrency(art.precio_venta_contado || 0)}</small>
            </div>
        `).join('');
        resultados.style.display = 'block';
    } else {
        resultados.style.display = 'none';
    }
}

function agregarArticuloVenta(articuloId) {
    const articulo = articulosDisponibles.find(a => a.id === articuloId);
    if (!articulo) return;
    
    const metodoPago = document.getElementById('ventaMetodoPago').value;
    const precio = metodoPago === 'contado' ? (articulo.precio_venta_contado || articulo.precio_venta) : (articulo.precio_venta_credito || articulo.precio_venta);
    
    const item = {
        id: articulo.id,
        codigo: articulo.codigo,
        nombre: articulo.nombre,
        cantidad: 1,
        precio_unitario: precio,
        descuento: 0,
        subtotal: precio
    };
    
    articulosVenta.push(item);
    document.getElementById('buscarArticuloVenta').value = '';
    document.getElementById('resultadosArticulos').style.display = 'none';
    actualizarTablaArticulosVenta();
    calcularTotalesVenta();
}

function actualizarTablaArticulosVenta() {
    const tbody = document.getElementById('tbodyArticulosVenta');
    
    if (articulosVenta.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center">No hay artículos agregados</td></tr>';
        return;
    }
    
    tbody.innerHTML = articulosVenta.map((item, index) => `
        <tr>
            <td>${item.codigo || ''} - ${item.nombre || ''}</td>
            <td>
                <input type="number" class="form-control form-control-sm" 
                       value="${item.cantidad}" min="1" 
                       onchange="actualizarCantidadArticulo(${index}, this.value)">
            </td>
            <td>${formatCurrency(item.precio_unitario)}</td>
            <td>
                <input type="number" class="form-control form-control-sm" 
                       value="${item.descuento}" min="0" max="100" step="0.01"
                       onchange="actualizarDescuentoArticulo(${index}, this.value)">
            </td>
            <td>${formatCurrency(item.subtotal)}</td>
            <td>
                <button class="btn btn-sm btn-danger" onclick="eliminarArticuloVenta(${index})">Eliminar</button>
            </td>
        </tr>
    `).join('');
}

function actualizarCantidadArticulo(index, cantidad) {
    const item = articulosVenta[index];
    item.cantidad = parseInt(cantidad) || 1;
    calcularSubtotalArticulo(index);
    calcularTotalesVenta();
}

function actualizarDescuentoArticulo(index, descuento) {
    const item = articulosVenta[index];
    item.descuento = parseFloat(descuento) || 0;
    calcularSubtotalArticulo(index);
    calcularTotalesVenta();
}

function calcularSubtotalArticulo(index) {
    const item = articulosVenta[index];
    const subtotalSinDescuento = item.precio_unitario * item.cantidad;
    const descuentoMonto = (subtotalSinDescuento * item.descuento) / 100;
    item.subtotal = subtotalSinDescuento - descuentoMonto;
    actualizarTablaArticulosVenta();
}

function eliminarArticuloVenta(index) {
    articulosVenta.splice(index, 1);
    actualizarTablaArticulosVenta();
    calcularTotalesVenta();
}

function actualizarPrecios() {
    const metodoPago = document.getElementById('ventaMetodoPago').value;
    
    articulosVenta.forEach(item => {
        const articulo = articulosDisponibles.find(a => a.id === item.id);
        if (articulo) {
            item.precio_unitario = metodoPago === 'contado' 
                ? (articulo.precio_venta_contado || articulo.precio_venta) 
                : (articulo.precio_venta_credito || articulo.precio_venta);
            calcularSubtotalArticulo(articulosVenta.indexOf(item));
        }
    });
    
    calcularTotalesVenta();
}

function calcularTotalesVenta() {
    const subtotal = articulosVenta.reduce((sum, item) => sum + item.subtotal, 0);
    const descuentoGeneral = parseFloat(document.getElementById('ventaDescuentoGeneral').value) || 0;
    const descuentoMonto = (subtotal * descuentoGeneral) / 100;
    const total = subtotal - descuentoMonto;
    
    document.getElementById('ventaSubtotal').textContent = formatCurrency(subtotal);
    document.getElementById('ventaDescuento').textContent = formatCurrency(descuentoMonto);
    document.getElementById('ventaTotal').textContent = formatCurrency(total);
}

async function cargarVentas() {
    try {
        UI.showLoading('Cargando ventas...');
        
        const filtros = {};
        const estado = document.getElementById('filtroEstado').value;
        if (estado) filtros.estado = estado;
        
        const metodoPago = document.getElementById('filtroMetodoPago').value;
        if (metodoPago) filtros.metodo_pago = metodoPago;
        
        const fechaDesde = document.getElementById('filtroFechaDesde').value;
        if (fechaDesde) filtros.fecha_desde = fechaDesde;
        
        const fechaHasta = document.getElementById('filtroFechaHasta').value;
        if (fechaHasta) filtros.fecha_hasta = fechaHasta;
        
        const response = await api.get('/ventas', {
            page: paginaActual,
            per_page: porPagina,
            ...filtros
        });
        
        if (response.success) {
            ventas = response.data.items || response.data || [];
            mostrarVentas(ventas);
            
            if (response.data.pagination) {
                mostrarPaginacion(response.data.pagination);
            }
        }
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al cargar ventas: ' + error.message, 'danger');
    }
}

function mostrarVentas(lista) {
    const tbody = document.querySelector('#tablaVentas tbody');
    
    if (!lista || lista.length === 0) {
        tbody.innerHTML = '<tr><td colspan="10" class="text-center">No hay ventas registradas</td></tr>';
        return;
    }
    
    tbody.innerHTML = lista.map(venta => `
        <tr>
            <td>${venta.numero_venta || venta.numero || '-'}</td>
            <td>${formatDate(venta.fecha || venta.fecha_venta)}</td>
            <td>${venta.cliente_nombre || ''} ${venta.cliente_apellido || ''}</td>
            <td>${venta.total_articulos || 0}</td>
            <td>${formatCurrency(venta.subtotal || 0)}</td>
            <td>${formatCurrency(venta.descuento || 0)}</td>
            <td>${formatCurrency(venta.total || venta.monto_total || 0)}</td>
            <td>
                <span class="badge badge-${venta.metodo_pago === 'contado' ? 'success' : 'info'}">
                    ${venta.metodo_pago === 'contado' ? 'Al Contado' : 'A Crédito'}
                </span>
            </td>
            <td>
                <span class="badge badge-${venta.estado === 'completada' ? 'success' : venta.estado === 'cancelada' ? 'danger' : 'warning'}">
                    ${venta.estado || 'pendiente'}
                </span>
            </td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="verDetalleVenta(${venta.id})">Ver</button>
                ${venta.estado === 'pendiente' ? `<button class="btn btn-sm btn-danger" onclick="cancelarVenta(${venta.id})">Cancelar</button>` : ''}
            </td>
        </tr>
    `).join('');
}

function mostrarPaginacion(pagination) {
    const paginacion = document.getElementById('paginacionVentas');
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
    cargarVentas();
}

function filtrarVentas() {
    const busqueda = document.getElementById('buscarVenta').value.toLowerCase();
    
    if (!busqueda) {
        mostrarVentas(ventas);
        return;
    }
    
    const filtrados = ventas.filter(v => 
        (v.numero_venta && v.numero_venta.toLowerCase().includes(busqueda)) ||
        (v.cliente_nombre && v.cliente_nombre.toLowerCase().includes(busqueda)) ||
        (v.cliente_apellido && v.cliente_apellido.toLowerCase().includes(busqueda))
    );
    
    mostrarVentas(filtrados);
}

function abrirModalNuevaVenta() {
    articulosVenta = [];
    document.getElementById('formVenta').reset();
    document.getElementById('ventaMetodoPago').value = 'contado';
    document.getElementById('ventaDescuentoGeneral').value = '0';
    actualizarTablaArticulosVenta();
    calcularTotalesVenta();
    document.getElementById('modalVenta').style.display = 'block';
}

async function verDetalleVenta(id) {
    try {
        UI.showLoading('Cargando detalle...');
        
        const response = await api.get(`/ventas/${id}`);
        
        if (response.success) {
            ventaActual = response.data;
            mostrarDetalleVenta(ventaActual);
            document.getElementById('modalDetalleVenta').style.display = 'block';
        }
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al cargar detalle: ' + error.message, 'danger');
    }
}

function mostrarDetalleVenta(venta) {
    document.getElementById('detalleVentaNumero').textContent = venta.numero_venta || venta.numero || '-';
    document.getElementById('detalleVentaFecha').textContent = formatDate(venta.fecha || venta.fecha_venta);
    document.getElementById('detalleVentaCliente').textContent = `${venta.cliente_nombre || ''} ${venta.cliente_apellido || ''}`;
    document.getElementById('detalleVentaMetodoPago').textContent = venta.metodo_pago === 'contado' ? 'Al Contado' : 'A Crédito';
    document.getElementById('detalleVentaSubtotal').textContent = formatCurrency(venta.subtotal || 0);
    document.getElementById('detalleVentaDescuento').textContent = formatCurrency(venta.descuento || 0);
    document.getElementById('detalleVentaTotal').textContent = formatCurrency(venta.total || venta.monto_total || 0);
    
    const estadoBadge = document.getElementById('detalleVentaEstado');
    estadoBadge.textContent = venta.estado || 'pendiente';
    estadoBadge.className = `badge badge-${venta.estado === 'completada' ? 'success' : venta.estado === 'cancelada' ? 'danger' : 'warning'}`;
    
    // Mostrar artículos
    if (venta.articulos && venta.articulos.length > 0) {
        const tbody = document.getElementById('detalleVentaArticulos');
        tbody.innerHTML = venta.articulos.map(a => `
            <tr>
                <td>${a.codigo || ''} - ${a.nombre || ''}</td>
                <td>${a.cantidad || 0}</td>
                <td>${formatCurrency(a.precio_unitario || 0)}</td>
                <td>${a.descuento || 0}%</td>
                <td>${formatCurrency(a.subtotal || 0)}</td>
            </tr>
        `).join('');
    } else {
        document.getElementById('detalleVentaArticulos').innerHTML = 
            '<tr><td colspan="5" class="text-center">No hay artículos</td></tr>';
    }
}

async function guardarVenta(event) {
    event.preventDefault();
    
    if (articulosVenta.length === 0) {
        UI.showAlert('Debe agregar al menos un artículo', 'warning');
        return;
    }
    
    try {
        UI.showLoading('Guardando venta...');
        
        const data = {
            cliente_id: document.getElementById('ventaClienteId').value,
            metodo_pago: document.getElementById('ventaMetodoPago').value,
            descuento_general: parseFloat(document.getElementById('ventaDescuentoGeneral').value) || 0,
            observaciones: document.getElementById('ventaObservaciones').value,
            articulos: articulosVenta.map(item => ({
                articulo_id: item.id,
                cantidad: item.cantidad,
                precio_unitario: item.precio_unitario,
                descuento: item.descuento
            }))
        };
        
        const response = await api.post('/ventas', data);
        
        if (response.success) {
            UI.showAlert('Venta creada correctamente', 'success');
            cerrarModal('modalVenta');
            cargarVentas();
        }
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al guardar venta: ' + error.message, 'danger');
    }
}

async function cancelarVenta(id) {
    if (!confirm('¿Está seguro de cancelar esta venta?')) {
        return;
    }
    
    try {
        UI.showLoading('Cancelando venta...');
        
        const response = await api.put(`/ventas/${id}`, { estado: 'cancelada' });
        
        if (response.success) {
            UI.showAlert('Venta cancelada correctamente', 'success');
            cargarVentas();
        }
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al cancelar venta: ' + error.message, 'danger');
    }
}

function imprimirVenta() {
    if (!ventaActual) return;
    
    window.print();
}

function cerrarModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

