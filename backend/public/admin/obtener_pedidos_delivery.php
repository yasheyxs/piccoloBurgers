<?php
/** Pedidos en camino para el panel de delivery (assets/js/pages/delivery.js). Responde {success, pedidos}. */
require_once __DIR__ . '/../../src/Http/Admin/acceso.php'; // permisos, sesión y CSRF del panel
require_once piccolo_path('src/Core/database.php');

use Piccolo\Http\Admin\PedidosPanelController;
use Piccolo\Repositories\PedidosPanelRepositorio;

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

try {
    $pedidos = (new PedidosPanelController(new PedidosPanelRepositorio($conexion), $conexion))->delivery();
    echo json_encode(['success' => true, 'pedidos' => $pedidos], JSON_UNESCAPED_UNICODE);
} catch (Throwable $error) {
    error_log('No se pudieron obtener los pedidos de delivery: ' . $error->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudieron obtener los pedidos.'], JSON_UNESCAPED_UNICODE);
}
