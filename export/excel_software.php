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
    $query = "SELECT s.*, est.nombre_establecimiento 
              FROM software s 
              LEFT JOIN establecimientos est ON s.id_establecimiento = est.id_establecimiento 
              WHERE s.id_establecimiento = ?";
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("i", $id_establecimiento);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $query = "SELECT s.*, est.nombre_establecimiento 
              FROM software s 
              LEFT JOIN establecimientos est ON s.id_establecimiento = est.id_establecimiento";
    $result = $conexion->query($query);
}

// Configurar headers para Excel
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="inventario_software_' . date('Y-m-d') . '.xls"');
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
        .critico-si { background-color: #ffeaa7; color: #e17055; font-weight: bold; }
        .critico-no { background-color: #55efc4; color: #00b894; }
        .estado-activo { background-color: #d5f5e3; color: #27ae60; }
    </style>
</head>
<body>
    <h2>INVENTARIO DE SOFTWARE INSTALADO</h2>
    <p><strong>Generado el:</strong> <?php echo date('d/m/Y H:i:s'); ?></p>
    <p><strong>Total de software registrado:</strong> <?php echo $result->num_rows; ?></p>
    <p><strong>Generado por:</strong> <?php echo htmlspecialchars($_SESSION['nombre'] ?? 'Sistema'); ?> (<?php echo htmlspecialchars($_SESSION['rol'] ?? 'N/A'); ?>)</p>
    
    <table border="1">
        <thead>
            <tr>
                <th>ID</th>
                <th>NOMBRE DEL SOFTWARE</th>
                <th>VERSIÓN</th>
                <th>CRÍTICO</th>
                <th>ESTABLECIMIENTO</th>
                <th>ESTADO</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $totalCritico = 0;
            $totalNoCritico = 0;
            
            while ($row = $result->fetch_assoc()): 
                if ($row['es_critico'] == 1) {
                    $totalCritico++;
                } else {
                    $totalNoCritico++;
                }
            ?>
            <tr>
                <td class="text-center"><?php echo $row['id_software']; ?></td>
                <td class="text-left">
                    <strong><?php echo htmlspecialchars($row['nombre_software']); ?></strong>
                </td>
                <td class="text-center">
                    <?php if (!empty($row['version'])): ?>
                        <code style="background: #f1f3f4; padding: 3px 6px; border-radius: 3px;">
                            v<?php echo htmlspecialchars($row['version']); ?>
                        </code>
                    <?php else: ?>
                        <span style="color: #999;">—</span>
                    <?php endif; ?>
                </td>
                <td class="text-center <?php echo $row['es_critico'] == 1 ? 'critico-si' : 'critico-no'; ?>">
                    <?php if ($row['es_critico'] == 1): ?>
                        <i class="fas fa-exclamation-triangle"></i> SÍ
                    <?php else: ?>
                        <i class="fas fa-check-circle"></i> NO
                    <?php endif; ?>
                </td>
                <td class="text-left">
                    <i class="fas fa-building" style="color: #7f8c8d; margin-right: 5px;"></i>
                    <?php echo htmlspecialchars($row['nombre_establecimiento'] ?? 'No asignado'); ?>
                </td>
                <td class="text-center estado-activo">
                    <i class="fas fa-check"></i> ACTIVO
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    
    <br>
    <div style="margin-top: 20px; padding: 15px; background-color: #f8f9fa; border-radius: 5px;">
        <h3>📊 Resumen del Inventario de Software</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 10px;">
            <div style="text-align: center; padding: 10px; background: white; border-radius: 5px; border-left: 4px solid #4361ee;">
                <div style="font-size: 24px; font-weight: bold; color: #4361ee;"><?php echo $result->num_rows; ?></div>
                <div style="font-size: 14px; color: #666;">Total Software</div>
            </div>
            <div style="text-align: center; padding: 10px; background: white; border-radius: 5px; border-left: 4px solid #e74c3c;">
                <div style="font-size: 24px; font-weight: bold; color: #e74c3c;"><?php echo $totalCritico; ?></div>
                <div style="font-size: 14px; color: #666;">Software Crítico</div>
            </div>
            <div style="text-align: center; padding: 10px; background: white; border-radius: 5px; border-left: 4px solid #27ae60;">
                <div style="font-size: 24px; font-weight: bold; color: #27ae60;"><?php echo $totalNoCritico; ?></div>
                <div style="font-size: 14px; color: #666;">Software Normal</div>
            </div>
            <div style="text-align: center; padding: 10px; background: white; border-radius: 5px; border-left: 4px solid #f39c12;">
                <div style="font-size: 24px; font-weight: bold; color: #f39c12;">
                    <?php echo $result->num_rows > 0 ? round(($totalCritico / $result->num_rows) * 100, 1) : 0; ?>%
                </div>
                <div style="font-size: 14px; color: #666;">Porcentaje Crítico</div>
            </div>
        </div>
    </div>

    <br>
    <div style="background-color: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px; padding: 15px; margin-top: 20px;">
        <h4 style="color: #856404; margin-top: 0;">
            <i class="fas fa-info-circle"></i> Información Importante
        </h4>
        <ul style="color: #856404; margin-bottom: 0;">
            <li><strong>Software Crítico:</strong> Aplicaciones esenciales para el funcionamiento operativo</li>
            <li><strong>Software Normal:</strong> Aplicaciones de uso general o complementarias</li>
            <li>Este inventario debe actualizarse periódicamente según las instalaciones nuevas</li>
            <li>Verificar la compatibilidad de versiones con los equipos disponibles</li>
        </ul>
    </div>
    
    <br>
    <p style="font-style: italic; color: #666; border-top: 1px solid #ddd; padding-top: 15px;">
        <strong>Nota:</strong> Este reporte fue generado automáticamente por el Sistema de Gestión Educativa.
        Incluye todo el software registrado en el sistema con su estado actual.
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