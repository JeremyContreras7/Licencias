        // Modal de Confirmación para Eliminar
        const deleteModal = document.getElementById('deleteModal');
        const cancelDelete = document.getElementById('cancelDelete');
        const confirmDelete = document.getElementById('confirmDelete');
        const licenciaSoftware = document.getElementById('licenciaSoftware');
        const licenciaEquipo = document.getElementById('licenciaEquipo');
        const licenciaUsuario = document.getElementById('licenciaUsuario');
        const licenciaVencimiento = document.getElementById('licenciaVencimiento');
        const licenciaEstado = document.getElementById('licenciaEstado');

        let currentDeleteUrl = '';

        // Abrir modal cuando se hace clic en eliminar
        document.querySelectorAll('.btn-delete').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                const id = this.getAttribute('data-id');
                const software = this.getAttribute('data-software');
                const equipo = this.getAttribute('data-equipo');
                const usuario = this.getAttribute('data-usuario');
                const vencimiento = this.getAttribute('data-vencimiento');
                const estado = this.getAttribute('data-estado');
                const estadoClass = this.getAttribute('data-estado-class');
                
                // Actualizar información en el modal
                licenciaSoftware.textContent = software;
                licenciaEquipo.textContent = equipo;
                licenciaUsuario.textContent = usuario;
                licenciaVencimiento.textContent = vencimiento;
                licenciaEstado.innerHTML = `<span class="estado-badge ${estadoClass}">${estado}</span>`;
                
                // Configurar URL de eliminación
                currentDeleteUrl = `gestionLicencias.php?eliminar=${id}`;
                
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

        // Efectos hover para las filas de la tabla
        document.addEventListener('DOMContentLoaded', function() {
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