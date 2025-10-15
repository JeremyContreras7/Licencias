<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== "ADMIN") {
    header("Location: ../index.php");
    exit();
}

include("../modelo/conexion.php");

if (!isset($_GET['id'])) {
    header("Location: gestionUsuarios.php");
    exit();
}

$id = (int)$_GET['id'];

// --- OBTENER DATOS DEL USUARIO ---
$stmt = $conexion->prepare("
    SELECT u.*, e.nombre_establecimiento 
    FROM usuarios u
    LEFT JOIN establecimientos e ON u.id_establecimiento = e.id_establecimiento
    WHERE u.id_usuario = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$usuario = $res->fetch_assoc();
$stmt->close();

if (!$usuario) {
    header("Location: gestionUsuarios.php?error=notfound");
    exit();
}

// --- ACTUALIZAR USUARIO ---
if (isset($_POST['actualizar'])) {
    $nombre = $conexion->real_escape_string($_POST['nombre']);
    $correo = $conexion->real_escape_string($_POST['correo']);
    $rol = $conexion->real_escape_string($_POST['rol']);
    $tipo_encargado = ($rol === "USUARIO") ? $conexion->real_escape_string($_POST['tipo_encargado']) : null;
    
    // Si es ADMIN, establecimiento es NULL, sino toma el valor del formulario
    $id_establecimiento = ($rol === "ADMIN") ? null : (int)$_POST['id_establecimiento'];
    
    $pass = !empty($_POST['pass']) ? password_hash($_POST['pass'], PASSWORD_DEFAULT) : $usuario['pass'];

    $stmt = $conexion->prepare("
        UPDATE usuarios 
        SET nombre=?, correo=?, pass=?, rol=?, id_establecimiento=?, tipo_encargado=? 
        WHERE id_usuario=?
    ");
    $stmt->bind_param("ssssisi", $nombre, $correo, $pass, $rol, $id_establecimiento, $tipo_encargado, $id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "✅ Usuario actualizado correctamente";
    } else {
        $_SESSION['error'] = "❌ Error al actualizar el usuario";
    }
    $stmt->close();

    header("Location: gestionUsuarios.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuario - Panel Administrativo</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/editarUsuarios.css">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="page-title">
                <div class="icon">
                    <i class="fas fa-user-edit"></i>
                </div>
                <div>
                    <h1>Editar Usuario</h1>
                    <p style="color: rgba(255,255,255,0.8); margin-top: 5px;">Actualiza la información del usuario</p>
                </div>
            </div>
            <a href="gestionUsuarios.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver al listado
            </a>
        </div>

        <!-- Card del formulario -->
        <div class="card">
            <div class="card-header">
                <h2>
                    <i class="fas fa-user-cog"></i>
                    Información del Usuario
                    <span class="user-badge">
                        ID: #<?= $usuario['id_usuario'] ?>
                    </span>
                </h2>
            </div>
            <div class="card-body">
                <form method="POST" id="userForm">
                    <div class="form-grid">
                        <!-- Información básica -->
                        <div class="field">
                            <label for="nombre" class="field-required">
                                <i class="fas fa-user"></i>
                                Nombre completo
                            </label>
                            <input type="text" id="nombre" name="nombre" class="form-control" 
                                   value="<?= htmlspecialchars($usuario['nombre']) ?>" 
                                   placeholder="Ingresa el nombre completo" required>
                        </div>

                        <div class="field">
                            <label for="correo" class="field-required">
                                <i class="fas fa-envelope"></i>
                                Correo electrónico
                            </label>
                            <input type="email" id="correo" name="correo" class="form-control" 
                                   value="<?= htmlspecialchars($usuario['correo']) ?>" 
                                   placeholder="usuario@institucion.edu" required>
                        </div>

                        <!-- Contraseña -->
                        <div class="field">
                            <label for="pass">
                                <i class="fas fa-lock"></i>
                                Nueva contraseña
                            </label>
                            <div class="password-container">
                                <input type="password" id="pass" name="pass" class="form-control" 
                                       placeholder="Dejar vacío para mantener la actual">
                                <button type="button" class="toggle-password" onclick="togglePassword()">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="form-help">
                                Mínimo 8 caracteres. Solo completa si deseas cambiar la contraseña.
                            </div>
                        </div>

                        <!-- Rol y establecimiento -->
                        <div class="field">
                            <label for="rol" class="field-required">
                                <i class="fas fa-user-tag"></i>
                                Rol del usuario
                            </label>
                            <select id="rol" name="rol" class="form-control form-select" required onchange="toggleTipoEncargado()">
                                <option value="ADMIN" <?= $usuario['rol']==='ADMIN'?'selected':'' ?>>👑 Administrador del Sistema</option>
                                <option value="ENCARGADO" <?= $usuario['rol']==='ENCARGADO'?'selected':'' ?>>💻 Encargado Informático</option>
                                <option value="USUARIO" <?= $usuario['rol']==='USUARIO'?'selected':'' ?>>👤 Personal Escolar</option>
                            </select>
                        </div>

                        <div class="field">
                            <label for="id_establecimiento" class="field-required">
                                <i class="fas fa-school"></i>
                                Establecimiento
                            </label>
                            <select id="id_establecimiento" name="id_establecimiento" class="form-control form-select" 
                                    <?= $usuario['rol'] === 'ADMIN' ? 'disabled' : 'required' ?>>
                                <option value="">Seleccionar establecimiento</option>
                                <?php
                                $escuelas = $conexion->query("SELECT id_establecimiento, nombre_establecimiento FROM establecimientos ORDER BY nombre_establecimiento");
                                while ($row = $escuelas->fetch_assoc()) {
                                    $selected = $usuario['id_establecimiento'] == $row['id_establecimiento'] ? 'selected' : '';
                                    echo "<option value='{$row['id_establecimiento']}' $selected>{$row['nombre_establecimiento']}</option>";
                                }
                                ?>
                            </select>
                            <div class="form-help" id="establecimiento-help">
                                <?= $usuario['rol'] === 'ADMIN' ? 
                                    'Los administradores no están asignados a un establecimiento específico' : 
                                    'Selecciona el establecimiento al que pertenece el usuario' ?>
                            </div>
                        </div>

                        <!-- Tipo de encargado (solo para usuarios) -->
                        <div class="field">
                            <label for="tipo_encargado">
                                <i class="fas fa-user-cog"></i>
                                Tipo de Encargado
                            </label>
                            <select id="tipo_encargado" name="tipo_encargado" class="form-control form-select" 
                                    <?= $usuario['rol']!=='USUARIO'?'disabled':'' ?>>
                                <option value="" <?= empty($usuario['tipo_encargado'])?'selected':'' ?>>Seleccionar tipo</option>
                                <option value="INFORMATICA" <?= $usuario['tipo_encargado']==='INFORMATICA'?'selected':'' ?>>💻 Informática</option>
                                <option value="ACADEMICA" <?= $usuario['tipo_encargado']==='ACADEMICA'?'selected':'' ?>>📚 Académica</option>
                                <option value="ADMINISTRATIVA" <?= $usuario['tipo_encargado']==='ADMINISTRATIVA'?'selected':'' ?>>📊 Administrativa</option>
                                <option value="DIRECCION" <?= $usuario['tipo_encargado']==='DIRECCION'?'selected':'' ?>>👨‍💼 Dirección</option>
                                <option value="CONVIVENCIA" <?= $usuario['tipo_encargado']==='CONVIVENCIA'?'selected':'' ?>>🤝 Convivencia Escolar</option>
                            </select>
                            <div class="form-help">
                                Solo aplica para usuarios con rol "Personal Escolar"
                            </div>
                        </div>
                    </div>

                    <!-- Información de auditoría -->
                    <div style="background: var(--gray-50); padding: 20px; border-radius: var(--border-radius-sm); margin-bottom: 20px;">
                        <h4 style="margin-bottom: 10px; color: var(--gray-700); font-size: 0.9rem;">
                            <i class="fas fa-info-circle"></i> Información de registro
                        </h4>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; font-size: 0.85rem; color: var(--gray-600);">
                            <div>
                                <strong>Fecha de registro:</strong><br>
                                <?= date('d/m/Y H:i', strtotime($usuario['fecha_registro'])) ?>
                            </div>
                            <div>
                                <strong>Última actualización:</strong><br>
                                <?= date('d/m/Y H:i', strtotime($usuario['fecha_registro'])) ?>
                            </div>
                            <div>
                                <strong>Establecimiento actual:</strong><br>
                                <?= htmlspecialchars($usuario['nombre_establecimiento'] ?? 'No asignado') ?>
                            </div>
                        </div>
                    </div>

                    <!-- Acciones del formulario -->
                    <div class="form-actions">
                        <a href="gestionUsuarios.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                        <button type="submit" name="actualizar" class="btn btn-primary">
                            <i class="fas fa-save"></i> Guardar cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="../js/editarUsuario.js"></script>
</body>
</html>