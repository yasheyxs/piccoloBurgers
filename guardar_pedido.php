<?php
include("admin/bd.php");
session_start();

// Validación de campos
if (!isset($_POST["nombre"], $_POST["telefono"], $_POST["carrito"])) {
    echo "<div class='alert alert-danger'>Faltan datos obligatorios.</div>";
    exit;
}

$nombre = $_POST["nombre"];
$telefono = $_POST["telefono"];
$email = $_POST["email"] ?? null;
$nota = $_POST["nota"] ?? "";
$metodo_pago = $_POST["metodo_pago"] ?? "";
$tipo_entrega = $_POST["tipo_entrega"] ?? "";
$direccion = $_POST["direccion"] ?? null;

if (empty($_POST["carrito"])) {
    echo "<div class='alert alert-warning'>El carrito está vacío.</div>";
    exit;
}

if (!$metodo_pago || !$tipo_entrega) {
    echo "<div class='alert alert-warning'>Por favor, seleccioná método de pago y tipo de entrega.</div>";
    exit;
}

if ($tipo_entrega === "Delivery" && !$direccion) {
    echo "<div class='alert alert-warning'>Debes ingresar una dirección para el envío.</div>";
    exit;
}

$carrito = json_decode($_POST["carrito"], true);
if (!is_array($carrito) || count($carrito) === 0) {
    echo "<div class='alert alert-warning'>El carrito está vacío.</div>";
    exit;
}

// Calcular total
$total_original = 0;
foreach ($carrito as $item) {
    $total_original += $item["precio"];
}

$total = $total_original;

// Aplicar puntos si corresponde
$usar_puntos = isset($_POST["usar_puntos"]) && $_POST["usar_puntos"] == "1";
$puntos_usados = 0;
$descuento = 0;

if ($usar_puntos && isset($_SESSION["cliente"])) {
    $valor_por_punto = 20;
    $minimo_puntos_para_canjear = 50;
    $redondear_a_multiplo = 100;

    $cliente_id = $_SESSION["cliente"]["id"];

    // Obtener puntos actuales
    $stmt = $conexion->prepare("SELECT puntos FROM tbl_clientes WHERE ID = ?");
    $stmt->execute([$cliente_id]);
    $puntos_disponibles = $stmt->fetchColumn();

    if ($puntos_disponibles >= $minimo_puntos_para_canjear) {
        $descuento_max = $total * 0.25;

        // Redondear descuento máximo hacia abajo al múltiplo más cercano
        $descuento_max_redondeado = floor($descuento_max / $redondear_a_multiplo) * $redondear_a_multiplo;

        $puntos_posibles = floor($descuento_max_redondeado / $valor_por_punto);
        $puntos_usados = min($puntos_disponibles, $puntos_posibles);
        $descuento = $puntos_usados * $valor_por_punto;

        $total -= $descuento;

        // Restar puntos
        $stmt = $conexion->prepare("UPDATE tbl_clientes SET puntos = puntos - ? WHERE ID = ?");
        $stmt->execute([$puntos_usados, $cliente_id]);
    }
}


try {
    // Insertar pedido
    $estado_inicial = "En preparación";
    $stmt = $conexion->prepare("INSERT INTO tbl_pedidos (nombre, telefono, email, nota, total, metodo_pago, tipo_entrega, direccion, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$nombre, $telefono, $email, $nota, $total, $metodo_pago, $tipo_entrega, $direccion, $estado_inicial]);
    $pedido_id = $conexion->lastInsertId();

    // Insertar detalle
    $stmt = $conexion->prepare("INSERT INTO tbl_pedidos_detalle (pedido_id, producto_id, nombre, precio) VALUES (?, ?, ?, ?)");
    foreach ($carrito as $item) {
        $stmt->execute([
            $pedido_id,
            $item["id"],
            $item["nombre"],
            $item["precio"]
        ]);
    }

    // Sumar nuevos puntos
    if (isset($_SESSION["cliente"])) {
        $puntos_ganados = floor($total / 1500);
        $stmt = $conexion->prepare("UPDATE tbl_clientes SET puntos = puntos + ? WHERE ID = ?");
        $stmt->execute([$puntos_ganados, $_SESSION["cliente"]["id"]]);
    }

    // Mensaje final
    echo "<div class='alert alert-success'>🎉 Gracias por tu pedido, <strong>" . htmlspecialchars($nombre) . "</strong>. Lo estamos preparando. 🍔<br><br>";

    if ($descuento > 0) {
        echo "Total original: $" . number_format($total_original, 2) . "<br>";
        echo "Descuento por puntos: -$" . number_format($descuento, 2) . "<br>";
    }

    echo "Total a pagar: <strong>$" . number_format($total, 2) . "</strong></div>";
    if (isset($_SESSION["cliente"])) {
        echo "<br>🎁 Puntos ganados: <strong>" . $puntos_ganados . "</strong>";
    }


    echo "<div class='text-center mt-4'><a href='index.php' class='btn btn-gold'>Volver al inicio</a></div>";
    echo "<script>localStorage.removeItem('carrito');</script>";

} catch (Exception $e) {
    echo "<div class='alert alert-danger'>Error al procesar el pedido: " . $e->getMessage() . "</div>";
}
?>
