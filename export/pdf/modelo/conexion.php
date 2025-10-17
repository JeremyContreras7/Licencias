<?php
$host = "127.0.0.1";          // Servidor MySQL
$puerto = "3306";             // Puerto
$usuario = "root";            // Usuario Workbench
$password = "halamadrid";  // Contraseña Workbench
$dbname = "sistema_licencias";    // Base de datos

$conexion = new mysqli($host, $usuario, $password, $dbname, $puerto);

if ($conexion->connect_error) {
    die("❌ Error en la conexión: " . $conexion->connect_error);
} else {
}
?>
