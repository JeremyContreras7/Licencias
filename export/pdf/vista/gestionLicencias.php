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

// --- CREAR LICENCIA ---
if (isset($_POST['crear'])) {
    $id_equipo = (int)$_POST['id_equipo'];
    $id_software = (int)$_POST['id_software'];
    $id_usuario = (int)$_POST['id_usuario'];
    $fecha_inicio = trim($_POST['fecha_inicio']);
    $fecha_vencimiento = trim($_POST['fecha_vencimiento']);

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
    }

    // Insertar licencia
    $stmt = $conexion->prepare("
        INSERT INTO licencias (id_equipo, id_software, id_usuario, fecha_inicio, fecha_vencimiento) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("iiiss", $id_equipo, $id_software, $id_usuario, $fecha_inicio, $fecha_vencimiento);
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
            DELETE l FROM licencias l
            JOIN equipos e ON l.id_equipo = e.id_equipo
            WHERE l.id_licencia = ? AND e.id_establecimiento = ?
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
               l.fecha_inicio, l.fecha_vencimiento, u.nombre AS usuario
        FROM licencias l
        JOIN equipos e ON e.id_equipo = l.id_equipo
        JOIN software s ON s.id_software = l.id_software
        JOIN usuarios u ON u.id_usuario = l.id_usuario
        WHERE e.id_establecimiento = {$id_establecimiento}
        ORDER BY l.fecha_vencimiento ASC
    ");
} else {
    $licencias = $conexion->query("
        SELECT l.id_licencia, e.nombre_equipo, s.nombre_software, s.version, 
               l.fecha_inicio, l.fecha_vencimiento, u.nombre AS usuario, est.nombre_establecimiento
        FROM licencias l
        JOIN equipos e ON e.id_equipo = l.id_equipo
        JOIN software s ON s.id_software = l.id_software
        JOIN usuarios u ON u.id_usuario = l.id_usuario
        JOIN establecimientos est ON e.id_establecimiento = est.id_establecimiento
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
    <style>
        /* Modal de Confirmación */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(5px);
            z-index: 1000;
            animation: fadeIn 0.3s ease;
        }

        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 0;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 90%;
            overflow: hidden;
            animation: modalSlideIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translate(-50%, -60%);
            }
            to {
                opacity: 1;
                transform: translate(-50%, -50%);
            }
        }

        .modal-header {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
            padding: 25px;
            text-align: center;
        }

        .modal-header i {
            font-size: 3rem;
            margin-bottom: 15px;
            display: block;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 1.4rem;
            font-weight: 600;
        }

        .modal-body {
            padding: 30px;
            text-align: center;
        }

        .modal-body p {
            margin: 0 0 20px 0;
            color: #555;
            font-size: 1.1rem;
            line-height: 1.5;
        }

        .licencia-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            border-left: 4px solid #e74c3c;
            text-align: left;
        }

        .licencia-detail {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            padding: 5px 0;
            border-bottom: 1px solid #e9ecef;
        }

        .licencia-detail:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        .detail-label {
            font-weight: 600;
            color: #495057;
        }

        .detail-value {
            color: #e74c3c;
            font-weight: 500;
        }

        .estado-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .estado-vigente { background: #d4edda; color: #155724; }
        .estado-proxima { background: #fff3cd; color: #856404; }
        .estado-vencida { background: #f8d7da; color: #721c24; }

        .modal-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-top: 25px;
        }

        .btn-cancel {
            background: #95a5a6;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            flex: 1;
        }

        .btn-cancel:hover {
            background: #7f8c8d;
            transform: translateY(-2px);
        }

        .btn-confirm-delete {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-confirm-delete:hover {
            background: linear-gradient(135deg, #c0392b, #a93226);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(231, 76, 60, 0.4);
        }

        .btn-delete {
            background: transparent;
            color: #e74c3c;
            border: 1px solid #e74c3c;
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.85rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-delete:hover {
            background: #e74c3c;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(231, 76, 60, 0.3);
        }

        /* Mejoras para la tabla */
        .action-btn {
            transition: all 0.3s ease;
        }

        .action-btn:hover {
            transform: translateY(-2px);
        }

        .table tbody tr {
            transition: all 0.3s ease;
        }

        .table tbody tr:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        /* Responsive para el modal */
        @media (max-width: 480px) {
            .modal-content {
                width: 95%;
                margin: 10px;
            }

            .modal-actions {
                flex-direction: column;
            }

            .btn-cancel, .btn-confirm-delete {
                flex: none;
            }

            .licencia-detail {
                flex-direction: column;
                gap: 5px;
            }
        }
    </style>
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
        $hoy = date('Y-m-d');
        $total_licencias = $licencias->num_rows;
        
        // Reiniciar el puntero para contar estados
        $licencias->data_seek(0);
        $vencidas = 0;
        $proximas = 0;
        $vigentes = 0;
        
        while($row = $licencias->fetch_assoc()) {
            $venc = $row['fecha_vencimiento'];
            $dias = floor((strtotime($venc)-strtotime($hoy))/(60*60*24));
            
            if ($venc < $hoy) {
                $vencidas++;
            } elseif ($dias <= 30) {
                $proximas++;
            } else {
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
                <div class="label">Próximas a Vencer</div>
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
                            <label for="fecha_inicio"><i class="fas fa-calendar-alt me-1"></i> Fecha de inicio</label>
                            <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <div class="field">
                            <label for="fecha_vencimiento"><i class="fas fa-calendar-times me-1"></i> Fecha de vencimiento</label>
                            <input type="date" class="form-control" id="fecha_vencimiento" name="fecha_vencimiento" required>
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
                                <th>Usuario asignado</th>
                                <th>Fecha inicio</th>
                                <th>Fecha vencimiento</th>
                                <th>Estado</th>
                                <?php if ($rol === "ADMIN"): ?><th>Establecimiento</th><?php endif; ?>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php 
                        if ($licencias->num_rows > 0) {
                            while($row = $licencias->fetch_assoc()): 
                                $hoy = date('Y-m-d');
                                $venc = $row['fecha_vencimiento'];
                                $dias = floor((strtotime($venc)-strtotime($hoy))/(60*60*24));
                                
                                if ($venc < $hoy) {
                                    $estado_class = "estado-vencida";
                                    $txt = "Vencida";
                                    $badge_class = "estado-vencida";
                                } elseif ($dias <= 30) {
                                    $estado_class = "estado-proxima";
                                    $txt = "Próxima";
                                    $badge_class = "estado-proxima";
                                } else {
                                    $estado_class = "estado-vigente";
                                    $txt = "Vigente";
                                    $badge_class = "estado-vigente";
                                }
                        ?>
                            <tr>
                                <td><?= $row['nombre_equipo'] ?></td>
                                <td><?= $row['nombre_software'] ?></td>
                                <td><?= $row['version'] ?></td>
                                <td><?= $row['usuario'] ?></td>
                                <td><?= $row['fecha_inicio'] ?></td>
                                <td><?= $row['fecha_vencimiento'] ?></td>
                                <td><span class="<?= $estado_class ?>"><?= $txt ?></span></td>
                                <?php if ($rol === "ADMIN"): ?><td><?= $row['nombre_establecimiento'] ?></td><?php endif; ?>
                                <td>
                                    <a href="editar_licencia.php?id=<?= $row['id_licencia'] ?>" class="btn btn-sm btn-outline-primary action-btn" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="#" class="btn-delete action-btn" 
                                       data-id="<?= $row['id_licencia'] ?>"
                                       data-software="<?= htmlspecialchars($row['nombre_software'] . ' ' . $row['version']) ?>"
                                       data-equipo="<?= htmlspecialchars($row['nombre_equipo']) ?>"
                                       data-usuario="<?= htmlspecialchars($row['usuario']) ?>"
                                       data-vencimiento="<?= $row['fecha_vencimiento'] ?>"
                                       data-estado="<?= $txt ?>"
                                       data-estado-class="<?= $badge_class ?>"
                                       title="Eliminar">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php 
                            endwhile;
                        } else {
                            $colspan = $rol === "ADMIN" ? 8 : 7;
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
<script src="../js/gestionLicencia.js"></script>

</body>
</html>
<?php
if ($rol === "ENCARGADO" && $licencias && $licencias->num_rows > 0) {
    $licencias->data_seek(0); // reiniciar puntero del resultset
    $proximas = [];

    while ($row = $licencias->fetch_assoc()) {
        $hoy = date('Y-m-d');
        $vencimiento = $row['fecha_vencimiento'];
        $dias_restantes = floor((strtotime($vencimiento) - strtotime($hoy)) / (60 * 60 * 24));

        if ($dias_restantes >= 0 && $dias_restantes <= 30) {
            $proximas[] = $row;
        }
    }

    if (count($proximas) > 0) {
        require 'phpmailer/Exception.php';
        require 'phpmailer/PHPMailer.php';
        require 'phpmailer/SMTP.php';

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
            $mail->Subject = "Notificacion: Licencias proximas a vencer";

            $body = "<h2>Estimado/a </h2>";
            $body .= "<p>Se han detectado las siguientes licencias de su establecimiento que vencen en los proximos 30 dias:</p>";
            $body .= "<table border='1' cellspacing='0' cellpadding='5'>
                        <tr>
                            <th>Equipo</th>
                            <th>Software</th>
                            <th>Version</th>
                            <th>Fecha de Vencimiento</th>
                        </tr>";
            foreach ($proximas as $lic) {
                $body .= "<tr>
                            <td>{$lic['nombre_equipo']}</td>
                            <td>{$lic['nombre_software']}</td>
                            <td>{$lic['version']}</td>
                            <td>{$lic['fecha_vencimiento']}</td>
                          </tr>";
            }
            $body .= "</table><p>Por favor, gestione la renovacion a la brevedad.</p>";

            $mail->Body = $body;
            $mail->AltBody = "Tiene licencias proximas a vencer. Revise el sistema para mas detalles.";

            $mail->send();
            // Puedes mostrar un aviso en pantalla si quieres:
            echo "<div class='notice success'><i class='fas fa-envelope'></i> Se ha enviado una notificación de licencias próximas a vencer a su correo.</div>";
        } catch (Exception $e) {
            echo "<div class='notice error'>❌ Error al enviar notificación: {$mail->ErrorInfo}</div>";
        }
    }
}
?>