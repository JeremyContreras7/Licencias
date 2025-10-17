<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== "ENCARGADO") {
    header("Location: ../index.php");
    exit();
}

include("../controlador/conexion.php");

$id_establecimiento = $_SESSION['id_establecimiento'];

if (!isset($_GET['id'])) {
    header("Location: ../vista/gestionEquipos.php");
    exit();
}

$id_equipo = (int)$_GET['id'];

// Borrar con seguridad
$stmt = $conexion->prepare("DELETE FROM equipos WHERE id_equipo = ? AND id_establecimiento = ?");
$stmt->bind_param("ii", $id_equipo, $id_establecimiento);

if ($stmt->execute()) {
    echo "<script>alert('✅ Equipo eliminado correctamente.'); window.location='../vista/gestionEquipos.php';</script>";
} else {
    echo "<script>alert('❌ Error al eliminar el equipo.'); window.location='../vista/gestionEquipos.php';</script>";
}
