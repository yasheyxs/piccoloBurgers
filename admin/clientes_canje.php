<?php

declare(strict_types=1);

require_once __DIR__ . '/bd.php';
require_once __DIR__ . '/../includes/puntos_config.php';
require_once __DIR__ . '/../includes/reservas_virtuales.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Envía una respuesta JSON y detiene la ejecución.
 */
function responderJson(array $payload, int $status = 200): void
{
    if (!headers_sent()) {
        http_response_code($status);
        header('Content-Type: application/json');
    }

    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Calcula la ruta pública de una imagen del menú.
 */
function rutaImagenMenu(?string $archivo): ?string
{
    if ($archivo === null || trim($archivo) === '') {
        return null;
    }

    $archivo = ltrim($archivo, '/');
    return '../public/img/menu/' . $archivo;
}

/**
 * Normaliza una nota opcional proveniente del formulario.
 */
function normalizarNota(?string $nota): ?string
{
    if ($nota === null) {
        return null;
    }

    $nota = trim($nota);
    if ($nota === '') {
        return null;
    }

    if (function_exists('mb_substr')) {
        return mb_substr($nota, 0, 500);
    }

    return substr($nota, 0, 500);
}

/**
 * Procesa el registro de una venta con descuento por puntos.
 *
 * @throws InvalidArgumentException
 */
function registrarVentaConDescuento(PDO $conexion, array $payload): array
{
    $clienteId = isset($payload['clienteId']) ? (int) $payload['clienteId'] : 0;
    if ($clienteId <= 0) {
        throw new InvalidArgumentException('El cliente seleccionado no es válido.');
    }

    $itemsRecibidos = $payload['items'] ?? [];
    if (!is_array($itemsRecibidos) || empty($itemsRecibidos)) {
        throw new InvalidArgumentException('Debés seleccionar al menos un producto para registrar la venta.');
    }

    $itemsNormalizados = [];
    foreach ($itemsRecibidos as $item) {
        if (!is_array($item)) {
            continue;
        }

        $menuId = isset($item['id']) ? (int) $item['id'] : (int) ($item['menu_id'] ?? 0);
        $cantidad = isset($item['cantidad']) ? (int) $item['cantidad'] : (int) ($item['qty'] ?? 0);

        if ($menuId <= 0 || $cantidad <= 0) {
            continue;
        }

        if (isset($itemsNormalizados[$menuId])) {
            $itemsNormalizados[$menuId] += $cantidad;
        } else {
            $itemsNormalizados[$menuId] = $cantidad;
        }
    }

    if (empty($itemsNormalizados)) {
        throw new InvalidArgumentException('Debés seleccionar al menos un producto para registrar la venta.');
    }

    $metodoPago = isset($payload['metodo_pago']) ? trim((string) $payload['metodo_pago']) : '';
    $tipoEntrega = isset($payload['tipo_entrega']) ? trim((string) $payload['tipo_entrega']) : '';

    if ($metodoPago === '' || $tipoEntrega === '') {
        throw new InvalidArgumentException('Seleccioná el método de pago y el tipo de entrega para la venta.');
    }

    $nota = normalizarNota($payload['nota'] ?? null);

    $direccion = null;
    $referencias = null;
    if ($tipoEntrega === 'Delivery') {
        $direccion = isset($payload['direccion']) ? trim((string) $payload['direccion']) : '';
        if ($direccion === '') {
            throw new InvalidArgumentException('Ingresá una dirección para la entrega.');
        }

        if (function_exists('mb_substr')) {
            $direccion = mb_substr($direccion, 0, 255);
        } else {
            $direccion = substr($direccion, 0, 255);
        }

        $referenciasTexto = isset($payload['referencias']) ? trim((string) $payload['referencias']) : '';
        if ($referenciasTexto !== '') {
            if (function_exists('mb_substr')) {
                $referencias = mb_substr($referenciasTexto, 0, 255);
            } else {
                $referencias = substr($referenciasTexto, 0, 255);
            }
        }
    }

    $configuracionPuntos = obtenerConfiguracionPuntos($conexion);
    $valorPorPunto = max(0.01, (float) ($configuracionPuntos['valor_punto'] ?? 0));
    $minimoPuntosCanje = max(0, (int) ($configuracionPuntos['minimo_puntos'] ?? 0));
    $maximoPorcentajeCanje = (float) ($configuracionPuntos['maximo_porcentaje'] ?? 0.25);
    if ($maximoPorcentajeCanje < 0) {
        $maximoPorcentajeCanje = 0;
    } elseif ($maximoPorcentajeCanje > 1) {
        $maximoPorcentajeCanje = 1;
    }

    try {
        $conexion->beginTransaction();

        $stmtCliente = $conexion->prepare('SELECT nombre, telefono, email, puntos FROM tbl_clientes WHERE ID = :id FOR UPDATE');
        $stmtCliente->execute([':id' => $clienteId]);
        $cliente = $stmtCliente->fetch(PDO::FETCH_ASSOC);

        if (!$cliente) {
            throw new InvalidArgumentException('No encontramos al cliente seleccionado.');
        }

        $puntosDisponibles = max(0, (int) ($cliente['puntos'] ?? 0));
        $nombreCliente = trim((string) ($cliente['nombre'] ?? ''));
        $telefonoCliente = trim((string) ($cliente['telefono'] ?? ''));
        $emailCliente = $cliente['email'] ?? null;
        if ($emailCliente !== null) {
            $emailCliente = trim((string) $emailCliente);
            if ($emailCliente === '') {
                $emailCliente = null;
            }
        }

        if ($nombreCliente === '') {
            $nombreCliente = 'Cliente sin nombre';
        }

        if ($telefonoCliente === '') {
            $telefonoCliente = 'N/A';
        }

        $idsMenus = array_keys($itemsNormalizados);
        $placeholders = implode(',', array_fill(0, count($idsMenus), '?'));
        $stmtMenu = $conexion->prepare("SELECT ID, nombre, precio FROM tbl_menu WHERE ID IN ($placeholders)");
        $stmtMenu->execute($idsMenus);
        $menuDisponible = $stmtMenu->fetchAll(PDO::FETCH_ASSOC);

        if (count($menuDisponible) !== count($idsMenus)) {
            throw new InvalidArgumentException('Uno de los productos seleccionados ya no está disponible.');
        }

        $detalleVenta = [];
        $totalOriginal = 0.0;

        foreach ($menuDisponible as $producto) {
            $menuId = (int) ($producto['ID'] ?? 0);
            $cantidad = $itemsNormalizados[$menuId] ?? 0;
            $precioUnitario = (float) ($producto['precio'] ?? 0);
            if ($precioUnitario < 0) {
                $precioUnitario = 0;
            }

            $subtotal = $precioUnitario * $cantidad;
            $totalOriginal += $subtotal;

            $detalleVenta[] = [
                'id' => $menuId,
                'nombre' => (string) ($producto['nombre'] ?? ''),
                'precio' => $precioUnitario,
                'cantidad' => $cantidad,
            ];
        }

        $maximoDescuento = $maximoPorcentajeCanje > 0 ? $totalOriginal * $maximoPorcentajeCanje : 0.0;
        if ($maximoDescuento > $totalOriginal) {
            $maximoDescuento = $totalOriginal;
        }

        $maximoPuntosPermitidos = $valorPorPunto > 0 ? (int) floor($maximoDescuento / $valorPorPunto) : 0;
        if ($maximoPuntosPermitidos < 0) {
            $maximoPuntosPermitidos = 0;
        }

        $puntosSolicitados = isset($payload['puntos_utilizados']) ? (int) $payload['puntos_utilizados'] : 0;
        if ($puntosSolicitados < 0) {
            throw new InvalidArgumentException('Ingresá una cantidad de puntos válida.');
        }

        if ($puntosSolicitados > $puntosDisponibles) {
            throw new InvalidArgumentException('No hay suficientes puntos disponibles para el canje solicitado.');
        }

        if ($puntosSolicitados > $maximoPuntosPermitidos) {
            throw new InvalidArgumentException('Superaste el máximo de puntos permitidos para esta venta.');
        }

        if ($puntosSolicitados > 0 && $puntosSolicitados < $minimoPuntosCanje) {
            throw new InvalidArgumentException(sprintf('El mínimo de puntos para canjear es de %d.', $minimoPuntosCanje));
        }

        $puedeCanjear = $totalOriginal > 0 && $maximoPuntosPermitidos > 0 && $puntosDisponibles >= $minimoPuntosCanje;
        if ($puntosSolicitados > 0 && !$puedeCanjear) {
            throw new InvalidArgumentException('No se pudo aplicar el canje de puntos con los parámetros actuales.');
        }

        $puntosPorAplicar = $puedeCanjear ? $puntosSolicitados : 0;

        $descuento = $puntosPorAplicar * $valorPorPunto;
        if ($descuento > $maximoDescuento) {
            $descuento = $maximoDescuento;
        }

        if ($descuento > $totalOriginal) {
            $descuento = $totalOriginal;
        }

        $totalFinal = max(0.0, $totalOriginal - $descuento);
        $puntosDespuesCanje = $puntosDisponibles - $puntosPorAplicar;

        if ($puntosDespuesCanje < 0) {
            throw new InvalidArgumentException('Los puntos disponibles no son suficientes para completar el canje.');
        }

        $itemsParaReservar = array_map(static function (array $linea): array {
            return [
                'id' => $linea['id'],
                'cantidad' => $linea['cantidad'],
            ];
        }, $detalleVenta);

        $sessionId = obtenerIdSesionActual();
        try {
            sincronizarReservas($conexion, $sessionId, $itemsParaReservar);
        } catch (RuntimeException $error) {
            throw new InvalidArgumentException($error->getMessage());
        }

        $notaFinal = $nota;
        if ($notaFinal === null) {
            $notaFinal = $puntosPorAplicar > 0
                ? 'Venta registrada desde el panel administrativo con canje de puntos.'
                : 'Venta registrada desde el panel administrativo.';
        }

        $stmtPedido = $conexion->prepare('INSERT INTO tbl_pedidos (nombre, telefono, email, nota, total, metodo_pago, tipo_entrega, direccion, referencias, estado, esta_pago, cliente_id) VALUES (:nombre, :telefono, :email, :nota, :total, :metodo_pago, :tipo_entrega, :direccion, :referencias, :estado, :esta_pago, :cliente_id)');
        $stmtPedido->execute([
            ':nombre' => $nombreCliente,
            ':telefono' => $telefonoCliente,
            ':email' => $emailCliente,
            ':nota' => $notaFinal,
            ':total' => $totalFinal,
            ':metodo_pago' => $metodoPago,
            ':tipo_entrega' => $tipoEntrega,
            ':direccion' => $direccion,
            ':referencias' => $referencias,
            ':estado' => 'En preparación',
            ':esta_pago' => 'No',
            ':cliente_id' => $clienteId,
        ]);

        $pedidoId = (int) $conexion->lastInsertId();

        $stmtDetalle = $conexion->prepare('INSERT INTO tbl_pedidos_detalle (pedido_id, producto_id, nombre, precio, cantidad) VALUES (:pedido_id, :producto_id, :nombre, :precio, :cantidad)');
        foreach ($detalleVenta as $linea) {
            $stmtDetalle->execute([
                ':pedido_id' => $pedidoId,
                ':producto_id' => $linea['id'],
                ':nombre' => $linea['nombre'],
                ':precio' => $linea['precio'],
                ':cantidad' => $linea['cantidad'],
            ]);
        }

        $puntosGanados = (int) floor($totalFinal / 1500);
        $puntosFinales = $puntosDespuesCanje + $puntosGanados;

        $stmtActualizar = $conexion->prepare('UPDATE tbl_clientes SET puntos = :puntos WHERE ID = :id');
        $stmtActualizar->execute([
            ':puntos' => $puntosFinales,
            ':id' => $clienteId,
        ]);

        if ($puntosPorAplicar > 0) {
            $stmtMovimiento = $conexion->prepare('INSERT INTO movimientos_puntos (cliente_id, tipo, descripcion, puntos, saldo_resultante) VALUES (:cliente_id, :tipo, :descripcion, :puntos, :saldo)');
            $stmtMovimiento->execute([
                ':cliente_id' => $clienteId,
                ':tipo' => 'canje',
                ':descripcion' => sprintf('Canje de %d puntos para descuento en pedido #%d', $puntosPorAplicar, $pedidoId),
                ':puntos' => -$puntosPorAplicar,
                ':saldo' => $puntosDespuesCanje,
            ]);
        }

        if ($puntosGanados > 0) {
            $stmtMovimiento = $conexion->prepare('INSERT INTO movimientos_puntos (cliente_id, tipo, descripcion, puntos, saldo_resultante) VALUES (:cliente_id, :tipo, :descripcion, :puntos, :saldo)');
            $stmtMovimiento->execute([
                ':cliente_id' => $clienteId,
                ':tipo' => 'compra',
                ':descripcion' => sprintf('Compra registrada en el panel administrativo (pedido #%d)', $pedidoId),
                ':puntos' => $puntosGanados,
                ':saldo' => $puntosFinales,
            ]);
        }

        $conexion->commit();

        try {
            limpiarReservasSesion($conexion, $sessionId);
        } catch (Throwable $cleanupError) {
            error_log('No se pudieron limpiar las reservas virtuales tras registrar la venta: ' . $cleanupError->getMessage());
        }

        return [
            'mensaje' => 'La venta con descuento se registró correctamente.',
            'pedido_id' => $pedidoId,
            'puntos_usados' => $puntosPorAplicar,
            'puntos_ganados' => $puntosGanados,
            'total_original' => $totalOriginal,
            'descuento' => $descuento,
            'total_final' => $totalFinal,
            'puntos_actuales' => $puntosFinales,
            'items' => $detalleVenta,
            'metodo_pago' => $metodoPago,
            'tipo_entrega' => $tipoEntrega,
        ];
    } catch (Throwable $error) {
        if ($conexion->inTransaction()) {
            $conexion->rollBack();
        }

        try {
            limpiarReservasSesion($conexion, $sessionId);
        } catch (Throwable $cleanupError) {
            error_log('No se pudieron limpiar las reservas virtuales tras un error de venta: ' . $cleanupError->getMessage());
        }

        throw $error;
    }
}

/**
 * Procesa un canje de premios.
 *
 * @throws InvalidArgumentException
 */
function canjearPremios(PDO $conexion, array $payload): array
{
    $clienteId = isset($payload['clienteId']) ? (int) $payload['clienteId'] : 0;
    if ($clienteId <= 0) {
        throw new InvalidArgumentException('El cliente seleccionado no es válido.');
    }

    $premiosRecibidos = $payload['premios'] ?? [];
    if (!is_array($premiosRecibidos) || empty($premiosRecibidos)) {
        throw new InvalidArgumentException('Seleccioná al menos un premio para registrar el canje.');
    }

    $premiosNormalizados = [];
    foreach ($premiosRecibidos as $premio) {
        if (!is_array($premio)) {
            continue;
        }

        $premioId = isset($premio['id']) ? (int) $premio['id'] : (int) ($premio['premio_id'] ?? 0);
        $cantidad = isset($premio['cantidad']) ? (int) $premio['cantidad'] : (int) ($premio['qty'] ?? 0);

        if ($premioId <= 0 || $cantidad <= 0) {
            continue;
        }

        if (isset($premiosNormalizados[$premioId])) {
            $premiosNormalizados[$premioId] += $cantidad;
        } else {
            $premiosNormalizados[$premioId] = $cantidad;
        }
    }

    if (empty($premiosNormalizados)) {
        throw new InvalidArgumentException('Seleccioná al menos un premio para registrar el canje.');
    }

    try {
        $conexion->beginTransaction();

        $stmtCliente = $conexion->prepare('SELECT puntos FROM tbl_clientes WHERE ID = :id FOR UPDATE');
        $stmtCliente->execute([':id' => $clienteId]);
        $puntosActuales = $stmtCliente->fetchColumn();

        if ($puntosActuales === false) {
            throw new InvalidArgumentException('No encontramos al cliente seleccionado.');
        }

        $puntosActuales = (int) $puntosActuales;

        $idsPremios = array_keys($premiosNormalizados);
        $placeholders = implode(',', array_fill(0, count($idsPremios), '?'));
        $stmtPremios = $conexion->prepare("SELECT id, nombre, costo_puntos FROM premios WHERE id IN ($placeholders)");
        $stmtPremios->execute($idsPremios);
        $premiosDisponibles = $stmtPremios->fetchAll(PDO::FETCH_ASSOC);

        if (count($premiosDisponibles) !== count($idsPremios)) {
            throw new InvalidArgumentException('Uno de los premios seleccionados ya no está disponible.');
        }

        $totalPuntos = 0;
        $descripcionDetalle = [];
        $detalleCanje = [];

        foreach ($premiosDisponibles as $premio) {
            $premioId = (int) ($premio['id'] ?? 0);
            $cantidad = $premiosNormalizados[$premioId] ?? 0;
            $costo = (int) ($premio['costo_puntos'] ?? 0);

            if ($costo <= 0) {
                throw new InvalidArgumentException('Uno de los premios tiene un costo inválido.');
            }

            $totalPuntos += $costo * $cantidad;
            $descripcionDetalle[] = sprintf('%dx %s', $cantidad, (string) ($premio['nombre'] ?? 'Premio'));
            $detalleCanje[] = [
                'id' => $premioId,
                'nombre' => (string) ($premio['nombre'] ?? 'Premio'),
                'cantidad' => $cantidad,
                'costo_puntos' => $costo,
                'total_puntos' => $costo * $cantidad,
            ];
        }

        if ($totalPuntos <= 0) {
            throw new InvalidArgumentException('La selección de premios no es válida.');
        }

        if ($totalPuntos > $puntosActuales) {
            throw new InvalidArgumentException('No hay puntos suficientes para completar el canje.');
        }

        $puntosFinales = $puntosActuales - $totalPuntos;

        $stmtActualizar = $conexion->prepare('UPDATE tbl_clientes SET puntos = :puntos WHERE ID = :id');
        $stmtActualizar->execute([
            ':puntos' => $puntosFinales,
            ':id' => $clienteId,
        ]);

        $descripcion = 'Canje de premios: ' . implode(', ', $descripcionDetalle);
        if (function_exists('mb_substr')) {
            $descripcion = mb_substr($descripcion, 0, 255);
        } else {
            $descripcion = substr($descripcion, 0, 255);
        }

        $stmtMovimiento = $conexion->prepare('INSERT INTO movimientos_puntos (cliente_id, tipo, descripcion, puntos, saldo_resultante) VALUES (:cliente_id, :tipo, :descripcion, :puntos, :saldo)');
        $stmtMovimiento->execute([
            ':cliente_id' => $clienteId,
            ':tipo' => 'canje',
            ':descripcion' => $descripcion,
            ':puntos' => -$totalPuntos,
            ':saldo' => $puntosFinales,
        ]);

        $conexion->commit();

        return [
            'mensaje' => 'El canje de premios se registró correctamente.',
            'puntos_actuales' => $puntosFinales,
            'puntos_restantes' => $puntosFinales,
            'puntos_usados' => $totalPuntos,
            'detalle' => $detalleCanje,
            'descripcion' => $descripcion,
            'fecha' => date('c'),
        ];
    } catch (Throwable $error) {
        if ($conexion->inTransaction()) {
            $conexion->rollBack();
        }

        throw $error;
    }
}

// Manejo de solicitudes AJAX
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && stripos($contentType, 'application/json') !== false) {
    $datos = json_decode(file_get_contents('php://input') ?: 'null', true);
    if (!is_array($datos)) {
        responderJson([
            'exito' => false,
            'mensaje' => 'No se pudieron interpretar los datos enviados.',
        ], 400);
    }

    $tokenSesion = $_SESSION['csrf_token'] ?? '';
    $tokenRecibido = isset($datos['csrf_token']) ? (string) $datos['csrf_token'] : '';

    if ($tokenSesion === '' || $tokenRecibido === '' || !hash_equals($tokenSesion, $tokenRecibido)) {
        responderJson([
            'exito' => false,
            'mensaje' => 'El token de seguridad no es válido. Actualizá la página e intentá nuevamente.',
        ], 403);
    }

    try {
        $accion = isset($datos['accion']) ? (string) $datos['accion'] : '';

        switch ($accion) {
            case 'registrar_descuento':
                $resultado = registrarVentaConDescuento($conexion, $datos);
                responderJson([
                    'exito' => true,
                    'mensaje' => $resultado['mensaje'],
                    'resumen' => $resultado,
                ]);
                break;

            case 'canjear_premios':
                $resultado = canjearPremios($conexion, $datos);
                responderJson([
                    'exito' => true,
                    'mensaje' => $resultado['mensaje'],
                    'resumen' => $resultado,
                ]);
                break;

            default:
                responderJson([
                    'exito' => false,
                    'mensaje' => 'La acción solicitada no está disponible.',
                ], 400);
        }
    } catch (InvalidArgumentException $argumentError) {
        responderJson([
            'exito' => false,
            'mensaje' => $argumentError->getMessage(),
        ], 422);
    } catch (Throwable $error) {
        error_log('Error al procesar canje de puntos: ' . $error->getMessage());
        responderJson([
            'exito' => false,
            'mensaje' => 'Ocurrió un error inesperado al procesar la solicitud. Intentá nuevamente en unos minutos.',
        ], 500);
    }
}

