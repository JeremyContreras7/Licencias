 // Validación del formulario
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const nombreInput = document.querySelector('input[name="nombre"]');
            const tipoSelect = document.querySelector('select[name="tipo_escuela"]');

            form.addEventListener('submit', function(e) {
                let isValid = true;
                
                // Validar nombre
                if (nombreInput.value.trim() === '') {
                    showError(nombreInput, 'El nombre del establecimiento es obligatorio');
                    isValid = false;
                } else {
                    clearError(nombreInput);
                }

                // Validar tipo
                if (tipoSelect.value === '') {
                    showError(tipoSelect, 'Debe seleccionar un tipo de escuela');
                    isValid = false;
                } else {
                    clearError(tipoSelect);
                }

                if (!isValid) {
                    e.preventDefault();
                    // Scroll al primer error
                    const firstError = document.querySelector('.is-invalid');
                    if (firstError) {
                        firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }
            });

            function showError(input, message) {
                input.classList.add('is-invalid');
                let errorDiv = input.parentNode.querySelector('.invalid-feedback');
                if (!errorDiv) {
                    errorDiv = document.createElement('div');
                    errorDiv.className = 'invalid-feedback';
                    input.parentNode.appendChild(errorDiv);
                }
                errorDiv.textContent = message;
            }

            function clearError(input) {
                input.classList.remove('is-invalid');
                const errorDiv = input.parentNode.querySelector('.invalid-feedback');
                if (errorDiv) {
                    errorDiv.remove();
                }
            }

            // Efectos visuales
            const inputs = document.querySelectorAll('.form-control, .form-select');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentNode.classList.add('focused');
                });
                
                input.addEventListener('blur', function() {
                    this.parentNode.classList.remove('focused');
                });
            });
        });