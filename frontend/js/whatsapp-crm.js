/**
 * CRM WhatsApp
 */

let numeroConversacionActual = null;

document.addEventListener('DOMContentLoaded', function() {
    cargarEstadisticas();
    cargarConversaciones();
    cargarHistorial();
    cargarClientes();
    
    document.getElementById('mensajeEnviar').addEventListener('input', function() {
        const length = this.value.length;
        document.getElementById('contadorCaracteres').textContent = `${length} / 4096 caracteres`;
    });
});

async function cargarEstadisticas() {
    try {
        const response = await api.get('/whatsapp/estadisticas');
        document.getElementById('totalEnviados').textContent = response.total_enviados || 0;
        document.getElementById('totalRecibidos').textContent = response.total_recibidos || 0;
        document.getElementById('totalConversaciones').textContent = response.total_conversaciones || 0;
    } catch (error) {
        console.error('Error cargando estadísticas:', error);
    }
}

async function cargarConversaciones() {
    try {
        const filters = {
            busqueda: document.getElementById('buscarConversacion').value,
            fecha_desde: document.getElementById('filtroFechaDesde').value,
            fecha_hasta: document.getElementById('filtroFechaHasta').value
        };
        
        const params = new URLSearchParams();
        Object.keys(filters).forEach(key => {
            if (filters[key]) params.append(key, filters[key]);
        });
        
        const response = await api.get(`/whatsapp/conversaciones?${params}`);
        mostrarConversaciones(response);
    } catch (error) {
        console.error('Error:', error);
        UI.showAlert('Error al cargar conversaciones', 'danger');
    }
}

function mostrarConversaciones(conversaciones) {
    const tbody = document.getElementById('tablaConversaciones').querySelector('tbody');
    
    if (!conversaciones || conversaciones.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center">No hay conversaciones</td></tr>';
        return;
    }

    tbody.innerHTML = conversaciones.map(c => {
        const ultimoMensaje = (c.mensajes || '').split(' | ')[0] || '';
        return `
        <tr>
            <td><strong>${c.numero || ''}</strong></td>
            <td>${ultimoMensaje.substring(0, 50)}${ultimoMensaje.length > 50 ? '...' : ''}</td>
            <td>${c.total_mensajes || 0}</td>
            <td>${formatDate(c.ultima_conversacion || '')}</td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="verConversacion('${c.numero}')">Ver</button>
                <button class="btn btn-sm btn-success" onclick="enviarMensajeRapido('${c.numero}')">Responder</button>
            </td>
        </tr>
    `;
    }).join('');
}

async function cargarHistorial() {
    try {
        const filters = {
            fecha_desde: document.getElementById('filtroFechaDesde').value,
            fecha_hasta: document.getElementById('filtroFechaHasta').value
        };
        
        const params = new URLSearchParams();
        Object.keys(filters).forEach(key => {
            if (filters[key]) params.append(key, filters[key]);
        });
        
        const response = await api.get(`/whatsapp/historial?${params}`);
        mostrarHistorial(response);
    } catch (error) {
        console.error('Error:', error);
    }
}

function mostrarHistorial(historial) {
    const tbody = document.getElementById('tablaHistorial').querySelector('tbody');
    
    if (!historial || historial.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center">No hay historial</td></tr>';
        return;
    }

    tbody.innerHTML = historial.map(h => `
        <tr>
            <td>${formatDate(h.fecha_envio || '')}</td>
            <td>${h.numero || ''}</td>
            <td>${(h.mensaje || '').substring(0, 100)}${(h.mensaje || '').length > 100 ? '...' : ''}</td>
            <td><span class="badge badge-${h.tipo === 'recibido' ? 'info' : 'primary'}">${h.tipo || 'enviado'}</span></td>
            <td><span class="badge badge-${h.estado === 'enviado' ? 'success' : h.estado === 'error' ? 'danger' : 'info'}">${h.estado || 'enviado'}</span></td>
        </tr>
    `).join('');
}

