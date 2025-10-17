<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== "ADMIN") {
    header("Location: ../index.php");
    exit();
}

include("../modelo/conexion.php");

// Obtener estadísticas generales
$stats_query = $conexion->query("
    SELECT 
        (SELECT COUNT(*) FROM establecimientos) as total_establecimientos,
        (SELECT COUNT(*) FROM usuarios) as total_usuarios,
        (SELECT COUNT(*) FROM licencias) as total_licencias,
        (SELECT COUNT(*) FROM equipos) as total_equipos
");
$stats = $stats_query->fetch_assoc();

// Obtener establecimientos
$establecimientos = $conexion->query("
    SELECT id_establecimiento, nombre_establecimiento, correo, telefono, tipo_escuela
    FROM establecimientos
    ORDER BY nombre_establecimiento ASC
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel Administrativo - Municipalidad de Ovalle</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Normalize CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css">
    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="../css/stylemenu_admin.css">
    <link rel="stylesheet" href="../css/cerrarsesion.css">

    <link rel="icon" href="../img/logo.png">
</head>
<body>
    <div class="layout">
        <!-- Sidebar Mejorado -->
        <aside class="sidebar">
            <div class="brand">
                <img src="../img/logo.png" alt="Logo Municipalidad" class="brand-logo">
                <div>
                    <h2>Panel Administrativo</h2>
                    <p>Municipalidad de Ovalle</p>
                </div>
            </div>
            <nav>
                <ul>
                    <li><a href="panel.php" class="active"><i class="fas fa-chart-bar"></i>Dashboard</a></li>
                    <li><a href="gestionLicencias.php"><i class="fas fa-file-contract"></i>Gestión Licencias</a></li>
                    <li><a href="gestionUsuarios.php"><i class="fas fa-users-cog"></i>Gestión Usuarios</a></li>
                    <li><a href="gestionEstablecimientos.php"><i class="fas fa-building"></i>Establecimientos</a></li>
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
                    <h1><i class="fas fa-crown"></i> Bienvenido, <?= htmlspecialchars($_SESSION['nombre']) ?></h1>
                    <div class="header-subtitle">
                        <i class="fas fa-shield-alt"></i>
                        Panel de Administración del Sistema
                        <span class="role">ADMINISTRADOR</span>
                    </div>
                </div>
                <div class="actions">
                    <a href="gestionUsuarios.php" class="btn primary"><i class="fas fa-user-plus"></i>Crear Usuario</a>
                    <a href="gestionLicencias.php" class="btn outline"><i class="fas fa-plus"></i>Nueva Licencia</a>
                </div>
            </header>

            <!-- Tarjetas de Estadísticas -->
            <section class="stats-cards">
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['total_establecimientos'] ?? 0 ?></div>
                    <div class="stat-label"><i class="fas fa-building"></i> Establecimientos</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['total_usuarios'] ?? 0 ?></div>
                    <div class="stat-label"><i class="fas fa-users"></i> Usuarios</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['total_licencias'] ?? 0 ?></div>
                    <div class="stat-label"><i class="fas fa-file-contract"></i> Licencias</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['total_equipos'] ?? 0 ?></div>
                    <div class="stat-label"><i class="fas fa-laptop"></i> Equipos</div>
                </div>
            </section>

            <!-- Panel de Establecimientos -->
            <section class="panel">
                <h2><i class="fas fa-school"></i> Establecimientos Registrados</h2>

                <!-- Buscador Mejorado -->
                <div class="search-container">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="search" class="search" placeholder="Buscar establecimiento por nombre, tipo o correo...">
                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre del Establecimiento</th>
                                <th>Correo</th>
                                <th>Teléfono</th>
                                <th>Tipo</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($establecimientos && $establecimientos->num_rows): ?>
                            <?php while ($row = $establecimientos->fetch_assoc()): ?>
                            <tr>
                                <td><strong>#<?= $row['id_establecimiento'] ?></strong></td>
                                <td class="establecimiento-name"><?= htmlspecialchars($row['nombre_establecimiento']) ?></td>
                                <td><?= htmlspecialchars($row['correo'] ?? '<span class="badge">No especificado</span>') ?></td>
                                <td><?= htmlspecialchars($row['telefono'] ?? '<span class="badge">No especificado</span>') ?></td>
                                <td><span class="badge"><?= htmlspecialchars($row['tipo_escuela'] ?? 'No especificado') ?></span></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="no-data">
                                    <i class="fas fa-school" style="font-size: 3rem; margin-bottom: 15px; display: block; color: var(--gray);"></i>
                                    No hay establecimientos registrados en el sistema
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
<script src="../js/menuAdmin.js"></script>
<script src="../js/cerrarsesion.js"></script>

</body>
</html>