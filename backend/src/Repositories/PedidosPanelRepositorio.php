<?php

declare(strict_types=1);

namespace Piccolo\Repositories;

use PDO;

/**
 * Consultas a la base de los pedidos que siguen cocina y delivery: los que están en curso,
 * el cambio de estado y de pago, el descuento de insumos y la devolución de puntos al cancelar.
 */
final class PedidosPanelRepositorio
{
    public function __construct(private PDO $conexion)
    {
    }

    /**
     * Pedidos en esos estados, cada uno con sus productos.
     *
     * @param string[] $estados
     * @return array<int, array<string, mixed>>
     */
    public function enEstados(array $estados, bool $masViejosPrimero): array
    {
        $marcadores = implode(',', array_fill(0, count($estados), '?'));
        $stmt = $this->conexion->prepare(
            "SELECT * FROM tbl_pedidos WHERE estado IN ($marcadores) ORDER BY fecha " . ($masViejosPrimero ? 'ASC' : 'DESC') . ', ID'
        );
        $stmt->execute(array_values($estados));
        $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!$pedidos) {
            return [];
        }

        // Los productos de todos los pedidos en una sola consulta
        $ids = array_map('intval', array_column($pedidos, 'ID'));
        $stmt = $this->conexion->prepare(
            'SELECT pedido_id, nombre, cantidad FROM tbl_pedidos_detalle WHERE pedido_id IN (' . implode(',', array_fill(0, count($ids), '?')) . ') ORDER BY ID'
        );
        $stmt->execute($ids);
        $porPedido = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            $porPedido[(int) $fila['pedido_id']][] = ['nombre' => $fila['nombre'], 'cantidad' => (int) $fila['cantidad']];
        }
        foreach ($pedidos as &$pedido) {
            $pedido['productos'] = $porPedido[(int) $pedido['ID']] ?? [];
        }
        unset($pedido);

        return $pedidos;
    }

    /** @return array<string, mixed>|null el pedido, bloqueado hasta terminar la transacción */
    public function bloquear(int $id): ?array
    {
        $stmt = $this->conexion->prepare('SELECT * FROM tbl_pedidos WHERE ID = ? FOR UPDATE');
        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function actualizar(int $id, ?string $estado, ?string $pago): void
    {
        $cambios = [];
        $valores = [];
        if ($estado !== null) {
            $cambios[] = 'estado = ?';
            $valores[] = $estado;
        }
        if ($pago !== null) {
            $cambios[] = 'esta_pago = ?';
            $valores[] = $pago;
        }
        if ($cambios) {
            $this->conexion->prepare('UPDATE tbl_pedidos SET ' . implode(', ', $cambios) . ' WHERE ID = ?')->execute([...$valores, $id]);
        }
    }

    /** Descuenta del stock los insumos de la receta de cada producto del pedido (sin bajar de cero) y lo marca. */
    public function descontarStock(int $id): void
    {
        $this->conexion->prepare(
            'UPDATE tbl_materias_primas mp
             JOIN (
                 SELECT r.materia_prima_id, SUM(r.cantidad * d.cantidad) AS consumo
                 FROM tbl_pedidos_detalle d
                 JOIN tbl_menu_materias_primas r ON r.menu_id = d.producto_id
                 WHERE d.pedido_id = ?
                 GROUP BY r.materia_prima_id
             ) uso ON uso.materia_prima_id = mp.ID
             SET mp.cantidad = GREATEST(0, mp.cantidad - uso.consumo)'
        )->execute([$id]);
        $this->conexion->prepare('UPDATE tbl_pedidos SET stock_descontado = 1 WHERE ID = ?')->execute([$id]);
    }

    /**
     * Devuelve al cliente los puntos que usó en el pedido y le quita los que ganó, y lo anota en su historial.
     * El saldo no baja de cero (por si ya gastó los puntos ganados).
     */
    public function revertirPuntos(array $pedido): void
    {
        $clienteId = (int) ($pedido['cliente_id'] ?? 0);
        $diferencia = (int) $pedido['puntos_usados'] - (int) $pedido['puntos_ganados'];
        if ($clienteId <= 0 || ((int) $pedido['puntos_usados'] === 0 && (int) $pedido['puntos_ganados'] === 0)) {
            return;
        }

        $stmt = $this->conexion->prepare('SELECT COALESCE(puntos, 0) FROM tbl_clientes WHERE ID = ? FOR UPDATE');
        $stmt->execute([$clienteId]);
        $saldoAnterior = $stmt->fetchColumn();
        if ($saldoAnterior === false) {
            return; // el cliente ya no existe
        }
        $saldo = max(0, (int) $saldoAnterior + $diferencia);
        $this->conexion->prepare('UPDATE tbl_clientes SET puntos = ? WHERE ID = ?')->execute([$saldo, $clienteId]);
        $this->conexion->prepare('INSERT INTO movimientos_puntos (cliente_id, tipo, descripcion, puntos, saldo_resultante) VALUES (?, ?, ?, ?, ?)')
            ->execute([$clienteId, 'ajuste', "Pedido #{$pedido['ID']} cancelado: se devuelven los puntos usados y se descuentan los ganados", $saldo - (int) $saldoAnterior, $saldo]);
        $this->conexion->prepare('UPDATE tbl_pedidos SET puntos_usados = 0, puntos_ganados = 0 WHERE ID = ?')->execute([(int) $pedido['ID']]);
    }

    public function enTransaccion(callable $trabajo): mixed
    {
        $this->conexion->beginTransaction();
        try {
            $resultado = $trabajo();
            $this->conexion->commit();

            return $resultado;
        } catch (\Throwable $error) {
            $this->conexion->rollBack();
            throw $error;
        }
    }
}
