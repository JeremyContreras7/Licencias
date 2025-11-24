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

// --- ACTUALIZAR ESTADOS DE LICENCIAS AUTOMÁTICAMENTE ---
$hoy = date('Y-m-d');

// Primero actualizamos todas las licencias que están vencidas
if ($rol === "ENCARGADO") {
    $update_vencidas = $conexion->prepare("
        UPDATE licencias 
        SET estado = 'VENCIDA' 
        WHERE fecha_vencimiento < ? 
        AND estado != 'VENCIDA'
        AND id_establecimiento = ?
    ");
    $update_vencidas->bind_param("si", $hoy, $id_establecimiento);
} else {
    $update_vencidas = $conexion->prepare("
        UPDATE licencias 
        SET estado = 'VENCIDA' 
        WHERE fecha_vencimiento < ? 
        AND estado != 'VENCIDA'
    ");
    $update_vencidas->bind_param("s", $hoy);
}
$update_vencidas->execute();
$update_vencidas->close();

// Luego actualizamos las licencias que están por vencer (próximos 30 días)
$fecha_limite = date('Y-m-d', strtotime('+30 days'));

if ($rol === "ENCARGADO") {
    $update_proximas = $conexion->prepare("
        UPDATE licencias 
        SET estado = 'POR VENCER' 
        WHERE fecha_vencimiento BETWEEN ? AND ?
        AND estado NOT IN ('VENCIDA', 'POR VENCER')
        AND id_establecimiento = ?
    ");
    $update_proximas->bind_param("ssi", $hoy, $fecha_limite, $id_establecimiento);
} else {
    $update_proximas = $conexion->prepare("
        UPDATE licencias 
        SET estado = 'POR VENCER' 
        WHERE fecha_vencimiento BETWEEN ? AND ?
        AND estado NOT IN ('VENCIDA', 'POR VENCER')
    ");
    $update_proximas->bind_param("ss", $hoy, $fecha_limite);
}
$update_proximas->execute();
$update_proximas->close();

// Finalmente, las que están vigentes (más de 30 días)
if ($rol === "ENCARGADO") {
    $update_vigentes = $conexion->prepare("
        UPDATE licencias 
        SET estado = 'VIGENTE' 
        WHERE fecha_vencimiento > ?
        AND estado NOT IN ('VENCIDA', 'VIGENTE')
        AND id_establecimiento = ?
    ");
    $update_vigentes->bind_param("si", $fecha_limite, $id_establecimiento);
} else {
    $update_vigentes = $conexion->prepare("
        UPDATE licencias 
        SET estado = 'VIGENTE' 
        WHERE fecha_vencimiento > ?
        AND estado NOT IN ('VENCIDA', 'VIGENTE')
    ");
    $update_vigentes->bind_param("s", $fecha_limite);
}
$update_vigentes->execute();
$update_vigentes->close();

// --- CREAR LICENCIA ---
if (isset($_POST['crear'])) {
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

    if (empty($fecha_inicio) || empty($fecha_vencimiento)) {
        header("Location: gestionLicencias.php?error=fechas");
        exit();
    }
    if ($fecha_inicio > $fecha_vencimiento) {
        header("Location: gestionLicencias.php?error=fechasinvalida");
        exit();
    }

    // Validaciones de establecimiento si es ENCARGADO
    if ($rol === "ENCARGADO") {
        // verificar equipo
        $stmt = $conexion->prepare("SELECT COUNT(*) FROM equipos WHERE id_equipo = ? AND id_establecimiento = ?");
        $stmt->bind_param("ii", $id_equipo, $id_establecimiento);
        $stmt->execute(); $stmt->bind_result($cntEq); $stmt->fetch(); $stmt->close();
        if ($cntEq == 0) {
            header("Location: gestionLicencias.php?error=equipo_no_pertenece");
            exit();
        }

        // verificar software
        $stmt = $conexion->prepare("SELECT COUNT(*) FROM software WHERE id_software = ? AND id_establecimiento = ?");
        $stmt->bind_param("ii", $id_software, $id_establecimiento);
        $stmt->execute(); $stmt->bind_result($cntSw); $stmt->fetch(); $stmt->close();
        if ($cntSw == 0) {
            header("Location: gestionLicencias.php?error=software_no_pertenece");
            exit();
        }

        // verificar usuario
        $stmt = $conexion->prepare("SELECT COUNT(*) FROM usuarios WHERE id_usuario = ? AND id_establecimiento = ?");
        $stmt->bind_param("ii", $id_usuario, $id_establecimiento);
        $stmt->execute(); $stmt->bind_result($cntUs); $stmt->fetch(); $stmt->close();
        if ($cntUs == 0) {
            header("Location: gestionLicencias.php?error=usuario_no_pertenece");
            exit();
        }

        // Para ENCARGADO, usar el id_establecimiento de la sesión
        $id_establecimiento_licencia = $id_establecimiento;
    } else {
        // Para ADMIN, obtener el id_establecimiento del equipo seleccionado
        $stmt = $conexion->prepare("SELECT id_establecimiento FROM equipos WHERE id_equipo = ?");
        $stmt->bind_param("i", $id_equipo);
        $stmt->execute();
        $stmt->bind_result($id_establecimiento_licencia);
        $stmt->fetch();
        $stmt->close();
    }

    // Calcular estado automáticamente
    $dias_restantes = floor((strtotime($fecha_vencimiento) - strtotime($hoy)) / (60 * 60 * 24));
    
    if ($fecha_vencimiento < $hoy) {
        $estado = 'VENCIDA';
    } elseif ($dias_restantes <= 30) {
        $estado = 'POR VENCER';
    } else {
        $estado = 'VIGENTE';
    }

    // Insertar licencia con todos los campos CORREGIDOS
    $stmt = $conexion->prepare("
        INSERT INTO licencias 
        (id_equipo, id_software, id_usuario, fecha_inicio, fecha_vencimiento, estado, tipo_licencia, restricciones, renovable, metodo_activacion, notas, id_establecimiento) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("iiisssssissi", $id_equipo, $id_software, $id_usuario, $fecha_inicio, $fecha_vencimiento, $estado, $tipo_licencia, $restricciones, $renovable, $metodo_activacion, $notas, $id_establecimiento_licencia);
    $ok = $stmt->execute();
    $stmt->close();

    header("Location: gestionLicencias.php?".($ok ? "msg=created" : "error=db"));
    exit();
}

// --- ELIMINAR LICENCIA ---
if (isset($_GET['eliminar'])) {
    $id = (int)$_GET['eliminar'];
    if ($rol === "ENCARGADO") {
        $stmt = $conexion->prepare("
            DELETE FROM licencias 
            WHERE id_licencia = ? AND id_establecimiento = ?
        ");
        $stmt->bind_param("ii", $id, $id_establecimiento);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
    } else {
        $stmt = $conexion->prepare("DELETE FROM licencias WHERE id_licencia = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
    }
    header("Location: gestionLicencias.php?".($affected > 0 ? "msg=deleted" : "error=delete_denegado"));
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

// --- LISTADO DE LICENCIAS ---
if ($rol === "ENCARGADO") {
    $licencias = $conexion->query("
        SELECT l.id_licencia, e.nombre_equipo, s.nombre_software, s.version, 
               l.fecha_inicio, l.fecha_vencimiento as fecha_vencimiento, l.estado, l.tipo_licencia,
               l.restricciones, l.renovable, l.metodo_activacion, l.notas,
               u.nombre AS usuario
        FROM licencias l
        JOIN equipos e ON e.id_equipo = l.id_equipo
        JOIN software s ON s.id_software = l.id_software
        JOIN usuarios u ON u.id_usuario = l.id_usuario
        WHERE l.id_establecimiento = {$id_establecimiento}
        ORDER BY l.fecha_vencimiento ASC
    ");
} else {
    $licencias = $conexion->query("
        SELECT l.id_licencia, e.nombre_equipo, s.nombre_software, s.version, 
               l.fecha_inicio, l.fecha_vencimiento as fecha_vencimiento, l.estado, l.tipo_licencia,
               l.restricciones, l.renovable, l.metodo_activacion, l.notas,
               u.nombre AS usuario, est.nombre_establecimiento
        FROM licencias l
        JOIN equipos e ON e.id_equipo = l.id_equipo
        JOIN software s ON s.id_software = l.id_software
        JOIN usuarios u ON u.id_usuario = l.id_usuario
        JOIN establecimientos est ON l.id_establecimiento = est.id_establecimiento
        ORDER BY l.fecha_vencimiento ASC
    ");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Licencias</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/styleGlicencias.css">
</head>
<body>
    <!-- Modal de Confirmación de Eliminación -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal-content">
            <div class="modal-header">
                <i class="fas fa-exclamation-triangle"></i>
                <h3>Confirmar Eliminación</h3>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de que deseas eliminar esta licencia?</p>
                <div class="licencia-info">
                    <div class="licencia-detail">
                        <span class="detail-label">Software:</span>
                        <span class="detail-value" id="licenciaSoftware"></span>
                    </div>
                    <div class="licencia-detail">
                        <span class="detail-label">Equipo:</span>
                        <span class="detail-value" id="licenciaEquipo"></span>
                    </div>
                    <div class="licencia-detail">
                        <span class="detail-label">Usuario:</span>
                        <span class="detail-value" id="licenciaUsuario"></span>
                    </div>
                    <div class="licencia-detail">
                        <span class="detail-label">Tipo Licencia:</span>
                        <span class="detail-value" id="licenciaTipo"></span>
                    </div>
                    <div class="licencia-detail">
                        <span class="detail-label">Vencimiento:</span>
                        <span class="detail-value" id="licenciaVencimiento"></span>
                    </div>
                    <div class="licencia-detail">
                        <span class="detail-label">Estado:</span>
                        <span class="detail-value" id="licenciaEstado"></span>
                    </div>
                </div>
                <p style="color: #e74c3c; font-weight: 500;">
                    <i class="fas fa-info-circle"></i>
                    Esta acción no se puede deshacer
                </p>
                <div class="modal-actions">
                    <button class="btn-cancel" id="cancelDelete">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <a href="#" class="btn-confirm-delete" id="confirmDelete">
                        <i class="fas fa-trash"></i> Sí, Eliminar
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Encabezado -->
        <div class="header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1><i class="fas fa-file-contract me-2"></i>Gestión de Licencias</h1>
                    <p class="mb-0 mt-1">Administra las licencias de software de tu organización</p>
                </div>
                <div class="text-end">
                    <div class="user-info">
                        <i class="fas fa-user me-1"></i> <?= $nombre_usuario ?> — <?= htmlspecialchars($rol) ?>
                    </div>
                    <a href="<?= $menu_url ?>" class="btn btn-light btn-sm mt-2">
                        <i class="fas fa-arrow-left me-1"></i> Volver al Menú
                    </a>
                </div>
            </div>
        </div>

        <!-- Tarjetas de estadísticas -->
        <?php
        // Calcular estadísticas
        $total_licencias = $licencias->num_rows;
        
        // Reiniciar el puntero para contar estados
        $licencias->data_seek(0);
        $vencidas = 0;
        $proximas = 0;
        $vigentes = 0;
        
        while($row = $licencias->fetch_assoc()) {
            $estado = $row['estado'];
            
            if ($estado === 'VENCIDA') {
                $vencidas++;
            } elseif ($estado === 'POR VENCER') {
                $proximas++;
            } elseif ($estado === 'VIGENTE') {
                $vigentes++;
            }
        }
        
        // Volver al inicio para mostrar la tabla
        $licencias->data_seek(0);
        ?>
        <div class="stats-container">
            <div class="stat-card">
                <div class="number"><?= $total_licencias ?></div>
                <div class="label">Total Licencias</div>
            </div>
            <div class="stat-card">
                <div class="number text-success"><?= $vigentes ?></div>
                <div class="label">Vigentes</div>
            </div>
            <div class="stat-card">
                <div class="number text-warning"><?= $proximas ?></div>
                <div class="label">Por Vencer</div>
            </div>
            <div class="stat-card">
                <div class="number text-danger"><?= $vencidas ?></div>
                <div class="label">Vencidas</div>
            </div>
        </div>

        <!-- Formulario de creación -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i> Registrar Nueva Licencia</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="form-grid">
                        <div class="field">
                            <label for="id_equipo"><i class="fas fa-desktop me-1"></i> Equipo</label>
                            <select class="form-select" name="id_equipo" id="id_equipo" required>
                                <option value="" selected disabled>Seleccione un equipo</option>
                                <?php while($eq = $equipos->fetch_assoc()): ?>
                                    <option value="<?= $eq['id_equipo'] ?>"><?= htmlspecialchars($eq['nombre_equipo']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="field">
                            <label for="id_software"><i class="fas fa-cube me-1"></i> Software</label>
                            <select class="form-select" name="id_software" id="id_software" required>
                                <option value="" selected disabled>Seleccione un software</option>
                                <?php while($sw = $software->fetch_assoc()): ?>
                                    <option value="<?= $sw['id_software'] ?>"><?= htmlspecialchars($sw['nombre_software'].' '.$sw['version']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="field">
                            <label for="id_usuario"><i class="fas fa-user me-1"></i> Usuario asignado</label>
                            <select class="form-select" name="id_usuario" id="id_usuario" required>
                                <option value="" selected disabled>Seleccione un usuario</option>
                                <?php while($us = $usuarios->fetch_assoc()): ?>
                                    <option value="<?= $us['id_usuario'] ?>"><?= htmlspecialchars($us['nombre']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="field">
                            <label for="tipo_licencia"><i class="fas fa-tag me-1"></i> Tipo de Licencia</label>
                            <input type="text" class="form-control" id="tipo_licencia" name="tipo_licencia" placeholder="Ej: Perpetua, Anual, Mensual...">
                        </div>

                        <div class="field">
                            <label for="fecha_inicio"><i class="fas fa-calendar-alt me-1"></i> Fecha de inicio</label>
                            <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <div class="field">
                            <label for="fecha_vencimiento"><i class="fas fa-calendar-times me-1"></i> Fecha de vencimiento</label>
                            <input type="date" class="form-control" id="fecha_vencimiento" name="fecha_vencimiento" required>
                        </div>

                        <div class="field">
                            <label for="metodo_activacion"><i class="fas fa-key me-1"></i> Método de Activación</label>
                            <input type="text" class="form-control" id="metodo_activacion" name="metodo_activacion" placeholder="Ej: Clave de producto, Online...">
                        </div>

                        <div class="checkbox-field">
                            <input type="checkbox" class="form-check-input" id="renovable" name="renovable" value="1">
                            <label for="renovable" class="form-check-label">¿Es renovable?</label>
                        </div>

                        <div class="field full-width">
                            <label for="restricciones"><i class="fas fa-ban me-1"></i> Restricciones</label>
                            <textarea class="form-control" id="restricciones" name="restricciones" rows="2" placeholder="Restricciones de uso..."></textarea>
                        </div>

                        <div class="field full-width">
                            <label for="notas"><i class="fas fa-sticky-note me-1"></i> Notas Adicionales</label>
                            <textarea class="form-control" id="notas" name="notas" rows="2" placeholder="Información adicional..."></textarea>
                        </div>
                    </div>
                    <button type="submit" name="crear" class="btn btn-primary mt-3">
                        <i class="fas fa-save me-1"></i> Registrar Licencia
                    </button>
                </form>
            </div>
        </div>

        <!-- Lista de licencias -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-list me-2"></i> Licencias Registradas</h5>
                <span class="badge bg-primary"><?= $total_licencias ?> licencias</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Equipo</th>
                                <th>Software</th>
                                <th>Versión</th>
                                <th>Usuario</th>
                                <th>Tipo Licencia</th>
                                <th>Fecha inicio</th>
                                <th>Fecha vencimiento</th>
                                <th>Estado</th>
                                <th>Renovable</th>
                                <?php if ($rol === "ADMIN"): ?><th>Establecimiento</th><?php endif; ?>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php 
                        if ($licencias->num_rows > 0) {
                            while($row = $licencias->fetch_assoc()): 
                                $estado = $row['estado'];
                                $estado_class = "";
                                $txt = $estado;
                                
                                if ($estado === 'VENCIDA') {
                                    $estado_class = "estado-vencida";
                                    $badge_class = "estado-vencida";
                                } elseif ($estado === 'POR VENCER') {
                                    $estado_class = "estado-proxima";
                                    $badge_class = "estado-proxima";
                                } else {
                                    $estado_class = "estado-vigente";
                                    $badge_class = "estado-vigente";
                                }
                        ?>
                            <tr>
                                <td><?= $row['nombre_equipo'] ?></td>
                                <td><?= $row['nombre_software'] ?></td>
                                <td><?= $row['version'] ?></td>
                                <td><?= $row['usuario'] ?></td>
                                <td><?= $row['tipo_licencia'] ?: 'N/A' ?></td>
                                <td><?= $row['fecha_inicio'] ?></td>
                                <td><?= $row['fecha_vencimiento'] ?></td>
                                <td><span class="estado-badge <?= $badge_class ?>"><?= $txt ?></span></td>
                                <td>
                                    <?php if ($row['renovable']): ?>
                                        <i class="fas fa-check text-success"></i>
                                    <?php else: ?>
                                        <i class="fas fa-times text-danger"></i>
                                    <?php endif; ?>
                                </td>
                                <?php if ($rol === "ADMIN"): ?><td><?= $row['nombre_establecimiento'] ?></td><?php endif; ?>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="editar_licencia.php?id=<?= $row['id_licencia'] ?>" class="btn btn-sm btn-outline-primary action-btn" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="#" class="btn-delete action-btn" 
                                           data-id="<?= $row['id_licencia'] ?>"
                                           data-software="<?= htmlspecialchars($row['nombre_software'] . ' ' . $row['version']) ?>"
                                           data-equipo="<?= htmlspecialchars($row['nombre_equipo']) ?>"
                                           data-usuario="<?= htmlspecialchars($row['usuario']) ?>"
                                           data-tipo="<?= htmlspecialchars($row['tipo_licencia']) ?>"
                                           data-vencimiento="<?= $row['fecha_vencimiento'] ?>"
                                           data-estado="<?= $txt ?>"
                                           data-estado-class="<?= $badge_class ?>"
                                           title="Eliminar">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php 
                            endwhile;
                        } else {
                            $colspan = $rol === "ADMIN" ? 10 : 9;
                            echo "<tr><td colspan='$colspan' class='text-center py-4 text-muted'><i class='fas fa-info-circle me-2'></i>No hay licencias registradas</td></tr>";
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Script para el modal de eliminación
        document.addEventListener('DOMContentLoaded', function() {
            const deleteButtons = document.querySelectorAll('.btn-delete');
            const modal = document.getElementById('deleteModal');
            const cancelBtn = document.getElementById('cancelDelete');
            const confirmBtn = document.getElementById('confirmDelete');
            
            let deleteUrl = '';
            
            deleteButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Obtener datos de la licencia
                    const software = this.getAttribute('data-software');
                    const equipo = this.getAttribute('data-equipo');
                    const usuario = this.getAttribute('data-usuario');
                    const tipo = this.getAttribute('data-tipo');
                    const vencimiento = this.getAttribute('data-vencimiento');
                    const estado = this.getAttribute('data-estado');
                    
                    // Llenar el modal con la información
                    document.getElementById('licenciaSoftware').textContent = software;
                    document.getElementById('licenciaEquipo').textContent = equipo;
                    document.getElementById('licenciaUsuario').textContent = usuario;
                    document.getElementById('licenciaTipo').textContent = tipo || 'N/A';
                    document.getElementById('licenciaVencimiento').textContent = vencimiento;
                    document.getElementById('licenciaEstado').textContent = estado;
                    
                    // Configurar URL de eliminación
                    const id = this.getAttribute('data-id');
                    deleteUrl = `gestionLicencias.php?eliminar=${id}`;
                    confirmBtn.href = deleteUrl;
                    
                    // Mostrar modal
                    modal.style.display = 'block';
                });
            });
            
            // Cerrar modal
            cancelBtn.addEventListener('click', function() {
                modal.style.display = 'none';
            });
            
            // Cerrar modal al hacer clic fuera
            window.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>
<?php
// ENVÍO DE NOTIFICACIONES POR CORREO
if ($rol === "ENCARGADO" && $licencias && $licencias->num_rows > 0) {
    $licencias->data_seek(0); // reiniciar puntero del resultset
    $proximas = [];
    $vencidas = [];

    while ($row = $licencias->fetch_assoc()) {
        if ($row['estado'] === 'POR VENCER') {
            $proximas[] = $row;
        } elseif ($row['estado'] === 'VENCIDA') {
            $vencidas[] = $row;
        }
    }

    // Enviar notificación si hay licencias por vencer O vencidas
    if (count($proximas) > 0 || count($vencidas) > 0) {
        require '../phpmailer/Exception.php';
        require '../phpmailer/PHPMailer.php';
        require '../phpmailer/SMTP.php';

        $mail = new PHPMailer\PHPMailer\PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = 'sandbox.smtp.mailtrap.io';
            $mail->SMTPAuth = true;
            $mail->Username = '50615979dcf445'; // usuario Mailtrap
            $mail->Password = '084d022f9ec7c1'; // contraseña Mailtrap
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('notificaciones@sistema-licencias.cl', 'Sistema de Licencias');
            $mail->addAddress('jeremytortuguita@gmail.com', 'Jeremy'); // Destinatario principal

            $mail->isHTML(true);
            $mail->Subject = "Notificación: Licencias Requieren Atención";

            // Construir el cuerpo del correo
            $body = "<h2>Estimado/a Usuario,</h2>";
            $body .= "<p>Se han detectado licencias en su establecimiento que requieren su atención:</p>";

            // Tabla para licencias por vencer
            if (count($proximas) > 0) {
                $body .= "<h3 style='color: #856404; background-color: #fff3cd; padding: 10px; border-radius: 5px;'>
                         <i class='fas fa-exclamation-triangle'></i> Licencias por Vencer (Próximos 30 días)</h3>";
                $body .= "<table border='1' cellspacing='0' cellpadding='8' style='border-collapse: collapse; width: 100%; margin-bottom: 20px;'>
                         <thead style='background-color: #fff3cd;'>
                            <tr>
                                <th>Equipo</th>
                                <th>Software</th>
                                <th>Versión</th>
                                <th>Usuario</th>
                                <th>Fecha Vencimiento</th>
                                <th>Días Restantes</th>
                            </tr>
                         </thead>
                         <tbody>";
                
                foreach ($proximas as $lic) {
                    $dias_restantes = floor((strtotime($lic['fecha_vencimiento']) - strtotime(date('Y-m-d'))) / (60 * 60 * 24));
                    $body .= "<tr>
                                <td>{$lic['nombre_equipo']}</td>
                                <td>{$lic['nombre_software']}</td>
                                <td>{$lic['version']}</td>
                                <td>{$lic['usuario']}</td>
                                <td>{$lic['fecha_vencimiento']}</td>
                                <td style='color: #856404; font-weight: bold;'>{$dias_restantes} días</td>
                              </tr>";
                }
                $body .= "</tbody></table>";
            }

            // Tabla para licencias vencidas
            if (count($vencidas) > 0) {
                $body .= "<h3 style='color: #721c24; background-color: #f8d7da; padding: 10px; border-radius: 5px;'>
                         <i class='fas fa-times-circle'></i> Licencias Vencidas</h3>";
                $body .= "<table border='1' cellspacing='0' cellpadding='8' style='border-collapse: collapse; width: 100%; margin-bottom: 20px;'>
                         <thead style='background-color: #f8d7da;'>
                            <tr>
                                <th>Equipo</th>
                                <th>Software</th>
                                <th>Versión</th>
                                <th>Usuario</th>
                                <th>Fecha Vencimiento</th>
                                <th>Días de Retraso</th>
                            </tr>
                         </thead>
                         <tbody>";
                
                foreach ($vencidas as $lic) {
                    $dias_retraso = floor((strtotime(date('Y-m-d')) - strtotime($lic['fecha_vencimiento'])) / (60 * 60 * 24));
                    $body .= "<tr>
                                <td>{$lic['nombre_equipo']}</td>
                                <td>{$lic['nombre_software']}</td>
                                <td>{$lic['version']}</td>
                                <td>{$lic['usuario']}</td>
                                <td>{$lic['fecha_vencimiento']}</td>
                                <td style='color: #721c24; font-weight: bold;'>{$dias_retraso} días</td>
                              </tr>";
                }
                $body .= "</tbody></table>";
            }

            // Resumen y recomendaciones
            $body .= "<div style='background-color: #e9ecef; padding: 15px; border-radius: 5px; margin-top: 20px;'>
                     <h4>Resumen:</h4>
                     <ul>
                         <li><strong>Licencias por vencer:</strong> " . count($proximas) . "</li>
                         <li><strong>Licencias vencidas:</strong> " . count($vencidas) . "</li>
                     </ul>
                     <p><strong>Recomendaciones:</strong></p>
                     <ul>
                         <li>Renueve las licencias por vencer antes de su fecha de expiración</li>
                         <li>Regularice las licencias vencidas lo antes posible</li>
                         <li>Revise el sistema de gestión de licencias para más detalles</li>
                     </ul>
                     </div>";

            $body .= "<p style='margin-top: 20px; color: #6c757d; font-size: 12px;'>
                     Este es un mensaje automático, por favor no responda a este correo.</p>";

            $mail->Body = $body;
            
            // Versión en texto plano para clientes de correo que no soportan HTML
            $textBody = "NOTIFICACIÓN DE LICENCIAS\n\n";
            $textBody .= "Se han detectado licencias que requieren atención:\n\n";
            
            if (count($proximas) > 0) {
                $textBody .= "LICENCIAS POR VENCER:\n";
                foreach ($proximas as $lic) {
                    $dias_restantes = floor((strtotime($lic['fecha_vencimiento']) - strtotime(date('Y-m-d'))) / (60 * 60 * 24));
                    $textBody .= "- {$lic['nombre_software']} {$lic['version']} en {$lic['nombre_equipo']} (Vence: {$lic['fecha_vencimiento']}, {$dias_restantes} días restantes)\n";
                }
                $textBody .= "\n";
            }
            
            if (count($vencidas) > 0) {
                $textBody .= "LICENCIAS VENCIDAS:\n";
                foreach ($vencidas as $lic) {
                    $dias_retraso = floor((strtotime(date('Y-m-d')) - strtotime($lic['fecha_vencimiento'])) / (60 * 60 * 24));
                    $textBody .= "- {$lic['nombre_software']} {$lic['version']} en {$lic['nombre_equipo']} (Venció: {$lic['fecha_vencimiento']}, {$dias_retraso} días de retraso)\n";
                }
                $textBody .= "\n";
            }
            
            $textBody .= "Total licencias por vencer: " . count($proximas) . "\n";
            $textBody .= "Total licencias vencidas: " . count($vencidas) . "\n\n";
            $textBody .= "Por favor, gestione la renovación y regularización a la brevedad.\n";
            $textBody .= "Acceda al sistema para más detalles.";

            $mail->AltBody = $textBody;

            $mail->send();
            
            $mensajes = [];
            $iconos = [];
            
            if (count($proximas) > 0) {
                $mensajes[] = "<strong>" . count($proximas) . " licencia(s) por vencer</strong>";
                $iconos[] = "⏰";
            }
            if (count($vencidas) > 0) {
                $mensajes[] = "<strong>" . count($vencidas) . " licencia(s) vencida(s)</strong>";
                $iconos[] = "⚠️";
            }
            
            if (!empty($mensajes)) {
                $icono_principal = implode(" ", $iconos);
                echo "
                <div class='container mt-4'>
                    <div class='alert alert-info border-0 shadow-lg' role='alert' style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-left: 6px solid #ffd700;'>
                        <div class='d-flex align-items-center'>
                            <div class='flex-shrink-0' style='font-size: 2rem; margin-right: 15px;'>
                                📧
                            </div>
                            <div class='flex-grow-1'>
                                <h5 class='alert-heading mb-2'><i class='fas fa-paper-plane me-2'></i>Notificación Enviada</h5>
                                <p class='mb-1'>Se ha enviado una alerta por correo electrónico con:</p>
                                <div class='mt-2'>" . implode(" y ", $mensajes) . "</div>
                                <hr style='border-color: rgba(255,255,255,0.3); margin: 10px 0;'>
                                <small class='opacity-75'>
                                    <i class='fas fa-clock me-1'></i>Enviado el " . date('d/m/Y \a \l\a\s H:i:s') . "
                                </small>
                            </div>
                            <button type='button' class='btn-close btn-close-white' data-bs-dismiss='alert' aria-label='Close'></button>
                        </div>
                    </div>
                </div>";
            }

        } catch (Exception $e) {
            echo "
            <div class='container mt-4'>
                <div class='alert alert-danger border-0 shadow-lg' role='alert' style='border-left: 6px solid #dc3545;'>
                    <div class='d-flex align-items-center'>
                        <div class='flex-shrink-0' style='font-size: 1.5rem; margin-right: 15px;'>
                            <i class='fas fa-exclamation-triangle text-danger'></i>
                        </div>
                        <div class='flex-grow-1'>
                            <h5 class='alert-heading mb-2'><i class='fas fa-times-circle me-2'></i>Error en Notificación</h5>
                            <p class='mb-1'>No se pudo enviar la notificación por correo electrónico.</p>
                            <small class='text-muted'>Error: " . htmlspecialchars($mail->ErrorInfo) . "</small>
                        </div>
                        <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                    </div>
                </div>
            </div>";
        }
    } else {
        // Mensaje cuando no hay licencias para notificar
        echo "
        <div class='container mt-4'>
            <div class='alert alert-success border-0 shadow-sm' role='alert' style='border-left: 6px solid #28a745;'>
                <div class='d-flex align-items-center'>
                    <div class='flex-shrink-0' style='font-size: 1.5rem; margin-right: 15px;'>
                        <i class='fas fa-check-circle text-success'></i>
                    </div>
                    <div class='flex-grow-1'>
                        <h6 class='alert-heading mb-1'><i class='fas fa-info-circle me-2'></i>Estado del Sistema</h6>
                        <p class='mb-0'>No hay licencias por vencer o vencidas que requieran notificación.</p>
                    </div>
                    <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                </div>
            </div>
        </div>";
    }
} else {
    // Mensaje cuando no se cumplen las condiciones para enviar notificaciones
    if ($rol !== "ENCARGADO") {
        echo "
        <div class='container mt-4'>
            <div class='alert alert-secondary border-0 shadow-sm' role='alert'>
                <div class='d-flex align-items-center'>
                    <div class='flex-shrink-0' style='font-size: 1.2rem; margin-right: 12px;'>
                        <i class='fas fa-user-shield text-muted'></i>
                    </div>
                    <div class='flex-grow-1'>
                        <small class='text-muted mb-0'>
                            <i class='fas fa-info-circle me-1'></i>Las notificaciones por correo están disponibles solo para usuarios ENCARGADO.
                        </small>
                    </div>
                </div>
            </div>
        </div>";
    }
}
?>