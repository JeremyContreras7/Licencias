<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== "ADMIN") {
    header("Location: ../index.php");
    exit();
}

include("../modelo/conexion.php");

// --- CREAR USUARIO ---
if (isset($_POST['crear'])) {
    $nombre = $conexion->real_escape_string($_POST['nombre']);
    $correo = $conexion->real_escape_string($_POST['correo']);
    $pass = $_POST['pass'];
    $confirm_pass = $_POST['confirm_pass'];
    $rol = $conexion->real_escape_string($_POST['rol']);
    $tipo_encargado = ($_POST['rol'] === "USUARIO") ? $conexion->real_escape_string($_POST['tipo_encargado']) : null;
    
    // Validar contraseña
    if (strlen($pass) < 8) {
        $_SESSION['error'] = "❌ La contraseña debe tener al menos 8 caracteres";
    } elseif (!preg_match('/^(?=.*[A-Za-z])(?=.*\d).{8,}$/', $pass)) {
        $_SESSION['error'] = "❌ La contraseña debe incluir letras y números";
    } elseif ($pass !== $confirm_pass) {
        $_SESSION['error'] = "❌ Las contraseñas no coinciden";
    } else {
        $pass_hash = password_hash($pass, PASSWORD_DEFAULT);
        
        // Si es ADMIN, no se asigna establecimiento
        if ($_POST['rol'] === "ADMIN") {
            $sql = "INSERT INTO usuarios (nombre, correo, pass, rol, tipo_encargado) 
                    VALUES ('$nombre','$correo','$pass_hash','$rol','$tipo_encargado')";
        } else {
            $id_establecimiento = (int)$_POST['id_establecimiento'];
            $sql = "INSERT INTO usuarios (nombre, correo, pass, rol, id_establecimiento, tipo_encargado) 
                    VALUES ('$nombre','$correo','$pass_hash','$rol','$id_establecimiento','$tipo_encargado')";
        }

        if ($conexion->query($sql)) {
            $_SESSION['success'] = "✅ Usuario creado correctamente";
        } else {
            $_SESSION['error'] = "❌ Error al crear el usuario: " . $conexion->error;
        }
    }
    
    header("Location: gestionUsuarios.php");
    exit();
}

// --- ELIMINAR USUARIO ---
if (isset($_GET['eliminar'])) {
    $id = (int)$_GET['eliminar'];
    
    // Verificar que el usuario existe y no se está eliminando a sí mismo
    $current_user_id = $_SESSION['id_usuario'] ?? 0;
    
    if ($id == $current_user_id) {
        $_SESSION['error'] = "❌ No puedes eliminar tu propio usuario";
    } else {
        // Verificar si el usuario existe
        $check_user = $conexion->query("SELECT nombre FROM usuarios WHERE id_usuario = $id");
        if ($check_user->num_rows > 0) {
            $user_data = $check_user->fetch_assoc();
            $user_name = $user_data['nombre'];
            
            // Eliminar usuario
            $result = $conexion->query("DELETE FROM usuarios WHERE id_usuario = $id");
            
            if ($result) {
                $_SESSION['success'] = "🗑️ Usuario <strong>'$user_name'</strong> eliminado correctamente";
            } else {
                $_SESSION['error'] = "❌ Error al eliminar el usuario: " . $conexion->error;
            }
        } else {
            $_SESSION['error'] = "❌ El usuario no existe";
        }
    }
    
    header("Location: gestionUsuarios.php");
    exit();
}

