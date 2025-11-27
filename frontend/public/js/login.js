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

        this.init();
    }

    init() {
        this.renderGreeting();
        this.bindPasswordToggle();
        this.bindDemoFill();
        this.restoreRememberedEmail();
        this.observeMetrics();
        this.persistEmailOnChange();
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

