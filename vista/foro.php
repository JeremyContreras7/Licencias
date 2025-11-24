<?php
session_start();
// Verificar si hay una sesión activa
if (!isset($_SESSION['rol'])) {
    header("Location: ../index.php");
    exit();
}

$rol = $_SESSION['rol'];
$nombre_usuario = htmlspecialchars($_SESSION['nombre'] ?? '');
$correo_usuario = htmlspecialchars($_SESSION['correo'] ?? '');

// Determinar la ruta del menú según el rol
if ($rol === "USUARIO") {
    $menu_url = "menu_funcionario.php";
    $menu_nombre = "Menú Principal";
} elseif ($rol === "ENCARGADO") {
    $menu_url = "menu_informatico.php";
    $menu_nombre = "Menú Informático";
} else {
    $menu_url = "../index.php";
    $menu_nombre = "Inicio";
}
?>

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
    <!-- Partículas de fondo -->
    <div class="particles" id="particles"></div>
    
    <div class="back-button-container">
        <a href="<?= $menu_url ?>" class="btn-back">
            <i class="fas fa-arrow-left"></i> Volver a <?= $menu_nombre ?>
        </a>
        
        <!-- Mostrar información del usuario -->
        <div class="user-info">
            <i class="fas fa-user-circle"></i>
            <div>
                <strong><?= $nombre_usuario ?></strong>
                <div class="role-badge"><?= $rol ?></div>
            </div>
        </div>
    </div>
    <!-- Mostrar mensajes de estado -->
<?php if (isset($_SESSION['message_type'])): ?>
<div class="message-overlay" id="messageOverlay">
    <div class="message-container animate__animated animate__bounceIn">
        <div class="message-icon message-<?= $_SESSION['message_type'] ?>">
            <?php if ($_SESSION['message_type'] === 'success'): ?>
                <i class="fas fa-check-circle"></i>
            <?php elseif ($_SESSION['message_type'] === 'error'): ?>
                <i class="fas fa-exclamation-circle"></i>
            <?php elseif ($_SESSION['message_type'] === 'warning'): ?>
                <i class="fas fa-exclamation-triangle"></i>
            <?php else: ?>
                <i class="fas fa-info-circle"></i>
            <?php endif; ?>
        </div>
        
        <div class="message-content">
            <h3 class="message-title"><?= $_SESSION['message_title'] ?? 'Mensaje del Sistema' ?></h3>
            <p class="message-text"><?= $_SESSION['message_content'] ?? '' ?></p>
            
            <?php if (isset($_SESSION['message_details'])): ?>
            <div class="message-details">
                <?php foreach ($_SESSION['message_details'] as $key => $value): ?>
                    <div class="detail-item">
                        <strong><?= ucfirst(str_replace('_', ' ', $key)) ?>:</strong>
                        <span><?= $value ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <button class="message-close" onclick="closeMessage()">
            <i class="fas fa-times"></i>
        </button>
        
        <div class="message-progress"></div>
    </div>
</div>

