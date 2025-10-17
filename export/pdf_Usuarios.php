<?php 
require 'pdf/fpdf.php';
include('../modelo/conexion.php');

class PDF extends FPDF {
    function Header() {
        // Logo en esquina superior izquierda
        if(file_exists('../img/logo.png')) {
            $this->Image('../img/logo.png', 10, 8, 25);
        }
        
        // Título al lado del logo
        $this->SetFont('Arial','B',16);
        $this->SetX(45); // Posición después del logo
        $this->Cell(0,10,'USUARIOS DEL SISTEMA',0,1,'L');
        
        // Línea decorativa
        $this->SetLineWidth(0.5);
        $this->SetDrawColor(67, 97, 238);
        $this->Line(10, 30, 200, 30);
        
        $this->Ln(12); // Espacio después del header
    }
    
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10,'Pagina '.$this->PageNo().'/{nb}',0,0,'C');
    }
}

// Crear PDF
$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();

// Obtener datos de la base de datos con JOIN correcto
$query = "SELECT u.*, e.nombre_establecimiento 
          FROM usuarios u 
          LEFT JOIN establecimientos e ON u.id_establecimiento = e.id_establecimiento";
$result = $conexion->query($query);

// Verificar si hay error en la consulta
if (!$result) {
    die('Error en la consulta: ' . $conexion->error);
}

// Cabecera de la tabla
$pdf->SetFont('Arial','B',12);
$pdf->SetFillColor(67, 97, 238);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(15, 10, 'ID', 1, 0, 'C', true);
$pdf->Cell(30, 10, 'NOMBRE', 1, 0, 'C', true);
$pdf->Cell(40, 10, 'CORREO', 1, 0, 'C', true);
$pdf->Cell(25, 10, 'ROL', 1, 0, 'C', true);
$pdf->Cell(73, 10, 'ESTABLECIMIENTO', 1, 1, 'C', true);

// Datos de la tabla
$pdf->SetFont('Arial','',10);
$pdf->SetTextColor(0, 0, 0);
$fill = false;
$total = 0;

while($row = $result->fetch_assoc()) {
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell(15, 8, $row['id_usuario'], 1, 0, 'C', $fill);
    $pdf->Cell(30, 8, $row['nombre'], 1, 0, 'L', $fill);
    $pdf->Cell(40, 8, $row['correo'], 1, 0, 'L', $fill);
    $pdf->Cell(25, 8, $row['rol'], 1, 0, 'L', $fill);
    $pdf->Cell(73, 8, $row['nombre_establecimiento'] ?? 'Sin asignar', 1, 1, 'L', $fill);
    $fill = !$fill;
    $total++;
}

// Pie de página con total
$pdf->Ln(10);
$pdf->SetFont('Arial','B',12);
$pdf->Cell(0, 10, "Total de usuarios: $total", 0, 1, 'R');

// Fecha de generación
$pdf->SetFont('Arial','I',10);
$pdf->Cell(0, 10, 'Generado el: ' . date('d/m/Y H:i:s'), 0, 1, 'L');

$pdf->Output('I', 'reporte_usuarios_' . date('Y-m-d') . '.pdf');
?>