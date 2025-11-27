// Notificaciones JavaScript

let paginaActual = 1;
const porPagina = 20;

document.addEventListener('DOMContentLoaded', () => {
    cargarNotificaciones();
    actualizarContador();
    
    // Actualizar cada 30 segundos
    setInterval(() => {
        cargarNotificaciones();
        actualizarContador();
    }, 30000);
});

async function cargarNotificaciones() {
    const filtroTipo = document.getElementById('filtroTipo').value;
    const filtroLeida = document.getElementById('filtroLeida').value;
    
    const params = {
        page: paginaActual,
        per_page: porPagina
    };
    
    if (filtroTipo) {
        params.tipo = filtroTipo;
    }
    
    if (filtroLeida !== '') {
        params.leida = filtroLeida;
    }
    
    try {
        UI.showLoading('Cargando notificaciones...');
        
        const response = await api.get('/notificaciones', params);
        
        if (response.success && response.data) {
            mostrarNotificaciones(response.data.data);
            mostrarPaginacion(response.data.pagination);
        } else {
            UI.showAlert('Error al cargar notificaciones', 'danger');
        }
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al cargar notificaciones: ' + error.message, 'danger');
    }
}

function mostrarNotificaciones(notificaciones) {
    const container = document.getElementById('listaNotificaciones');
    const sinNotificaciones = document.getElementById('sinNotificaciones');
    
    if (!notificaciones || notificaciones.length === 0) {
        container.innerHTML = '';
        sinNotificaciones.style.display = 'block';
        return;
    }
    
    sinNotificaciones.style.display = 'none';
    
    container.innerHTML = notificaciones.map(notif => `
        <div class="notificacion-item ${notif.leida ? 'leida' : 'no-leida'}" onclick="marcarLeida(${notif.id})">
            <div class="notificacion-icon">
                ${obtenerIconoTipo(notif.tipo)}
            </div>
            <div class="notificacion-content">
                <div class="notificacion-header">
                    <h3>${notif.titulo}</h3>
                    <span class="notificacion-fecha">${formatearFecha(notif.fecha_creacion)}</span>
                </div>
                <p class="notificacion-mensaje">${notif.mensaje}</p>
                ${notif.leida ? '' : '<span class="badge badge-primary">Nueva</span>'}
            </div>
        </div>
    `).join('');
}

function obtenerIconoTipo(tipo) {
    const iconos = {
        'info': 'ℹ️',
        'success': '✅',
        'warning': '⚠️',
        'error': '❌',
        'alert': '🚨'
    };
    return iconos[tipo] || '📢';
}

function formatearFecha(fecha) {
    const date = new Date(fecha);
    const ahora = new Date();
    const diff = ahora - date;
    const minutos = Math.floor(diff / 60000);
    const horas = Math.floor(minutos / 60);
    const dias = Math.floor(horas / 24);
    
    if (minutos < 1) return 'Hace un momento';
    if (minutos < 60) return `Hace ${minutos} minuto${minutos > 1 ? 's' : ''}`;
    if (horas < 24) return `Hace ${horas} hora${horas > 1 ? 's' : ''}`;
    if (dias < 7) return `Hace ${dias} día${dias > 1 ? 's' : ''}`;
    
    return date.toLocaleDateString('es-DO', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

async function marcarLeida(notificacionId) {
    try {
        await api.put('/notificaciones/marcar-leida', {
            notificacion_id: notificacionId
        });
        
        cargarNotificaciones();
        actualizarContador();
    } catch (error) {
        UI.showAlert('Error al marcar notificación: ' + error.message, 'danger');
    }
}

async function marcarTodasLeidas() {
    try {
        UI.showLoading('Marcando notificaciones...');
        
        await api.put('/notificaciones/marcar-todas-leidas', {});
        
        UI.hideLoading();
        UI.showAlert('Todas las notificaciones marcadas como leídas', 'success');
        
        cargarNotificaciones();
        actualizarContador();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al marcar notificaciones: ' + error.message, 'danger');
    }
}

async function actualizarContador() {
    try {
        const response = await api.get('/notificaciones/cantidad-no-leidas');
        
        if (response.success && response.cantidad !== undefined) {
            const badge = document.getElementById('badgeNotificaciones');
            if (badge) {
                if (response.cantidad > 0) {
                    badge.textContent = response.cantidad;
                    badge.style.display = 'inline-block';
                } else {
                    badge.style.display = 'none';
                }
            }
        }
    } catch (error) {
        console.error('Error actualizando contador:', error);
    }
}

function mostrarPaginacion(pagination) {
    const container = document.getElementById('paginacion');
    
    if (!pagination || pagination.total_pages <= 1) {
        container.style.display = 'none';
        return;
    }
    
    container.style.display = 'flex';
    
    let html = '';
    
    if (pagination.page > 1) {
        html += `<button class="btn btn-sm" onclick="cambiarPagina(${pagination.page - 1})">Anterior</button>`;
    }
    
    html += `<span>Página ${pagination.page} de ${pagination.total_pages}</span>`;
    
    if (pagination.page < pagination.total_pages) {
        html += `<button class="btn btn-sm" onclick="cambiarPagina(${pagination.page + 1})">Siguiente</button>`;
    }
    
    container.innerHTML = html;
}

function cambiarPagina(pagina) {
    paginaActual = pagina;
    cargarNotificaciones();
    window.scrollTo(0, 0);
}

