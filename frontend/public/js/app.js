// ============================================
// ImaxPrestamos - Aplicación Principal
// Sistema de Gestión de Préstamos
// ============================================

// Cargar configuración desde archivo o usar valores por defecto
let API_BASE_URL = (window.APP_CONFIG && window.APP_CONFIG.API_BASE_URL) || 'http://localhost/api';

let authToken = localStorage.getItem('authToken');

// ============================================
// API Client
// ============================================

class ApiClient {
    constructor(baseUrl) {
        this.baseUrl = baseUrl;
    }

    async request(endpoint, options = {}) {
        const url = `${this.baseUrl}${endpoint}`;
        const config = {
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            },
            ...options
        };

        if (authToken) {
            config.headers['Authorization'] = `Bearer ${authToken}`;
        }

        try {
            const response = await fetch(url, config);
            
            // Manejar errores de autenticación
            if (response.status === 401 || response.status === 403) {
                authToken = null;
                localStorage.removeItem('authToken');
                localStorage.removeItem('user');
                throw new Error('No autorizado');
            }

            // Verificar si la respuesta tiene contenido antes de parsear JSON
            const contentType = response.headers.get('content-type');
            const hasContent = response.status !== 204 && contentType && contentType.includes('application/json');
            
            let data = null;
            if (hasContent) {
                const text = await response.text();
                if (text && text.trim()) {
                    try {
                        data = JSON.parse(text);
                    } catch (parseError) {
                        // Si no es JSON válido, podría ser un archivo o respuesta vacía
                        console.warn('Respuesta no es JSON válido:', text);
                        return { success: true, message: 'Operación exitosa' };
                    }
                }
            }

            if (!response.ok) {
                // Manejo uniforme de errores HTTP
                let errorMessage = `Error ${response.status}: ${response.statusText}`;
                
                if (data) {
                    errorMessage = data.error || data.message || data.errors?.join(', ') || errorMessage;
                }
                
                // Mensajes específicos por código de estado
                const statusMessages = {
                    400: 'Solicitud incorrecta. Verifique los datos enviados.',
                    401: 'Sesión expirada. Será redirigido al login.',
                    403: 'No tiene permisos para realizar esta acción.',
                    404: 'Recurso no encontrado.',
                    422: 'Datos de validación incorrectos.',
                    500: 'Error interno del servidor. Por favor, intente más tarde.',
                    503: 'Servicio no disponible. Por favor, intente más tarde.'
                };
                
                if (statusMessages[response.status]) {
                    errorMessage = statusMessages[response.status];
                }
                
                const error = new Error(errorMessage);
                error.status = response.status;
                error.data = data;
                throw error;
            }

            // Para respuestas 204 (No Content) o respuestas vacías exitosas
            if (response.status === 204 || !data) {
                return { success: true, message: 'Operación exitosa' };
            }

            return data;
        } catch (error) {
            // Si es un error de red, no redirigir
            if (error.name === 'TypeError' && error.message.includes('fetch')) {
                console.error('Error de red:', error);
                throw new Error('Error de conexión. Verifique su conexión a internet.');
            }
            console.error('API Error:', error);
            throw error;
        }
    }

    get(endpoint, params = {}) {
        const queryString = new URLSearchParams(params).toString();
        const url = queryString ? `${endpoint}?${queryString}` : endpoint;
        return this.request(url, { method: 'GET' });
    }

    post(endpoint, data) {
        return this.request(endpoint, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    }

    put(endpoint, data) {
        return this.request(endpoint, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    }

    delete(endpoint) {
        return this.request(endpoint, { method: 'DELETE' });
    }

    async downloadFile(endpoint, params = {}, filename = 'download') {
        const queryString = new URLSearchParams(params).toString();
        const url = queryString ? `${endpoint}?${queryString}` : endpoint;
        const fullUrl = `${this.baseUrl}${url}`;
        
        const config = {
            headers: {}
        };

        if (authToken) {
            config.headers['Authorization'] = `Bearer ${authToken}`;
        }

        try {
            const response = await fetch(fullUrl, config);
            
            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.error || 'Error al descargar el archivo');
            }

            const blob = await response.blob();
            const urlBlob = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = urlBlob;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(urlBlob);
            
            return { success: true };
        } catch (error) {
            console.error('Download Error:', error);
            throw error;
        }
    }
}

const api = new ApiClient(API_BASE_URL);

// ============================================
// Auth Service
// ============================================

class AuthService {
    async login(email, password) {
        const response = await api.post('/auth/login', { email, password });
        if (response.success && response.data.token) {
            authToken = response.data.token;
            localStorage.setItem('authToken', authToken);
            localStorage.setItem('user', JSON.stringify(response.data.user));
            return response.data;
        }
        throw new Error('Error en el login');
    }

    async logout() {
        try {
            // Notificar al servidor que el usuario está cerrando sesión
            await api.put('/auth/logout', {});
        } catch (error) {
            // Continuar con el logout incluso si falla la notificación al servidor
            console.error('Error al notificar logout al servidor:', error);
        } finally {
            authToken = null;
            localStorage.removeItem('authToken');
            localStorage.removeItem('user');
        }
    }

    isAuthenticated() {
        return !!authToken;
    }

    getCurrentUser() {
        const user = localStorage.getItem('user');
        return user ? JSON.parse(user) : null;
    }
}

const auth = new AuthService();

// ============================================
// Prestamos Service
// ============================================

class PrestamosService {
    async getAll(page = 1, filters = {}) {
        return api.get('/prestamos', { page, ...filters });
    }

    async getById(id) {
        return api.get(`/prestamos/${id}`);
    }

    async create(data) {
        return api.post('/prestamos', data);
    }

    async update(id, data) {
        return api.put(`/prestamos/${id}`, data);
    }

