<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Método no permitido."], JSON_UNESCAPED_UNICODE);
    exit;
}

if (empty($_SESSION['admin_usuario'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Sesión no válida."], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . '/bd.php';

require_once __DIR__ . '/../includes/estado_pago_helpers.php';

try {
    $sentencia = $conexion->prepare('SELECT rol FROM tbl_usuarios WHERE usuario = :usuario LIMIT 1');
    $sentencia->execute([':usuario' => $_SESSION['admin_usuario']]);
    $usuario = $sentencia->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "No se pudo validar la sesión."], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!$usuario) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Usuario no autorizado."], JSON_UNESCAPED_UNICODE);
    exit;
}

$rol = $usuario['rol'] ?? '';
$rolesPermitidos = ['admin', 'empleado', 'delivery'];
if (!in_array($rol, $rolesPermitidos, true)) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "No tenés permisos para realizar esta acción."], JSON_UNESCAPED_UNICODE);
    exit;
}

$tokenSesion = $_SESSION['csrf_token'] ?? '';
$tokenRecibido = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
if (!$tokenSesion || !$tokenRecibido || !hash_equals($tokenSesion, $tokenRecibido)) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Token CSRF inválido."], JSON_UNESCAPED_UNICODE);
    exit;
}

$pedido_id = $_POST["pedido_id"] ?? null;
$nuevo_estado = $_POST["nuevo_estado"] ?? null;
$estado_pago_recibido = $_POST["esta_pago"] ?? $_POST["estado_pago"] ?? null;

if (!$pedido_id) {// Validar que se reciba el ID del pedido

    echo json_encode(["success" => false, "message" => "Datos incompletos."]);
    exit;
}

$pedido_id = intval($pedido_id);

try {
    $stmtPedido = $conexion->prepare('SELECT estado, tipo_entrega FROM tbl_pedidos WHERE ID = ? LIMIT 1');
    $stmtPedido->execute([$pedido_id]);
    $pedidoActual = $stmtPedido->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "No se pudo obtener la información del pedido."], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!$pedidoActual) {
    echo json_encode(["success" => false, "message" => "El pedido indicado no existe."], JSON_UNESCAPED_UNICODE);
    exit;
}

$estadoActual = $pedidoActual['estado'] ?? '';
$tipoEntregaActual = $pedidoActual['tipo_entrega'] ?? '';

$estados_validos = ["En preparación", "Listo", "En camino", "Entregado", "Cancelado"];
$actualizaciones = [];
$valores = [];

if ($nuevo_estado !== null) {
    $nuevo_estado = trim((string)$nuevo_estado);
    $estados_validos = ["En preparación", "Listo", "En camino", "Entregado", "Cancelado"];
    if (!in_array($nuevo_estado, $estados_validos, true)) {
        echo json_encode(["success" => false, "message" => "Estado inválido."]);
        exit;
    }

    if ($nuevo_estado === 'Listo' && $estadoActual === 'En camino' && $tipoEntregaActual === 'Delivery' && $rol !== 'delivery') {
        echo json_encode(["success" => false, "message" => "Solo el personal de delivery puede marcar como listo un pedido en camino."], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $actualizaciones[] = "estado = ?";
    $valores[] = $nuevo_estado;
}

$estado_pago = null;
if ($estado_pago_recibido !== null) {
    $estado_pago = piccolo_resolver_valor_pago($conexion, (string)$estado_pago_recibido);
    if ($estado_pago === null) {
        echo json_encode(["success" => false, "message" => "Valor de pago inválido."]);
        exit;
    }
}

$estado_pago_por_estado = null;
if ($nuevo_estado !== null) {
    if (in_array($nuevo_estado, ['Listo', 'Entregado'], true)) {
        $estado_pago_por_estado = piccolo_resolver_valor_pago($conexion, 'Si');
    } elseif ($nuevo_estado === 'Cancelado') {
        $estado_pago_por_estado = piccolo_resolver_valor_pago($conexion, 'No');
    }
}

$estado_pago_final = $estado_pago_por_estado ?? $estado_pago;
if ($estado_pago_final !== null) {
    $actualizaciones[] = "esta_pago = ?";
    $valores[] = $estado_pago_final;
}

if (count($actualizaciones) === 0) {
    echo json_encode(["success" => false, "message" => "Datos incompletos."]);
    exit;
}

try {
    $sql = "UPDATE tbl_pedidos SET " . implode(', ', $actualizaciones) . " WHERE ID = ?";
    $valores[] = $pedido_id;

    $stmt = $conexion->prepare($sql);
    $stmt->execute($valores);

    echo json_encode(["success" => true, "message" => "Actualización realizada correctamente."]);
} catch (Exception $e) {// Capturar cualquier error al actualizar el estado
    echo json_encode(["success" => false, "message" => "Error al actualizar: " . $e->getMessage()]);
    exit;
}

if (isset($nuevo_estado) && $nuevo_estado === "Listo") {
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
