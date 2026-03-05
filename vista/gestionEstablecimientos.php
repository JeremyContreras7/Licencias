<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'ADMIN') {
    header("Location: ../index.php");
    exit();
}

include("../modelo/conexion.php");

// --- CREAR ESTABLECIMIENTO ---
if (isset($_POST['crear'])) {
    $nombre = trim($_POST['nombre']);
    $correo = trim($_POST['correo']);
    $telefono = trim($_POST['telefono']);
    $tipo = trim($_POST['tipo_escuela']);

    // Validación básica
    if (!empty($nombre) && !empty($tipo)) {
        $stmt = $conexion->prepare("
            INSERT INTO establecimientos (nombre_establecimiento, correo, telefono, tipo_escuela) 
            VALUES (?, ?, ?, ?)
        ");
        if ($stmt) {
            $stmt->bind_param("ssss", $nombre, $correo, $telefono, $tipo);
            $stmt->execute();
            $stmt->close();
            header("Location: gestionEstablecimientos.php?msg=created");
            exit();
        }
    }
}

// --- ELIMINAR ESTABLECIMIENTO ---
if (isset($_GET['eliminar'])) {
    $id = intval($_GET['eliminar']);

    // Verificar si el ID existe antes de eliminar
    $verificar = $conexion->prepare("SELECT id_establecimiento FROM establecimientos WHERE id_establecimiento = ?");
    $verificar->bind_param("i", $id);
    $verificar->execute();
    $resultado = $verificar->get_result();
    $verificar->close();

    if ($resultado->num_rows === 0) {
        header("Location: gestionEstablecimientos.php?error=noexists");
        exit();
    }

    // Verificar si hay usuarios asociados
    $check = $conexion->prepare("SELECT COUNT(*) FROM usuarios WHERE id_establecimiento = ?");
    $check->bind_param("i", $id);
    $check->execute();
    $check->bind_result($usuarios_count);
    $check->fetch();
    $check->close();

    if ($usuarios_count > 0) {
        header("Location: gestionEstablecimientos.php?error=asociado");
        exit();
    }

    // Eliminar establecimiento
    $stmt = $conexion->prepare("DELETE FROM establecimientos WHERE id_establecimiento = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        header("Location: gestionEstablecimientos.php?msg=deleted");
        exit();
    }
}

// --- LISTAR ESTABLECIMIENTOS ---
$establecimientos = $conexion->query("SELECT * FROM establecimientos ORDER BY nombre_establecimiento ASC");
$total_establecimientos = $establecimientos ? $establecimientos->num_rows : 0;

// --- ESTADÍSTICAS POR TIPO ---
$estadisticas_tipos = [
    'Urbano' => 0,
    'Polidocente' => 0,
    'Unidocente' => 0,
    'Jardín Infantil' => 0,
    'Otro' => 0
];

$tipos_query = $conexion->query("
    SELECT tipo_escuela, COUNT(*) AS cantidad 
    FROM establecimientos 
    GROUP BY tipo_escuela
");

if ($tipos_query) {
    while ($tipo = $tipos_query->fetch_assoc()) {
        $estadisticas_tipos[$tipo['tipo_escuela']] = $tipo['cantidad'];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Establecimientos | Sistema Educativo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" href="../img/logo.png">
    <link rel="stylesheet" href="../css/style_Establecimiento.css">
</head>
<body>
    <div class="container container-main py-4">
        <div class="header-main animate__animated animate__fadeInDown">
            <div>
                <h2><i class="fas fa-school"></i>Gestión de Establecimientos</h2>
                <p><i class="fas fa-map-marker-alt me-2"></i>Administra los establecimientos educativos del sistema</p>
            </div>
            <a href="menu.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                <span>Volver al Menú</span>
            </a>
        </div>
        <?php if (isset($_GET['msg'])): ?>
            <?php if ($_GET['msg'] === 'created'): ?>
                <div class="alert alert-success alert-custom animate__animated animate__slideInDown">
                    <i class="fas fa-check-circle me-3 fa-2x"></i>
                    <div>
                        <strong>¡Establecimiento creado exitosamente!</strong>
                        <p class="mb-0">El establecimiento ha sido registrado en el sistema.</p>
                    </div>
                </div>
            <?php elseif ($_GET['msg'] === 'deleted'): ?>
                <div class="alert alert-success alert-custom animate__animated animate__slideInDown">
                    <i class="fas fa-check-circle me-3 fa-2x"></i>
                    <div>
                        <strong>¡Establecimiento eliminado correctamente!</strong>
                        <p class="mb-0">El establecimiento ha sido eliminado del sistema.</p>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <?php if ($_GET['error'] === 'asociado'): ?>
                <div class="alert alert-danger alert-custom animate__animated animate__slideInDown">
                    <i class="fas fa-exclamation-triangle me-3 fa-2x"></i>
                    <div>
                        <strong>¡No se puede eliminar el establecimiento!</strong>
                        <p class="mb-0">El establecimiento tiene usuarios asociados. Debes reasignarlos primero.</p>
                    </div>
                </div>
            <?php elseif ($_GET['error'] === 'noexists'): ?>
                <div class="alert alert-danger alert-custom animate__animated animate__slideInDown">
                    <i class="fas fa-ban me-3 fa-2x"></i>
                    <div>
                        <strong>¡Establecimiento no encontrado!</strong>
                        <p class="mb-0">El establecimiento que intentas eliminar no existe o ya fue eliminado.</p>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Estadísticas Mejoradas -->
        <div class="stats-container animate__animated animate__fadeInUp">
            <div class="stat-card">
                <div class="stat-number"><?= $total_establecimientos ?></div>
                <div class="stat-label">
                    <i class="fas fa-school"></i>
                    Total Establecimientos
                </div>
            </div>
            <div class="stat-card urban">
                <div class="stat-number"><?= $estadisticas_tipos['Urbano'] ?></div>
                <div class="stat-label">
                    <i class="fas fa-city"></i>
                    Urbanos
                </div>
            </div>
            <div class="stat-card polidocente">
                <div class="stat-number"><?= $estadisticas_tipos['Polidocente'] ?></div>
                <div class="stat-label">
                    <i class="fas fa-users"></i>
                    Rural Polidocente
                </div>
            </div>
            <div class="stat-card unidocente">
                <div class="stat-number"><?= $estadisticas_tipos['Unidocente'] ?></div>
                <div class="stat-label">
                    <i class="fas fa-user"></i>
                    Rural Unidocente
                </div>
            </div>
            <div class="stat-card jardin">
                <div class="stat-number"><?= $estadisticas_tipos['Jardín Infantil'] ?></div>
                <div class="stat-label">
                    <i class="fas fa-child"></i>
                    Jardín Infantil
                </div>
            </div>
            <div class="stat-card otro">
                <div class="stat-number"><?= $estadisticas_tipos['Otro'] ?></div>
                <div class="stat-label">
                    <i class="fas fa-ellipsis-h"></i>
                    Otros
                </div>
            </div>
        </div>
        <div class="export-section animate__animated animate__fadeInUp">
            <div class="export-header">
                <h3><i class="fas fa-download"></i> Exportar Reportes</h3>
                <p>Genera reportes detallados de establecimientos en diferentes formatos</p>
            </div>
            
            <div class="export-grid">
                <!-- Exportar PDF -->
                <div class="export-card pdf-export">
                    <div class="export-icon">
                        <i class="fas fa-file-pdf"></i>
                    </div>
                    <div class="export-content">
                        <h4>Reporte en PDF</h4>
                        <p>Formato profesional optimizado para impresión y presentaciones</p>
                        <ul class="export-features">
                            <li><i class="fas fa-check-circle"></i> Diseño corporativo</li>
                            <li><i class="fas fa-check-circle"></i> Listo para imprimir</li>
                            <li><i class="fas fa-check-circle"></i> Incluye estadísticas</li>
                            <li><i class="fas fa-check-circle"></i> Tabla de datos</li>
                        </ul>
                    </div>
                    <div class="export-action">
                        <a href="../export/pdf_establecimiento.php" class="btn-export pdf-btn">
                            <i class="fas fa-file-pdf"></i>
                            Descargar PDF
                        </a>
                        <small class="export-info">
                            <i class="fas fa-bolt"></i> Generación instantánea
                        </small>
                    </div>
                </div>

                <!-- Exportar Excel -->
                <div class="export-card excel-export">
                    <div class="export-icon">
                        <i class="fas fa-file-excel"></i>
                    </div>
                    <div class="export-content">
                        <h4>Reporte en Excel</h4>
                        <p>Datos estructurados para análisis y procesamiento avanzado</p>
                        <ul class="export-features">
                            <li><i class="fas fa-check-circle"></i> Formato editable</li>
                            <li><i class="fas fa-check-circle"></i> Ideal para análisis</li>
                            <li><i class="fas fa-check-circle"></i> Filtros incluidos</li>
                            <li><i class="fas fa-check-circle"></i> Tablas dinámicas</li>
                        </ul>
                    </div>
                    <div class="export-action">
                        <a href="../export/excel_establecimiento.php" class="btn-export excel-btn">
                            <i class="fas fa-file-excel"></i>
                            Descargar Excel
                        </a>
                        <small class="export-info">
                            <i class="fas fa-bolt"></i> Generación instantánea
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Formulario de creación -->
        <div class="card card-custom animate__animated animate__fadeInUp">
            <div class="card-header-custom">
                <h5 class="mb-0">
                    <i class="fas fa-plus-circle"></i>
                    Registrar Nuevo Establecimiento
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label">
                                <i class="fas fa-school me-2 text-primary"></i>Nombre del Establecimiento
                            </label>
                            <input type="text" class="form-control" name="nombre" 
                                   placeholder="Ej: Liceo Nacional de Ovalle" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">
                                <i class="fas fa-tag me-2 text-primary"></i>Tipo de Escuela
                            </label>
                            <select name="tipo_escuela" class="form-select" required>
                                <option value="" disabled selected>Seleccionar tipo</option>
                                <option value="Urbano">🏙️ Urbano</option>
                                <option value="Polidocente">👥 Rural Polidocente</option>
                                <option value="Unidocente">👤 Rural Unidocente</option>
                                <option value="Jardín Infantil">🧸 Jardín Infantil</option>
                                <option value="Otro">📌 Otro</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">
                                <i class="fas fa-envelope me-2 text-primary"></i>Correo Electrónico
                            </label>
                            <input type="email" class="form-control" name="correo" 
                                   placeholder="contacto@establecimiento.cl">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">
                                <i class="fas fa-phone me-2 text-primary"></i>Teléfono de Contacto
                            </label>
                            <input type="text" class="form-control" name="telefono" 
                                   placeholder="+56 9 1234 5678">
                        </div>
                    </div>
                    <div class="text-end mt-4">
                        <button type="submit" name="crear" class="btn btn-primary-custom">
                            <i class="fas fa-save"></i>
                            Guardar Establecimiento
                        </button>
                        <button type="reset" class="btn btn-secondary ms-2">
                            <i class="fas fa-undo-alt"></i>
                            Limpiar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card card-custom animate__animated animate__fadeInUp">
            <div class="card-header-custom d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-list"></i>
                    Establecimientos Registrados
                </h5>
                <span class="badge bg-white text-primary px-3 py-2 rounded-pill">
                    <i class="fas fa-building me-1"></i>
                    <?= $total_establecimientos ?> establecimientos
                </span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-custom align-middle mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Director</th>
                                <th>Contacto</th>
                                <th>Tipo</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($total_establecimientos > 0): ?>
                                <?php while ($row = $establecimientos->fetch_assoc()): ?>
                                    <?php
                                    $badge_class = match($row['tipo_escuela']) {
                                        'Urbano' => 'badge-urban',
                                        'Polidocente' => 'badge-polidocente',
                                        'Unidocente' => 'badge-unidocente',
                                        'Jardín Infantil' => 'badge-jardin',
                                        default => 'badge-other'
                                    };
                                    
                                    $tipo_icon = match($row['tipo_escuela']) {
                                        'Urbano' => '🏙️',
                                        'Polidocente' => '👥',
                                        'Unidocente' => '👤',
                                        'Jardín Infantil' => '🧸',
                                        default => '📌'
                                    };
                                    ?>
                                    <tr>
                                        <td>
                                            <span class="fw-bold text-primary">#<?= str_pad($row['id_establecimiento'], 3, '0', STR_PAD_LEFT) ?></span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-circle bg-primary bg-gradient me-3">
                                                    <span class="text-white"><?= substr($row['nombre_establecimiento'], 0, 2) ?></span>
                                                </div>
                                                <div>
                                                    <strong><?= htmlspecialchars($row['nombre_establecimiento']) ?></strong>
                                                    <?php if (!empty($row['direccion'])): ?>
                                                        <br>
                                                        <small class="text-muted">
                                                            <i class="fas fa-map-marker-alt me-1"></i>
                                                            <?= htmlspecialchars($row['direccion']) ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if (!empty($row['director'])): ?>
                                                <i class="fas fa-user-tie me-2 text-primary"></i>
                                                <?= htmlspecialchars($row['director']) ?>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($row['correo'])): ?>
                                                <div>
                                                    <i class="fas fa-envelope me-2 text-primary"></i>
                                                    <?= htmlspecialchars($row['correo']) ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (!empty($row['telefono'])): ?>
                                                <div>
                                                    <i class="fas fa-phone me-2 text-primary"></i>
                                                    <?= htmlspecialchars($row['telefono']) ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (empty($row['correo']) && empty($row['telefono'])): ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge-custom <?= $badge_class ?>">
                                                <?= $tipo_icon ?> <?= htmlspecialchars($row['tipo_escuela']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="editar_establecimiento.php?id=<?= $row['id_establecimiento'] ?>" 
                                                   class="btn-action btn-edit"
                                                   title="Editar establecimiento">
                                                    <i class="fas fa-edit"></i>
                                                    Editar
                                                </a>
                                                <button type="button" 
                                                        class="btn-action btn-delete" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#deleteModal"
                                                        data-id="<?= $row['id_establecimiento'] ?>"
                                                        data-name="<?= htmlspecialchars($row['nombre_establecimiento']) ?>"
                                                        data-type="<?= htmlspecialchars($row['tipo_escuela']) ?>"
                                                        data-email="<?= htmlspecialchars($row['correo'] ?? 'No especificado') ?>"
                                                        data-phone="<?= htmlspecialchars($row['telefono'] ?? 'No especificado') ?>"
                                                        data-director="<?= htmlspecialchars($row['director'] ?? 'No especificado') ?>"
                                                        title="Eliminar establecimiento">
                                                    <i class="fas fa-trash"></i>
                                                    Eliminar
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="empty-state">
                                        <i class="fas fa-school"></i>
                                        <h5 class="mt-3">No hay establecimientos registrados</h5>
                                        <p class="text-muted">Agrega un nuevo establecimiento usando el formulario superior.</p>
                                        <button class="btn btn-primary-custom mt-3" onclick="document.querySelector('form').scrollIntoView({behavior: 'smooth'})">
                                            <i class="fas fa-plus-circle me-2"></i>
                                            Agregar Establecimiento
                                        </button>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-confirm">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">
                        <i class="fas fa-exclamation-triangle"></i>
                        Confirmar Eliminación
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="warning-icon">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <h4 class="mb-3">¿Estás seguro de eliminar este establecimiento?</h4>
                    <p class="text-muted mb-4">
                        Esta acción es irreversible. Se eliminarán permanentemente todos los datos asociados.
                    </p>
                    
                    <div class="establishment-info">
                        <div class="establishment-detail">
                            <span class="detail-label">
                                <i class="fas fa-school me-2"></i>Nombre:
                            </span>
                            <span class="detail-value" id="modal-establishment-name"></span>
                        </div>
                        <div class="establishment-detail">
                            <span class="detail-label">
                                <i class="fas fa-tag me-2"></i>Tipo:
                            </span>
                            <span class="detail-value" id="modal-establishment-type"></span>
                        </div>
                        <div class="establishment-detail">
                            <span class="detail-label">
                                <i class="fas fa-user-tie me-2"></i>Director:
                            </span>
                            <span class="detail-value" id="modal-establishment-director"></span>
                        </div>
                        <div class="establishment-detail">
                            <span class="detail-label">
                                <i class="fas fa-envelope me-2"></i>Correo:
                            </span>
                            <span class="detail-value" id="modal-establishment-email"></span>
                        </div>
                        <div class="establishment-detail">
                            <span class="detail-label">
                                <i class="fas fa-phone me-2"></i>Teléfono:
                            </span>
                            <span class="detail-value" id="modal-establishment-phone"></span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>
                        Cancelar
                    </button>
                    <a href="#" class="btn btn-confirm-delete" id="confirm-delete-btn">
                        <i class="fas fa-trash me-2"></i>
                        Eliminar Definitivamente
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
       
</body>
</html>