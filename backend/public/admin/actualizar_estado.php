<?php
/**
 * Cambio de estado o de pago de un pedido desde los paneles de cocina y delivery.
 * Recibe pedido_id, nuevo_estado y/o esta_pago (formulario) y responde {success, message}.
 * Las reglas (quién puede hacer cada cambio, stock y puntos) están en PedidosPanelController.
 */
require_once __DIR__ . '/../../src/Http/Admin/acceso.php'; // permisos, sesión y CSRF del panel
require_once piccolo_path('src/Core/database.php');

use Piccolo\Http\Admin\PedidosPanelController;
use Piccolo\Http\Api\ApiException;
use Piccolo\Repositories\PedidosPanelRepositorio;

header('Content-Type: application/json; charset=utf-8');

$responder = static function (int $status, bool $exito, string $mensaje): never {
    http_response_code($status);
    echo json_encode(['success' => $exito, 'message' => $mensaje], JSON_UNESCAPED_UNICODE);
    exit;
};

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $responder(405, false, 'Método no permitido.');
}

$pedidos = new PedidosPanelController(new PedidosPanelRepositorio($conexion), $conexion);
try {
    $pedidos->actualizar(
        (int) ($_POST['pedido_id'] ?? 0),
        isset($_POST['nuevo_estado']) ? (string) $_POST['nuevo_estado'] : null,
        $_POST['esta_pago'] ?? ($_POST['estado_pago'] ?? null),
        (string) ($_SESSION['rol'] ?? '')
    );
    $responder(200, true, 'Actualización realizada correctamente.');
} catch (ApiException $error) {
    $responder($error->status(), false, $error->errores() ? implode(' ', $error->errores()) : $error->getMessage());
} catch (Throwable $error) {
    error_log('No se pudo actualizar el pedido: ' . $error->getMessage());
    $responder(500, false, 'No se pudo actualizar el pedido. Intentá de nuevo.');
}
