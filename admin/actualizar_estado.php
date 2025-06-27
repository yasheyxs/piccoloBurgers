<?php
include("bd.php");

header("Content-Type: application/json");

// Usar POST si está, si no usar GET
$pedido_id = $_POST["pedido_id"] ?? $_GET["id"] ?? null;
$nuevo_estado = $_POST["nuevo_estado"] ?? $_GET["estado"] ?? null;

if (!$pedido_id || !$nuevo_estado) {
    echo json_encode(["success" => false, "message" => "Datos incompletos."]);
    exit;
}

$pedido_id = intval($pedido_id);
$estados_validos = ["En preparación", "Listo", "Cancelado"];
if (!in_array($nuevo_estado, $estados_validos)) {
    echo json_encode(["success" => false, "message" => "Estado inválido."]);
    exit;
}

try {
    $stmt = $conexion->prepare("UPDATE tbl_pedidos SET estado = ? WHERE ID = ?");
    $stmt->execute([$nuevo_estado, $pedido_id]);

    echo json_encode(["success" => true, "message" => "Estado actualizado correctamente."]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Error al actualizar: " . $e->getMessage()]);
}
