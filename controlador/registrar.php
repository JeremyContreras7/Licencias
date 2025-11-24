<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== "ENCARGADO") {
    header("Location: ../index.php");
    exit();
}

include("../modelo/conexion.php");

$id_establecimiento = $_SESSION['id_establecimiento'];
$nombre_establecimiento = "";
$mensaje = '';
$tipo_mensaje = '';

// Obtener el nombre del establecimiento
$stmt = $conexion->prepare("SELECT nombre_establecimiento FROM establecimientos WHERE id_establecimiento = ?");
if ($stmt) {
    $stmt->bind_param("i", $id_establecimiento);
    $stmt->execute();
    $stmt->bind_result($nombre_establecimiento);
    $stmt->fetch();
    $stmt->close();
}

// Procesar registro
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btnregistrar'])) {
    $nombre = trim($_POST['nombre']);
    $correo = trim($_POST['correo']);
    $pass = $_POST['pass'];
    $confirm_pass = $_POST['confirm_pass'];
    $tipo_funcionario = $_POST['tipo_funcionario'];

    // Validaciones
    $errores = [];

    // Validar nombre
    if (empty($nombre) || strlen($nombre) < 2) {
        $errores[] = "El nombre debe tener al menos 2 caracteres";
    }

    // Validar correo
    if (empty($correo) || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El correo electrónico no es válido";
    }

    // Validar contraseña
    if (empty($pass) || strlen($pass) < 8) {
        $errores[] = "La contraseña debe tener al menos 8 caracteres";
    } elseif (!preg_match('/^(?=.*[A-Za-z])(?=.*\d).{8,}$/', $pass)) {
        $errores[] = "La contraseña debe incluir letras y números";
    }

    // Validar confirmación de contraseña
    if ($pass !== $confirm_pass) {
        $errores[] = "Las contraseñas no coinciden";
    }

    // Validar tipo de funcionario
    if (empty($tipo_funcionario)) {
        $errores[] = "Debe seleccionar un tipo de funcionario";
    }

    // Si no hay errores, proceder con el registro
    if (empty($errores)) {
        // Verificar si el correo ya existe en este establecimiento
        $check_stmt = $conexion->prepare("SELECT id_usuario FROM usuarios WHERE correo = ? AND id_establecimiento = ?");
        if ($check_stmt) {
            $check_stmt->bind_param("si", $correo, $id_establecimiento);
            $check_stmt->execute();
            $check_stmt->store_result();

            if ($check_stmt->num_rows > 0) {
                $errores[] = "El correo electrónico ya está registrado en este establecimiento";
            }
            $check_stmt->close();
        }

        // Si todavía no hay errores, insertar el usuario
        if (empty($errores)) {
            $pass_hash = password_hash($pass, PASSWORD_DEFAULT);
            $rol = "USUARIO";

            $insert_stmt = $conexion->prepare("INSERT INTO usuarios 
                (nombre, correo, pass, rol, id_establecimiento, tipo_encargado) 
                VALUES (?, ?, ?, ?, ?, ?)");

            if ($insert_stmt) {
                $insert_stmt->bind_param("ssssis", $nombre, $correo, $pass_hash, $rol, $id_establecimiento, $tipo_funcionario);

                if ($insert_stmt->execute()) {
                    $mensaje = "✅ Funcionario registrado exitosamente. Puede iniciar sesión con sus credenciales.";
                    $tipo_mensaje = "success";
                    
                    // Limpiar el formulario
                    $_POST = array();
                } else {
                    $errores[] = "Error al registrar en la base de datos: " . $insert_stmt->error;
                }
                $insert_stmt->close();
            } else {
                $errores[] = "Error al preparar la consulta: " . $conexion->error;
            }
        }
    }

    // Si hay errores, mostrar el primero
    if (!empty($errores)) {
        $mensaje = "❌ " . $errores[0];
        $tipo_mensaje = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Funcionario - <?= htmlspecialchars($nombre_establecimiento) ?></title>
    <link rel="stylesheet" href="../css/styleregistro.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" href="../img/logo.png">
</head>
<body>
    <div class="container">
        <div class="registro-card">
            <!-- Header -->
            <div class="card-header">
                <div class="header-content">
                    <img src="../img/logo.png" alt="Logo" class="logo">
                    <div class="header-text">
                        <h1><i class="fas fa-user-plus"></i> Registrar Nuevo Funcionario</h1>
                        <p class="establecimiento"><?= htmlspecialchars($nombre_establecimiento) ?></p>
                    </div>
                </div>
            </div>

            <!-- Mensajes -->
            <?php if (!empty($mensaje)): ?>
                <div class="alert alert-<?= $tipo_mensaje ?>">
                    <div class="alert-content">
                        <i class="fas <?= $tipo_mensaje === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle' ?>"></i>
                        <span><?= $mensaje ?></span>
                    </div>
                    <button class="alert-close" onclick="this.parentElement.style.display='none'">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            <?php endif; ?>

            <!-- Formulario -->
            <form class="registro-form" method="POST" action="" id="registroForm">
                <div class="form-grid">
                    <!-- Información Personal -->
                    <div class="form-section">
                        <h3><i class="fas fa-user"></i> Información Personal</h3>
                        
                        <div class="field-group">
                            <div class="field">
                                <label for="nombre">
                                    <i class="fas fa-user"></i> Nombre Completo *
                                </label>
                                <input type="text" id="nombre" name="nombre" 
                                       value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>" 
                                       placeholder="Ej: María González López" 
                                       required 
                                       minlength="2"
                                       maxlength="100">
                                <div class="field-hint">Mínimo 2 caracteres, solo letras y espacios</div>
                            </div>

                            <div class="field">
                                <label for="correo">
                                    <i class="fas fa-envelope"></i> Correo Institucional *
                                </label>
                                <input type="email" id="correo" name="correo" 
                                       value="<?= htmlspecialchars($_POST['correo'] ?? '') ?>" 
                                       placeholder="funcionario@establecimiento.cl" 
                                       required>
                                <div class="field-hint">Será usado para iniciar sesión</div>
                            </div>
                        </div>
                    </div>

                    <!-- Información Laboral -->
                    <div class="form-section">
                        <h3><i class="fas fa-briefcase"></i> Información Laboral</h3>
                        
                        <div class="field-group">
                            <div class="field">
                                <label for="id_establecimiento">
                                    <i class="fas fa-school"></i> Establecimiento
                                </label>
                                <div class="readonly-field">
                                    <?= htmlspecialchars($nombre_establecimiento) ?>
                                </div>
                                <input type="hidden" name="id_establecimiento" value="<?= $id_establecimiento ?>">
                            </div>

                            <div class="field">
                                <label for="tipo_funcionario">
                                    <i class="fas fa-user-tag"></i> Tipo de Funcionario *
                                </label>
                                <select id="tipo_funcionario" name="tipo_funcionario" required>
                                    <option value="">Seleccione un tipo...</option>
                                    <option value="ACADEMICA" <?= ($_POST['tipo_funcionario'] ?? '') === 'ACADEMICA' ? 'selected' : '' ?>>
                                        👨‍🏫 Personal Académico
                                    </option>
                                    <option value="ADMINISTRATIVA" <?= ($_POST['tipo_funcionario'] ?? '') === 'ADMINISTRATIVA' ? 'selected' : '' ?>>
                                        📊 Personal Administrativo
                                    </option>
                                    <option value="DIRECCION" <?= ($_POST['tipo_funcionario'] ?? '') === 'DIRECCION' ? 'selected' : '' ?>>
                                        👨‍💼 Dirección y Gestión
                                    </option>
                                    <option value="CONVIVENCIA" <?= ($_POST['tipo_funcionario'] ?? '') === 'CONVIVENCIA' ? 'selected' : '' ?>>
                                        🤝 Convivencia Escolar
                                    </option>
                                    <option value="APOYO" <?= ($_POST['tipo_funcionario'] ?? '') === 'APOYO' ? 'selected' : '' ?>>
                                        🛠️ Apoyo Técnico-Pedagógico
                                    </option>
                                    <option value="AUXILIAR" <?= ($_POST['tipo_funcionario'] ?? '') === 'AUXILIAR' ? 'selected' : '' ?>>
                                        🧹 Personal Auxiliar
                                    </option>
                                </select>
                                <div class="field-hint">Define el área de trabajo del funcionario</div>
                            </div>
                        </div>
                    </div>

                    <!-- Seguridad -->
                    <div class="form-section">
                        <h3><i class="fas fa-shield-alt"></i> Seguridad</h3>
                        
                        <div class="field-group">
                            <div class="field">
                                <label for="pass">
                                    <i class="fas fa-lock"></i> Contraseña *
                                </label>
                                <div class="password-container">
                                    <input type="password" id="pass" name="pass" 
                                           placeholder="Mínimo 8 caracteres con letras y números" 
                                           required 
                                           minlength="8"
                                           pattern="^(?=.*[A-Za-z])(?=.*\d).{8,}$">
                                    <button type="button" class="toggle-password" onclick="togglePassword('pass')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="field-hint">Debe incluir letras y números</div>
                                
                                <!-- Indicador de fortaleza de contraseña -->
                                <div class="password-strength">
                                    <div class="strength-labels">
                                        <span>Seguridad:</span>
                                        <span id="strengthText">Débil</span>
                                    </div>
                                    <div class="strength-bar">
                                        <div class="strength-fill" id="strengthFill"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="field">
                                <label for="confirm_pass">
                                    <i class="fas fa-lock"></i> Confirmar Contraseña *
                                </label>
                                <div class="password-container">
                                    <input type="password" id="confirm_pass" name="confirm_pass" 
                                           placeholder="Repita la contraseña" 
                                           required 
                                           minlength="8">
                                    <button type="button" class="toggle-password" onclick="togglePassword('confirm_pass')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="field-hint">Ambas contraseñas deben coincidir</div>
                                <div id="passwordMatch" class="match-indicator"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botones de acción -->
                <div class="form-actions">
                    <button type="submit" name="btnregistrar" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Registrar Funcionario
                    </button>
                    <a href="../vista/menu_informatico.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver al Panel
                    </a>
                    <button type="reset" class="btn btn-outline">
                        <i class="fas fa-undo"></i> Limpiar Formulario
                    </button>
                </div>
            </form>

            <!-- Footer informativo -->
            <div class="card-footer">
                <div class="footer-info">
                    <i class="fas fa-info-circle"></i>
                    <div>
                        <strong>Información importante:</strong>
                        <ul>
                            <li>El funcionario recibirá el rol <strong>USUARIO</strong> automáticamente</li>
                            <li>Podrá acceder al sistema con su correo y contraseña</li>
                            <li>Los permisos están limitados según su tipo de funcionario</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/registrar.js"></script>
</body>
</html>