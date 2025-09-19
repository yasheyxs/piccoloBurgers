<?php
include('bd.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

$rol = $_SESSION['rol'] ?? null;
if (!in_array($rol, ['admin', 'delivery'], true)) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'No autorizado.'
    ]);
    exit;
}

try {
    $sentencia = $conexion->prepare("SELECT * FROM tbl_pedidos WHERE estado = 'En camino' ORDER BY fecha DESC");
    $sentencia->execute();
    $pedidos = $sentencia->fetchAll(PDO::FETCH_ASSOC);

    $stmtDetalle = $conexion->prepare("SELECT nombre, cantidad FROM tbl_pedidos_detalle WHERE pedido_id = ?");

    foreach ($pedidos as &$pedido) {
        $stmtDetalle->execute([$pedido['ID']]);
        $pedido['productos'] = $stmtDetalle->fetchAll(PDO::FETCH_ASSOC);
    }
    unset($pedido);

    echo json_encode([
        'success' => true,
        'pedidos' => $pedidos,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener los pedidos: ' . $e->getMessage(),
    ]);
}