 let userToDelete = null;

        function toggleCamposPorRol(rol) {
            const selectTipo = document.getElementById('tipo_encargado');
            const selectEstablecimiento = document.getElementById('id_establecimiento');
            const establecimientoHelp = document.getElementById('establecimientoHelp');
            const tipoEncargadoHelp = document.getElementById('tipoEncargadoHelp');
            
            if (rol === 'USUARIO') {
                // Habilitar tipo de encargado y establecimiento
                selectTipo.disabled = false;
                selectTipo.required = true;
                selectEstablecimiento.disabled = false;
                selectEstablecimiento.required = true;
                establecimientoHelp.textContent = 'Requerido para Personal Escolar';
                establecimientoHelp.style.color = 'var(--gray-600)';
                tipoEncargadoHelp.textContent = 'Selecciona el tipo de encargado para Personal Escolar';
                tipoEncargadoHelp.style.color = 'var(--gray-600)';
            } else if (rol === 'ADMIN') {
                // Deshabilitar ambos campos para ADMIN
                selectTipo.disabled = true;
                selectTipo.required = false;
                selectTipo.value = '';
                selectEstablecimiento.disabled = true;
                selectEstablecimiento.required = false;
                selectEstablecimiento.value = '';
                establecimientoHelp.textContent = 'No requerido para administradores del sistema';
                establecimientoHelp.style.color = 'var(--info)';
                tipoEncargadoHelp.textContent = 'No aplica para administradores';
                tipoEncargadoHelp.style.color = 'var(--info)';
            } else if (rol === 'ENCARGADO') {
                // ENCARGADO - solo habilitar establecimiento
                selectTipo.disabled = true;
                selectTipo.required = false;
                selectTipo.value = '';
                selectEstablecimiento.disabled = false;
                selectEstablecimiento.required = true;
                establecimientoHelp.textContent = 'Requerido para encargados informáticos';
                establecimientoHelp.style.color = 'var(--gray-600)';
                tipoEncargadoHelp.textContent = 'No aplica para encargados informáticos';
                tipoEncargadoHelp.style.color = 'var(--info)';
            } else {
                // Estado inicial
                selectTipo.disabled = true;
                selectTipo.required = false;
                selectTipo.value = '';
                selectEstablecimiento.disabled = false;
                selectEstablecimiento.required = false;
                establecimientoHelp.textContent = 'Selecciona un rol para ver los requisitos';
                establecimientoHelp.style.color = 'var(--gray-600)';
                tipoEncargadoHelp.textContent = 'Solo aplica para usuarios con rol "Personal Escolar"';
                tipoEncargadoHelp.style.color = 'var(--gray-600)';
            }
        }

        function showDeleteModal(userId, userName, userRole) {
            userToDelete = userId;
            const modal = document.getElementById('deleteModal');
            const userInfo = document.getElementById('userInfo');
            const confirmBtn = document.getElementById('confirmDeleteBtn');
            
            // Actualizar información del usuario
            userInfo.innerHTML = `
                <div style="text-align: left;">
                    <strong><i class="fas fa-user"></i> Nombre:</strong> ${userName}<br>
                    <strong><i class="fas fa-tag"></i> Rol:</strong> ${userRole}<br>
                    <strong><i class="fas fa-id-badge"></i> ID:</strong> #${userId}
                </div>
            `;
            
            // Actualizar mensaje
            document.getElementById('modalMessage').textContent = 
                `Esta acción eliminará permanentemente al usuario "${userName}" del sistema.`;
            
            // Actualizar enlace de confirmación
            confirmBtn.href = `gestionUsuarios.php?eliminar=${userId}`;
            
            // Mostrar modal
            modal.style.display = 'flex';
            
            // Prevenir scroll del body
            document.body.style.overflow = 'hidden';
        }

        function hideDeleteModal() {
            const modal = document.getElementById('deleteModal');
            modal.style.display = 'none';
            userToDelete = null;
            
            // Restaurar scroll del body
            document.body.style.overflow = 'auto';
        }

        // Cerrar modal al hacer clic fuera
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                hideDeleteModal();
            }
        });

        // Cerrar modal con ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                hideDeleteModal();
            }
        });

        // Inicializar el estado de los campos al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            const rolSelect = document.getElementById('rol');
            if (rolSelect) {
                toggleCamposPorRol(rolSelect.value);
            }

            // Efectos hover mejorados para las filas de la tabla
            const tableRows = document.querySelectorAll('.table tbody tr');
            tableRows.forEach(row => {
                row.addEventListener('mouseenter', function() {
                    this.style.transition = 'all 0.3s ease';
                });
            });

            // Animar contadores de estadísticas
            const statNumbers = document.querySelectorAll('.stat-number');
            statNumbers.forEach(stat => {
                const target = parseInt(stat.textContent);
                let current = 0;
                const increment = target / 50;
                const duration = 1500;
                const stepTime = duration / (target / increment);
                
                const timer = setInterval(() => {
                    current += increment;
                    if (current >= target) {
                        stat.textContent = target;
                        clearInterval(timer);
                    } else {
                        stat.textContent = Math.ceil(current);
                    }
                }, stepTime);
            });
        });