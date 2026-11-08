<?php
require_once __DIR__ . '/../../../../src/Http/Admin/acceso.php'; // permisos, sesión y CSRF del panel
require_once piccolo_path('src/Core/database.php');

use Dompdf\Dompdf;
use Dompdf\Options;
use Piccolo\Http\Admin\VentasController;
use Piccolo\Http\Api\ApiException;
use Piccolo\Repositories\VentasRepositorio;

$ventas = new VentasController(new VentasRepositorio($conexion), $conexion);
try {
    $resumen = $ventas->resumen($_GET);
} catch (ApiException) {
    $resumen = $ventas->resumen([]);
}

ob_start();
piccolo_render(piccolo_path('views/admin/ventas/pdf.php'), ['resumen' => $resumen]);
$html = (string) ob_get_clean();

// Los gráficos se dibujan en el mismo HTML: el PDF no descarga nada de internet
// ni manda los datos de ventas a otro servicio.
$opciones = new Options();
$opciones->set('isRemoteEnabled', false);
$opciones->set('defaultFont', 'DejaVu Sans');
$pdf = new Dompdf($opciones);
$pdf->loadHtml($html, 'UTF-8');
$pdf->setPaper('A4', 'portrait');
$pdf->render();
$pdf->stream("ventas_{$resumen['desde']}_{$resumen['hasta']}.pdf", ['Attachment' => true]);
