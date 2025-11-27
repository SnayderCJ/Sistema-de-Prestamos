/**
 * Comprobantes Fiscales (NCF)
 */

let impuestosSeleccionados = {};

document.addEventListener('DOMContentLoaded', function() {
    cargarComprobantes();
    cargarTiposComprobantes();
    cargarClientes();
    cargarImpuestos();
    
    // Establecer fecha actual por defecto
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('fechaEmision').value = today;
});

async function cargarTiposComprobantes() {
    try {
        const response = await fetch(`${API_BASE_URL}/tipos-comprobantes`, {
            headers: {
                'Authorization': `Bearer ${getToken()}`
            }
        });

        if (response.ok) {
            const tipos = await response.json();
            const select = document.getElementById('tipoComprobanteId');
            const filtro = document.getElementById('filtroTipo');
            
            const options = tipos.map(t => `<option value="${t.id}">${t.codigo} - ${t.nombre}</option>`).join('');
            select.innerHTML = '<option value="">Seleccionar tipo</option>' + options;
            filtro.innerHTML = '<option value="">Todos los tipos</option>' + options;
        }
    } catch (error) {
        console.error('Error cargando tipos:', error);
    }
}

async function cargarClientes() {
    try {
        const response = await fetch(`${API_BASE_URL}/clientes`, {
            headers: {
                'Authorization': `Bearer ${getToken()}`
            }
        });

        if (response.ok) {
            const data = await response.json();
            const clientes = data.data || data;
            const select = document.getElementById('clienteId');
            
            select.innerHTML = '<option value="">Seleccionar cliente</option>' +
                clientes.map(c => `
                    <option value="${c.id}" data-rnc="${c.rnc || ''}">
                        ${c.cedula} - ${c.nombre} ${c.apellido}
                    </option>
                `).join('');
        }
    } catch (error) {
        console.error('Error cargando clientes:', error);
    }
}

async function cargarImpuestos() {
    try {
        const response = await fetch(`${API_BASE_URL}/impuestos`, {
            headers: {
                'Authorization': `Bearer ${getToken()}`
            }
        });

        if (response.ok) {
            const impuestos = await response.json();
            const container = document.getElementById('impuestosContainer');
            
            container.innerHTML = impuestos.map(imp => `
                <div class="form-check mb-2">
                    <input type="checkbox" class="form-check-input" id="imp_${imp.id}" 
                           value="${imp.id}" onchange="toggleImpuesto(${imp.id}, '${imp.tipo}', ${imp.valor})">
                    <label class="form-check-label" for="imp_${imp.id}">
                        ${imp.nombre} (${imp.tipo === 'porcentaje' ? imp.valor + '%' : 'RD$ ' + imp.valor})
                    </label>
                </div>
            `).join('');
        }
    } catch (error) {
        console.error('Error cargando impuestos:', error);
    }
}

function toggleImpuesto(impuestoId, tipo, valor) {
    const checkbox = document.getElementById(`imp_${impuestoId}`);
    if (!checkbox) return;
    
    if (checkbox.checked) {
        impuestosSeleccionados[impuestoId] = {
            tipo: tipo,
            valor: parseFloat(valor)
        };
    } else {
        delete impuestosSeleccionados[impuestoId];
    }
    
    calcularTotal();
}

function calcularTotal() {
    const subtotal = parseFloat(document.getElementById('montoSubtotal').value) || 0;
    let totalImpuestos = 0;
    
    Object.keys(impuestosSeleccionados).forEach(impId => {
        const imp = impuestosSeleccionados[impId];
        if (imp.tipo === 'porcentaje') {
            totalImpuestos += subtotal * (imp.valor / 100);
        } else {
            totalImpuestos += imp.valor;
        }
    });
    
    const total = subtotal + totalImpuestos;
    document.getElementById('montoTotal').value = total.toFixed(2);
}

