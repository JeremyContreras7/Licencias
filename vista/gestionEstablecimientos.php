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

// --- ESTADÍSTICAS POR TIPO (URBANO, RURAL POLIDOCENTE, RURAL UNIDOCENTE) ---
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
    <title>Gestión de Establecimientos</title>
    <link rel="stylesheet" href="../css/style_Establecimiento.css">
    <link rel="stylesheet" href="../css/reportes.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
</head>
<body>
    <div class="container container-main py-4">

        <!-- Encabezado -->
        <div class="header-main animate__animated animate__fadeIn">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="fas fa-school me-2"></i>Gestión de Establecimientos</h2>
                    <p class="mb-0">Administra los establecimientos educativos del sistema</p>
                </div>
                <a href="menu.php" class="btn back-btn">
                    <i class="fas fa-arrow-left me-1"></i> Volver al Menú
                </a>
            </div>
        </div>

        <!-- Mensajes de estado -->
        <?php if (isset($_GET['msg'])): ?>
            <?php if ($_GET['msg'] === 'created'): ?>
                <div class="alert alert-success alert-custom animate__animated animate__slideInDown mb-4">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>¡Éxito!</strong> Establecimiento creado correctamente.
                </div>
            <?php elseif ($_GET['msg'] === 'deleted'): ?>
                <div class="alert alert-success alert-custom animate__animated animate__slideInDown mb-4">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>¡Éxito!</strong> Establecimiento eliminado correctamente.
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <?php if ($_GET['error'] === 'asociado'): ?>
                <div class="alert alert-danger alert-custom animate__animated animate__slideInDown mb-4">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Error:</strong> No se puede eliminar el establecimiento porque tiene usuarios asociados.
                </div>
            <?php elseif ($_GET['error'] === 'noexists'): ?>
                <div class="alert alert-danger alert-custom animate__animated animate__slideInDown mb-4">
                    <i class="fas fa-ban me-2"></i>
                    <strong>Error:</strong> El establecimiento no existe o ya fue eliminado.
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Estadísticas -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-number"><?= $total_establecimientos ?></div>
                <div class="stat-label"><i class="fas fa-school me-1"></i>Total Establecimientos</div>
            </div>
            <div class="stat-card urban">
                <div class="stat-number"><?= $estadisticas_tipos['Urbano'] ?></div>
                <div class="stat-label"><i class="fas fa-city me-1"></i>Urbanos</div>
            </div>
            <div class="stat-card polidocente">
                <div class="stat-number"><?= $estadisticas_tipos['Polidocente'] ?></div>
                <div class="stat-label"><i class="fas fa-users me-1"></i>Rural Polidocente</div>
            </div>
            <div class="stat-card unidocente">
                <div class="stat-number"><?= $estadisticas_tipos['Unidocente'] ?></div>
                <div class="stat-label"><i class="fas fa-user me-1"></i>Rural Unidocente</div>
            </div>
            <div class="stat-card jardin">
                <div class="stat-number"><?= $estadisticas_tipos['Jardín Infantil'] ?></div>
                <div class="stat-label"><i class="fas fa-child me-1"></i>Jardín Infantil</div>
            </div>
            <div class="stat-card otro">
                <div class="stat-number"><?= $estadisticas_tipos['Otro'] ?></div>
                <div class="stat-label"><i class="fas fa-ellipsis-h me-1"></i>Otros</div>
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
                        <a href="../export/pdf_establecimiento.php" class="btn-export pdf-btn">
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
                        <a href="../export/excel_establecimiento.php" class="btn-export excel-btn">
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

        <!-- Formulario de creación -->
        <div class="card card-custom mb-4 animate__animated animate__fadeInUp">
            <div class="card-header-custom">
                <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Registrar Nuevo Establecimiento</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nombre del Establecimiento</label>
                            <input type="text" class="form-control" name="nombre" placeholder="Ej: Liceo Nacional de Ovalle" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tipo de Escuela</label>
                            <select name="tipo_escuela" class="form-select" required>
                                <option value="" disabled selected>Seleccionar tipo</option>
                                <option value="Urbano">Urbano</option>
                                <option value="Polidocente">Rural Polidocente</option>
                                <option value="Unidocente">Rural Unidocente</option>
                                <option value="Jardín Infantil">Jardín Infantil</option>
                                <option value="Otro">Otro</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Correo Electrónico</label>
                            <input type="email" class="form-control" name="correo" placeholder="contacto@establecimiento.cl">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Teléfono de Contacto</label>
                            <input type="text" class="form-control" name="telefono" placeholder="+56 9 1234 5678">
                        </div>
                    </div>
                    <div class="text-end mt-4">
                        <button type="submit" name="crear" class="btn btn-primary-custom">
                            <i class="fas fa-save me-1"></i>Guardar Establecimiento
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabla -->
        <div class="card card-custom animate__animated animate__fadeInUp">
            <div class="card-header-custom d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-list me-2"></i>Establecimientos Registrados</h5>
                <span class="badge bg-light text-dark fs-6"><?= $total_establecimientos ?> registros</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-custom align-middle mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Correo</th>
                                <th>Teléfono</th>
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
                                    ?>
                                    <tr>
                                        <td><strong class="text-primary">#<?= $row['id_establecimiento'] ?></strong></td>
                                        <td><i class="fas fa-school text-primary me-2"></i><?= htmlspecialchars($row['nombre_establecimiento']) ?></td>
                                        <td><?= $row['correo'] ? htmlspecialchars($row['correo']) : '—' ?></td>
                                        <td><?= $row['telefono'] ? htmlspecialchars($row['telefono']) : '—' ?></td>
                                        <td><span class="badge badge-custom <?= $badge_class ?>"><?= htmlspecialchars($row['tipo_escuela']) ?></span></td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="editar_establecimiento.php?id=<?= $row['id_establecimiento'] ?>" class="btn btn-action btn-edit">
                                                    <i class="fas fa-edit me-1"></i>Editar
                                                </a>
                                                <button type="button" class="btn btn-action btn-delete" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#deleteModal"
                                                        data-id="<?= $row['id_establecimiento'] ?>"
                                                        data-name="<?= htmlspecialchars($row['nombre_establecimiento']) ?>"
                                                        data-type="<?= htmlspecialchars($row['tipo_escuela']) ?>"
                                                        data-email="<?= htmlspecialchars($row['correo'] ?? 'No especificado') ?>"
                                                        data-phone="<?= htmlspecialchars($row['telefono'] ?? 'No especificado') ?>">
                                                    <i class="fas fa-trash me-1"></i>Eliminar
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
                                        <p class="text-muted">Agrega uno usando el formulario superior.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmación de Eliminación -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-confirm">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Confirmar Eliminación
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="warning-icon">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <h4 class="mb-3">¿Estás seguro de eliminar este establecimiento?</h4>
                    <p class="text-muted mb-4">Esta acción no se puede deshacer. Se eliminarán permanentemente todos los datos del establecimiento.</p>
                    
                    <div class="establishment-info">
                        <div class="establishment-detail">
                            <span class="detail-label">Nombre:</span>
                            <span class="detail-value" id="modal-establishment-name"></span>
                        </div>
                        <div class="establishment-detail">
                            <span class="detail-label">Tipo:</span>
                            <span class="detail-value" id="modal-establishment-type"></span>
                        </div>
                        <div class="establishment-detail">
                            <span class="detail-label">Correo:</span>
                            <span class="detail-value" id="modal-establishment-email"></span>
                        </div>
                        <div class="establishment-detail">
                            <span class="detail-label">Teléfono:</span>
                            <span class="detail-value" id="modal-establishment-phone"></span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancelar
                    </button>
                    <a href="#" class="btn btn-confirm-delete" id="confirm-delete-btn">
                        <i class="fas fa-trash me-1"></i>Eliminar Definitivamente
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/gestionEstablecimientos.js"></script>

</body>
</html>