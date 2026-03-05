<?php
session_start();
if (!isset($_SESSION['rol'])) {
    header("Location: ../index.php");
    exit();
}

include("../modelo/conexion.php");

$rol = $_SESSION['rol'];
$ses_establecimiento = $_SESSION['id_establecimiento'] ?? null;

// filtros
$selected_est = $_GET['establecimiento'] ?? '';
$q = trim($_GET['q'] ?? '');

// IMPORTANTE: Para ADMIN, mostrar todos los establecimientos por defecto
// solo filtrar si selecciona un establecimiento específico
if ($rol === 'ENCARGADO') {
    // ENCARGADO siempre ve solo su establecimiento
    $selected_est = $ses_establecimiento;
    $force_est = true;
} else {
    // ADMIN puede ver todos o filtrar por uno específico
    $force_est = false;
}

// lista de establecimientos
$establecimientos = [];
$res = $conexion->query("SELECT id_establecimiento, nombre_establecimiento FROM establecimientos ORDER BY nombre_establecimiento");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $establecimientos[] = $row;
    }
}

// construir WHERE dinámico CORREGIDO
$whereClauses = ["1=1"];
$params = [];
$types = "";

// Solo agregar filtro de establecimiento si:
// 1. Es ENCARGADO (siempre)
// 2. Es ADMIN y seleccionó un establecimiento específico
if (($rol === 'ENCARGADO') || ($rol === 'ADMIN' && !empty($selected_est))) {
    $whereClauses[] = "e.id_establecimiento = ?";
    $params[] = $selected_est;
    $types .= "i";
}

if (!empty($q)) {
    $whereClauses[] = "(e.nombre_equipo LIKE ? OR s.nombre_software LIKE ?)";
    $params[] = "%" . $q . "%";
    $params[] = "%" . $q . "%";
    $types .= "ss";
}

$where = implode(" AND ", $whereClauses);

// Consultas SQL actualizadas
$sql_vencidas = "
SELECT e.id_equipo, e.nombre_equipo, est.nombre_establecimiento,
       s.nombre_software, s.version, l.fecha_vencimiento,
       DATEDIFF(CURDATE(), l.fecha_vencimiento) AS dias_vencidos
FROM licencias l
JOIN equipos e ON e.id_equipo = l.id_equipo
JOIN software s ON s.id_software = l.id_software
JOIN establecimientos est ON e.id_establecimiento = est.id_establecimiento
WHERE l.estado = 'VENCIDA'
AND $where
ORDER BY l.fecha_vencimiento ASC
LIMIT 200
";

$sql_proximas = "
SELECT e.id_equipo, e.nombre_equipo, est.nombre_establecimiento,
       s.nombre_software, s.version, l.fecha_vencimiento,
       DATEDIFF(l.fecha_vencimiento, CURDATE()) AS dias_restantes
FROM licencias l
JOIN equipos e ON e.id_equipo = l.id_equipo
JOIN software s ON s.id_software = l.id_software
JOIN establecimientos est ON e.id_establecimiento = est.id_establecimiento
WHERE l.estado = 'POR VENCER'
AND $where
ORDER BY l.fecha_vencimiento ASC
LIMIT 200
";

// Consulta para software crítico sin licencia vigente
$sql_criticos = "
SELECT e.id_equipo, e.nombre_equipo, est.nombre_establecimiento,
       s.nombre_software, s.version, s.es_critico,
       MAX(l.fecha_vencimiento) as ultima_fecha_vencimiento,
       CASE 
           WHEN MAX(l.fecha_vencimiento) IS NULL THEN 'SIN LICENCIA'
           WHEN MAX(l.fecha_vencimiento) < CURDATE() THEN 'VENCIDA'
           ELSE 'VIGENTE'
       END AS estado_licencia
FROM software s
LEFT JOIN licencias l ON l.id_software = s.id_software
LEFT JOIN equipos e ON l.id_equipo = e.id_equipo
LEFT JOIN establecimientos est ON e.id_establecimiento = est.id_establecimiento
WHERE s.es_critico = 1
AND $where
GROUP BY e.id_equipo, e.nombre_equipo, est.nombre_establecimiento, s.nombre_software, s.version, s.es_critico
HAVING estado_licencia IN ('SIN LICENCIA', 'VENCIDA')
ORDER BY est.nombre_establecimiento, e.nombre_equipo, s.nombre_software
LIMIT 200
";

