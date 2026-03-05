        // Animación de conteo para estadísticas
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.stat-number').forEach(stat => {
                const target = parseInt(stat.textContent) || 0;
                let current = 0;
                const increment = Math.max(1, target / 50);
                const interval = setInterval(() => {
                    current += increment;
                    if (current >= target) {
                        stat.textContent = target;
                        clearInterval(interval);
                    } else {
                        stat.textContent = Math.ceil(current);
                    }
                }, 30);
            });

            // Configuración del modal de eliminación
            const deleteModal = document.getElementById('deleteModal');
            if (deleteModal) {
                deleteModal.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget;
                    const id = button.getAttribute('data-id');
                    const name = button.getAttribute('data-name');
                    const type = button.getAttribute('data-type');
                    const email = button.getAttribute('data-email');
                    const phone = button.getAttribute('data-phone');

                    // Actualizar la información en el modal
                    document.getElementById('modal-establishment-name').textContent = name;
                    document.getElementById('modal-establishment-type').textContent = type;
                    document.getElementById('modal-establishment-email').textContent = email;
                    document.getElementById('modal-establishment-phone').textContent = phone;

                    // Actualizar el enlace de eliminación
                    const deleteLink = document.getElementById('confirm-delete-btn');
                    deleteLink.href = `gestionEstablecimientos.php?eliminar=${id}`;
                });
            }

            // Efectos adicionales para los botones de eliminar
            const deleteButtons = document.querySelectorAll('.btn-delete');
            deleteButtons.forEach(button => {
                button.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px) scale(1.05)';
                });
                
                button.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                });
            });
        });

         document.addEventListener('DOMContentLoaded', function() {
            // Configuración del modal de eliminación
            const deleteModal = document.getElementById('deleteModal');
            if (deleteModal) {
                deleteModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    
                    const id = button.getAttribute('data-id');
                    const name = button.getAttribute('data-name');
                    const type = button.getAttribute('data-type');
                    const email = button.getAttribute('data-email');
                    const phone = button.getAttribute('data-phone');
                    const director = button.getAttribute('data-director');
                    
                    document.getElementById('modal-establishment-name').textContent = name;
                    document.getElementById('modal-establishment-type').textContent = type;
                    document.getElementById('modal-establishment-director').textContent = director;
                    document.getElementById('modal-establishment-email').textContent = email;
                    document.getElementById('modal-establishment-phone').textContent = phone;
                    
                    const confirmBtn = document.getElementById('confirm-delete-btn');
                    confirmBtn.href = `gestionEstablecimientos.php?eliminar=${id}`;
                });
            }

            // Auto-cerrar alertas después de 5 segundos
            const alerts = document.querySelectorAll('.alert-custom');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.transition = 'all 0.5s ease';
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-100%)';
                    setTimeout(() => alert.remove(), 500);
                }, 5000);
            });

            // Tooltips de Bootstrap
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
            tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });

   