    async delete(id) {
        return api.delete(`/prestamos/${id}`);
    }
}

const prestamosService = new PrestamosService();

// ============================================
// Clientes Service
// ============================================

class ClientesService {
    async getAll(filters = {}) {
        return api.get('/clientes', filters);
    }

    async getById(id) {
        return api.get(`/clientes/${id}`);
    }

    async create(data) {
        return api.post('/clientes', data);
    }

    async update(id, data) {
        return api.put(`/clientes/${id}`, data);
    }

    async delete(id) {
        return api.delete(`/clientes/${id}`);
    }
}

const clientesService = new ClientesService();

// ============================================
// UI Helpers
// ============================================

class UI {
    static showLoading(message = 'Cargando...') {
        // Remover overlay existente si hay uno
        const existing = document.querySelector('.loading-overlay');
        if (existing) existing.remove();
        
        const overlay = document.createElement('div');
        overlay.className = 'loading-overlay';
        overlay.innerHTML = `
            <div class="loading-spinner"></div>
            ${message ? `<p class="loading-message">${this.escapeHtml(message)}</p>` : ''}
        `;
        document.body.appendChild(overlay);
    }
    
    static escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Función para sanitizar y mostrar datos de forma segura
    static sanitize(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Función para establecer contenido de forma segura
    static setSafeText(element, text) {
        if (!element) return;
        element.textContent = text || '';
    }
    
    // Función para establecer HTML de forma segura (solo cuando sea necesario)
    static setSafeHtml(element, html) {
        if (!element) return;
        element.innerHTML = this.sanitize(html);
    }

    static hideLoading() {
        const overlay = document.querySelector('.loading-overlay');
        if (overlay) overlay.remove();
    }

    static showAlert(message, type = 'info') {
        const alert = document.createElement('div');
        alert.className = `alert alert-${type}`;
        alert.innerHTML = `
            <span>${message}</span>
            <button class="alert-close">&times;</button>
        `;
        document.body.insertBefore(alert, document.body.firstChild);
        
        alert.querySelector('.alert-close').addEventListener('click', () => {
            alert.remove();
        });

        setTimeout(() => alert.remove(), 5000);
    }

    static formatCurrency(amount) {
        return new Intl.NumberFormat('es-DO', {
            style: 'currency',
            currency: 'DOP'
        }).format(amount);
    }

    static formatDate(date) {
        return new Date(date).toLocaleDateString('es-DO');
    }
}

// ============================================
// Inicialización
// ============================================

document.addEventListener('DOMContentLoaded', () => {
    const currentPage = window.location.pathname.split('/').pop() || 'index.html';
    const isAuthPage = currentPage === 'login.html' || currentPage === 'register.html';

    if (auth.isAuthenticated()) {
        if (currentPage === 'index.html') {
            loadDashboard();
        }
    } else if (!isAuthPage) {
        console.warn('Vista sin autenticación: acceso permitido sin redirección.');
    }
});

// ============================================
// Funciones Globales de Compatibilidad
// ============================================

function getToken() {
    return localStorage.getItem('authToken');
}

function formatCurrency(amount) {
    if (!amount) return 'RD$ 0.00';
    return new Intl.NumberFormat('es-DO', {
        style: 'currency',
        currency: 'DOP'
    }).format(amount);
}

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('es-DO', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    });
}

function showAlert(message, type = 'info') {
    // Mapear tipos a clases CSS
    const typeMap = {
        'success': 'success',
        'error': 'danger',
        'warning': 'warning',
        'info': 'info',
        'danger': 'danger'
    };
    
    const alertClass = typeMap[type] || 'info';
    
    // Crear elemento de alerta
    const alert = document.createElement('div');
    alert.className = `alert alert-${alertClass}`;
    alert.style.position = 'fixed';
    alert.style.top = '20px';
    alert.style.right = '20px';
    alert.style.zIndex = '10000';
    alert.style.minWidth = '300px';
    alert.style.maxWidth = '500px';
    alert.style.padding = '1rem 1.5rem';
    alert.style.borderRadius = '8px';
    alert.style.boxShadow = '0 4px 6px rgba(0, 0, 0, 0.1)';
    alert.style.animation = 'slideIn 0.3s ease';
    alert.innerHTML = `
        <span>${message}</span>
        <button class="alert-close" onclick="this.parentElement.remove()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; margin-left: 1rem; opacity: 0.8;">&times;</button>
    `;
    
    document.body.appendChild(alert);
    
    // Auto-remover después de 5 segundos
    setTimeout(() => {
        if (alert.parentElement) {
            alert.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => alert.remove(), 300);
        }
    }, 5000);
}

// Agregar animaciones CSS si no existen
if (!document.getElementById('alert-styles')) {
    const style = document.createElement('style');
    style.id = 'alert-styles';
    style.textContent = `
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);
}

// Función global de logout unificada - usa el método del AuthService
async function logout() {
    await auth.logout();
}

function cerrarModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
    }
}

// Cerrar modal al hacer clic fuera
document.addEventListener('click', function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
});

// Cerrar modal con ESC
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        document.querySelectorAll('.modal').forEach(modal => {
            if (modal.style.display === 'block') {
                modal.style.display = 'none';
            }
        });
    }
});

async function loadDashboard() {
    try {
        UI.showLoading();
        const stats = await api.get('/dashboard/estadisticas');
        const vencidos = await api.get('/dashboard/prestamos-vencidos');
        
        // Actualizar UI con los datos
        console.log('Dashboard data:', stats, vencidos);
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al cargar el dashboard', 'danger');
    }
}

// Exportar para uso global
window.api = api;
window.auth = auth;
window.UI = UI;
window.prestamosService = prestamosService;
window.clientesService = clientesService;

