<?php

declare(strict_types=1);

namespace Piccolo\Http\Api\Admin;

use PDO;
use Piccolo\Http\Api\ApiException;
use Piccolo\Http\Api\Peticion;
use Piccolo\Http\Api\Respuesta;

/**
 * Inicio y cierre de sesión del panel por la API. Usa la misma sesión que las pantallas PHP:
 * iniciar sesión acá también la inicia en el panel, y al revés.
 */
final class SesionPanelController
{
    public function __construct(private PDO $conexion)
    {
    }

    /** GET /admin/sesion → el usuario con sesión y el token CSRF, o null */
    public function ver(Peticion $peticion): void
    {
        if (!\piccolo_admin_sesion_vigente()) {
            Respuesta::ok(null);
            return;
        }

        Respuesta::ok($this->datosSesion());
    }

    /** POST /admin/sesion {usuario, password} */
    public function iniciar(Peticion $peticion): void
    {
        $usuario = $peticion->texto('usuario', 100);
        $password = $peticion->crudo('password');
        if ($usuario === '' || $password === '') {
            throw ApiException::validacion([], 'Completá usuario y contraseña.');
        }

        $resultado = \piccoloPanelAutenticar($this->conexion, $usuario, $password);
        if ($resultado['usuario'] === null) {
            throw new ApiException(\piccoloPanelMensajeRechazo($resultado), $resultado['motivo'] === 'bloqueado' ? 429 : 401);
        }

        \piccolo_admin_guardar_sesion($resultado['usuario']);
        Respuesta::ok($this->datosSesion());
    }

    /** DELETE /admin/sesion */
    public function cerrar(Peticion $peticion): void
    {
        if (\piccolo_admin_sesion_vigente() && !\piccolo_csrf_valido()) {
            throw new ApiException('Falta el token de seguridad (X-CSRF-Token).', 403);
        }

        \piccolo_admin_cerrar_sesion();
        unset($_SESSION['csrf_token']);
        Respuesta::ok(null);
    }

    /** @return array{usuario: string, rol: string, csrf_token: string} */
    private function datosSesion(): array
    {
        return [
            'usuario' => (string) $_SESSION['admin_usuario'],
            'rol' => (string) $_SESSION['rol'],
            'csrf_token' => \piccolo_csrf_token(),
        ];
    }
}