$clienteId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($clienteId <= 0) {
    $_SESSION['error_clientes'] = 'No encontramos al cliente que querés ver.';
    header('Location: clientes.php');
    exit;
}

try {
    $stmtCliente = $conexion->prepare('SELECT ID, nombre, telefono, email, puntos, fecha_registro FROM tbl_clientes WHERE ID = :id');
    $stmtCliente->execute([':id' => $clienteId]);
    $cliente = $stmtCliente->fetch(PDO::FETCH_ASSOC);

    if (!$cliente) {
        $_SESSION['error_clientes'] = 'No encontramos al cliente que querés ver.';
        header('Location: clientes.php');
        exit;
    }

    $configuracionPuntos = obtenerConfiguracionPuntos($conexion);

    $stmtMenu = $conexion->query('SELECT ID, nombre, precio, categoria, foto FROM tbl_menu WHERE visible_en_menu = 1 ORDER BY categoria, nombre');
    $menuDisponible = $stmtMenu->fetchAll(PDO::FETCH_ASSOC);

    $categoriasMenu = [];
    $menuNormalizado = [];
    foreach ($menuDisponible as $producto) {
        $categoria = (string) ($producto['categoria'] ?? 'General');
        $categoriasMenu[$categoria] = true;

        $menuNormalizado[] = [
            'id' => (int) ($producto['ID'] ?? 0),
            'nombre' => (string) ($producto['nombre'] ?? ''),
            'precio' => (float) ($producto['precio'] ?? 0),
            'categoria' => $categoria,
            'imagen' => rutaImagenMenu($producto['foto'] ?? null),
        ];
    }

    $categoriasMenu = array_keys($categoriasMenu);
    sort($categoriasMenu);

    $tieneImagenPremio = piccolo_columna_existe($conexion, 'premios', 'imagen');
    $sqlPremios = $tieneImagenPremio
        ? 'SELECT id, nombre, descripcion, costo_puntos, imagen FROM premios ORDER BY nombre ASC'
        : 'SELECT id, nombre, descripcion, costo_puntos FROM premios ORDER BY nombre ASC';

    $stmtPremios = $conexion->query($sqlPremios);
    $premios = $stmtPremios->fetchAll(PDO::FETCH_ASSOC);

    $premiosNormalizados = [];
    foreach ($premios as $premio) {
        $imagen = null;
        if ($tieneImagenPremio) {
            $valorImagen = $premio['imagen'] ?? null;
            if (is_string($valorImagen) && trim($valorImagen) !== '') {
                $valorImagen = trim($valorImagen);
                if (preg_match('/^https?:\/\//i', $valorImagen)) {
                    $imagen = $valorImagen;
                } else {
                    $imagen = '../public/img/premios/' . ltrim($valorImagen, '/');
                }
            }
        }

        $premiosNormalizados[] = [
            'id' => (int) ($premio['id'] ?? 0),
            'nombre' => (string) ($premio['nombre'] ?? ''),
            'descripcion' => $premio['descripcion'] ?? null,
            'costo_puntos' => (int) ($premio['costo_puntos'] ?? 0),
            'imagen' => $imagen,
        ];
    }
} catch (Throwable $error) {
    error_log('No se pudo cargar la información del canje: ' . $error->getMessage());
    $_SESSION['error_clientes'] = 'Ocurrió un error al intentar cargar la sección de canje. Intentá nuevamente.';
    header('Location: clientes.php');
    exit;
}

