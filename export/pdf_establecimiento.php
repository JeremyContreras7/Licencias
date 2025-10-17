<?php 
require 'pdf/fpdf.php';
include('../modelo/conexion.php'); // Incluir conexión a la base de datos

// Crear PDF
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial','B',16);

// Título del reporte
$pdf->Cell(0, 10, 'LISTADO DE ESTABLECIMIENTO', 0, 1, 'C');
$pdf->Ln(10);

// Obtener datos de la base de datos
$query = "SELECT * FROM establecimientos";
$result = $conexion->query($query);

// Cabecera de la tabla
$pdf->SetFont('Arial','B',12);
$pdf->SetFillColor(67, 97, 238); // Color azul
$pdf->SetTextColor(255, 255, 255); // Texto blanco
$pdf->Cell(15, 10, 'ID', 1, 0, 'C', true);
$pdf->Cell(50, 10, 'NOMBRE ESTABLECIMIENTO', 1, 0, 'C', true);
$pdf->Cell(50, 10, 'CORREO', 1, 0, 'C', true);
$pdf->Cell(40, 10, 'TELEFONO', 1, 0, 'C', true);
$pdf->Cell(35, 10, 'TIPO DE ESCUELA', 1, 1, 'C', true);

// Datos de la tabla
$pdf->SetFont('Arial','',10);
$pdf->SetTextColor(0, 0, 0); // Texto negro
$fill = false; // Para alternar colores

while($row = $result->fetch_assoc()) {
    $pdf->SetFillColor(240, 240, 240); // Gris claro
    $pdf->Cell(15, 8, $row['id_establecimiento'], 1, 0, 'C', $fill);
    $pdf->Cell(50, 8, $row['nombre_establecimiento'], 1, 0, 'L', $fill);
    $pdf->Cell(50, 8, $row['correo'], 1, 0, 'L', $fill);
    $pdf->Cell(40, 8, $row['telefono'], 1, 0, 'L', $fill);
    $pdf->Cell(35, 8, $row['tipo_escuela'], 1, 1, 'L', $fill);
    
    $fill = !$fill; // Alternar color
}

// Pie de página con total
$total = $result->num_rows;
$pdf->Ln(10);
$pdf->SetFont('Arial','B',12);
$pdf->Cell(0, 10, "Total de establecimiento: $total", 0, 1, 'R');

// Fecha de generación
$pdf->SetFont('Arial','I',10);
$pdf->Cell(0, 10, 'Generado el: ' . date('d/m/Y H:i:s'), 0, 1, 'L');

// Output del PDF
$pdf->Output('I', 'inventario_establecimiento_' . date('Y-m-d') . '.pdf');
?>