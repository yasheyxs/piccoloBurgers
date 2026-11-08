<?php

declare(strict_types=1);

namespace Piccolo\Http\Api\Admin;

use Piccolo\Http\Admin\BebidasController;
use Piccolo\Http\Api\Peticion;
use Piccolo\Http\Api\Respuesta;

/**
 * Rutas /admin/bebidas. Las reglas están en BebidasController; la jornada se abre y se cierra
 * desde /admin/pool/jornada porque es la misma.
 */
final class BebidasApi
{
    private const PANTALLA = 'seccion/bebidas/index.php';
    private const PANTALLA_CATALOGO = 'seccion/bebidasParametros/index.php';
    private const NO_EXISTE = 'No encontramos esa venta en la jornada activa.';

    public function __construct(private BebidasController $bebidas)
    {
    }

    /** GET /admin/bebidas: bebidas activas y ventas pendientes de entregar (sin montos) */
    public function estado(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, self::PANTALLA);
        Respuesta::ok($this->bebidas->estado());
    }

    /** POST /admin/bebidas/datos-sensibles {password} */
    public function sensibles(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, self::PANTALLA);
        Respuesta::ok($this->bebidas->mostrarSensibles((int) ($_SESSION['admin_id'] ?? 0), $peticion->crudo('password')));
    }

    /** POST /admin/bebidas/ventas {bebida_id, persona, tipo, cantidad} */
    public function vender(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, self::PANTALLA);
        $this->bebidas->vender($peticion->cuerpo());
        Respuesta::creado($this->bebidas->estado());
    }

    /** PUT /admin/bebidas/ventas/{id} {nombre} */
    public function renombrar(Peticion $peticion): void
    {
        $this->operar($peticion, fn(int $id) => $this->bebidas->renombrar($id, $peticion->cuerpo()));
    }

    /** POST /admin/bebidas/ventas/{id}/entregar */
    public function entregar(Peticion $peticion): void
    {
        $this->operar($peticion, fn(int $id) => $this->bebidas->entregar($id));
    }

    /** POST /admin/bebidas/ventas/{id}/revertir */
    public function revertir(Peticion $peticion): void
    {
        $this->operar($peticion, fn(int $id) => $this->bebidas->revertir($id));
    }

    /** GET /admin/bebidas/catalogo */
    public function catalogo(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, self::PANTALLA_CATALOGO);
        Respuesta::ok($this->bebidas->catalogo());
    }

    /** POST /admin/bebidas/catalogo {nombre, valor_jarra, valor_promo, jarras_por_promo, activa} */
    public function crearBebida(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, self::PANTALLA_CATALOGO);
        Respuesta::creado($this->bebidas->guardarBebida($peticion->cuerpo()));
    }

    /** PUT /admin/bebidas/catalogo/{id} */
    public function actualizarBebida(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, self::PANTALLA_CATALOGO);
        Respuesta::ok($this->bebidas->guardarBebida($peticion->cuerpo(), AccesoPanel::idDeRuta($peticion, 'La bebida no existe.')));
    }

    private function operar(Peticion $peticion, callable $accion): void
    {
        AccesoPanel::exigir($peticion, self::PANTALLA);
        $accion(AccesoPanel::idDeRuta($peticion, self::NO_EXISTE));
        Respuesta::ok($this->bebidas->estado());
    }
}