async function cargarClientes() {
    try {
        const response = await api.get('/clientes');
        const select = document.getElementById('clienteEnviar');
        select.innerHTML = '<option value="">Seleccionar cliente...</option>' + 
            response.map(c => `<option value="${c.id}" data-telefono="${c.telefono || ''}">${c.nombre} ${c.apellido} - ${c.telefono || 'Sin teléfono'}</option>`).join('');
    } catch (error) {
        console.error('Error cargando clientes:', error);
    }
}

function cargarTelefonoCliente() {
    const select = document.getElementById('clienteEnviar');
    const option = select.options[select.selectedIndex];
    if (option && option.dataset.telefono) {
        document.getElementById('numeroEnviar').value = option.dataset.telefono;
    }
}

function abrirModalEnviar() {
    document.getElementById('modalEnviar').classList.add('active');
}

function cerrarModalEnviar() {
    document.getElementById('modalEnviar').classList.remove('active');
    document.getElementById('formEnviar').reset();
    document.getElementById('contadorCaracteres').textContent = '0 / 4096 caracteres';
}

async function enviarMensaje(event) {
    event.preventDefault();
    
    const data = {
        numero: document.getElementById('numeroEnviar').value,
        mensaje: document.getElementById('mensajeEnviar').value
    };

    try {
        UI.showLoading('Enviando mensaje...');
        const response = await api.post('/whatsapp/enviar', data);
        
        if (response.success !== false) {
            UI.showAlert('Mensaje enviado exitosamente', 'success');
            cerrarModalEnviar();
            cargarConversaciones();
            cargarHistorial();
            cargarEstadisticas();
        } else {
            UI.showAlert(response.message || 'Error al enviar mensaje', 'danger');
        }
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        console.error('Error:', error);
        UI.showAlert(error.message || 'Error al enviar mensaje', 'danger');
    }
}

function enviarMensajeRapido(numero) {
    document.getElementById('numeroEnviar').value = numero;
    document.getElementById('clienteEnviar').value = '';
    abrirModalEnviar();
}

async function verConversacion(numero) {
    numeroConversacionActual = numero;
    document.getElementById('tituloConversacion').textContent = `Conversación - ${numero}`;
    
    try {
        const response = await api.get(`/whatsapp/historial?numero=${numero}`);
        mostrarMensajesConversacion(response);
        document.getElementById('modalConversacion').classList.add('active');
    } catch (error) {
        UI.showAlert('Error al cargar conversación', 'danger');
    }
}

function mostrarMensajesConversacion(mensajes) {
    const contenedor = document.getElementById('mensajesConversacion');
    
    if (!mensajes || mensajes.length === 0) {
        contenedor.innerHTML = '<p class="text-center">No hay mensajes</p>';
        return;
    }

    contenedor.innerHTML = mensajes.map(m => {
        const esEnviado = m.estado === 'enviado' && m.tipo !== 'recibido';
        const clase = esEnviado ? 'alert-info' : 'alert-secondary';
        const alineacion = esEnviado ? 'text-right' : 'text-left';
        
        return `
        <div class="alert ${clase} ${alineacion}" style="margin-bottom: 10px;">
            <small><strong>${formatDate(m.fecha_envio || '')}</strong></small>
            <p style="margin: 5px 0;">${(m.mensaje || '').replace(/\n/g, '<br>')}</p>
        </div>
    `;
    }).join('');
    
    // Scroll al final
    contenedor.scrollTop = contenedor.scrollHeight;
}

function cerrarModalConversacion() {
    document.getElementById('modalConversacion').classList.remove('active');
    numeroConversacionActual = null;
}

async function enviarRespuestaRapida() {
    const mensaje = document.getElementById('respuestaRapida').value;
    
    if (!mensaje || !numeroConversacionActual) {
        UI.showAlert('Escriba un mensaje', 'warning');
        return;
    }

    try {
        UI.showLoading('Enviando...');
        const response = await api.post('/whatsapp/enviar', {
            numero: numeroConversacionActual,
            mensaje: mensaje
        });
        
        if (response.success !== false) {
            UI.showAlert('Mensaje enviado', 'success');
            document.getElementById('respuestaRapida').value = '';
            verConversacion(numeroConversacionActual);
            cargarConversaciones();
            cargarEstadisticas();
        } else {
            UI.showAlert('Error al enviar mensaje', 'danger');
        }
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al enviar mensaje', 'danger');
    }
}

