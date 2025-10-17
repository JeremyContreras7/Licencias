    
        function toggleTipoEncargado() {
            const rol = document.getElementById('rol').value;
            const tipoEncargadoSelect = document.getElementById('tipo_encargado');
            
            if (rol === 'USUARIO') {
                tipoEncargadoSelect.disabled = false;
                tipoEncargadoSelect.required = true;
            } else {
                tipoEncargadoSelect.disabled = true;
                tipoEncargadoSelect.required = false;
                tipoEncargadoSelect.value = '';
            }
        }

        function togglePassword() {
            const passwordInput = document.getElementById('pass');
            const toggleButton = document.querySelector('.toggle-password i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleButton.className = 'fas fa-eye-slash';
            } else {
                passwordInput.type = 'password';
                toggleButton.className = 'fas fa-eye';
            }
        }

        // Inicializar el estado del campo tipo_encargado
        document.addEventListener('DOMContentLoaded', function() {
            toggleTipoEncargado();
            
            // Validación de contraseña
            const form = document.getElementById('userForm');
            const passwordInput = document.getElementById('pass');
            
            form.addEventListener('submit', function(e) {
                if (passwordInput.value && passwordInput.value.length < 8) {
                    e.preventDefault();
                    alert('La contraseña debe tener al menos 8 caracteres.');
                    passwordInput.focus();
                }
            });
        });

        // Efectos de interacción
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', function() {
                this.style.transform = 'translateY(-2px)';
                this.style.boxShadow = '0 4px 12px rgba(0,0,0,0.1)';
            });
            
            input.addEventListener('blur', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = 'none';
            });
        });
    
        function toggleTipoEncargado() {
    const rol = document.getElementById('rol').value;
    const tipoEncargadoField = document.getElementById('tipo_encargado');
    const establecimientoField = document.getElementById('id_establecimiento');
    const establecimientoHelp = document.getElementById('establecimiento-help');
    
    // Manejar campo tipo_encargado
    if (rol === 'USUARIO') {
        tipoEncargadoField.disabled = false;
        tipoEncargadoField.required = true;
    } else {
        tipoEncargadoField.disabled = true;
        tipoEncargadoField.required = false;
        if (rol !== 'USUARIO') {
            tipoEncargadoField.value = '';
        }
    }
    
    // Manejar campo establecimiento
    if (rol === 'ADMIN') {
        establecimientoField.disabled = true;
        establecimientoField.required = false;
        establecimientoField.value = '';
        establecimientoHelp.textContent = 'Los administradores no están asignados a un establecimiento específico';
        establecimientoHelp.style.color = '#6b7280';
    } else {
        establecimientoField.disabled = false;
        establecimientoField.required = true;
        establecimientoHelp.textContent = 'Selecciona el establecimiento al que pertenece el usuario';
        establecimientoHelp.style.color = '#6b7280';
    }
}

// También añade esta función para el evento change del rol
document.addEventListener('DOMContentLoaded', function() {
    const rolSelect = document.getElementById('rol');
    if (rolSelect) {
        rolSelect.addEventListener('change', toggleTipoEncargado);
    }
    
    // Ejecutar al cargar la página para establecer estado inicial
    toggleTipoEncargado();
});

// Función para mostrar/ocultar contraseña
function togglePassword() {
    const passwordInput = document.getElementById('pass');
    const toggleButton = document.querySelector('.toggle-password i');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleButton.className = 'fas fa-eye-slash';
    } else {
        passwordInput.type = 'password';
        toggleButton.className = 'fas fa-eye';
    }
}

// Validación adicional del formulario
document.getElementById('userForm').addEventListener('submit', function(e) {
    const rol = document.getElementById('rol').value;
    const establecimiento = document.getElementById('id_establecimiento').value;
    
    // Si no es ADMIN y no hay establecimiento seleccionado, prevenir envío
    if (rol !== 'ADMIN' && !establecimiento) {
        e.preventDefault();
        alert('Por favor selecciona un establecimiento para este rol.');
        document.getElementById('id_establecimiento').focus();
    }
});