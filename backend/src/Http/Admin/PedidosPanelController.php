<?php

declare(strict_types=1);

namespace Piccolo\Http\Admin;

use PDO;
use Piccolo\Http\Api\ApiException;
use Piccolo\Repositories\PedidosPanelRepositorio;

/**
 * Seguimiento de pedidos en cocina y delivery. La usan los paneles de cocina y de delivery
 * (por actualizar_estado.php y obtener_pedidos_delivery.php) y la API (/api/v1/admin/pedidos).
 *
 * Recorrido de un pedido:
 *   En preparación → Listo (retiro, o delivery que se entrega sin salir)
 *   En preparación → En camino → Listo (lo marca el delivery al entregarlo)
 *   Listo → Entregado (opcional)
 *   En preparación o En camino → Cancelado
 * Al llegar a Listo se descuentan los insumos del stock (una sola vez). Al cancelar, el cliente
 * recupera los puntos que usó y pierde los que ganó con ese pedido.
 */
final class PedidosPanelController
{
    public const ESTADOS = ['En preparación', 'Listo', 'En camino', 'Entregado', 'Cancelado'];

    /** estado actual => [estado nuevo => roles que pueden hacer ese cambio] */
    private const CAMBIOS = [
        'En preparación' => ['Listo' => ['admin', 'empleado'], 'En camino' => ['admin', 'empleado'], 'Cancelado' => ['admin', 'empleado']],
        'En camino' => ['Listo' => ['admin', 'delivery'], 'Cancelado' => ['admin', 'empleado']],
        'Listo' => ['Entregado' => ['admin', 'empleado']],
    ];

    public function __construct(private PedidosPanelRepositorio $repositorio, private PDO $conexion)
    {
        require_once \piccolo_path('src/Services/estado_pago_helpers.php');
    }

    /** @return array<int, array<string, mixed>> pedidos en preparación o en camino, del más viejo al más nuevo */
    public function cocina(): array
    {
        return array_map([$this, 'formato'], $this->repositorio->enEstados(['En preparación', 'En camino'], true));
    }

    /** @return array<int, array<string, mixed>> pedidos en camino, del más nuevo al más viejo */
    public function delivery(): array
    {
        return array_map([$this, 'formato'], $this->repositorio->enEstados(['En camino'], false));
    }

    /**
     * Cambia el estado y, si se indica, el pago. Pasar a Listo o Entregado lo marca pagado;
     * cancelarlo lo marca no pagado.
     *
     * @param string|null $estado estado nuevo (o null para cambiar solo el pago)
     * @param mixed $pago "Si"/"No" (o null para no tocarlo)
     * @param string $rol rol de quien hace el cambio
     * @return array{id: int, estado: string, pagado: bool}
     */
    public function actualizar(int $id, ?string $estado, mixed $pago, string $rol): array
    {
        $estado = $estado !== null ? Datos::texto($estado) : null;
        if ($estado !== null && !in_array($estado, self::ESTADOS, true)) {
            throw ApiException::validacion(['estado' => 'El estado tiene que ser uno de: ' . implode(', ', self::ESTADOS) . '.']);
        }
        $valorPago = null;
        if ($pago !== null && $pago !== '') {
            $valorPago = \piccolo_resolver_valor_pago($this->conexion, Datos::texto($pago));
            if ($valorPago === null) {
                throw ApiException::validacion(['pago' => 'El pago tiene que ser "Si" o "No".']);
            }
        }
        if ($estado === null && $valorPago === null) {
            throw ApiException::validacion([], 'Indicá el estado nuevo o el pago.');
        }

        return $this->repositorio->enTransaccion(function () use ($id, $estado, $valorPago, $rol): array {
            $pedido = $this->repositorio->bloquear($id);
            if ($pedido === null) {
                throw ApiException::noEncontrado('El pedido no existe.');
            }

            $actual = (string) $pedido['estado'];
            if ($estado !== null && $estado !== $actual) {
                $this->validarCambio($pedido, $estado, $rol);
                if (in_array($estado, ['Listo', 'Entregado'], true)) {
                    $valorPago = \piccolo_resolver_valor_pago($this->conexion, 'Si') ?? $valorPago;
                } elseif ($estado === 'Cancelado') {
                    $valorPago = \piccolo_resolver_valor_pago($this->conexion, 'No') ?? $valorPago;
                }
            } else {
                $estado = null; // mismo estado: no cambia nada
            }
            if ($valorPago !== null && $estado === null && in_array($actual, ['Cancelado'], true)) {
                throw new ApiException('Un pedido cancelado no se puede marcar como pagado.', 409);
            }
            if ($valorPago !== null && $estado === null && $rol === 'delivery' && $actual !== 'En camino') {
                throw new ApiException('El delivery solo puede cambiar el pago de los pedidos que lleva.', 403);
            }

            $this->repositorio->actualizar($id, $estado, $valorPago);
            if (in_array($estado, ['Listo', 'Entregado'], true) && !(int) $pedido['stock_descontado']) {
                $this->repositorio->descontarStock($id);
            }
            if ($estado === 'Cancelado') {
                $this->repositorio->revertirPuntos($pedido);
            }

            $final = (array) $this->repositorio->bloquear($id);

            return ['id' => $id, 'estado' => (string) $final['estado'], 'pagado' => self::pagado($final['esta_pago'] ?? '')];
        });
    }

    private function validarCambio(array $pedido, string $nuevo, string $rol): void
    {
        $actual = (string) $pedido['estado'];
        $roles = self::CAMBIOS[$actual][$nuevo] ?? null;
        if ($roles === null) {
            throw new ApiException("Un pedido \"$actual\" no puede pasar a \"$nuevo\".", 409);
        }
        if ($nuevo === 'En camino' && $pedido['tipo_entrega'] !== 'Delivery') {
            throw new ApiException('Solo los pedidos con delivery pueden salir "En camino".', 409);
        }
        if (!in_array($rol, $roles, true)) {
            throw new ApiException(match (true) {
                $actual === 'En camino' && $nuevo === 'Listo' => 'Solo el delivery marca como entregado un pedido en camino.',
                $rol === 'delivery' => 'El delivery solo puede marcar como entregados los pedidos que lleva.',
                default => 'Tu usuario no puede hacer ese cambio.',
            }, 403);
        }
    }

    /** @return array<string, mixed> */
    private function formato(array $fila): array
    {
        return [
            'id' => (int) $fila['ID'],
            'fecha' => (string) $fila['fecha'],
            'estado' => (string) $fila['estado'],
            'nombre' => (string) $fila['nombre'],
            'telefono' => (string) $fila['telefono'],
            'tipo_entrega' => (string) $fila['tipo_entrega'],
            'metodo_pago' => (string) $fila['metodo_pago'],
            'pagado' => self::pagado($fila['esta_pago'] ?? ''),
            'direccion' => $fila['direccion'] !== null && $fila['direccion'] !== '' ? (string) $fila['direccion'] : null,
            'referencias' => $fila['referencias'] !== null && $fila['referencias'] !== '' ? (string) $fila['referencias'] : null,
            'nota' => $fila['nota'] !== null && $fila['nota'] !== '' ? (string) $fila['nota'] : null,
            'total' => (float) $fila['total'],
            'productos' => $fila['productos'],
        ];
    }

    private static function pagado(mixed $valor): bool
    {
        return \piccolo_interpretar_estado_pago_para_presentacion($valor)['es_pagado'];
    }
}
