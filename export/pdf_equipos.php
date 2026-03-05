<?php 
require 'pdf/fpdf.php';
include('../modelo/conexion.php'); // Incluir conexión a la base de datos

// Crear PDF
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial','B',16);

// Título del reporte
$pdf->Cell(0, 10, 'INVENTARIO DE EQUIPOS', 0, 1, 'C');
$pdf->Ln(10);

// Obtener datos de la base de datos
$query = "SELECT * FROM equipos";
$result = $conexion->query($query);

// Cabecera de la tabla
$pdf->SetFont('Arial','B',12);
$pdf->SetFillColor(67, 97, 238); // Color azul
$pdf->SetTextColor(255, 255, 255); // Texto blanco

// Opción 1: Usar todos los parámetros posicionales (recomendado)
$pdf->Cell(10, 10, 'ID', 1, 0, 'C', true);
$pdf->Cell(41, 10, 'NOMBRE EQUIPO', 1, 0, 'C', true);
$pdf->Cell(46, 10, 'SISTEMA OPERATIVO', 1, 0, 'C', true);
$pdf->Cell(30, 10, 'MODELO', 1, 0, 'C', true);
$pdf->Cell(25, 10, 'SERIAL', 1, 0, 'C', true);
$pdf->Cell(25, 10, 'ESTADO', 1, 1, 'C', true); // CORREGIDO: 'ln: 1' cambiado a '1'

/*
// Opción 2: Usar argumentos nombrados (PHP 8+)
$pdf->Cell(35, 10, 'ESTADO', border: 1, ln: 1, align: 'C', fill: true);
*/

// Datos de la tabla
$pdf->SetFont('Arial','',10);
$pdf->SetTextColor(0, 0, 0); // Texto negro
$fill = false; // Para alternar colores

while($row = $result->fetch_assoc()) {
    $pdf->SetFillColor(240, 240, 240); // Gris claro
    $pdf->Cell(10, 8, $row['id_equipo'], 1, 0, 'C', $fill);
    $pdf->Cell(41, 8, $row['nombre_equipo'], 1, 0, 'L', $fill);
    $pdf->Cell(46, 8, $row['sistema_operativo'], 1, 0, 'L', $fill);
    $pdf->Cell(30, 8, $row['Modelo'], 1, 0, 'L', $fill);
    $pdf->Cell(25, 8, $row['Numero_serial'], 1, 0, 'L', $fill);
    $pdf->Cell(25, 8, $row['estado'], 1, 1, 'L', $fill);

    $fill = !$fill; // Alternar color
}

// Pie de página con total
$total = $result->num_rows;
$pdf->Ln(10);
$pdf->SetFont('Arial','B',12);
$pdf->Cell(0, 10, "Total de equipos: $total", 0, 1, 'R');

// Fecha de generación
$pdf->SetFont('Arial','I',10);
$pdf->Cell(0, 10, 'Generado el: ' . date('d/m/Y H:i:s'), 0, 1, 'L');

// Output del PDF
$pdf->Output('I', 'inventario_equipos_' . date('Y-m-d') . '.pdf');
?>