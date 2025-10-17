<?php
session_start();
if (!isset($_SESSION['rol'])) {
    header("Location: ../index.php");
    exit();
}

include("../modelo/conexion.php");

$rol = $_SESSION['rol'];
$id_establecimiento = $_SESSION['id_establecimiento'] ?? null;

// Validar ID recibido
if (!isset($_GET['id'])) {
    header("Location: gestionLicencias.php");
    exit();
}
$id_licencia = (int)$_GET['id'];

// Obtener datos de la licencia
if ($rol === "ENCARGADO") {
    $stmt = $conexion->prepare("
        SELECT l.*, e.id_establecimiento 
        FROM licencias l
        JOIN equipos e ON l.id_equipo = e.id_equipo
        WHERE l.id_licencia = ? AND e.id_establecimiento = ?
    ");
    $stmt->bind_param("ii", $id_licencia, $id_establecimiento);
} else {
    $stmt = $conexion->prepare("SELECT * FROM licencias WHERE id_licencia = ?");
    $stmt->bind_param("i", $id_licencia);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<script>
        alert('❌ Licencia no encontrada o no tienes permisos para editarla.');
        window.location='gestionLicencias.php';
    </script>";
    exit();
}

$licencia = $result->fetch_assoc();
$stmt->close();

// Variable para mensajes
$mensaje = '';

// Guardar cambios
if (isset($_POST['guardar'])) {
    $id_equipo = (int)$_POST['id_equipo'];
    $id_software = (int)$_POST['id_software'];
    $id_usuario = (int)$_POST['id_usuario'];
    $fecha_inicio = trim($_POST['fecha_inicio']);
    $fecha_vencimiento = trim($_POST['fecha_vencimiento']);

    // Validaciones básicas
    if (empty($fecha_inicio) || empty($fecha_vencimiento)) {
        $mensaje = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Error: Las fechas son obligatorias.</div>';
    } elseif ($fecha_inicio > $fecha_vencimiento) {
        $mensaje = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Error: La fecha de inicio no puede ser posterior a la fecha de vencimiento.</div>';
    } else {
        // Validaciones de establecimiento si es ENCARGADO
        $permiso_ok = true;
        
        if ($rol === "ENCARGADO") {
            // Verificar equipo
            $stmt = $conexion->prepare("SELECT COUNT(*) FROM equipos WHERE id_equipo = ? AND id_establecimiento = ?");
            $stmt->bind_param("ii", $id_equipo, $id_establecimiento);
            $stmt->execute(); 
            $stmt->bind_result($cntEq); 
            $stmt->fetch(); 
            $stmt->close();
            
            if ($cntEq == 0) {
                $mensaje = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Error: El equipo seleccionado no pertenece a su establecimiento.</div>';
                $permiso_ok = false;
            }

            // Verificar software
            if ($permiso_ok) {
                $stmt = $conexion->prepare("SELECT COUNT(*) FROM software WHERE id_software = ? AND id_establecimiento = ?");
                $stmt->bind_param("ii", $id_software, $id_establecimiento);
                $stmt->execute(); 
                $stmt->bind_result($cntSw); 
                $stmt->fetch(); 
                $stmt->close();
                
                if ($cntSw == 0) {
                    $mensaje = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Error: El software seleccionado no pertenece a su establecimiento.</div>';
                    $permiso_ok = false;
                }
            }

            // Verificar usuario
            if ($permiso_ok) {
                $stmt = $conexion->prepare("SELECT COUNT(*) FROM usuarios WHERE id_usuario = ? AND id_establecimiento = ?");
                $stmt->bind_param("ii", $id_usuario, $id_establecimiento);
                $stmt->execute(); 
                $stmt->bind_result($cntUs); 
                $stmt->fetch(); 
                $stmt->close();
                
                if ($cntUs == 0) {
                    $mensaje = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Error: El usuario seleccionado no pertenece a su establecimiento.</div>';
                    $permiso_ok = false;
                }
            }
        }

        // Actualizar licencia si todo está bien
        if ($permiso_ok) {
            $update = $conexion->prepare("
                UPDATE licencias 
                SET id_equipo = ?, id_software = ?, id_usuario = ?, fecha_inicio = ?, fecha_vencimiento = ? 
                WHERE id_licencia = ?
            ");
            $update->bind_param("iiissi", $id_equipo, $id_software, $id_usuario, $fecha_inicio, $fecha_vencimiento, $id_licencia);

            if ($update->execute()) {
                echo "<script>
                    alert('✅ Licencia actualizada correctamente.');
                    window.location='gestionLicencias.php?success=1';
                </script>";
                exit();
            } else {
                $mensaje = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Error al actualizar la licencia: ' . $conexion->error . '</div>';
            }
            $update->close();
        }
    }
}

// --- LISTADO DE EQUIPOS ---
if ($rol === "ENCARGADO") {
    $equipos = $conexion->query("SELECT id_equipo, nombre_equipo FROM equipos WHERE id_establecimiento = {$id_establecimiento} ORDER BY nombre_equipo");
} else {
    $equipos = $conexion->query("SELECT id_equipo, nombre_equipo FROM equipos ORDER BY nombre_equipo");
}

// --- LISTADO DE SOFTWARE ---
if ($rol === "ENCARGADO") {
    $software = $conexion->query("SELECT id_software, nombre_software, version FROM software WHERE id_establecimiento = {$id_establecimiento} ORDER BY nombre_software");
} else {
    $software = $conexion->query("SELECT id_software, nombre_software, version FROM software ORDER BY nombre_software");
}

// --- LISTADO DE USUARIOS ---
if ($rol === "ENCARGADO") {
    $usuarios = $conexion->query("SELECT id_usuario, nombre FROM usuarios WHERE id_establecimiento = {$id_establecimiento} ORDER BY nombre");
} else {
    $usuarios = $conexion->query("SELECT id_usuario, nombre FROM usuarios ORDER BY nombre");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Licencia</title>
    <link rel="stylesheet" href="../css/editarlicencia.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container py-4">
        <!-- Encabezado -->
        <div class="header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1><i class="fas fa-edit me-2"></i>Editar Licencia</h1>
                    <p class="mb-0 mt-1">Modifica la información de la licencia seleccionada</p>
                </div>
                <div class="text-end">
                    <div class="user-info">
                        <i class="fas fa-user me-1"></i> <?= htmlspecialchars($_SESSION['nombre'] ?? '') ?> — <?= htmlspecialchars($rol) ?>
                    </div>
                    <a href="gestionLicencias.php" class="back-btn mt-2">
                        <i class="fas fa-arrow-left me-1"></i> Volver a Licencias
                    </a>
                </div>
            </div>
        </div>

        <!-- Mostrar mensajes -->
        <?php if (!empty($mensaje)): ?>
            <div class="container">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <!-- Información de la licencia -->
        <div class="licencia-info">
            <h6><i class="fas fa-info-circle"></i> Información de la Licencia</h6>
            <div class="row">
                <div class="col-md-6">
                    <strong>ID de Licencia:</strong> #<?= $licencia['id_licencia'] ?>
                </div>
                <div class="col-md-6">
                    <strong>Establecimiento:</strong> <?= htmlspecialchars($_SESSION['establecimiento'] ?? '—') ?>
                </div>
            </div>
        </div>

        <!-- Formulario de Edición -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-edit me-2"></i> Modificar Datos de la Licencia</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="form-grid">
                        <div class="field">
                            <label for="id_equipo"><i class="fas fa-desktop me-1"></i> Equipo</label>
                            <select class="form-select" name="id_equipo" id="id_equipo" required>
                                <option value="" disabled>Seleccione un equipo</option>
                                <?php 
                                $equipos->data_seek(0);
                                while($eq = $equipos->fetch_assoc()): 
                                    $selected = ($eq['id_equipo'] == $licencia['id_equipo']) ? 'selected' : '';
                                ?>
                                    <option value="<?= $eq['id_equipo'] ?>" <?= $selected ?>>
                                        <?= htmlspecialchars($eq['nombre_equipo']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="field">
                            <label for="id_software"><i class="fas fa-cube me-1"></i> Software</label>
                            <select class="form-select" name="id_software" id="id_software" required>
                                <option value="" disabled>Seleccione un software</option>
                                <?php 
                                $software->data_seek(0);
                                while($sw = $software->fetch_assoc()): 
                                    $selected = ($sw['id_software'] == $licencia['id_software']) ? 'selected' : '';
                                ?>
                                    <option value="<?= $sw['id_software'] ?>" <?= $selected ?>>
                                        <?= htmlspecialchars($sw['nombre_software'] . ' ' . $sw['version']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="field">
                            <label for="id_usuario"><i class="fas fa-user me-1"></i> Usuario asignado</label>
                            <select class="form-select" name="id_usuario" id="id_usuario" required>
                                <option value="" disabled>Seleccione un usuario</option>
                                <?php 
                                $usuarios->data_seek(0);
                                while($us = $usuarios->fetch_assoc()): 
                                    $selected = ($us['id_usuario'] == $licencia['id_usuario']) ? 'selected' : '';
                                ?>
                                    <option value="<?= $us['id_usuario'] ?>" <?= $selected ?>>
                                        <?= htmlspecialchars($us['nombre']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="field">
                            <label for="fecha_inicio"><i class="fas fa-calendar-alt me-1"></i> Fecha de inicio</label>
                            <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" 
                                   value="<?= $licencia['fecha_inicio'] ?>" required>
                        </div>

                        <div class="field">
                            <label for="fecha_vencimiento"><i class="fas fa-calendar-times me-1"></i> Fecha de vencimiento</label>
                            <input type="date" class="form-control" id="fecha_vencimiento" name="fecha_vencimiento" 
                                   value="<?= $licencia['fecha_vencimiento'] ?>" required>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="gestionLicencias.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-1"></i> Cancelar
                        </a>
                        <button type="submit" name="guardar" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/editarLicencia.js"></script>
</body>
</html>