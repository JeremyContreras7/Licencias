<?php
session_start();
if (!isset($_SESSION['rol'])) {
    header("Location: ../index.php");
    exit();
}

include('../modelo/conexion.php');

// Obtener datos según el rol
$rol = $_SESSION['rol'];
$id_establecimiento = $_SESSION['id_establecimiento'] ?? null;

if ($rol === "ENCARGADO" && $id_establecimiento) {
    $query = "SELECT u.*, est.nombre_establecimiento 
              FROM usuarios u 
              LEFT JOIN establecimientos est ON u.id_establecimiento = est.id_establecimiento 
              WHERE u.id_establecimiento = ?";
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("i", $id_establecimiento);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $query = "SELECT u.*, est.nombre_establecimiento 
              FROM usuarios u 
              LEFT JOIN establecimientos est ON u.id_establecimiento = est.id_establecimiento";
    $result = $conexion->query($query);
}

// Configurar headers para Excel
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="lista_usuarios_' . date('Y-m-d') . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');

// BOM para UTF-8
echo "\xEF\xBB\xBF";

// Crear contenido HTML que Excel entenderá
?>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <style>
        table { border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; }
        th { background-color: #4361ee; color: white; font-weight: bold; padding: 10px; text-align: center; }
        td { border: 1px solid #ddd; padding: 8px; }
        tr:nth-child(even) { background-color: #f8f9fa; }
        .text-left { text-align: left; }
        .text-center { text-align: center; }
        .role-admin { background-color: #e8f5e8; color: #2e7d32; }
        .role-encargado { background-color: #e3f2fd; color: #1565c0; }
        .role-usuario { background-color: #fff3e0; color: #ef6c00; }
    </style>
</head>
<body>
    <h2>LISTADO DE USUARIOS DEL SISTEMA</h2>
    <p><strong>Generado el:</strong> <?php echo date('d/m/Y H:i:s'); ?></p>
    <p><strong>Total de usuarios:</strong> <?php echo $result->num_rows; ?></p>
    <p><strong>Generado por:</strong> <?php echo htmlspecialchars($_SESSION['nombre'] ?? 'Sistema'); ?> (<?php echo htmlspecialchars($_SESSION['rol'] ?? 'N/A'); ?>)</p>
    
    <table border="1">
        <thead>
            <tr>
                <th>ID</th>
                <th>NOMBRE COMPLETO</th>
                <th>CORREO ELECTRÓNICO</th>
                <th>ROL</th>
                <th>TIPO ENCARGADO</th>
                <th>ESTABLECIMIENTO</th>
                <th>FECHA REGISTRO</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td class="text-center"><?php echo $row['id_usuario']; ?></td>
                <td class="text-left"><?php echo htmlspecialchars($row['nombre']); ?></td>
                <td class="text-left"><?php echo htmlspecialchars($row['correo']); ?></td>
                <td class="text-center 
                    <?php 
                    switch($row['rol']) {
                        case 'ADMIN': echo 'role-admin'; break;
                        case 'ENCARGADO': echo 'role-encargado'; break;
                        case 'USUARIO': echo 'role-usuario'; break;
                    }
                    ?>">
                    <?php 
                    $rolText = '';
                    switch($row['rol']) {
                        case 'ADMIN': $rolText = '👑 ADMINISTRADOR'; break;
                        case 'ENCARGADO': $rolText = '💻 ENCARGADO'; break;
                        case 'USUARIO': $rolText = '👤 USUARIO'; break;
                        default: $rolText = htmlspecialchars($row['rol']);
                    }
                    echo $rolText; 
                    ?>
                </td>
                <td class="text-center">
                    <?php 
                    if ($row['rol'] === 'USUARIO' && !empty($row['tipo_encargado'])) {
                        $tipoText = '';
                        switch($row['tipo_encargado']) {
                            case 'INFORMATICA': $tipoText = '💻 Informática'; break;
                            case 'ACADEMICA': $tipoText = '📚 Académica'; break;
                            case 'ADMINISTRATIVA': $tipoText = '📊 Administrativa'; break;
                            case 'DIRECCION': $tipoText = '👨‍💼 Dirección'; break;
                            case 'CONVIVENCIA': $tipoText = '🤝 Convivencia'; break;
                            default: $tipoText = htmlspecialchars($row['tipo_encargado']);
                        }
                        echo $tipoText;
                    } else {
                        echo '—';
                    }
                    ?>
                </td>
                <td class="text-left"><?php echo htmlspecialchars($row['nombre_establecimiento'] ?? 'No asignado'); ?></td>
                <td class="text-center"><?php echo date('d/m/Y H:i', strtotime($row['fecha_registro'])); ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    
    <br>
    
    <br>
    <p style="font-style: italic; color: #666;">
        <strong>Nota:</strong> Este reporte fue generado automáticamente por el Sistema de Gestión Educativa.
        Los datos mostrados corresponden al estado actual de la base de datos.
    </p>

</body>
</html>
<?php
// Cerrar conexión si se usó prepared statement
if (isset($stmt)) {
    $stmt->close();
}
$conexion->close();
exit;
?>