function cargarDatosCliente() {
    const select = document.getElementById('clienteId');
    if (!select || !select.value) {
        document.getElementById('datosCliente').style.display = 'none';
        return;
    }
    
    const option = select.options[select.selectedIndex];
    const rnc = option.getAttribute('data-rnc');
    
    if (rnc) {
        document.getElementById('rncCliente').value = rnc;
        document.getElementById('datosCliente').style.display = 'block';
    } else {
        document.getElementById('datosCliente').style.display = 'none';
    }
}

function cargarRelacionado() {
    const relacionado = document.getElementById('relacionadoCon').value;
    
    const relacionadoPrestamo = document.getElementById('relacionadoPrestamo');
    const relacionadoPago = document.getElementById('relacionadoPago');
    
    if (relacionadoPrestamo) {
        relacionadoPrestamo.style.display = relacionado === 'prestamo' ? 'block' : 'none';
    }
    
    if (relacionadoPago) {
        relacionadoPago.style.display = relacionado === 'pago' ? 'block' : 'none';
    }
    
    if (relacionado === 'prestamo') {
        cargarPrestamos();
    } else if (relacionado === 'pago') {
        cargarPagos();
    }
}

async function cargarPrestamos() {
    try {
        const response = await fetch(`${API_BASE_URL}/prestamos`, {
            headers: {
                'Authorization': `Bearer ${getToken()}`
            }
        });

        if (response.ok) {
            const data = await response.json();
            const prestamos = data.data || data;
            const select = document.getElementById('prestamoId');
            
            select.innerHTML = '<option value="">Seleccionar préstamo</option>' +
                prestamos.map(p => `
                    <option value="${p.id}" data-monto="${p.monto_aprobado}">
                        ${p.numero_prestamo} - ${formatCurrency(p.monto_aprobado)}
                    </option>
                `).join('');
        }
    } catch (error) {
        console.error('Error cargando préstamos:', error);
    }
}

async function cargarPagos() {
    try {
        const response = await fetch(`${API_BASE_URL}/pagos`, {
            headers: {
                'Authorization': `Bearer ${getToken()}`
            }
        });

        if (response.ok) {
            const data = await response.json();
            const pagos = data.data || data;
            const select = document.getElementById('pagoId');
            
            select.innerHTML = '<option value="">Seleccionar pago</option>' +
                pagos.map(p => `
                    <option value="${p.id}" data-monto="${p.monto}">
                        Pago #${p.id} - ${formatCurrency(p.monto)} - ${formatDate(p.fecha_pago)}
                    </option>
                `).join('');
        }
    } catch (error) {
        console.error('Error cargando pagos:', error);
    }
}

async function cargarComprobantes() {
    try {
        const params = new URLSearchParams();
        if (document.getElementById('filtroTipo').value) {
            params.append('tipo_comprobante_id', document.getElementById('filtroTipo').value);
        }
        if (document.getElementById('filtroNCF').value) {
            params.append('numero_ncf', document.getElementById('filtroNCF').value);
        }
        if (document.getElementById('fechaDesde').value) {
            params.append('fecha_desde', document.getElementById('fechaDesde').value);
        }
        if (document.getElementById('fechaHasta').value) {
            params.append('fecha_hasta', document.getElementById('fechaHasta').value);
        }
        if (document.getElementById('filtroEstado').value) {
            params.append('estado', document.getElementById('filtroEstado').value);
        }

        const response = await fetch(`${API_BASE_URL}/comprobantes-fiscales?${params}`, {
            headers: {
                'Authorization': `Bearer ${getToken()}`
            }
        });

        if (response.ok) {
            const comprobantes = await response.json();
            mostrarComprobantes(comprobantes);
        } else {
            showAlert('Error al cargar los comprobantes', 'error');
        }
    } catch (error) {
        console.error('Error cargando comprobantes:', error);
        showAlert('Error al cargar los comprobantes', 'error');
    }
}

