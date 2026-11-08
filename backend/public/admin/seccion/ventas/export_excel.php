<?php
require_once __DIR__ . '/../../../../src/Http/Admin/acceso.php'; // permisos, sesión y CSRF del panel
require_once piccolo_path('src/Core/database.php');

use Piccolo\Http\Admin\VentasController;
use Piccolo\Http\Api\ApiException;
use Piccolo\Repositories\VentasRepositorio;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$ventas = new VentasController(new VentasRepositorio($conexion), $conexion);
try {
    $resumen = $ventas->resumen($_GET);
} catch (ApiException) {
    $resumen = $ventas->resumen([]);
}

$hoja = (new Spreadsheet())->getActiveSheet();
$fila = 1;
$formatoPesos = '"$"#,##0.00';

$hoja->setCellValue('A1', "Reporte de ventas del {$resumen['desde']} al {$resumen['hasta']}");
$hoja->mergeCells('A1:C1');
$hoja->getStyle('A1')->getFont()->setBold(true)->setSize(16);
$hoja->getRowDimension(1)->setRowHeight(30);

/** Encabezado de una tabla, con fondo azul. */
$encabezado = static function (array $columnas) use ($hoja, &$fila): void {
    $hoja->fromArray($columnas, null, "A$fila");
    $rango = 'A' . $fila . ':' . chr(ord('A') + count($columnas) - 1) . $fila;
    $hoja->getStyle($rango)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
    $hoja->getStyle($rango)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('4287F5');
    $fila++;
};
/** Una fila de datos con borde fino. */
$linea = static function (array $valores) use ($hoja, &$fila): void {
    $hoja->fromArray($valores, null, "A$fila", true);
    $rango = 'A' . $fila . ':' . chr(ord('A') + count($valores) - 1) . $fila;
    $hoja->getStyle($rango)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    $fila++;
};
/** Título de un bloque. */
$titulo = static function (string $texto) use ($hoja, &$fila): void {
    $fila += 2;
    $hoja->setCellValue("A$fila", $texto);
    $hoja->getStyle("A$fila")->getFont()->setBold(true)->setSize(14);
    $fila++;
};

$fila = 3;
$estrella = $resumen['producto_estrella'];
foreach ([
    ['Total vendido', $resumen['total']],
    ['Pedidos', $resumen['por_origen']['pedidos']],
    ['Fichas de pool', $resumen['por_origen']['fichas']],
    ['Bebidas', $resumen['por_origen']['bebidas']],
    ['Cantidad de pedidos (sin cancelados)', $resumen['cantidad_pedidos']],
    ['Ticket promedio', $resumen['ticket_promedio']],
    ['Producto estrella', $estrella ? "{$estrella['nombre']} ({$estrella['cantidad']})" : 'Sin ventas'],
] as [$nombre, $valor]) {
    $linea([$nombre, $valor]);
    $hoja->getStyle('A' . ($fila - 1) . ':B' . ($fila - 1))->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D9EDF7');
    if (is_float($valor)) {
        $hoja->getStyle('B' . ($fila - 1))->getNumberFormat()->setFormatCode($formatoPesos);
    }
}

$titulo('Productos vendidos');
$encabezado(['Producto', 'Unidades', 'Monto (precio de lista)']);
foreach ($resumen['productos'] as $producto) {
    $linea([$producto['nombre'], $producto['cantidad'], $producto['monto']]);
    $hoja->getStyle('C' . ($fila - 1))->getNumberFormat()->setFormatCode($formatoPesos);
}

foreach ([['Métodos de pago', 'Método', $resumen['metodos_pago']], ['Tipos de entrega', 'Tipo', $resumen['tipos_entrega']]] as [$nombre, $columna, $grupos]) {
    $titulo($nombre);
    $encabezado([$columna, 'Pedidos', 'Monto']);
    foreach ($grupos as $grupo) {
        $linea([ucfirst($grupo['nombre']), $grupo['pedidos'], $grupo['monto']]);
        $hoja->getStyle('C' . ($fila - 1))->getNumberFormat()->setFormatCode($formatoPesos);
    }
}

$titulo("Ventas por mes de {$resumen['mensual']['anio']}");
$encabezado(['Mes', 'Pedidos', 'Fichas', 'Bebidas', 'Total']);
foreach ($resumen['mensual']['meses'] as $mes) {
    $linea([$mes['nombre'], $mes['pedidos'], $mes['fichas'], $mes['bebidas'], $mes['total']]);
    $hoja->getStyle('B' . ($fila - 1) . ':E' . ($fila - 1))->getNumberFormat()->setFormatCode($formatoPesos);
}

$hoja->getColumnDimension('A')->setWidth(40);
foreach (['B', 'C', 'D', 'E'] as $columna) {
    $hoja->getColumnDimension($columna)->setWidth(20);
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"ventas_{$resumen['desde']}_{$resumen['hasta']}.xlsx\"");
header('Cache-Control: max-age=0');
(new Xlsx($hoja->getParent()))->save('php://output');
exit;
