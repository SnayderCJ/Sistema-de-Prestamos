/**
 * Módulo de Autenticación
 * Maneja login, registro y recuperación de contraseña
 */

class AuthModule {
    constructor() {
        this.init();
    }

    init() {
        this.loginStatusEl = document.getElementById('loginStatus');
        this.loginErrorEl = document.getElementById('loginError');
        this.loginSubmitBtn = document.getElementById('loginSubmit');

        // Inicializar formulario de login si existe
        const loginForm = document.getElementById('loginForm');
        if (loginForm) {
            loginForm.addEventListener('submit', (e) => this.handleLogin(e));
        }

        // Inicializar formulario de registro si existe
        const registerForm = document.getElementById('registerForm');
        if (registerForm) {
            registerForm.addEventListener('submit', (e) => this.handleRegister(e));
        }

        // Validación en tiempo real
        this.setupRealTimeValidation();
    }

    /**
     * Maneja el submit del formulario de login
     */
    async handleLogin(event) {
        event.preventDefault();
        const form = event.target;
        this.clearLoginMessages();
        
        if (!validateFormAdvanced('loginForm')) {
            this.setLoginError('Verifica los campos marcados en rojo.');
            return;
        }

        const formData = new FormData(form);
        const email = formData.get('email');
        const password = formData.get('password');
        const remember = formData.get('remember') === 'on';

        try {
            this.setLoginStatus('Validando credenciales...', 'info');
            this.toggleLoginButton(true, 'Validando...');
            UI.showLoading('Iniciando sesión...');
            const result = await auth.login(email, password);
            
            if (result && result.token) {
                // Si marcó "recordar", guardar en sessionStorage también
                if (remember) {
                    sessionStorage.setItem('rememberSession', 'true');
                    localStorage.setItem('imax:lastEmail', email);
                } else {
                    localStorage.removeItem('imax:lastEmail');
                }
                
                this.setLoginStatus('¡Bienvenido! Puedes navegar al módulo que necesites.', 'success');
                this.setLoginError(null);
                UI.showAlert('¡Bienvenido!', 'success');
                
                // Mantener al usuario en la vista actual para permitir navegación manual
                UI.hideLoading();
            }
        } catch (error) {
            UI.hideLoading();
            this.toggleLoginButton(false);
            this.setLoginStatus(null);
            this.setLoginError(error.message || 'Error al iniciar sesión');
            UI.showAlert(error.message || 'Error al iniciar sesión', 'error');
            this.showFieldError('email', error.message);
            return;
        }

        this.toggleLoginButton(false);
    }

    setLoginStatus(message, variant = 'info') {
        if (!this.loginStatusEl) return;
        if (!message) {
            this.loginStatusEl.classList.remove('is-visible');
            this.loginStatusEl.removeAttribute('data-variant');
            this.loginStatusEl.textContent = '';
            return;
        }

        this.loginStatusEl.textContent = message;
        this.loginStatusEl.setAttribute('data-variant', variant);
        this.loginStatusEl.classList.add('is-visible');
    }

    setLoginError(message) {
        if (!this.loginErrorEl) return;
        if (!message) {
            this.loginErrorEl.classList.remove('is-visible');
            this.loginErrorEl.setAttribute('hidden', 'hidden');
            this.loginErrorEl.textContent = '';
            return;
        }

        this.loginErrorEl.textContent = message;
        this.loginErrorEl.classList.add('is-visible');
        this.loginErrorEl.removeAttribute('hidden');
    }

    clearLoginMessages() {
        this.setLoginStatus(null);
        this.setLoginError(null);
    }

    toggleLoginButton(isLoading, label) {
        if (!this.loginSubmitBtn) return;
        const labelEl = this.loginSubmitBtn.querySelector('.btn-label');
        this.loginSubmitBtn.disabled = !!isLoading;
        this.loginSubmitBtn.classList.toggle('is-loading', !!isLoading);

        if (labelEl) {
            labelEl.textContent = isLoading ? (label || 'Procesando...') : 'Iniciar sesión';
        }
    }

