// Crear partículas de fondo
        function createParticles() {
            const particlesContainer = document.getElementById('particles');
            const particleCount = 15;
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                
                // Tamaño y posición aleatoria
                const size = Math.random() * 100 + 50;
                const left = Math.random() * 100;
                const top = Math.random() * 100;
                const delay = Math.random() * 5;
                
                particle.style.width = `${size}px`;
                particle.style.height = `${size}px`;
                particle.style.left = `${left}%`;
                particle.style.top = `${top}%`;
                particle.style.animationDelay = `${delay}s`;
                particle.style.opacity = Math.random() * 0.1 + 0.05;
                
                particlesContainer.appendChild(particle);
            }
        }

        // Script para mejorar la experiencia del usuario
        document.addEventListener('DOMContentLoaded', function() {
            createParticles();
            
            const form = document.getElementById('contactForm');
            const submitBtn = document.getElementById('submitBtn');
            const btnText = document.getElementById('btnText');
            const messageTextarea = document.getElementById('message');
            
            // Contador de caracteres
            const charCount = document.createElement('div');
            charCount.style.cssText = 'text-align: right; font-size: 0.8rem; color: var(--gray); margin-top: 5px;';
            messageTextarea.parentNode.appendChild(charCount);
            
            messageTextarea.addEventListener('input', function() {
                const length = this.value.length;
                charCount.textContent = `${length} caracteres`;
                
                if (length < 10) {
                    charCount.style.color = 'var(--warning)';
                } else if (length < 50) {
                    charCount.style.color = 'var(--warning)';
                } else {
                    charCount.style.color = 'var(--success)';
                }
            });
            
            form.addEventListener('submit', function(e) {
                // Validación mejorada del formulario
                const subject = document.getElementById('subject').value;
                const message = document.getElementById('message').value;
                
                if (!subject) {
                    e.preventDefault();
                    showMessage('❌ Por favor seleccione el tipo de solicitud.', 'error');
                    document.getElementById('subject').focus();
                    return;
                }
                
                if (message.length < 20) {
                    e.preventDefault();
                    showMessage('❌ Por favor describa su problema con más detalle (mínimo 20 caracteres).', 'error');
                    document.getElementById('message').focus();
                    return;
                }
                
                // Cambiar el texto del botón durante el envío
                submitBtn.disabled = true;
                btnText.textContent = 'Enviando solicitud...';
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
                submitBtn.classList.remove('pulse');
                
                // Simular envío (en producción esto sería real)
                setTimeout(() => {
                    showMessage('✅ Su solicitud ha sido enviada correctamente. Nos pondremos en contacto pronto.', 'success');
                }, 2000);
            });
            
            // Efectos de hover en los inputs
            const inputs = form.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.style.transform = 'translateY(-2px)';
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.style.transform = 'translateY(0)';
                });
            });
            
            function showMessage(message, type) {
                const statusMessage = document.getElementById('statusMessage');
                statusMessage.textContent = message;
                statusMessage.className = `status-message ${type}`;
                statusMessage.style.display = 'block';
                
                // Scroll suave al mensaje
                statusMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
                
                setTimeout(() => {
                    statusMessage.style.display = 'none';
                }, 6000);
            }
            
            // Efecto de carga inicial
            const container = document.querySelector('.container');
            container.style.opacity = '0';
            container.style.transform = 'translateY(30px)';
            
            setTimeout(() => {
                container.style.transition = 'all 0.6s ease-out';
                container.style.opacity = '1';
                container.style.transform = 'translateY(0)';
            }, 100);
        });
        // Función para cerrar mensajes
function closeMessage() {
    const overlay = document.getElementById('messageOverlay');
    if (overlay) {
        overlay.style.animation = 'slideOutDown 0.5s forwards';
        setTimeout(() => {
            overlay.remove();
        }, 500);
    }
}

// Cerrar mensaje automáticamente después de 5 segundos
document.addEventListener('DOMContentLoaded', function() {
    const messageOverlay = document.getElementById('messageOverlay');
    if (messageOverlay) {
        setTimeout(() => {
            closeMessage();
        }, 5000);
    }
    
    // Cerrar con la tecla ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeMessage();
        }
    });
    
    // Cerrar haciendo clic fuera del mensaje
    document.addEventListener('click', function(e) {
        const messageOverlay = document.getElementById('messageOverlay');
        if (messageOverlay && e.target === messageOverlay) {
            closeMessage();
        }
    });
});

// Agregar animación de salida
const style = document.createElement('style');
style.textContent = `
    @keyframes slideOutDown {
        from {
            opacity: 1;
            transform: translateY(0);
        }
        to {
            opacity: 0;
            transform: translateY(50px);
        }
    }
`;
document.head.appendChild(style);