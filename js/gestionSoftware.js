
        // Script para mejorar la interactividad
        document.addEventListener('DOMContentLoaded', function() {
            // Agregar animación a las cards de exportación
            const exportCards = document.querySelectorAll('.export-card');
            exportCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });

            // Mejorar la experiencia del formulario
            const form = document.querySelector('form');
            const inputs = form.querySelectorAll('input[type="text"]');
            
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.style.transform = 'scale(1.02)';
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.style.transform = 'scale(1)';
                });
            });

            // Confirmación mejorada para eliminación
            const deleteButtons = document.querySelectorAll('.btn-delete');
            deleteButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    if (!confirm('¿Estás seguro de eliminar este software?\n\nEsta acción no se puede deshacer.')) {
                        e.preventDefault();
                    }
                });
            });

            // Auto-ocultar notificaciones después de 5 segundos
            const notifications = document.querySelectorAll('.notification');
            notifications.forEach(notification => {
                setTimeout(() => {
                    notification.style.opacity = '0';
                    notification.style.transform = 'translateY(-10px)';
                    setTimeout(() => {
                        notification.remove();
                    }, 300);
                }, 5000);
            });
        });
  