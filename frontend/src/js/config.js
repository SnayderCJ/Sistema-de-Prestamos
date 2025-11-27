/**
 * Configuración de la Aplicación
 * 
 * Este archivo contiene la configuración centralizada de la aplicación.
 * Para cambiar la URL del API en producción, edita este archivo o
 * establece la variable de entorno correspondiente.
 */

window.APP_CONFIG = {
    // URL base del API
    // En desarrollo: http://localhost/api
    // En producción: https://tu-dominio.com/api
    API_BASE_URL: 'http://localhost:8080',
    
    // Configuración de timeout para peticiones (en milisegundos)
    REQUEST_TIMEOUT: 30000,
    
    // Configuración de notificaciones
    NOTIFICATION_DURATION: 5000,
    
    // Configuración de paginación
    DEFAULT_PAGE_SIZE: 20,
    
    // Configuración de formato
    DEFAULT_CURRENCY: 'DOP',
    DEFAULT_LOCALE: 'es-DO',
    
    // Configuración de seguridad
    TOKEN_STORAGE_KEY: 'authToken',
    USER_STORAGE_KEY: 'user'
};

// Permitir sobrescribir desde localStorage (útil para desarrollo)
if (localStorage.getItem('apiBaseUrl')) {
    window.APP_CONFIG.API_BASE_URL = localStorage.getItem('apiBaseUrl');
}

