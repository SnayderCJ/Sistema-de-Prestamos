/**
 * Utilidades Globales
 */

// Función para mostrar loading
function showLoading(message = 'Cargando...') {
    const loading = document.createElement('div');
    loading.className = 'loading-overlay';
    loading.innerHTML = `
        <div class="loading-spinner"></div>
        <p>${message}</p>
    `;
    document.body.appendChild(loading);
}

function hideLoading() {
    const loading = document.querySelector('.loading-overlay');
    if (loading) {
        loading.remove();
    }
}

// Función para validar formularios
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;
    
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('error');
            isValid = false;
        } else {
            field.classList.remove('error');
        }
    });
    
    return isValid;
}

// Función para limpiar formularios
function resetForm(formId) {
    const form = document.getElementById(formId);
    if (form) {
        form.reset();
        form.querySelectorAll('.error').forEach(field => {
            field.classList.remove('error');
        });
    }
}

// Función para confirmar acciones
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// Función para copiar al portapapeles
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showAlert('Copiado al portapapeles', 'success');
    }).catch(() => {
        showAlert('Error al copiar', 'error');
    });
}

// Función para descargar archivo
function downloadFile(url, filename) {
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Función para formatear números
function formatNumber(number, decimals = 2) {
    return new Intl.NumberFormat('es-DO', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    }).format(number);
}

// Función para validar email
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// Función para validar cédula dominicana
function validateCedula(cedula) {
    if (!cedula || cedula.length !== 11) return false;
    if (!/^\d+$/.test(cedula)) return false;
    
    let sum = 0;
    const weights = [1, 2, 1, 2, 1, 2, 1, 2, 1, 2];
    
    for (let i = 0; i < 10; i++) {
        let digit = parseInt(cedula[i]) * weights[i];
        if (digit > 9) digit = Math.floor(digit / 10) + (digit % 10);
        sum += digit;
    }
    
    const checkDigit = (10 - (sum % 10)) % 10;
    return checkDigit === parseInt(cedula[10]);
}

// Función para debounce
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Función para sanitizar HTML y prevenir XSS
function sanitizeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Función para insertar contenido de forma segura usando textContent
function setTextContent(element, text) {
    if (!element) return;
    element.textContent = text || '';
}

// Función para insertar HTML de forma segura (solo cuando sea necesario)
function setSafeHtml(element, html) {
    if (!element) return;
    element.innerHTML = sanitizeHtml(html);
}

// Función helper para crear elementos de forma segura
function createElement(tag, attributes = {}, textContent = '') {
    const element = document.createElement(tag);
    Object.keys(attributes).forEach(key => {
        if (key === 'className') {
            element.className = attributes[key];
        } else if (key === 'onclick') {
            // Para eventos, usar addEventListener es más seguro, pero mantenemos compatibilidad
            element.setAttribute('onclick', attributes[key]);
        } else {
            element.setAttribute(key, attributes[key]);
        }
    });
    if (textContent) {
        element.textContent = textContent;
    }
    return element;
}

// Función helper para crear filas de tabla de forma segura
function createTableRow(cells, rowAttributes = {}) {
    const tr = createElement('tr', rowAttributes);
    cells.forEach(cell => {
        const td = typeof cell === 'string' 
            ? createElement('td', {}, cell)
            : createElement('td', cell.attributes || {}, cell.text || '');
        if (cell.innerHTML) {
            // Solo si realmente necesitamos HTML (como badges), sanitizamos
            td.innerHTML = sanitizeHtml(cell.innerHTML);
        }
        tr.appendChild(td);
    });
    return tr;
}

// Función helper para construir tablas de forma segura
function buildSafeTable(headers, rows, tableAttributes = {}) {
    const table = createElement('table', { class: 'table', ...tableAttributes });
    const thead = createElement('thead');
    const headerRow = createElement('tr');
    
    headers.forEach(header => {
        const th = createElement('th', {}, header);
        headerRow.appendChild(th);
    });
    thead.appendChild(headerRow);
    table.appendChild(thead);
    
    const tbody = createElement('tbody');
    rows.forEach(row => {
        const tr = typeof row === 'function' ? row() : createTableRow(row);
        tbody.appendChild(tr);
    });
    table.appendChild(tbody);
    
    return table;
}

// Función helper para crear opciones de select de forma segura
function createSelectOptions(options, defaultValue = '') {
    return options.map(opt => {
        if (typeof opt === 'string') {
            return `<option value="${sanitizeHtml(opt)}">${sanitizeHtml(opt)}</option>`;
        }
        const value = sanitizeHtml(opt.value || '');
        const text = sanitizeHtml(opt.text || opt.label || '');
        const selected = opt.selected ? ' selected' : '';
        return `<option value="${value}"${selected}>${text}</option>`;
    }).join('');
}

