<?php
require_once __DIR__ . '/../../../../src/Http/Admin/acceso.php'; // permisos, sesión y CSRF del panel
require_once piccolo_path('src/Core/database.php');

use Piccolo\Http\Admin\EstadisticasController;
use Piccolo\Http\Api\ApiException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$estadisticas = new EstadisticasController($conexion);
try {
    $datos = $estadisticas->pool($_GET);
} catch (ApiException) {
    $datos = $estadisticas->pool([]);
}
$resumen = $datos['resumen'];
$pesos = '"$"#,##0.00';

$libro = new Spreadsheet();
$hoja = $libro->getActiveSheet();
$hoja->setTitle('Pool');
$hoja->setCellValue('A1', "Estadísticas del pool del {$datos['desde']} al {$datos['hasta']}");
$hoja->mergeCells('A1:E1');
$hoja->getStyle('A1')->getFont()->setBold(true)->setSize(16);

$fila = 3;
foreach ([
    ['Jornadas', $resumen['jornadas'], false],
    ['Fichas vendidas', $resumen['vendidas'], false],
    ['Fichas vendidas mesa azul', $resumen['mesas']['azul']['vendidas'], false],
    ['Fichas vendidas mesa roja', $resumen['mesas']['rojo']['vendidas'], false],
    ['Fichas pendientes', $resumen['pendientes'], false],
    ['Promedio de fichas por jornada', $resumen['promedio_por_jornada'], false],
    ['Valor vendido', $resumen['monto'], true],
    ['Valor vendido mesa azul', $resumen['mesas']['azul']['monto'], true],
    ['Valor vendido mesa roja', $resumen['mesas']['rojo']['monto'], true],
] as [$nombre, $valor, $esDinero]) {
    $hoja->fromArray([$nombre, $valor], null, "A$fila", true);
    $hoja->getStyle("A$fila:B$fila")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    $hoja->getStyle("A$fila:B$fila")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D9EDF7');
    if ($esDinero) {
        $hoja->getStyle("B$fila")->getNumberFormat()->setFormatCode($pesos);
    }
    $fila++;
}

$fila += 2;
$columnas = ['Jornada', 'Apertura', 'Cierre', 'Estado', 'Vendidas azul', 'Vendidas roja', 'Valor azul', 'Valor roja', 'Valor total', 'Pendientes', 'Jugadores', 'Turnos completos', 'Duración (min)'];
$hoja->fromArray($columnas, null, "A$fila");
$hoja->getStyle("A$fila:M$fila")->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
$hoja->getStyle("A$fila:M$fila")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('4287F5');
$fila++;

foreach ($datos['jornadas'] as $j) {
    $hoja->fromArray([
        $j['id'], $j['apertura'], $j['cierre'] ?? 'En curso', ucfirst($j['estado']),
        $j['mesas']['azul']['vendidas'], $j['mesas']['rojo']['vendidas'],
        $j['mesas']['azul']['monto'], $j['mesas']['rojo']['monto'], $j['monto'],
        $j['pendientes'], $j['jugadores'], $j['turnos_completados'], $j['duracion_minutos'] ?? 'En curso',
    ], null, "A$fila", true);
    $hoja->getStyle("G$fila:I$fila")->getNumberFormat()->setFormatCode($pesos);
    $hoja->getStyle("A$fila:M$fila")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    $fila++;
}

foreach (range('A', 'M') as $columna) {
    $hoja->getColumnDimension($columna)->setAutoSize(true);
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"pool_{$datos['desde']}_{$datos['hasta']}.xlsx\"");
header('Cache-Control: max-age=0');
(new Xlsx($libro))->save('php://output');
exit;
