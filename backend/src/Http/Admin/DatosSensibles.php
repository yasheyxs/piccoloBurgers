<?php

declare(strict_types=1);

namespace Piccolo\Http\Admin;

use PDO;
use Piccolo\Http\Api\ApiException;

/**
 * Confirmación con la contraseña propia antes de mostrar montos en pantallas que suelen estar
 * a la vista del público (pool y bebidas). Después de varios intentos fallidos se bloquea unos
 * minutos, igual que el ingreso al panel.
 */
final class DatosSensibles
{
    /** @throws ApiException 422 si la contraseña no coincide, 429 si hubo demasiados intentos */
    public static function confirmar(PDO $conexion, int $usuarioId, string $password): void
    {
        require_once \piccolo_path('src/Services/Security/password_utils.php');
        require_once \piccolo_path('src/Services/Security/limite_intentos.php');

        $clave = (string) $usuarioId;
        $minutos = \piccoloLoginMinutosBloqueado($conexion, 'privacidad', $clave);
        if ($minutos > 0) {
            throw new ApiException("Demasiados intentos. Probá de nuevo en $minutos minuto" . ($minutos === 1 ? '' : 's') . '.', 429);
        }
        if ($password === '') {
            throw ApiException::validacion(['password' => 'Ingresá tu contraseña para continuar.']);
        }

        $stmt = $conexion->prepare('SELECT password FROM tbl_usuarios WHERE ID = ?');
        $stmt->execute([$usuarioId]);
        $hash = (string) ($stmt->fetchColumn() ?: '');
        if ($hash === '' || !\passwordCoincideConHash($password, $hash)) {
            \piccoloLoginRegistrarFallo($conexion, 'privacidad', $clave);
            throw ApiException::validacion(['password' => 'La contraseña no es correcta.']);
        }
        \piccoloLoginRegistrarExito($conexion, 'privacidad', $clave);
    }
}
