<?php

declare(strict_types=1);

namespace Piccolo\Http\Api\Admin;

use Piccolo\Http\Admin\MateriasPrimasController;
use Piccolo\Http\Api\Peticion;
use Piccolo\Http\Api\Respuesta;

/**
 * Rutas /admin/materias-primas. Las reglas están en MateriasPrimasController.
 */
final class MateriasPrimasApi
{
    private const PANTALLA = 'seccion/materiasPrimas/index.php';
    private const NO_EXISTE = 'La materia prima no existe o ya fue eliminada.';

    public function __construct(private MateriasPrimasController $materias)
    {
    }

    /** GET /admin/materias-primas[?criticos=1] */
    public function listar(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, self::PANTALLA);
        Respuesta::ok($this->materias->listar((string) $peticion->query('criticos', '') === '1'));
    }

    /** GET /admin/materias-primas/{id} */
    public function ver(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, self::PANTALLA);
        Respuesta::ok($this->materias->obtener(AccesoPanel::idDeRuta($peticion, self::NO_EXISTE)));
    }

    /** POST /admin/materias-primas */
    public function crear(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, self::PANTALLA);
        Respuesta::creado($this->materias->crear($peticion->cuerpo()));
    }

    /** PUT /admin/materias-primas/{id} */
    public function actualizar(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, self::PANTALLA);
        Respuesta::ok($this->materias->actualizar(AccesoPanel::idDeRuta($peticion, self::NO_EXISTE), $peticion->cuerpo()));
    }

    /** DELETE /admin/materias-primas/{id} */
    public function eliminar(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, self::PANTALLA);
        $this->materias->eliminar(AccesoPanel::idDeRuta($peticion, self::NO_EXISTE));
        Respuesta::ok(null);
    }
}
