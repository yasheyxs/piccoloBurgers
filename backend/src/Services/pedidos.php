<?php

/**
 * Creación de pedidos. La usa la API (POST /pedidos).
 *
 * Los precios y nombres de los productos siempre se toman de la base de datos:
 * lo que mande el navegador solo indica qué productos y cuántos.
 */

require_once __DIR__ . '/Security/validar_telefono.php';
require_once __DIR__ . '/puntos_config.php';
require_once __DIR__ . '/menu_catalogo.php';

if (!class_exists('PedidoException')) {
    /** Error de validación del pedido, con un mensaje pensado para mostrarle al cliente. */
    class PedidoException extends RuntimeException
    {
    }
}

if (!function_exists('pedidoMetodosPago')) {
    /** @return string[] */
    function pedidoMetodosPago(): array
    {
        return ['Efectivo', 'Tarjeta', 'MercadoPago'];
    }
}

if (!function_exists('pedidoTiposEntrega')) {
    /** @return string[] */
    function pedidoTiposEntrega(): array
    {
        return ['Retiro', 'Delivery'];
    }
}

if (!function_exists('pedidoCalcularCanjePuntos')) {
    /**
     * Cuántos puntos se canjean en un pedido y qué descuento dan.
     *
     * El descuento no puede superar el porcentaje máximo del total, y se redondea hacia abajo
     * a múltiplos de $100. Si el cliente no llega al mínimo de puntos, no canjea nada.
     *
     * @param array{minimo_puntos?: int|string, valor_punto?: float|string, maximo_porcentaje?: float|string} $configuracion
     * @return array{puntos_usados: int, descuento: float}
     */
    function pedidoCalcularCanjePuntos(float $total, int $puntosDisponibles, array $configuracion): array
    {
        $valorPorPunto = max(0.01, (float) ($configuracion['valor_punto'] ?? 0));
        $minimoPuntos = max(0, (int) ($configuracion['minimo_puntos'] ?? 0));
        $maximoPorcentaje = (float) ($configuracion['maximo_porcentaje'] ?? 0.25);
        $maximoPorcentaje = $maximoPorcentaje <= 0 ? 0.25 : min(1, $maximoPorcentaje);

        if ($puntosDisponibles < $minimoPuntos || $total <= 0) {
            return ['puntos_usados' => 0, 'descuento' => 0.0];
        }

        $descuentoMaximo = $total * $maximoPorcentaje;
        $descuentoMaximoRedondeado = floor($descuentoMaximo / 100) * 100;
        $puntosUsados = max(0, min($puntosDisponibles, (int) floor($descuentoMaximoRedondeado / $valorPorPunto)));

        return [
            'puntos_usados' => $puntosUsados,
            'descuento' => $puntosUsados > 0 ? min($puntosUsados * $valorPorPunto, $descuentoMaximo) : 0.0,
        ];
    }
}

if (!function_exists('pedidoValidarPuntosElegidos')) {
    /**
     * Canje con una cantidad de puntos elegida a mano (venta desde el panel). A diferencia del sitio,
     * que usa el máximo redondeado a $100, acá se respeta lo que se eligió si entra en las reglas:
     * no más de lo que tiene el cliente, no más del tope por venta y, si se usan, al menos el mínimo.
     *
     * @param array{minimo_puntos?: int|string, valor_punto?: float|string, maximo_porcentaje?: float|string} $configuracion
     * @return array{puntos_usados: int, descuento: float}
     * @throws PedidoException
     */
    function pedidoValidarPuntosElegidos(float $total, int $puntosDisponibles, array $configuracion, int $puntosElegidos): array
    {
        $valorPorPunto = max(0.01, (float) ($configuracion['valor_punto'] ?? 0));
        $minimoPuntos = max(0, (int) ($configuracion['minimo_puntos'] ?? 0));
        $maximoPorcentaje = min(1, max(0, (float) ($configuracion['maximo_porcentaje'] ?? 0.25)));
        $descuentoMaximo = $total * $maximoPorcentaje;
        $maximoPuntos = (int) floor($descuentoMaximo / $valorPorPunto);

        if ($puntosElegidos < 0) {
            throw new PedidoException('Ingresá una cantidad de puntos válida.');
        }
        if ($puntosElegidos === 0) {
            return ['puntos_usados' => 0, 'descuento' => 0.0];
        }
        if ($puntosElegidos > $puntosDisponibles) {
            throw new PedidoException('El cliente no tiene puntos suficientes para ese canje.');
        }
        if ($puntosElegidos > $maximoPuntos) {
            throw new PedidoException(sprintf('Para esta venta se pueden usar hasta %d puntos.', $maximoPuntos));
        }
        if ($puntosElegidos < $minimoPuntos) {
            throw new PedidoException(sprintf('El mínimo de puntos para canjear es de %d.', $minimoPuntos));
        }

        return ['puntos_usados' => $puntosElegidos, 'descuento' => min($puntosElegidos * $valorPorPunto, $descuentoMaximo)];
    }
}

