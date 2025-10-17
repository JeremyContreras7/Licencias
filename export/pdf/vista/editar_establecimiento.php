<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'ADMIN') {
    header("Location: ../index.php");
    exit();
}

include("../modelo/conexion.php");

// Verificar si se proporcionó un ID válido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: gestionEstablecimientos.php?error=invalid_id");
    exit();
}

$id_establecimiento = intval($_GET['id']);

// Obtener datos del establecimiento
$stmt = $conexion->prepare("SELECT * FROM establecimientos WHERE id_establecimiento = ?");
$stmt->bind_param("i", $id_establecimiento);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows === 0) {
    header("Location: gestionEstablecimientos.php?error=not_found");
    exit();
}

$establecimiento = $resultado->fetch_assoc();
$stmt->close();

// --- ACTUALIZAR ESTABLECIMIENTO ---
if (isset($_POST['actualizar'])) {
    $nombre = trim($_POST['nombre']);
    $correo = trim($_POST['correo']);
    $telefono = trim($_POST['telefono']);
    $tipo = trim($_POST['tipo_escuela']);

    // Validación básica
    if (!empty($nombre) && !empty($tipo)) {
        $stmt = $conexion->prepare("
            UPDATE establecimientos 
            SET nombre_establecimiento = ?, correo = ?, telefono = ?, tipo_escuela = ?
            WHERE id_establecimiento = ?
        ");
        if ($stmt) {
            $stmt->bind_param("ssssi", $nombre, $correo, $telefono, $tipo, $id_establecimiento);
            
            if ($stmt->execute()) {
                header("Location: gestionEstablecimientos.php?msg=updated");
                exit();
            } else {
                $error_message = "Error al actualizar el establecimiento: " . $stmt->error;
            }
            $stmt->close();
        }
    } else {
        $error_message = "Por favor complete todos los campos requeridos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Establecimiento - <?= htmlspecialchars($establecimiento['nombre_establecimiento']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="../css/style_editarEstablecimiento.css">
</head>
<body>
    <div class="container container-main py-4">

        <!-- Encabezado -->
        <div class="header-main animate__animated animate__fadeIn">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="fas fa-edit me-2"></i>Editar Establecimiento</h2>
                    <p class="mb-0">Modifica la información del establecimiento educativo</p>
                </div>
                <a href="gestionEstablecimientos.php" class="btn back-btn">
                    <i class="fas fa-arrow-left me-1"></i> Volver a Gestión
                </a>
            </div>
        </div>

        <!-- Mensajes de error -->
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-custom animate__animated animate__slideInDown mb-4">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Error:</strong> <?= $error_message ?>
            </div>
        <?php endif; ?>

        <!-- Información del establecimiento -->
        <div class="card card-custom mb-4 animate__animated animate__fadeInUp">
            <div class="card-header-custom">
                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Información Actual</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <strong>ID del Establecimiento:</strong>
                        <span class="info-badge ms-2">#<?= $establecimiento['id_establecimiento'] ?></span>
                    </div>
                    <div class="col-md-6">
                        <strong>Tipo Actual:</strong>
                        <?php
                        $badge_class = match($establecimiento['tipo_escuela']) {
                            'Urbano' => 'badge-urban',
                            'Polidocente' => 'badge-polidocente',
                            'Unidocente' => 'badge-unidocente',
                            'Jardín Infantil' => 'badge-jardin',
                            default => 'badge-other'
                        };
                        ?>
                        <span class="current-type-badge <?= $badge_class ?> ms-2">
                            <?= htmlspecialchars($establecimiento['tipo_escuela']) ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Formulario de edición -->
        <div class="card card-custom animate__animated animate__fadeInUp">
            <div class="card-header-custom">
                <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Modificar Información</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Nombre del Establecimiento <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nombre" 
                                   value="<?= htmlspecialchars($establecimiento['nombre_establecimiento']) ?>" 
                                   placeholder="Ej: Liceo Nacional de Ovalle" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tipo de Escuela <span class="text-danger">*</span></label>
                            <select name="tipo_escuela" class="form-select" required>
                                <option disabled selected value="">Tipo de escuela</option>
                                <option value="Urbano" <?= $establecimiento['tipo_escuela'] == 'Urbano' ? 'selected' : '' ?>>Urbano</option>
                                <option value="Polidocente" <?= $establecimiento['tipo_escuela'] == 'Polidocente' ? 'selected' : '' ?>>Rural Polidocente</option>
                                <option value="Unidocente" <?= $establecimiento['tipo_escuela'] == 'Unidocente' ? 'selected' : '' ?>>Rural Unidocente</option>
                                <option value="Jardín Infantil" <?= $establecimiento['tipo_escuela'] == 'Jardín Infantil' ? 'selected' : '' ?>>Jardín Infantil</option>
                                <option value="Otro" <?= $establecimiento['tipo_escuela'] == 'Otro' ? 'selected' : '' ?>>Otro</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Correo Electrónico</label>
                            <input type="email" class="form-control" name="correo" 
                                   value="<?= htmlspecialchars($establecimiento['correo'] ?? '') ?>" 
                                   placeholder="contacto@establecimiento.cl">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Teléfono de Contacto</label>
                            <input type="text" class="form-control" name="telefono" 
                                   value="<?= htmlspecialchars($establecimiento['telefono'] ?? '') ?>" 
                                   placeholder="+56 9 1234 5678">
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Los campos marcados con <span class="text-danger">*</span> son obligatorios
                                    </small>
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="gestionEstablecimientos.php" class="btn btn-secondary-custom">
                                        <i class="fas fa-times me-1"></i>Cancelar
                                    </a>
                                    <button type="submit" name="actualizar" class="btn btn-primary-custom">
                                        <i class="fas fa-save me-1"></i>Guardar Cambios
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Información adicional -->
        <div class="card card-custom animate__animated animate__fadeInUp">
            <div class="card-header-custom" style="background: linear-gradient(135deg, #6c757d, #5a6268);">
                <h5 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Información de Seguridad</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-4">
                        <div class="mb-2">
                            <i class="fas fa-user-shield fa-2x text-primary"></i>
                        </div>
                        <h6>Usuario Administrador</h6>
                        <p class="text-muted small">Sesión activa como: <?= htmlspecialchars($_SESSION['nombre'] ?? 'Administrador') ?></p>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-2">
                            <i class="fas fa-clock fa-2x text-warning"></i>
                        </div>
                        <h6>Última Modificación</h6>
                        <p class="text-muted small"><?= date('d/m/Y H:i:s') ?></p>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-2">
                            <i class="fas fa-database fa-2x text-success"></i>
                        </div>
                        <h6>Estado de la Base</h6>
                        <p class="text-muted small">Conexión activa</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
        <script src="js/editarEstablecimiento.js"></script>

</body>
</html>