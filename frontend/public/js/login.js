class LoginExperience {
    constructor() {
        this.form = document.getElementById('loginForm');
        if (!this.form) return;

        this.passwordInput = document.getElementById('password');
        this.togglePasswordBtn = document.getElementById('togglePassword');
        this.demoBtn = document.querySelector('[data-fill-demo]');
        this.greetingEl = document.getElementById('loginGreeting');
        this.rememberCheckbox = document.getElementById('remember');
        this.emailInput = document.getElementById('email');
        this.submitBtn = document.getElementById('loginSubmit');
        this.errorEl = document.getElementById('loginError');


        this.init();
    }

    init() {
        this.renderGreeting();
        this.bindPasswordToggle();
        this.bindDemoFill();
        this.restoreRememberedEmail();
        this.observeMetrics();
        this.persistEmailOnChange();
        this.bindFormSubmit();
    }

    async handleLogin(email, password) {
        this.setLoading(true);
        this.showError(''); // Limpiar errores previos

        try {
            await auth.login(email, password);
            window.location.href = '/views/index.html';
        } catch (error) {
            console.error('Error de login:', error);
            const detail = error.status ? ` (Código: ${error.status})` : '';
            this.showError(`${error.message}${detail}`);
        } finally {
            this.setLoading(false);
        }
    }

    bindFormSubmit() {
        if (!this.form) return;
        this.form.addEventListener('submit', (e) => {
            e.preventDefault();
            const email = this.emailInput.value;
            const password = this.passwordInput.value;
            this.handleLogin(email, password);
        });
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

    renderGreeting() {
        if (!this.greetingEl) return;
        const hour = new Date().getHours();
        let message = 'Listo para continuar con tus operaciones.';

        if (hour < 12) message = 'Buenos días, revisemos los desembolsos de hoy.';
        else if (hour < 18) message = 'Buenas tardes, hay alertas en cola listas para ti.';
        else message = 'Buenas noches, puedes programar los recordatorios pendientes.';

        this.greetingEl.textContent = message;
    }

    bindPasswordToggle() {
        if (!this.togglePasswordBtn || !this.passwordInput) return;
        this.togglePasswordBtn.addEventListener('click', () => {
            const isPassword = this.passwordInput.type === 'password';
            this.passwordInput.type = isPassword ? 'text' : 'password';
            this.togglePasswordBtn.setAttribute('aria-pressed', String(isPassword));
            this.togglePasswordBtn.querySelector('.password-toggle__icon').textContent = isPassword ? '🙈' : '👁️';
        });
    }

    bindDemoFill() {
        if (!this.demoBtn || !this.emailInput || !this.passwordInput) return;
        this.demoBtn.addEventListener('click', () => {
            this.emailInput.value = 'demo@imaxprestamos.com';
            this.passwordInput.value = 'DemoPassword!123';
            this.emailInput.dispatchEvent(new Event('input'));
        });
    }

    restoreRememberedEmail() {
        if (!this.emailInput || !this.rememberCheckbox) return;
        const storedEmail = localStorage.getItem('imax:lastEmail');
        if (storedEmail) {
            this.emailInput.value = storedEmail;
            this.rememberCheckbox.checked = true;
        }
    }

    persistEmailOnChange() {
        if (!this.emailInput || !this.rememberCheckbox) return;
        const persist = () => {
            if (this.rememberCheckbox.checked && this.emailInput.value) {
                localStorage.setItem('imax:lastEmail', this.emailInput.value);
            } else {
                localStorage.removeItem('imax:lastEmail');
            }
        };

        this.emailInput.addEventListener('input', persist);
        this.rememberCheckbox.addEventListener('change', persist);
    }

    observeMetrics() {
        const metrics = document.querySelectorAll('[data-counter]');
        if (!metrics.length) return;

        const animateNumber = (el) => {
            const target = Number(el.dataset.counter) || 0;
            const suffix = el.dataset.suffix || '';
            const duration = 1200;
            let start = null;

            const step = (timestamp) => {
                if (!start) start = timestamp;
                const progress = Math.min((timestamp - start) / duration, 1);
                const value = Math.floor(progress * target);
                el.textContent = `${value}${suffix}`;
                if (progress < 1) requestAnimationFrame(step);
            };

            requestAnimationFrame(step);
        };

        const observer = new IntersectionObserver((entries, obs) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animateNumber(entry.target);
                    obs.unobserve(entry.target);
                }
            });
        }, { threshold: 0.4 });

        metrics.forEach(metric => observer.observe(metric));
    }
}

document.addEventListener('DOMContentLoaded', () => new LoginExperience());