function mostrarComprobantes(comprobantes) {
    const tbody = document.getElementById('tablaComprobantes');
    
    if (comprobantes.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center">No hay comprobantes registrados</td></tr>';
        return;
    }

    tbody.innerHTML = comprobantes.map(c => `
        <tr>
            <td><strong>${c.numero_ncf}</strong></td>
            <td>${c.tipo_comprobante_nombre || '-'}</td>
            <td>${c.cliente_nombre || ''} ${c.cliente_apellido || ''}</td>
            <td>${formatDate(c.fecha_emision)}</td>
            <td>${formatCurrency(c.monto_subtotal)}</td>
            <td>${formatCurrency(c.monto_impuestos || 0)}</td>
            <td><strong>${formatCurrency(c.monto_total)}</strong></td>
            <td><span class="badge badge-${getEstadoBadge(c.estado)}">${c.estado}</span></td>
            <td>
                <button class="btn btn-sm btn-info" onclick="verDetalleNCF(${c.id})">Ver</button>
                ${c.estado === 'emitido' ? `<button class="btn btn-sm btn-danger" onclick="anularNCF(${c.id})">Anular</button>` : ''}
                ${c.estado === 'emitido' ? `<button class="btn btn-sm btn-success" onclick="generarFacturaElectronica(${c.id})" title="Generar Factura Electrónica">⚡ eCF</button>` : ''}
            </td>
        </tr>
    `).join('');
}

function getEstadoBadge(estado) {
    const badges = {
        'pendiente': 'warning',
        'emitido': 'success',
        'anulado': 'danger',
        'vencido': 'secondary'
    };
    return badges[estado] || 'secondary';
}

function generarNCF() {
    document.getElementById('formNCF').reset();
    impuestosSeleccionados = {};
    document.getElementById('modalNCF').style.display = 'block';
    cargarTiposComprobantes();
    cargarClientes();
    cargarImpuestos();
}

