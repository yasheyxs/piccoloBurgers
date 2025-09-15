<?php
ob_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../admin/bd.php';
session_start();

function responder_error($mensaje) {
    header('Content-Type: application/json');
    echo json_encode([
        "exito" => false,
        "mensaje" => $mensaje,
        "scroll" => true // Indicador para el frontend de que debe hacer scroll
    ]);
    exit;
}

if (!isset($_POST["nombre"], $_POST["telefono"], $_POST["carrito"])) {
    responder_error("Faltan datos obligatorios.");
}

$nombre = $_POST["nombre"];
$telefono = $_POST["telefono"];
$email = $_POST["email"] ?? null;
$nota = $_POST["nota"] ?? "";
$metodo_pago = $_POST["metodo_pago"] ?? "";
$tipo_entrega = $_POST["tipo_entrega"] ?? "";
$direccion = $_POST["direccion"] ?? null;

if (empty($_POST["carrito"])) {
    responder_error("El carrito está vacío.");
}

if (!$metodo_pago || !$tipo_entrega) {
    responder_error("Por favor, seleccioná método de pago y tipo de entrega.");
}

if ($tipo_entrega === "Delivery" && !$direccion) {
    responder_error("Debes ingresar una dirección para el envío.");
}

$carrito = json_decode($_POST["carrito"], true);
if (!is_array($carrito) || count($carrito) === 0) {
    responder_error("El carrito está vacío.");
}

$total_original = 0;
foreach ($carrito as $item) {
    $total_original += $item["precio"];
}

$total = $total_original;

// Aplicar puntos
$usar_puntos = isset($_POST["usar_puntos"]) && $_POST["usar_puntos"] == "1";
$puntos_usados = 0;
$descuento = 0;

if ($usar_puntos && isset($_SESSION["cliente"])) {
    $valor_por_punto = 20;
    $minimo_puntos_para_canjear = 50;
    $redondear_a_multiplo = 100;

    $cliente_id = $_SESSION["cliente"]["id"];
    $stmt = $conexion->prepare("SELECT puntos FROM tbl_clientes WHERE ID = ?");
    $stmt->execute([$cliente_id]);
    $puntos_disponibles = $stmt->fetchColumn();

    if ($puntos_disponibles >= $minimo_puntos_para_canjear) {
        $descuento_max = $total * 0.25;
        $descuento_max_redondeado = floor($descuento_max / $redondear_a_multiplo) * $redondear_a_multiplo;
        $puntos_posibles = floor($descuento_max_redondeado / $valor_por_punto);
        $puntos_usados = min($puntos_disponibles, $puntos_posibles);
        $descuento = $puntos_usados * $valor_por_punto;
        $total -= $descuento;

        $stmt = $conexion->prepare("UPDATE tbl_clientes SET puntos = puntos - ? WHERE ID = ?");
        $stmt->execute([$puntos_usados, $cliente_id]);
    }
}

// Validar stock antes de registrar el pedido
foreach ($carrito as $item) {
    $producto_id = $item["id"];
    $cantidadVendida = $item["cantidad"];

    $stmt = $conexion->prepare("
        SELECT mp.ID, mp.nombre, mp.cantidad AS stock_actual, mp_req.cantidad AS requerido
        FROM tbl_menu_materias_primas mp_req
        JOIN tbl_materias_primas mp ON mp_req.materia_prima_id = mp.ID
        WHERE mp_req.menu_id = ?
    ");
    $stmt->execute([$producto_id]);
    $insumos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($insumos as $insumo) {
        $consumo = $insumo['requerido'] * $cantidadVendida;
        if ($insumo['stock_actual'] < $consumo) {
            responder_error("Ups... hubo un inconveniente al procesar tu pedido. Por favor, revisá tu carrito o intentá nuevamente en unos minutos.");
        }
    }
}

try {
    $estado_inicial = "En preparación";
    $cliente_id = $_SESSION["cliente"]["id"] ?? null;

    $stmt = $conexion->prepare("INSERT INTO tbl_pedidos (nombre, telefono, email, nota, total, metodo_pago, tipo_entrega, direccion, estado, cliente_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$nombre, $telefono, $email, $nota, $total, $metodo_pago, $tipo_entrega, $direccion, $estado_inicial, $cliente_id]);
    $pedido_id = $conexion->lastInsertId();

    $stmt = $conexion->prepare("INSERT INTO tbl_pedidos_detalle (pedido_id, producto_id, nombre, precio, cantidad) VALUES (?, ?, ?, ?, ?)");
    foreach ($carrito as $item) {
        if (!isset($item["id"], $item["nombre"], $item["precio"], $item["cantidad"])) {
            responder_error("Producto inválido: " . json_encode($item));
        }
        $stmt->execute([
            $pedido_id,
            $item["id"],
            $item["nombre"],
            $item["precio"],
            $item["cantidad"]
        ]);
    }

    // Sumar puntos
    $puntos_ganados = 0;
    if (isset($_SESSION["cliente"])) {
        $puntos_ganados = floor($total / 1500);
        $stmt = $conexion->prepare("UPDATE tbl_clientes SET puntos = puntos + ? WHERE ID = ?");
        $stmt->execute([$puntos_ganados, $cliente_id]);
    }

    header('Content-Type: application/json');
    ob_end_clean();
    echo json_encode([
        "exito" => true,
        "nombre" => $nombre,
        "total_original" => number_format($total_original, 2),
        "descuento" => number_format($descuento, 2),
        "total" => number_format($total, 2),
        "puntos_ganados" => $puntos_ganados
    ]);
    exit;
} catch (Exception $e) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode([
        "exito" => false,
        "mensaje" => "Error al procesar el pedido: " . $e->getMessage()
    ]);
    exit;
}
