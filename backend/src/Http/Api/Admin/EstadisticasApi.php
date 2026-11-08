<?php

declare(strict_types=1);

namespace Piccolo\Http\Api\Admin;

use Piccolo\Http\Admin\EstadisticasController;
use Piccolo\Http\Api\Peticion;
use Piccolo\Http\Api\Respuesta;

/**
 * Rutas /admin/estadisticas. Las cuentas están en EstadisticasController.
 */
final class EstadisticasApi
{
    public function __construct(private EstadisticasController $estadisticas)
    {
    }

    /** GET /admin/estadisticas/pool?desde=&hasta=&jornada= */
    public function pool(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, 'seccion/estadisticasPool/index.php');
        Respuesta::ok($this->estadisticas->pool(self::filtros($peticion)));
    }

    /** GET /admin/estadisticas/bebidas?desde=&hasta=&jornada= */
    public function bebidas(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, 'seccion/estadisticasBebidas/index.php');
        Respuesta::ok($this->estadisticas->bebidas(self::filtros($peticion)));
    }

    /** @return array<string, mixed> */
    private static function filtros(Peticion $peticion): array
    {
        return [
            'desde' => $peticion->query('desde', ''),
            'hasta' => $peticion->query('hasta', ''),
            'jornada' => $peticion->query('jornada', 0),
        ];
    }
}
