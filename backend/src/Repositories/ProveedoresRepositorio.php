<?php

declare(strict_types=1);

namespace Piccolo\Repositories;

use PDO;

/**
 * Consultas a la base de datos de los proveedores. No valida nada: eso lo hace el controlador.
 */
final class ProveedoresRepositorio
{
    public function __construct(private PDO $conexion)
    {
    }

    /** @return array<int, array{ID: int, nombre: string, telefono: string, email: ?string}> */
    public function listar(): array
    {
        return $this->conexion->query('SELECT ID, nombre, telefono, email FROM tbl_proveedores ORDER BY nombre')
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return array{ID: int, nombre: string, telefono: string, email: ?string}|null */
    public function buscar(int $id): ?array
    {
        $stmt = $this->conexion->prepare('SELECT ID, nombre, telefono, email FROM tbl_proveedores WHERE ID = ?');
        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** @param array{nombre: string, telefono: string, email: ?string} $datos */
    public function crear(array $datos): int
    {
        $this->conexion->prepare('INSERT INTO tbl_proveedores (nombre, telefono, email) VALUES (?, ?, ?)')
            ->execute([$datos['nombre'], $datos['telefono'], $datos['email']]);

        return (int) $this->conexion->lastInsertId();
    }

    /** @param array{nombre: string, telefono: string, email: ?string} $datos */
    public function actualizar(int $id, array $datos): void
    {
        $this->conexion->prepare('UPDATE tbl_proveedores SET nombre = ?, telefono = ?, email = ? WHERE ID = ?')
            ->execute([$datos['nombre'], $datos['telefono'], $datos['email'], $id]);
    }

    public function eliminar(int $id): void
    {
        $this->conexion->prepare('DELETE FROM tbl_proveedores WHERE ID = ?')->execute([$id]);
    }

    /**
     * Cuántas materias primas y compras usan al proveedor.
     *
     * @return array{materias_primas: int, compras: int}
     */
    public function usos(int $id): array
    {
        $contar = function (string $tabla) use ($id): int {
            $stmt = $this->conexion->prepare("SELECT COUNT(*) FROM $tabla WHERE proveedor_id = ?");
            $stmt->execute([$id]);
            return (int) $stmt->fetchColumn();
        };

        return [
            'materias_primas' => $contar('tbl_materias_primas'),
            'compras' => $contar('tbl_compras'),
        ];
    }
}