// función auxiliar para ejecutar consultas con parámetros
function ejecutarConsulta($conexion, $sql, $types, $params) {
    if (empty($params)) {
        $result = $conexion->query($sql);
        $data = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        } else {
            error_log("Error en query sin parámetros: " . $conexion->error);
        }
        return $data;
    }
    
    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        error_log("Error en preparación SQL: " . $conexion->error . " - SQL: " . $sql);
        return [];
    }
    
    $stmt->bind_param($types, ...$params);
    if (!$stmt->execute()) {
        error_log("Error ejecutando SQL: " . $stmt->error);
        $stmt->close();
        return [];
    }
    
    $res = $stmt->get_result();
    $data = [];
    while ($row = $res->fetch_assoc()) {
        $data[] = $row;
    }
    $stmt->close();
    return $data;
}

// Ejecutar consultas
$vencidas = [];
$proximas = [];
$criticos = [];
$errorMsg = null;

try {
    $vencidas = ejecutarConsulta($conexion, $sql_vencidas, $types, $params);
    $proximas = ejecutarConsulta($conexion, $sql_proximas, $types, $params);
    $criticos = ejecutarConsulta($conexion, $sql_criticos, $types, $params);
} catch (Exception $e) {
    $errorMsg = "Error al consultar la base de datos: " . $e->getMessage();
    error_log($errorMsg);
}

