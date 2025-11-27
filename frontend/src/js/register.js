class RegisterExperience {
    constructor() {
        this.form = document.getElementById('registerForm');
        if (!this.form) return;

        this.progressBar = document.getElementById('registerProgressBar');
        this.progressLabel = document.getElementById('registerProgressLabel');
        this.passwordInput = document.getElementById('password');
        this.passwordConfirmInput = document.getElementById('passwordConfirm');
        this.passwordStrengthBar = document.getElementById('passwordStrengthBar');
        this.passwordStrengthLabel = document.getElementById('password-strength-label');
        this.telefonoInput = document.getElementById('telefono');
        this.cedulaInput = document.getElementById('cedula');
        this.demoBtn = document.querySelector('[data-fill-register]');
        this.submitBtn = document.getElementById('registerSubmit');
        this.errorEl = document.getElementById('registerError');

        this.init();
    }

    init() {
        this.form.addEventListener('input', () => this.updateProgress());
        this.updateProgress();
        this.bindPasswordStrength();
        this.bindFormatters();
        this.bindDemoButton();
        this.observeStats();
        this.bindFormSubmit();
    }

    async handleRegister(e) {
        e.preventDefault();
        this.setLoading(true);
        this.showError('');

        // Validar contraseñas
        if (this.passwordInput.value !== this.passwordConfirmInput.value) {
            this.showError('Las contraseñas no coinciden.');
            this.setLoading(false);
            return;
        }

        const formData = new FormData(this.form);
        const userData = Object.fromEntries(formData.entries());

        try {
            // auth.register ahora maneja el token y la sesión
            await auth.register(userData);
            // Redirigir directamente al panel principal
            window.location.href = '/views/index.html';

        } catch (error) {
            console.error('Error de registro:', error);
            const detail = error.status ? ` (Código: ${error.status})` : '';
            this.showError(`${error.message}${detail}`);
        } finally {
            this.setLoading(false);
        }
    }

    bindFormSubmit() {
        if (!this.form) return;
        this.form.addEventListener('submit', this.handleRegister.bind(this));
    }

    setLoading(isLoading) {
        if (!this.submitBtn) return;
        const label = this.submitBtn.querySelector('.btn-label');
        const spinner = this.submitBtn.querySelector('.btn-spinner');

        if (isLoading) {
            this.submitBtn.disabled = true;
            if(label) label.style.display = 'none';
            if(spinner) spinner.style.display = 'inline-block';
        } else {
            this.submitBtn.disabled = false;
            if(label) label.style.display = 'inline-block';
            if(spinner) spinner.style.display = 'none';
        }
    }

    showError(message) {
        if (!this.errorEl) return;
        this.errorEl.textContent = message;
        this.errorEl.hidden = !message;
    }

    updateProgress() {
        const requiredFields = Array.from(this.form.querySelectorAll('[required]'));
        const filled = requiredFields.filter(field => field.type === 'checkbox' ? field.checked : field.value.trim() !== '').length;
        const progress = Math.round((filled / requiredFields.length) * 100);

        if (this.progressBar) this.progressBar.style.width = `${progress}%`;
        if (this.progressLabel) this.progressLabel.textContent = `${progress}%`;
    }

    bindPasswordStrength() {
        if (!this.passwordInput || !this.passwordStrengthBar) return;

        const calculateScore = (value) => {
            let score = 0;
            if (value.length >= 8) score += 30;
            if (/[A-Z]/.test(value)) score += 20;
            if (/[a-z]/.test(value)) score += 20;
            if (/[0-9]/.test(value)) score += 15;
            if (/[^A-Za-z0-9]/.test(value)) score += 15;
            return Math.min(score, 100);
        };

        this.passwordInput.addEventListener('input', () => {
            const value = this.passwordInput.value;
            const score = calculateScore(value);
            this.passwordStrengthBar.style.width = `${score}%`;

            if (this.passwordStrengthLabel) {
                let label = 'Débil';
                if (score >= 80) label = 'Muy fuerte';
                else if (score >= 60) label = 'Fuerte';
                else if (score >= 40) label = 'Aceptable';
                this.passwordStrengthLabel.textContent = `Fortaleza: ${label}`;
            }

            if (this.passwordConfirmInput && this.passwordConfirmInput.value) {
                this.passwordConfirmInput.dispatchEvent(new Event('input'));
            }
        });

        if (this.passwordConfirmInput) {
            this.passwordConfirmInput.addEventListener('input', () => {
                const errorEl = document.getElementById('passwordConfirm-error');
                if (this.passwordConfirmInput.value && this.passwordConfirmInput.value !== this.passwordInput.value) {
                    errorEl.textContent = 'Las contraseñas no coinciden';
                } else {
                    errorEl.textContent = '';
                }
            });
        }
    }

    bindFormatters() {
        if (this.telefonoInput) {
            this.telefonoInput.addEventListener('input', () => {
                const digits = this.telefonoInput.value.replace(/\D/g, '').slice(0, 10);
                const match = digits.match(/^(\d{0,3})(\d{0,3})(\d{0,4})$/);
                if (!match) return;
                this.telefonoInput.value = [match[1] && `(${match[1]}`, match[2] && `) ${match[2]}`, match[3] && `-${match[3]}`]
                    .filter(Boolean)
                    .join('');
            });
        }

        if (this.cedulaInput) {
            this.cedulaInput.addEventListener('input', () => {
                const digits = this.cedulaInput.value.replace(/\D/g, '').slice(0, 11);
                const match = digits.match(/^(\d{0,3})(\d{0,7})(\d{0,1})$/);
                if (!match) return;
                this.cedulaInput.value = [match[1], match[2] && match[2].padStart(7, match[2]), match[3]]
                    .filter(Boolean)
                    .map((segment, index) => index === 0 ? segment : segment.padStart(index === 1 ? 7 : 1, '0'))
                    .join('-')
                    .replace(/-+/g, '-')
                    .replace(/-$/, '');
            });
        }
    }

    bindDemoButton() {
        if (!this.demoBtn) return;
        this.demoBtn.addEventListener('click', () => {
            const demoData = {
                nombre: 'Camila',
                apellido: 'Morales',
                cedula: '402-1234567-1',
                email: 'camila.morales@demo.com',
                telefono: '(809) 555-0101',
                cargo: 'Gerente de cobranzas',
                password: 'DemoStrong!24',
                passwordConfirm: 'DemoStrong!24',
                terms: true
            };

            Object.entries(demoData).forEach(([key, value]) => {
                const field = this.form.elements[key];
                if (!field) return;
                if (field.type === 'checkbox') {
                    field.checked = value;
                } else {
                    field.value = value;
                    field.dispatchEvent(new Event('input'));
                }
            });

            this.updateProgress();
        });
    }

    observeStats() {
        const stats = document.querySelectorAll('.register-stats [data-counter]');
        if (!stats.length) return;

        const observer = new IntersectionObserver((entries, obs) => {
            entries.forEach(entry => {
                if (!entry.isIntersecting) return;
                const el = entry.target;
                const target = el.dataset.counter;
                el.textContent = `${target}${el.dataset.suffix || ''}`;
                obs.unobserve(el);
            });
        }, { threshold: 0.4 });

        stats.forEach(stat => observer.observe(stat));
    }
}

document.addEventListener('DOMContentLoaded', () => new RegisterExperience());

