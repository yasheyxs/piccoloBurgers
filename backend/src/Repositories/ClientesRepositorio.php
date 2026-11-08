<?php

declare(strict_types=1);

namespace Piccolo\Repositories;

use PDO;
use Throwable;

/**
 * Consultas a la base de los clientes registrados. Cada cambio de puntos queda anotado en
 * movimientos_puntos con el saldo que resulta, dentro de la misma transacción.
 */
final class ClientesRepositorio
{
    private const CON_ACTIVIDAD = 'SELECT c.ID, c.nombre, c.telefono, c.email, c.fecha_registro, COALESCE(c.puntos, 0) AS puntos,
               COALESCE(p.total_pedidos, 0) AS total_pedidos, p.ultimo_pedido
        FROM tbl_clientes c
        LEFT JOIN (
            SELECT cliente_id, COUNT(*) AS total_pedidos, MAX(fecha) AS ultimo_pedido
            FROM tbl_pedidos GROUP BY cliente_id
        ) p ON p.cliente_id = c.ID';

    public function __construct(private PDO $conexion)
    {
    }

    /** @return array<int, array<string, mixed>> clientes con su cantidad de pedidos y el último, del más nuevo al más viejo */
    public function listar(): array
    {
        return $this->conexion->query(self::CON_ACTIVIDAD . ' ORDER BY c.fecha_registro DESC, c.ID DESC')->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return array<string, mixed>|null */
    public function buscar(int $id): ?array
    {
        $stmt = $this->conexion->prepare(self::CON_ACTIVIDAD . ' WHERE c.ID = ?');
        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** ¿Hay otro cliente (distinto de $exceptoId) con ese valor en la columna? */
    public function enUso(string $columna, string $valor, int $exceptoId = 0): bool
    {
        $columna = $columna === 'email' ? 'email' : 'telefono';
        $stmt = $this->conexion->prepare("SELECT COUNT(*) FROM tbl_clientes WHERE $columna = ? AND ID <> ?");
        $stmt->execute([$valor, $exceptoId]);

        return (int) $stmt->fetchColumn() > 0;
    }

    /** @param array{nombre: string, telefono: string, email: string, puntos: int} $datos */
    public function crear(array $datos, string $hashPassword): int
    {
        return $this->enTransaccion(function () use ($datos, $hashPassword): int {
            $this->conexion->prepare('INSERT INTO tbl_clientes (nombre, telefono, email, password, puntos) VALUES (?, ?, ?, ?, ?)')
                ->execute([$datos['nombre'], $datos['telefono'], $datos['email'], $hashPassword, $datos['puntos']]);
            $id = (int) $this->conexion->lastInsertId();
            if ($datos['puntos'] > 0) {
                $this->anotarMovimiento($id, $datos['puntos'], $datos['puntos'], 'Puntos iniciales al crear el cliente desde el panel');
            }

            return $id;
        });
    }

    /**
     * Guarda los datos y, si cambió el saldo de puntos, anota la diferencia como ajuste.
     * El saldo anterior se lee bloqueando la fila, así no se pisa un pedido que sume puntos al mismo tiempo.
     *
     * @param array{nombre: string, telefono: string, email: string, puntos: int} $datos
     */
    public function actualizar(int $id, array $datos): void
    {
        $this->enTransaccion(function () use ($id, $datos): void {
            $stmt = $this->conexion->prepare('SELECT COALESCE(puntos, 0) FROM tbl_clientes WHERE ID = ? FOR UPDATE');
            $stmt->execute([$id]);
            $anteriores = (int) $stmt->fetchColumn();

            $this->conexion->prepare('UPDATE tbl_clientes SET nombre = ?, telefono = ?, email = ?, puntos = ? WHERE ID = ?')
                ->execute([$datos['nombre'], $datos['telefono'], $datos['email'], $datos['puntos'], $id]);

            $diferencia = $datos['puntos'] - $anteriores;
            if ($diferencia !== 0) {
                $detalle = $diferencia > 0 ? 'Ajuste manual: suma de puntos desde el panel' : 'Ajuste manual: descuento de puntos desde el panel';
                $this->anotarMovimiento($id, $diferencia, $datos['puntos'], $detalle);
            }
        });
    }

    /** Borra el cliente y sus movimientos de puntos. Sus pedidos quedan en el historial, sin cliente asociado. */
    public function eliminar(int $id): void
    {
        $this->enTransaccion(function () use ($id): void {
            $this->conexion->prepare('UPDATE tbl_pedidos SET cliente_id = NULL WHERE cliente_id = ?')->execute([$id]);
            $this->conexion->prepare('DELETE FROM movimientos_puntos WHERE cliente_id = ?')->execute([$id]);
            $this->conexion->prepare('DELETE FROM tbl_clientes WHERE ID = ?')->execute([$id]);
        });
    }

    /** @return array<int, array<string, mixed>> pedidos del cliente, del más nuevo al más viejo */
    public function pedidos(int $id): array
    {
        $stmt = $this->conexion->prepare(
            'SELECT ID, total, fecha, metodo_pago, tipo_entrega, estado FROM tbl_pedidos WHERE cliente_id = ? ORDER BY fecha DESC, ID DESC'
        );
        $stmt->execute([$id]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return array<int, array<string, mixed>> movimientos de puntos, del más nuevo al más viejo */
    public function movimientos(int $id): array
    {
        $stmt = $this->conexion->prepare(
            'SELECT created_at, tipo, descripcion, puntos, saldo_resultante FROM movimientos_puntos WHERE cliente_id = ? ORDER BY created_at DESC, id DESC'
        );
        $stmt->execute([$id]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function anotarMovimiento(int $clienteId, int $puntos, int $saldo, string $descripcion): void
    {
        $this->conexion->prepare('INSERT INTO movimientos_puntos (cliente_id, tipo, descripcion, puntos, saldo_resultante) VALUES (?, ?, ?, ?, ?)')
            ->execute([$clienteId, 'ajuste', $descripcion, $puntos, $saldo]);
    }

    /**
     * @template T
     * @param callable(): T $trabajo
     * @return T
     */
    private function enTransaccion(callable $trabajo): mixed
    {
        $this->conexion->beginTransaction();
        try {
            $resultado = $trabajo();
            $this->conexion->commit();

            return $resultado;
        } catch (Throwable $error) {
            $this->conexion->rollBack();
            throw $error;
        }
    }
}