// --- LISTAR USUARIOS ---
$usuarios = $conexion->query("
    SELECT u.*, e.nombre_establecimiento 
    FROM usuarios u
    LEFT JOIN establecimientos e ON u.id_establecimiento = e.id_establecimiento
    ORDER BY u.id_usuario DESC
");

// Contar usuarios por rol para estadísticas
$total_usuarios = $usuarios->num_rows;
$admin_count = $conexion->query("SELECT COUNT(*) as count FROM usuarios WHERE rol = 'ADMIN'")->fetch_assoc()['count'];
$encargado_count = $conexion->query("SELECT COUNT(*) as count FROM usuarios WHERE rol = 'ENCARGADO'")->fetch_assoc()['count'];
$usuario_count = $conexion->query("SELECT COUNT(*) as count FROM usuarios WHERE rol = 'USUARIO'")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios</title>
    <link rel="stylesheet" href="../css/styleGusuario.css">
    <link rel="stylesheet" href="../css/reportes.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="icon" href="../img/logo.png">
    <style>
        /* Estilos adicionales para la validación de contraseña */
        .password-container {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--gray-500);
            cursor: pointer;
            padding: 0.25rem;
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .toggle-password:hover {
            color: var(--primary);
            background: var(--gray-200);
        }

        .password-strength {
            margin-top: 0.75rem;
            padding: 0.75rem;
            background: var(--gray-100);
            border-radius: 8px;
            border: 1px solid var(--gray-300);
        }

        .strength-labels {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
            color: var(--gray-600);
        }

        .strength-bar {
            height: 8px;
            background: var(--gray-300);
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 0.5rem;
        }

        .strength-fill {
            height: 100%;
            width: 0%;
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .strength-weak { background: #dc3545; width: 25%; }
        .strength-medium { background: #ffc107; width: 50%; }
        .strength-strong { background: #28a745; width: 75%; }
        .strength-very-strong { background: #198754; width: 100%; }

        .strength-requirements {
            font-size: 0.8rem;
            color: var(--gray-600);
        }

        .requirement {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.25rem;
        }

        .requirement.valid {
            color: var(--success);
        }

        .requirement.invalid {
            color: var(--gray-500);
        }

        .requirement i {
            font-size: 0.7rem;
        }

        .password-match {
            margin-top: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .match-valid {
            color: var(--success);
        }

        .match-invalid {
            color: var(--danger);
        }

        .field-hint {
            font-size: 0.8rem;
            color: var(--gray-600);
            margin-top: 0.25rem;
        }

        .password-field {
            margin-bottom: 1rem;
        }

        /* Variables CSS */
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --secondary: #6c757d;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
            --info: #17a2b8;
            --light: #f8f9fa;
            --dark: #343a40;
            --white: #ffffff;
            --gray-100: #f8f9fa;
            --gray-200: #e9ecef;
            --gray-300: #dee2e6;
            --gray-400: #ced4da;
            --gray-500: #adb5bd;
            --gray-600: #6c757d;
            --gray-700: #495057;
            --gray-800: #343a40;
            --gray-900: #212529;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header class="header">
            <h1><i class="fas fa-users-cog"></i> Gesti&oacute;n de Usuarios</h1>
            <p>Administra los usuarios del sistema de licencias</p>
            <a href="menu.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Volver al Men&uacute;
            </a>
        </header>

        <!-- Mensajes -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success animate__animated animate__slideInDown">
                <i class="fas fa-check-circle"></i> 
                <div><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger animate__animated animate__slideInDown">
                <i class="fas fa-exclamation-triangle"></i> 
                <div><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            </div>
        <?php endif; ?>

        <!-- Estadísticas -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-number"><?= $total_usuarios ?></div>
                <div class="stat-label">
                    <i class="fas fa-users"></i>
                    Total Usuarios
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $admin_count ?></div>
                <div class="stat-label">
                    <i class="fas fa-crown"></i>
                    Administradores
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $encargado_count ?></div>
                <div class="stat-label">
                    <i class="fas fa-laptop-code"></i>
                    Encargados
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $usuario_count ?></div>
                <div class="stat-label">
                    <i class="fas fa-user"></i>
                    Usuarios
                </div>
            </div>
        </div>

        <!-- Botones de Exportación Mejorados -->
        <div class="export-section">
            <div class="export-header">
                <h3><i class="fas fa-download"></i> Exportar Reportes</h3>
                <p>Genera reportes en diferentes formatos</p>
            </div>
            
            <div class="export-grid">
                <!-- Exportar PDF -->
                <div class="export-card pdf-export">
                    <div class="export-icon">
                        <i class="fas fa-file-pdf"></i>
                    </div>
                    <div class="export-content">
                        <h4>Exportar a PDF</h4>
                        <p>Reporte formal con diseño optimizado para impresión</p>
                        <ul class="export-features">
                            <li><i class="fas fa-check"></i> Formato profesional</li>
                            <li><i class="fas fa-check"></i> Listo para imprimir</li>
                            <li><i class="fas fa-check"></i> Incluye estadísticas</li>
                        </ul>
                    </div>
                    <div class="export-action">
                        <a href="../export/pdf_Usuarios.php" class="btn-export pdf-btn">
                            <i class="fas fa-download"></i>
                            Descargar PDF
                        </a>
                        <small class="export-info">
                            <i class="fas fa-clock"></i> Generación instantánea
                        </small>
                    </div>
                </div>

                <!-- Exportar Excel -->
                <div class="export-card excel-export">
                    <div class="export-icon">
                        <i class="fas fa-file-excel"></i>
                    </div>
                    <div class="export-content">
                        <h4>Exportar a Excel</h4>
                        <p>Datos estructurados para análisis y procesamiento</p>
                        <ul class="export-features">
                            <li><i class="fas fa-check"></i> Formato editable</li>
                            <li><i class="fas fa-check"></i> Ideal para análisis</li>
                            <li><i class="fas fa-check"></i> Filtros incluidos</li>
                        </ul>
                    </div>
                    <div class="export-action">
                        <a href="../export/excel_usuarios.php" class="btn-export excel-btn">
                            <i class="fas fa-download"></i>
                            Descargar Excel
                        </a>
                        <small class="export-info">
                            <i class="fas fa-clock"></i> Generación instantánea
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Formulario -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-user-plus"></i> Registrar Nuevo Usuario</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="" id="userForm">
                    <div class="form-grid">
                        <div class="field">
                            <label for="nombre">
                                <i class="fas fa-user"></i>
                                Nombre completo
                            </label>
                            <input type="text" id="nombre" name="nombre" placeholder="Ingresa el nombre completo" required
                                   value="<?= isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : '' ?>">
                        </div>
                        <div class="field">
                            <label for="correo">
                                <i class="fas fa-envelope"></i>
                                Correo electrónico
                            </label>
                            <input type="email" id="correo" name="correo" placeholder="usuario@institucion.edu" required
                                   value="<?= isset($_POST['correo']) ? htmlspecialchars($_POST['correo']) : '' ?>">
                        </div>
                        
                        <!-- Campo de contraseña con validación -->
                        <div class="field password-field">
                            <label for="pass">
                                <i class="fas fa-lock"></i>
                                Contraseña
                            </label>
                            <div class="password-container">
                                <input type="password" id="pass" name="pass" placeholder="Crear una contraseña segura" required
                                       minlength="8" pattern="^(?=.*[A-Za-z])(?=.*\d).{8,}$">
                                <button type="button" class="toggle-password" onclick="togglePassword('pass')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="field-hint">Mínimo 8 caracteres con letras y números</div>
                            
                            <!-- Indicador de fortaleza de contraseña -->
                            <div class="password-strength">
                                <div class="strength-labels">
                                    <span>Seguridad:</span>
                                    <span id="strengthText">Débil</span>
                                </div>
                                <div class="strength-bar">
                                    <div class="strength-fill" id="strengthFill"></div>
                                </div>
                                <div class="strength-requirements">
                                    <div class="requirement invalid" id="reqLength">
                                        <i class="fas fa-circle"></i>
                                        <span>Mínimo 8 caracteres</span>
                                    </div>
                                    <div class="requirement invalid" id="reqLetter">
                                        <i class="fas fa-circle"></i>
                                        <span>Al menos una letra</span>
                                    </div>
                                    <div class="requirement invalid" id="reqNumber">
                                        <i class="fas fa-circle"></i>
                                        <span>Al menos un número</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Campo de confirmación de contraseña -->
                        <div class="field">
                            <label for="confirm_pass">
                                <i class="fas fa-lock"></i>
                                Confirmar Contraseña
                            </label>
                            <div class="password-container">
                                <input type="password" id="confirm_pass" name="confirm_pass" placeholder="Repetir contraseña" required
                                       minlength="8">
                                <button type="button" class="toggle-password" onclick="togglePassword('confirm_pass')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div id="passwordMatch" class="password-match"></div>
                        </div>

                        <div class="field">
                            <label for="rol">
                                <i class="fas fa-user-tag"></i>
                                Rol del usuario
                            </label>
                            <select id="rol" name="rol" required onchange="toggleCamposPorRol(this.value)">
                                <option value="">Seleccionar rol</option>
                                <option value="ADMIN" <?= (isset($_POST['rol']) && $_POST['rol'] == 'ADMIN') ? 'selected' : '' ?>>Administrador del Sistema</option>
                                <option value="ENCARGADO" <?= (isset($_POST['rol']) && $_POST['rol'] == 'ENCARGADO') ? 'selected' : '' ?>>Encargado Informático</option>
                                <option value="USUARIO" <?= (isset($_POST['rol']) && $_POST['rol'] == 'USUARIO') ? 'selected' : '' ?>>Personal Escolar</option>
                            </select>
                        </div>
                        <div class="field">
                            <label for="id_establecimiento">
                                <i class="fas fa-school"></i>
                                Establecimiento
                            </label>
                            <select id="id_establecimiento" name="id_establecimiento">
                                <option value="">Seleccionar establecimiento</option>
                                <?php
                                $escuelas = $conexion->query("SELECT id_establecimiento, nombre_establecimiento FROM establecimientos ORDER BY nombre_establecimiento");
                                while ($row = $escuelas->fetch_assoc()) {
                                    $selected = (isset($_POST['id_establecimiento']) && $_POST['id_establecimiento'] == $row['id_establecimiento']) ? 'selected' : '';
                                    echo "<option value='".$row['id_establecimiento']."' $selected>".htmlspecialchars($row['nombre_establecimiento'])."</option>";
                                }
                                ?>
                            </select>
                            <small class="info-text" id="establecimientoHelp">
                                Selecciona un rol para ver los requisitos
                            </small>
                        </div>
                        <div class="field">
                            <label for="tipo_encargado">
                                <i class="fas fa-user-cog"></i>
                                Tipo de Encargado
                            </label>
                            <select id="tipo_encargado" name="tipo_encargado" disabled>
                                <option value="">Seleccionar tipo</option>
                                <option value="INFORMATICA" <?= (isset($_POST['tipo_encargado']) && $_POST['tipo_encargado'] == 'INFORMATICA') ? 'selected' : '' ?>>Informática</option>
                                <option value="ACADEMICA" <?= (isset($_POST['tipo_encargado']) && $_POST['tipo_encargado'] == 'ACADEMICA') ? 'selected' : '' ?>>Académica</option>
                                <option value="ADMINISTRATIVA" <?= (isset($_POST['tipo_encargado']) && $_POST['tipo_encargado'] == 'ADMINISTRATIVA') ? 'selected' : '' ?>>Administrativa</option>
                                <option value="DIRECCION" <?= (isset($_POST['tipo_encargado']) && $_POST['tipo_encargado'] == 'DIRECCION') ? 'selected' : '' ?>>Dirección</option>
                                <option value="CONVIVENCIA" <?= (isset($_POST['tipo_encargado']) && $_POST['tipo_encargado'] == 'CONVIVENCIA') ? 'selected' : '' ?>>Convivencia Escolar</option>
                            </select>
                            <small class="info-text" id="tipoEncargadoHelp">
                                Solo aplica para usuarios con rol "Personal Escolar"
                            </small>
                        </div>
                    </div>
                    <button type="submit" name="crear" class="btn btn-primary" id="submitBtn">
                        <i class="fas fa-save"></i> Registrar Usuario
                    </button>
                    <button type="reset" class="btn btn-secondary" onclick="resetPasswordValidation()">
                        <i class="fas fa-broom"></i> Limpiar Formulario
                    </button>
                </form>
            </div>
        </div>

        <!-- Tabla de usuarios -->
        <div class="table-container">
            <div class="card-header">
                <h3><i class="fas fa-list"></i> Usuarios Registrados</h3>
            </div>
            <div style="overflow-x: auto;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Correo</th>
                            <th>Rol</th>
                            <th>Establecimiento</th>
                            <th>Tipo Encargado</th>
                            <th>Fecha Registro</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($usuarios->num_rows > 0): ?>
                            <?php 
                            $usuarios->data_seek(0);
                            while($row = $usuarios->fetch_assoc()): 
                                $is_current_user = ($row['id_usuario'] == ($_SESSION['id_usuario'] ?? 0));
                                
                                // Determinar clase del badge
                                if ($row['rol'] === 'ADMIN') {
                                    $badge_class = 'badge-admin';
                                } elseif ($row['rol'] === 'ENCARGADO') {
                                    $badge_class = 'badge-encargado';
                                } else {
                                    $badge_class = 'badge-usuario';
                                }
                            ?>
                            <tr>
                                <td><strong>#<?= $row['id_usuario'] ?></strong></td>
                                <td>
                                    <i class="fas fa-user" style="color: var(--primary); margin-right: 10px;"></i>
                                    <?= htmlspecialchars($row['nombre']) ?>
                                    <?php if ($is_current_user): ?>
                                        <span class="badge badge-current" style="margin-left: 10px;">
                                            <i class="fas fa-user-check"></i> Tú
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <i class="fas fa-envelope" style="color: var(--secondary); margin-right: 10px;"></i>
                                    <?= htmlspecialchars($row['correo']) ?>
                                </td>
                                <td>
                                    <span class="badge <?= $badge_class ?>">
                                        <i class="fas <?= $row['rol'] === 'ADMIN' ? 'fa-crown' : ($row['rol'] === 'ENCARGADO' ? 'fa-laptop-code' : 'fa-user') ?>"></i>
                                        <?= $row['rol'] ?>
                                    </span>
                                </td>
                                <td>
                                    <i class="fas fa-school" style="color: var(--success); margin-right: 10px;"></i>
                                    <?= htmlspecialchars($row['nombre_establecimiento'] ?? 'Sin asignar') ?>
                                </td>
                                <td>
                                    <?php if ($row['tipo_encargado']): ?>
                                        <span class="badge" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); color: white;">
                                            <?= $row['tipo_encargado'] ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: var(--gray-500);">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small style="color: var(--gray-600);">
                                        <?= date('d/m/Y', strtotime($row['fecha_registro'])) ?>
                                    </small>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                        <a href="editar_usuario.php?id=<?= $row['id_usuario'] ?>" class="action-btn btn-edit">
                                            <i class="fas fa-edit"></i> Editar
                                        </a>
                                        <?php if (!$is_current_user): ?>
                                            <button class="action-btn btn-delete" 
                                                    onclick="showDeleteModal(<?= $row['id_usuario'] ?>, '<?= addslashes(htmlspecialchars($row['nombre'])) ?>', '<?= $row['rol'] ?>')">
                                                <i class="fas fa-trash"></i> Eliminar
                                            </button>
                                        <?php else: ?>
                                            <span class="action-btn btn-disabled">
                                                <i class="fas fa-trash"></i> Eliminar
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="empty-state">
                                    <i class="fas fa-users-slash"></i>
                                    <p>No hay usuarios registrados</p>
                                    <p class="subtext">Comienza agregando el primer usuario utilizando el formulario superior.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmación de Eliminación -->
    <div id="deleteModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3 class="modal-title">Confirmar Eliminación</h3>
            <p class="modal-message" id="modalMessage">
                ¿Estás seguro de que deseas eliminar este usuario?
            </p>
            <div class="modal-user-info" id="userInfo">
                <!-- Información del usuario se insertará aquí -->
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-cancel" onclick="hideDeleteModal()">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <a href="#" class="btn btn-confirm-delete" id="confirmDeleteBtn">
                    <i class="fas fa-trash"></i> Sí, Eliminar Usuario
                </a>
            </div>
        </div>
    </div>

    <script>
        // Validación de contraseña en tiempo real
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('pass');
            const confirmPasswordInput = document.getElementById('confirm_pass');
            const strengthFill = document.getElementById('strengthFill');
            const strengthText = document.getElementById('strengthText');
            const passwordMatch = document.getElementById('passwordMatch');
            const submitBtn = document.getElementById('submitBtn');

            // Validar fortaleza de contraseña
            if (passwordInput) {
                passwordInput.addEventListener('input', checkPasswordStrength);
            }

            // Validar coincidencia de contraseñas
            if (confirmPasswordInput) {
                confirmPasswordInput.addEventListener('input', checkPasswordMatch);
            }

            // Validar formulario antes de enviar
            const form = document.getElementById('userForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    if (!validatePassword()) {
                        e.preventDefault();
                        showNotification('Por favor, corrige los errores en la contraseña.', 'error');
                    }
                });
            }
        });

        // Verificar fortaleza de la contraseña
        function checkPasswordStrength() {
            const password = this.value;
            let strength = 0;
            
            // Actualizar requisitos visuales
            updateRequirements(password);
            
            // Longitud mínima
            if (password.length >= 8) strength += 1;
            
            // Contiene letras
            if (/[a-zA-Z]/.test(password)) strength += 1;
            
            // Contiene números
            if (/[0-9]/.test(password)) strength += 1;
            
            // Contiene caracteres especiales
            if (/[^a-zA-Z0-9]/.test(password)) strength += 1;
            
            // Longitud mayor a 12
            if (password.length >= 12) strength += 1;

            // Actualizar barra y texto
            updateStrengthIndicator(strength);
        }

        // Actualizar indicadores de requisitos
        function updateRequirements(password) {
            const reqLength = document.getElementById('reqLength');
            const reqLetter = document.getElementById('reqLetter');
            const reqNumber = document.getElementById('reqNumber');

            // Longitud
            if (password.length >= 8) {
                reqLength.classList.add('valid');
                reqLength.classList.remove('invalid');
                reqLength.innerHTML = '<i class="fas fa-check-circle"></i><span>Mínimo 8 caracteres</span>';
            } else {
                reqLength.classList.add('invalid');
                reqLength.classList.remove('valid');
                reqLength.innerHTML = '<i class="fas fa-circle"></i><span>Mínimo 8 caracteres</span>';
            }

            // Letras
            if (/[a-zA-Z]/.test(password)) {
                reqLetter.classList.add('valid');
                reqLetter.classList.remove('invalid');
                reqLetter.innerHTML = '<i class="fas fa-check-circle"></i><span>Al menos una letra</span>';
            } else {
                reqLetter.classList.add('invalid');
                reqLetter.classList.remove('valid');
                reqLetter.innerHTML = '<i class="fas fa-circle"></i><span>Al menos una letra</span>';
            }

            // Números
            if (/[0-9]/.test(password)) {
                reqNumber.classList.add('valid');
                reqNumber.classList.remove('invalid');
                reqNumber.innerHTML = '<i class="fas fa-check-circle"></i><span>Al menos un número</span>';
            } else {
                reqNumber.classList.add('invalid');
                reqNumber.classList.remove('valid');
                reqNumber.innerHTML = '<i class="fas fa-circle"></i><span>Al menos un número</span>';
            }
        }

        // Actualizar indicador visual de fortaleza
        function updateStrengthIndicator(strength) {
            const strengthFill = document.getElementById('strengthFill');
            const strengthText = document.getElementById('strengthText');
            
            strengthFill.className = 'strength-fill';
            
            switch(strength) {
                case 0:
                case 1:
                    strengthFill.classList.add('strength-weak');
                    strengthText.textContent = 'Muy Débil';
                    strengthText.style.color = '#dc3545';
                    break;
                case 2:
                    strengthFill.classList.add('strength-weak');
                    strengthText.textContent = 'Débil';
                    strengthText.style.color = '#dc3545';
                    break;
                case 3:
                    strengthFill.classList.add('strength-medium');
                    strengthText.textContent = 'Moderada';
                    strengthText.style.color = '#ffc107';
                    break;
                case 4:
                    strengthFill.classList.add('strength-strong');
                    strengthText.textContent = 'Fuerte';
                    strengthText.style.color = '#28a745';
                    break;
                case 5:
                    strengthFill.classList.add('strength-very-strong');
                    strengthText.textContent = 'Muy Fuerte';
                    strengthText.style.color = '#198754';
                    break;
            }
        }

        // Verificar coincidencia de contraseñas
        function checkPasswordMatch() {
            const password = document.getElementById('pass').value;
            const confirmPassword = this.value;
            const matchIndicator = document.getElementById('passwordMatch');

            if (confirmPassword === '') {
                matchIndicator.textContent = '';
                matchIndicator.className = 'password-match';
                return;
            }

            if (password === confirmPassword) {
                matchIndicator.textContent = '✓ Las contraseñas coinciden';
                matchIndicator.className = 'password-match match-valid';
            } else {
                matchIndicator.textContent = '✗ Las contraseñas no coinciden';
                matchIndicator.className = 'password-match match-invalid';
            }
        }

        // Alternar visibilidad de contraseña
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = field.nextElementSibling.querySelector('i');
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Validar contraseña antes del envío
        function validatePassword() {
            const password = document.getElementById('pass').value;
            const confirmPassword = document.getElementById('confirm_pass').value;
            const passwordRegex = /^(?=.*[A-Za-z])(?=.*\d).{8,}$/;

            if (!passwordRegex.test(password)) {
                showNotification('La contraseña debe tener al menos 8 caracteres con letras y números.', 'error');
                return false;
            }

            if (password !== confirmPassword) {
                showNotification('Las contraseñas no coinciden.', 'error');
                return false;
            }

            return true;
        }

        // Mostrar notificación
        function showNotification(message, type = 'info') {
            // Crear elemento de notificación
            const notification = document.createElement('div');
            notification.className = `alert alert-${type} animate__animated animate__slideInDown`;
            notification.style.position = 'fixed';
            notification.style.top = '20px';
            notification.style.right = '20px';
            notification.style.zIndex = '1000';
            notification.style.minWidth = '300px';
            
            notification.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : 'info-circle'}"></i> 
                <div>${message}</div>
            `;
            
            document.body.appendChild(notification);
            
            // Auto-remover después de 5 segundos
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 5000);
        }

        // Resetear validación de contraseña
        function resetPasswordValidation() {
            const strengthFill = document.getElementById('strengthFill');
            const strengthText = document.getElementById('strengthText');
            const passwordMatch = document.getElementById('passwordMatch');
            const requirements = document.querySelectorAll('.requirement');

            // Resetear indicadores
            if (strengthFill && strengthText) {
                strengthFill.className = 'strength-fill';
                strengthText.textContent = 'Débil';
                strengthText.style.color = '';
            }

            if (passwordMatch) {
                passwordMatch.textContent = '';
                passwordMatch.className = 'password-match';
            }

            // Resetear requisitos
            requirements.forEach(req => {
                req.classList.add('invalid');
                req.classList.remove('valid');
                req.innerHTML = req.innerHTML.replace('fa-check-circle', 'fa-circle');
            });
        }

        // Funciones para mostrar/ocultar campos según el rol
        function toggleCamposPorRol(rol) {
            const establecimientoField = document.getElementById('id_establecimiento');
            const tipoEncargadoField = document.getElementById('tipo_encargado');
            const establecimientoHelp = document.getElementById('establecimientoHelp');
            const tipoEncargadoHelp = document.getElementById('tipoEncargadoHelp');

            switch(rol) {
                case 'ADMIN':
                    establecimientoField.disabled = true;
                    tipoEncargadoField.disabled = true;
                    establecimientoHelp.textContent = 'Los administradores no están asignados a establecimientos';
                    tipoEncargadoHelp.textContent = 'No aplica para administradores';
                    break;
                case 'ENCARGADO':
                    establecimientoField.disabled = false;
                    establecimientoField.required = true;
                    tipoEncargadoField.disabled = true;
                    establecimientoHelp.textContent = 'Selecciona el establecimiento que gestionará este encargado';
                    tipoEncargadoHelp.textContent = 'No aplica para encargados informáticos';
                    break;
                case 'USUARIO':
                    establecimientoField.disabled = false;
                    establecimientoField.required = true;
                    tipoEncargadoField.disabled = false;
                    tipoEncargadoField.required = true;
                    establecimientoHelp.textContent = 'Selecciona el establecimiento del funcionario';
                    tipoEncargadoHelp.textContent = 'Define el área de trabajo del funcionario';
                    break;
                default:
                    establecimientoField.disabled = true;
                    tipoEncargadoField.disabled = true;
                    establecimientoHelp.textContent = 'Selecciona un rol para ver los requisitos';
                    tipoEncargadoHelp.textContent = 'Solo aplica para usuarios con rol "Personal Escolar"';
            }
        }

        // Las funciones existentes del modal
        function showDeleteModal(id, nombre, rol) {
            const modal = document.getElementById('deleteModal');
            const userInfo = document.getElementById('userInfo');
            const confirmBtn = document.getElementById('confirmDeleteBtn');
            
            userInfo.innerHTML = `
                <div class="user-detail">
                    <strong>Nombre:</strong> ${nombre}
                </div>
                <div class="user-detail">
                    <strong>Rol:</strong> ${rol}
                </div>
                <div class="user-detail">
                    <strong>ID:</strong> #${id}
                </div>
            `;
            
            confirmBtn.href = `gestionUsuarios.php?eliminar=${id}`;
            modal.style.display = 'flex';
        }

        function hideDeleteModal() {
            const modal = document.getElementById('deleteModal');
            modal.style.display = 'none';
        }

        // Cerrar modal al hacer clic fuera
        window.onclick = function(event) {
            const modal = document.getElementById('deleteModal');
            if (event.target === modal) {
                hideDeleteModal();
            }
        }

        // Inicializar campos según el rol seleccionado
        document.addEventListener('DOMContentLoaded', function() {
            const rolSelect = document.getElementById('rol');
            if (rolSelect.value) {
                toggleCamposPorRol(rolSelect.value);
            }
        });
    </script>
    <script src="../js/gestionUsuarios.js"></script>
</body>
</html>