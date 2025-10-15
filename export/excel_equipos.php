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
    $query = "SELECT e.*, est.nombre_establecimiento 
              FROM equipos e 
              LEFT JOIN establecimientos est ON e.id_establecimiento = est.id_establecimiento 
              WHERE e.id_establecimiento = ?";
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("i", $id_establecimiento);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $query = "SELECT e.*, est.nombre_establecimiento 
              FROM equipos e 
              LEFT JOIN establecimientos est ON e.id_establecimiento = est.id_establecimiento";
    $result = $conexion->query($query);
}

// Configurar headers para Excel
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="inventario_equipos_' . date('Y-m-d') . '.xls"');
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
        table { border-collapse: collapse; width: 100%; }
        th { background-color: #4361ee; color: white; font-weight: bold; padding: 8px; text-align: center; }
        td { border: 1px solid #ddd; padding: 8px; }
        tr:nth-child(even) { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h2>INVENTARIO DE EQUIPOS TECNOLÓGICOS</h2>
    <p><strong>Generado el:</strong> <?php echo date('d/m/Y H:i:s'); ?></p>
    <p><strong>Total de equipos:</strong> <?php echo $result->num_rows; ?></p>
    
    <table border="1">
        <thead>
            <tr>
                <th>ID</th>
                <th>NOMBRE EQUIPO</th>
                <th>SISTEMA OPERATIVO</th>
                <th>MODELO</th>
                <th>NÚMERO SERIAL</th>
                <th>ESTABLECIMIENTO</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td style="text-align: center;"><?php echo $row['id_equipo']; ?></td>
                <td><?php echo htmlspecialchars($row['nombre_equipo']); ?></td>
                <td><?php echo htmlspecialchars($row['sistema_operativo']); ?></td>
                <td><?php echo htmlspecialchars($row['Modelo']); ?></td>
                <td><?php echo htmlspecialchars($row['Numero_serial']); ?></td>
                <td><?php echo htmlspecialchars($row['nombre_establecimiento'] ?? 'N/A'); ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    
    <br>
    <p><em>Reporte generado automáticamente por el Sistema de Gestión</em></p>

</body>
</html>
<?php
exit;
?>