<?php
require("../../bd.php");
require __DIR__ . '/../../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

// Fechas
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');

// Total de ventas
$stmt = $conexion->prepare("SELECT SUM(pd.precio * pd.cantidad) AS total_ventas
    FROM tbl_pedidos_detalle pd
    JOIN tbl_pedidos p ON pd.pedido_id = p.ID
    WHERE p.fecha BETWEEN :inicio AND :fin");
$stmt->bindParam(':inicio', $fecha_inicio);
$stmt->bindParam(':fin', $fecha_fin);
$stmt->execute();
$total_ventas = $stmt->fetch(PDO::FETCH_ASSOC)['total_ventas'] ?? 0;

// Total de pedidos
$stmt = $conexion->prepare("SELECT COUNT(*) AS total_pedidos
    FROM tbl_pedidos
    WHERE fecha BETWEEN :inicio AND :fin");
$stmt->bindParam(':inicio', $fecha_inicio);
$stmt->bindParam(':fin', $fecha_fin);
$stmt->execute();
$total_pedidos = $stmt->fetch(PDO::FETCH_ASSOC)['total_pedidos'] ?? 0;

// Producto más vendido
$stmt = $conexion->prepare("SELECT pd.nombre, SUM(pd.cantidad) AS total_vendido
    FROM tbl_pedidos_detalle pd
    JOIN tbl_pedidos p ON pd.pedido_id = p.ID
    WHERE p.fecha BETWEEN :inicio AND :fin
    GROUP BY pd.nombre
    ORDER BY total_vendido DESC
    LIMIT 1");
$stmt->bindParam(':inicio', $fecha_inicio);
$stmt->bindParam(':fin', $fecha_fin);
$stmt->execute();
$producto_mas_vendido = $stmt->fetch(PDO::FETCH_ASSOC);

// Todos los productos
$stmt = $conexion->prepare("SELECT pd.nombre, SUM(pd.cantidad) AS total_vendido
    FROM tbl_pedidos_detalle pd
    JOIN tbl_pedidos p ON pd.pedido_id = p.ID
    WHERE p.fecha BETWEEN :inicio AND :fin
    GROUP BY pd.nombre
    ORDER BY total_vendido DESC");
$stmt->bindParam(':inicio', $fecha_inicio);
$stmt->bindParam(':fin', $fecha_fin);
$stmt->execute();
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Crear spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Título
$sheet->setCellValue('A1', "Reporte de Ventas desde $fecha_inicio hasta $fecha_fin");
$sheet->mergeCells('A1:B1');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
$sheet->getRowDimension(1)->setRowHeight(30);

// Métricas
$sheet->setCellValue('A3', 'Total Ventas');
$sheet->setCellValue('B3', $total_ventas);
$sheet->setCellValue('A4', 'Total Pedidos');
$sheet->setCellValue('B4', $total_pedidos);
$sheet->setCellValue('A5', 'Producto Más Vendido');
$sheet->setCellValue('B5', $producto_mas_vendido['nombre'].' ('.$producto_mas_vendido['total_vendido'].')');

// Estilo para métricas
foreach(range(3,5) as $row){
    $sheet->getStyle("A$row:B$row")->getFont()->setBold(true);
    $sheet->getStyle("A$row:B$row")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    $sheet->getStyle("A$row:B$row")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D9EDF7');
}

// Encabezados tabla de productos
$sheet->setCellValue('A7','Producto');
$sheet->setCellValue('B7','Cantidad Vendida');
$sheet->getStyle('A7:B7')->getFont()->setBold(true);
$sheet->getStyle('A7:B7')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('4287f5');
$sheet->getStyle('A7:B7')->getFont()->getColor()->setRGB('FFFFFF');

// Ajustar ancho de columnas
$sheet->getColumnDimension('A')->setWidth(50);
$sheet->getColumnDimension('B')->setWidth(25);

// Rellenar productos
$row = 8;
foreach($productos as $producto){
    $sheet->setCellValue("A$row", $producto['nombre']);
    $sheet->setCellValue("B$row", $producto['total_vendido']);
    $sheet->getStyle("A$row:B$row")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    $row++;
}

// Generar archivo
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="reporte.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>
