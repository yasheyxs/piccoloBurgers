<?php

declare(strict_types=1);

require_once __DIR__ . '/../../admin/bd.php';
require_once __DIR__ . '/../../includes/reservas_virtuales.php';

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

$menuIds = [];
if (isset($payload['menuIds']) && is_array($payload['menuIds'])) {
    $menuIds = array_map('intval', $payload['menuIds']);
}

try {
    $disponibilidad = obtenerDisponibilidadMenu($conexion, $menuIds);
    echo json_encode([
        'exito' => true,
        'disponibilidad' => $disponibilidad,
    ]);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'exito' => false,
        'mensaje' => $exception->getMessage(),
    ]);
}