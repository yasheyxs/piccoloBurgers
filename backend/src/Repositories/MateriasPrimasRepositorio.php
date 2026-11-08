<?php

declare(strict_types=1);

namespace Piccolo\Repositories;

use PDO;

/**
 * Consultas a la base de las materias primas (los insumos del local y su stock).
 */
final class MateriasPrimasRepositorio
{
    private const COLUMNAS = 'mp.ID, mp.nombre, mp.unidad_medida, mp.cantidad, mp.stock_minimo, mp.proveedor_id, p.nombre AS proveedor';

    public function __construct(private PDO $conexion)
    {
    }

    /**
     * @param bool $soloCriticos solo las que están en el stock mínimo o por debajo (sin mínimo cargado, se toma 1)
     * @return array<int, array<string, mixed>>
     */
    public function listar(bool $soloCriticos = false): array
    {
        $sql = 'SELECT ' . self::COLUMNAS . ' FROM tbl_materias_primas mp LEFT JOIN tbl_proveedores p ON mp.proveedor_id = p.ID';
        if ($soloCriticos) {
            $sql .= ' WHERE mp.cantidad <= IFNULL(mp.stock_minimo, 1)';
        }

        return $this->conexion->query($sql . ' ORDER BY mp.nombre')->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return array<string, mixed>|null */
    public function buscar(int $id): ?array
    {
        $stmt = $this->conexion->prepare('SELECT ' . self::COLUMNAS . ' FROM tbl_materias_primas mp LEFT JOIN tbl_proveedores p ON mp.proveedor_id = p.ID WHERE mp.ID = ?');
        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** @param int[] $ids @return int[] los IDs que existen */
    public function existentes(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if (!$ids) {
            return [];
        }
        $marcadores = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->conexion->prepare("SELECT ID FROM tbl_materias_primas WHERE ID IN ($marcadores)");
        $stmt->execute($ids);

        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    /** @param array{nombre: string, unidad_medida: string, cantidad: float, stock_minimo: ?float, proveedor_id: int} $datos */
    public function crear(array $datos): int
    {
        $this->conexion->prepare(
            'INSERT INTO tbl_materias_primas (nombre, unidad_medida, cantidad, stock_minimo, proveedor_id) VALUES (?, ?, ?, ?, ?)'
        )->execute([$datos['nombre'], $datos['unidad_medida'], $datos['cantidad'], $datos['stock_minimo'], $datos['proveedor_id']]);

        return (int) $this->conexion->lastInsertId();
    }

    /** @param array{nombre: string, unidad_medida: string, cantidad: float, stock_minimo: ?float, proveedor_id: int} $datos */
    public function actualizar(int $id, array $datos): void
    {
        $this->conexion->prepare(
            'UPDATE tbl_materias_primas SET nombre = ?, unidad_medida = ?, cantidad = ?, stock_minimo = ?, proveedor_id = ? WHERE ID = ?'
        )->execute([$datos['nombre'], $datos['unidad_medida'], $datos['cantidad'], $datos['stock_minimo'], $datos['proveedor_id'], $id]);
    }

    public function eliminar(int $id): void
    {
        $this->conexion->prepare('DELETE FROM tbl_materias_primas WHERE ID = ?')->execute([$id]);
    }

    /**
     * En cuántos productos del menú (recetas) y en cuántas compras se usa.
     *
     * @return array{productos: int, compras: int}
     */
    public function usos(int $id): array
    {
        $contar = function (string $sql) use ($id): int {
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute([$id]);
            return (int) $stmt->fetchColumn();
        };

        return [
            'productos' => $contar('SELECT COUNT(DISTINCT menu_id) FROM tbl_menu_materias_primas WHERE materia_prima_id = ?'),
            'compras' => $contar('SELECT COUNT(DISTINCT compra_id) FROM tbl_compras_detalle WHERE materia_prima_id = ?'),
        ];
    }
}
