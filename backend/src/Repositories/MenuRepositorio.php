<?php

declare(strict_types=1);

namespace Piccolo\Repositories;

use PDO;
use Throwable;

/**
 * Consultas a la base de los productos del menú y de su receta (qué materias primas usa
 * cada producto y cuánto de cada una).
 */
final class MenuRepositorio
{
    public function __construct(private PDO $conexion)
    {
    }

    /** @return array<int, array<string, mixed>> productos con la cantidad de insumos de su receta */
    public function listar(): array
    {
        return $this->conexion->query(
            'SELECT m.ID, m.nombre, m.ingredientes, m.foto, m.precio, m.categoria, m.visible_en_menu,
                    (SELECT COUNT(*) FROM tbl_menu_materias_primas r WHERE r.menu_id = m.ID) AS insumos
             FROM tbl_menu m
             ORDER BY m.categoria, m.nombre'
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return array<string, mixed>|null */
    public function buscar(int $id): ?array
    {
        $stmt = $this->conexion->prepare(
            'SELECT m.ID, m.nombre, m.ingredientes, m.foto, m.precio, m.categoria, m.visible_en_menu,
                    (SELECT COUNT(*) FROM tbl_menu_materias_primas r WHERE r.menu_id = m.ID) AS insumos
             FROM tbl_menu m WHERE m.ID = ?'
        );
        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** @param array{nombre: string, ingredientes: string, precio: float, categoria: string, visible: bool, foto: string} $datos */
    public function crear(array $datos): int
    {
        $this->conexion->prepare(
            'INSERT INTO tbl_menu (nombre, ingredientes, foto, precio, categoria, visible_en_menu) VALUES (?, ?, ?, ?, ?, ?)'
        )->execute([$datos['nombre'], $datos['ingredientes'], $datos['foto'], self::precio($datos['precio']), $datos['categoria'], (int) $datos['visible']]);

        return (int) $this->conexion->lastInsertId();
    }

    /** @param array{nombre: string, ingredientes: string, precio: float, categoria: string, visible: bool, foto: string} $datos */
    public function actualizar(int $id, array $datos): void
    {
        $this->conexion->prepare(
            'UPDATE tbl_menu SET nombre = ?, ingredientes = ?, foto = ?, precio = ?, categoria = ?, visible_en_menu = ? WHERE ID = ?'
        )->execute([$datos['nombre'], $datos['ingredientes'], $datos['foto'], self::precio($datos['precio']), $datos['categoria'], (int) $datos['visible'], $id]);
    }

    public function eliminar(int $id): void
    {
        // La receta se borra sola (la tabla tiene ON DELETE CASCADE)
        $this->conexion->prepare('DELETE FROM tbl_menu WHERE ID = ?')->execute([$id]);
    }

    /** Cuántas veces se vendió el producto (líneas de pedidos). */
    public function vecesVendido(int $id): int
    {
        $stmt = $this->conexion->prepare('SELECT COUNT(*) FROM tbl_pedidos_detalle WHERE producto_id = ?');
        $stmt->execute([$id]);

        return (int) $stmt->fetchColumn();
    }

    /** @return array<int, array<string, mixed>> la receta: cada materia prima con lo que usa una unidad vendida y su stock */
    public function receta(int $menuId): array
    {
        $stmt = $this->conexion->prepare(
            'SELECT mp.ID AS materia_prima_id, mp.nombre, mp.unidad_medida, mp.cantidad AS stock, r.cantidad
             FROM tbl_menu_materias_primas r
             JOIN tbl_materias_primas mp ON mp.ID = r.materia_prima_id
             WHERE r.menu_id = ?
             ORDER BY mp.nombre'
        );
        $stmt->execute([$menuId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Reemplaza la receta completa por la indicada.
     *
     * @param array<int, float> $cantidades materia_prima_id => cantidad por unidad vendida
     */
    public function guardarReceta(int $menuId, array $cantidades): void
    {
        $this->conexion->beginTransaction();
        try {
            $this->conexion->prepare('DELETE FROM tbl_menu_materias_primas WHERE menu_id = ?')->execute([$menuId]);
            $insertar = $this->conexion->prepare('INSERT INTO tbl_menu_materias_primas (menu_id, materia_prima_id, cantidad) VALUES (?, ?, ?)');
            foreach ($cantidades as $materiaId => $cantidad) {
                $insertar->execute([$menuId, $materiaId, $cantidad]);
            }
            $this->conexion->commit();
        } catch (Throwable $error) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            throw $error;
        }
    }

    /** El precio se guarda como texto en la base; siempre con punto decimal y sin separador de miles. */
    private static function precio(float $precio): string
    {
        return number_format($precio, 2, '.', '');
    }
}
