<?php

declare(strict_types=1);

namespace Piccolo\Repositories;

use PDO;

/**
 * Consultas a la base de los usuarios del panel (tbl_usuarios).
 */
final class UsuariosRepositorio
{
    public function __construct(private PDO $conexion)
    {
    }

    /** @return array<int, array<string, mixed>> usuarios por nombre (sin la contraseña) */
    public function listar(): array
    {
        return $this->conexion->query('SELECT ID, usuario, correo, rol FROM tbl_usuarios ORDER BY usuario')->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return array<string, mixed>|null el usuario sin la contraseña */
    public function buscar(int $id): ?array
    {
        $stmt = $this->conexion->prepare('SELECT ID, usuario, correo, rol FROM tbl_usuarios WHERE ID = ?');
        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function hashPassword(int $id): ?string
    {
        $stmt = $this->conexion->prepare('SELECT password FROM tbl_usuarios WHERE ID = ?');
        $stmt->execute([$id]);
        $hash = $stmt->fetchColumn();

        return $hash !== false ? (string) $hash : null;
    }

    /** ¿Hay otro usuario (distinto de $exceptoId) con ese valor en la columna? */
    public function enUso(string $columna, string $valor, int $exceptoId = 0): bool
    {
        $columna = $columna === 'correo' ? 'correo' : 'usuario';
        $stmt = $this->conexion->prepare("SELECT COUNT(*) FROM tbl_usuarios WHERE $columna = ? AND ID <> ?");
        $stmt->execute([$valor, $exceptoId]);

        return (int) $stmt->fetchColumn() > 0;
    }

    /** Cantidad de administradores, sin contar a $exceptoId. */
    public function administradores(int $exceptoId = 0): int
    {
        $stmt = $this->conexion->prepare("SELECT COUNT(*) FROM tbl_usuarios WHERE rol = 'admin' AND ID <> ?");
        $stmt->execute([$exceptoId]);

        return (int) $stmt->fetchColumn();
    }

    /** @param array{usuario: string, correo: string, rol: string} $datos */
    public function crear(array $datos, string $hashPassword): int
    {
        $this->conexion->prepare('INSERT INTO tbl_usuarios (usuario, correo, rol, password) VALUES (?, ?, ?, ?)')
            ->execute([$datos['usuario'], $datos['correo'], $datos['rol'], $hashPassword]);

        return (int) $this->conexion->lastInsertId();
    }

    /** @param array{usuario: string, correo: string, rol: string} $datos */
    public function actualizar(int $id, array $datos): void
    {
        $this->conexion->prepare('UPDATE tbl_usuarios SET usuario = ?, correo = ?, rol = ? WHERE ID = ?')
            ->execute([$datos['usuario'], $datos['correo'], $datos['rol'], $id]);
    }

    public function eliminar(int $id): void
    {
        $this->conexion->prepare('DELETE FROM tbl_usuarios WHERE ID = ?')->execute([$id]);
    }
}
