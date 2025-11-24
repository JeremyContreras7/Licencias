// Funcionalidades para el formulario de registro
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('registroForm');
    const passwordInput = document.getElementById('pass');
    const confirmPasswordInput = document.getElementById('confirm_pass');
    const strengthFill = document.getElementById('strengthFill');
    const strengthText = document.getElementById('strengthText');
    const passwordMatch = document.getElementById('passwordMatch');

    // Validar fortaleza de contraseña en tiempo real
    if (passwordInput) {
        passwordInput.addEventListener('input', checkPasswordStrength);
    }

    // Validar coincidencia de contraseñas en tiempo real
    if (confirmPasswordInput) {
        confirmPasswordInput.addEventListener('input', checkPasswordMatch);
    }

    // Validación antes del envío del formulario
    if (form) {
        form.addEventListener('submit', function(e) {
            if (!validateForm()) {
                e.preventDefault();
                showNotification('Por favor, corrige los errores en el formulario.', 'error');
            }
        });
    }

    // Efecto de focus en inputs
    const inputs = form.querySelectorAll('input, select');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });

        input.addEventListener('blur', function() {
            this.parentElement.classList.remove('focused');
        });
    });
});

// Verificar fortaleza de la contraseña
function checkPasswordStrength() {
    const password = this.value;
    let strength = 0;
    let feedback = '';

    // Longitud mínima
    if (password.length >= 8) strength += 1;
    
    // Contiene letras
    if (/[a-zA-Z]/.test(password)) strength += 1;
    
    // Contiene números
    if (/[0-9]/.test(password)) strength += 1;
    
    // Contiene caracteres especiales
    if (/[^a-zA-Z0-9]/.test(password)) strength += 1;
    
    // Longitud mayor a 12
    if (password.length >= 12) strength += 1;

    // Actualizar barra y texto
    updateStrengthIndicator(strength);
}

// Actualizar indicador visual de fortaleza
function updateStrengthIndicator(strength) {
    const strengthFill = document.getElementById('strengthFill');
    const strengthText = document.getElementById('strengthText');
    
    strengthFill.className = 'strength-fill';
    
    switch(strength) {
        case 0:
        case 1:
            strengthFill.classList.add('strength-weak');
            strengthText.textContent = 'Muy Débil';
            strengthText.style.color = '#dc3545';
            break;
        case 2:
            strengthFill.classList.add('strength-weak');
            strengthText.textContent = 'Débil';
            strengthText.style.color = '#dc3545';
            break;
        case 3:
            strengthFill.classList.add('strength-medium');
            strengthText.textContent = 'Moderada';
            strengthText.style.color = '#ffc107';
            break;
        case 4:
            strengthFill.classList.add('strength-strong');
            strengthText.textContent = 'Fuerte';
            strengthText.style.color = '#28a745';
            break;
        case 5:
            strengthFill.classList.add('strength-very-strong');
            strengthText.textContent = 'Muy Fuerte';
            strengthText.style.color = '#4361ee';
            break;
    }
}

// Verificar coincidencia de contraseñas
function checkPasswordMatch() {
    const password = document.getElementById('pass').value;
    const confirmPassword = this.value;
    const matchIndicator = document.getElementById('passwordMatch');

    if (confirmPassword === '') {
        matchIndicator.textContent = '';
        matchIndicator.className = 'match-indicator';
        return;
    }

    if (password === confirmPassword) {
        matchIndicator.textContent = '✓ Las contraseñas coinciden';
        matchIndicator.className = 'match-indicator match-valid';
    } else {
        matchIndicator.textContent = '✗ Las contraseñas no coinciden';
        matchIndicator.className = 'match-indicator match-invalid';
    }
}

