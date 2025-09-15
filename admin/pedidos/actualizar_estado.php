<?php
include("../bd.php");

header("Content-Type: application/json");

// Usar POST si está, si no usar GET
$pedido_id = $_POST["pedido_id"] ?? $_GET["id"] ?? null;
$nuevo_estado = $_POST["nuevo_estado"] ?? $_GET["estado"] ?? null;

if (!$pedido_id || !$nuevo_estado) {// Validar que se reciban los datos necesarios
    echo json_encode(["success" => false, "message" => "Datos incompletos."]);
    exit;
}

$pedido_id = intval($pedido_id);
$estados_validos = ["En preparación", "Listo", "En camino", "Entregado", "Cancelado"];
if (!in_array($nuevo_estado, $estados_validos)) {// Validar que el estado sea válido
    echo json_encode(["success" => false, "message" => "Estado inválido."]);
    exit;
}

try {// Preparar la consulta para actualizar el estado del pedido
    $stmt = $conexion->prepare("UPDATE tbl_pedidos SET estado = ? WHERE ID = ?");
    $stmt->execute([$nuevo_estado, $pedido_id]);

    echo json_encode(["success" => true, "message" => "Estado actualizado correctamente."]);
} catch (Exception $e) {// Capturar cualquier error al actualizar el estado
    echo json_encode(["success" => false, "message" => "Error al actualizar: " . $e->getMessage()]);
}

if ($nuevo_estado === "Listo") {
    $stmt = $conexion->prepare("
        SELECT pd.producto_id, pd.cantidad, mp.materia_prima_id, mp.cantidad AS requerido
        FROM tbl_pedidos_detalle pd
        JOIN tbl_menu_materias_primas mp ON mp.menu_id = pd.producto_id
        WHERE pd.pedido_id = ?
    ");
    $stmt->execute([$pedido_id]);
    $insumos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($insumos as $insumo) {
        $consumo = $insumo['requerido'] * $insumo['cantidad'];
        $stmtUpdate = $conexion->prepare("
            UPDATE tbl_materias_primas
            SET cantidad = GREATEST(0, cantidad - ?)
            WHERE ID = ?
        ");
        $stmtUpdate->execute([$consumo, $insumo['materia_prima_id']]);
    }
}
