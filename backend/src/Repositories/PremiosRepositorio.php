<?php

declare(strict_types=1);

namespace Piccolo\Repositories;

use PDO;

/**
 * Consultas a la base de los premios que se canjean por puntos.
 */
final class PremiosRepositorio
{
    public function __construct(private PDO $conexion)
    {
    }

    /** @return array<int, array<string, mixed>> premios, del más nuevo al más viejo */
    public function listar(): array
    {
        return $this->conexion->query('SELECT id, nombre, descripcion, costo_puntos, created_at FROM premios ORDER BY created_at DESC, id DESC')
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return array<string, mixed>|null */
    public function buscar(int $id): ?array
    {
        $stmt = $this->conexion->prepare('SELECT id, nombre, descripcion, costo_puntos, created_at FROM premios WHERE id = ?');
        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** @param array{nombre: string, descripcion: ?string, costo_puntos: int} $datos */
    public function crear(array $datos): int
    {
        $this->conexion->prepare('INSERT INTO premios (nombre, descripcion, costo_puntos) VALUES (?, ?, ?)')
            ->execute([$datos['nombre'], $datos['descripcion'], $datos['costo_puntos']]);

        return (int) $this->conexion->lastInsertId();
    }

    /** @param array{nombre: string, descripcion: ?string, costo_puntos: int} $datos */
    public function actualizar(int $id, array $datos): void
    {
        $this->conexion->prepare('UPDATE premios SET nombre = ?, descripcion = ?, costo_puntos = ? WHERE id = ?')
            ->execute([$datos['nombre'], $datos['descripcion'], $datos['costo_puntos'], $id]);
    }

    /** Los canjes ya hechos quedan en el historial de cada cliente: guardan el nombre del premio, no el id. */
    public function eliminar(int $id): void
    {
        $this->conexion->prepare('DELETE FROM premios WHERE id = ?')->execute([$id]);
    }
}
