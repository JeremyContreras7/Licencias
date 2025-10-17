<?php
session_start();
include('../modelo/conexion.php');

// =========================
// 🔹 Conexión MySQLi
// =========================
$conexion = new mysqli($host, $usuario, $password, $dbname);
if ($conexion->connect_error) {
    die("❌ Error en la conexión: " . $conexion->connect_error);
}
$conexion->set_charset("utf8");

// =========================
// 🔹 LOGIN
// =========================
if (isset($_POST['btnloginx'])) {
    $correo = $_POST['txtusuario'];
    $pass   = $_POST['txtpassword'];
    $rol    = $_POST['rol'];

    $sql = "SELECT * FROM usuarios WHERE correo = ? AND rol = ? LIMIT 1";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ss", $correo, $rol);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $usuario = $resultado->fetch_assoc();

    if ($usuario && password_verify($pass, $usuario['pass'])) {
        $_SESSION['id_usuario']       = $usuario['id_usuario'];   // 👈 corregido
        $_SESSION['nombre']           = $usuario['nombre'];
        $_SESSION['rol']              = $usuario['rol'];
        $_SESSION['id_establecimiento'] = $usuario['id_establecimiento']; // 👈 ahora usas FK
        $_SESSION['tipo_encargado']   = $usuario['tipo_encargado'];

        // Redirigir según rol
        if ($rol === "ADMIN") {
            header("Location: ../vista/menu.php");
        } elseif ($rol === "ENCARGADO") {
            header("Location: ../vista/menu_informatico.php");
        } elseif ($rol === "USUARIO") {
            header("Location: ../vista/menu_funcionario.php");
        }
        exit();
    } else {
        echo "<script>alert('❌ Credenciales incorrectas.'); window.location='../index.php';</script>";
    }
}

// =========================
// 🔹 REGISTRO DE NUEVOS USUARIOS
// =========================
if (isset($_POST['btnregistrarx'])) {
    $nombre           = $_POST['nombre'];
    $correo           = $_POST['correo'];
    $pass             = $_POST['pass'];
    $rol              = $_POST['rol'];
    $id_establecimiento = $_POST['id_establecimiento']; // 👈 ahora se usa el ID, no el nombre
    $tipo_encargado   = ($rol === "USUARIO") ? $_POST['tipo_encargado'] : null;

    // Verificar si ya existe el correo
    $sql = "SELECT COUNT(*) FROM usuarios WHERE correo = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $stmt->bind_result($existe);
    $stmt->fetch();
    $stmt->close();

    if ($existe == 0) {
        $pass_segura = password_hash($pass, PASSWORD_BCRYPT);

        $sql = "INSERT INTO usuarios (nombre, correo, pass, rol, id_establecimiento, tipo_encargado) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("ssssis", $nombre, $correo, $pass_segura, $rol, $id_establecimiento, $tipo_encargado);

        if ($stmt->execute()) {
            echo "<script>alert('✅ Usuario registrado correctamente: $nombre'); window.location='registrar.php';</script>";
        } else {
            echo "<script>alert('❌ Error al registrar el usuario.'); window.location='registrar.php';</script>";
        }
    } else {
        echo "<script>alert('⚠️ El correo ya está registrado: $correo'); window.location='registrar.php';</script>";
    }
}
?>
