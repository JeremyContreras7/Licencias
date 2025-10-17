<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foro de Ayuda - Soporte Técnico</title>
    <link rel="stylesheet" href="../css/styleForo.css">
    <link rel="icon" href="../img/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="back-button-container">
        <a href="menu_informatico.php" class="btn-back">
            <i class="fas fa-arrow-left"></i> Volver al menú
        </a>    
    </div>
    
    <div class="container">
        <div class="logo-container">
            <img src="../img/logo.png" alt="Logo Institución">
        </div>
        
        <div class="header">
            <h1><i class="fas fa-headset"></i> Foro de Ayuda</h1>
            <p>Complete el siguiente formulario y nos pondremos en contacto con usted lo antes posible. Estamos aquí para ayudarle con cualquier consulta o problema que pueda tener.</p>
        </div>
        
        <form id="contactForm" class="contact-form" method="POST" action="../controlador/enviar.php">
            <div class="form-row">
                <div class="form-group">
                    <label for="name">
                        <i class="fas fa-user"></i> Nombre <span class="required">*</span>
                    </label>
                    <input type="text" id="name" name="name" required placeholder="Ingrese su nombre completo">
                </div>
                
                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i> Correo Electrónico <span class="required">*</span>
                    </label>
                    <input type="email" id="email" name="email" required placeholder="ejemplo@correo.com">
                </div>
            </div>
            
            <div class="form-group full-width">
                <label for="subject">
                    <i class="fas fa-tag"></i> Asunto <span class="required">*</span>
                </label>
                <input type="text" id="subject" name="subject" required placeholder="Breve descripción de su consulta">
            </div>
            
            <div class="form-group full-width">
                <label for="message">
                    <i class="fas fa-comment-dots"></i> Mensaje <span class="required">*</span>
                </label>
                <textarea id="message" name="message" required placeholder="Describa su consulta o problema en detalle..."></textarea>
                <small style="color: var(--gray); font-size: 0.85rem; margin-top: 5px; display: block;">
                    <i class="fas fa-info-circle"></i> Sea lo más específico posible para poder ayudarle mejor
                </small>
            </div>
            
            <div class="btn-container">
                <button type="submit" class="btn-send pulse" id="submitBtn">
                    <i class="fas fa-paper-plane"></i>
                    <span id="btnText">Enviar Mensaje</span>
                </button>
            </div>
            
            <div id="statusMessage" class="status-message"></div>
        </form>
        
        <div class="contact-info">
            <div class="info-item">
                <div class="info-icon">
                    <i class="fas fa-envelope"></i>
                </div>
                <div>
                    <p><strong>Correo Electrónico</strong></p>
                    <p>informatica@demovalle.com</p>
                </div>
            </div>
            <div class="info-item">
                <div class="info-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div>
                    <p><strong>Horario de Atención</strong></p>
                    <div class="schedule">
                        <p>Lunes a jueves: 8:00 - 17:30</p>
                        <p>Viernes: 8:00 - 14:00</p>
                    </div>
                </div>
            </div>
            <div class="info-item">
                <div class="info-icon">
                    <i class="fas fa-phone"></i>
                </div>
                <div>
                    <p><strong>Teléfono de Contacto</strong></p>
                    <p>+56 9 1234 5678</p>
                </div>
            </div>
        </div>
    </div>
    <script src="../js/foro.js"></script>

</body>
</html>