// Función helper para renderizar tabla de forma segura desde datos
function renderSafeTable(tbody, data, columns, emptyMessage = 'No hay datos') {
    if (!tbody) return;
    
    // Limpiar contenido existente
    tbody.innerHTML = '';
    
    if (!data || data.length === 0) {
        const tr = createElement('tr');
        const td = createElement('td', { 
            colspan: columns.length.toString(), 
            class: 'text-center' 
        }, emptyMessage);
        tr.appendChild(td);
        tbody.appendChild(tr);
        return;
    }
    
    data.forEach(item => {
        const tr = createElement('tr');
        if (item.onclick) {
            tr.setAttribute('onclick', item.onclick);
        }
        
        columns.forEach(col => {
            const td = createElement('td');
            let content = '';
            
            if (col.render) {
                // Función de renderizado personalizada
                content = col.render(item);
            } else if (col.field) {
                // Campo directo
                const value = col.field.split('.').reduce((obj, key) => obj?.[key], item);
                content = value !== null && value !== undefined ? String(value) : '-';
            } else if (col.html) {
                // HTML personalizado (se sanitiza)
                content = sanitizeHtml(col.html(item));
            }
            
            if (col.className) {
                td.className = col.className;
            }
            
            if (col.sanitize !== false) {
                td.textContent = content;
            } else {
                // Solo para casos donde realmente necesitamos HTML (como badges)
                td.innerHTML = sanitizeHtml(content);
            }
            
            tr.appendChild(td);
        });
        
        tbody.appendChild(tr);
    });
}

// Función helper para crear badges de forma segura
function createBadge(text, type = 'info') {
    const badge = createElement('span', { class: `badge badge-${type}` }, text);
    return badge.outerHTML;
}

// Función mejorada para validar formularios con validadores específicos
function validateFormAdvanced(formId, customValidators = {}) {
    const form = document.getElementById(formId);
    if (!form) return false;
    
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        const value = field.value.trim();
        const fieldName = field.name || field.id;
        
        // Validar campo requerido
        if (!value) {
            field.classList.add('error');
            isValid = false;
            return;
        }
        
        // Validar según el tipo de campo
        if (field.type === 'email' && !validateEmail(value)) {
            field.classList.add('error');
            showAlert(`El correo electrónico en ${fieldName} no es válido`, 'error');
            isValid = false;
            return;
        }
        
        // Validar cédula si el campo tiene el atributo data-validate="cedula"
        if (field.dataset.validate === 'cedula' && !validateCedula(value)) {
            field.classList.add('error');
            showAlert(`La cédula en ${fieldName} no es válida`, 'error');
            isValid = false;
            return;
        }
        
        // Validar número si el campo tiene el atributo data-validate="number"
        if (field.dataset.validate === 'number') {
            const num = parseFloat(value);
            if (isNaN(num)) {
                field.classList.add('error');
                showAlert(`El valor en ${fieldName} debe ser un número válido`, 'error');
                isValid = false;
                return;
            }
            // Validar rango si está especificado
            if (field.dataset.min && num < parseFloat(field.dataset.min)) {
                field.classList.add('error');
                showAlert(`El valor en ${fieldName} debe ser mayor o igual a ${field.dataset.min}`, 'error');
                isValid = false;
                return;
            }
            if (field.dataset.max && num > parseFloat(field.dataset.max)) {
                field.classList.add('error');
                showAlert(`El valor en ${fieldName} debe ser menor o igual a ${field.dataset.max}`, 'error');
                isValid = false;
                return;
            }
        }
        
        // Validadores personalizados
        if (customValidators[fieldName]) {
            const customResult = customValidators[fieldName](value, field);
            if (!customResult.valid) {
                field.classList.add('error');
                showAlert(customResult.message || `El campo ${fieldName} no es válido`, 'error');
                isValid = false;
                return;
            }
        }
        
        field.classList.remove('error');
    });
    
    return isValid;
}

// Exportar funciones globales
window.showLoading = showLoading;
window.hideLoading = hideLoading;
window.validateForm = validateForm;
window.validateFormAdvanced = validateFormAdvanced;
window.resetForm = resetForm;
window.confirmAction = confirmAction;
window.copyToClipboard = copyToClipboard;
window.downloadFile = downloadFile;
window.formatNumber = formatNumber;
window.validateEmail = validateEmail;
window.validateCedula = validateCedula;
window.debounce = debounce;
window.sanitizeHtml = sanitizeHtml;
window.setTextContent = setTextContent;
window.setSafeHtml = setSafeHtml;
window.createElement = createElement;
window.createTableRow = createTableRow;
window.buildSafeTable = buildSafeTable;
window.createSelectOptions = createSelectOptions;
window.renderSafeTable = renderSafeTable;
window.createBadge = createBadge;


