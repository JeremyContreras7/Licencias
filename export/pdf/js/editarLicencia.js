        // Efectos de interacción mejorados
        document.addEventListener('DOMContentLoaded', function() {
            // Agregar efecto de carga al botón de guardar
            const form = document.querySelector('form');
            form.addEventListener('submit', function() {
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Guardando...';
                    submitBtn.disabled = true;
                }
            });

            // Validación en tiempo real de fechas
            const fechaInicio = document.getElementById('fecha_inicio');
            const fechaVencimiento = document.getElementById('fecha_vencimiento');
            
            function validarFechas() {
                if (fechaInicio.value && fechaVencimiento.value) {
                    if (fechaInicio.value > fechaVencimiento.value) {
                        fechaVencimiento.style.borderColor = 'var(--danger)';
                    } else {
                        fechaVencimiento.style.borderColor = '';
                    }
                }
            }
            
            fechaInicio.addEventListener('change', validarFechas);
            fechaVencimiento.addEventListener('change', validarFechas);
        });