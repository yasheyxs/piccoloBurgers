<?php
ob_start();


require_once __DIR__ . '/../admin/bd.php';
require_once __DIR__ . '/../componentes/validar_telefono.php';

session_start();

function responder_error($mensaje)
{
    global $conexion;

    if ($conexion instanceof PDO && $conexion->inTransaction()) {
        $conexion->rollBack();
    }

    error_log('guardar_pedido.php: ' . $mensaje);

    if (ob_get_length()) {
        ob_end_clean();
    }
    header('Content-Type: application/json');
    echo json_encode([
        "exito" => false,
        "mensaje" => $mensaje,
        "scroll" => true // Indicador para el frontend de que debe hacer scroll
    ]);
    exit;
}

if (!isset($_POST["nombre"], $_POST["telefono"], $_POST["codigo_pais"], $_POST["carrito"])) {
    responder_error("Faltan datos obligatorios.");
}

$nombre = $_POST["nombre"];
$telefono = trim($_POST["telefono"]);
$codigoPais = trim($_POST["codigo_pais"] ?? '');
$email = null;
if (isset($_POST["email"])) {
    $email = trim((string) $_POST["email"]);
    if ($email === '') {
        $email = null;
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        responder_error("Ingresá un email válido o dejalo en blanco.");
    }
}
$nota = $_POST["nota"] ?? "";
$metodo_pago = $_POST["metodo_pago"] ?? "";
$tipo_entrega = $_POST["tipo_entrega"] ?? "";
$direccion = isset($_POST["direccion"]) ? trim($_POST["direccion"]) : '';
$referencias = isset($_POST["referencias"]) ? trim($_POST["referencias"]) : '';

$telefonoFormateado = validarTelefono($codigoPais, $telefono);
if ($telefonoFormateado === false) {
    responder_error("Ingresá un número de teléfono válido.");
}
$telefono = $telefonoFormateado;

if ($referencias !== '') {
    $referencias = function_exists('mb_substr') ? mb_substr($referencias, 0, 255) : substr($referencias, 0, 255);
} else {
    $referencias = null;
}

if ($tipo_entrega !== "Delivery") {
    $referencias = null;
}

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
$puntos_ganados = 0;
$cliente_id = $_SESSION["cliente"]["id"] ?? null;

try {
    $conexion->beginTransaction();

    // Validar stock antes de registrar el pedido y tocar puntos
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


    // Validar stock antes de registrar el pedido
    if ($usar_puntos && $cliente_id) {
        $valor_por_punto = 20;
        $minimo_puntos_para_canjear = 50;
        $redondear_a_multiplo = 100;

        $stmt = $conexion->prepare("SELECT puntos FROM tbl_clientes WHERE ID = ?");
        $stmt->execute([$cliente_id]);
        $puntos_disponibles = $stmt->fetchColumn();

        if ($puntos_disponibles >= $minimo_puntos_para_canjear) {
            $descuento_max = $total * 0.25;
            $descuento_max_redondeado = floor($descuento_max / $redondear_a_multiplo) * $redondear_a_multiplo;
            $puntos_posibles = floor($descuento_max_redondeado / $valor_por_punto);
            $puntos_usados = min($puntos_disponibles, $puntos_posibles);

            if ($puntos_usados > 0) {
                $descuento = $puntos_usados * $valor_por_punto;
                $total -= $descuento;

                $stmt = $conexion->prepare("UPDATE tbl_clientes SET puntos = puntos - ? WHERE ID = ?");
                $stmt->execute([$puntos_usados, $cliente_id]);
            }
        }
    }

    $estado_inicial = "En preparación";
    $estado_pago_inicial = "No";

    $stmt = $conexion->prepare("INSERT INTO tbl_pedidos (nombre, telefono, email, nota, total, metodo_pago, tipo_entrega, direccion, referencias, estado, esta_pago, cliente_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$nombre, $telefono, $email, $nota, $total, $metodo_pago, $tipo_entrega, $direccion, $referencias, $estado_inicial, $estado_pago_inicial, $cliente_id]);

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
    if ($cliente_id) {
        $puntos_ganados = floor($total / 1500);
        $stmt = $conexion->prepare("UPDATE tbl_clientes SET puntos = puntos + ? WHERE ID = ?");
        $stmt->execute([$puntos_ganados, $cliente_id]);
    }

    $conexion->commit();


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
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }

    if (ob_get_length()) {
        ob_end_clean();
    }
    error_log(sprintf(
        'Error al procesar el pedido en guardar_pedido.php: %s en %s:%d',
        $e->getMessage(),
        $e->getFile(),
        $e->getLine()
    ));
    header('Content-Type: application/json');
    echo json_encode([
        "exito" => false,
        "mensaje" => "Ups... hubo un inconveniente al procesar tu pedido. Por favor, intentá nuevamente más tarde."
    ]);
    exit;
}
