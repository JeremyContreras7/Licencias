<?php
session_start();
error_log("Archivo gestionLicencias.php ejecutándose");
error_log("GET eliminar: " . ($_GET['eliminar'] ?? 'No establecido'));
if (!isset($_SESSION['rol'])) {
    header("Location: ../index.php");
    exit();
}

include("../modelo/conexion.php");

$rol = $_SESSION['rol'];
$id_establecimiento = $_SESSION['id_establecimiento'] ?? null;
$nombre_usuario = htmlspecialchars($_SESSION['nombre'] ?? '');

// Determinar la ruta del menú según el rol
$menu_url = ($rol === "ADMIN") ? "menu.php" : "menu_informatico.php";

// --- ACTUALIZAR ESTADOS DE LICENCIAS AUTOMÁTICAMENTE ---
$hoy = date('Y-m-d');

// Primero actualizamos todas las licencias que están vencidas
if ($rol === "ENCARGADO") {
    $update_vencidas = $conexion->prepare("
        UPDATE licencias 
        SET estado = 'VENCIDA' 
        WHERE fecha_vencimiento < ? 
        AND estado != 'VENCIDA'
        AND id_establecimiento = ?
    ");
    $update_vencidas->bind_param("si", $hoy, $id_establecimiento);
} else {
    $update_vencidas = $conexion->prepare("
        UPDATE licencias 
        SET estado = 'VENCIDA' 
        WHERE fecha_vencimiento < ? 
        AND estado != 'VENCIDA'
    ");
    $update_vencidas->bind_param("s", $hoy);
}
$update_vencidas->execute();
$update_vencidas->close();

// Luego actualizamos las licencias que están por vencer (próximos 30 días)
$fecha_limite = date('Y-m-d', strtotime('+30 days'));

if ($rol === "ENCARGADO") {
    $update_proximas = $conexion->prepare("
        UPDATE licencias 
        SET estado = 'POR VENCER' 
        WHERE fecha_vencimiento BETWEEN ? AND ?
        AND estado NOT IN ('VENCIDA', 'POR VENCER')
        AND id_establecimiento = ?
    ");
    $update_proximas->bind_param("ssi", $hoy, $fecha_limite, $id_establecimiento);
} else {
    $update_proximas = $conexion->prepare("
        UPDATE licencias 
        SET estado = 'POR VENCER' 
        WHERE fecha_vencimiento BETWEEN ? AND ?
        AND estado NOT IN ('VENCIDA', 'POR VENCER')
    ");
    $update_proximas->bind_param("ss", $hoy, $fecha_limite);
}
$update_proximas->execute();
$update_proximas->close();

// Finalmente, las que están vigentes (más de 30 días)
if ($rol === "ENCARGADO") {
    $update_vigentes = $conexion->prepare("
        UPDATE licencias 
        SET estado = 'VIGENTE' 
        WHERE fecha_vencimiento > ?
        AND estado NOT IN ('VENCIDA', 'VIGENTE')
        AND id_establecimiento = ?
    ");
    $update_vigentes->bind_param("si", $fecha_limite, $id_establecimiento);
} else {
    $update_vigentes = $conexion->prepare("
        UPDATE licencias 
        SET estado = 'VIGENTE' 
        WHERE fecha_vencimiento > ?
        AND estado NOT IN ('VENCIDA', 'VIGENTE')
    ");
    $update_vigentes->bind_param("s", $fecha_limite);
}
$update_vigentes->execute();
$update_vigentes->close();

// --- RENOVAR LICENCIA ---
if (isset($_POST['renovar'])) {
    $id_licencia = (int)$_POST['id_licencia'];
    $periodo_renovacion = trim($_POST['periodo_renovacion']);
    $nueva_fecha_inicio = trim($_POST['nueva_fecha_inicio']);
    $nueva_fecha_vencimiento = trim($_POST['nueva_fecha_vencimiento']);
    $notas_renovacion = trim($_POST['notas_renovacion']);
    
    // Validaciones
    if (empty($nueva_fecha_inicio) || empty($nueva_fecha_vencimiento)) {
        header("Location: gestionLicencias.php?error=fechas_renovacion");
        exit();
    }
    
    if ($nueva_fecha_inicio > $nueva_fecha_vencimiento) {
        header("Location: gestionLicencias.php?error=fechas_invalidas_renovacion");
        exit();
    }
    
    // Verificar permisos según rol
    if ($rol === "ENCARGADO") {
        // Verificar que la licencia pertenece al establecimiento del encargado
        $stmt = $conexion->prepare("SELECT COUNT(*) FROM licencias WHERE id_licencia = ? AND id_establecimiento = ?");
        $stmt->bind_param("ii", $id_licencia, $id_establecimiento);
        $stmt->execute();
        $stmt->bind_result($cnt);
        $stmt->fetch();
        $stmt->close();
        
        if ($cnt == 0) {
            header("Location: gestionLicencias.php?error=licencia_no_pertenece");
            exit();
        }
    }
    
    // Calcular el nuevo estado
    $dias_restantes = floor((strtotime($nueva_fecha_vencimiento) - strtotime($hoy)) / (60 * 60 * 24));
    
    if ($nueva_fecha_vencimiento < $hoy) {
        $nuevo_estado = 'VENCIDA';
    } elseif ($dias_restantes <= 30) {
        $nuevo_estado = 'POR VENCER';
    } else {
        $nuevo_estado = 'VIGENTE';
    }
    
    // Actualizar la licencia existente
    $stmt = $conexion->prepare("
        UPDATE licencias 
        SET fecha_inicio = ?, 
            fecha_vencimiento = ?, 
            estado = ?, 
            renovable = 1,
            notas = CONCAT(IFNULL(notas, ''), ' | Renovada el ', CURDATE(), ' por ', ?, ' - ', ?)
        WHERE id_licencia = ?
    ");
    
    $periodo_texto = ($periodo_renovacion === 'mensual') ? 'Mensual' : 'Anual';
    $notas_completas = $periodo_texto . ': ' . $notas_renovacion;
    
    $stmt->bind_param("sssssi", 
        $nueva_fecha_inicio, 
        $nueva_fecha_vencimiento, 
        $nuevo_estado, 
        $periodo_texto, 
        $notas_completas, 
        $id_licencia
    );
    
    $ok = $stmt->execute();
    $stmt->close();
    
    // Registrar la renovación en un historial (opcional - si tienes tabla historial_licencias)
    try {
        $stmt_historial = $conexion->prepare("
            INSERT INTO historial_renovaciones 
            (id_licencia, fecha_renovacion, periodo_renovacion, fecha_inicio_nueva, fecha_vencimiento_nueva, notas, id_usuario_renovador)
            VALUES (?, NOW(), ?, ?, ?, ?, ?)
        ");
        
        $id_usuario_renovador = $_SESSION['id_usuario'] ?? 0;
        $stmt_historial->bind_param("issssi", 
            $id_licencia, 
            $periodo_renovacion, 
            $nueva_fecha_inicio, 
            $nueva_fecha_vencimiento, 
            $notas_renovacion,
            $id_usuario_renovador
        );
        $stmt_historial->execute();
        $stmt_historial->close();
    } catch (Exception $e) {
        // Si no existe la tabla de historial, no hacemos nada
    }
    
    header("Location: gestionLicencias.php?" . ($ok ? "msg=renewed" : "error=renew_failed"));
    exit();
}

// --- CREAR LICENCIA CON RESTRICCIÓN ANTI-DUPLICADOS ---
if (isset($_POST['crear'])) {
    $id_equipo = (int)$_POST['id_equipo'];
    $id_software = (int)$_POST['id_software'];
    $id_usuario = (int)$_POST['id_usuario'];
    $fecha_inicio = trim($_POST['fecha_inicio']);
    $fecha_vencimiento = trim($_POST['fecha_vencimiento']);
    $tipo_licencia = trim($_POST['tipo_licencia']);
    $restricciones = trim($_POST['restricciones']);
    $renovable = isset($_POST['renovable']) ? 1 : 0;
    $metodo_activacion = trim($_POST['metodo_activacion']);
    $notas = trim($_POST['notas']);

    if (empty($fecha_inicio) || empty($fecha_vencimiento)) {
        header("Location: gestionLicencias.php?error=fechas");
        exit();
    }
    if ($fecha_inicio > $fecha_vencimiento) {
        header("Location: gestionLicencias.php?error=fechasinvalida");
        exit();
    }

    // Validaciones de establecimiento si es ENCARGADO
    if ($rol === "ENCARGADO") {
        // verificar equipo
        $stmt = $conexion->prepare("SELECT COUNT(*) FROM equipos WHERE id_equipo = ? AND id_establecimiento = ?");
        $stmt->bind_param("ii", $id_equipo, $id_establecimiento);
        $stmt->execute(); $stmt->bind_result($cntEq); $stmt->fetch(); $stmt->close();
        if ($cntEq == 0) {
            header("Location: gestionLicencias.php?error=equipo_no_pertenece");
            exit();
        }

        // verificar software
        $stmt = $conexion->prepare("SELECT COUNT(*) FROM software WHERE id_software = ? AND id_establecimiento = ?");
        $stmt->bind_param("ii", $id_software, $id_establecimiento);
        $stmt->execute(); $stmt->bind_result($cntSw); $stmt->fetch(); $stmt->close();
        if ($cntSw == 0) {
            header("Location: gestionLicencias.php?error=software_no_pertenece");
            exit();
        }

        // verificar usuario
        $stmt = $conexion->prepare("SELECT COUNT(*) FROM usuarios WHERE id_usuario = ? AND id_establecimiento = ?");
        $stmt->bind_param("ii", $id_usuario, $id_establecimiento);
        $stmt->execute(); $stmt->bind_result($cntUs); $stmt->fetch(); $stmt->close();
        if ($cntUs == 0) {
            header("Location: gestionLicencias.php?error=usuario_no_pertenece");
            exit();
        }

        // Para ENCARGADO, usar el id_establecimiento de la sesión
        $id_establecimiento_licencia = $id_establecimiento;
    } else {
        // Para ADMIN, obtener el id_establecimiento del equipo seleccionado
        $stmt = $conexion->prepare("SELECT id_establecimiento FROM equipos WHERE id_equipo = ?");
        $stmt->bind_param("i", $id_equipo);
        $stmt->execute();
        $stmt->bind_result($id_establecimiento_licencia);
        $stmt->fetch();
        $stmt->close();
    }

    // --- RESTRICCIÓN: VERIFICAR SI YA EXISTE UNA LICENCIA ACTIVA PARA ESTE EQUIPO Y SOFTWARE ---
    $stmt_verificar = $conexion->prepare("
        SELECT COUNT(*) FROM licencias 
        WHERE id_equipo = ? 
        AND id_software = ? 
        AND estado IN ('VIGENTE', 'POR VENCER')
        AND id_establecimiento = ?
    ");
    $stmt_verificar->bind_param("iii", $id_equipo, $id_software, $id_establecimiento_licencia);
    $stmt_verificar->execute();
    $stmt_verificar->bind_result($licencias_activas);
    $stmt_verificar->fetch();
    $stmt_verificar->close();
    
    if ($licencias_activas > 0) {
        header("Location: gestionLicencias.php?error=licencia_duplicada&equipo=" . urlencode($id_equipo) . "&software=" . urlencode($id_software));
        exit();
    }

    // --- RESTRICCIÓN: VERIFICAR SI YA EXISTE UNA LICENCIA CON LA MISMA CLAVE DE ACTIVACIÓN ---
    if (!empty($metodo_activacion)) {
        $stmt_clave = $conexion->prepare("
            SELECT COUNT(*) FROM licencias 
            WHERE metodo_activacion = ? 
            AND id_establecimiento = ?
            AND metodo_activacion IS NOT NULL 
            AND metodo_activacion != ''
        ");
        $stmt_clave->bind_param("si", $metodo_activacion, $id_establecimiento_licencia);
        $stmt_clave->execute();
        $stmt_clave->bind_result($claves_existentes);
        $stmt_clave->fetch();
        $stmt_clave->close();
        
        if ($claves_existentes > 0) {
            header("Location: gestionLicencias.php?error=clave_duplicada");
            exit();
        }
    }

    // Calcular estado automáticamente
    $dias_restantes = floor((strtotime($fecha_vencimiento) - strtotime($hoy)) / (60 * 60 * 24));
    
    if ($fecha_vencimiento < $hoy) {
        $estado = 'VENCIDA';
    } elseif ($dias_restantes <= 30) {
        $estado = 'POR VENCER';
    } else {
        $estado = 'VIGENTE';
    }

    // Insertar licencia con todos los campos
    $stmt = $conexion->prepare("
        INSERT INTO licencias 
        (id_equipo, id_software, id_usuario, fecha_inicio, fecha_vencimiento, estado, tipo_licencia, restricciones, renovable, metodo_activacion, notas, id_establecimiento) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("iiisssssissi", $id_equipo, $id_software, $id_usuario, $fecha_inicio, $fecha_vencimiento, $estado, $tipo_licencia, $restricciones, $renovable, $metodo_activacion, $notas, $id_establecimiento_licencia);
    $ok = $stmt->execute();
    $stmt->close();

    header("Location: gestionLicencias.php?" . ($ok ? "msg=created" : "error=db"));
    exit();
}

// --- ELIMINAR LICENCIA ---
if (isset($_GET['eliminar'])) {
    $id = (int)$_GET['eliminar'];
    
    // Preparar mensaje de éxito/error
    $mensaje = "";
    
    try {
        if ($rol === "ENCARGADO") {
            // Para ENCARGADO: solo eliminar licencias de su establecimiento
            $stmt = $conexion->prepare("
                DELETE l FROM licencias l
                INNER JOIN equipos e ON l.id_equipo = e.id_equipo
                WHERE l.id_licencia = ? 
                AND e.id_establecimiento = ?
            ");
            $stmt->bind_param("ii", $id, $id_establecimiento);
        } else {
            // Para ADMIN: eliminar cualquier licencia
            $stmt = $conexion->prepare("DELETE FROM licencias WHERE id_licencia = ?");
            $stmt->bind_param("i", $id);
        }
        
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        
        if ($affected > 0) {
            $mensaje = "msg=deleted";
        } else {
            $mensaje = "error=delete_denegado";
        }
        
    } catch (Exception $e) {
        error_log("Error al eliminar licencia: " . $e->getMessage());
        $mensaje = "error=db";
    }
    
    // Redirigir
    header("Location: gestionLicencias.php?" . $mensaje);
    exit();

}

// --- LISTADO DE EQUIPOS ---
if ($rol === "ENCARGADO") {
    $equipos = $conexion->query("SELECT id_equipo, nombre_equipo FROM equipos WHERE id_establecimiento = {$id_establecimiento} ORDER BY nombre_equipo");
} else {
    $equipos = $conexion->query("SELECT id_equipo, nombre_equipo FROM equipos ORDER BY nombre_equipo");
}

// --- LISTADO DE SOFTWARE ---
if ($rol === "ENCARGADO") {
    $software = $conexion->query("SELECT id_software, nombre_software, version FROM software WHERE id_establecimiento = {$id_establecimiento} ORDER BY nombre_software");
} else {
    $software = $conexion->query("SELECT id_software, nombre_software, version FROM software ORDER BY nombre_software");
}

// --- LISTADO DE USUARIOS ---
if ($rol === "ENCARGADO") {
    $usuarios = $conexion->query("SELECT id_usuario, nombre FROM usuarios WHERE id_establecimiento = {$id_establecimiento} ORDER BY nombre");
} else {
    $usuarios = $conexion->query("SELECT id_usuario, nombre FROM usuarios ORDER BY nombre");
}

// --- LISTADO DE LICENCIAS ---
if ($rol === "ENCARGADO") {
    $licencias = $conexion->query("
        SELECT l.id_licencia, e.nombre_equipo, s.nombre_software, s.version, 
               l.fecha_inicio, l.fecha_vencimiento as fecha_vencimiento, l.estado, l.tipo_licencia,
               l.restricciones, l.renovable, l.metodo_activacion, l.notas,
               u.nombre AS usuario
        FROM licencias l
        JOIN equipos e ON e.id_equipo = l.id_equipo
        JOIN software s ON s.id_software = l.id_software
        JOIN usuarios u ON u.id_usuario = l.id_usuario
        WHERE l.id_establecimiento = {$id_establecimiento}
        ORDER BY l.fecha_vencimiento ASC
    ");
} else {
    $licencias = $conexion->query("
        SELECT l.id_licencia, e.nombre_equipo, s.nombre_software, s.version, 
               l.fecha_inicio, l.fecha_vencimiento as fecha_vencimiento, l.estado, l.tipo_licencia,
               l.restricciones, l.renovable, l.metodo_activacion, l.notas,
               u.nombre AS usuario, est.nombre_establecimiento
        FROM licencias l
        JOIN equipos e ON e.id_equipo = l.id_equipo
        JOIN software s ON s.id_software = l.id_software
        JOIN usuarios u ON u.id_usuario = l.id_usuario
        JOIN establecimientos est ON l.id_establecimiento = est.id_establecimiento
        ORDER BY l.fecha_vencimiento ASC
    ");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Licencias</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/styleGlicencias.css">
    <link rel="icon" href="../img/logo.png">
</head>
<body>
    <!-- Mensajes de error/success flotantes -->
    <?php
    if (isset($_GET['error'])) {
        $error = $_GET['error'];
        $mensaje = '';
        $icono = '';
        
        switch ($error) {
            case 'licencia_duplicada':
                $mensaje = 'Ya existe una licencia VIGENTE o POR VENCER para este equipo y software. No se puede duplicar.';
                $icono = 'fa-ban';
                break;
            case 'clave_duplicada':
                $mensaje = 'Esta clave de activación ya está registrada en el sistema.';
                $icono = 'fa-key';
                break;
            case 'equipo_no_pertenece':
                $mensaje = 'El equipo seleccionado no pertenece a su establecimiento.';
                $icono = 'fa-desktop';
                break;
            case 'software_no_pertenece':
                $mensaje = 'El software seleccionado no pertenece a su establecimiento.';
                $icono = 'fa-cube';
                break;
            case 'usuario_no_pertenece':
                $mensaje = 'El usuario seleccionado no pertenece a su establecimiento.';
                $icono = 'fa-user';
                break;
            case 'fechas':
                $mensaje = 'Debe completar las fechas de inicio y vencimiento.';
                $icono = 'fa-calendar-alt';
                break;
            case 'fechasinvalida':
                $mensaje = 'La fecha de vencimiento debe ser posterior a la fecha de inicio.';
                $icono = 'fa-calendar-times';
                break;
            case 'delete_denegado':
                $mensaje = 'No tiene permisos para eliminar esta licencia.';
                $icono = 'fa-lock';
                break;
            case 'db':
                $mensaje = 'Error en la base de datos. Intente nuevamente.';
                $icono = 'fa-database';
                break;
            default:
                $mensaje = 'Ha ocurrido un error desconocido.';
                $icono = 'fa-exclamation-circle';
        }
        
        echo "
        <div class='alert-custom error-message alert alert-dismissible fade show' role='alert'>
            <div class='d-flex align-items-center'>
                <i class='fas {$icono} me-3 fa-2x'></i>
                <div>
                    <strong>Error de Registro</strong>
                    <p class='mb-0'>{$mensaje}</p>
                </div>
            </div>
            <button type='button' class='btn-close btn-close-white' data-bs-dismiss='alert' aria-label='Close'></button>
        </div>
        <script>
            setTimeout(function() {
                document.querySelector('.alert-custom').style.display = 'none';
            }, 5000);
        </script>";
    }
    
    if (isset($_GET['msg'])) {
        $msg = $_GET['msg'];
        $mensaje = '';
        $icono = '';
        
        switch ($msg) {
            case 'created':
                $mensaje = 'Licencia registrada exitosamente.';
                $icono = 'fa-check-circle';
                break;
            case 'deleted':
                $mensaje = 'Licencia eliminada correctamente.';
                $icono = 'fa-trash-alt';
                break;
            case 'renewed':
                $mensaje = 'Licencia renovada exitosamente.';
                $icono = 'fa-sync-alt';
                break;
        }
        
        echo "
        <div class='alert-custom success-message alert alert-dismissible fade show' role='alert'>
            <div class='d-flex align-items-center'>
                <i class='fas {$icono} me-3 fa-2x'></i>
                <div>
                    <strong>Operación Exitosa</strong>
                    <p class='mb-0'>{$mensaje}</p>
                </div>
            </div>
            <button type='button' class='btn-close btn-close-white' data-bs-dismiss='alert' aria-label='Close'></button>
        </div>
        <script>
            setTimeout(function() {
                document.querySelector('.alert-custom').style.display = 'none';
            }, 5000);
        </script>";
    }
    ?>

    <!-- Modal de Confirmación de Eliminación -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal-content">
            <div class="modal-header">
                <i class="fas fa-exclamation-triangle"></i>
                <h3>Confirmar Eliminación</h3>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de que deseas eliminar esta licencia?</p>
                <div class="licencia-info">
                    <div class="licencia-detail">
                        <span class="detail-label">Software:</span>
                        <span class="detail-value" id="licenciaSoftware"></span>
                    </div>
                    <div class="licencia-detail">
                        <span class="detail-label">Equipo:</span>
                        <span class="detail-value" id="licenciaEquipo"></span>
                    </div>
                    <div class="licencia-detail">
                        <span class="detail-label">Usuario:</span>
                        <span class="detail-value" id="licenciaUsuario"></span>
                    </div>
                    <div class="licencia-detail">
                        <span class="detail-label">Tipo Licencia:</span>
                        <span class="detail-value" id="licenciaTipo"></span>
                    </div>
                    <div class="licencia-detail">
                        <span class="detail-label">Vencimiento:</span>
                        <span class="detail-value" id="licenciaVencimiento"></span>
                    </div>
                    <div class="licencia-detail">
                        <span class="detail-label">Estado:</span>
                        <span class="detail-value" id="licenciaEstado"></span>
                    </div>
                </div>
                <p style="color: #e74c3c; font-weight: 500;">
                    <i class="fas fa-info-circle"></i>
                    Esta acción no se puede deshacer
                </p>
                <div class="modal-actions">
                    <button class="btn-cancel" id="cancelDelete">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <a href="#" class="btn-confirm-delete" id="confirmDelete">
                        <i class="fas fa-trash"></i> Sí, Eliminar
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Renovación (COMPACTO) -->
    <div class="modal-overlay" id="renewModal">
        <div class="modal-content compact-modal">
            <form method="POST" action="" class="d-flex flex-column h-100">
                <input type="hidden" name="renovar" value="1">
                <input type="hidden" name="id_licencia" id="renewLicenciaId">
                
                <div class="modal-renew-header">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-sync-alt me-3 fa-lg"></i>
                            <h4 class="mb-0">Renovar Licencia</h4>
                        </div>
                        <button type="button" class="btn-close btn-close-white" id="closeRenewModal" style="opacity: 0.8;"></button>
                    </div>
                </div>
                
                <div class="modal-body">
                    <!-- Información actual compacta -->
                    <div class="compact-info">
                        <div class="compact-info-item">
                            <span class="compact-info-label">Software:</span>
                            <span class="compact-info-value" id="renewSoftware">-</span>
                        </div>
                        <div class="compact-info-item">
                            <span class="compact-info-label">Equipo:</span>
                            <span class="compact-info-value" id="renewEquipo">-</span>
                        </div>
                        <div class="compact-info-item">
                            <span class="compact-info-label">Vencimiento actual:</span>
                            <span class="compact-info-value" id="renewVencimiento">-</span>
                        </div>
                        <div class="compact-info-item">
                            <span class="compact-info-label">Estado:</span>
                            <span class="compact-info-value" id="renewEstado">-</span>
                        </div>
                    </div>

                    <h6><i class="fas fa-calendar-alt me-2"></i>Seleccione período de renovación:</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <div class="renew-period-option" id="periodMensual" onclick="selectPeriod('mensual')">
                                <div class="renew-period-icon">
                                    <i class="fas fa-calendar-week"></i>
                                </div>
                                <h6 class="mb-1 text-center">Mensual</h6>
                                <p class="text-muted mb-2 text-center" style="font-size: 0.85rem;">30 días de vigencia</p>
                                <div class="form-check d-flex justify-content-center">
                                    <input class="form-check-input me-2" type="radio" name="periodo_renovacion" id="periodMensualRadio" value="mensual" checked>
                                    <label class="form-check-label" for="periodMensualRadio">
                                        Seleccionar
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="renew-period-option" id="periodAnual" onclick="selectPeriod('anual')">
                                <div class="renew-period-icon">
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                                <h6 class="mb-1 text-center">Anual</h6>
                                <p class="text-muted mb-2 text-center" style="font-size: 0.85rem;">365 días de vigencia</p>
                                <div class="form-check d-flex justify-content-center">
                                    <input class="form-check-input me-2" type="radio" name="periodo_renovacion" id="periodAnualRadio" value="anual">
                                    <label class="form-check-label" for="periodAnualRadio">
                                        Seleccionar
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Previsualización compacta -->
                    <div class="date-preview">
                        <div class="compact-info-item">
                            <span class="compact-info-label">Nuevo inicio:</span>
                            <span class="compact-info-value" id="previewInicio">-</span>
                        </div>
                        <div class="compact-info-item">
                            <span class="compact-info-label">Nuevo vencimiento:</span>
                            <span class="compact-info-value" id="previewVencimiento">-</span>
                        </div>
                        <div class="compact-info-item">
                            <span class="compact-info-label">Período seleccionado:</span>
                            <span class="compact-info-value" id="previewPeriodo">Mensual (30 días)</span>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <div class="field">
                                <label for="nueva_fecha_inicio" class="form-label">
                                    <i class="fas fa-calendar-plus me-1"></i>Fecha de inicio
                                </label>
                                <input type="date" class="form-control" id="nueva_fecha_inicio" name="nueva_fecha_inicio" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="field">
                                <label for="nueva_fecha_vencimiento" class="form-label">
                                    <i class="fas fa-calendar-check me-1"></i>Fecha de vencimiento
                                </label>
                                <input type="date" class="form-control" id="nueva_fecha_vencimiento" name="nueva_fecha_vencimiento" required>
                            </div>
                        </div>
                    </div>

                    <div class="field mb-3">
                        <label for="notas_renovacion" class="form-label">
                            <i class="fas fa-sticky-note me-1"></i>Notas de renovación
                        </label>
                        <textarea class="form-control" id="notas_renovacion" name="notas_renovacion" rows="3" placeholder="Motivo de la renovación, observaciones, cambios realizados..."></textarea>
                    </div>

                    <div class="alert alert-info alert-sm">
                        <div class="d-flex align-items-start">
                            <i class="fas fa-info-circle me-2 mt-1"></i>
                            <div>
                                <strong class="d-block mb-1">Información importante:</strong>
                                <small class="d-block">Al renovar, la licencia se marcará automáticamente como renovable y se actualizarán las fechas de vigencia en el sistema.</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <div class="d-flex w-100 gap-3">
                        <button type="button" class="btn btn-outline-secondary flex-grow-1" id="cancelRenew">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </button>
                        <button type="submit" class="btn btn-renovar flex-grow-1">
                            <i class="fas fa-sync-alt me-2"></i>Confirmar Renovación
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="container">
        <!-- Encabezado -->
        <div class="header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1><i class="fas fa-file-contract me-2"></i>Gestión de Licencias</h1>
                    <p class="mb-0 mt-1">Administra las licencias de software de tu organización</p>
                </div>
                <div class="text-end">
                    <div class="user-info">
                        <i class="fas fa-user me-1"></i> <?= $nombre_usuario ?> — <?= htmlspecialchars($rol) ?>
                    </div>
                    <a href="<?= $menu_url ?>" class="btn btn-light btn-sm mt-2">
                        <i class="fas fa-arrow-left me-1"></i> Volver al Menú
                    </a>
                </div>
            </div>
        </div>

        <!-- Tarjetas de estadísticas -->
        <?php
        // Calcular estadísticas
        $total_licencias = $licencias->num_rows;
        
        // Reiniciar el puntero para contar estados
        $licencias->data_seek(0);
        $vencidas = 0;
        $proximas = 0;
        $vigentes = 0;
        
        while($row = $licencias->fetch_assoc()) {
            $estado = $row['estado'];
            
            if ($estado === 'VENCIDA') {
                $vencidas++;
            } elseif ($estado === 'POR VENCER') {
                $proximas++;
            } elseif ($estado === 'VIGENTE') {
                $vigentes++;
            }
        }
        
        // Volver al inicio para mostrar la tabla
        $licencias->data_seek(0);
        ?>
        <div class="stats-container">
            <div class="stat-card">
                <div class="number"><?= $total_licencias ?></div>
                <div class="label">Total Licencias</div>
            </div>
            <div class="stat-card">
                <div class="number text-success"><?= $vigentes ?></div>
                <div class="label">Vigentes</div>
            </div>
            <div class="stat-card">
                <div class="number text-warning"><?= $proximas ?></div>
                <div class="label">Por Vencer</div>
            </div>
            <div class="stat-card">
                <div class="number text-danger"><?= $vencidas ?></div>
                <div class="label">Vencidas</div>
            </div>
            <div class="stat-card">
                <div class="number text-info"><?= $vencidas + $proximas ?></div>
                <div class="label">Para Renovar</div>
            </div>
        </div>

        <!-- Formulario de creación -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i> Registrar Nueva Licencia</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="" id="formCrearLicencia">
                    <div class="form-grid">
                        <div class="field">
                            <label for="id_equipo"><i class="fas fa-desktop me-1"></i> Equipo <span class="text-danger">*</span></label>
                            <select class="form-select" name="id_equipo" id="id_equipo" required>
                                <option value="" selected disabled>Seleccione un equipo</option>
                                <?php while($eq = $equipos->fetch_assoc()): ?>
                                    <option value="<?= $eq['id_equipo'] ?>"><?= htmlspecialchars($eq['nombre_equipo']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="field">
                            <label for="id_software"><i class="fas fa-cube me-1"></i> Software <span class="text-danger">*</span></label>
                            <select class="form-select" name="id_software" id="id_software" required>
                                <option value="" selected disabled>Seleccione un software</option>
                                <?php while($sw = $software->fetch_assoc()): ?>
                                    <option value="<?= $sw['id_software'] ?>"><?= htmlspecialchars($sw['nombre_software'].' '.$sw['version']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="field">
                            <label for="id_usuario"><i class="fas fa-user me-1"></i> Usuario asignado <span class="text-danger">*</span></label>
                            <select class="form-select" name="id_usuario" id="id_usuario" required>
                                <option value="" selected disabled>Seleccione un usuario</option>
                                <?php while($us = $usuarios->fetch_assoc()): ?>
                                    <option value="<?= $us['id_usuario'] ?>"><?= htmlspecialchars($us['nombre']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="field">
                            <label for="tipo_licencia"><i class="fas fa-tag me-1"></i> Tipo de Licencia</label>
                            <input type="text" class="form-control" id="tipo_licencia" name="tipo_licencia" placeholder="Ej: Perpetua, Anual, Mensual...">
                        </div>

                        <div class="field">
                            <label for="fecha_inicio"><i class="fas fa-calendar-alt me-1"></i> Fecha de inicio <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <div class="field">
                            <label for="fecha_vencimiento"><i class="fas fa-calendar-times me-1"></i> Fecha de vencimiento <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="fecha_vencimiento" name="fecha_vencimiento" required>
                        </div>

                        <div class="field">
                            <label for="metodo_activacion"><i class="fas fa-key me-1"></i> Método de Activación</label>
                            <input type="text" class="form-control" id="metodo_activacion" name="metodo_activacion" placeholder="Ej: Clave de producto, Online...">
                            <small class="text-muted">Debe ser único en el sistema</small>
                        </div>

                        <div class="checkbox-field">
                            <input type="checkbox" class="form-check-input" id="renovable" name="renovable" value="1">
                            <label for="renovable" class="form-check-label">¿Es renovable?</label>
                        </div>

                        <div class="field full-width">
                            <label for="restricciones"><i class="fas fa-ban me-1"></i> Restricciones</label>
                            <textarea class="form-control" id="restricciones" name="restricciones" rows="2" placeholder="Restricciones de uso..."></textarea>
                        </div>

                        <div class="field full-width">
                            <label for="notas"><i class="fas fa-sticky-note me-1"></i> Notas Adicionales</label>
                            <textarea class="form-control" id="notas" name="notas" rows="2" placeholder="Información adicional..."></textarea>
                        </div>
                    </div>
                    
                    <div class="mt-3 d-flex gap-2">
                        <button type="submit" name="crear" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Registrar Licencia
                        </button>
                        <button type="reset" class="btn btn-secondary">
                            <i class="fas fa-undo me-1"></i> Limpiar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Lista de licencias -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-list me-2"></i> Licencias Registradas</h5>
                <span class="badge bg-primary"><?= $total_licencias ?> licencias</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Equipo</th>
                                <th>Software</th>
                                <th>Versión</th>
                                <th>Usuario</th>
                                <th>Tipo Licencia</th>
                                <th>Fecha inicio</th>
                                <th>Fecha vencimiento</th>
                                <th>Estado</th>
                                <th>Renovable</th>
                                <?php if ($rol === "ADMIN"): ?><th>Establecimiento</th><?php endif; ?>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php 
                        if ($licencias->num_rows > 0) {
                            while($row = $licencias->fetch_assoc()): 
                                $estado = $row['estado'];
                                $txt = $estado;
                                
                                if ($estado === 'VENCIDA') {
                                    $badge_class = "estado-vencida";
                                } elseif ($estado === 'POR VENCER') {
                                    $badge_class = "estado-proxima";
                                } else {
                                    $badge_class = "estado-vigente";
                                }
                        ?>
                            <tr>
                                <td><?= htmlspecialchars($row['nombre_equipo']) ?></td>
                                <td><?= htmlspecialchars($row['nombre_software']) ?></td>
                                <td><?= htmlspecialchars($row['version']) ?></td>
                                <td><?= htmlspecialchars($row['usuario']) ?></td>
                                <td><?= htmlspecialchars($row['tipo_licencia'] ?: 'N/A') ?></td>
                                <td><?= $row['fecha_inicio'] ?></td>
                                <td><?= $row['fecha_vencimiento'] ?></td>
                                <td><span class="estado-badge <?= $badge_class ?>"><?= $txt ?></span></td>
                                <td class="text-center">
                                    <?php if ($row['renovable']): ?>
                                        <span class="badge bg-success"><i class="fas fa-check"></i> Sí</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><i class="fas fa-times"></i> No</span>
                                    <?php endif; ?>
                                </td>
                                <?php if ($rol === "ADMIN"): ?><td><?= htmlspecialchars($row['nombre_establecimiento']) ?></td><?php endif; ?>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="editar_licencia.php?id=<?= $row['id_licencia'] ?>" class="btn btn-sm btn-outline-primary" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button class="btn btn-sm btn-renovar btn-renew" 
                                           data-id="<?= $row['id_licencia'] ?>"
                                           data-software="<?= htmlspecialchars($row['nombre_software'] . ' ' . $row['version']) ?>"
                                           data-equipo="<?= htmlspecialchars($row['nombre_equipo']) ?>"
                                           data-usuario="<?= htmlspecialchars($row['usuario']) ?>"
                                           data-vencimiento="<?= $row['fecha_vencimiento'] ?>"
                                           data-estado="<?= $txt ?>"
                                           title="Renovar">
                                            <i class="fas fa-sync-alt"></i>
                                        </button>
                                        <a href="#" class="btn btn-sm btn-outline-danger btn-delete" 
                                           data-id="<?= $row['id_licencia'] ?>"
                                           data-software="<?= htmlspecialchars($row['nombre_software'] . ' ' . $row['version']) ?>"
                                           data-equipo="<?= htmlspecialchars($row['nombre_equipo']) ?>"
                                           data-usuario="<?= htmlspecialchars($row['usuario']) ?>"
                                           data-tipo="<?= htmlspecialchars($row['tipo_licencia']) ?>"
                                           data-vencimiento="<?= $row['fecha_vencimiento'] ?>"
                                           data-estado="<?= $txt ?>"
                                           title="Eliminar">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php 
                            endwhile;
                        } else {
                            $colspan = $rol === "ADMIN" ? 11 : 10;
                            echo "<tr><td colspan='$colspan' class='text-center py-5 text-muted'>
                                <i class='fas fa-file-contract fa-3x mb-3'></i>
                                <h5>No hay licencias registradas</h5>
                                <p>Comienza registrando tu primera licencia usando el formulario superior.</p>
                            </td></tr>";
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Validación del formulario antes de enviar
            const formCrear = document.getElementById('formCrearLicencia');
            if (formCrear) {
                formCrear.addEventListener('submit', function(e) {
                    const fechaInicio = new Date(document.getElementById('fecha_inicio').value);
                    const fechaVencimiento = new Date(document.getElementById('fecha_vencimiento').value);
                    
                    if (fechaVencimiento <= fechaInicio) {
                        e.preventDefault();
                        alert('La fecha de vencimiento debe ser posterior a la fecha de inicio.');
                        return false;
                    }
                });
            }

            // Modal de eliminación
            const deleteButtons = document.querySelectorAll('.btn-delete');
            const deleteModal = document.getElementById('deleteModal');
            const cancelDeleteBtn = document.getElementById('cancelDelete');
            const confirmDeleteBtn = document.getElementById('confirmDelete');
            
            let deleteUrl = '';
            
            deleteButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const software = this.dataset.software;
                    const equipo = this.dataset.equipo;
                    const usuario = this.dataset.usuario;
                    const tipo = this.dataset.tipo;
                    const vencimiento = this.dataset.vencimiento;
                    const estado = this.dataset.estado;
                    
                    document.getElementById('licenciaSoftware').textContent = software;
                    document.getElementById('licenciaEquipo').textContent = equipo;
                    document.getElementById('licenciaUsuario').textContent = usuario;
                    document.getElementById('licenciaTipo').textContent = tipo || 'N/A';
                    document.getElementById('licenciaVencimiento').textContent = vencimiento;
                    document.getElementById('licenciaEstado').textContent = estado;
                    
                    const id = this.dataset.id;
                    deleteUrl = `gestionLicencias.php?eliminar=${id}`;
                    confirmDeleteBtn.href = deleteUrl;
                    
                    deleteModal.style.display = 'block';
                });
            });
            
            cancelDeleteBtn.addEventListener('click', function() {
                deleteModal.style.display = 'none';
            });
            
            window.addEventListener('click', function(e) {
                if (e.target === deleteModal) {
                    deleteModal.style.display = 'none';
                }
            });

            // Modal de renovación
            const renewButtons = document.querySelectorAll('.btn-renew');
            const renewModal = document.getElementById('renewModal');
            const cancelRenewBtn = document.getElementById('cancelRenew');
            const closeRenewModalBtn = document.getElementById('closeRenewModal');
            
            renewButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const id = this.dataset.id;
                    const software = this.dataset.software;
                    const equipo = this.dataset.equipo;
                    const usuario = this.dataset.usuario;
                    const vencimiento = this.dataset.vencimiento;
                    const estado = this.dataset.estado;
                    
                    document.getElementById('renewLicenciaId').value = id;
                    document.getElementById('renewSoftware').textContent = software;
                    document.getElementById('renewEquipo').textContent = `${equipo} (${usuario})`;
                    document.getElementById('renewVencimiento').textContent = vencimiento;
                    document.getElementById('renewEstado').textContent = estado;
                    
                    const hoy = new Date().toISOString().split('T')[0];
                    document.getElementById('nueva_fecha_inicio').value = hoy;
                    
                    const fechaMensual = new Date();
                    fechaMensual.setDate(fechaMensual.getDate() + 30);
                    document.getElementById('nueva_fecha_vencimiento').value = fechaMensual.toISOString().split('T')[0];
                    
                    updateDatePreview('mensual');
                    
                    renewModal.style.display = 'block';
                    
                    setTimeout(() => {
                        document.getElementById('nueva_fecha_inicio').focus();
                    }, 100);
                });
            });
            
            cancelRenewBtn.addEventListener('click', function() {
                renewModal.style.display = 'none';
            });
            
            closeRenewModalBtn.addEventListener('click', function() {
                renewModal.style.display = 'none';
            });
            
            window.addEventListener('click', function(e) {
                if (e.target === renewModal) {
                    renewModal.style.display = 'none';
                }
            });

            // Eventos de fechas
            document.getElementById('nueva_fecha_inicio')?.addEventListener('change', function() {
                const periodo = document.querySelector('input[name="periodo_renovacion"]:checked')?.value || 'mensual';
                updateDatePreview(periodo);
            });

            document.getElementById('nueva_fecha_vencimiento')?.addEventListener('change', function() {
                const periodo = document.querySelector('input[name="periodo_renovacion"]:checked')?.value || 'mensual';
                updateDatePreview(periodo);
            });

            document.querySelectorAll('input[name="periodo_renovacion"]').forEach(radio => {
                radio.addEventListener('change', function() {
                    selectPeriod(this.value);
                });
            });

            const modalBody = renewModal?.querySelector('.modal-body');
            if (modalBody) {
                modalBody.addEventListener('wheel', function(e) {
                    if (this.scrollHeight > this.clientHeight) {
                        e.stopPropagation();
                    }
                });
            }
        });

        function selectPeriod(period) {
            document.getElementById('periodMensual')?.classList.remove('selected');
            document.getElementById('periodAnual')?.classList.remove('selected');
            document.getElementById('period' + period.charAt(0).toUpperCase() + period.slice(1))?.classList.add('selected');
            
            document.getElementById('period' + period.charAt(0).toUpperCase() + period.slice(1) + 'Radio').checked = true;
            
            const fechaInicio = document.getElementById('nueva_fecha_inicio').value;
            if (fechaInicio) {
                updateDatePreview(period);
            }
        }

        function updateDatePreview(period) {
            const fechaInicio = document.getElementById('nueva_fecha_inicio').value;
            if (!fechaInicio) return;
            
            const fechaInicioObj = new Date(fechaInicio);
            const fechaVencimiento = document.getElementById('nueva_fecha_vencimiento').value;
            
            if (!fechaVencimiento) {
                if (period === 'mensual') {
                    fechaInicioObj.setDate(fechaInicioObj.getDate() + 30);
                } else {
                    fechaInicioObj.setFullYear(fechaInicioObj.getFullYear() + 1);
                }
                document.getElementById('nueva_fecha_vencimiento').value = fechaInicioObj.toISOString().split('T')[0];
            }
            
            document.getElementById('previewInicio').textContent = fechaInicio;
            document.getElementById('previewVencimiento').textContent = fechaVencimiento || fechaInicioObj.toISOString().split('T')[0];
            document.getElementById('previewPeriodo').textContent = period === 'mensual' ? 'Mensual (30 días)' : 'Anual (365 días)';
        }

        window.onload = function() {
            selectPeriod('mensual');
        };

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const renewModal = document.getElementById('renewModal');
                if (renewModal?.style.display === 'block') {
                    renewModal.style.display = 'none';
                }
            }
        });
    </script>
</body>
</html>
<?php
// ENVÍO DE NOTIFICACIONES POR CORREO
if ($rol === "ENCARGADO" && $licencias && $licencias->num_rows > 0) {
    $licencias->data_seek(0);
    $proximas = [];
    $vencidas = [];

    while ($row = $licencias->fetch_assoc()) {
        if ($row['estado'] === 'POR VENCER') {
            $proximas[] = $row;
        } elseif ($row['estado'] === 'VENCIDA') {
            $vencidas[] = $row;
        }
    }

    if (count($proximas) > 0 || count($vencidas) > 0) {
        // Configuración de PHPMailer (mantén tu código existente)
    }
}
?>