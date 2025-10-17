// Efectos de interacción mejorados
        document.addEventListener('DOMContentLoaded', function() {
            // Agregar efecto de carga al botón de guardar
            const form = document.querySelector('form');
            form.addEventListener('submit', function() {
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.classList.add('btn-loading');
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
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
                            alert.parentElement.remove();
                        }
                    }, 500);
                }, 5000);
            });

            // Validación en tiempo real del número serial
            const serialInput = document.getElementById('Numero_serial');
            if (serialInput) {
                serialInput.addEventListener('input', function() {
                    const value = this.value.trim();
                    if (value.length > 0) {
                        this.style.borderColor = '#28a745';
                    } else {
                        this.style.borderColor = '';
                    }
                });
            }
        });