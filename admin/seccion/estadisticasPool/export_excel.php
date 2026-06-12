<?php
require_once __DIR__ . '/../../bd.php';
require_once __DIR__ . '/../../../app/Services/pool_schema.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

verificarRol('admin');
asegurarTablaPoolTurnos($conexion);

function poolExportValidarFecha(string $fecha): bool
{
    return (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) && strtotime($fecha) !== false;
}

$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');

if (!poolExportValidarFecha($fecha_inicio)) {
    $fecha_inicio = date('Y-m-01');
}

if (!poolExportValidarFecha($fecha_fin)) {
    $fecha_fin = date('Y-m-d');
}

$jornadas = poolListarJornadas($conexion, $fecha_inicio, $fecha_fin);
$resumen = poolResumenGeneral($jornadas);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Estadisticas Pool');

$sheet->setCellValue('A1', "Estadisticas de Pool desde $fecha_inicio hasta $fecha_fin");
$sheet->mergeCells('A1:J1');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);

$metricas = [
    ['Jornadas registradas', $resumen['total_jornadas']],
    ['Fichas vendidas', $resumen['total_vendidas']],
    ['Valor monetario vendido', $resumen['monto_vendido_total']],
    ['Valor monetario Pool Azul', $resumen['monto_vendido_azul']],
    ['Valor monetario Pool Rojo', $resumen['monto_vendido_rojo']],
    ['Fichas pendientes', $resumen['total_pendientes']],
    ['Promedio por jornada', $resumen['promedio_fichas_jornada']],
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
$headers = ['ID', 'Apertura', 'Cierre', 'Estado', 'Vendidas Azul', 'Vendidas Rojo', 'Valor Azul', 'Valor Rojo', 'Valor Total', 'Pendientes Azul', 'Pendientes Rojo', 'Jugadores', 'Completados', 'Duracion min'];
$col = 'A';
foreach ($headers as $header) {
    $sheet->setCellValue($col . $row, $header);
    $col++;
}
$sheet->getStyle("A$row:N$row")->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
$sheet->getStyle("A$row:N$row")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('4287F5');
$row++;

foreach ($jornadas as $jornada) {
    $sheet->setCellValue("A$row", $jornada['id']);
    $sheet->setCellValue("B$row", $jornada['fecha_apertura']);
    $sheet->setCellValue("C$row", $jornada['fecha_cierre'] ?: 'En curso');
    $sheet->setCellValue("D$row", $jornada['estado']);
    $sheet->setCellValue("E$row", $jornada['fichas_vendidas_azul']);
    $sheet->setCellValue("F$row", $jornada['fichas_vendidas_rojo']);
    $sheet->setCellValue("G$row", $jornada['monto_vendido_azul']);
    $sheet->setCellValue("H$row", $jornada['monto_vendido_rojo']);
    $sheet->setCellValue("I$row", $jornada['monto_vendido_total']);
    $sheet->setCellValue("J$row", $jornada['fichas_pendientes_azul']);
    $sheet->setCellValue("K$row", $jornada['fichas_pendientes_rojo']);
    $sheet->setCellValue("L$row", $jornada['jugadores_atendidos']);
    $sheet->setCellValue("M$row", $jornada['turnos_completados']);
    $sheet->setCellValue("N$row", $jornada['duracion_minutos'] ?? 'En curso');
    $sheet->getStyle("A$row:N$row")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    $row++;
}

foreach (range('A', 'N') as $column) {
    $sheet->getColumnDimension($column)->setAutoSize(true);
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="estadisticas_pool.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
