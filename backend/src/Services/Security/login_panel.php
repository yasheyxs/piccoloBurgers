<?php

/**
 * Verificación de usuario y contraseña del panel. La usan la pantalla de login y la API
 * (POST /api/v1/admin/sesion), así las dos aplican las mismas reglas.
 */

require_once __DIR__ . '/password_utils.php';
require_once __DIR__ . '/limite_intentos.php';

if (!function_exists('piccoloPanelAutenticar')) {
    /**
     * Devuelve el usuario si los datos son correctos, o el motivo del rechazo:
     *  - 'bloqueado': demasiados intentos fallidos (incluye los minutos que faltan).
     *  - 'incorrecto': usuario o contraseña equivocados (mismo motivo en los dos casos).
     *
     * @return array{usuario: array|null, motivo: ?string, minutos: int}
     */
    function piccoloPanelAutenticar(PDO $conexion, string $usuario, string $password): array
    {
        $usuario = trim($usuario);
        $minutos = piccoloLoginMinutosBloqueado($conexion, 'panel', $usuario);
        if ($minutos > 0) {
            return ['usuario' => null, 'motivo' => 'bloqueado', 'minutos' => $minutos];
        }

        $stmt = $conexion->prepare('SELECT * FROM tbl_usuarios WHERE usuario = ?');
        $stmt->execute([$usuario]);
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$registro || $password === '' || !passwordCoincideConHash($password, (string) $registro['password'])) {
            piccoloLoginRegistrarFallo($conexion, 'panel', $usuario);
            return ['usuario' => null, 'motivo' => 'incorrecto', 'minutos' => 0];
        }

        piccoloLoginRegistrarExito($conexion, 'panel', $usuario);

        // Las contraseñas con un formato viejo se actualizan al entrar
        if (passwordDebeRehash((string) $registro['password'])) {
            try {
                $conexion->prepare('UPDATE tbl_usuarios SET password = ? WHERE ID = ?')
                    ->execute([generarHashPassword($password), $registro['ID']]);
            } catch (Throwable $error) {
                error_log('No se pudo actualizar el hash del usuario ' . $registro['ID'] . ': ' . $error->getMessage());
            }
        }

        return ['usuario' => $registro, 'motivo' => null, 'minutos' => 0];
    }
}

if (!function_exists('piccoloPanelMensajeRechazo')) {
    function piccoloPanelMensajeRechazo(array $resultado): string
    {
        if ($resultado['motivo'] === 'bloqueado') {
            $minutos = (int) $resultado['minutos'];
            return "Demasiados intentos fallidos. Probá de nuevo en {$minutos} minuto" . ($minutos === 1 ? '' : 's') . '.';
        }

        return 'Usuario o contraseña incorrectos.';
    }
}
