<?php

declare(strict_types=1);

namespace Piccolo\Repositories;

use PDO;
use Throwable;

/**
 * Consultas a la base de las compras a proveedores. Registrar o borrar una compra también
 * actualiza el stock de las materias primas, todo dentro de una misma transacción.
 */
final class ComprasRepositorio
{
    public function __construct(private PDO $conexion)
    {
    }

    /** @return array<int, array<string, mixed>> compras con su proveedor y total, de la más nueva a la más vieja */
    public function listar(): array
    {
        return $this->conexion->query(
            'SELECT c.ID, c.fecha, c.proveedor_id, p.nombre AS proveedor, p.telefono,
                    COALESCE((SELECT SUM(cd.cantidad * cd.precio_unitario) FROM tbl_compras_detalle cd WHERE cd.compra_id = c.ID), 0) AS total
             FROM tbl_compras c
             LEFT JOIN tbl_proveedores p ON c.proveedor_id = p.ID
             ORDER BY c.fecha DESC, c.ID DESC'
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return array<string, mixed>|null */
    public function buscar(int $id): ?array
    {
        $stmt = $this->conexion->prepare(
            'SELECT c.ID, c.fecha, c.proveedor_id, p.nombre AS proveedor, p.telefono
             FROM tbl_compras c LEFT JOIN tbl_proveedores p ON c.proveedor_id = p.ID
             WHERE c.ID = ?'
        );
        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** @return array<int, array<string, mixed>> */
    public function detalle(int $compraId): array
    {
        $stmt = $this->conexion->prepare(
            'SELECT cd.materia_prima_id, mp.nombre AS materia_prima, mp.unidad_medida, cd.cantidad, cd.precio_unitario
             FROM tbl_compras_detalle cd
             LEFT JOIN tbl_materias_primas mp ON cd.materia_prima_id = mp.ID
             WHERE cd.compra_id = ?
             ORDER BY cd.ID'
        );
        $stmt->execute([$compraId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Guarda la compra y suma lo comprado al stock.
     *
     * @param array<int, array{materia_prima_id: int, cantidad: float, precio_unitario: float}> $items
     */
    public function registrar(string $fecha, int $proveedorId, array $items): int
    {
        return $this->enTransaccion(function () use ($fecha, $proveedorId, $items): int {
            $this->conexion->prepare('INSERT INTO tbl_compras (fecha, proveedor_id) VALUES (?, ?)')->execute([$fecha, $proveedorId]);
            $compraId = (int) $this->conexion->lastInsertId();

            $detalle = $this->conexion->prepare('INSERT INTO tbl_compras_detalle (compra_id, materia_prima_id, cantidad, precio_unitario) VALUES (?, ?, ?, ?)');
            $stock = $this->conexion->prepare('UPDATE tbl_materias_primas SET cantidad = cantidad + ? WHERE ID = ?');
            foreach ($items as $item) {
                $detalle->execute([$compraId, $item['materia_prima_id'], $item['cantidad'], $item['precio_unitario']]);
                $stock->execute([$item['cantidad'], $item['materia_prima_id']]);
            }

            return $compraId;
        });
    }

    /**
     * Stock actual de cada materia prima de la compra, comparado con lo que se compró
     * (para saber si se puede deshacer la compra sin dejar stock negativo).
     *
     * @return array<int, array{materia_prima: string, comprado: float, stock: float}>
     */
    public function stockParaDeshacer(int $compraId): array
    {
        $stmt = $this->conexion->prepare(
            'SELECT mp.nombre AS materia_prima, SUM(cd.cantidad) AS comprado, mp.cantidad AS stock
             FROM tbl_compras_detalle cd
             JOIN tbl_materias_primas mp ON cd.materia_prima_id = mp.ID
             WHERE cd.compra_id = ?
             GROUP BY mp.ID, mp.nombre, mp.cantidad'
        );
        $stmt->execute([$compraId]);

        return array_map(static fn(array $f): array => [
            'materia_prima' => (string) $f['materia_prima'],
            'comprado' => (float) $f['comprado'],
            'stock' => (float) $f['stock'],
        ], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /** Borra la compra y descuenta del stock lo que esa compra había sumado. */
    public function eliminar(int $compraId): void
    {
        $this->enTransaccion(function () use ($compraId): void {
            $this->conexion->prepare(
                'UPDATE tbl_materias_primas mp
                 JOIN (SELECT materia_prima_id, SUM(cantidad) AS comprado FROM tbl_compras_detalle WHERE compra_id = ? GROUP BY materia_prima_id) cd
                   ON cd.materia_prima_id = mp.ID
                 SET mp.cantidad = mp.cantidad - cd.comprado'
            )->execute([$compraId]);
            $this->conexion->prepare('DELETE FROM tbl_compras_detalle WHERE compra_id = ?')->execute([$compraId]);
            $this->conexion->prepare('DELETE FROM tbl_compras WHERE ID = ?')->execute([$compraId]);
        });
    }

    /**
     * Gasto del mes y del año en curso, y los proveedores y materias primas con más compras.
     *
     * @return array{gasto_mes: float, gasto_anio: float, proveedores: array<int, array{nombre: string, total: float}>, materias_primas: array<int, array{nombre: string, cantidad: float}>}
     */
    public function resumen(int $limite = 5): array
    {
        $gasto = function (string $condicion): float {
            return (float) $this->conexion->query(
                "SELECT COALESCE(SUM(cd.cantidad * cd.precio_unitario), 0)
                 FROM tbl_compras c JOIN tbl_compras_detalle cd ON c.ID = cd.compra_id
                 WHERE $condicion"
            )->fetchColumn();
        };

        $proveedores = $this->conexion->query(
            "SELECT p.nombre, SUM(cd.cantidad * cd.precio_unitario) AS total
             FROM tbl_compras c
             JOIN tbl_proveedores p ON c.proveedor_id = p.ID
             JOIN tbl_compras_detalle cd ON c.ID = cd.compra_id
             GROUP BY p.ID, p.nombre ORDER BY total DESC LIMIT $limite"
        )->fetchAll(PDO::FETCH_ASSOC);

        $materias = $this->conexion->query(
            "SELECT mp.nombre, SUM(cd.cantidad) AS cantidad
             FROM tbl_compras_detalle cd
             JOIN tbl_materias_primas mp ON cd.materia_prima_id = mp.ID
             GROUP BY mp.ID, mp.nombre ORDER BY cantidad DESC LIMIT $limite"
        )->fetchAll(PDO::FETCH_ASSOC);

        return [
            'gasto_mes' => $gasto('YEAR(c.fecha) = YEAR(CURDATE()) AND MONTH(c.fecha) = MONTH(CURDATE())'),
            'gasto_anio' => $gasto('YEAR(c.fecha) = YEAR(CURDATE())'),
            'proveedores' => array_map(static fn(array $f): array => ['nombre' => (string) $f['nombre'], 'total' => (float) $f['total']], $proveedores),
            'materias_primas' => array_map(static fn(array $f): array => ['nombre' => (string) $f['nombre'], 'cantidad' => (float) $f['cantidad']], $materias),
        ];
    }

    /**
     * @template T
     * @param callable(): T $accion
     * @return T
     */
    private function enTransaccion(callable $accion): mixed
    {
        $this->conexion->beginTransaction();
        try {
            $resultado = $accion();
            $this->conexion->commit();
            return $resultado;
        } catch (Throwable $error) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            throw $error;
        }
    }
}
