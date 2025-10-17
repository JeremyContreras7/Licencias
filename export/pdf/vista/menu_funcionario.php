<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== "USUARIO") {
    header("Location: ../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Panel Usuario - Municipalidad de Ovalle</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="icon" href="../img/logo.png">
  <link rel="stylesheet" href="../css/stylemenu_funcionario.css">
  <link rel="stylesheet" href="../css/cerrarsesion.css">


</head>
<body>
  <!-- Efecto de partículas de fondo -->
  <div class="particles" id="particles"></div>

  <div class="layout">
    <!-- Sidebar Mejorado -->
    <aside class="sidebar">
      <div class="brand">
        <img src="../img/logo.png" alt="Logo Municipalidad" class="brand-logo">
        <div class="brand-text">
          <h2>Panel de Usuario</h2>
          <p>Departamento de Educación</p>
        </div>
      </div>
      <nav class="nav">
        <ul>
          <li>
            <a href="mis_licencias.php" class="active">
              <i class="fas fa-file-contract"></i>
              <span>Mis Licencias</span>
              <span class="badge">2</span>
            </a>
          </li>
          <li>
            <a href="reportar_problema.php">
              <i class="fas fa-exclamation-triangle"></i>
              <span>Reportar Problema</span>
            </a>
          </li>
        </ul>
      </nav>
      <a href="../controlador/logout.php" class="logout">
        <i class="fas fa-sign-out-alt"></i>
        <span>Cerrar Sesión</span>
      </a>
    </aside>

    <!-- Contenido principal -->
    <main class="main">
      <header class="header">
        <div>
          <h1>
            <i class="fas fa-user-circle"></i>
            Hola, <?= htmlspecialchars($_SESSION['nombre']) ?>
            <span class="role">USUARIO</span>
          </h1>
          <p style="color: var(--gray); margin-top: 8px; font-size: 0.95rem;">
            <i class="fas fa-calendar-day"></i>
            <?php echo date('d/m/Y'); ?> - Bienvenido a tu panel de control
          </p>
        </div>
      </header>

      <section class="cards">
        <div class="card">
          <div class="card-icon">
            <i class="fas fa-file-contract"></i>
          </div>
          <h3>Mis Licencias</h3>
          <p>Revisa el estado y detalle de tus solicitudes de licencias de software asignadas a tu equipo.</p>
          <a href="mis_licencias.php" class="btn">
            <i class="fas fa-arrow-right"></i>
            Ver Mis Licencias
          </a>
        </div>

        <div class="card">
          <div class="card-icon">
            <i class="fas fa-exclamation-triangle"></i>
          </div>
          <h3>Reportar Problema</h3>
          <p>Notifica cualquier inconveniente técnico o problema que tengas con el software o equipos.</p>
          <a href="foro.php" class="btn">
            <i class="fas fa-arrow-right"></i>
            Enviar Reporte
          </a>
        </div>
      </section>

      <footer class="footer">
        <small>
          <i class="fas fa-building"></i>
          Municipalidad de Ovalle – Departamento de Educación
          <span style="margin: 0 10px;">•</span>
          <i class="fas fa-phone"></i>
          Soporte: +56 9 1234 5678
        </small>
      </footer>
    </main>
  </div>
<script src="../js/menuFuncionario.js"></script>
<script src="../js/cerrarsesion.js"></script>
</body>
</html>