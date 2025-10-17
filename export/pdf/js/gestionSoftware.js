// Mejoras de interacción
        document.addEventListener('DOMContentLoaded', function() {
            // Efecto de carga en el botón de enviar
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function() {
                    const submitBtn = this.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.classList.add('btn-loading');
                        submitBtn.innerHTML = '<i class="fas fa-spinner"></i> Registrando...';
                    }
                });
            });

            // Confirmación mejorada para eliminación
            const deleteLinks = document.querySelectorAll('.btn-delete');
            deleteLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    if (!confirm('⚠️ ¿Estás seguro de eliminar este software?\n\nEsta acción eliminará permanentemente el registro.')) {
                        e.preventDefault();
                    }
                });
            });

            // Efecto visual en el checkbox crítico
            const criticoCheckbox = document.getElementById('es_critico');
            const criticoLabel = criticoCheckbox.closest('.checkbox-group');
            
            criticoCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    criticoLabel.style.borderColor = 'var(--danger)';
                    criticoLabel.style.background = 'rgba(239, 68, 68, 0.05)';
                } else {
                    criticoLabel.style.borderColor = 'var(--gray-300)';
                    criticoLabel.style.background = 'var(--gray-100)';
                }
            });
        });