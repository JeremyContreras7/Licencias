<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== "ENCARGADO") {
    header("Location: ../index.php");
    exit();
}

include("../modelo/conexion.php");

// --- Obtener ID del software a editar ---
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: gestionSoftware.php");
    exit();
}

$id = (int)$_GET['id'];
$id_establecimiento = $_SESSION['id_establecimiento'];

// --- Cargar datos actuales ---
$sql = "SELECT * FROM software WHERE id_software = $id AND id_establecimiento = $id_establecimiento LIMIT 1";
$result = $conexion->query($sql);

if (!$result || $result->num_rows == 0) {
    echo "<script>alert('❌ Software no encontrado.'); window.location='gestionSoftware.php';</script>";
    exit();
}

$software = $result->fetch_assoc();

// Variable para mensajes
$mensaje = '';

// --- Procesar actualización ---
if (isset($_POST['actualizar'])) {
    $nombre_software = $conexion->real_escape_string($_POST['nombre_software']);
    $version = $conexion->real_escape_string($_POST['version']);
    $es_critico = isset($_POST['es_critico']) ? 1 : 0;

    $sqlUpdate = "
        UPDATE software 
        SET nombre_software = '$nombre_software',
            version = '$version',
            es_critico = $es_critico
        WHERE id_software = $id AND id_establecimiento = $id_establecimiento
    ";

    if ($conexion->query($sqlUpdate)) {
        echo "<script>
            alert('✅ Software actualizado correctamente');
            window.location='gestionSoftware.php?success=1';
        </script>";
        exit();
    } else {
        $mensaje = '<div class="alert error"><i class="fas fa-exclamation-triangle"></i> Error al actualizar el software: ' . $conexion->error . '</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Software - <?php echo htmlspecialchars($software['nombre_software']); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../css/editarsoftware.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" href="../img/logo.png">
</head>
<body>
    <div class="page">
        <!-- Header Mejorado -->
        <header class="header">
            <div class="header-content">
                <div class="header-text">
                    <h1><i class="fas fa-edit"></i> Editar Software</h1>
                    <p>Modifica la información del software seleccionado</p>
                </div>
                <div class="header-actions">
                    <a class="back-btn" href="gestionSoftware.php">
                        <i class="fas fa-arrow-left"></i> Volver a Software
                    </a>
                    <div class="badge">
                        <i class="fas fa-building"></i>
                        <?php echo htmlspecialchars($_SESSION['establecimiento'] ?? '—'); ?>
                    </div>
                </div>
            </div>
        </header>

        <!-- Mostrar mensajes -->
        <?php if (!empty($mensaje)): ?>
            <div class="message-container">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <div class="edit-container">
            <!-- Tarjeta de información del software -->
            <div class="software-card">
                <div class="software-header">
                    <div class="software-icon">
                        <i class="fas fa-cube"></i>
                    </div>
                    <div class="software-details">
                        <h2><?php echo htmlspecialchars($software['nombre_software']); ?></h2>
                        <div class="software-meta">
                            <span class="version">Versión: <?php echo htmlspecialchars($software['version'] ?: 'No especificada'); ?></span>
                            <span class="status <?php echo $software['es_critico'] ? 'critico' : 'normal'; ?>">
                                <i class="fas fa-<?php echo $software['es_critico'] ? 'exclamation-triangle' : 'check-circle'; ?>"></i>
                                <?php echo $software['es_critico'] ? 'Software Crítico' : 'Software Normal'; ?>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="software-info">
                    <div class="info-item">
                        <i class="fas fa-hashtag"></i>
                        <strong>ID:</strong> #<?php echo $software['id_software']; ?>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-building"></i>
                        <strong>Establecimiento:</strong> <?php echo htmlspecialchars($_SESSION['establecimiento'] ?? '—'); ?>
                    </div>
                </div>
            </div>

            <!-- Formulario de Edición -->
            <section class="form-card">
                <div class="form-header">
                    <h3><i class="fas fa-sliders-h"></i> Configuración del Software</h3>
                    <p>Actualiza la información según tus necesidades</p>
                </div>
                
                <form method="POST" action="" id="softwareForm">
                    <div class="form-grid">
                        <div class="field">
                            <label for="nombre_software">
                                <i class="fas fa-cube"></i> 
                                <span>Nombre del Software</span>
                                <span class="required">*</span>
                            </label>
                            <div class="input-container">
                                <input id="nombre_software" type="text" name="nombre_software" 
                                       value="<?php echo htmlspecialchars($software['nombre_software']); ?>" 
                                       placeholder="Ej: Microsoft Office, Adobe Photoshop" required>
                                <i class="fas fa-check validation-icon valid"></i>
                                <i class="fas fa-times validation-icon invalid"></i>
                            </div>
                        </div>

                        <div class="field">
                            <label for="version">
                                <i class="fas fa-code-branch"></i> 
                                <span>Versión</span>
                            </label>
                            <div class="input-container">
                                <input id="version" type="text" name="version" 
                                       value="<?php echo htmlspecialchars($software['version']); ?>" 
                                       placeholder="Ej: 2021, 2.0, v3.5.1">
                                <i class="fas fa-tag input-icon"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Checkbox para software crítico -->
                    <div class="feature-toggle">
                        <div class="toggle-header">
                            <i class="fas fa-shield-alt"></i>
                            <h4>Configuración de Seguridad</h4>
                        </div>
                        <div class="toggle-content">
                            <div class="checkbox-card <?php echo $software['es_critico'] ? 'active' : ''; ?>" id="criticoCard">
                                <div class="checkbox-main">
                                    <input type="checkbox" id="es_critico" name="es_critico" value="1" 
                                           <?php echo $software['es_critico'] ? "checked" : ""; ?>>
                                    <div class="custom-checkbox">
                                        <div class="checkmark"></div>
                                    </div>
                                    <div class="checkbox-content">
                                        <label for="es_critico" class="checkbox-label">
                                            <span class="label-title">Marcar como software crítico</span>
                                            <span class="label-description">
                                                Este software será monitoreado más estrechamente y generará alertas prioritarias
                                            </span>
                                        </label>
                                    </div>
                                </div>
                                <?php if ($software['es_critico']): ?>
                                <div class="critico-indicator">
                                    <i class="fas fa-bell"></i>
                                    <span>Actualmente configurado como crítico</span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="gestionSoftware.php" class="btn-cancel">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                        <button type="submit" name="actualizar" class="btn-save">
                            <i class="fas fa-save"></i> Guardar Cambios
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </div>

    <script src="../js/editarsoftware.js"></script>
</body>
</html>