// Efectos de interacción mejorados para editar software - Responsive
document.addEventListener('DOMContentLoaded', function() {
    // Detectar si es dispositivo táctil
    const isTouchDevice = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
    
    // Agregar efecto de carga al botón de guardar
    const form = document.getElementById('softwareForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.classList.add('btn-loading');
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
            }
        });
    }

    // Efecto visual para el checkbox crítico - Optimizado para touch
    const checkbox = document.getElementById('es_critico');
    const criticoCard = document.getElementById('criticoCard');
    
    if (checkbox && criticoCard) {
        const updateCheckboxState = function() {
            if (checkbox.checked) {
                criticoCard.classList.add('active');
                if (!isTouchDevice) {
                    criticoCard.style.transform = 'translateY(-2px)';
                    criticoCard.style.boxShadow = '0 8px 25px rgba(243, 156, 18, 0.3)';
                }
            } else {
                criticoCard.classList.remove('active');
                if (!isTouchDevice) {
                    criticoCard.style.transform = 'translateY(0)';
                    criticoCard.style.boxShadow = 'var(--shadow)';
                }
            }
        };

        checkbox.addEventListener('change', updateCheckboxState);

        // Efecto hover solo para dispositivos no táctiles
        if (!isTouchDevice) {
            criticoCard.addEventListener('mouseenter', function() {
                if (!checkbox.checked) {
                    this.style.borderColor = 'var(--primary)';
                }
            });
            
            criticoCard.addEventListener('mouseleave', function() {
                if (!checkbox.checked) {
                    this.style.borderColor = 'var(--gray-light)';
                }
            });
        }

        // Permitir hacer clic en toda la tarjeta (optimizado para touch)
        criticoCard.addEventListener('click', function(e) {
            if (e.target !== checkbox) {
                checkbox.checked = !checkbox.checked;
                checkbox.dispatchEvent(new Event('change'));
                
                // Feedback táctil
                if (isTouchDevice) {
                    this.style.transform = 'scale(0.98)';
                    setTimeout(() => {
                        this.style.transform = '';
                    }, 150);
                }
            }
        });

        // Estado inicial
        updateCheckboxState();
    }

    // Auto-ocultar mensajes después de 5 segundos
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            alert.style.transition = 'all 0.5s ease';
            setTimeout(() => {
                if (alert.parentElement) {
                    alert.parentElement.removeChild(alert);
                }
            }, 500);
        }, 5000);
    });

    // Validación en tiempo real del nombre
    const nombreInput = document.getElementById('nombre_software');
    if (nombreInput) {
        const validateNombre = function() {
            const value = this.value.trim();
            const isValid = value.length > 0;
            
            if (isValid) {
                this.style.borderColor = 'var(--success)';
            } else {
                this.style.borderColor = '';
            }
        };

        nombreInput.addEventListener('input', validateNombre);
        nombreInput.addEventListener('blur', validateNombre);
    }

    // Efectos de foco mejorados en campos del formulario (solo desktop)
    if (!isTouchDevice) {
        const inputs = document.querySelectorAll('input');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'translateY(-2px)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'translateY(0)';
            });
        });
    }

    // Gestión de cambios en el formulario para prevenir pérdida de datos
    let formChanged = false;
    const formElements = document.querySelectorAll('input, textarea, select');
    
    formElements.forEach(element => {
        const initialValue = element.value;
        
        const checkChanges = function() {
            formChanged = this.value !== initialValue;
        };
        
        element.addEventListener('input', checkChanges);
        element.addEventListener('change', checkChanges);
    });

    // Prevenir salida si hay cambios sin guardar
    window.addEventListener('beforeunload', function(e) {
        if (formChanged) {
            e.preventDefault();
            e.returnValue = 'Tienes cambios sin guardar. ¿Estás seguro de que quieres salir?';
        }
    });

    // Resetear el estado de cambios cuando se envía el formulario
    if (form) {
        form.addEventListener('submit', function() {
            formChanged = false;
        });
    }

    // Animación de aparición gradual para los elementos
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

    // Aplicar animación a los elementos del formulario
    const formElementsToAnimate = document.querySelectorAll('.field, .feature-toggle, .form-actions');
    formElementsToAnimate.forEach((el, index) => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = `opacity 0.6s ease ${index * 0.1}s, transform 0.6s ease ${index * 0.1}s`;
        observer.observe(el);
    });

    // Optimización para dispositivos táctiles
    if (isTouchDevice) {
        // Aumentar área táctil para botones
        const buttons = document.querySelectorAll('button, .btn-cancel, .back-btn');
        buttons.forEach(btn => {
            btn.style.minHeight = '44px';
            btn.style.minWidth = '44px';
        });
        
        // Mejorar feedback táctil
        const touchElements = document.querySelectorAll('.checkbox-card, .btn-cancel, .btn-save, .back-btn');
        touchElements.forEach(el => {
            el.addEventListener('touchstart', function() {
                this.style.transform = 'scale(0.98)';
            });
            
            el.addEventListener('touchend', function() {
                this.style.transform = '';
            });
        });
    }

    // Detectar cambios de orientación
    window.addEventListener('orientationchange', function() {
        // Pequeño delay para que se complete el cambio de orientación
        setTimeout(() => {
            window.scrollTo(0, 0);
        }, 100);
    });

    // Prevenir zoom en inputs en iOS
    const inputs = document.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
        input.addEventListener('touchstart', function(e) {
            if (window.innerWidth <= 768) {
                this.style.fontSize = '16px'; // Previene zoom en iOS
            }
        });
    });
});

// Animación de pulso para elementos importantes
const style = document.createElement('style');
style.textContent = `
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }
    
    @media (max-width: 768px) {
        .btn-save {
            animation: pulse 3s infinite;
        }
    }
`;
document.head.appendChild(style);