<?php
// Archivo: ../controlador/procesar_solicitud.php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../phpmailer/Exception.php';
require '../phpmailer/PHPMailer.php';
require '../phpmailer/SMTP.php';

// Iniciar sesión para mostrar mensajes
session_start();

// Verificar si se envió el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['btnSolicitar'])) {
    
    // Recoger y sanitizar los datos del formulario
    $nombre_software = htmlspecialchars(trim($_POST['nombre_software']));
    $version = htmlspecialchars(trim($_POST['version'] ?? ''));
    $cantidad = intval($_POST['cantidad']);
    $justificacion = htmlspecialchars(trim($_POST['justificacion']));
    $departamento = htmlspecialchars(trim($_POST['departamento']));
    $presupuesto = !empty($_POST['presupuesto']) ? floatval($_POST['presupuesto']) : null;
    
    // Obtener datos del usuario desde la sesión
    $usuario_nombre = $_SESSION['nombre'] ?? 'Usuario';
    $usuario_email = $_SESSION['correo'] ?? 'usuario@dominio.com';
    $usuario_id = $_SESSION['id'] ?? 'N/A';
    
    // Validar campos requeridos
    $errores = [];
    
    if (empty($nombre_software)) {
        $errores[] = "El nombre del software es requerido";
    }
    
    if (empty($cantidad) || $cantidad < 1) {
        $errores[] = "La cantidad de licencias debe ser al menos 1";
    }
    
    if (empty($justificacion)) {
        $errores[] = "La justificación es requerida";
    } elseif (strlen($justificacion) < 20) {
        $errores[] = "La justificación debe tener al menos 20 caracteres";
    }
    
    if (empty($departamento)) {
        $errores[] = "El departamento/área es requerido";
    }
    
    // Si hay errores, mostrar mensaje y redirigir
    if (!empty($errores)) {
        $_SESSION['message_type'] = 'error';
        $_SESSION['message_title'] = '❌ Error en la Solicitud';
        $_SESSION['message_content'] = implode('<br>', $errores);
        header("Location: ../vista/solicitar_licencia.php");
        exit;
    }

    $mail = new PHPMailer(true);

    try {
        // Configuración SMTP (igual que en tu ejemplo)
        $mail->isSMTP();
        $mail->Host = 'sandbox.smtp.mailtrap.io';
        $mail->SMTPAuth = true;
        $mail->Username = '50615979dcf445';
        $mail->Password = '084d022f9ec7c1';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        // Configuración de remitente y destinatarios
        $mail->setFrom('solicitudes@licentix.cl', 'Sistema de Gestión de Licencias - Licentix');
        $mail->addAddress('jeremytortuguita@gmail.com', 'Administrador de Licencias');
        $mail->addReplyTo($usuario_email, $usuario_nombre);
        
        // Asignar prioridad basada en cantidad de licencias
        $prioridad = 'Media';
        if ($cantidad >= 50) {
            $prioridad = 'Crítica';
        } elseif ($cantidad >= 20) {
            $prioridad = 'Alta';
        } elseif ($cantidad <= 5) {
            $prioridad = 'Baja';
        }
        
        // Contenido del email mejorado para solicitud de licencias
        $mail->isHTML(true);
        $mail->Subject = " [SOLICITUD DE LICENCIA] - $prioridad - $nombre_software";
        
        // Determinar color de prioridad
        $priority_color = '';
        switch($prioridad) {
            case 'Baja':
                $priority_color = '#28a745';
                break;
            case 'Media':
                $priority_color = '#ffc107';
                break;
            case 'Alta':
                $priority_color = '#fd7e14';
                break;
            case 'Crítica':
                $priority_color = '#dc3545';
                break;
            default:
                $priority_color = '#6c757d';
        }
        
        // Cuerpo del mensaje mejorado para solicitud de licencias
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
                .info-item.full-width {
                    grid-column: 1 / -1;
                }
                .priority-badge {
                    display: inline-block;
                    padding: 8px 16px;
                    border-radius: 20px;
                    color: white;
                    font-weight: bold;
                    font-size: 14px;
                }
                .presupuesto-box {
                    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
                    color: white;
                    padding: 15px;
                    border-radius: 10px;
                    text-align: center;
                    font-size: 18px;
                    font-weight: bold;
                    margin: 20px 0;
                }
                .justificacion-box {
                    background: #f8f9fa;
                    padding: 20px;
                    border-radius: 10px;
                    border: 1px solid #e9ecef;
                    margin: 20px 0;
                    font-style: italic;
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
                    <h1>NUEVA SOLICITUD DE LICENCIA</h1>
                    <p style='margin: 10px 0 0; opacity: 0.9;'>Sistema de Gestión de Licencias - Licentix</p>
                </div>
                
                <div class='email-content'>
                    <div style='text-align: center; margin-bottom: 25px;'>
                        <span class='priority-badge' style='background: {$priority_color};'>
                            Prioridad: {$prioridad}
                        </span>
                    </div>
                    
                    <div class='info-grid'>
                        <div class='info-item'>
                            <strong>Solicitante</strong>
                            {$usuario_nombre}
                        </div>
                        <div class='info-item'>
                            <strong>Correo</strong>
                            {$usuario_email}
                        </div>
                        <div class='info-item'>
                            <strong>ID Usuario</strong>
                            {$usuario_id}
                        </div>
                        <div class='info-item'>
                            <strong>Departamento/Area</strong>
                            {$departamento}
                        </div>
                        <div class='info-item'>
                            <strong>💻 Software</strong>
                            {$nombre_software}
                        </div>
                        <div class='info-item'>
                            <strong>Version</strong>
                            " . (!empty($version) ? $version : 'No especificada') . "
                        </div>
                        <div class='info-item'>
                            <strong>📊 Cantidad</strong>
                            {$cantidad} licencia(s)
                        </div>
                    </div>
                    
                    " . ($presupuesto ? "
                    <div class='presupuesto-box'>
                        Presupuesto Estimado: $" . number_format($presupuesto, 2, ',', '.') . "
                    </div>
                    " : "") . "
                    
                    <div class='info-item full-width'>
                        <strong>Justificacion de la Solicitud:</strong>
                        <div class='justificacion-box'>
                            " . nl2br($justificacion) . "
                        </div>
                    </div>
                    
                    <div style='background: #e3f2fd; padding: 15px; border-radius: 10px; margin-top: 20px;'>
                        <strong>Proximos Pasos:</strong>
                        <ul style='margin: 10px 0 0 20px;'>
                            <li>El administrador revisará la solicitud</li>
                            <li>Se verificará disponibilidad y presupuesto</li>
                            <li>Recibirás una respuesta en un plazo máximo de 48 horas hábiles</li>
                        </ul>
                    </div>
                </div>
                
                <div class='footer'>
                    <p><strong>📅 Fecha de solicitud:</strong> " . date('d/m/Y H:i:s') . "</p>
                    <p><strong>🔢 Número de solicitud:</strong> SOL-" . date('Ymd') . "-" . rand(1000, 9999) . "</p>
                    <p>Este mensaje fue generado automáticamente por el sistema Licentix</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        // Versión en texto plano
        $mail->AltBody = "
================================
📋 NUEVA SOLICITUD DE LICENCIA
================================

👤 INFORMACIÓN DEL SOLICITANTE:
• Nombre: {$usuario_nombre}
• Correo: {$usuario_email}
• ID Usuario: {$usuario_id}
• Departamento: {$departamento}

💻 DETALLES DE LA SOLICITUD:
• Software: {$nombre_software}
• Versión: " . (!empty($version) ? $version : 'No especificada') . "
• Cantidad: {$cantidad} licencia(s)
• Prioridad: {$prioridad}
" . ($presupuesto ? "• Presupuesto: $" . number_format($presupuesto, 2, ',', '.') . "\n" : "") . "
• Justificación: 
{$justificacion}

📌 PRÓXIMOS PASOS:
- El administrador revisará la solicitud
- Se verificará disponibilidad y presupuesto
- Recibirás respuesta en 48 horas hábiles

📅 FECHA: " . date('d/m/Y H:i:s') . "
🔢 SOLICITUD: SOL-" . date('Ymd') . "-" . rand(1000, 9999) . "

================================
Sistema Licentix - Gestión de Licencias
        ";

        if($mail->send()) {
            $_SESSION['message_type'] = 'success';
            $_SESSION['message_title'] = '¡Solicitud Enviada!';
            $_SESSION['message_content'] = 'Su solicitud de licencia ha sido registrada correctamente. Recibirá una respuesta en un máximo de 48 horas hábiles.';
            $_SESSION['message_details'] = [
                'numero_solicitud' => 'SOL-' . date('Ymd') . '-' . rand(1000, 9999),
                'software' => $nombre_software,
                'cantidad' => $cantidad,
                'prioridad' => $prioridad,
                'tiempo_estimado' => $prioridad === 'Crítica' ? '24 horas' : '48 horas'
            ];
        } else {
            $_SESSION['message_type'] = 'error';
            $_SESSION['message_title'] = 'Error de Envío';
            $_SESSION['message_content'] = 'No pudimos procesar su solicitud en este momento. Por favor, inténtelo de nuevo más tarde.';
        }
        
    } catch (Exception $e) {
        $_SESSION['message_type'] = 'error';
        $_SESSION['message_title'] = 'Error del Sistema';
        $_SESSION['message_content'] = 'Ocurrió un error inesperado. Por favor, contacte al administrador.';
        // Para depuración (puedes comentarlo en producción)
        $_SESSION['debug_error'] = $e->getMessage();
    }
    
    // Redirigir de vuelta al formulario
    header("Location: ../vista/solicitar_licencia.php");
    exit;
    
} else {
    $_SESSION['message_type'] = 'error';
    $_SESSION['message_title'] = 'Acceso Denegado';
    $_SESSION['message_content'] = 'Esta página solo puede ser accedida mediante el formulario de solicitud de licencias.';
    header("Location: ../vista/solicitar_licencia.php");
    exit;
}
?>