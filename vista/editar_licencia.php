<?php
session_start();
if (!isset($_SESSION['rol'])) {
    header("Location: ../index.php");
    exit();
}

include("../modelo/conexion.php");

$rol = $_SESSION['rol'];
$id_establecimiento = $_SESSION['id_establecimiento'] ?? null;
$nombre_usuario = htmlspecialchars($_SESSION['nombre'] ?? '');

// Determinar la ruta del menú según el rol
$menu_url = ($rol === "ADMIN") ? "menu.php" : "menu_informatico.php";

// Verificar si se proporcionó un ID válido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: gestionLicencias.php?error=id_invalido");
    exit();
}

$id_licencia = (int)$_GET['id'];

// Obtener datos de la licencia a editar
if ($rol === "ENCARGADO") {
    $stmt = $conexion->prepare("
        SELECT l.*, e.nombre_equipo, s.nombre_software, s.version, u.nombre AS usuario_nombre
        FROM licencias l
        JOIN equipos e ON e.id_equipo = l.id_equipo
        JOIN software s ON s.id_software = l.id_software
        JOIN usuarios u ON u.id_usuario = l.id_usuario
        WHERE l.id_licencia = ? AND l.id_establecimiento = ?
    ");
    $stmt->bind_param("ii", $id_licencia, $id_establecimiento);
} else {
    $stmt = $conexion->prepare("
        SELECT l.*, e.nombre_equipo, s.nombre_software, s.version, u.nombre AS usuario_nombre,
               est.nombre_establecimiento
        FROM licencias l
        JOIN equipos e ON e.id_equipo = l.id_equipo
        JOIN software s ON s.id_software = l.id_software
        JOIN usuarios u ON u.id_usuario = l.id_usuario
        JOIN establecimientos est ON l.id_establecimiento = est.id_establecimiento
        WHERE l.id_licencia = ?
    ");
    $stmt->bind_param("i", $id_licencia);
}

$stmt->execute();
$result = $stmt->get_result();
$licencia = $result->fetch_assoc();
$stmt->close();

// Si no se encuentra la licencia o no tiene permisos
if (!$licencia) {
    header("Location: gestionLicencias.php?error=licencia_no_encontrada");
    exit();
}

// --- ACTUALIZAR LICENCIA ---
if (isset($_POST['actualizar'])) {
    $id_equipo = (int)$_POST['id_equipo'];
    $id_software = (int)$_POST['id_software'];
    $id_usuario = (int)$_POST['id_usuario'];
    $fecha_inicio = trim($_POST['fecha_inicio']);
    $fecha_vencimiento = trim($_POST['fecha_vencimiento']);
    $tipo_licencia = trim($_POST['tipo_licencia']);
    $restricciones = trim($_POST['restricciones']);
    $renovable = isset($_POST['renovable']) ? 1 : 0;
    $metodo_activacion = trim($_POST['metodo_activacion']);
    $notas = trim($_POST['notas']);

    // Validaciones básicas
    if (empty($fecha_inicio) || empty($fecha_vencimiento)) {
        header("Location: editar_licencia.php?id=$id_licencia&error=fechas");
        exit();
    }
    if ($fecha_inicio > $fecha_vencimiento) {
        header("Location: editar_licencia.php?id=$id_licencia&error=fechasinvalida");
        exit();
    }

    // Validaciones de establecimiento si es ENCARGADO
    if ($rol === "ENCARGADO") {
        // verificar equipo
        $stmt = $conexion->prepare("SELECT COUNT(*) FROM equipos WHERE id_equipo = ? AND id_establecimiento = ?");
        $stmt->bind_param("ii", $id_equipo, $id_establecimiento);
        $stmt->execute(); $stmt->bind_result($cntEq); $stmt->fetch(); $stmt->close();
        if ($cntEq == 0) {
            header("Location: editar_licencia.php?id=$id_licencia&error=equipo_no_pertenece");
            exit();
        }

        // verificar software
        $stmt = $conexion->prepare("SELECT COUNT(*) FROM software WHERE id_software = ? AND id_establecimiento = ?");
        $stmt->bind_param("ii", $id_software, $id_establecimiento);
        $stmt->execute(); $stmt->bind_result($cntSw); $stmt->fetch(); $stmt->close();
        if ($cntSw == 0) {
            header("Location: editar_licencia.php?id=$id_licencia&error=software_no_pertenece");
            exit();
        }

        // verificar usuario
        $stmt = $conexion->prepare("SELECT COUNT(*) FROM usuarios WHERE id_usuario = ? AND id_establecimiento = ?");
        $stmt->bind_param("ii", $id_usuario, $id_establecimiento);
        $stmt->execute(); $stmt->bind_result($cntUs); $stmt->fetch(); $stmt->close();
        if ($cntUs == 0) {
            header("Location: editar_licencia.php?id=$id_licencia&error=usuario_no_pertenece");
            exit();
        }
    }

    // Calcular nuevo estado automáticamente
    $hoy = date('Y-m-d');
    $dias_restantes = floor((strtotime($fecha_vencimiento) - strtotime($hoy)) / (60 * 60 * 24));
    
    if ($fecha_vencimiento < $hoy) {
        $estado = 'VENCIDA';
    } elseif ($dias_restantes <= 30) {
        $estado = 'POR VENCER';
    } else {
        $estado = 'VIGENTE';
    }

    // Actualizar licencia
    $stmt = $conexion->prepare("
        UPDATE licencias 
        SET id_equipo = ?, id_software = ?, id_usuario = ?, fecha_inicio = ?, 
            fecha_vencimiento = ?, estado = ?, tipo_licencia = ?, restricciones = ?, 
            renovable = ?, metodo_activacion = ?, notas = ?
        WHERE id_licencia = ?
    ");
    $stmt->bind_param("iiisssssissi", $id_equipo, $id_software, $id_usuario, $fecha_inicio, 
                     $fecha_vencimiento, $estado, $tipo_licencia, $restricciones, 
                     $renovable, $metodo_activacion, $notas, $id_licencia);
    $ok = $stmt->execute();
    $stmt->close();

    header("Location: gestionLicencias.php?".($ok ? "msg=updated" : "error=db"));
    exit();
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/styleGlicencias.css">
    <link rel="stylesheet" href="../css/editarlicencia.css">

</head>
<body>
    <div class="container">
        <!-- Encabezado -->
        <div class="header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1><i class="fas fa-edit me-2"></i>Editar Licencia</h1>
                    <p class="mb-0 mt-1">Modificar información de la licencia de software</p>
                </div>
                <div class="text-end">
                    <div class="user-info">
                        <i class="fas fa-user me-1"></i> <?= $nombre_usuario ?> — <?= htmlspecialchars($rol) ?>
                    </div>
                    <a href="gestionLicencias.php" class="btn btn-light btn-sm mt-2">
                        <i class="fas fa-arrow-left me-1"></i> Volver a Licencias
                    </a>
                </div>
            </div>
        </div>

        <!-- Información actual de la licencia -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i> Información Actual de la Licencia</h5>
            </div>
            <div class="card-body">
                <div class="licencia-info">
                    <div class="licencia-detail">
                        <span class="detail-label">Software Actual:</span>
                        <span class="detail-value"><?= htmlspecialchars($licencia['nombre_software'] . ' ' . $licencia['version']) ?></span>
                    </div>
                    <div class="licencia-detail">
                        <span class="detail-label">Equipo Actual:</span>
                        <span class="detail-value"><?= htmlspecialchars($licencia['nombre_equipo']) ?></span>
                    </div>
                    <div class="licencia-detail">
                        <span class="detail-label">Usuario Actual:</span>
                        <span class="detail-value"><?= htmlspecialchars($licencia['usuario_nombre']) ?></span>
                    </div>
                    <div class="licencia-detail">
                        <span class="detail-label">Estado Actual:</span>
                        <span class="detail-value">
                            <span class="estado-badge <?php 
                                if ($licencia['estado'] === 'VENCIDA') echo 'estado-vencida';
                                elseif ($licencia['estado'] === 'POR VENCER') echo 'estado-proxima';
                                else echo 'estado-vigente';
                            ?>">
                                <?= $licencia['estado'] ?>
                            </span>
                        </span>
                    </div>
                    <?php if ($rol === "ADMIN"): ?>
                    <div class="licencia-detail">
                        <span class="detail-label">Establecimiento:</span>
                        <span class="detail-value"><?= htmlspecialchars($licencia['nombre_establecimiento']) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Formulario de edición -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-edit me-2"></i> Modificar Información de la Licencia</h5>
            </div>
            <div class="card-body">
                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php
                        $errors = [
                            'fechas' => 'Las fechas de inicio y vencimiento son requeridas',
                            'fechasinvalida' => 'La fecha de inicio no puede ser posterior a la fecha de vencimiento',
                            'equipo_no_pertenece' => 'El equipo seleccionado no pertenece a su establecimiento',
                            'software_no_pertenece' => 'El software seleccionado no pertenece a su establecimiento',
                            'usuario_no_pertenece' => 'El usuario seleccionado no pertenece a su establecimiento',
                            'db' => 'Error al actualizar en la base de datos'
                        ];
                        echo $errors[$_GET['error']] ?? 'Error desconocido';
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-grid">
                        <div class="field">
                            <label for="id_equipo"><i class="fas fa-desktop me-1"></i> Equipo</label>
                            <select class="form-select" name="id_equipo" id="id_equipo" required>
                                <option value="" disabled>Seleccione un equipo</option>
                                <?php 
                                $equipos->data_seek(0);
                                while($eq = $equipos->fetch_assoc()): 
                                    $selected = $eq['id_equipo'] == $licencia['id_equipo'] ? 'selected' : '';
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
                                    $selected = $sw['id_software'] == $licencia['id_software'] ? 'selected' : '';
                                ?>
                                    <option value="<?= $sw['id_software'] ?>" <?= $selected ?>>
                                        <?= htmlspecialchars($sw['nombre_software'].' '.$sw['version']) ?>
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
                                    $selected = $us['id_usuario'] == $licencia['id_usuario'] ? 'selected' : '';
                                ?>
                                    <option value="<?= $us['id_usuario'] ?>" <?= $selected ?>>
                                        <?= htmlspecialchars($us['nombre']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="field">
                            <label for="tipo_licencia"><i class="fas fa-tag me-1"></i> Tipo de Licencia</label>
                            <input type="text" class="form-control" id="tipo_licencia" name="tipo_licencia" 
                                   value="<?= htmlspecialchars($licencia['tipo_licencia']) ?>" 
                                   placeholder="Ej: Perpetua, Anual, Mensual...">
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

                        <div class="field">
                            <label for="metodo_activacion"><i class="fas fa-key me-1"></i> Método de Activación</label>
                            <input type="text" class="form-control" id="metodo_activacion" name="metodo_activacion" 
                                   value="<?= htmlspecialchars($licencia['metodo_activacion']) ?>" 
                                   placeholder="Ej: Clave de producto, Online...">
                        </div>

                        <div class="checkbox-field">
                            <input type="checkbox" class="form-check-input" id="renovable" name="renovable" value="1"
                                <?= $licencia['renovable'] ? 'checked' : '' ?>>
                            <label for="renovable" class="form-check-label">¿Es renovable?</label>
                        </div>

                        <div class="field full-width">
                            <label for="restricciones"><i class="fas fa-ban me-1"></i> Restricciones</label>
                            <textarea class="form-control" id="restricciones" name="restricciones" rows="2" 
                                      placeholder="Restricciones de uso..."><?= htmlspecialchars($licencia['restricciones']) ?></textarea>
                        </div>

                        <div class="field full-width">
                            <label for="notas"><i class="fas fa-sticky-note me-1"></i> Notas Adicionales</label>
                            <textarea class="form-control" id="notas" name="notas" rows="2" 
                                      placeholder="Información adicional..."><?= htmlspecialchars($licencia['notas']) ?></textarea>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" name="actualizar" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Actualizar Licencia
                        </button>
                        <a href="gestionLicencias.php" class="btn btn-secondary">
                            <i class="fas fa-times me-1"></i> Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Script para mostrar advertencia si se modifican fechas
        document.addEventListener('DOMContentLoaded', function() {
            const fechaInicio = document.getElementById('fecha_inicio');
            const fechaVencimiento = document.getElementById('fecha_vencimiento');
            const form = document.querySelector('form');
            
            form.addEventListener('submit', function(e) {
                if (fechaInicio.value > fechaVencimiento.value) {
                    e.preventDefault();
                    alert('Error: La fecha de inicio no puede ser posterior a la fecha de vencimiento');
                    fechaVencimiento.focus();
                }
            });
            
            // Calcular automáticamente el estado al cambiar fechas
            function calcularEstado() {
                const hoy = new Date().toISOString().split('T')[0];
                const vencimiento = fechaVencimiento.value;
                
                if (vencimiento && vencimiento < hoy) {
                    return 'VENCIDA';
                } else if (vencimiento) {
                    const diasRestantes = Math.floor((new Date(vencimiento) - new Date(hoy)) / (1000 * 60 * 60 * 24));
                    if (diasRestantes <= 30) {
                        return 'POR VENCER';
                    }
                }
                return 'VIGENTE';
            }
            
            fechaVencimiento.addEventListener('change', function() {
                const nuevoEstado = calcularEstado();
                console.log('El nuevo estado será:', nuevoEstado);
            });
        });
    </script>
</body>
</html>