 document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('contactForm');
            const statusMessage = document.getElementById('statusMessage');
            const submitBtn = document.getElementById('submitBtn');
            const btnText = document.getElementById('btnText');

            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(form);
                
                // Mostrar estado de carga
                btnText.innerHTML = '<div class="loading"></div> Enviando...';
                submitBtn.disabled = true;
                submitBtn.classList.remove('pulse');
                
                // Mostrar mensaje de carga
                statusMessage.textContent = 'Enviando mensaje...';
                statusMessage.className = 'status-message';
                statusMessage.style.display = 'block';
                
                // Simular envío (reemplaza con tu endpoint real)
                setTimeout(() => {
                    fetch(form.action, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.text())
                    .then(data => {
                        if (data.includes('enviado') || data.includes('success')) {
                            statusMessage.textContent = '¡Mensaje enviado con éxito! Nos pondremos en contacto contigo pronto.';
                            statusMessage.className = 'status-message success';
                            form.reset();
                            
                            // Mostrar confeti visual
                            showSuccessAnimation();
                        } else {
                            throw new Error('Error en el servidor');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        statusMessage.textContent = 'Error al enviar el mensaje. Por favor, inténtelo de nuevo o contacte directamente por teléfono.';
                        statusMessage.className = 'status-message error';
                    })
                    .finally(() => {
                        // Restaurar el botón
                        btnText.textContent = 'Enviar Mensaje';
                        submitBtn.disabled = false;
                        submitBtn.classList.add('pulse');
                    });
                }, 1500);
            });

            // Efectos de validación en tiempo real
            const inputs = form.querySelectorAll('input, textarea');
            inputs.forEach(input => {
                input.addEventListener('input', function() {
                    if (this.checkValidity()) {
                        this.style.borderColor = '#28a745';
                    } else {
                        this.style.borderColor = '';
                    }
                });
            });

            function showSuccessAnimation() {
                // Efecto visual simple de éxito
                submitBtn.style.background = 'linear-gradient(135deg, #27ae60, #2ecc71)';
                setTimeout(() => {
                    submitBtn.style.background = 'linear-gradient(135deg, var(--primary), var(--primary-dark))';
                }, 2000);
            }

            // Efecto de aparición gradual para los elementos
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, observerOptions);

            // Aplicar a los elementos del formulario
            const formElements = document.querySelectorAll('.form-group, .btn-container');
            formElements.forEach(el => {
                el.style.opacity = '0';
                el.style.transform = 'translateY(20px)';
                el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                observer.observe(el);
            });
        });