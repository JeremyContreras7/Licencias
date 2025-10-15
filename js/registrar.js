// Validación de fortaleza de contraseña en tiempo real
        document.getElementById('pass').addEventListener('input', function(e) {
            const password = e.target.value;
            const strengthFill = document.getElementById('strengthFill');
            const strengthText = document.getElementById('strengthText');
            
            let strength = 0;
            
            // Validar longitud
            if (password.length >= 8) strength += 1;
            if (password.length >= 12) strength += 1;
            
            // Validar caracteres diversos
            if (/[A-Z]/.test(password)) strength += 1;
            if (/[0-9]/.test(password)) strength += 1;
            if (/[^A-Za-z0-9]/.test(password)) strength += 1;
            
            // Actualizar interfaz
            strengthFill.className = 'strength-fill';
            if (password.length === 0) {
                strengthFill.style.width = '0%';
                strengthText.textContent = 'Ingrese contraseña';
            } else if (strength <= 2) {
                strengthFill.classList.add('strength-weak');
                strengthText.textContent = 'Débil';
            } else if (strength <= 4) {
                strengthFill.classList.add('strength-medium');
                strengthText.textContent = 'Media';
            } else {
                strengthFill.classList.add('strength-strong');
                strengthText.textContent = 'Fuerte';
            }
        });

        // Validación de formulario mejorada
        document.getElementById('registroForm').addEventListener('submit', function(e) {
            const password = document.getElementById('pass').value;
            const tipoFuncionario = document.getElementById('tipo_encargado').value;
            
            if (password.length < 8) {
                e.preventDefault();
                alert('❌ La contraseña debe tener al menos 8 caracteres.');
                return;
            }
            
            if (!tipoFuncionario) {
                e.preventDefault();
                alert('❌ Por favor, seleccione el tipo de funcionario.');
                return;
            }
            
            // Mostrar loading en el botón
            const submitBtn = e.target.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Registrando...';
                submitBtn.disabled = true;
            }
        });

        // Auto-ocultar mensajes después de 5 segundos
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.opacity = '0';
                alert.style.transition = 'opacity 0.5s ease';
                setTimeout(() => {
                    if (alert.parentElement) {
                        alert.parentElement.removeChild(alert);
                    }
                }, 500);
            }, 5000);
        });

        // Efecto de foco en campos
        const inputs = document.querySelectorAll('input, select');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'translateY(-2px)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'translateY(0)';
            });
        });