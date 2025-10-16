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
$pdf->Cell(15, 10, 'ID', 1, 0, 'C', true);
$pdf->Cell(50, 10, 'NOMBRE EQUIPO', 1, 0, 'C', true);
$pdf->Cell(50, 10, 'SISTEMA OPERATIVO', 1, 0, 'C', true);
$pdf->Cell(40, 10, 'MODELO', 1, 0, 'C', true);
$pdf->Cell(35, 10, 'N° SERIAL', 1, 1, 'C', true);

// Datos de la tabla
$pdf->SetFont('Arial','',10);
$pdf->SetTextColor(0, 0, 0); // Texto negro
$fill = false; // Para alternar colores

while($row = $result->fetch_assoc()) {
    $pdf->SetFillColor(240, 240, 240); // Gris claro
    $pdf->Cell(15, 8, $row['id_equipo'], 1, 0, 'C', $fill);
    $pdf->Cell(50, 8, $row['nombre_equipo'], 1, 0, 'L', $fill);
    $pdf->Cell(50, 8, $row['sistema_operativo'], 1, 0, 'L', $fill);
    $pdf->Cell(40, 8, $row['Modelo'], 1, 0, 'L', $fill);
    $pdf->Cell(35, 8, $row['Numero_serial'], 1, 1, 'L', $fill);
    
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