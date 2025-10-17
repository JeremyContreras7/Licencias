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
$query = "SELECT u.*, e.nombre_establecimiento 
          FROM software u 
          LEFT JOIN establecimientos e ON u.id_establecimiento = e.id_establecimiento";
$result = $conexion->query($query);

// Cabecera de la tabla
$pdf->SetFont('Arial','B',12);
$pdf->SetFillColor(67, 97, 238); // Color azul
$pdf->SetTextColor(255, 255, 255); // Texto blanco
$pdf->Cell(15, 10, 'ID', 1, 0, 'C', true);
$pdf->Cell(50, 10, 'NOMBRE SOFTWARE', 1, 0, 'C', true);
$pdf->Cell(30, 10, 'VERSION', 1, 0, 'C', true);
$pdf->Cell(30, 10, 'ESTADO', 1, 0, 'C', true);
$pdf->Cell(73, 10, 'ESTABLECIMIENTO', 1, 1, 'C', true);

// Datos de la tabla
$pdf->SetFont('Arial','',10);
$pdf->SetTextColor(0, 0, 0); // Texto negro
$fill = false; // Para alternar colores

while($row = $result->fetch_assoc()) {
    $pdf->SetFillColor(240, 240, 240); // Gris claro
    $pdf->Cell(15, 8, $row['id_software'], 1, 0, 'C', $fill);
    $pdf->Cell(50, 8, $row['nombre_software'], 1, 0, 'L', $fill);
    $pdf->Cell(30, 8, $row['version'], 1, 0, 'L', $fill);
    $pdf->Cell(30, 8, $row['es_critico'], 1, 0, 'L', $fill);
    $pdf->Cell(73, 8, $row['nombre_establecimiento'] ?? 'Sin asignar', 1, 1, 'L', $fill);    
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