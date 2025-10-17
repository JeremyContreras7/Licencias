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
    $pass = password_hash($_POST['pass'], PASSWORD_DEFAULT);
    $rol = $conexion->real_escape_string($_POST['rol']);
    $tipo_encargado = ($_POST['rol'] === "USUARIO") ? $conexion->real_escape_string($_POST['tipo_encargado']) : null;
    
    // Si es ADMIN, no se asigna establecimiento
    if ($_POST['rol'] === "ADMIN") {
        $sql = "INSERT INTO usuarios (nombre, correo, pass, rol, tipo_encargado) 
                VALUES ('$nombre','$correo','$pass','$rol','$tipo_encargado')";
    } else {
        $id_establecimiento = (int)$_POST['id_establecimiento'];
        $sql = "INSERT INTO usuarios (nombre, correo, pass, rol, id_establecimiento, tipo_encargado) 
                VALUES ('$nombre','$correo','$pass','$rol','$id_establecimiento','$tipo_encargado')";
    }

    if ($conexion->query($sql)) {
        $_SESSION['success'] = "✅ Usuario creado correctamente";
    } else {
        $_SESSION['error'] = "❌ Error al crear el usuario: " . $conexion->error;
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
                <form method="POST" action="">
                    <div class="form-grid">
                        <div class="field">
                            <label for="nombre">
                                <i class="fas fa-user"></i>
                                Nombre completo
                            </label>
                            <input type="text" id="nombre" name="nombre" placeholder="Ingresa el nombre completo" required>
                        </div>
                        <div class="field">
                            <label for="correo">
                                <i class="fas fa-envelope"></i>
                                Correo electrónico
                            </label>
                            <input type="email" id="correo" name="correo" placeholder="usuario@institucion.edu" required>
                        </div>
                        <div class="field">
                            <label for="pass">
                                <i class="fas fa-lock"></i>
                                Contraseña
                            </label>
                            <input type="password" id="pass" name="pass" placeholder="Crear una contraseña segura" required>
                        </div>
                        <div class="field">
                            <label for="rol">
                                <i class="fas fa-user-tag"></i>
                                Rol del usuario
                            </label>
                            <select id="rol" name="rol" required onchange="toggleCamposPorRol(this.value)">
                                <option value="">Seleccionar rol</option>
                                <option value="ADMIN">Administrador del Sistema</option>
                                <option value="ENCARGADO">Encargado Informático</option>
                                <option value="USUARIO">Personal Escolar</option>
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
                                    echo "<option value='".$row['id_establecimiento']."'>".htmlspecialchars($row['nombre_establecimiento'])."</option>";
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
                                <option value="INFORMATICA">Informática</option>
                                <option value="ACADEMICA">Académica</option>
                                <option value="ADMINISTRATIVA">Administrativa</option>
                                <option value="DIRECCION">Dirección</option>
                                <option value="CONVIVENCIA">Convivencia Escolar</option>
                            </select>
                            <small class="info-text" id="tipoEncargadoHelp">
                                Solo aplica para usuarios con rol "Personal Escolar"
                            </small>
                        </div>
                    </div>
                    <button type="submit" name="crear" class="btn btn-primary">
                        <i class="fas fa-save"></i> Registrar Usuario
                    </button>
                    <a href="gestionUsuarios.php" class="btn btn-secondary">
                        <i class="fas fa-broom"></i> Limpiar Formulario
                    </a>
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
<script src="../js/gestionUsuarios.js"></script>
</body>
</html>