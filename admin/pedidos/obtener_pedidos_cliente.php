<?php
include("../bd.php");
session_start();

header("Content-Type: application/json");

if (!isset($_SESSION["cliente"])) {// Verificar si el cliente está autenticado
    // Si no hay sesión de cliente, redirigir al login
    echo json_encode([]);
    exit;
}

$cliente = $_SESSION["cliente"];
$cliente_id = $cliente["id"] ?? null;

if (!$cliente_id) {
    echo json_encode([]);
    exit;
}

// Obtener pedidos usando cliente_id
$stmt = $conexion->prepare("SELECT * FROM tbl_pedidos WHERE cliente_id = ? ORDER BY fecha DESC");
$stmt->execute([$cliente_id]);
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($pedidos as &$pedido) {
    // Obtener detalles
    $stmt_detalle = $conexion->prepare("SELECT nombre, precio, cantidad FROM tbl_pedidos_detalle WHERE pedido_id = ?");
    $stmt_detalle->execute([$pedido["ID"]]);
    $pedido["detalles"] = $stmt_detalle->fetchAll(PDO::FETCH_ASSOC);
}

echo json_encode($pedidos);
exit;
