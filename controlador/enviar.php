<?php
// Archivo: enviar.php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../phpmailer/Exception.php';
require '../phpmailer/PHPMailer.php';
require '../phpmailer/SMTP.php';

// Iniciar sesión para mostrar mensajes bonitos
session_start();

// Verificar si se envió el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recoger y sanitizar los datos del formulario
    $name = htmlspecialchars(trim($_POST['name']));
    $email = htmlspecialchars(trim($_POST['email']));
    $subject = htmlspecialchars(trim($_POST['subject']));
    $message = htmlspecialchars(trim($_POST['message']));
    $priority = $_POST['priority'] ?? 'Media';
    $user_role = $_POST['user_role'] ?? 'Usuario';
    
    // Validar campos requeridos
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $_SESSION['message_type'] = 'error';
        $_SESSION['message_title'] = '❌ Campos Incompletos';
        $_SESSION['message_content'] = 'Por favor, complete todos los campos requeridos.';
        header("Location: ../vista/foro.php");
        exit;
    }
    
    // Validar formato de email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['message_type'] = 'error';
        $_SESSION['message_title'] = '❌ Correo Inválido';
        $_SESSION['message_content'] = 'Por favor, ingrese un correo electrónico válido.';
        header("Location: ../vista/foro.php");
        exit;
    }
    
    // Validar longitud mínima del mensaje
    if (strlen($message) < 20) {
        $_SESSION['message_type'] = 'warning';
        $_SESSION['message_title'] = '⚠️ Mensaje Muy Corto';
        $_SESSION['message_content'] = 'Por favor, describa su problema con más detalle (mínimo 20 caracteres).';
        header("Location: ../vista/foro.php");
        exit;
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'sandbox.smtp.mailtrap.io';
        $mail->SMTPAuth = true;
        $mail->Username = '50615979dcf445';
        $mail->Password = '084d022f9ec7c1';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        // Configuración de remitente y destinatario
        $mail->setFrom('soporte@demovalle.cl', 'Sistema de Soporte Técnico');
        $mail->addAddress('jeremytortuguita@gmail.com', 'Equipo de Soporte');
        
        // Contenido del email mejorado
        $mail->isHTML(true);
        $mail->Subject = " [$priority] $subject - $user_role";
        
        // Determinar color de prioridad
        $priority_color = '';
        $priority_icon = '';
        switch($priority) {
            case 'Baja':
                $priority_color = '#28a745';
                $priority_icon = '';
                break;
            case 'Media':
                $priority_color = '#ffc107';
                $priority_icon = '';
                break;
            case 'Alta':
                $priority_color = '#fd7e14';
                $priority_icon = '';
                break;
            case 'Crítica':
                $priority_color = '#dc3545';
                $priority_icon = '';
                break;
            default:
                $priority_color = '#6c757d';
                $priority_icon = '';
        }
        
        // Cuerpo del mensaje mejorado con diseño profesional
        $mail->Body = "
        <!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <style>
                body {
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    margin: 0;
                    padding: 20px;
                }
                .email-container {
                    max-width: 600px;
                    margin: 0 auto;
                    background: white;
                    border-radius: 16px;
                    overflow: hidden;
                    box-shadow: 0 15px 50px rgba(0,0,0,0.1);
                }
                .email-header {
                    background: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);
                    color: white;
                    padding: 30px;
                    text-align: center;
                }
                .email-header h1 {
                    margin: 0;
                    font-size: 24px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 10px;
                }
                .email-content {
                    padding: 30px;
                }
                .info-grid {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 20px;
                    margin-bottom: 25px;
                }
                .info-item {
                    background: #f8f9fa;
                    padding: 15px;
                    border-radius: 10px;
                    border-left: 4px solid #4361ee;
                }
                .info-item strong {
                    color: #4361ee;
                    display: block;
                    margin-bottom: 5px;
                }
                .priority-badge {
                    display: inline-block;
                    padding: 5px 12px;
                    border-radius: 20px;
                    color: white;
                    font-weight: bold;
                    font-size: 12px;
                }
                .message-content {
                    background: #f8f9fa;
                    padding: 20px;
                    border-radius: 10px;
                    border: 1px solid #e9ecef;
                    margin-top: 20px;
                }
                .footer {
                    background: #f8f9fa;
                    padding: 20px;
                    text-align: center;
                    color: #6c757d;
                    font-size: 14px;
                    border-top: 1px solid #e9ecef;
                }
                @media (max-width: 600px) {
                    .info-grid {
                        grid-template-columns: 1fr;
                    }
                }
            </style>
        </head>
        <body>
            <div class='email-container'>
                <div class='email-header'>
                    <h1>Nuevo Ticket de Soporte</h1>
                    <p style='margin: 10px 0 0; opacity: 0.9;'>Sistema de Gestion de Licencias</p>
                </div>
                
                <div class='email-content'>
                    <div class='info-grid'>
                        <div class='info-item'>
                            <strong>Nombre</strong>
                            {$name}
                        </div>
                        <div class='info-item'>
                            <strong>Correo</strong>
                            {$email}
                        </div>
                        <div class='info-item'>
                            <strong>Prioridad</strong>
                            <span class='priority-badge' style='background: {$priority_color};'>
                                {$priority_icon} {$priority}
                            </span>
                        </div>
                        <div class='info-item'>
                            <strong>Rol</strong>
                            {$user_role}
                        </div>
                    </div>
                    
                    <div class='info-item'>
                        <strong>Tipo de Solicitud</strong>
                        {$subject}
                    </div>
                    
                    <div style='margin-top: 20px;'>
                        <strong style='color: #4361ee; font-size: 16px;'>Descripcion del Problema:</strong>
                        <div class='message-content'>
                            " . nl2br($message) . "
                        </div>
                    </div>
                </div>
                
                <div class='footer'>
                    <p><strong>Fecha de envio:</strong> " . date('d/m/Y H:i:s') . "</p>
                    <p>Este mensaje fue generado automaticamente por el sistema de soporte</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        // Versión en texto plano mejorada
        $mail->AltBody = "
        ================================
        🎯 NUEVO TICKET DE SOPORTE
        ================================
        
        📋 INFORMACIÓN DEL SOLICITANTE:
        • Nombre: {$name}
        • Correo: {$email}
        • Rol: {$user_role}
        • Prioridad: {$priority_icon} {$priority}
        
        📝 DETALLES DE LA SOLICITUD:
        • Tipo: {$subject}
        • Mensaje: 
        {$message}
        
        🕐 FECHA: " . date('d/m/Y H:i:s') . "
        ⚡ Generado automáticamente por el sistema
        
        ================================
        ";

        if($mail->send()) {
            $_SESSION['message_type'] = 'success';
            $_SESSION['message_title'] = '✅ ¡Solicitud Enviada!';
            $_SESSION['message_content'] = 'Su ticket de soporte ha sido registrado correctamente. Nos pondremos en contacto con usted a la brevedad.';
            $_SESSION['message_details'] = [
                'numero_ticket' => 'T-' . time(),
                'prioridad' => $priority,
                'tiempo_estimado' => $priority === 'Crítica' ? '2 horas' : ($priority === 'Alta' ? '4 horas' : '24 horas')
            ];
        } else {
            $_SESSION['message_type'] = 'error';
            $_SESSION['message_title'] = '❌ Error de Envío';
            $_SESSION['message_content'] = 'No pudimos procesar su solicitud en este momento. Por favor, inténtelo de nuevo más tarde.';
        }
        
    } catch (Exception $e) {
        $_SESSION['message_type'] = 'error';
        $_SESSION['message_title'] = '🚨 Error del Sistema';
        $_SESSION['message_content'] = 'Ocurrió un error inesperado: ' . $e->getMessage();
    }
    
    // Redirigir de vuelta al formulario
    header("Location: ../vista/foro.php");
    exit;
    
} else {
    $_SESSION['message_type'] = 'error';
    $_SESSION['message_title'] = '⚠️ Acceso Denegado';
    $_SESSION['message_content'] = 'Esta página solo puede ser accedida mediante el formulario de contacto.';
    header("Location: ../vista/foro.php");
    exit;
}
?>