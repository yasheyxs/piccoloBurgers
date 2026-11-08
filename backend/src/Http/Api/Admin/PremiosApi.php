<?php

declare(strict_types=1);

namespace Piccolo\Http\Api\Admin;

use Piccolo\Http\Admin\PremiosController;
use Piccolo\Http\Admin\PuntosController;
use Piccolo\Http\Api\Peticion;
use Piccolo\Http\Api\Respuesta;

/**
 * Rutas /admin/premios y /admin/puntos/configuracion. Las reglas están en PremiosController y PuntosController.
 */
final class PremiosApi
{
    private const PANTALLA_PREMIOS = 'seccion/premios/index.php';
    private const PANTALLA_PUNTOS = 'seccion/puntos/index.php';
    private const NO_EXISTE = 'El premio no existe o ya fue eliminado.';

    public function __construct(private PremiosController $premios, private PuntosController $puntos)
    {
    }

    /** GET /admin/premios */
    public function listar(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, self::PANTALLA_PREMIOS);
        Respuesta::ok($this->premios->listar());
    }

    /** GET /admin/premios/{id} */
    public function ver(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, self::PANTALLA_PREMIOS);
        Respuesta::ok($this->premios->obtener(AccesoPanel::idDeRuta($peticion, self::NO_EXISTE)));
    }

    /** POST /admin/premios {nombre, descripcion, costo_puntos} */
    public function crear(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, self::PANTALLA_PREMIOS);
        Respuesta::creado($this->premios->crear($peticion->cuerpo()));
    }

    /** PUT /admin/premios/{id} */
    public function actualizar(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, self::PANTALLA_PREMIOS);
        Respuesta::ok($this->premios->actualizar(AccesoPanel::idDeRuta($peticion, self::NO_EXISTE), $peticion->cuerpo()));
    }

    /** DELETE /admin/premios/{id} */
    public function eliminar(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, self::PANTALLA_PREMIOS);
        $this->premios->eliminar(AccesoPanel::idDeRuta($peticion, self::NO_EXISTE));
        Respuesta::ok(null);
    }

    /** GET /admin/puntos/configuracion */
    public function verConfiguracion(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, self::PANTALLA_PUNTOS);
        Respuesta::ok($this->puntos->obtener());
    }

    /** PUT /admin/puntos/configuracion {minimo_puntos, valor_punto, maximo_porcentaje (0 a 100)} */
    public function guardarConfiguracion(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, self::PANTALLA_PUNTOS);
        Respuesta::ok($this->puntos->actualizar($peticion->cuerpo()));
    }
}
