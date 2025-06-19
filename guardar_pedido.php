<?php
include("admin/bd.php");

// Validar que los campos requeridos estén presentes
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

// Validar carrito
if (!is_array($carrito) || count($carrito) === 0) {
    echo "<div class='alert alert-warning'>El carrito está vacío.</div>";
    exit;
}

try {
    $total = 0;
    foreach ($carrito as $item) {
        $total += $item["precio"];
    }

    $sentencia = $conexion->prepare("INSERT INTO tbl_pedidos (nombre, telefono, email, nota, total, metodo_pago, tipo_entrega, direccion) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $sentencia->execute([$nombre, $telefono, $email, $nota, $total, $metodo_pago, $tipo_entrega, $direccion]);


    $pedido_id = $conexion->lastInsertId();

    $sentencia_prod = $conexion->prepare("INSERT INTO tbl_pedidos_detalle (pedido_id, producto_id, nombre, precio) VALUES (?, ?, ?, ?)");
    foreach ($carrito as $item) {
        $sentencia_prod->execute([
            $pedido_id,
            $item["id"],
            $item["nombre"],
            $item["precio"]
        ]);
    }

    echo "<div class='alert alert-success'>🎉 Gracias por tu pedido, <strong>$nombre</strong>. Lo estamos preparando. 🍔<br><br>Total: <strong>$" . number_format($total, 2) . "</strong></div>";

    echo "<div class='text-center mt-4'>
            <a href='index.php' class='btn btn-gold'>Volver al inicio</a>
          </div>";

    echo "<script>localStorage.removeItem('carrito');</script>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>Error al procesar el pedido: " . $e->getMessage() . "</div>";
}


?>
