<?php

declare(strict_types=1);

namespace Piccolo\Http\Api\Admin;

use Piccolo\Http\Admin\CanjeController;
use Piccolo\Http\Api\Peticion;
use Piccolo\Http\Api\Respuesta;

/**
 * Rutas /admin/clientes/{id}/canje. Las reglas están en CanjeController.
 */
final class CanjeApi
{
    private const PANTALLA = 'clientes_canje.php';
    private const NO_EXISTE = 'El cliente no existe o ya fue eliminado.';

    public function __construct(private CanjeController $canje)
    {
    }

    /** GET /admin/clientes/{id}/canje: cliente, reglas de puntos, productos y premios */
    public function datos(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, self::PANTALLA);
        Respuesta::ok($this->canje->datos(AccesoPanel::idDeRuta($peticion, self::NO_EXISTE)));
    }

    /** POST /admin/clientes/{id}/canje/venta {items, puntos_utilizados, metodo_pago, tipo_entrega, direccion, referencias, nota} */
    public function venta(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, self::PANTALLA);
        Respuesta::creado($this->canje->registrarVenta(AccesoPanel::idDeRuta($peticion, self::NO_EXISTE), $peticion->cuerpo(), session_id()));
    }

    /** POST /admin/clientes/{id}/canje/premios {premios: [{id, cantidad}]} */
    public function premios(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, self::PANTALLA);
        Respuesta::creado($this->canje->canjearPremios(AccesoPanel::idDeRuta($peticion, self::NO_EXISTE), $peticion->cuerpo()));
    }
}
