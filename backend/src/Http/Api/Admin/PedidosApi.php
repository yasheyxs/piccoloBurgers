<?php

declare(strict_types=1);

namespace Piccolo\Http\Api\Admin;

use Piccolo\Http\Admin\PedidosPanelController;
use Piccolo\Http\Api\Peticion;
use Piccolo\Http\Api\Respuesta;

/**
 * Rutas /admin/pedidos (cocina y delivery). Las reglas están en PedidosPanelController.
 */
final class PedidosApi
{
    public function __construct(private PedidosPanelController $pedidos)
    {
    }

    /** GET /admin/pedidos/cocina: en preparación y en camino */
    public function cocina(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, 'panel_cocina.php');
        Respuesta::ok($this->pedidos->cocina());
    }

    /** GET /admin/pedidos/delivery: en camino */
    public function delivery(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, 'panel_delivery.php');
        Respuesta::ok($this->pedidos->delivery());
    }

    /** PUT /admin/pedidos/{id} {estado?, pago?: "Si"|"No"} */
    public function actualizar(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, 'actualizar_estado.php');
        $datos = $peticion->cuerpo();
        Respuesta::ok($this->pedidos->actualizar(
            AccesoPanel::idDeRuta($peticion, 'El pedido no existe.'),
            isset($datos['estado']) ? (string) $datos['estado'] : null,
            $datos['pago'] ?? null,
            (string) ($_SESSION['rol'] ?? '')
        ));
    }
}