function confirmarGenerarNCF() {
    const tipoComprobanteId = document.getElementById('tipoComprobanteId').value;
    const clienteId = document.getElementById('clienteId').value;
    const montoSubtotal = parseFloat(document.getElementById('montoSubtotal').value);
    const fechaEmision = document.getElementById('fechaEmision').value;

    if (!tipoComprobanteId || !clienteId || !montoSubtotal || montoSubtotal <= 0) {
        showAlert('Complete todos los campos requeridos', 'error');
        return;
    }

    const data = {
        tipo_comprobante_id: tipoComprobanteId,
        cliente_id: clienteId,
        monto_subtotal: montoSubtotal,
        fecha_emision: fechaEmision || new Date().toISOString().split('T')[0],
        rnc_cliente: document.getElementById('rncCliente')?.value || null,
        razon_social: document.getElementById('razonSocial')?.value || null,
        prestamo_id: document.getElementById('prestamoId')?.value || null,
        pago_id: document.getElementById('pagoId')?.value || null,
        observaciones: document.getElementById('observaciones')?.value || null,
        impuestos: impuestosSeleccionados
    };

    fetch(`${API_BASE_URL}/comprobantes-fiscales`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${getToken()}`
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(err => {
                throw new Error(err.message || 'Error al generar el comprobante');
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success !== false) {
            showAlert('Comprobante fiscal generado exitosamente', 'success');
            cerrarModal('modalNCF');
            cargarComprobantes();
        } else {
            showAlert(data.message || 'Error al generar el comprobante', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert(error.message || 'Error al generar el comprobante', 'error');
    });
}

function filtrarComprobantes() {
    cargarComprobantes();
}

async function verDetalleNCF(id) {
    try {
        showLoading('Cargando detalle de comprobante...');
        const response = await fetch(`${API_BASE_URL}/comprobantes-fiscales/${id}`, {
            headers: {
                'Authorization': `Bearer ${getToken()}`
            }
        });

        if (response.ok) {
            const comprobante = await response.json();
            mostrarDetalleNCF(comprobante);
            document.getElementById('modalDetalleNCF').style.display = 'block';
        } else {
            showAlert('Error al cargar el detalle del comprobante', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('Error al cargar el detalle del comprobante', 'error');
    } finally {
        hideLoading();
    }
}

function mostrarDetalleNCF(comprobante) {
    // Actualizar ambos elementos con el mismo ID (header y tabla)
    document.querySelectorAll('#detalleNCFId').forEach(el => {
        el.textContent = comprobante.id;
    });
    document.getElementById('detalleNCFNumero').textContent = comprobante.numero_ncf || '-';
    document.getElementById('detalleNCFTipo').textContent = comprobante.tipo_comprobante || '-';
    document.getElementById('detalleNCFFecha').textContent = formatDate(comprobante.fecha_emision);
    document.getElementById('detalleNCFCliente').textContent = `${comprobante.cliente_nombre || ''} ${comprobante.cliente_apellido || ''}`;
    document.getElementById('detalleNCFRNC').textContent = comprobante.rnc_cliente || '-';
    document.getElementById('detalleNCFRazonSocial').textContent = comprobante.razon_social || '-';
    document.getElementById('detalleNCFSubtotal').textContent = formatCurrency(comprobante.monto_subtotal || 0);
    document.getElementById('detalleNCFImpuestos').textContent = formatCurrency(comprobante.monto_impuestos || 0);
    document.getElementById('detalleNCFTotal').textContent = formatCurrency(comprobante.monto_total || 0);
    document.getElementById('detalleNCFEstado').textContent = comprobante.estado || '-';
    document.getElementById('detalleNCFEstado').className = `badge ${comprobante.estado === 'anulado' ? 'badge-danger' : 'badge-success'}`;
    document.getElementById('detalleNCFPrestamo').textContent = comprobante.prestamo_id ? `Préstamo #${comprobante.prestamo_id}` : '-';
    document.getElementById('detalleNCFPago').textContent = comprobante.pago_id ? `Pago #${comprobante.pago_id}` : '-';
    document.getElementById('detalleNCFObservaciones').textContent = comprobante.observaciones || '-';
    
    // Mostrar impuestos si existen
    const impuestosDiv = document.getElementById('detalleNCFImpuestosLista');
    if (comprobante.impuestos && comprobante.impuestos.length > 0) {
        impuestosDiv.innerHTML = comprobante.impuestos.map(imp => `
            <tr>
                <td>${imp.nombre}</td>
                <td>${imp.porcentaje}%</td>
                <td>${formatCurrency(imp.monto)}</td>
            </tr>
        `).join('');
    } else {
        impuestosDiv.innerHTML = '<tr><td colspan="3" class="text-center">No hay impuestos</td></tr>';
    }
}

function anularNCF(id) {
    if (!confirm('¿Está seguro de anular este comprobante fiscal?')) {
        return;
    }

    const motivo = prompt('Ingrese el motivo de anulación:');
    if (!motivo) {
        return;
    }

    fetch(`${API_BASE_URL}/comprobantes-fiscales/${id}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${getToken()}`
        },
        body: JSON.stringify({
            anular: true,
            motivo: motivo
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Comprobante anulado exitosamente', 'success');
            cargarComprobantes();
        } else {
            showAlert(data.message || 'Error al anular el comprobante', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error al anular el comprobante', 'error');
    });
}

// ============================================
// FUNCIONES DE FACTURACIÓN ELECTRÓNICA
// ============================================

/**
 * Generar factura electrónica (XML)
 */
async function generarFacturaElectronica(comprobanteId) {
    if (!confirm('¿Desea generar la factura electrónica (eCF) para este comprobante?')) {
        return;
    }

    try {
        showLoading('Generando factura electrónica...');
        
        const response = await fetch(`${API_BASE_URL}/facturacion-electronica/generar/${comprobanteId}`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${getToken()}`
            }
        });

        const data = await response.json();

        if (response.ok && data.success) {
            showAlert('Factura electrónica generada correctamente. Ahora debe firmarla.', 'success');
            
            // Mostrar opciones de facturación electrónica
            mostrarOpcionesFacturacionElectronica(comprobanteId);
        } else {
            showAlert(data.message || 'Error al generar la factura electrónica', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('Error al generar la factura electrónica', 'error');
    } finally {
        hideLoading();
    }
}

/**
 * Firmar factura electrónica
 */
async function firmarFacturaElectronica(comprobanteId) {
    try {
        showLoading('Firmando factura electrónica...');
        
        const response = await fetch(`${API_BASE_URL}/facturacion-electronica/firmar/${comprobanteId}`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${getToken()}`
            }
        });

        const data = await response.json();

        if (response.ok && data.success) {
            if (data.firma_valida) {
                showAlert('Factura firmada correctamente. La firma es válida.', 'success');
            } else {
                showAlert('Factura firmada, pero la firma no es válida. Verifique el certificado.', 'warning');
            }
            
            mostrarOpcionesFacturacionElectronica(comprobanteId);
        } else {
            showAlert(data.message || 'Error al firmar la factura', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('Error al firmar la factura', 'error');
    } finally {
        hideLoading();
    }
}

/**
 * Enviar factura electrónica a DGII
 */
async function enviarFacturaDGII(comprobanteId) {
    if (!confirm('¿Desea enviar esta factura electrónica a DGII?')) {
        return;
    }

    try {
        showLoading('Enviando factura a DGII...');
        
        const response = await fetch(`${API_BASE_URL}/facturacion-electronica/enviar-dgii/${comprobanteId}`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${getToken()}`
            }
        });

        const data = await response.json();

        if (response.ok && data.success) {
            showAlert('Factura enviada a DGII correctamente', 'success');
            mostrarOpcionesFacturacionElectronica(comprobanteId);
        } else {
            showAlert(data.message || 'Error al enviar la factura a DGII', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('Error al enviar la factura a DGII', 'error');
    } finally {
        hideLoading();
    }
}

/**
 * Descargar XML de factura
 */
function descargarXML(comprobanteId, tipo = 'firmado') {
    window.open(`${API_BASE_URL}/facturacion-electronica/descargar-xml/${comprobanteId}?tipo=${tipo}`, '_blank');
}

/**
 * Ver QR Code de factura
 */
async function verQRCode(comprobanteId) {
    try {
        showLoading('Cargando QR Code...');
        
        const response = await fetch(`${API_BASE_URL}/facturacion-electronica/qr/${comprobanteId}`, {
            headers: {
                'Authorization': `Bearer ${getToken()}`
            }
        });

        const data = await response.json();

        if (response.ok && data.success) {
            mostrarModalQR(data.data);
        } else {
            showAlert(data.message || 'Error al obtener QR Code', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('Error al obtener QR Code', 'error');
    } finally {
        hideLoading();
    }
}

/**
 * Validar firma digital
 */
async function validarFirmaDigital(comprobanteId) {
    try {
        showLoading('Validando firma digital...');
        
        const response = await fetch(`${API_BASE_URL}/facturacion-electronica/validar-firma/${comprobanteId}`, {
            headers: {
                'Authorization': `Bearer ${getToken()}`
            }
        });

        const data = await response.json();

        if (response.ok && data.success) {
            const validacion = data.validacion;
            if (validacion.valido) {
                showAlert('✅ Firma digital válida', 'success');
            } else {
                showAlert('❌ Firma digital inválida: ' + validacion.mensaje, 'error');
            }
        } else {
            showAlert(data.message || 'Error al validar la firma', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('Error al validar la firma', 'error');
    } finally {
        hideLoading();
    }
}

/**
 * Mostrar opciones de facturación electrónica
 */
function mostrarOpcionesFacturacionElectronica(comprobanteId) {
    const modal = document.getElementById('modalFacturacionElectronica');
    if (!modal) {
        // Crear modal si no existe
        crearModalFacturacionElectronica();
    }
    
    document.getElementById('modalFacturacionElectronica').style.display = 'block';
    document.getElementById('ecfComprobanteId').value = comprobanteId;
    
    // Cargar estado actual
    cargarEstadoFacturacionElectronica(comprobanteId);
}

/**
 * Crear modal de facturación electrónica
 */
function crearModalFacturacionElectronica() {
    const modalHTML = `
        <div id="modalFacturacionElectronica" class="modal">
            <div class="modal-content modal-large">
                <div class="modal-header">
                    <h2>⚡ Facturación Electrónica (eCF)</h2>
                    <span class="close" onclick="cerrarModal('modalFacturacionElectronica')">&times;</span>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="ecfComprobanteId">
                    
                    <div id="ecfEstado" class="alert alert-info">
                        <strong>Estado:</strong> <span id="ecfEstadoTexto">Cargando...</span>
                    </div>
                    
                    <div class="btn-group-vertical" style="width: 100%; gap: 10px;">
                        <button class="btn btn-primary" onclick="generarFacturaElectronica(document.getElementById('ecfComprobanteId').value)">
                            📄 Generar XML
                        </button>
                        <button class="btn btn-warning" onclick="firmarFacturaElectronica(document.getElementById('ecfComprobanteId').value)">
                            ✍️ Firmar XML
                        </button>
                        <button class="btn btn-success" onclick="enviarFacturaDGII(document.getElementById('ecfComprobanteId').value)">
                            📤 Enviar a DGII
                        </button>
                        <button class="btn btn-info" onclick="validarFirmaDigital(document.getElementById('ecfComprobanteId').value)">
                            ✅ Validar Firma
                        </button>
                        <div class="btn-group">
                            <button class="btn btn-secondary" onclick="descargarXML(document.getElementById('ecfComprobanteId').value, 'original')">
                                📥 XML Original
                            </button>
                            <button class="btn btn-secondary" onclick="descargarXML(document.getElementById('ecfComprobanteId').value, 'firmado')">
                                📥 XML Firmado
                            </button>
                        </div>
                        <button class="btn btn-info" onclick="verQRCode(document.getElementById('ecfComprobanteId').value)">
                            📱 Ver QR Code
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHTML);
}

/**
 * Cargar estado de facturación electrónica
 */
async function cargarEstadoFacturacionElectronica(comprobanteId) {
    try {
        const response = await fetch(`${API_BASE_URL}/comprobantes-fiscales/${comprobanteId}`, {
            headers: {
                'Authorization': `Bearer ${getToken()}`
            }
        });

        if (response.ok) {
            const comprobante = await response.json();
            const estado = comprobante.estado_electronico || 'pendiente';
            
            document.getElementById('ecfEstadoTexto').textContent = estado.toUpperCase();
            
            // Actualizar clase del alert según estado
            const estadoDiv = document.getElementById('ecfEstado');
            estadoDiv.className = 'alert alert-' + (
                estado === 'aceptado' ? 'success' :
                estado === 'rechazado' ? 'danger' :
                estado === 'enviado' ? 'info' :
                estado === 'firmado' ? 'warning' :
                estado === 'generado' ? 'primary' : 'secondary'
            );
        }
    } catch (error) {
        console.error('Error cargando estado:', error);
    }
}

/**
 * Mostrar modal de QR Code
 */
function mostrarModalQR(qrData) {
    const modalHTML = `
        <div id="modalQR" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>📱 Código QR de Factura</h2>
                    <span class="close" onclick="cerrarModal('modalQR')">&times;</span>
                </div>
                <div class="modal-body text-center">
                    <p><strong>URL de Verificación:</strong></p>
                    <p><a href="${qrData.url}" target="_blank">${qrData.url}</a></p>
                    <p><strong>Datos del QR:</strong></p>
                    <pre>${JSON.stringify(qrData.data, null, 2)}</pre>
                    <p class="text-muted">Nota: En producción, aquí se mostraría la imagen del QR Code</p>
                </div>
            </div>
        </div>
    `;
    
    // Remover modal anterior si existe
    const modalAnterior = document.getElementById('modalQR');
    if (modalAnterior) {
        modalAnterior.remove();
    }
    
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    document.getElementById('modalQR').style.display = 'block';
}

