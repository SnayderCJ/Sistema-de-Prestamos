/**
 * register.js
 * Maneja la lógica específica de la UI de registro:
 * - Barra de progreso
 * - Medidor de fuerza de contraseña
 * - Autocompletado demo
 */

document.addEventListener('DOMContentLoaded', () => {
    initProgressBar();
    initPasswordStrength();
    initDemoAutofill();
});

/**
 * 1. Lógica de la Barra de Progreso
 * Escucha cambios en los campos requeridos y actualiza la barra superior.
 */
function initProgressBar() {
    const form = document.getElementById('registerForm');
    if (!form) return;

    const progressBar = document.getElementById('registerProgressBar');
    const progressLabel = document.getElementById('registerProgressLabel');
    // Seleccionamos solo inputs requeridos para el cálculo
    const requiredInputs = form.querySelectorAll('input[required]');
    
    function updateProgress() {
        let completed = 0;
        requiredInputs.forEach(input => {
            // Checkbox: checked, Texto: value.length > 0
            if ((input.type === 'checkbox' && input.checked) || 
                (input.type !== 'checkbox' && input.value.trim() !== '')) {
                completed++;
            }
        });

        const percentage = Math.round((completed / requiredInputs.length) * 100);
        
        if (progressBar) progressBar.style.width = `${percentage}%`;
        if (progressLabel) progressLabel.textContent = `${percentage}%`;
    }

    // Escuchar eventos
    requiredInputs.forEach(input => {
        input.addEventListener('input', updateProgress);
        input.addEventListener('change', updateProgress);
    });
}

/**
 * 2. Medidor de Fuerza de Contraseña
 * Analiza la contraseña y actualiza la barra de colores.
 */
function initPasswordStrength() {
    const passwordInput = document.getElementById('password');
    const strengthBar = document.getElementById('passwordStrengthBar');
    const strengthLabel = document.getElementById('password-strength-label');

    if (!passwordInput || !strengthBar) return;

    passwordInput.addEventListener('input', (e) => {
        const val = e.target.value;
        let strength = 0;
        let label = 'Débil';
        let color = '#ef4444'; // Rojo (danger)
        let width = '0%';

        if (val.length > 0) {
            // Criterios simples para demo
            if (val.length >= 8) strength += 1;
            if (/[A-Z]/.test(val)) strength += 1;
            if (/[0-9]/.test(val)) strength += 1;
            if (/[^A-Za-z0-9]/.test(val)) strength += 1;

            switch (strength) {
                case 0:
                case 1:
                    width = '25%';
                    color = '#ef4444'; // Rojo
                    label = 'Débil';
                    break;
                case 2:
                    width = '50%';
                    color = '#eab308'; // Amarillo
                    label = 'Regular';
                    break;
                case 3:
                    width = '75%';
                    color = '#3b82f6'; // Azul
                    label = 'Buena';
                    break;
                case 4:
                    width = '100%';
                    color = '#22c55e'; // Verde
                    label = 'Excelente';
                    break;
            }
        }

        strengthBar.style.width = width;
        strengthBar.style.background = color;
        if (strengthLabel) strengthLabel.textContent = `Fortaleza: ${label}`;
    });
}

/**
 * 3. Botón de Autocompletar Demo
 * Útil para desarrollo o demos rápidas.
 */
function initDemoAutofill() {
    const btn = document.querySelector('[data-fill-register]');
    if (!btn) return;

    btn.addEventListener('click', () => {
        const setters = {
            'nombre': 'Juan',
            'apellido': 'Pérez',
            'cedula': '001-1234567-8', // Asegúrate que pase tu validación de utils
            'email': `demo${Math.floor(Math.random() * 1000)}@empresa.com`,
            'telefono': '809-555-0000',
            'cargo': 'Gerente de Ventas',
            'password': 'Password123!',
            'passwordConfirm': 'Password123!'
        };

        Object.keys(setters).forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.value = setters[id];
                // Disparar evento para que la validación y progreso se actualicen
                el.dispatchEvent(new Event('input', { bubbles: true }));
                el.dispatchEvent(new Event('blur', { bubbles: true })); // Para quitar errores visuales
            }
        });

        // Marcar checkbox de términos
        const terms = document.getElementById('terms');
        if (terms) {
            terms.checked = true;
            terms.dispatchEvent(new Event('change', { bubbles: true }));
        }
    });
}