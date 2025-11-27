/**
 * Sistema de Componentes
 * Carga componentes HTML de forma dinámica para evitar código repetido
 */

class ComponentLoader {
    constructor() {
        this.components = new Map();
        this.loaded = false;
    }

    /**
     * Carga un componente desde un archivo HTML
     */
    async loadComponent(name) {
        if (this.components.has(name)) {
            return this.components.get(name);
        }

        try {
            const response = await fetch(`/views/layouts/components/${name}.html`);
            if (!response.ok) {
                throw new Error(`No se pudo cargar el componente: ${name}`);
            }
            const html = await response.text();
            this.components.set(name, html);
            return html;
        } catch (error) {
            console.error(`Error cargando componente ${name}:`, error);
            return '';
        }
    }

    /**
     * Inserta un componente en un elemento del DOM
     */
    async insertComponent(selector, componentName) {
        const element = document.querySelector(selector);
        if (!element) {
            console.warn(`No se encontró el selector: ${selector}`);
            return;
        }

        const html = await this.loadComponent(componentName);
        if (html) {
            element.innerHTML = html;
            // Marcar el componente como cargado
            element.setAttribute('data-component-loaded', 'true');
            
            // Disparar evento personalizado
            element.dispatchEvent(new CustomEvent('componentLoaded', {
                detail: { component: componentName }
            }));
        }
    }

    /**
     * Carga todos los componentes marcados con data-component
     */
    async loadAllComponents() {
        const componentElements = document.querySelectorAll('[data-component]');
        const promises = [];

        componentElements.forEach(element => {
            const componentName = element.getAttribute('data-component');
            if (componentName) {
                promises.push(this.insertComponent(`[data-component="${componentName}"]`, componentName));
            }
        });

        await Promise.all(promises);
        this.loaded = true;
    }

    /**
     * Actualiza el estado activo de los enlaces de navegación
     */
    updateActiveLinks() {
        const currentPage = window.location.pathname.split('/').pop() || 'index.html';
        const pageName = currentPage.replace('.html', '');

        // Actualizar header nav
        document.querySelectorAll('.header-nav a').forEach(link => {
            const linkHref = link.getAttribute('href') || '';
            const linkPage = linkHref.split('/').pop()?.replace('.html', '') || '';
            if (linkPage === pageName || (pageName === 'index' && linkPage === 'index')) {
                link.classList.add('active');
                link.setAttribute('aria-current', 'page');
            } else {
                link.classList.remove('active');
                link.removeAttribute('aria-current');
            }
        });

        // Actualizar sidebar nav
        document.querySelectorAll('.sidebar-menu a').forEach(link => {
            const linkPage = link.getAttribute('data-page');
            if (linkPage === pageName) {
                link.classList.add('active');
                link.setAttribute('aria-current', 'page');
            } else {
                link.classList.remove('active');
                link.removeAttribute('aria-current');
            }
        });
    }

    /**
     * Actualiza la información del usuario en el header
     */
    updateUserInfo() {
        try {
            const user = auth.getCurrentUser();
            if (user) {
                const userNameEl = document.getElementById('userName');
                const userAvatarEl = document.getElementById('userAvatar');
                
                if (userNameEl) {
                    const fullName = `${user.nombre || ''} ${user.apellido || ''}`.trim() || user.email || 'Usuario';
                    userNameEl.textContent = fullName;
                }
                
                if (userAvatarEl) {
                    const initials = this.getInitials(user.nombre, user.apellido, user.email);
                    userAvatarEl.textContent = initials;
                }
            }
        } catch (error) {
            console.error('Error actualizando información del usuario:', error);
        }
    }

    /**
     * Obtiene las iniciales del usuario
     */
    getInitials(nombre, apellido, email) {
        if (nombre && apellido) {
            return `${nombre.charAt(0)}${apellido.charAt(0)}`.toUpperCase();
        }
        if (nombre) {
            return nombre.substring(0, 2).toUpperCase();
        }
        if (email) {
            return email.substring(0, 2).toUpperCase();
        }
        return 'U';
    }

    /**
     * Inicializa el sistema de componentes
     */
    async init() {
        // Cargar componentes
        await this.loadAllComponents();
        
        // Actualizar navegación activa
        this.updateActiveLinks();
        
        // Actualizar información del usuario
        this.updateUserInfo();
        
        // Escuchar cambios de ruta
        window.addEventListener('popstate', () => {
            this.updateActiveLinks();
        });
    }
}

// Crear instancia global
const componentLoader = new ComponentLoader();

// Inicializar cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        componentLoader.init();
    });
} else {
    componentLoader.init();
}

// Exportar para uso global
window.componentLoader = componentLoader;