if (!function_exists('pedidoPuntosGanados')) {
    /** Puntos que suma un pedido: 1 cada $1500 pagados. */
    function pedidoPuntosGanados(float $totalPagado): int
    {
        return max(0, (int) floor($totalPagado / 1500));
    }
}

if (!function_exists('crearPedido')) {
    /**
     * Valida y registra un pedido, aplica el canje de puntos y suma los puntos ganados.
     * Los puntos usados y ganados quedan anotados en movimientos_puntos.
     *
     * Con $puntosElegidos (venta desde el panel) se usan exactamente esos puntos en lugar del máximo.
     *
     * @param array{
     *   nombre?: string, telefono?: string, codigo_pais?: string, email?: ?string, nota?: string,
     *   metodo_pago?: string, tipo_entrega?: string, direccion?: string, referencias?: string,
     *   usar_puntos?: bool, items?: array<int, array{id?: int|string, cantidad?: int|string}>
     * } $datos
     * @return array{pedido_id:int, nombre:string, items:array, total_original:float, descuento:float, total:float, puntos_ganados:int, puntos_usados:int}
     * @throws PedidoException si los datos no son válidos o no hay stock
     */
    function crearPedido(PDO $conexion, array $datos, ?int $clienteId = null, ?int $puntosElegidos = null): array
    {
        $nombre = trim((string) ($datos['nombre'] ?? ''));
        $telefono = trim((string) ($datos['telefono'] ?? ''));
        $codigoPais = trim((string) ($datos['codigo_pais'] ?? ''));
        $email = trim((string) ($datos['email'] ?? ''));
        $nota = trim((string) ($datos['nota'] ?? ''));
        $metodoPago = trim((string) ($datos['metodo_pago'] ?? ''));
        $tipoEntrega = trim((string) ($datos['tipo_entrega'] ?? ''));
        $direccion = trim((string) ($datos['direccion'] ?? ''));
        $referencias = trim((string) ($datos['referencias'] ?? ''));
        $usarPuntos = !empty($datos['usar_puntos']);
        $itemsSolicitados = is_array($datos['items'] ?? null) ? $datos['items'] : [];

        if ($nombre === '' || $telefono === '' || $codigoPais === '') {
            throw new PedidoException('Faltan datos obligatorios.');
        }

        $email = $email === '' ? null : $email;
        if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new PedidoException('Ingresá un email válido o dejalo en blanco.');
        }

        $telefonoFormateado = validarTelefono($codigoPais, $telefono);
        if ($telefonoFormateado === false) {
            throw new PedidoException('Ingresá un número de teléfono válido.');
        }

        if (!$itemsSolicitados) {
            throw new PedidoException('El carrito está vacío.');
        }

        if (!in_array($metodoPago, pedidoMetodosPago(), true) || !in_array($tipoEntrega, pedidoTiposEntrega(), true)) {
            throw new PedidoException('Por favor, seleccioná método de pago y tipo de entrega.');
        }

        if ($tipoEntrega === 'Delivery' && $direccion === '') {
            throw new PedidoException('Debes ingresar una dirección para el envío.');
        }

        $referencias = ($tipoEntrega === 'Delivery' && $referencias !== '')
            ? (function_exists('mb_substr') ? mb_substr($referencias, 0, 255) : substr($referencias, 0, 255))
            : null;

        // Agrupar cantidades por producto
        $cantidades = [];
        foreach ($itemsSolicitados as $item) {
            $id = is_array($item) ? (int) ($item['id'] ?? 0) : 0;
            $cantidad = is_array($item) && is_numeric($item['cantidad'] ?? null) ? (int) $item['cantidad'] : 0;
            if ($id <= 0 || $cantidad <= 0) {
                throw new PedidoException('Hay un producto inválido en el carrito.');
            }
            $cantidades[$id] = ($cantidades[$id] ?? 0) + $cantidad;
        }

        $productos = obtenerProductosMenuPorId($conexion, array_keys($cantidades));
        $items = [];
        $totalOriginal = 0.0;
        foreach ($cantidades as $id => $cantidad) {
            if (!isset($productos[$id])) {
                throw new PedidoException('Uno de los productos del carrito ya no está disponible.');
            }
            $items[] = [
                'id' => $id,
                'nombre' => $productos[$id]['nombre'],
                'precio' => $productos[$id]['precio'],
                'cantidad' => $cantidad,
            ];
            $totalOriginal += $productos[$id]['precio'] * $cantidad;
        }

        $configuracionPuntos = obtenerConfiguracionPuntos($conexion);


        $total = $totalOriginal;
        $descuento = 0.0;
        $puntosUsados = 0;
        $puntosGanados = 0;

        $conexion->beginTransaction();
        try {
            // Verificar que alcancen los insumos de cada producto
            $stmtInsumos = $conexion->prepare(
                'SELECT mp.cantidad AS stock_actual, req.cantidad AS requerido
                 FROM tbl_menu_materias_primas req
                 JOIN tbl_materias_primas mp ON req.materia_prima_id = mp.ID
                 WHERE req.menu_id = ?'
            );
            foreach ($items as $item) {
                $stmtInsumos->execute([$item['id']]);
                foreach ($stmtInsumos->fetchAll(PDO::FETCH_ASSOC) as $insumo) {
                    if ((float) $insumo['stock_actual'] < (float) $insumo['requerido'] * $item['cantidad']) {
                        throw new PedidoException('Ups... hubo un inconveniente al procesar tu pedido. Por favor, revisá tu carrito o intentá nuevamente en unos minutos.');
                    }
                }
            }

            // Canje de puntos
            $saldo = 0;
            if ($clienteId) {
                $stmt = $conexion->prepare('SELECT COALESCE(puntos, 0) FROM tbl_clientes WHERE ID = ? FOR UPDATE');
                $stmt->execute([$clienteId]);
                $saldo = (int) $stmt->fetchColumn();
            }
            if (($usarPuntos || $puntosElegidos !== null) && $clienteId) {
                $puntosDisponibles = $saldo;

                $canje = $puntosElegidos !== null
                    ? pedidoValidarPuntosElegidos($total, $puntosDisponibles, $configuracionPuntos, $puntosElegidos)
                    : pedidoCalcularCanjePuntos($total, $puntosDisponibles, $configuracionPuntos);
                $puntosUsados = $canje['puntos_usados'];

                if ($puntosUsados > 0) {
                    $descuento = $canje['descuento'];
                    $total -= $descuento;

                    $stmt = $conexion->prepare('UPDATE tbl_clientes SET puntos = puntos - ? WHERE ID = ?');
                    $stmt->execute([$puntosUsados, $clienteId]);
                }
            }

            $stmt = $conexion->prepare(
                'INSERT INTO tbl_pedidos (nombre, telefono, email, nota, total, metodo_pago, tipo_entrega, direccion, referencias, estado, esta_pago, cliente_id)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $nombre, $telefonoFormateado, $email, $nota, $total, $metodoPago, $tipoEntrega,
                $direccion, $referencias, 'En preparación', 'No', $clienteId,
            ]);
            $pedidoId = (int) $conexion->lastInsertId();

            $stmt = $conexion->prepare(
                'INSERT INTO tbl_pedidos_detalle (pedido_id, producto_id, nombre, precio, cantidad) VALUES (?, ?, ?, ?, ?)'
            );
            foreach ($items as $item) {
                $stmt->execute([$pedidoId, $item['id'], $item['nombre'], $item['precio'], $item['cantidad']]);
            }

            if ($clienteId) {
                $puntosGanados = pedidoPuntosGanados($total);
                $stmt = $conexion->prepare('UPDATE tbl_clientes SET puntos = puntos + ? WHERE ID = ?');
                $stmt->execute([$puntosGanados, $clienteId]);

                // Historial de puntos del cliente
                $movimiento = $conexion->prepare(
                    'INSERT INTO movimientos_puntos (cliente_id, tipo, descripcion, puntos, saldo_resultante) VALUES (?, ?, ?, ?, ?)'
                );
                if ($puntosUsados > 0) {
                    $saldo -= $puntosUsados;
                    $movimiento->execute([$clienteId, 'canje', "Canje de $puntosUsados puntos como descuento en el pedido #$pedidoId", -$puntosUsados, $saldo]);
                }
                if ($puntosGanados > 0) {
                    $saldo += $puntosGanados;
                    $movimiento->execute([$clienteId, 'compra', "Puntos ganados con el pedido #$pedidoId", $puntosGanados, $saldo]);
                }

                // Se guardan en el pedido para poder devolverlos si se cancela
                $conexion->prepare('UPDATE tbl_pedidos SET puntos_usados = ?, puntos_ganados = ? WHERE ID = ?')
                    ->execute([$puntosUsados, $puntosGanados, $pedidoId]);
            }

            $conexion->commit();
        } catch (Throwable $error) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            throw $error;
        }

        return [
            'pedido_id' => $pedidoId,
            'nombre' => $nombre,
            'items' => $items,
            'total_original' => $totalOriginal,
            'descuento' => $descuento,
            'total' => $total,
            'puntos_ganados' => $puntosGanados,
            'puntos_usados' => $puntosUsados,
        ];
    }
}

if (!function_exists('obtenerPedidosCliente')) {
    /** Pedidos de un cliente, del más nuevo al más viejo, con su detalle. */
    function obtenerPedidosCliente(PDO $conexion, int $clienteId): array
    {
        $stmt = $conexion->prepare('SELECT * FROM tbl_pedidos WHERE cliente_id = ? ORDER BY fecha DESC');
        $stmt->execute([$clienteId]);
        $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmtDetalle = $conexion->prepare('SELECT nombre, precio, cantidad FROM tbl_pedidos_detalle WHERE pedido_id = ?');
        foreach ($pedidos as &$pedido) {
            $stmtDetalle->execute([$pedido['ID']]);
            $pedido['detalles'] = $stmtDetalle->fetchAll(PDO::FETCH_ASSOC);
        }
        unset($pedido);

        return $pedidos;
    }
}
