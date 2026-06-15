<?php
require("../../bd.php");
require_once __DIR__ . '/../../../app/Services/pool_schema.php';
require_once __DIR__ . '/../../../app/Services/bebidas_schema.php';
require __DIR__ . '/../../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function validarFecha($fecha) {
  return preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) && strtotime($fecha);
}

$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fecha_fin_input = $_GET['fecha_fin'] ?? date('Y-m-d');

if (!validarFecha($fecha_inicio)) $fecha_inicio = date('Y-m-01');
if (!validarFecha($fecha_fin_input)) $fecha_fin_input = date('Y-m-d');

$fecha_fin = $fecha_fin_input . ' 23:59:59';

$stmt = $conexion->prepare("SELECT SUM(pd.precio * pd.cantidad) AS total_ventas
    FROM tbl_pedidos_detalle pd
    JOIN tbl_pedidos p ON pd.pedido_id = p.ID
    WHERE p.fecha BETWEEN :inicio AND :fin");
$stmt->execute([':inicio' => $fecha_inicio, ':fin' => $fecha_fin]);
$total_ventas_tradicionales = (float) ($stmt->fetch(PDO::FETCH_ASSOC)['total_ventas'] ?? 0);
$total_ventas_fichas = poolMontoVendidoEntreFechas($conexion, $fecha_inicio, $fecha_fin);
$total_ventas_bebidas = bebidaMontoVendidoEntreFechas($conexion, $fecha_inicio, $fecha_fin);
$total_ventas = $total_ventas_tradicionales + $total_ventas_fichas + $total_ventas_bebidas;

$stmt = $conexion->prepare("SELECT COUNT(*) AS total_pedidos FROM tbl_pedidos WHERE fecha BETWEEN :inicio AND :fin");
$stmt->execute([':inicio' => $fecha_inicio, ':fin' => $fecha_fin]);
$total_pedidos = (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total_pedidos'] ?? 0);

$stmt = $conexion->prepare("SELECT pd.nombre, SUM(pd.cantidad) AS total_vendido
    FROM tbl_pedidos_detalle pd
    JOIN tbl_pedidos p ON pd.pedido_id = p.ID
    WHERE p.fecha BETWEEN :inicio AND :fin
    GROUP BY pd.nombre
    ORDER BY total_vendido DESC");
$stmt->execute([':inicio' => $fecha_inicio, ':fin' => $fecha_fin]);
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
$producto_mas_vendido = $productos[0] ?? ['nombre' => 'N/A', 'total_vendido' => 0];

$stmt = $conexion->prepare("SELECT metodo_pago, COUNT(*) AS total FROM tbl_pedidos WHERE fecha BETWEEN :inicio AND :fin GROUP BY metodo_pago");
$stmt->execute([':inicio' => $fecha_inicio, ':fin' => $fecha_fin]);
$metodos_pago = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conexion->prepare("SELECT tipo_entrega, COUNT(*) AS total FROM tbl_pedidos WHERE fecha BETWEEN :inicio AND :fin GROUP BY tipo_entrega");
$stmt->execute([':inicio' => $fecha_inicio, ':fin' => $fecha_fin]);
$tipos_entrega = $stmt->fetchAll(PDO::FETCH_ASSOC);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$sheet->setCellValue('A1', "Reporte de Ventas desde $fecha_inicio hasta $fecha_fin_input");
$sheet->mergeCells('A1:B1');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
$sheet->getRowDimension(1)->setRowHeight(30);

$metricas = [
    ['Total Ventas', $total_ventas],
    ['Ventas tradicionales', $total_ventas_tradicionales],
    ['Venta de fichas', $total_ventas_fichas],
    ['Venta de bebidas', $total_ventas_bebidas],
    ['Total Pedidos', $total_pedidos],
    ['Producto Mas Vendido', ($producto_mas_vendido['nombre'] ?? 'N/A') . ' (' . ($producto_mas_vendido['total_vendido'] ?? 0) . ')'],
];

$row = 3;
foreach ($metricas as $metrica) {
    $sheet->setCellValue("A$row", $metrica[0]);
    $sheet->setCellValue("B$row", $metrica[1]);
    $sheet->getStyle("A$row:B$row")->getFont()->setBold(true);
    $sheet->getStyle("A$row:B$row")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    $sheet->getStyle("A$row:B$row")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D9EDF7');
    $row++;
}

$row += 2;
$sheet->setCellValue("A$row", 'Producto');
$sheet->setCellValue("B$row", 'Cantidad Vendida');
$sheet->getStyle("A$row:B$row")->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
$sheet->getStyle("A$row:B$row")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('4287F5');
$row++;

foreach ($productos as $producto) {
    $sheet->setCellValue("A$row", $producto['nombre']);
    $sheet->setCellValue("B$row", $producto['total_vendido']);
    $sheet->getStyle("A$row:B$row")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    $row++;
}

foreach ([['Metodos de Pago', 'Metodo', $metodos_pago, 'metodo_pago'], ['Tipos de Entrega', 'Tipo', $tipos_entrega, 'tipo_entrega']] as $bloque) {
    $row += 2;
    $sheet->setCellValue("A$row", $bloque[0]);
    $sheet->getStyle("A$row")->getFont()->setBold(true)->setSize(14);
    $row++;
    $sheet->setCellValue("A$row", $bloque[1]);
    $sheet->setCellValue("B$row", 'Cantidad');
    $sheet->getStyle("A$row:B$row")->getFont()->setBold(true);
    $row++;

    foreach ($bloque[2] as $item) {
        $sheet->setCellValue("A$row", ucfirst($item[$bloque[3]] ?? 'N/A'));
        $sheet->setCellValue("B$row", $item['total'] ?? 0);
        $sheet->getStyle("A$row:B$row")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $row++;
    }
}

$sheet->getColumnDimension('A')->setWidth(50);
$sheet->getColumnDimension('B')->setWidth(25);

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="reporte_ventas.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
