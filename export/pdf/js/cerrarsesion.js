// Script específico para el botón de cerrar sesión
document.addEventListener('DOMContentLoaded', function() {
    const logoutBtn = document.querySelector('.logout');
    
    if (logoutBtn) {
        // Efecto de confirmación al hacer clic
        logoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Crear overlay de confirmación
            const overlay = document.createElement('div');
            overlay.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 10000;
                backdrop-filter: blur(5px);
            `;
            
            // Crear modal de confirmación
            const modal = document.createElement('div');
            modal.style.cssText = `
                background: white;
                padding: 30px;
                border-radius: 15px;
                text-align: center;
                box-shadow: 0 10px 30px rgba(0,0,0,0.3);
                max-width: 400px;
                width: 90%;
                animation: modalSlideIn 0.3s ease;
            `;
            
            modal.innerHTML = `
                <div style="font-size: 3rem; color: #ff6b6b; margin-bottom: 15px;">
                    <i class="fas fa-sign-out-alt"></i>
                </div>
                <h3 style="margin-bottom: 10px; color: #333; font-family: 'Inter', sans-serif;">
                    ¿Cerrar Sesión?
                </h3>
                <p style="color: #666; margin-bottom: 25px; line-height: 1.5;">
                    Estás a punto de salir del panel administrativo. ¿Estás seguro de que deseas continuar?
                </p>
                <div style="display: flex; gap: 10px; justify-content: center;">
                    <button id="confirmLogout" style="
                        background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
                        color: white;
                        border: none;
                        padding: 12px 25px;
                        border-radius: 8px;
                        cursor: pointer;
                        font-weight: 500;
                        transition: all 0.3s ease;
                        font-family: 'Inter', sans-serif;
                    ">
                        <i class="fas fa-check"></i> Cerrar Sesión
                    </button>
                    <button id="cancelLogout" style="
                        background: #f8f9fa;
                        color: #333;
                        border: 1px solid #ddd;
                        padding: 12px 25px;
                        border-radius: 8px;
                        cursor: pointer;
                        font-weight: 500;
                        transition: all 0.3s ease;
                        font-family: 'Inter', sans-serif;
                    ">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                </div>
            `;
            
            overlay.appendChild(modal);
            document.body.appendChild(overlay);
            
            // Animación de entrada
            const style = document.createElement('style');
            style.textContent = `
                @keyframes modalSlideIn {
                    from {
                        opacity: 0;
                        transform: translateY(-50px) scale(0.8);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0) scale(1);
                    }
                }
            `;
            document.head.appendChild(style);
            
            // Eventos de los botones del modal
            document.getElementById('confirmLogout').addEventListener('click', function() {
                // Añadir efecto de salida
                modal.style.animation = 'modalSlideOut 0.3s ease forwards';
                
                // Crear animación de salida
                const exitStyle = document.createElement('style');
                exitStyle.textContent = `
                    @keyframes modalSlideOut {
                        from {
                            opacity: 1;
                            transform: translateY(0) scale(1);
                        }
                        to {
                            opacity: 0;
                            transform: translateY(50px) scale(0.8);
                        }
                    }
                `;
                document.head.appendChild(exitStyle);
                
                // Redirigir después de la animación
                setTimeout(() => {
                    window.location.href = logoutBtn.getAttribute('href');
                }, 300);
            });
            
            document.getElementById('cancelLogout').addEventListener('click', function() {
                // Animación de salida al cancelar
                overlay.style.animation = 'fadeOut 0.3s ease forwards';
                
                const fadeStyle = document.createElement('style');
                fadeStyle.textContent = `
                    @keyframes fadeOut {
                        from { opacity: 1; }
                        to { opacity: 0; }
                    }
                `;
                document.head.appendChild(fadeStyle);
                
                setTimeout(() => {
                    document.body.removeChild(overlay);
                    document.head.removeChild(fadeStyle);
                    document.head.removeChild(style);
                    if (exitStyle) document.head.removeChild(exitStyle);
                }, 300);
            });
            
            // Cerrar modal haciendo clic fuera
            overlay.addEventListener('click', function(e) {
                if (e.target === overlay) {
                    document.getElementById('cancelLogout').click();
                }
            });
            
            // Efecto hover para botones del modal
            const confirmBtn = document.getElementById('confirmLogout');
            const cancelBtn = document.getElementById('cancelLogout');
            
            [confirmBtn, cancelBtn].forEach(btn => {
                btn.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px)';
                    this.style.boxShadow = '0 5px 15px rgba(0,0,0,0.2)';
                });
                
                btn.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = 'none';
                });
            });
        });
        
        // Efecto de tooltip
        logoutBtn.addEventListener('mouseenter', function() {
            const tooltip = document.createElement('div');
            tooltip.textContent = 'Cerrar sesión del sistema';
            tooltip.style.cssText = `
                position: absolute;
                background: #333;
                color: white;
                padding: 8px 12px;
                border-radius: 6px;
                font-size: 12px;
                white-space: nowrap;
                z-index: 1000;
                pointer-events: none;
                opacity: 0;
                transform: translateY(10px);
                transition: all 0.3s ease;
                font-family: 'Inter', sans-serif;
            `;
            
            this.appendChild(tooltip);
            
            // Posicionar tooltip
            setTimeout(() => {
                tooltip.style.opacity = '1';
                tooltip.style.transform = 'translateY(0)';
            }, 10);
        });
        
        logoutBtn.addEventListener('mouseleave', function() {
            const tooltip = this.querySelector('div');
            if (tooltip) {
                tooltip.style.opacity = '0';
                tooltip.style.transform = 'translateY(10px)';
                setTimeout(() => {
                    if (tooltip.parentNode) {
                        tooltip.parentNode.removeChild(tooltip);
                    }
                }, 300);
            }
        });
    }
});

// Efecto de teclado (Ctrl + L para logout)
document.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 'l') {
        e.preventDefault();
        const logoutBtn = document.querySelector('.logout');
        if (logoutBtn) {
            logoutBtn.click();
        }
    }
});