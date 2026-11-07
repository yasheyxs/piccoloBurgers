<?php
ob_start();

require_once piccolo_path('src/Core/database.php');
require_once piccolo_path('src/Services/reservas_virtuales.php');
require_once piccolo_path('src/Services/pedidos.php');

session_start();

function responder_pedido(array $cuerpo): void
{
    if (ob_get_length()) {
        ob_end_clean();
    }
    header('Content-Type: application/json');
    echo json_encode($cuerpo);
    exit;
}

function responder_error($mensaje)
{
    error_log('guardar_pedido.php: ' . $mensaje);
    responder_pedido([
        "exito" => false,
        "mensaje" => $mensaje,
        "scroll" => true // Indicador para el frontend de que debe hacer scroll
    ]);
}

if (!isset($_POST["nombre"], $_POST["telefono"], $_POST["codigo_pais"], $_POST["carrito"])) {
    responder_error("Faltan datos obligatorios.");
}

$carrito = json_decode((string) $_POST["carrito"], true);
if (!is_array($carrito) || count($carrito) === 0) {
    responder_error("El carrito está vacío.");
}

try {
    // Del carrito solo se usan el producto y la cantidad: el precio sale de la base de datos.
    $pedido = crearPedido($conexion, [
        'nombre' => $_POST["nombre"],
        'telefono' => $_POST["telefono"],
        'codigo_pais' => $_POST["codigo_pais"],
        'email' => $_POST["email"] ?? null,
        'nota' => $_POST["nota"] ?? '',
        'metodo_pago' => $_POST["metodo_pago"] ?? '',
        'tipo_entrega' => $_POST["tipo_entrega"] ?? '',
        'direccion' => $_POST["direccion"] ?? '',
        'referencias' => $_POST["referencias"] ?? '',
        'usar_puntos' => ($_POST["usar_puntos"] ?? '') == "1",
        'items' => array_map(static fn($item) => [
            'id' => is_array($item) ? ($item['id'] ?? 0) : 0,
            'cantidad' => is_array($item) ? ($item['cantidad'] ?? 0) : 0,
        ], $carrito),
    ], isset($_SESSION["cliente"]["id"]) ? (int) $_SESSION["cliente"]["id"] : null);
} catch (PedidoException $e) {
    responder_error($e->getMessage());
} catch (Throwable $e) {
    error_log(sprintf(
        'Error al procesar el pedido en guardar_pedido.php: %s en %s:%d',
        $e->getMessage(),
        $e->getFile(),
        $e->getLine()
    ));
    responder_pedido([
        "exito" => false,
        "mensaje" => "Ups... hubo un inconveniente al procesar tu pedido. Por favor, intentá nuevamente más tarde."
    ]);
}

try {
    limpiarReservasSesion($conexion, obtenerIdSesionActual());
} catch (Throwable $cleanupError) {
    error_log('No se pudieron limpiar las reservas virtuales tras confirmar el pedido: ' . $cleanupError->getMessage());
}

responder_pedido([
    "exito" => true,
    "nombre" => $pedido['nombre'],
    "total_original" => number_format($pedido['total_original'], 2),
    "descuento" => number_format($pedido['descuento'], 2),
    "total" => number_format($pedido['total'], 2),
    "puntos_ganados" => $pedido['puntos_ganados'],
    "puntos_usados" => $pedido['puntos_usados'],
]);
