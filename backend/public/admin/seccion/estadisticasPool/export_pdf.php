<?php
require_once __DIR__ . '/../../../../src/Http/Admin/acceso.php'; // permisos, sesión y CSRF del panel
require_once piccolo_path('src/Core/database.php');

use Dompdf\Dompdf;
use Dompdf\Options;
use Piccolo\Http\Admin\EstadisticasController;
use Piccolo\Http\Api\ApiException;

$estadisticas = new EstadisticasController($conexion);
try {
    $datos = $estadisticas->pool($_GET);
} catch (ApiException) {
    $datos = $estadisticas->pool([]);
}

ob_start();
piccolo_render(piccolo_path('views/admin/estadisticas/pool_pdf.php'), ['datos' => $datos]);
$html = (string) ob_get_clean();

// Los gráficos se dibujan en el mismo HTML: el PDF no descarga nada de internet
$opciones = new Options();
$opciones->set('isRemoteEnabled', false);
$opciones->set('defaultFont', 'DejaVu Sans');
$pdf = new Dompdf($opciones);
$pdf->loadHtml($html, 'UTF-8');
$pdf->setPaper('A4', 'landscape');
$pdf->render();
$pdf->stream("pool_{$datos['desde']}_{$datos['hasta']}.pdf", ['Attachment' => true]);
