 let currentTab = 1;
        const totalTabs = 3;

        function showTab(tabNumber) {
            // Ocultar todos los tabs
            for(let i = 1; i <= totalTabs; i++) {
                document.getElementById(`tab${i}`).classList.remove('active');
                document.getElementById(`tab${i}Btn`).classList.remove('active');
            }
            
            // Mostrar tab seleccionado
            document.getElementById(`tab${tabNumber}`).classList.add('active');
            document.getElementById(`tab${tabNumber}Btn`).classList.add('active');
            
            // Actualizar botones de navegación
            updateNavigationButtons(tabNumber);
            
            currentTab = tabNumber;
        }

        function navigateTabs(direction) {
            const newTab = currentTab + direction;
            if(newTab >= 1 && newTab <= totalTabs) {
                showTab(newTab);
            }
        }

        function updateNavigationButtons(tabNumber) {
            const prevBtn = document.getElementById('prevBtn');
            const nextBtn = document.getElementById('nextBtn');
            const submitBtn = document.getElementById('submitBtn');
            
            if(tabNumber === 1) {
                prevBtn.style.display = 'none';
                nextBtn.style.display = 'flex';
                submitBtn.style.display = 'none';
            } else if(tabNumber === totalTabs) {
                prevBtn.style.display = 'flex';
                nextBtn.style.display = 'none';
                submitBtn.style.display = 'flex';
            } else {
                prevBtn.style.display = 'flex';
                nextBtn.style.display = 'flex';
                submitBtn.style.display = 'none';
            }
        }

        // Contador de caracteres
        function updateCharCounter(textarea, maxLength) {
            const currentLength = textarea.value.length;
            const counterDiv = document.getElementById('justificacionCounter');
            const currentSpan = document.getElementById('currentChars');
            
            currentSpan.textContent = currentLength;
            
            // Cambiar color según la longitud
            counterDiv.className = 'char-counter';
            if (currentLength > maxLength * 0.8) {
                counterDiv.classList.add('warning');
            } else if (currentLength >= maxLength) {
                counterDiv.classList.add('danger');
            } else {
                counterDiv.classList.add('normal');
            }
        }

        // Validación del formulario
        document.getElementById('frmSolicitud').addEventListener('submit', function(e) {
            const btnSubmit = document.getElementById('submitBtn');
            const cantidad = document.getElementById('cantidad').value;
            const justificacion = document.getElementById('justificacion').value;
            const software = document.getElementById('software').value;
            const departamento = document.getElementById('departamento').value;
            const tipoLicencia = document.getElementById('tipo_licencia').value;
            
            let errors = [];
            
            // Validar campos requeridos
            if (!software) errors.push('El nombre del software es requerido');
            if (!tipoLicencia) errors.push('El tipo de licencia es requerido');
            if (!departamento) errors.push('El departamento es requerido');
            
            if (cantidad < 1 || cantidad > 1000) {
                errors.push('La cantidad debe ser entre 1 y 1000');
            }
            
            if (justificacion.length < 20) {
                errors.push('La justificación debe tener al menos 20 caracteres');
            }
            
            if (errors.length > 0) {
                e.preventDefault();
                showAlert('error', errors.join('<br>'));
                
                // Ir al tab donde está el error
                if (!software || !tipoLicencia) showTab(1);
                else if (!departamento) showTab(2);
                else if (justificacion.length < 20) showTab(3);
            } else {
                btnSubmit.classList.add('loading');
            }
        });

        // Función para mostrar alertas
        function showAlert(type, message) {
            const alertContainer = document.getElementById('alertContainer');
            const alertMessage = document.getElementById('alertMessage');
            
            alertContainer.className = `alert alert-${type}`;
            alertMessage.innerHTML = message;
            alertContainer.style.display = 'flex';
            
            // Scroll a la alerta
            alertContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
            
            // Desaparecer después de 5 segundos
            setTimeout(() => {
                alertContainer.style.display = 'none';
            }, 5000);
        }

        // Inicializar
        document.addEventListener('DOMContentLoaded', function() {
            const justificacion = document.getElementById('justificacion');
            updateCharCounter(justificacion, 500);
            showTab(1);
        });

        // Validar cantidad
        document.getElementById('cantidad').addEventListener('input', function(e) {
            let value = parseInt(this.value);
            if (isNaN(value) || value < 1) {
                this.value = 1;
            } else if (value > 1000) {
                this.value = 1000;
            }
        });

        // Marcar checkboxes automáticamente
        document.querySelectorAll('.checklist-item input[type="checkbox"]').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const item = this.closest('.checklist-item');
                if (this.checked) {
                    item.style.background = '#f0f7ff';
                } else {
                    item.style.background = 'white';
                }
            });
        });

        // Efecto en el file upload (simulado)
        const fileUpload = document.querySelector('.file-upload');
        if(fileUpload) {
            fileUpload.addEventListener('click', function() {
                alert('Funcionalidad de carga de archivos disponible próximamente');
            });
        }

        // Tooltips dinámicos
        document.querySelectorAll('[data-tooltip]').forEach(element => {
            element.addEventListener('mouseenter', function(e) {
                const tooltip = this.getAttribute('data-tooltip');
                console.log('Tooltip:', tooltip); // Para depuración
            });
        });