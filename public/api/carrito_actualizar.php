<?php

declare(strict_types=1);

require_once __DIR__ . '/../../admin/bd.php';
require_once __DIR__ . '/../../includes/reservas_virtuales.php';

iniciarSesionSiEsNecesario();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Método no permitido',
    ]);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload)) {
    $payload = [];
}

$accion = $payload['action'] ?? $payload['accion'] ?? '';
$menuId = isset($payload['menuId']) ? (int) $payload['menuId'] : (int) ($payload['producto'] ?? 0);
$sessionId = obtenerIdSesionActual();

try {
    switch ($accion) {
        case 'increment':
        case 'agregar':
            if ($menuId <= 0) {
                throw new InvalidArgumentException('Producto inválido.');
            }
            $resultado = actualizarReservaProducto($conexion, $sessionId, $menuId, 1);
            break;
        case 'decrement':
        case 'restar':
            if ($menuId <= 0) {
                throw new InvalidArgumentException('Producto inválido.');
            }
            $resultado = actualizarReservaProducto($conexion, $sessionId, $menuId, -1);
            break;
        case 'remove':
        case 'eliminar':
            if ($menuId <= 0) {
                throw new InvalidArgumentException('Producto inválido.');
            }
            $resultado = establecerReservaProducto($conexion, $sessionId, $menuId, 0);
            break;
        case 'set':
        case 'establecer':
            if ($menuId <= 0) {
                throw new InvalidArgumentException('Producto inválido.');
            }
            $cantidad = normalizarCantidadSolicitada($payload['cantidad'] ?? $payload['qty'] ?? 0);
            $resultado = establecerReservaProducto($conexion, $sessionId, $menuId, $cantidad);
            break;
        case 'sync':
        case 'sincronizar':
            $items = is_array($payload['items'] ?? null) ? $payload['items'] : [];
            $resultado = sincronizarReservas($conexion, $sessionId, $items);
            break;
        case 'clear':
        case 'limpiar':
            $resultado = limpiarReservasSesion($conexion, $sessionId);
            break;
        default:
            throw new InvalidArgumentException('Acción no soportada.');
    }

    $reservas = $resultado['reservas'] ?? [];
    $ids = array_map(static fn($item) => $item['menu_id'] ?? null, $reservas);
    $ids = array_filter($ids, static fn($id) => $id !== null);
    $disponibilidad = obtenerDisponibilidadMenu($conexion, $ids);

    echo json_encode([
        'exito' => true,
        'reservas' => $reservas,
        'disponibilidad' => $disponibilidad,
    ]);
} catch (Throwable $exception) {
    http_response_code(400);
    echo json_encode([
        'exito' => false,
        'mensaje' => $exception->getMessage(),
    ]);
}