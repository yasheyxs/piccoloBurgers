<?php

declare(strict_types=1);

namespace Piccolo\Repositories;

use PDO;

/**
 * Consultas a la base del contenido que se ve en el sitio: banners, testimonios y los
 * comentarios que dejan los visitantes desde el formulario de contacto.
 */
final class ContenidoRepositorio
{
    public function __construct(private PDO $conexion)
    {
    }

    /** @return array<int, array<string, mixed>> banners, del más nuevo al más viejo (el sitio muestra el primero) */
    public function banners(): array
    {
        return $this->conexion->query('SELECT * FROM tbl_banners ORDER BY ID DESC')->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return array<string, mixed>|null */
    public function banner(int $id): ?array
    {
        $stmt = $this->conexion->prepare('SELECT * FROM tbl_banners WHERE ID = ?');
        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** @param array{titulo: string, descripcion: string, imagen: string} $datos */
    public function crearBanner(array $datos): int
    {
        $this->conexion->prepare("INSERT INTO tbl_banners (titulo, descripcion, imagen, link) VALUES (?, ?, ?, '#menu')")
            ->execute([$datos['titulo'], $datos['descripcion'], $datos['imagen']]);

        return (int) $this->conexion->lastInsertId();
    }

    /** @param array{titulo: string, descripcion: string, imagen: string} $datos */
    public function actualizarBanner(int $id, array $datos): void
    {
        $this->conexion->prepare('UPDATE tbl_banners SET titulo = ?, descripcion = ?, imagen = ? WHERE ID = ?')
            ->execute([$datos['titulo'], $datos['descripcion'], $datos['imagen'], $id]);
    }

    public function eliminarBanner(int $id): void
    {
        $this->conexion->prepare('DELETE FROM tbl_banners WHERE ID = ?')->execute([$id]);
    }

    /** @return array<int, array<string, mixed>> */
    public function testimonios(): array
    {
        return $this->conexion->query('SELECT * FROM tbl_testimonios ORDER BY ID DESC')->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return array<string, mixed>|null */
    public function testimonio(int $id): ?array
    {
        $stmt = $this->conexion->prepare('SELECT * FROM tbl_testimonios WHERE ID = ?');
        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** @param array{opinion: string, nombre: string} $datos */
    public function guardarTestimonio(int $id, array $datos): int
    {
        if ($id > 0) {
            $this->conexion->prepare('UPDATE tbl_testimonios SET opinion = ?, nombre = ? WHERE ID = ?')->execute([$datos['opinion'], $datos['nombre'], $id]);

            return $id;
        }
        $this->conexion->prepare('INSERT INTO tbl_testimonios (opinion, nombre) VALUES (?, ?)')->execute([$datos['opinion'], $datos['nombre']]);

        return (int) $this->conexion->lastInsertId();
    }

    public function eliminarTestimonio(int $id): void
    {
        $this->conexion->prepare('DELETE FROM tbl_testimonios WHERE ID = ?')->execute([$id]);
    }

    /** @return array<int, array<string, mixed>> comentarios, del más nuevo al más viejo */
    public function comentarios(): array
    {
        return $this->conexion->query('SELECT * FROM tbl_comentarios ORDER BY ID DESC')->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return bool si existía */
    public function eliminarComentario(int $id): bool
    {
        $stmt = $this->conexion->prepare('DELETE FROM tbl_comentarios WHERE ID = ?');
        $stmt->execute([$id]);

        return $stmt->rowCount() > 0;
    }
}
