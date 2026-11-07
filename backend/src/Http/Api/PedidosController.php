<?php

namespace Piccolo\Http\Api;

use InvalidArgumentException;
use PDO;
use PedidoException;

/**
 * Carrito (reservas de stock mientras el cliente arma su pedido) y pedidos.
 *
 * El carrito reserva unidades por sesión durante unos minutos, para que dos personas
 * no compren el último producto al mismo tiempo.
 */
final class PedidosController
{
    public function __construct(private PDO $conexion)
    {
    }

    /** GET /carrito */
    public function verCarrito(Peticion $peticion): void
    {
        Sesion::iniciar();
        $reservas = \obtenerReservasPorSesion($this->conexion, session_id());
        $this->responderCarrito($reservas);
    }

    /** PUT /carrito/productos/{id} {cantidad} — fija la cantidad (0 lo quita). */
    public function fijarCantidad(Peticion $peticion): void
    {
        Sesion::iniciar();
        $productoId = (int) $peticion->parametroRuta('id');
        $cuerpo = $peticion->cuerpo();

        if ($productoId <= 0) {
            throw ApiException::noEncontrado('El producto no existe.');
        }
        if (!isset($cuerpo['cantidad']) || !is_numeric($cuerpo['cantidad']) || (int) $cuerpo['cantidad'] < 0) {
            throw ApiException::validacion(['cantidad' => 'Indicá una cantidad igual o mayor a 0.']);
        }

        $resultado = $this->conReservas(fn() => \establecerReservaProducto(
            $this->conexion,
            session_id(),
            $productoId,
            \normalizarCantidadSolicitada($cuerpo['cantidad'])
        ));

        $this->responderCarrito($resultado['reservas'] ?? [], [$productoId]);
    }

    /** DELETE /carrito/productos/{id} */
    public function quitarProducto(Peticion $peticion): void
    {
        Sesion::iniciar();
        $productoId = (int) $peticion->parametroRuta('id');
        $resultado = $this->conReservas(fn() => \establecerReservaProducto($this->conexion, session_id(), $productoId, 0));

        $this->responderCarrito($resultado['reservas'] ?? [], [$productoId]);
    }

    /** PUT /carrito {productos: [{id, cantidad}]} — reemplaza el carrito completo. */
    public function sincronizarCarrito(Peticion $peticion): void
    {
        Sesion::iniciar();
        $productos = $peticion->cuerpo()['productos'] ?? null;
        if (!is_array($productos)) {
            throw ApiException::validacion(['productos' => 'Enviá la lista de productos del carrito.']);
        }

        $items = array_map(static fn($item) => [
            'menu_id' => is_array($item) ? (int) ($item['id'] ?? 0) : 0,
            'cantidad' => is_array($item) ? (int) ($item['cantidad'] ?? 0) : 0,
        ], $productos);

        $resultado = $this->conReservas(fn() => \sincronizarReservas($this->conexion, session_id(), $items));
        $this->responderCarrito($resultado['reservas'] ?? [], array_column($items, 'menu_id'));
    }

    /** DELETE /carrito */
    public function vaciarCarrito(Peticion $peticion): void
    {
        Sesion::iniciar();
        \limpiarReservasSesion($this->conexion, session_id());
        $this->responderCarrito([]);
    }

    /**
     * POST /pedidos — confirma un pedido. Los precios salen de la base de datos.
     * {nombre, telefono, codigoPais, email?, nota?, metodoPago, tipoEntrega, direccion?, referencias?, usarPuntos?, productos: [{id, cantidad}]}
     */
    public function crear(Peticion $peticion): void
    {
        $cuerpo = $peticion->cuerpo();
        $cliente = Sesion::cliente();

        try {
            $pedido = \crearPedido($this->conexion, [
                'nombre' => $peticion->texto('nombre', 120),
                'telefono' => $peticion->texto('telefono', 30),
                'codigo_pais' => $peticion->texto('codigoPais', 5),
                'email' => $peticion->texto('email', 160),
                'nota' => $peticion->texto('nota', 500),
                'metodo_pago' => $peticion->texto('metodoPago', 30),
                'tipo_entrega' => $peticion->texto('tipoEntrega', 30),
                'direccion' => $peticion->texto('direccion', 255),
                'referencias' => $peticion->texto('referencias', 255),
                'usar_puntos' => !empty($cuerpo['usarPuntos']),
                'items' => is_array($cuerpo['productos'] ?? null) ? $cuerpo['productos'] : [],
            ], $cliente ? (int) $cliente['id'] : null);
        } catch (PedidoException $error) {
            throw new ApiException($error->getMessage(), 422);
        }

        try {
            \limpiarReservasSesion($this->conexion, session_id());
        } catch (\Throwable $error) {
            error_log('No se pudieron liberar las reservas tras el pedido ' . $pedido['pedido_id'] . ': ' . $error->getMessage());
        }

        Respuesta::creado([
            'id' => $pedido['pedido_id'],
            'productos' => $pedido['items'],
            'totalOriginal' => $pedido['total_original'],
            'descuento' => $pedido['descuento'],
            'total' => $pedido['total'],
            'puntosUsados' => $pedido['puntos_usados'],
            'puntosGanados' => $pedido['puntos_ganados'],
        ]);
    }

    /** GET /pedidos — historial del cliente con sesión iniciada. */
    public function historial(Peticion $peticion): void
    {
        $cliente = Sesion::clienteObligatorio();
        $pedidos = \obtenerPedidosCliente($this->conexion, (int) $cliente['id']);

        Respuesta::ok(array_map([Formato::class, 'pedido'], $pedidos));
    }

    /** Las funciones de reservas avisan con excepciones cuando no hay stock o el dato es inválido. */
    private function conReservas(callable $accion): array
    {
        try {
            return $accion();
        } catch (InvalidArgumentException $error) {
            throw new ApiException($error->getMessage(), 422);
        } catch (\RuntimeException $error) {
            // Errores de base de datos: no se muestran al cliente (el router responde un 500 genérico)
            if ($error instanceof ApiException || $error instanceof \PDOException) {
                throw $error;
            }
            throw new ApiException($error->getMessage(), 409);
        }
    }

    /**
     * @param array<int, array{menu_id:int, cantidad:int}> $reservas
     * @param int[] $idsExtra productos de los que también se informa la disponibilidad
     */
    private function responderCarrito(array $reservas, array $idsExtra = []): void
    {
        $ids = array_merge(array_map(static fn($r) => (int) $r['menu_id'], $reservas), array_map('intval', $idsExtra));
        $ids = array_values(array_unique(array_filter($ids, static fn($id) => $id > 0)));
        $productos = \obtenerProductosMenuPorId($this->conexion, $ids);

        $items = [];
        $total = 0.0;
        foreach ($reservas as $reserva) {
            $id = (int) $reserva['menu_id'];
            $cantidad = (int) $reserva['cantidad'];
            if ($cantidad <= 0 || !isset($productos[$id])) {
                continue;
            }
            $subtotal = $productos[$id]['precio'] * $cantidad;
            $total += $subtotal;
            $items[] = [
                'id' => $id,
                'nombre' => $productos[$id]['nombre'],
                'precio' => $productos[$id]['precio'],
                'cantidad' => $cantidad,
                'subtotal' => $subtotal,
            ];
        }

        Respuesta::ok([
            'productos' => $items,
            'total' => $total,
            'disponibilidad' => $ids ? Formato::disponibilidad(\obtenerDisponibilidadMenu($this->conexion, $ids)) : [],
        ]);
    }
}