// Para debugging (opcional, remover en producción)
// error_log("Rol: $rol, Selected Est: $selected_est, Force: " . ($force_est ? 'true' : 'false'));
// error_log("WHERE: $where");
// error_log("Params: " . print_r($params, true));
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Panel de Control - Licencias</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="../css/stylePanel.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="icon" href="../img/logo.png">
</head>
<body>
<div class="container">
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <div class="header-text">
                <h1><i class="fas fa-tachometer-alt"></i> Panel de Control</h1>
                <div class="user-info">
                    <i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['nombre'] ?? '') ?> 
                    — <i class="fas fa-shield-alt"></i> <?= htmlspecialchars($rol) ?>
                    <?php if ($rol === 'ADMIN' && empty($selected_est)): ?>
                        <span style="margin-left: 10px; background: var(--success); color: white; padding: 2px 8px; border-radius: 4px; font-size: 0.8rem;">
                            <i class="fas fa-globe"></i> Todos los establecimientos
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="header-actions">
                <a href="menu.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Volver al Menú
                </a>
                <a href="../controlador/logout.php" class="btn btn-primary">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
            </div>
        </div>
    </header>

    <!-- Filtros -->
    <div class="filter-card">
        <form method="get" class="filter-form">
            <div class="form-group">
                <label for="search"><i class="fas fa-search"></i> Buscar</label>
                <input type="text" id="search" name="q" class="form-control" 
                       placeholder="Buscar equipo o software..." value="<?= htmlspecialchars($q) ?>">
            </div>
            
            <?php if ($rol === 'ADMIN'): ?>
            <div class="form-group">
                <label for="establecimiento"><i class="fas fa-school"></i> Establecimiento</label>
                <select id="establecimiento" name="establecimiento" class="form-control">
                    <option value="">Todos los establecimientos</option>
                    <?php foreach ($establecimientos as $est): ?>
                        <option value="<?= $est['id_establecimiento'] ?>" 
                            <?= ($selected_est == $est['id_establecimiento']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($est['nombre_establecimiento']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php else: ?>
                <!-- Mostrar establecimiento fijo para ENCARGADO -->
                <div class="form-group">
                    <label><i class="fas fa-school"></i> Establecimiento</label>
                    <input type="text" class="form-control" value="<?= 
                        htmlspecialchars($establecimientos[$selected_est-1]['nombre_establecimiento'] ?? 'No disponible') ?>" 
                        readonly>
                    <input type="hidden" name="establecimiento" value="<?= $selected_est ?>">
                </div>
            <?php endif; ?>

            <button type="submit" class="btn-search">
                <i class="fas fa-search"></i> Buscar
            </button>
            <a href="panel.php" class="btn-clear">
                <i class="fas fa-times"></i> Limpiar
            </a>
        </form>
    </div>

    <?php if (!empty($errorMsg)): ?>
        <div class="alert-card" style="border-left-color: var(--danger);">
            <div class="alert-header">
                <div class="alert-icon" style="background: rgba(252, 91, 105, 0.1); color: var(--danger);">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h3>Error del Sistema</h3>
            </div>
            <p><?= htmlspecialchars($errorMsg) ?></p>
        </div>
    <?php endif; ?>

    <!-- Estadísticas -->
    <div class="stats-container">
        <div class="stat-card vencidas">
            <div class="stat-number"><?= count($vencidas) ?></div>
            <div class="stat-label">
                <i class="fas fa-exclamation-triangle"></i>
                Licencias Vencidas
            </div>
            <?php if ($rol === 'ADMIN' && empty($selected_est)): ?>
                <div class="stat-info">En todos los establecimientos</div>
            <?php endif; ?>
        </div>
        <div class="stat-card proximas">
            <div class="stat-number"><?= count($proximas) ?></div>
            <div class="stat-label">
                <i class="fas fa-clock"></i>
                Próximas a Vencer
            </div>
            <?php if ($rol === 'ADMIN' && empty($selected_est)): ?>
                <div class="stat-info">En todos los establecimientos</div>
            <?php endif; ?>
        </div>
        <div class="stat-card criticos">
            <div class="stat-number"><?= count($criticos) ?></div>
            <div class="stat-label">
                <i class="fas fa-bug"></i>
                Críticos sin Licencia
            </div>
            <?php if ($rol === 'ADMIN' && empty($selected_est)): ?>
                <div class="stat-info">En todos los establecimientos</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Licencias Vencidas -->
    <div class="alert-card vencidas">
        <div class="alert-header">
            <div class="alert-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3>Licencias Vencidas</h3>
            <span class="status-badge badge-danger"><?= count($vencidas) ?> registros</span>
            <?php if ($rol === 'ADMIN' && empty($selected_est)): ?>
                <span class="status-badge" style="background: var(--info);">
                    <i class="fas fa-globe"></i> Todos los establecimientos
                </span>
            <?php endif; ?>
        </div>
        
        <?php if ($vencidas): ?>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <?php if ($rol === 'ADMIN' && empty($selected_est)): ?>
                                <th>Establecimiento</th>
                            <?php endif; ?>
                            <th>Equipo</th>
                            <th>Software</th>
                            <th>Versión</th>
                            <th>Vencimiento</th>
                            <th>Días Vencidos</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vencidas as $r): 
                            $dias_vencidos = $r['dias_vencidos'] ?? 0;
                        ?>
                        <tr>
                            <?php if ($rol === 'ADMIN' && empty($selected_est)): ?>
                                <td>
                                    <i class="fas fa-school" style="color: var(--secondary); margin-right: 8px;"></i>
                                    <?= htmlspecialchars($r['nombre_establecimiento']) ?>
                                </td>
                            <?php endif; ?>
                            <td>
                                <i class="fas fa-desktop" style="color: var(--primary); margin-right: 8px;"></i>
                                <?= htmlspecialchars($r['nombre_equipo']) ?>
                            </td>
                            <td>
                                <i class="fas fa-cube" style="color: var(--info); margin-right: 8px;"></i>
                                <?= htmlspecialchars($r['nombre_software']) ?>
                            </td>
                            <td>
                                <span class="status-badge" style="background: var(--gray-200); color: var(--gray-700);">
                                    <?= htmlspecialchars($r['version']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge badge-danger">
                                    <i class="fas fa-calendar-times"></i>
                                    <?= htmlspecialchars($r['fecha_vencimiento']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge badge-danger">
                                    <i class="fas fa-hourglass-end"></i>
                                    <?= $dias_vencidos ?> días
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-check-circle" style="color: var(--success);"></i>
                <p>No hay licencias vencidas</p>
                <p style="font-size: 0.9rem; color: var(--gray-600); margin-top: 8px;">
                    <?php if ($rol === 'ADMIN' && empty($selected_est)): ?>
                        ¡Excelente! Todas las licencias en todos los establecimientos están al día.
                    <?php else: ?>
                        ¡Excelente! Todas las licencias están al día.
                    <?php endif; ?>
                </p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Próximas a Vencer -->
    <div class="alert-card proximas">
        <div class="alert-header">
            <div class="alert-icon">
                <i class="fas fa-clock"></i>
            </div>
            <h3>Próximas a Vencer (30 días)</h3>
            <span class="status-badge badge-warning"><?= count($proximas) ?> registros</span>
            <?php if ($rol === 'ADMIN' && empty($selected_est)): ?>
                <span class="status-badge" style="background: var(--info);">
                    <i class="fas fa-globe"></i> Todos los establecimientos
                </span>
            <?php endif; ?>
        </div>
        
        <?php if ($proximas): ?>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <?php if ($rol === 'ADMIN' && empty($selected_est)): ?>
                                <th>Establecimiento</th>
                            <?php endif; ?>
                            <th>Equipo</th>
                            <th>Software</th>
                            <th>Versión</th>
                            <th>Días Restantes</th>
                            <th>Fecha Vencimiento</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($proximas as $r): 
                            $dias = $r['dias_restantes'];
                            $badgeClass = $dias <= 7 ? 'badge-danger' : 'badge-warning';
                        ?>
                        <tr>
                            <?php if ($rol === 'ADMIN' && empty($selected_est)): ?>
                                <td>
                                    <i class="fas fa-school" style="color: var(--secondary); margin-right: 8px;"></i>
                                    <?= htmlspecialchars($r['nombre_establecimiento']) ?>
                                </td>
                            <?php endif; ?>
                            <td>
                                <i class="fas fa-desktop" style="color: var(--primary); margin-right: 8px;"></i>
                                <?= htmlspecialchars($r['nombre_equipo']) ?>
                            </td>
                            <td>
                                <i class="fas fa-cube" style="color: var(--info); margin-right: 8px;"></i>
                                <?= htmlspecialchars($r['nombre_software']) ?>
                            </td>
                            <td>
                                <span class="status-badge" style="background: var(--gray-200); color: var(--gray-700);">
                                    <?= htmlspecialchars($r['version']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge <?= $badgeClass ?>">
                                    <i class="fas fa-hourglass-half"></i>
                                    <?= $dias ?> días
                                </span>
                            </td>
                            <td><?= htmlspecialchars($r['fecha_vencimiento']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-check-circle" style="color: var(--success);"></i>
                <p>No hay licencias próximas a vencer</p>
                <?php if ($rol === 'ADMIN' && empty($selected_est)): ?>
                    <p style="font-size: 0.9rem; color: var(--gray-600); margin-top: 8px;">
                        En ninguno de los establecimientos.
                    </p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Críticos sin Licencia -->
    <div class="alert-card criticos">
        <div class="alert-header">
            <div class="alert-icon">
                <i class="fas fa-bug"></i>
            </div>
            <h3>Software Crítico sin Licencia Válida</h3>
            <span class="status-badge badge-info"><?= count($criticos) ?> registros</span>
            <?php if ($rol === 'ADMIN' && empty($selected_est)): ?>
                <span class="status-badge" style="background: var(--info);">
                    <i class="fas fa-globe"></i> Todos los establecimientos
                </span>
            <?php endif; ?>
        </div>
        
        <?php if ($criticos): ?>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <?php if ($rol === 'ADMIN' && empty($selected_est)): ?>
                                <th>Establecimiento</th>
                            <?php endif; ?>
                            <th>Equipo</th>
                            <th>Software</th>
                            <th>Versión</th>
                            <th>Estado</th>
                            <th>Último Vencimiento</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($criticos as $r): 
                            $estado = $r['estado_licencia'] ?? 'SIN LICENCIA';
                            $badgeClass = $estado === 'SIN LICENCIA' ? 'badge-danger' : 'badge-warning';
                        ?>
                        <tr>
                            <?php if ($rol === 'ADMIN' && empty($selected_est)): ?>
                                <td>
                                    <i class="fas fa-school" style="color: var(--secondary); margin-right: 8px;"></i>
                                    <?= htmlspecialchars($r['nombre_establecimiento']) ?>
                                </td>
                            <?php endif; ?>
                            <td>
                                <i class="fas fa-desktop" style="color: var(--primary); margin-right: 8px;"></i>
                                <?= htmlspecialchars($r['nombre_equipo']) ?>
                            </td>
                            <td>
                                <i class="fas fa-cube" style="color: var(--info); margin-right: 8px;"></i>
                                <strong><?= htmlspecialchars($r['nombre_software']) ?></strong>
                            </td>
                            <td>
                                <span class="status-badge" style="background: var(--gray-200); color: var(--gray-700);">
                                    <?= htmlspecialchars($r['version']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge <?= $badgeClass ?>">
                                    <i class="fas fa-exclamation-circle"></i>
                                    <?= htmlspecialchars($estado) ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($r['ultima_fecha_vencimiento'])): ?>
                                    <span class="status-badge badge-info">
                                        <i class="fas fa-calendar-alt"></i>
                                        <?= htmlspecialchars($r['ultima_fecha_vencimiento']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="status-badge" style="background: var(--gray-300);">
                                        <i class="fas fa-times"></i> Nunca
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-check-circle" style="color: var(--success);"></i>
                <p>Todo el software crítico tiene licencias válidas</p>
                <p style="font-size: 0.9rem; color: var(--gray-600); margin-top: 8px;">
                    <?php if ($rol === 'ADMIN' && empty($selected_est)): ?>
                        En todos los establecimientos.
                    <?php endif; ?>
                </p>
            </div>
        <?php endif; ?>
    </div>
</div>
<script src="../js/Panel.js"></script>
</body>
</html>