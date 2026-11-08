<?php

declare(strict_types=1);

namespace Piccolo\Repositories;

use PDO;
use Throwable;

/**
 * Consultas para el canje de puntos desde el panel: productos y premios que se pueden elegir,
 * y el canje de premios (descuenta los puntos y lo anota en el historial, todo junto).
 */
final class CanjeRepositorio
{
    public function __construct(private PDO $conexion)
    {
    }

    /** @return array<int, array<string, mixed>> productos visibles del menú, por categoría y nombre */
    public function productos(): array
    {
        return $this->conexion->query(
            'SELECT ID, nombre, precio, categoria, foto FROM tbl_menu WHERE visible_en_menu = 1 ORDER BY categoria, nombre'
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return array<int, array<string, mixed>> premios, por nombre */
    public function premios(): array
    {
        $imagen = \piccolo_columna_existe($this->conexion, 'premios', 'imagen') ? ', imagen' : ', NULL AS imagen';

        return $this->conexion->query("SELECT id, nombre, descripcion, costo_puntos$imagen FROM premios ORDER BY nombre")
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param int[] $ids
     * @return array<int, array<string, mixed>> premios pedidos, por id
     */
    public function premiosPorId(array $ids): array
    {
        if (!$ids) {
            return [];
        }
        $marcadores = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->conexion->prepare("SELECT id, nombre, costo_puntos FROM premios WHERE id IN ($marcadores)");
        $stmt->execute(array_values($ids));

        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), null, 'id');
    }

    /**
     * Descuenta los puntos de un canje de premios y lo anota en el historial.
     * El saldo se lee bloqueando la fila: si no alcanza (por ejemplo, porque otro canje se hizo al mismo
     * tiempo), no se descuenta nada y devuelve null.
     *
     * @return int|null saldo que le queda al cliente, o null si no le alcanzan los puntos
     */
    public function canjearPremios(int $clienteId, int $puntos, string $descripcion): ?int
    {
        $this->conexion->beginTransaction();
        try {
            $stmt = $this->conexion->prepare('SELECT COALESCE(puntos, 0) FROM tbl_clientes WHERE ID = ? FOR UPDATE');
            $stmt->execute([$clienteId]);
            $saldo = (int) $stmt->fetchColumn() - $puntos;
            if ($saldo < 0) {
                $this->conexion->rollBack();
                return null;
            }

            $this->conexion->prepare('UPDATE tbl_clientes SET puntos = ? WHERE ID = ?')->execute([$saldo, $clienteId]);
            $this->conexion->prepare('INSERT INTO movimientos_puntos (cliente_id, tipo, descripcion, puntos, saldo_resultante) VALUES (?, ?, ?, ?, ?)')
                ->execute([$clienteId, 'canje', $descripcion, -$puntos, $saldo]);
            $this->conexion->commit();

            return $saldo;
        } catch (Throwable $error) {
            $this->conexion->rollBack();
            throw $error;
        }
    }
}