$clienteNombre = htmlspecialchars($cliente['nombre'] ?? '', ENT_QUOTES, 'UTF-8');
$clienteTelefono = htmlspecialchars($cliente['telefono'] ?? '', ENT_QUOTES, 'UTF-8');
$clienteEmail = htmlspecialchars((string) ($cliente['email'] ?? ''), ENT_QUOTES, 'UTF-8');
$clientePuntos = (int) ($cliente['puntos'] ?? 0);
$clienteFecha = $cliente['fecha_registro'] ?? null;

$metodosPago = [
    ['valor' => 'Efectivo', 'texto' => 'Efectivo'],
    ['valor' => 'Tarjeta', 'texto' => 'Tarjeta'],
    ['valor' => 'MercadoPago', 'texto' => 'Mercado Pago'],
];

$tiposEntrega = [
    ['valor' => 'Retiro', 'texto' => 'Retiro en local'],
    ['valor' => 'Delivery', 'texto' => 'Delivery'],
];

include __DIR__ . '/templates/header.php';
?>

<div class="py-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
        <div>
            <h1 class="h4 mb-1">Canje de puntos</h1>
            <p class="text-muted mb-0">Gestioná descuentos o premios disponibles para el cliente.</p>
        </div>
        <a href="clientes.php" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-arrow-left me-1" aria-hidden="true"></i>
            Volver al listado
        </a>
    </div>
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-4 align-items-center">
                <div class="col-12 col-md-6">
                    <h2 class="h5 mb-3">Datos del cliente</h2>
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Nombre</dt>
                        <dd class="col-sm-8 mb-2"><?= $clienteNombre; ?></dd>

                        <dt class="col-sm-4">Teléfono</dt>
                        <dd class="col-sm-8 mb-2"><?= $clienteTelefono !== '' ? $clienteTelefono : '<span class="text-muted">Sin registrar</span>'; ?></dd>

                        <dt class="col-sm-4">Email</dt>
                        <dd class="col-sm-8 mb-2"><?= $clienteEmail !== '' ? $clienteEmail : '<span class="text-muted">Sin registrar</span>'; ?></dd>

                        <dt class="col-sm-4">Puntos disponibles</dt>
                        <dd id="puntosClienteValor" class="col-sm-8 mb-0 fw-semibold text-success">
                            <?= number_format($clientePuntos, 0, ',', '.'); ?>
                        </dd>
                    </dl>
                </div>
                <div class="col-12 col-md-6">
                    <div class="admin-surface-panel rounded-3 p-3 h-100">
                        <h2 class="h6 mb-2">Parámetros del sistema de puntos</h2>
                        <ul class="list-unstyled mb-0 small">
                            <li><strong>Mínimo para canjear:</strong> <?= number_format((int) ($configuracionPuntos['minimo_puntos'] ?? 0), 0, ',', '.'); ?> pts</li>
                            <li><strong>Valor por punto:</strong> $<?= number_format((float) ($configuracionPuntos['valor_punto'] ?? 0), 2, ',', '.'); ?></li>
                            <li><strong>Tope por venta:</strong> <?= number_format((float) ($configuracionPuntos['maximo_porcentaje'] ?? 0) * 100, 0); ?>%</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header admin-card-header border-bottom-0">
            <div class="d-flex flex-wrap align-items-center gap-3">
                <h2 class="h5 mb-0">Opciones de canje</h2>
                <div class="btn-group modo-canje-toggle" role="group" aria-label="Modo de canje">
                    <input type="radio" class="btn-check" name="modoCanje" id="modoDescuento" value="descuento" checked>
                    <label class="btn btn-outline-primary" for="modoDescuento">
                        <i class="fa-solid fa-tags me-1" aria-hidden="true"></i>
                        Descuento
                    </label>
                    <input type="radio" class="btn-check" name="modoCanje" id="modoPremios" value="premios">
                    <label class="btn btn-outline-primary" for="modoPremios">
                        <i class="fa-solid fa-gift me-1" aria-hidden="true"></i>
                        Premios
                    </label>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div id="alertasCanje" class="d-none"></div>

            <div id="modoDescuentoPanel" class="modo-canje-panel">
                <div class="row g-4">
                    <div class="col-12 col-lg-7">
                        <div class="modo-canje-filtros rounded-3 p-3 mb-3">
                            <div class="row g-3 align-items-end">
                                <div class="col-12 col-md-6">
                                    <label for="filtroNombre" class="form-label">Buscar producto</label>
                                    <input type="search" id="filtroNombre" class="form-control" placeholder="Nombre o palabra clave">
                                </div>
                                <div class="col-12 col-md-6">
                                    <label for="filtroCategoria" class="form-label">Categoría</label>
                                    <select id="filtroCategoria" class="form-select">
                                        <option value="">Todas las categorías</option>
                                        <?php foreach ($categoriasMenu as $categoria): ?>
                                            <option value="<?= htmlspecialchars($categoria, ENT_QUOTES, 'UTF-8'); ?>">
                                                <?= htmlspecialchars($categoria, ENT_QUOTES, 'UTF-8'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div id="listadoProductos" class="row g-3"></div>
                        <div id="estadoListadoProductos" class="text-center text-muted small py-3 d-none"></div>
                    </div>

                    <div class="col-12 col-lg-5">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <h3 class="h6 d-flex align-items-center justify-content-between mb-3">
                                    Productos seleccionados
                                    <button type="button" class="btn btn-link btn-sm text-danger p-0" id="btnVaciarSeleccion">
                                        Vaciar
                                    </button>
                                </h3>

                                <div class="table-responsive mb-3">
                                    <table class="table table-sm align-middle mb-0" data-no-datatable>
                                        <thead class="admin-table-head">
                                            <tr>
                                                <th scope="col">Producto</th>
                                                <th scope="col" class="text-center">Cant.</th>
                                                <th scope="col" class="text-end">Subtotal</th>
                                                <th scope="col" class="text-center">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tablaProductosSeleccionados">
                                            <tr class="text-muted" data-estado="vacio">
                                                <td colspan="4" class="text-center py-4">Todavía no agregaste productos.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="border-top pt-3">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Total parcial</span>
                                        <strong id="resumenTotalParcial">$0,00</strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2" id="filaPuntosAplicados">
                                        <span>Puntos aplicados</span>
                                        <strong id="resumenPuntosAplicados">0 pts ($0,00)</strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2 text-success">
                                        <span>Descuento por puntos</span>
                                        <strong id="resumenDescuento">-$0,00</strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-3 fs-5">
                                        <span>Total a cobrar</span>
                                        <strong id="resumenTotalFinal">$0,00</strong>
                                    </div>
                                    <div id="alertaMinimoPuntos" class="alert alert-warning d-none" role="alert">
                                        <i class="fa-solid fa-circle-info me-2" aria-hidden="true"></i>
                                        <span id="alertaMinimoPuntosTexto">El cliente aún no tiene puntos disponibles para realizar un canje por descuento. Todavía puede canjear premios.</span>
                                    </div>

                                    <div class="mb-3">
                                        <h4 class="h6">Aplicar puntos</h4>
                                        <label for="inputPuntosPersonalizados" class="form-label small mb-1">Cantidad de puntos a usar</label>
                                        <div class="input-group input-group-sm">
                                            <input type="number" class="form-control" id="inputPuntosPersonalizados" min="0" step="1" value="0" inputmode="numeric">
                                            <button class="btn btn-outline-secondary" type="button" id="btnMaximoPuntos">Máximo</button>
                                        </div>
                                        <div class="form-text" id="ayudaPuntos">Disponible: <?= number_format($clientePuntos, 0, ',', '.'); ?> pts</div>
                                        <div class="invalid-feedback d-none" id="errorPuntosPersonalizados"></div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="selectMetodoPago" class="form-label">Método de pago</label>
                                        <select id="selectMetodoPago" class="form-select">
                                            <option value="">Seleccioná una opción</option>
                                            <?php foreach ($metodosPago as $metodo): ?>
                                                <option value="<?= htmlspecialchars($metodo['valor'], ENT_QUOTES, 'UTF-8'); ?>">
                                                    <?= htmlspecialchars($metodo['texto'], ENT_QUOTES, 'UTF-8'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="invalid-feedback">Seleccioná un método de pago.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="selectTipoEntrega" class="form-label">Tipo de entrega</label>
                                        <select id="selectTipoEntrega" class="form-select">
                                            <option value="">Seleccioná una opción</option>
                                            <?php foreach ($tiposEntrega as $tipo): ?>
                                                <option value="<?= htmlspecialchars($tipo['valor'], ENT_QUOTES, 'UTF-8'); ?>">
                                                    <?= htmlspecialchars($tipo['texto'], ENT_QUOTES, 'UTF-8'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="invalid-feedback">Seleccioná el tipo de entrega.</div>
                                    </div>

                                    <div id="camposDelivery" class="mb-3 d-none">
                                        <label for="inputDireccion" class="form-label">Dirección de entrega</label>
                                        <input type="text" class="form-control" id="inputDireccion" placeholder="Ej.: San Martín 1234">
                                        <div class="invalid-feedback">Ingresá la dirección para el envío.</div>
                                        <label for="inputReferencias" class="form-label mt-2">Referencias</label>
                                        <textarea class="form-control" id="inputReferencias" rows="2" placeholder="Datos adicionales para la entrega"></textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label for="textareaNota" class="form-label">Nota interna</label>
                                        <textarea class="form-control" id="textareaNota" rows="2" placeholder="Observaciones opcionales"></textarea>
                                    </div>

                                    <button type="button" class="btn btn-success w-100" id="btnRegistrarVenta" disabled>
                                        <i class="fa-solid fa-cash-register me-1" aria-hidden="true"></i>
                                        Registrar venta con descuento
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="modoPremiosPanel" class="modo-canje-panel d-none">
                <div class="row g-4">
                    <div class="col-12 col-lg-8">
                        <div class="row g-3" id="listadoPremios"></div>
                        <div id="estadoListadoPremios" class="text-center text-muted small py-3 d-none"></div>
                    </div>
                    <div class="col-12 col-lg-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <h3 class="h6 mb-3">Resumen del canje</h3>
                                <p class="mb-2">
                                    <span class="text-muted">Puntos disponibles:</span>
                                    <strong id="premiosPuntosDisponibles"><?= number_format($clientePuntos, 0, ',', '.'); ?> pts</strong>
                                </p>
                                <p class="mb-4">
                                    <span class="text-muted">Puntos a utilizar:</span>
                                    <strong id="premiosPuntosUsados">0 pts</strong>
                                </p>

                                <button type="button" class="btn btn-primary w-100" id="btnConfirmarPremios" disabled>
                                    <i class="fa-solid fa-check me-1" aria-hidden="true"></i>
                                    Confirmar canje de premios
                                </button>
                                <div class="form-text mt-2">No se descontarán más puntos que los disponibles.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="resumenVentaModal" tabindex="-1" aria-labelledby="resumenVentaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h2 class="modal-title h5" id="resumenVentaModalLabel">Venta registrada</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-4">El pedido fue enviado al panel de cocina y los puntos se actualizaron para el cliente.</p>
                <div class="row gy-4">
                    <div class="col-12 col-lg-7">
                        <h3 class="h6 mb-3">Detalle del pedido</h3>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0" data-no-datatable>
                                <thead class="admin-table-head">
                                    <tr>
                                        <th scope="col">Producto</th>
                                        <th scope="col" class="text-end">Cantidad</th>
                                        <th scope="col" class="text-end">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody id="resumenVentaProductos">
                                    <tr class="text-muted">
                                        <td colspan="3" class="text-center py-3">Sin productos.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-12 col-lg-5">
                        <h3 class="h6 mb-3">Resumen del canje</h3>
                        <ul class="list-unstyled small mb-0">
                            <li class="d-flex justify-content-between mb-2"><span>Total original</span><strong id="resumenVentaTotalOriginal">$0,00</strong></li>
                            <li class="d-flex justify-content-between mb-2"><span>Puntos aplicados</span><strong id="resumenVentaPuntosUsados">0 pts</strong></li>
                            <li class="d-flex justify-content-between mb-2"><span>Descuento</span><strong id="resumenVentaDescuento">-$0,00</strong></li>
                            <li class="d-flex justify-content-between mb-2"><span>Total final</span><strong id="resumenVentaTotalFinal">$0,00</strong></li>
                            <li class="d-flex justify-content-between mb-2"><span>Puntos ganados</span><strong id="resumenVentaPuntosGanados">0 pts</strong></li>
                            <li class="d-flex justify-content-between mb-0"><span>Puntos actuales del cliente</span><strong id="resumenVentaPuntosActuales">0 pts</strong></li>
                        </ul>
                        <div class="mt-3 border-top pt-3 small">
                            <p class="mb-1"><strong>Pedido #</strong> <span id="resumenVentaPedidoId">-</span></p>
                            <p class="mb-1"><strong>Método de pago:</strong> <span id="resumenVentaMetodoPago">-</span></p>
                            <p class="mb-0"><strong>Tipo de entrega:</strong> <span id="resumenVentaTipoEntrega">-</span></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Entendido</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalCanjePremios" tabindex="-1" aria-labelledby="modalCanjePremiosLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h2 class="modal-title h5" id="modalCanjePremiosLabel">Canje de premios registrado</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p id="modalCanjePremiosMensaje" class="text-muted">El canje se registró correctamente.</p>
                <div class="table-responsive mb-4">
                    <table class="table table-sm align-middle" data-no-datatable>
                        <thead class="admin-table-head">
                            <tr>
                                <th scope="col">Premio</th>
                                <th scope="col" class="text-end">Cantidad</th>
                                <th scope="col" class="text-end">Costo (pts)</th>
                                <th scope="col" class="text-end">Total (pts)</th>
                            </tr>
                        </thead>
                        <tbody id="modalCanjePremiosDetalle">
                            <tr class="text-muted">
                                <td colspan="4" class="text-center py-3">Sin información disponible.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="row gy-3">
                    <div class="col-12 col-md-6">
                        <div class="border rounded-3 p-3 h-100">
                            <h3 class="h6 mb-3">Resumen de puntos</h3>
                            <ul class="list-unstyled mb-0 small">
                                <li class="d-flex justify-content-between mb-2"><span>Puntos utilizados</span><strong id="modalCanjePremiosPuntosUsados">0 pts</strong></li>
                                <li class="d-flex justify-content-between"><span>Puntos restantes</span><strong id="modalCanjePremiosPuntosRestantes">0 pts</strong></li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="border rounded-3 p-3 h-100">
                            <h3 class="h6 mb-3">Detalles del canje</h3>
                            <p class="mb-2 small text-muted">Registrado el <span id="modalCanjePremiosFecha">-</span></p>
                            <p class="mb-0 small" id="modalCanjePremiosDescripcion"></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Entendido</button>
            </div>
        </div>
    </div>
</div>

<script>
    window.ClienteCanjeData = <?php
                                echo json_encode([
                                    'csrf_token' => $_SESSION['csrf_token'] ?? '',
                                    'cliente' => [
                                        'id' => (int) ($cliente['ID'] ?? 0),
                                        'puntos' => $clientePuntos,
                                    ],
                                    'menu' => $menuNormalizado,
                                    'premios' => $premiosNormalizados,
                                    'config' => [
                                        'minimo_puntos' => (int) ($configuracionPuntos['minimo_puntos'] ?? 0),
                                        'valor_punto' => (float) ($configuracionPuntos['valor_punto'] ?? 0),
                                        'maximo_porcentaje' => (float) ($configuracionPuntos['maximo_porcentaje'] ?? 0),
                                    ],
                                    'reservas_endpoint' => piccolo_public_base_url() . 'api/carrito_actualizar.php',
                                    'disponibilidad_endpoint' => piccolo_public_base_url() . 'api/disponibilidad_menu.php',
                                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                                ?>;
</script>
<script src="<?= $url_base; ?>assets/js/clientes_canje.js"></script>

<?php include __DIR__ . '/templates/footer.php'; ?>