// Alternar visibilidad de contraseña
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = field.nextElementSibling.querySelector('i');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Validación completa del formulario
function validateForm() {
    let isValid = true;
    const form = document.getElementById('registroForm');
    
    // Validar nombre
    const nombre = form.querySelector('#nombre');
    if (nombre.value.trim().length < 2) {
        markInvalid(nombre, 'El nombre debe tener al menos 2 caracteres');
        isValid = false;
    } else {
        markValid(nombre);
    }

    // Validar correo
    const correo = form.querySelector('#correo');
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(correo.value)) {
        markInvalid(correo, 'Ingrese un correo electrónico válido');
        isValid = false;
    } else {
        markValid(correo);
    }

    // Validar tipo de funcionario
    const tipoFuncionario = form.querySelector('#tipo_funcionario');
    if (!tipoFuncionario.value) {
        markInvalid(tipoFuncionario, 'Seleccione un tipo de funcionario');
        isValid = false;
    } else {
        markValid(tipoFuncionario);
    }

    // Validar contraseña
    const password = form.querySelector('#pass');
    const passwordRegex = /^(?=.*[A-Za-z])(?=.*\d).{8,}$/;
    if (!passwordRegex.test(password.value)) {
        markInvalid(password, 'La contraseña debe tener al menos 8 caracteres con letras y números');
        isValid = false;
    } else {
        markValid(password);
    }

    // Validar confirmación de contraseña
    const confirmPassword = form.querySelector('#confirm_pass');
    if (password.value !== confirmPassword.value) {
        markInvalid(confirmPassword, 'Las contraseñas no coinciden');
        isValid = false;
    } else {
        markValid(confirmPassword);
    }

    return isValid;
}

// Marcar campo como inválido
function markInvalid(field, message) {
    field.style.borderColor = '#dc3545';
    field.style.boxShadow = '0 0 0 3px rgba(220, 53, 69, 0.1)';
    
    // Remover tooltip anterior si existe
    const existingTooltip = field.parentElement.querySelector('.field-error');
    if (existingTooltip) {
        existingTooltip.remove();
    }
    
    // Agregar tooltip de error
    const errorTooltip = document.createElement('div');
    errorTooltip.className = 'field-error';
    errorTooltip.style.color = '#dc3545';
    errorTooltip.style.fontSize = '0.8rem';
    errorTooltip.style.marginTop = '0.25rem';
    errorTooltip.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
    field.parentElement.appendChild(errorTooltip);
}

// Marcar campo como válido
function markValid(field) {
    field.style.borderColor = '#28a745';
    field.style.boxShadow = '0 0 0 3px rgba(40, 167, 69, 0.1)';
    
    // Remover tooltip de error si existe
    const existingTooltip = field.parentElement.querySelector('.field-error');
    if (existingTooltip) {
        existingTooltip.remove();
    }
}

// Mostrar notificación
function showNotification(message, type = 'info') {
    // Crear elemento de notificación
    const notification = document.createElement('div');
    notification.className = `alert alert-${type}`;
    notification.style.position = 'fixed';
    notification.style.top = '20px';
    notification.style.right = '20px';
    notification.style.zIndex = '1000';
    notification.style.minWidth = '300px';
    notification.style.animation = 'slideIn 0.3s ease';
    
    notification.innerHTML = `
        <div class="alert-content">
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : 'info-circle'}"></i>
            <span>${message}</span>
        </div>
        <button class="alert-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto-remover después de 5 segundos
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
}

// Limpiar validaciones al resetear el formulario
document.getElementById('registroForm').addEventListener('reset', function() {
    const fields = this.querySelectorAll('input, select');
    fields.forEach(field => {
        field.style.borderColor = '';
        field.style.boxShadow = '';
        
        const errorTooltip = field.parentElement.querySelector('.field-error');
        if (errorTooltip) {
            errorTooltip.remove();
        }
    });
    
    // Resetear indicadores de contraseña
    if (strengthFill && strengthText) {
        strengthFill.className = 'strength-fill';
        strengthText.textContent = 'Débil';
        strengthText.style.color = '';
    }
    
    if (passwordMatch) {
        passwordMatch.textContent = '';
        passwordMatch.className = 'match-indicator';
    }
});