<?php 
// Limpiar los mensajes después de mostrarlos
unset($_SESSION['message_type'], $_SESSION['message_title'], $_SESSION['message_content'], $_SESSION['message_details']);
endif; 
?>
    
    <div class="container">
        <div class="logo-container">
            <img src="../img/logo.png" alt="Logo Institución">
        </div>
        
        <div class="header">
            <h1><i class="fas fa-headset"></i> Centro de Soporte Técnico</h1>
            <p>Estamos aquí para ayudarle. Complete el formulario y nuestro equipo se pondrá en contacto con usted a la brevedad. Su satisfacción es nuestra prioridad.</p>
        </div>
        
        <form id="contactForm" class="contact-form" method="POST" action="../controlador/enviar.php">
            <div class="form-row">
                <div class="form-group">
                    <label for="name">
                        <i class="fas fa-user"></i> Nombre Completo <span class="required">*</span>
                    </label>
                    <input type="text" id="name" name="name" required 
                           placeholder="Ingrese su nombre completo"
                           value="<?= $nombre_usuario ?>">
                </div>
                
                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i> Correo Electrónico <span class="required">*</span>
                    </label>
                    <input type="email" id="email" name="email" required 
                           placeholder="ejemplo@institucion.com"
                           value="<?= $correo_usuario ?>">
                </div>
            </div>
            
            <div class="form-group full-width">
                <label for="subject">
                    <i class="fas fa-tag"></i> Tipo de Solicitud <span class="required">*</span>
                </label>
                <select id="subject" name="subject" required>
                    <option value="">Seleccione el tipo de solicitud...</option>
                    <option value="🛠️ Problema técnico con equipo">🛠️ Problema técnico con equipo</option>
                    <option value="📋 Consulta sobre licencias de software">📋 Consulta sobre licencias de software</option>
                    <option value="💻 Error en software específico">💻 Error en software específico</option>
                    <option value="🖥️ Solicitud de nuevo equipo">🖥️ Solicitud de nuevo equipo</option>
                    <option value="🚨 Problema crítico del sistema">🚨 Problema crítico del sistema</option>
                    <option value="📊 Consulta sobre reportes">📊 Consulta sobre reportes</option>
                    <option value="🔐 Problema de acceso">🔐 Problema de acceso al sistema</option>
                    <option value="❓ Otra consulta">❓ Otra consulta</option>
                </select>
            </div>
            
            <div class="form-group full-width">
                <label for="priority">
                    <i class="fas fa-exclamation-circle"></i> Nivel de Urgencia
                </label>
                <select id="priority" name="priority">
                    <option value="Baja">🟢 Baja - Consulta general</option>
                    <option value="Media" selected>🟡 Media - Problema que afecta el trabajo</option>
                    <option value="Alta">🔴 Alta - Bloquea actividades críticas</option>
                    <option value="Crítica">🚨 Crítica - Todo el sistema está afectado</option>
                </select>
            </div>
            
            <div class="form-group full-width">
                <label for="message">
                    <i class="fas fa-comment-dots"></i> Descripción Detallada <span class="required">*</span>
                </label>
                <textarea id="message" name="message" required 
                          placeholder="Por favor, describa su problema o consulta con el mayor detalle posible. Incluya información como: número de equipo, software afectado, mensajes de error, pasos para reproducir el problema..."
                          rows="6"></textarea>
                <small style="color: var(--gray); font-size: 0.85rem; margin-top: 8px; display: block;">
                    <i class="fas fa-info-circle"></i> Entre más detallada sea su descripción, más rápido podremos ayudarle.
                </small>
            </div>
            
            <!-- Campos ocultos para enviar información del usuario -->
            <input type="hidden" name="user_role" value="<?= $rol ?>">
            <input type="hidden" name="user_id" value="<?= $_SESSION['id_usuario'] ?? '' ?>">
            
            <div class="btn-container">
                <button type="submit" class="btn-send pulse" id="submitBtn">
                    <i class="fas fa-paper-plane"></i>
                    <span id="btnText">Enviar Solicitud de Soporte</span>
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
                    <p>soporte@demovalle.com</p>
                    <p style="font-size: 0.9rem; color: var(--gray);">Respuesta en 24 horas</p>
                </div>
            </div>
            <div class="info-item">
                <div class="info-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div>
                    <p><strong>Horario de Atención</strong></p>
                    <div class="schedule">
                        <p>Lunes a Jueves: 8:00 - 17:30</p>
                        <p>Viernes: 8:00 - 14:00</p>
                        <p style="color: var(--primary); font-weight: 600;">Sábados: 9:00 - 13:00 (emergencias)</p>
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
                    <p style="font-size: 0.9rem; color: var(--gray);">Línea directa soporte</p>
                </div>
            </div>
            <div class="info-item">
                <div class="info-icon">
                    <i class="fas fa-rocket"></i>
                </div>
                <div>
                    <p><strong>Tiempo de Respuesta</strong></p>
                    <p>Urgencias: 2 horas</p>
                    <p>Consultas: 24 horas</p>
                </div>
            </div>
        </div>
        
        <!-- Información adicional para el usuario -->
        <div class="additional-info">
            <h3><i class="fas fa-lightbulb"></i> Para una atención más rápida</h3>
            <div class="tips">
                <div class="tip">
                    <i class="fas fa-desktop"></i>
                    <p><strong>Problemas con equipos</strong>: Incluya el número de inventario y modelo del equipo afectado</p>
                </div>
                <div class="tip">
                    <i class="fas fa-key"></i>
                    <p><strong>Problemas de acceso</strong>: Especifique el software, versión y mensajes de error exactos</p>
                </div>
                <div class="tip">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p><strong>Errores del sistema</strong>: Capture pantallazos y describa los pasos para reproducir el error</p>
                </div>
            </div>
        </div>
    </div>
    <script src="../js/foro.js"></script>
</body>
</html>