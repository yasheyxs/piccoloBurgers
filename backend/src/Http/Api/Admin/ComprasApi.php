<?php

declare(strict_types=1);

namespace Piccolo\Http\Api\Admin;

use Piccolo\Http\Admin\ComprasController;
use Piccolo\Http\Api\Peticion;
use Piccolo\Http\Api\Respuesta;

/**
 * Rutas /admin/compras. Las reglas (y el ajuste del stock) están en ComprasController.
 */
final class ComprasApi
{
    private const PANTALLA = 'seccion/compras/index.php';
    private const NO_EXISTE = 'La compra no existe o ya fue eliminada.';

    public function __construct(private ComprasController $compras)
    {
    }

    /** GET /admin/compras */
    public function listar(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, self::PANTALLA);
        Respuesta::ok($this->compras->listar());
    }

    /** GET /admin/compras/resumen */
    public function resumen(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, self::PANTALLA);
        Respuesta::ok($this->compras->resumen());
    }

    /** GET /admin/compras/{id} */
    public function ver(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, self::PANTALLA);
        Respuesta::ok($this->compras->obtener(AccesoPanel::idDeRuta($peticion, self::NO_EXISTE)));
    }

    /** POST /admin/compras {fecha, proveedor_id, items: [{materia_prima_id, cantidad, precio_unitario}]} */
    public function registrar(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, self::PANTALLA);
        Respuesta::creado($this->compras->registrar($peticion->cuerpo()));
    }

    /** DELETE /admin/compras/{id} */
    public function eliminar(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, self::PANTALLA);
        $this->compras->eliminar(AccesoPanel::idDeRuta($peticion, self::NO_EXISTE));
        Respuesta::ok(null);
    }
}
