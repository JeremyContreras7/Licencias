<?php
// Evitar caching para mayor seguridad
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
session_start();
?>
<!DOCTYPE HTML>
<html lang="es">
<head>
    <title>Licentix - Sistema de Gestión de Licencias</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
    <link rel="stylesheet" href="css/stylelogin.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" href="/img/logo.png">
</head>

<body>
    <div class="login-container">
        <!-- Panel Izquierdo - Marca -->
        <div class="brand-panel">
            <div class="brand-content">
                <img src="img/logo2.jpg" alt="Logo Institución" class="logo">
                <h1 class="brand-title">Licentix</h1>
                <p class="brand-subtitle">Sistema de Gestión de Licencias</p>
                
                <div class="features">
                    <div class="feature">
                        <i class="fas fa-shield-alt"></i>
                        <span>Acceso seguro y encriptado</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-clock"></i>
                        <span>Gestión en tiempo real</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-chart-line"></i>
                        <span>Reportes y analíticas</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Panel Derecho - Formulario -->
        <div class="login-panel">
            <div class="login-header">
                <h2 class="login-title">Bienvenido</h2>
                <p class="login-subtitle">Ingresa a tu cuenta para continuar</p>
            </div>

            <!-- Formulario de inicio de sesión -->
            <form id="frmlogin" class="grupo-entradas" method="POST" action="controlador/validar.php" autocomplete="off">
                <!-- Email -->
                <div class="form-group">
                    <label class="form-label" for="email">
                        <i class="fas fa-envelope"></i>
                        Correo Institucional
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-user input-icon"></i>
                        <input type="email" class="cajaentradatexto" id="email" name="txtusuario" placeholder="usuario@institucion.edu" required>
                    </div>
                </div>

                <!-- Password -->
                <div class="form-group">
                    <label class="form-label" for="password">
                        <i class="fas fa-lock"></i>
                        Contraseña
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-key input-icon"></i>
                        <input type="password" class="cajaentradatexto" id="password" placeholder="Ingresa tu contraseña" name="txtpassword" id="txtpassword" required>
                    </div>
                </div>

                <!-- Show Password -->
                <div class="checkbox-wrapper">
                    <input type="checkbox" id="showPassword" onclick="verpassword()">
                    <label for="showPassword">Mostrar contraseña</label>
                </div>

                <!-- Role Selection -->
                <div class="form-group">
                    <label class="form-label" for="role">
                        <i class="fas fa-user-tag"></i>
                        Tipo de Usuario
                    </label>
                    <div class="select-wrapper">
                        <i class="fas fa-briefcase input-icon"></i>
                        <select name="rol" id="role" required>
                            <option disabled selected value="">Selecciona tu rol</option>
                            <option value="ADMIN">Administrador del Sistema</option>
                            <option value="ENCARGADO">Encargado Informático</option>
                            <option value="USUARIO">Personal Escolar</option>
                        </select>
                    </div>
                </div>

                <!-- Role Indicators -->
                <div class="role-indicators">
                    <div class="role-indicator">
                        <i class="fas fa-crown"></i>
                        <span>Admin</span>
                    </div>
                    <div class="role-indicator">
                        <i class="fas fa-laptop-code"></i>
                        <span>Encargado</span>
                    </div>
                    <div class="role-indicator">
                        <i class="fas fa-users"></i>
                        <span>Personal</span>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="botonenviar" name="btnloginx" id="loginBtn">
                    <span class="btn-text">Iniciar Sesión</span>
                    <i class="fas fa-arrow-right"></i>
                </button>
            </form>

            <!-- Footer -->
            <div class="login-footer">
                <p><i class="fas fa-info-circle"></i> Departamento de educacion municipal ovalle</p>
            </div>
        </div>
    </div>
<script src="js/login.js"></script>

</body>
</html>