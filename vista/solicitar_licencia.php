<?php
session_start();
if(!isset($_SESSION['rol']) || $_SESSION['rol'] !== "USUARIO") {
    header("Location: ../index.php");
    exit;
}
?>
<!DOCTYPE HTML>
<html lang="es">
<head>
    <title>Solicitar Nueva Licencia - Licentix</title>
    <meta charset="utf-8" />
    <link rel="stylesheet" href="../css/solicitar_licencia.css">
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" href="../img/logo.png">
</head>
<body>
    <!-- Elementos decorativos flotantes -->
    <div class="floating-shape shape-1"></div>
    <div class="floating-shape shape-2"></div>
    <div class="floating-shape shape-3"></div>

    <div class="solicitud-card">
        <!-- Header con efecto glass -->
        <div class="solicitud-header">
            <div class="header-content">
                <div class="header-icon">
                    <i class="fas fa-file-signature"></i>
                </div>
                <div class="header-text">
                    <h1>Nueva Solicitud de Licencia</h1>
                    <p>
                        <i class="fas fa-clock"></i>
                        Tiempo estimado: 5 minutos
                    </p>
                </div>
            </div>
        </div>

        <!-- Stats rápidas -->
        <div class="quick-stats">
            <div class="stat-item">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3>3 Pasos</h3>
                    <p>Completa el formulario</p>
                </div>
            </div>
            <div class="stat-item">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <h3>48h</h3>
                    <p>Tiempo de respuesta</p>
                </div>
            </div>
            <div class="stat-item">
                <div class="stat-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <div class="stat-info">
                    <h3>Seguro</h3>
                    <p>Datos protegidos</p>
                </div>
            </div>
        </div>

        <!-- Contenedor de alertas -->
        <div id="alertContainer" class="alert">
            <i class="fas fa-info-circle"></i>
            <span id="alertMessage"></span>
        </div>

        <!-- Cuerpo del formulario -->
        <div class="solicitud-body">
            <!-- Pestañas de navegación -->
            <div class="form-tabs">
                <button class="tab-btn active" onclick="showTab(1)" id="tab1Btn">
                    <i class="fas fa-cube"></i>
                    Software
                </button>
                <button class="tab-btn" onclick="showTab(2)" id="tab2Btn">
                    <i class="fas fa-building"></i>
                    Departamento
                </button>
                <button class="tab-btn" onclick="showTab(3)" id="tab3Btn">
                    <i class="fas fa-file-alt"></i>
                    Justificación
                </button>
            </div>

            <form id="frmSolicitud" method="POST" action="../controlador/procesar_solicitud.php" autocomplete="off">
                <!-- Tab 1: Software -->
                <div id="tab1" class="tab-content active">
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label class="form-label" for="software">
                                <i class="fas fa-cube"></i>
                                Nombre del Software
                                <span class="required-badge">Requerido</span>
                            </label>
                            <div class="input-wrapper">
                                <i class="fas fa-search input-icon"></i>
                                <input type="text" class="form-control" id="software" name="nombre_software" 
                                       placeholder="Ej: Microsoft Office 365, Adobe Photoshop, AutoCAD" 
                                       required 
                                       maxlength="100"
                                       data-tooltip="Ingresa el nombre completo del software que necesitas">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="version">
                                <i class="fas fa-tag"></i>
                                Versión
                                <span class="optional-badge">Opcional</span>
                            </label>
                            <div class="input-wrapper">
                                <i class="fas fa-code-branch input-icon"></i>
                                <input type="text" class="form-control" id="version" name="version" 
                                       placeholder="Ej: 2024, Pro, Enterprise"
                                       maxlength="50"
                                       data-tooltip="Especifica la versión si es importante">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="tipo_licencia">
                                <i class="fas fa-tag"></i>
                                Tipo de Licencia
                                <span class="required-badge">Requerido</span>
                            </label>
                            <div class="input-wrapper">
                                <i class="fas fa-certificate input-icon"></i>
                                <select class="custom-select" id="tipo_licencia" name="tipo_licencia" required>
                                    <option value="" disabled selected>Selecciona un tipo</option>
                                    <option value="individual">Individual</option>
                                    <option value="empresarial">Empresarial</option>
                                    <option value="educativa">Educativa</option>
                                    <option value="trial">Trial / Prueba</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="cantidad">
                                <i class="fas fa-layer-group"></i>
                                Cantidad
                                <span class="required-badge">Requerido</span>
                            </label>
                            <div class="input-wrapper">
                                <i class="fas fa-sort-numeric-up input-icon"></i>
                                <input type="number" class="form-control" id="cantidad" name="cantidad" 
                                       min="1" max="1000" value="1" required
                                       data-tooltip="Número de licencias que necesitas">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab 2: Departamento -->
                <div id="tab2" class="tab-content">
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label class="form-label" for="departamento">
                                <i class="fas fa-building"></i>
                                Departamento/Área
                                <span class="required-badge">Requerido</span>
                            </label>
                            <div class="input-wrapper">
                                <i class="fas fa-sitemap input-icon"></i>
                                <input type="text" class="form-control" id="departamento" name="departamento" 
                                       placeholder="Ej: Informática, Recursos Humanos, Marketing" 
                                       required
                                       maxlength="50"
                                       data-tooltip="Departamento al que perteneces">
                            </div>
                        </div>

                        <div class="form-group full-width">
                            <label class="form-label" for="presupuesto">
                                <i class="fas fa-dollar-sign"></i>
                                Presupuesto Estimado
                                <span class="optional-badge">Opcional</span>
                            </label>
                            <div class="input-wrapper">
                                <i class="fas fa-coins input-icon"></i>
                                <input type="number" class="form-control" id="presupuesto" name="presupuesto" 
                                       min="0" step="0.01" placeholder="0.00"
                                       data-tooltip="Presupuesto aproximado (si lo conoces)">
                            </div>
                        </div>

                        <!-- Checklist de requisitos -->
                        <div class="form-group full-width">
                            <div class="checklist">
                                <div class="checklist-title">
                                    <i class="fas fa-clipboard-list"></i>
                                    Requisitos del Departamento
                                </div>
                                <div class="checklist-item">
                                    <input type="checkbox" id="req1" checked disabled>
                                    <label for="req1">El software es necesario para operaciones diarias</label>
                                </div>
                                <div class="checklist-item">
                                    <input type="checkbox" id="req2">
                                    <label for="req2">Existe presupuesto asignado para esta compra</label>
                                </div>
                                <div class="checklist-item">
                                    <input type="checkbox" id="req3">
                                    <label for="req3">Se ha consultado con el equipo de TI</label>
                                </div>
                                <div class="checklist-item">
                                    <input type="checkbox" id="req4">
                                    <label for="req4">El software cumple con políticas de seguridad</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab 3: Justificación -->
                <div id="tab3" class="tab-content">
                    <div class="form-group full-width">
                        <label class="form-label" for="justificacion">
                            <i class="fas fa-pen"></i>
                            Justificación Detallada
                            <span class="required-badge">Requerido</span>
                        </label>
                        <div class="input-wrapper">
                            <i class="fas fa-quote-right input-icon"></i>
                            <textarea class="form-control" id="justificacion" name="justificacion" 
                                      rows="5" 
                                      placeholder="Explica detalladamente:&#10;• Por qué necesitas esta licencia&#10;• Para qué proyectos o actividades&#10;• Beneficios esperados&#10;• Alternativas consideradas" 
                                      required
                                      maxlength="500"
                                      oninput="updateCharCounter(this, 500)"
                                      data-tooltip="Sé específico, esto ayudará a aprobar tu solicitud"></textarea>
                        </div>
                        <div class="char-counter normal" id="justificacionCounter">
                            <i class="fas fa-keyboard"></i>
                            <span id="currentChars">0</span>/500 caracteres
                        </div>
                    </div>

                    <!-- Timeline de proceso -->
                    <div class="timeline">
                        <div class="timeline-step">
                            <div class="step-number">1</div>
                            <div class="step-content">
                                <h4>Revisión inicial</h4>
                                <p>El administrador revisará tu solicitud (12-24h)</p>
                            </div>
                        </div>
                        <div class="timeline-step">
                            <div class="step-number">2</div>
                            <div class="step-content">
                                <h4>Validación de presupuesto</h4>
                                <p>Se verificará la disponibilidad de fondos</p>
                            </div>
                        </div>
                        <div class="timeline-step">
                            <div class="step-number">3</div>
                            <div class="step-content">
                                <h4>Aprobación final</h4>
                                <p>Respuesta final y asignación de licencias</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botones de navegación entre tabs -->
                <div style="display: flex; gap: 12px; margin-top: 30px;">
                    <button type="button" class="btn btn-secondary" id="prevBtn" onclick="navigateTabs(-1)" style="display: none;">
                        <i class="fas fa-arrow-left"></i>
                        <span class="btn-text">Anterior</span>
                    </button>
                    <button type="button" class="btn btn-primary" id="nextBtn" onclick="navigateTabs(1)">
                        <span class="btn-text">Siguiente</span>
                        <i class="fas fa-arrow-right"></i>
                    </button>
                    <button type="submit" class="btn btn-primary" id="submitBtn" name="btnSolicitar" style="display: none;">
                        <span class="btn-text">Enviar Solicitud</span>
                        <i class="fas fa-paper-plane"></i>
                    </button>
                    <a href="menu_funcionario.php" class="btn btn-secondary" id="cancelBtn">
                        <i class="fas fa-times"></i>
                        <span class="btn-text">Cancelar</span>
                    </a>
                </div>
            </form>
        </div>

        <!-- Footer -->
        <div class="solicitud-footer">
            <div>
                <i class="fas fa-lock" style="margin-right: 8px; color: #667eea;"></i>
                Todos los datos están encriptados
            </div>
           
        </div>
    </div>
<script src="../js/solicitar_licencia.js"></script>
</body>
</html>