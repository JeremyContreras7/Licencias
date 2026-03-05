<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== "ENCARGADO") {
    header("Location: ../index.php");
    exit();
}

include("../modelo/conexion.php");

$id_establecimiento = $_SESSION['id_establecimiento'];

// Validar ID recibido
if (!isset($_GET['id'])) {
    header("Location: gestionEquipos.php");
    exit();
}
$id_equipo = (int)$_GET['id'];

// Obtener datos del equipo
$stmt = $conexion->prepare("SELECT * FROM equipos WHERE id_equipo = ? AND id_establecimiento = ?");
$stmt->bind_param("ii", $id_equipo, $id_establecimiento);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<script>alert('❌ Equipo no encontrado.'); window.location='gestionEquipos.php';</script>";
    exit();
}

$equipo = $result->fetch_assoc();

// Variable para mensajes
$mensaje = '';

// Guardar cambios
if (isset($_POST['guardar'])) {
    $nombre_equipo = $conexion->real_escape_string($_POST['nombre_equipo']);
    $sistema_operativo = $conexion->real_escape_string($_POST['sistema_operativo']);
    $Modelo = $conexion->real_escape_string($_POST['Modelo']);
    $Numero_serial = $conexion->real_escape_string($_POST['Numero_serial']);
    $estado = $conexion->real_escape_string($_POST['estado']);

    // Verificar si el número serial ya existe en OTRO equipo
    $check_sql = "SELECT id_equipo FROM equipos WHERE Numero_serial = ? AND id_equipo != ?";
    $check_stmt = $conexion->prepare($check_sql);
    $check_stmt->bind_param("si", $Numero_serial, $id_equipo);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $mensaje = '<div class="alert error"><i class="fas fa-exclamation-triangle"></i> Error: El número serial "' . htmlspecialchars($Numero_serial) . '" ya está registrado en otro equipo.</div>';
    } else {
        // CORRECCIÓN: La consulta tiene 7 parámetros (5 campos + 2 condiciones)
        $update = $conexion->prepare("UPDATE equipos SET nombre_equipo = ?, sistema_operativo = ?, Modelo = ?, Numero_serial = ?, estado = ? WHERE id_equipo = ? AND id_establecimiento = ?");
        
        // CORRECCIÓN: "ssssssi" significa 6 strings y 1 integer (o "ssssisi" dependiendo de tus tipos de datos)
        // nombre_equipo (string), sistema_operativo (string), Modelo (string), Numero_serial (string), estado (string), id_equipo (int), id_establecimiento (int)
        $update->bind_param("sssssii", $nombre_equipo, $sistema_operativo, $Modelo, $Numero_serial, $estado, $id_equipo, $id_establecimiento);

        if ($update->execute()) {
            echo "<script>
                alert('✅ Equipo actualizado correctamente.');
                window.location='gestionEquipos.php?updated=1';
            </script>";
            exit();
        } else {
            $mensaje = '<div class="alert error"><i class="fas fa-exclamation-triangle"></i> Error al actualizar el equipo: ' . $conexion->error . '</div>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Equipo</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../css/editarEquipos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" href="../img/logo.png">
</head>
<body>
    <div class="page">
        <!-- Header Mejorado -->
        <header class="header">
            <div class="header-content">
                <div class="header-text">
                    <h1><i class="fas fa-edit"></i> Editar Equipo</h1>
                    <p>Modifica la información del equipo seleccionado</p>
                </div>
                <div class="header-actions">
                    <a class="back-btn" href="gestionEquipos.php">
                        <i class="fas fa-arrow-left"></i> Volver a Equipos
                    </a>
                    <div class="badge">
                        <i class="fas fa-building"></i>
                        Establecimiento: <?php echo htmlspecialchars($_SESSION['establecimiento'] ?? '—'); ?>
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
            <!-- Información del equipo -->
            <div class="equipo-info">
                <i class="fas fa-info-circle"></i> 
                Editando equipo <strong>#<?php echo $equipo['id_equipo']; ?></strong> 
                del establecimiento <strong><?php echo htmlspecialchars($_SESSION['establecimiento'] ?? '—'); ?></strong>
            </div>

            <!-- Formulario de Edición -->
            <section class="form-card">
                <form method="POST" action="">
                    <div class="form-grid">
                        <div class="field full-width">
                            <label for="nombre_equipo">
                                <i class="fas fa-desktop"></i> Nombre del equipo
                            </label>
                            <input id="nombre_equipo" type="text" name="nombre_equipo" 
                                   value="<?php echo htmlspecialchars($equipo['nombre_equipo']); ?>" 
                                   placeholder="Ej: Aula-Comp-01, Laboratorio-PC-02" required>
                        </div>

                        <div class="field full-width">
                            <label for="sistema_operativo">
                                <i class="fas fa-cog"></i> Sistema operativo
                            </label>
                            <textarea id="sistema_operativo" name="sistema_operativo" 
                                      placeholder="Windows 10 Pro, Ubuntu 22.04, etc." 
                                      rows="3"><?php echo htmlspecialchars($equipo['sistema_operativo']); ?></textarea>
                        </div>

                        <div class="field">
                            <label for="Modelo">
                                <i class="fas fa-laptop"></i> Modelo
                            </label>
                            <input id="Modelo" type="text" name="Modelo" 
                                   value="<?php echo htmlspecialchars($equipo['Modelo']); ?>" 
                                   placeholder="Expertbook, ThinkPad, etc.">
                        </div>
                        
                        <div class="field">
                            <label for="Numero_serial">
                                <i class="fas fa-barcode"></i> Número Serial
                            </label>
                            <input id="Numero_serial" type="text" name="Numero_serial" 
                                   value="<?php echo htmlspecialchars($equipo['Numero_serial']); ?>" 
                                   placeholder="3CMN8G21B" required>
                            <small style="color: #666; font-size: 12px; margin-top: 5px; display: block;">
                                <i class="fas fa-info-circle"></i> Este número debe ser único
                            </small>
                        </div>

                        <div class="field full-width">
                            <label for="estado">
                                <i class="fa-solid fa-computer"></i></i> Estado del Equipo
                            </label>
                            <select id="estado" name="estado" class="select-estado" required>
                                <option value="Disponible" <?php echo ($equipo['estado'] == 'Disponible') ? 'selected' : ''; ?>>🟢 Disponible</option>
                                <option value="En Uso" <?php echo ($equipo['estado'] == 'En Uso') ? 'selected' : ''; ?>>🔵 En Uso</option>
                                <option value="En Mantenimiento" <?php echo ($equipo['estado'] == 'En Mantenimiento') ? 'selected' : ''; ?>>🟡 En Mantenimiento</option>
                                <option value="Dañado" <?php echo ($equipo['estado'] == 'Dañado') ? 'selected' : ''; ?>>🔴 Dañado</option>
                                <option value="De Baja" <?php echo ($equipo['estado'] == 'De Baja') ? 'selected' : ''; ?>>⚫ De Baja</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="gestionEquipos.php" class="btn-cancel">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                        <button type="submit" name="guardar" class="btn-save">
                            <i class="fas fa-save"></i> Guardar Cambios
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </div>
</body>
</html>