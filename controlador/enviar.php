<?php
// Archivo: enviar.php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../phpmailer/Exception.php';
require '../phpmailer/PHPMailer.php';
require '../phpmailer/SMTP.php';

// Verificar si se envió el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recoger y sanitizar los datos del formulario
    $name = htmlspecialchars(trim($_POST['name']));
    $email = htmlspecialchars(trim($_POST['email']));
    $subject = htmlspecialchars(trim($_POST['subject']));
    $message = htmlspecialchars(trim($_POST['message']));
    
    // Validar campos requeridos
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        echo "Por favor, complete todos los campos requeridos.";
        exit;
    }
    
    // Validar formato de email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Por favor, ingrese un correo electrónico válido.";
        exit;
    }
    
    $mail = new PHPMailer(true); // Pasar `true` habilita las excepciones

    try {
        $mail->isSMTP();
        $mail->Host = 'sandbox.smtp.mailtrap.io'; // Su servidor SMTP
        $mail->SMTPAuth = true;
        $mail->Username = '50615979dcf445'; // Su usuario de Mailtrap
        $mail->Password = '084d022f9ec7c1'; // Su contraseña de Mailtrap
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        // Configuración de remitente y destinatario
        $mail->setFrom('alexis@demovalle.cl', 'Formulario de Contacto');
        $mail->addAddress('jeremytortuguita@gmail.com', 'Jeremy'); // Destinatario principal
        
        // Contenido del email
        $mail->isHTML(true); // Establecer formato de email a HTML
        $mail->Subject = "Nuevo mensaje de contacto: " . $subject;
        
        // Cuerpo del mensaje con los datos del formulario
        $mail->Body = "
            <h2>Nuevo mensaje de contacto</h2>
            <p><strong>Nombre:</strong> {$name}</p>
            <p><strong>Email:</strong> {$email}</p>
            <p><strong>Asunto:</strong> {$subject}</p>
            <p><strong>Mensaje:</strong></p>
            <p>{$message}</p>
        ";
        
        // Versión en texto plano
        $mail->AltBody = "
            Nuevo mensaje de contacto
            Nombre: {$name}
            Email: {$email}
            Asunto: {$subject}
            Mensaje: {$message}
        ";
        
        if($mail->send()) {
            echo "El mensaje ha sido enviado con éxito";
        } else {
            echo "El mensaje no pudo ser enviado. Inténtelo de nuevo más tarde.";
        }
    } catch (Exception $e) {
        echo "El mensaje no pudo ser enviado. Error de Mailer: {$mail->ErrorInfo}";
    }
} else {
    echo "Acceso no autorizado.";
}
?>