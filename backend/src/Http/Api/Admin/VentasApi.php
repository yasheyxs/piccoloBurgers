<?php

declare(strict_types=1);

namespace Piccolo\Http\Api\Admin;

use Piccolo\Http\Admin\VentasController;
use Piccolo\Http\Api\Peticion;
use Piccolo\Http\Api\Respuesta;

/**
 * Ruta /admin/ventas: el mismo reporte que la pantalla de ventas, calculado en VentasController.
 */
final class VentasApi
{
    private const PANTALLA = 'seccion/ventas/index.php';

    public function __construct(private VentasController $ventas)
    {
    }

    /** GET /admin/ventas?desde=AAAA-MM-DD&hasta=AAAA-MM-DD */
    public function resumen(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, self::PANTALLA);
        Respuesta::ok($this->ventas->resumen([
            'desde' => $peticion->query('desde', ''),
            'hasta' => $peticion->query('hasta', ''),
        ]));
    }
}
