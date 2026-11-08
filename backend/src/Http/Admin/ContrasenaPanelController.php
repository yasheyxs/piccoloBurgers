<?php

declare(strict_types=1);

namespace Piccolo\Http\Admin;

use PDO;
use Piccolo\Http\Api\ApiException;

/**
 * Contraseñas de los usuarios del panel: cambiarla con la sesión iniciada y recuperarla por correo.
 *
 * - El enlace de recuperación dura 30 minutos y en la base se guarda solo su huella (hash): quien
 *   llegue a leer la base no puede usarlo.
 * - La respuesta al pedir el enlace es siempre la misma, exista o no el correo, para no revelar
 *   qué correos tienen usuario. Y si el correo no se puede enviar, el enlace nunca se muestra en pantalla.
 */
final class ContrasenaPanelController
{
    public const MENSAJE_ENVIADO = 'Si el correo corresponde a un usuario del panel, en unos minutos te llega un enlace para crear una contraseña nueva.';
    private const MINUTOS_ENLACE = 30;

    public function __construct(private PDO $conexion)
    {
        require_once \piccolo_path('src/Services/Security/password_utils.php');
        require_once \piccolo_path('src/Services/Security/limite_intentos.php');
    }

    /** @param array<string, mixed> $datos actual, nueva, confirmar */
    public function cambiar(int $usuarioId, array $datos): void
    {
        $actual = is_string($datos['actual'] ?? null) ? $datos['actual'] : '';
        $hash = $this->hash($usuarioId);
        if ($hash === null) {
            throw ApiException::noEncontrado('El usuario ya no existe.');
        }

        $errores = [];
        if ($actual === '' || !\passwordCoincideConHash($actual, $hash)) {
            $errores['actual'] = 'La contraseña actual no es correcta.';
        }
        $errores += $this->validarNueva($datos, $hash);
        if ($errores) {
            throw ApiException::validacion($errores);
        }

        $this->conexion->prepare('UPDATE tbl_usuarios SET password = ? WHERE ID = ?')
            ->execute([\generarHashPassword((string) $datos['nueva']), $usuarioId]);
    }

    /**
     * Genera un enlace de recuperación y lo manda por correo.
     *
     * @param callable(string $correo, string $usuario, string $enlace): bool $enviarCorreo
     * @param string $baseEnlace dirección de la pantalla para restablecer (se le agrega ?token=...)
     * @throws ApiException 422 si el correo no es válido, 429 si se pidieron demasiados enlaces
     */
    public function solicitar(string $correo, callable $enviarCorreo, string $baseEnlace): void
    {
        $correo = Datos::texto($correo);
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            throw ApiException::validacion(['correo' => 'Ingresá un correo electrónico válido.']);
        }

        // Cada pedido cuenta como un intento: después de varios seguidos, se espera unos minutos
        $clave = mb_strtolower($correo);
        $minutos = \piccoloLoginMinutosBloqueado($this->conexion, 'recuperacion', $clave);
        if ($minutos > 0) {
            throw new ApiException("Ya pediste varios enlaces. Probá de nuevo en $minutos minuto" . ($minutos === 1 ? '' : 's') . '.', 429);
        }
        \piccoloLoginRegistrarFallo($this->conexion, 'recuperacion', $clave);

        $stmt = $this->conexion->prepare('SELECT ID, usuario FROM tbl_usuarios WHERE correo = ?');
        $stmt->execute([$correo]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$usuario) {
            return; // misma respuesta que si existiera
        }

        $token = bin2hex(random_bytes(32));
        // El vencimiento lo calcula la base, con su propio reloj (el mismo con el que después se compara)
        $this->conexion->prepare('UPDATE tbl_usuarios SET reset_token = ?, token_expira = DATE_ADD(NOW(), INTERVAL ' . self::MINUTOS_ENLACE . ' MINUTE) WHERE ID = ?')
            ->execute([hash('sha256', $token), (int) $usuario['ID']]);

        $enlace = $baseEnlace . '?' . http_build_query(['token' => $token]);
        if (!$enviarCorreo($correo, (string) $usuario['usuario'], $enlace)) {
            // Nunca se muestra en pantalla: solo queda en el registro del servidor si se está desarrollando
            error_log('No se pudo enviar el correo de recuperación a ' . $correo . (defined('MODO_DESARROLLO') && MODO_DESARROLLO ? ". Enlace: $enlace" : ''));
        }
    }

    /** @return string|null el nombre de usuario si el enlace sigue vigente */
    public function usuarioDelEnlace(string $token): ?string
    {
        $usuario = $this->porToken($token);

        return $usuario ? (string) $usuario['usuario'] : null;
    }

    /** @param array<string, mixed> $datos nueva, confirmar */
    public function restablecer(string $token, array $datos): void
    {
        $usuario = $this->porToken($token);
        if ($usuario === null) {
            throw new ApiException('El enlace venció o ya se usó. Pedí uno nuevo.', 410);
        }
        $errores = $this->validarNueva($datos, (string) $usuario['password']);
        if ($errores) {
            throw ApiException::validacion($errores);
        }

        $this->conexion->prepare('UPDATE tbl_usuarios SET password = ?, reset_token = NULL, token_expira = NULL WHERE ID = ?')
            ->execute([\generarHashPassword((string) $datos['nueva']), (int) $usuario['ID']]);
        \piccoloLoginRegistrarExito($this->conexion, 'panel', (string) $usuario['usuario']);
    }

    /** @return array<string, string> errores de la contraseña nueva */
    private function validarNueva(array $datos, string $hashActual): array
    {
        $nueva = is_string($datos['nueva'] ?? null) ? $datos['nueva'] : '';
        if (!\passwordCumpleRequisitos($nueva)) {
            return ['nueva' => \mensajeRequisitosPassword()];
        }
        if ($nueva !== ($datos['confirmar'] ?? null)) {
            return ['confirmar' => 'Las contraseñas no coinciden.'];
        }
        if (\passwordCoincideConHash($nueva, $hashActual)) {
            return ['nueva' => 'La contraseña nueva tiene que ser distinta de la actual.'];
        }

        return [];
    }

    /** @return array<string, mixed>|null */
    private function porToken(string $token): ?array
    {
        if (!preg_match('/^[0-9a-f]{64}$/', $token)) {
            return null;
        }
        $stmt = $this->conexion->prepare('SELECT ID, usuario, password FROM tbl_usuarios WHERE reset_token = ? AND token_expira > NOW()');
        $stmt->execute([hash('sha256', $token)]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function hash(int $usuarioId): ?string
    {
        $stmt = $this->conexion->prepare('SELECT password FROM tbl_usuarios WHERE ID = ?');
        $stmt->execute([$usuarioId]);
        $hash = $stmt->fetchColumn();

        return $hash !== false ? (string) $hash : null;
    }
}
