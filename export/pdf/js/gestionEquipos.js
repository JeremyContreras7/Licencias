// Modal de Confirmación para Eliminar
        const deleteModal = document.getElementById('deleteModal');
        const cancelDelete = document.getElementById('cancelDelete');
        const confirmDelete = document.getElementById('confirmDelete');
        const equipoNombre = document.getElementById('equipoNombre');
        const equipoDetalles = document.getElementById('equipoDetalles');

        let currentDeleteUrl = '';

        // Abrir modal cuando se hace clic en eliminar
        document.querySelectorAll('.btn-delete').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                const id = this.getAttribute('data-id');
                const nombre = this.getAttribute('data-nombre');
                const serial = this.getAttribute('data-serial');
                const modelo = this.getAttribute('data-modelo');
                
                // Actualizar información en el modal
                equipoNombre.textContent = nombre;
                equipoDetalles.innerHTML = `
                    Serial: <strong>${serial}</strong><br>
                    Modelo: ${modelo || '—'}
                `;
                
                // Configurar URL de eliminación
                currentDeleteUrl = `gestionEquipos.php?eliminar=${id}`;
                
                // Mostrar modal
                deleteModal.style.display = 'block';
                document.body.style.overflow = 'hidden';
            });
        });

        // Cerrar modal al cancelar
        cancelDelete.addEventListener('click', function() {
            deleteModal.style.display = 'none';
            document.body.style.overflow = 'auto';
        });

        // Confirmar eliminación
        confirmDelete.addEventListener('click', function(e) {
            e.preventDefault();
            window.location.href = currentDeleteUrl;
        });

        // Cerrar modal al hacer clic fuera
        deleteModal.addEventListener('click', function(e) {
            if (e.target === deleteModal) {
                deleteModal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });

        // Cerrar modal con tecla Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && deleteModal.style.display === 'block') {
                deleteModal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });

        // Efectos de interacción mejorados
        document.addEventListener('DOMContentLoaded', function() {
            // Agregar efecto de carga a los botones de envío
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function() {
                    const submitBtn = this.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.classList.add('btn-loading');
                        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
                    }
                });
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

            // Efectos hover para las filas de la tabla
            const tableRows = document.querySelectorAll('tbody tr');
            tableRows.forEach(row => {
                row.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px)';
                    this.style.boxShadow = '0 4px 12px rgba(0, 0, 0, 0.1)';
                });
                
                row.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = 'none';
                });
            });
        });