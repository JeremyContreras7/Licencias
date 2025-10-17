function verpassword() {
            let x = document.getElementById("password");
            let icon = x.parentElement.querySelector('.input-icon');
            if (x.type === "password") {
                x.type = "text";
                icon.className = 'fas fa-eye input-icon';
            } else {
                x.type = "password";
                icon.className = 'fas fa-key input-icon';
            }
        }

        // Form submission
        document.getElementById('frmlogin').addEventListener('submit', function(e) {
            const btn = document.getElementById('loginBtn');
            const btnText = btn.querySelector('.btn-text');
            const btnIcon = btn.querySelector('.fa-arrow-right');
            
            btnText.textContent = 'Iniciando Sesión...';
            btnIcon.className = 'fas fa-spinner fa-spin';
            btn.classList.add('btn-loading');
        });

        // Input interactions
        const inputs = document.querySelectorAll('.cajaentradatexto, select');
        inputs.forEach(input => {
            // Add focus effect
            input.addEventListener('focus', function() {
                this.parentElement.parentElement.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.parentElement.classList.remove('focused');
            });

            // Add input validation
            input.addEventListener('input', function() {
                if (this.checkValidity()) {
                    this.classList.remove('input-error');
                } else {
                    this.classList.add('input-error');
                }
            });
        });

        // Role selection effect
        document.getElementById('role').addEventListener('change', function() {
            const indicators = document.querySelectorAll('.role-indicator');
            indicators.forEach(indicator => indicator.style.opacity = '0.6');
            
            const selectedRole = this.value;
            if (selectedRole === 'ADMIN') {
                indicators[0].style.opacity = '1';
                indicators[0].style.background = 'rgba(37, 99, 235, 0.1)';
            } else if (selectedRole === 'ENCARGADO') {
                indicators[1].style.opacity = '1';
                indicators[1].style.background = 'rgba(37, 99, 235, 0.1)';
            } else if (selectedRole === 'USUARIO') {
                indicators[2].style.opacity = '1';
                indicators[2].style.background = 'rgba(37, 99, 235, 0.1)';
            }
        });