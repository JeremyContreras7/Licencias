<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== "ENCARGADO") {
    header("Location: ../index.php");
    exit();
}

include("../modelo/conexion.php");

// Obtener el establecimiento del usuario logueado
$id_establecimiento = $_SESSION['id_establecimiento'];

// --- CREAR SOFTWARE ---
if (isset($_POST['crear'])) {
    $nombre_software = $conexion->real_escape_string($_POST['nombre_software']);
    $version = $conexion->real_escape_string($_POST['version']);
    $es_critico = isset($_POST['es_critico']) ? 1 : 0;

    $sql = "INSERT INTO software (nombre_software, version, es_critico, id_establecimiento) 
            VALUES ('$nombre_software', '$version', $es_critico, $id_establecimiento)";
    $conexion->query($sql);
    header("Location: gestionSoftware.php");
    exit();
}

// --- ELIMINAR SOFTWARE ---
if (isset($_GET['eliminar'])) {
    $id = (int)$_GET['eliminar'];
    // Elimina solo si pertenece al establecimiento del encargado
    $conexion->query("DELETE FROM software WHERE id_software=$id AND id_establecimiento=$id_establecimiento");
    header("Location: gestionSoftware.php");
    exit();
}

// --- LISTAR SOFTWARE SOLO DEL ESTABLECIMIENTO ---
$software = $conexion->query("
    SELECT s.*, e.nombre_establecimiento 
    FROM software s
    INNER JOIN establecimientos e ON s.id_establecimiento = e.id_establecimiento
    WHERE s.id_establecimiento = $id_establecimiento
    ORDER BY s.id_software DESC
");

// Contar software para estadísticas
$total_software = $software->num_rows;
$software_critico = $conexion->query("SELECT COUNT(*) as count FROM software WHERE id_establecimiento = $id_establecimiento AND es_critico = 1")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Software</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../css/style_software.css">
    <link rel="stylesheet" href="../css/reportes.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" href="../img/logo.png">
</head>
<body>
    <div class="page">
        <!-- Header Mejorado -->
        <header class="header">
            <div class="header-content">
                <div class="header-text">
                    <h1><i class="fas fa-boxes"></i> Gesti&oacute;n de Software</h1>
                    <p>Registra y administra el software disponible en tu establecimiento</p>
                </div>
                <div class="header-actions">
                    <a class="back-btn" href="menu_informatico.php">
                        <i class="fas fa-arrow-left"></i> Volver al Men&uacute;
                    </a>
                    <div class="user-badge">
                        <i class="fas fa-user-cog"></i>
                        Encargado: <?php echo htmlspecialchars($_SESSION['nombre'] ?? '—'); ?>
                    </div>
                </div>
            </div>
        </header>

        <!-- Tarjetas de Estadísticas -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="number"><?php echo $total_software; ?></div>
                <div class="label">Total de Software</div>
            </div>
            <div class="stat-card">
                <div class="number" style="color: var(--danger);"><?php echo $software_critico; ?></div>
                <div class="label">Software Crítico</div>
            </div>
            <div class="stat-card">
                <div class="number" style="color: var(--success);"><?php echo $total_software - $software_critico; ?></div>
                <div class="label">Software Normal</div>
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
                        <a href="../export/pdf_software.php" class="btn-export pdf-btn">
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
                        <a href="../export/excel_software.php" class="btn-export excel-btn">
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


        <!-- Formulario de Registro -->
        <section class="form-card">
            <h2><i class="fas fa-plus-circle"></i> Registrar Nuevo Software</h2>
            <form method="POST" action="">
                <div class="form-grid">
                    <div class="field">
                        <label for="nombre_software">
                            <i class="fas fa-cube"></i> Nombre del Software
                        </label>
                        <input id="nombre_software" type="text" name="nombre_software" 
                               placeholder="Ej: Microsoft Office, Adobe Photoshop, AutoCAD" required>
                    </div>

                    <div class="field">
                        <label for="version">
                            <i class="fas fa-code-branch"></i> Versión
                        </label>
                        <input id="version" type="text" name="version" 
                               placeholder="Ej: 2023, 2.0, Professional Edition">
                    </div>

                    <div class="field">
                        <div class="checkbox-group">
                            <input type="checkbox" id="es_critico" name="es_critico" value="1">
                            <label for="es_critico">
                                <i class="fas fa-exclamation-triangle"></i> Marcar como software crítico
                            </label>
                        </div>
                        <small style="color: var(--gray-600); margin-top: 8px; display: block;">
                            El software crítico requiere atención prioritaria y licencias siempre activas.
                        </small>
                    </div>
                </div>

                <div class="btn-group">
                    <button type="submit" name="crear" class="btn-primary">
                        <i class="fas fa-save"></i> Registrar Software
                    </button>
                    <a href="gestionSoftware.php" class="btn-outline">
                        <i class="fas fa-broom"></i> Limpiar Formulario
                    </a>
                </div>
            </form>
        </section>

        <!-- Lista de Software -->
        <section class="table-container">
            <div class="table-header">
                <h2 class="section-title">
                    <i class="fas fa-list"></i> Software Registrado
                </h2>
                <div class="count-badge">
                    <i class="fas fa-box"></i> <?php echo $total_software; ?> programas
                </div>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Versión</th>
                            <th class="center">Estado</th>
                            <th class="center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($software && $software->num_rows > 0): ?>
                            <?php while($row = $software->fetch_assoc()): ?>
                                <tr>
                                    <td><strong>#<?= $row['id_software'] ?></strong></td>
                                    <td>
                                        <i class="fas fa-cube" style="color: var(--primary); margin-right: 10px;"></i>
                                        <strong><?= htmlspecialchars($row['nombre_software']) ?></strong>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['version'])): ?>
                                            <span style="background: var(--gray-100); padding: 4px 8px; border-radius: 6px; font-family: monospace;">
                                                v<?= htmlspecialchars($row['version']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="color: var(--gray-500);">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="center">
                                        <?php if ($row['es_critico']): ?>
                                            <span class="badge-critical">
                                                <i class="fas fa-exclamation-triangle"></i> Crítico
                                            </span>
                                        <?php else: ?>
                                            <span class="badge-normal">
                                                <i class="fas fa-check-circle"></i> Normal
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="center actions">
                                        <a href="editar_software.php?id=<?= $row['id_software'] ?>" class="btn-edit">
                                            <i class="fas fa-edit"></i> Editar
                                        </a>
                                        <a href="gestionSoftware.php?eliminar=<?= $row['id_software'] ?>" class="btn-delete" onclick="return confirm('¿Estás seguro de eliminar este software?\n\nEsta acción no se puede deshacer.')">
                                            <i class="fas fa-trash"></i> Eliminar
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="empty-state">
                                    <i class="fas fa-box-open"></i>
                                    <p>No hay software registrado</p>
                                    <p class="subtext">Comienza agregando el primer software utilizando el formulario superior.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
<script src="../js/gestionSoftware.js"></script>

</body>
</html>