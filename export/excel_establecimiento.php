<?php
session_start();
if (!isset($_SESSION['rol'])) {
    header("Location: ../index.php");
    exit();
}

include('../modelo/conexion.php');

// Solo administradores pueden ver todos los establecimientos
if ($_SESSION['rol'] !== "ADMIN") {
    header("Location: ../index.php?error=permisos");
    exit();
}

// Obtener todos los establecimientos
$query = "SELECT * FROM establecimientos ORDER BY nombre_establecimiento ASC";
$result = $conexion->query($query);

// Configurar headers para Excel
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="directorio_establecimientos_' . date('Y-m-d') . '.xls"');
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
        th { background-color: #4361ee; color: white; font-weight: bold; padding: 12px; text-align: center; }
        td { border: 1px solid #ddd; padding: 10px; }
        tr:nth-child(even) { background-color: #f8f9fa; }
        .text-left { text-align: left; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .tipo-urbana { background-color: #e3f2fd; color: #1565c0; }
        .tipo-rural { background-color: #e8f5e8; color: #2e7d32; }
        .tipo-mixta { background-color: #fff3e0; color: #ef6c00; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: bold; }
        .contact-info { font-size: 13px; color: #555; }
    </style>
</head>
<body>
    <h2>DIRECTORIO DE ESTABLECIMIENTOS EDUCATIVOS</h2>
    <p><strong>Generado el:</strong> <?php echo date('d/m/Y H:i:s'); ?></p>
    <p><strong>Total de establecimientos:</strong> <?php echo $result->num_rows; ?></p>
    <p><strong>Generado por:</strong> <?php echo htmlspecialchars($_SESSION['nombre'] ?? 'Sistema'); ?> (Administrador)</p>
    
    <table border="1">
        <thead>
            <tr>
                <th>ID</th>
                <th>NOMBRE DEL ESTABLECIMIENTO</th>
                <th>CORREO ELECTRÓNICO</th>
                <th>TELÉFONO</th>
                <th>TIPO DE ESCUELA</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $totalUrbana = 0;
            $totalRural = 0;
            $totalMixta = 0;
            $totalConContacto = 0;
            
            while ($row = $result->fetch_assoc()): 
                // Contar por tipo de escuela
                switch(strtoupper($row['tipo_escuela'])) {
                    case 'URBANA': $totalUrbana++; break;
                    case 'RURAL': $totalRural++; break;
                    case 'MIXTA': $totalMixta++; break;
                }
                
                // Contar establecimientos con información de contacto completa
                if (!empty($row['correo']) && !empty($row['telefono'])) {
                    $totalConContacto++;
                }
            ?>
            <tr>
                <td class="text-center">
                    <strong>#<?php echo $row['id_establecimiento']; ?></strong>
                </td>
                <td class="text-left">
                    <strong style="font-size: 14px;"><?php echo htmlspecialchars($row['nombre_establecimiento']); ?></strong>
                </td>
                <td class="text-left">
                    <?php if (!empty($row['correo'])): ?>
                        <span class="contact-info">
                            <i class="fas fa-envelope"></i> 
                            <?php echo htmlspecialchars($row['correo']); ?>
                        </span>
                    <?php else: ?>
                        <span style="color: #999; font-style: italic;">No registrado</span>
                    <?php endif; ?>
                </td>
                <td class="text-center">
                    <?php if (!empty($row['telefono'])): ?>
                        <span class="contact-info">
                            <i class="fas fa-phone"></i> 
                            <?php echo htmlspecialchars($row['telefono']); ?>
                        </span>
                    <?php else: ?>
                        <span style="color: #999; font-style: italic;">No registrado</span>
                    <?php endif; ?>
                </td>
                <td class="text-center 
                    <?php 
                    $tipo = strtoupper($row['tipo_escuela']);
                    switch($tipo) {
                        case 'URBANA': echo 'tipo-urbana'; break;
                        case 'RURAL': echo 'tipo-rural'; break;
                        case 'MIXTA': echo 'tipo-mixta'; break;
                        default: echo '';
                    }
                    ?>">
                    <?php 
                    $tipoText = '';
                    switch($tipo) {
                        case 'URBANA': $tipoText = '🏙️ URBANA'; break;
                        case 'RURAL': $tipoText = '🌄 RURAL'; break;
                        case 'MIXTA': $tipoText = '🏘️ MIXTA'; break;
                        default: $tipoText = htmlspecialchars($row['tipo_escuela'] ?? 'No especificado');
                    }
                    echo $tipoText; 
                    ?>
                </td>
                <td class="text-center">
                    <?php
                    $contactoCompleto = !empty($row['correo']) && !empty($row['telefono']);
                    $soloCorreo = !empty($row['correo']) && empty($row['telefono']);
                    $soloTelefono = empty($row['correo']) && !empty($row['telefono']);
                    $sinContacto = empty($row['correo']) && empty($row['telefono']);
                    
                    if ($contactoCompleto): ?>
                        <span class="badge" style="background: #27ae60; color: white;">
                            <i class="fas fa-check-circle"></i> Contacto Completo
                        </span>
                    <?php elseif ($soloCorreo): ?>
                        <span class="badge" style="background: #3498db; color: white;">
                            <i class="fas fa-envelope"></i> Solo Correo
                        </span>
                    <?php elseif ($soloTelefono): ?>
                        <span class="badge" style="background: #3498db; color: white;">
                            <i class="fas fa-phone"></i> Solo Teléfono
                        </span>
                    <?php else: ?>
                        <span class="badge" style="background: #e74c3c; color: white;">
                            <i class="fas fa-exclamation-triangle"></i> Sin Contacto
                        </span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    
    <br>
    <div style="margin-top: 20px; padding: 20px; background-color: #f8f9fa; border-radius: 8px;">
        <h3>📈 Estadísticas del Directorio</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; margin-top: 15px;">
            <div style="text-align: center; padding: 15px; background: white; border-radius: 8px; border-left: 4px solid #4361ee;">
                <div style="font-size: 28px; font-weight: bold; color: #4361ee;"><?php echo $result->num_rows; ?></div>
                <div style="font-size: 14px; color: #666;">Total Establecimientos</div>
            </div>
            <div style="text-align: center; padding: 15px; background: white; border-radius: 8px; border-left: 4px solid #1565c0;">
                <div style="font-size: 28px; font-weight: bold; color: #1565c0;"><?php echo $totalUrbana; ?></div>
                <div style="font-size: 14px; color: #666;">Escuelas Urbanas</div>
            </div>
            <div style="text-align: center; padding: 15px; background: white; border-radius: 8px; border-left: 4px solid #2e7d32;">
                <div style="font-size: 28px; font-weight: bold; color: #2e7d32;"><?php echo $totalRural; ?></div>
                <div style="font-size: 14px; color: #666;">Escuelas Rurales</div>
            </div>
            <div style="text-align: center; padding: 15px; background: white; border-radius: 8px; border-left: 4px solid #ef6c00;">
                <div style="font-size: 28px; font-weight: bold; color: #ef6c00;"><?php echo $totalMixta; ?></div>
                <div style="font-size: 14px; color: #666;">Escuelas Mixtas</div>
            </div>
            <div style="text-align: center; padding: 15px; background: white; border-radius: 8px; border-left: 4px solid #27ae60;">
                <div style="font-size: 28px; font-weight: bold; color: #27ae60;"><?php echo $totalConContacto; ?></div>
                <div style="font-size: 14px; color: #666;">Contacto Completo</div>
            </div>
            <div style="text-align: center; padding: 15px; background: white; border-radius: 8px; border-left: 4px solid #e74c3c;">
                <div style="font-size: 28px; font-weight: bold; color: #e74c3c;">
                    <?php echo $result->num_rows > 0 ? round(($totalConContacto / $result->num_rows) * 100, 1) : 0; ?>%
                </div>
                <div style="font-size: 14px; color: #666;">Con Contacto Completo</div>
            </div>
        </div>
    </div>

    <br>
    <div style="background-color: #e3f2fd; border: 1px solid #bbdefb; border-radius: 8px; padding: 20px; margin-top: 20px;">
        <h4 style="color: #1565c0; margin-top: 0;">
            <i class="fas fa-info-circle"></i> Información del Directorio
        </h4>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px; color: #1565c0;">
            <div>
                <strong><i class="fas fa-building"></i> Establecimientos Urbanos:</strong><br>
                Escuelas ubicadas en zonas urbanas con mayor densidad poblacional
            </div>
            <div>
                <strong><i class="fas fa-tree"></i> Establecimientos Rurales:</strong><br>
                Escuelas en zonas rurales con acceso limitado a servicios
            </div>
            <div>
                <strong><i class="fas fa-city"></i> Establecimientos Mixtos:</strong><br>
                Escuelas que combinan características urbanas y rurales
            </div>
        </div>
    </div>
    
    <br>
    <p style="font-style: italic; color: #666; border-top: 1px solid #ddd; padding-top: 15px;">
        <strong>Nota:</strong> Este directorio fue generado automáticamente por el Sistema de Gestión Educativa.
        Contiene información actualizada de todos los establecimientos registrados en el sistema.
    </p>

</body>
</html>
<?php
// Cerrar conexión
$conexion->close();
exit;
?>