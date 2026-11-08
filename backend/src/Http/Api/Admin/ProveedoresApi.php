<?php

declare(strict_types=1);

namespace Piccolo\Http\Api\Admin;

use Piccolo\Http\Admin\ProveedoresController;
use Piccolo\Http\Api\Peticion;
use Piccolo\Http\Api\Respuesta;

/**
 * Rutas /admin/proveedores. Solo traduce la petición HTTP: las reglas están en ProveedoresController,
 * que también usan las pantallas del panel.
 */
final class ProveedoresApi
{
    private const PANTALLA = 'seccion/proveedores/index.php';

    public function __construct(private ProveedoresController $proveedores)
    {
    }

    /** GET /admin/proveedores */
    public function listar(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, self::PANTALLA);
        Respuesta::ok($this->proveedores->listar());
    }

    /** GET /admin/proveedores/{id} */
    public function ver(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, self::PANTALLA);
        Respuesta::ok($this->proveedores->obtener(self::id($peticion)));
    }

    /** POST /admin/proveedores {nombre, telefono, email} */
    public function crear(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, self::PANTALLA);
        Respuesta::creado($this->proveedores->crear($peticion->cuerpo()));
    }

    /** PUT /admin/proveedores/{id} {nombre, telefono, email} */
    public function actualizar(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, self::PANTALLA);
        Respuesta::ok($this->proveedores->actualizar(self::id($peticion), $peticion->cuerpo()));
    }

    /** DELETE /admin/proveedores/{id} */
    public function eliminar(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, self::PANTALLA);
        $this->proveedores->eliminar(self::id($peticion));
        Respuesta::ok(null);
    }

    private static function id(Peticion $peticion): int
    {
        return AccesoPanel::idDeRuta($peticion, 'El proveedor no existe o ya fue eliminado.');
    }
}
