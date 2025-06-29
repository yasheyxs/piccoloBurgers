<?php
ob_start(); // Iniciar el buffer de salida
// Mostrar errores para depuración
ini_set('display_errors', 1);
error_reporting(E_ALL);

include("admin/bd.php");
session_start();

function responder_error($mensaje)
{
    header('Content-Type: application/json');
    echo json_encode(["exito" => false, "mensaje" => $mensaje]);
    exit;
}// Validar que se reciban los datos necesarios

if (!isset($_POST["nombre"], $_POST["telefono"], $_POST["carrito"])) {
    responder_error("Faltan datos obligatorios.");
}// Validar que el nombre y teléfono no estén vacíos

$nombre = $_POST["nombre"];
$telefono = $_POST["telefono"];
$email = $_POST["email"] ?? null;
$nota = $_POST["nota"] ?? "";
$metodo_pago = $_POST["metodo_pago"] ?? "";
$tipo_entrega = $_POST["tipo_entrega"] ?? "";
$direccion = $_POST["direccion"] ?? null;

if (empty($_POST["carrito"])) {// Validar que el carrito no esté vacío
    responder_error("El carrito está vacío.");
}

if (!$metodo_pago || !$tipo_entrega) {// Validar que se haya seleccionado método de pago y tipo de entrega
    responder_error("Por favor, seleccioná método de pago y tipo de entrega.");
}

if ($tipo_entrega === "Delivery" && !$direccion) {// Validar que se haya ingresado una dirección para el envío
    responder_error("Debes ingresar una dirección para el envío.");
}

$carrito = json_decode($_POST["carrito"], true);
if (!is_array($carrito) || count($carrito) === 0) {// Validar que el carrito sea un array y no esté vacío
    responder_error("El carrito está vacío.");
}

$total_original = 0;
foreach ($carrito as $item) {// Validar que cada item tenga los campos necesarios
    $total_original += $item["precio"];
}

$total = $total_original;

// Aplicar puntos
$usar_puntos = isset($_POST["usar_puntos"]) && $_POST["usar_puntos"] == "1";
$puntos_usados = 0;
$descuento = 0;

if ($usar_puntos && isset($_SESSION["cliente"])) {// Validar que el cliente esté autenticado y quiera usar puntos
    $valor_por_punto = 20;
    $minimo_puntos_para_canjear = 50;
    $redondear_a_multiplo = 100;

    $cliente_id = $_SESSION["cliente"]["id"];

    $stmt = $conexion->prepare("SELECT puntos FROM tbl_clientes WHERE ID = ?");
    $stmt->execute([$cliente_id]);
    $puntos_disponibles = $stmt->fetchColumn();

    if ($puntos_disponibles >= $minimo_puntos_para_canjear) {// Validar que el cliente tenga suficientes puntos para canjear
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

try {// Preparar la consulta para insertar el pedido
    $estado_inicial = "En preparación";
    $cliente_id = $_SESSION["cliente"]["id"] ?? null;

    $stmt = $conexion->prepare("INSERT INTO tbl_pedidos (nombre, telefono, email, nota, total, metodo_pago, tipo_entrega, direccion, estado, cliente_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$nombre, $telefono, $email, $nota, $total, $metodo_pago, $tipo_entrega, $direccion, $estado_inicial, $cliente_id]);
    // Obtener el ID del último pedido insertado
    $pedido_id = $conexion->lastInsertId();
    
    $stmt = $conexion->prepare("INSERT INTO tbl_pedidos_detalle (pedido_id, producto_id, nombre, precio, cantidad) VALUES (?, ?, ?, ?, ?)");
    foreach ($carrito as $item) {// Validar que cada item tenga los campos necesarios
        if (!isset($item["id"], $item["nombre"], $item["precio"], $item["cantidad"])) {
            responder_error("Producto inválido: " . json_encode($item));
        }// Validar que los campos del item no estén vacíos
        $stmt->execute([
            $pedido_id,
            $item["id"],
            $item["nombre"],
            $item["precio"],
            $item["cantidad"]
        ]);
    }

    if (isset($_SESSION["cliente"])) {// Si el cliente está autenticado, sumar puntos
        $puntos_ganados = floor($total / 1500);
        $stmt = $conexion->prepare("UPDATE tbl_clientes SET puntos = puntos + ? WHERE ID = ?");
        $stmt->execute([$puntos_ganados, $_SESSION["cliente"]["id"]]);
    } else {// Si el cliente no está autenticado, no se suman puntos
        $puntos_ganados = 0;
    }

    header('Content-Type: application/json'); // Enviar encabezado JSON
    ob_end_clean();
    echo json_encode([// Respuesta exitosa
        "exito" => true,
        "nombre" => $nombre,
        "total_original" => number_format($total_original, 2),
        "descuento" => number_format($descuento, 2),
        "total" => number_format($total, 2),
        "puntos_ganados" => $puntos_ganados,
    ]);
    exit;
} catch (Exception $e) {// Capturar cualquier error al insertar el pedido
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode([
        "exito" => false,
        "mensaje" => "Error al procesar el pedido: " . $e->getMessage()
    ]);

    exit;
}
