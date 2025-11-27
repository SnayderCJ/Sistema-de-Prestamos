// Configuración del Sistema

document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('configuracionContainer')) {
        cargarConfiguracion();
    }
});

async function cargarConfiguracion() {
    try {
        UI.showLoading('Cargando configuración...');
        
        const response = await api.get('/configuracion');
        
        if (response.success && response.data) {
            mostrarConfiguracion(response.data);
        } else {
            UI.showAlert('Error al cargar configuración', 'danger');
        }
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al cargar configuración: ' + error.message, 'danger');
    }
}

function mostrarConfiguracion(configs) {
    const container = document.getElementById('configuracionContainer');
    
    if (!container) return;
    
    container.innerHTML = configs.map(config => `
        <div class="config-item">
            <label>${config.descripcion || config.clave}</label>
            <input type="text" 
                   class="form-control" 
                   id="config_${config.clave}" 
                   value="${config.valor || ''}"
                   data-tipo="${config.tipo || 'string'}"
                   data-clave="${config.clave}">
            <small class="text-muted">${config.clave}</small>
        </div>
    `).join('');
}

async function guardarConfiguracion() {
    const configs = [];
    const inputs = document.querySelectorAll('[id^="config_"]');
    
    inputs.forEach(input => {
        configs.push({
            clave: input.dataset.clave,
            valor: input.value,
            tipo: input.dataset.tipo
        });
    });
    
    try {
        UI.showLoading('Guardando configuración...');
        
        const response = await api.put('/configuracion', { configuraciones: configs });
        
        if (response.success) {
            UI.showAlert('Configuración guardada correctamente', 'success');
        } else {
            UI.showAlert('Error al guardar configuración', 'danger');
        }
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al guardar configuración: ' + error.message, 'danger');
    }
}

