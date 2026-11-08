<?php

declare(strict_types=1);

namespace Piccolo\Http\Api\Admin;

use Piccolo\Http\Admin\UsuariosController;
use Piccolo\Http\Api\Peticion;
use Piccolo\Http\Api\Respuesta;

/**
 * Rutas /admin/usuarios. Las reglas están en UsuariosController.
 */
final class UsuariosApi
{
    private const PANTALLA = 'seccion/usuarios/index.php';
    private const NO_EXISTE = 'El usuario no existe o ya fue eliminado.';

    public function __construct(private UsuariosController $usuarios)
    {
    }

    /** GET /admin/usuarios */
    public function listar(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, self::PANTALLA);
        Respuesta::ok($this->usuarios->listar(self::actual()));
    }

    /** GET /admin/usuarios/{id} */
    public function ver(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, self::PANTALLA);
        Respuesta::ok($this->usuarios->obtener(AccesoPanel::idDeRuta($peticion, self::NO_EXISTE)));
    }

    /** POST /admin/usuarios {usuario, correo, rol, password, confirmar, password_actual?} */
    public function crear(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, self::PANTALLA);
        Respuesta::creado($this->usuarios->crear($peticion->cuerpo(), self::actual()));
    }

    /** PUT /admin/usuarios/{id} {usuario, correo, rol, password_actual?} */
    public function actualizar(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, self::PANTALLA);
        Respuesta::ok($this->usuarios->actualizar(AccesoPanel::idDeRuta($peticion, self::NO_EXISTE), $peticion->cuerpo(), self::actual()));
    }

    /** DELETE /admin/usuarios/{id} */
    public function eliminar(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, self::PANTALLA);
        $this->usuarios->eliminar(AccesoPanel::idDeRuta($peticion, self::NO_EXISTE), self::actual());
        Respuesta::ok(null);
    }

    /** Usuario que está usando el panel (lo deja AccesoPanel::exigir al revisar la sesión). */
    private static function actual(): int
    {
        return (int) ($_SESSION['admin_id'] ?? 0);
    }
}
