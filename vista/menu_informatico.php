<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== "ENCARGADO") {
    header("Location: ../index.php");
    exit();
}
include("../modelo/conexion.php");

$id_establecimiento = $_SESSION['id_establecimiento'] ?? 0;
$nombre_usuario   = htmlspecialchars($_SESSION['nombre'] ?? '');
$establecimiento_nombre = htmlspecialchars($_SESSION['establecimiento'] ?? '');

// Obtener estadísticas para las tarjetas
$stats_stmt = $conexion->prepare("
    SELECT 
        (SELECT COUNT(*) FROM usuarios WHERE id_establecimiento = ?) as total_usuarios,
        (SELECT COUNT(*) FROM equipos WHERE id_establecimiento = ?) as total_equipos,
        (SELECT COUNT(*) FROM software WHERE id_establecimiento = ?) as total_software,
        (SELECT COUNT(*) FROM licencias l JOIN equipos e ON l.id_equipo = e.id_equipo WHERE e.id_establecimiento = ?) as total_licencias
");
$stats_stmt->bind_param("iiii", $id_establecimiento, $id_establecimiento, $id_establecimiento, $id_establecimiento);
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
$stats = $stats_result->fetch_assoc();

$stmt = $conexion->prepare("
    SELECT u.id_usuario, u.nombre, u.correo, u.rol, u.tipo_encargado, u.fecha_registro
    FROM usuarios u
    WHERE u.id_establecimiento = ?
    ORDER BY u.fecha_registro DESC
");
$stmt->bind_param("i", $id_establecimiento);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Panel Encargado Informático - <?= $establecimiento_nombre ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Normalize.css -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css">
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <!-- Estilos personalizados -->
  <link rel="stylesheet" href="../css/styleMenu.css">
  <link rel="stylesheet" href="../css/cerrarsesion.css">
  <link rel="icon" href="../img/logo.png">
</head>
<body>
  <div class="layout">
    <!-- Sidebar Mejorado -->
    <aside class="sidebar">
      <div class="brand">
        <img src="../img/logo.png" alt="Logo Municipalidad" class="brand-logo">
        <div class="brand-text">
          <h2>Panel Encargado</h2>
          <p><?= $establecimiento_nombre ?></p>
        </div>
      </div>
      <nav class="nav">
        <ul>
          <li><a href="../controlador/registrar.php" class="active"><i class="fas fa-user-plus"></i>Crear Cuentas</a></li>
          <li><a href="gestionEquipos.php"><i class="fas fa-laptop"></i>Gestión de Equipos</a></li>
          <li><a href="gestionLicencias.php"><i class="fas fa-file-contract"></i>Gestión de Licencias</a></li>
          <li><a href="gestionSoftware.php"><i class="fas fa-cube"></i>Gestión de Software</a></li>
          <li><a href="foro.php"><i class="fas fa-headset"></i>Foro de Consultas</a></li>
        </ul>
      </nav>
      <a href="../controlador/logout.php" class="logout">
        <i class="fas fa-sign-out-alt"></i>
        Cerrar Sesión
      </a>
    </aside>

    <!-- Contenido principal -->
    <main class="main">
      <header class="header">
        <div class="header-content">
          <h1><i class="fas fa-user-cog"></i> Bienvenido, <?= $nombre_usuario ?></h1>
          <div class="header-subtitle">
            <i class="fas fa-building"></i>
            <?= $establecimiento_nombre ?>
            <span class="role">ENCARGADO INFORMÁTICO</span>
          </div>
        </div>
        <div class="actions">
          <a href="../controlador/registrar.php" class="btn primary"><i class="fas fa-plus"></i>Crear Cuenta</a>
          <a href="gestionEquipos.php" class="btn outline"><i class="fas fa-laptop"></i>Gestionar Equipos</a>
        </div>
      </header>

      <!-- Tarjetas de Estadísticas -->
      <section class="stats-cards">
        <div class="stat-card">
          <div class="stat-number"><?= $stats['total_usuarios'] ?? 0 ?></div>
          <div class="stat-label"><i class="fas fa-users"></i> Usuarios</div>
        </div>
        <div class="stat-card">
          <div class="stat-number"><?= $stats['total_equipos'] ?? 0 ?></div>
          <div class="stat-label"><i class="fas fa-laptop"></i> Equipos</div>
        </div>
        <div class="stat-card">
          <div class="stat-number"><?= $stats['total_software'] ?? 0 ?></div>
          <div class="stat-label"><i class="fas fa-cube"></i> Software</div>
        </div>
        <div class="stat-card">
          <div class="stat-number"><?= $stats['total_licencias'] ?? 0 ?></div>
          <div class="stat-label"><i class="fas fa-file-contract"></i> Licencias</div>
        </div>
      </section>

      <!-- Tarjetas de acceso rápido -->
      <section class="cards">
        <div class="card">
          <div class="icon"><i class="fas fa-user-plus"></i></div>
          <h3>Crear Cuentas</h3>
          <p>Genera nuevos accesos de usuario para tu establecimiento educativo.</p>
          <a href="../controlador/registrar.php" class="btn primary"><i class="fas fa-arrow-right"></i>Gestionar</a>
        </div>
        <div class="card">
          <div class="icon"><i class="fas fa-laptop"></i></div>
          <h3>Gestión de Equipos</h3>
          <p>Administra el inventario de computadoras y equipos tecnológicos.</p>
          <a href="gestionEquipos.php" class="btn primary"><i class="fas fa-arrow-right"></i>Gestionar</a>
        </div>
        <div class="card">
          <div class="icon"><i class="fas fa-file-contract"></i></div>
          <h3>Gestión de Licencias</h3>
          <p>Controla y administra las licencias de software institucional.</p>
          <a href="gestionLicencias.php" class="btn primary"><i class="fas fa-arrow-right"></i>Gestionar</a>
        </div>
        <div class="card">
          <div class="icon"><i class="fas fa-cube"></i></div>
          <h3>Gestión de Software</h3>
          <p>Mantén actualizado el catálogo de programas y aplicaciones.</p>
          <a href="gestionSoftware.php" class="btn primary"><i class="fas fa-arrow-right"></i>Gestionar</a>
        </div>
        <div class="card">
          <div class="icon"><i class="fas fa-headset"></i></div>
          <h3>Foro de Consultas</h3>
          <p>Atiende dudas y solicitudes de soporte de los usuarios.</p>
          <a href="foro.php" class="btn primary"><i class="fas fa-arrow-right"></i>Revisar</a>
        </div>
      </section>

      <!-- Tabla de usuarios -->
      <section class="table-section">
        <h2><i class="fas fa-users"></i> Usuarios del Establecimiento</h2>
        <input type="text" id="search" class="search" placeholder="🔍 Buscar por nombre, correo o rol...">
        <div class="table-container">
          <table>
            <thead>
              <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Correo</th>
                <th>Rol</th>
                <th>Tipo Encargado</th>
                <th>Fecha Registro</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($result->num_rows): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                  <tr>
                    <td><strong>#<?= $row['id_usuario'] ?></strong></td>
                    <td><?= htmlspecialchars($row['nombre']) ?></td>
                    <td><?= htmlspecialchars($row['correo']) ?></td>
                    <td><span class="role" style="font-size: 0.8rem; padding: 4px 12px;"><?= htmlspecialchars($row['rol']) ?></span></td>
                    <td><?= htmlspecialchars($row['tipo_encargado'] ?? '-') ?></td>
                    <td><?= date('d/m/Y', strtotime($row['fecha_registro'])) ?></td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr>
                  <td colspan="6" class="no-data">
                    <i class="fas fa-users-slash" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
                    No hay usuarios registrados en este establecimiento
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </section>

      <footer class="footer">
        <small>
          <i class="fas fa-building"></i>
          Departamento de Educación — Municipalidad de Ovalle
          <span style="margin: 0 10px;">•</span>
          <i class="fas fa-clock"></i>
          <?= date('d/m/Y H:i') ?>
        </small>
      </footer>
    </main>
  </div>
<script src="../js/menuInformatico.js"></script>
<script src="../js/cerrarsesion.js"></script>
</body>
</html>