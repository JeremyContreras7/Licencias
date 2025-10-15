<?php
session_start();
if (!isset($_SESSION['rol'])) {
    header("Location: ../index.php");
    exit();
}
include("../modelo/conexion.php");

// Obtenemos el usuario actual y su establecimiento
$id_usuario = $_SESSION['id_usuario'];
$id_establecimiento = $_SESSION['id_establecimiento'];

// Consulta: solo licencias asignadas al usuario actual
$sql = "
  SELECT l.id_licencia, e.nombre_equipo, s.nombre_software, s.version,
         l.fecha_inicio, l.fecha_vencimiento, s.es_critico
  FROM licencias l
  INNER JOIN equipos e ON l.id_equipo = e.id_equipo
  INNER JOIN software s ON l.id_software = s.id_software
  WHERE e.id_establecimiento = ? AND l.id_usuario = ?
  ORDER BY l.fecha_vencimiento ASC
";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("ii", $id_establecimiento, $id_usuario);
$stmt->execute();
$licencias = $stmt->get_result();

// Calcular estadísticas
$total_licencias = $licencias->num_rows;
$hoy = date("Y-m-d");
$vigentes = 0;
$proximas = 0;
$vencidas = 0;

$licencias->data_seek(0); // Reiniciar puntero para contar
while ($row = $licencias->fetch_assoc()) {
    $fven = $row['fecha_vencimiento'];
    if ($fven < $hoy) {
        $vencidas++;
    } elseif ($fven <= date("Y-m-d", strtotime("+30 days"))) {
        $proximas++;
    } else {
        $vigentes++;
    }
}
$licencias->data_seek(0); // Volver al inicio para mostrar
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Mis Licencias - <?= htmlspecialchars($_SESSION['nombre']) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Normalize y Google Fonts -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <!-- Estilos personalizados -->
  <link rel="stylesheet" href="../css/styleLicencia.css">
  <link rel="icon" href="../img/logo.png">
</head>
<body>
  <div class="container">
    <!-- Header Mejorado -->
    <header class="page-header">
      <h1><i class="fas fa-file-contract"></i> Mis Licencias</h1>
      <div class="user-info">
        <span><i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['nombre']) ?></span>
        <span class="badge status vigente"><?= htmlspecialchars($_SESSION['rol']) ?></span>
      </div>
    </header>

    <!-- Barra Superior -->
    <div class="top-bar">
      <a href="menu_funcionario.php" class="btn-back">
        <i class="fas fa-arrow-left"></i> Volver al Menú
      </a>
      <input type="text" id="search" class="search" placeholder="🔍 Buscar licencia por equipo, software...">
    </div>

    <!-- Tarjetas de Estadísticas -->
    <div class="stats-container">
      <div class="stat-card">
        <div class="stat-number"><?= $total_licencias ?></div>
        <div class="stat-label"><i class="fas fa-file-contract"></i> Total Licencias</div>
      </div>
      <div class="stat-card">
        <div class="stat-number" style="color: var(--success);"><?= $vigentes ?></div>
        <div class="stat-label"><i class="fas fa-check-circle"></i> Vigentes</div>
      </div>
      <div class="stat-card">
        <div class="stat-number" style="color: var(--warning);"><?= $proximas ?></div>
        <div class="stat-label"><i class="fas fa-exclamation-triangle"></i> Por Vencer</div>
      </div>
      <div class="stat-card">
        <div class="stat-number" style="color: var(--danger);"><?= $vencidas ?></div>
        <div class="stat-label"><i class="fas fa-times-circle"></i> Vencidas</div>
      </div>
    </div>

    <!-- Tabla de Licencias -->
    <div class="table-container">
      <table>
        <thead>
          <tr>
            <th><i class="fas fa-hashtag"></i> ID</th>
            <th><i class="fas fa-desktop"></i> Equipo</th>
            <th><i class="fas fa-cube"></i> Software</th>
            <th><i class="fas fa-code-branch"></i> Versión</th>
            <th><i class="fas fa-calendar-plus"></i> Inicio</th>
            <th><i class="fas fa-calendar-times"></i> Vencimiento</th>
            <th><i class="fas fa-info-circle"></i> Estado</th>
            <th><i class="fas fa-shield-alt"></i> Crítico</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($licencias->num_rows): ?>
            <?php while ($row = $licencias->fetch_assoc()): ?>
              <?php
              $hoy = date("Y-m-d");
              $fven = $row['fecha_vencimiento'];
              $dias_restantes = floor((strtotime($fven) - strtotime($hoy)) / (60 * 60 * 24));
              
              if ($fven < $hoy) {
                $estado = 'vencida';
                $texto = 'Vencida';
                $icono = 'fas fa-times-circle';
              } elseif ($dias_restantes <= 30) {
                $estado = 'por-vencer';
                $texto = "Por vencer ({$dias_restantes}d)";
                $icono = 'fas fa-exclamation-triangle';
              } else {
                $estado = 'vigente';
                $texto = "Vigente ({$dias_restantes}d)";
                $icono = 'fas fa-check-circle';
              }
              ?>
              <tr>
                <td data-label="ID"><strong>#<?= $row['id_licencia'] ?></strong></td>
                <td data-label="Equipo"><?= htmlspecialchars($row['nombre_equipo']) ?></td>
                <td data-label="Software"><?= htmlspecialchars($row['nombre_software']) ?></td>
                <td data-label="Versión"><?= htmlspecialchars($row['version']) ?></td>
                <td data-label="Inicio"><?= date('d/m/Y', strtotime($row['fecha_inicio'])) ?></td>
                <td data-label="Vencimiento"><?= date('d/m/Y', strtotime($fven)) ?></td>
                <td data-label="Estado">
                  <span class="badge status <?= $estado ?>">
                    <i class="<?= $icono ?>"></i>
                    <?= $texto ?>
                  </span>
                </td>
                <td data-label="Crítico">
                  <?= $row['es_critico']
                    ? '<span class="badge critico"><i class="fas fa-exclamation-triangle"></i> Sí</span>'
                    : '<span class="badge normal"><i class="fas fa-check"></i> No</span>' ?>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="8" class="no-data">
                <i class="fas fa-file-contract"></i>
                <p>No hay licencias asignadas a tu usuario</p>
                <small>Contacta al encargado de informática para asignarte licencias de software.</small>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
<script src="../js/Licencia.js"></script>

</body>
</html>