    /**
     * Maneja el submit del formulario de registro
     */
    async handleRegister(event) {
        event.preventDefault();
        const form = event.target;
        
        // Validación personalizada para registro
        const customValidators = {
            'cedula': (value) => {
                if (!validateCedula(value)) {
                    return {
                        valid: false,
                        message: 'La cédula no es válida'
                    };
                }
                return { valid: true };
            },
            'passwordConfirm': (value) => {
                const password = form.querySelector('#password').value;
                if (value !== password) {
                    return {
                        valid: false,
                        message: 'Las contraseñas no coinciden'
                    };
                }
                return { valid: true };
            }
        };

        if (!validateFormAdvanced('registerForm', customValidators)) {
            return;
        }

        const formData = new FormData(form);
        const data = {
            nombre: formData.get('nombre'),
            apellido: formData.get('apellido'),
            cedula: formData.get('cedula'),
            email: formData.get('email'),
            telefono: formData.get('telefono') || null,
            password: formData.get('password')
        };

        try {
            UI.showLoading('Creando cuenta...');
            
            // Llamar al API de registro
            const response = await api.post('/auth/register', data);
            
            if (response.success) {
                UI.showAlert('¡Cuenta creada exitosamente!', 'success');
                
                // Permitir que el usuario decida a dónde ir después de registrarse
                UI.hideLoading();
            }
        } catch (error) {
            UI.hideLoading();
            const errorMessage = error.message || 'Error al crear la cuenta';
            UI.showAlert(errorMessage, 'error');
            
            // Mostrar errores específicos por campo si vienen del servidor
            if (error.data && error.data.errors) {
                Object.keys(error.data.errors).forEach(field => {
                    this.showFieldError(field, error.data.errors[field]);
                });
            }
        }
    }

    /**
     * Configura validación en tiempo real
     */
    setupRealTimeValidation() {
        // Validación de email
        const emailInputs = document.querySelectorAll('input[type="email"]');
        emailInputs.forEach(input => {
            input.addEventListener('blur', () => {
                if (input.value && !validateEmail(input.value)) {
                    this.showFieldError(input.name, 'El correo electrónico no es válido');
                } else {
                    this.clearFieldError(input.name);
                }
            });
        });

        // Validación de cédula
        const cedulaInputs = document.querySelectorAll('input[data-validate="cedula"]');
        cedulaInputs.forEach(input => {
            input.addEventListener('blur', () => {
                if (input.value && !validateCedula(input.value)) {
                    this.showFieldError(input.name, 'La cédula no es válida');
                } else {
                    this.clearFieldError(input.name);
                }
            });
        });

        // Validación de confirmación de contraseña
        const passwordConfirm = document.getElementById('passwordConfirm');
        const password = document.getElementById('password');
        if (passwordConfirm && password) {
            passwordConfirm.addEventListener('blur', () => {
                if (passwordConfirm.value && passwordConfirm.value !== password.value) {
                    this.showFieldError('passwordConfirm', 'Las contraseñas no coinciden');
                } else {
                    this.clearFieldError('passwordConfirm');
                }
            });
        }
    }

    /**
     * Muestra un error en un campo específico
     */
    showFieldError(fieldName, message) {
        const field = document.querySelector(`[name="${fieldName}"]`);
        const errorEl = document.getElementById(`${fieldName}-error`);
        
        if (field) {
            field.classList.add('error');
            field.setAttribute('aria-invalid', 'true');
        }
        
        if (errorEl) {
            errorEl.textContent = message;
            errorEl.style.display = 'block';
        }
    }

    /**
     * Limpia el error de un campo
     */
    clearFieldError(fieldName) {
        const field = document.querySelector(`[name="${fieldName}"]`);
        const errorEl = document.getElementById(`${fieldName}-error`);
        
        if (field) {
            field.classList.remove('error');
            field.removeAttribute('aria-invalid');
        }
        
        if (errorEl) {
            errorEl.textContent = '';
            errorEl.style.display = 'none';
        }
    }
}

// Inicializar cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        new AuthModule();
    });
} else {
    new AuthModule();
}

