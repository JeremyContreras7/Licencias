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