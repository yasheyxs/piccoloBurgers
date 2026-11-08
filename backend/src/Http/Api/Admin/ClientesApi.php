<?php

declare(strict_types=1);

namespace Piccolo\Http\Api\Admin;

use Piccolo\Http\Admin\ClientesController;
use Piccolo\Http\Api\Peticion;
use Piccolo\Http\Api\Respuesta;

/**
 * Rutas /admin/clientes. Las reglas están en ClientesController.
 */
final class ClientesApi
{
    private const PANTALLA = 'clientes.php';
    private const NO_EXISTE = 'El cliente no existe o ya fue eliminado.';

    public function __construct(private ClientesController $clientes)
    {
    }

    /** GET /admin/clientes */
    public function listar(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, self::PANTALLA);
        Respuesta::ok($this->clientes->listar());
    }

    /** GET /admin/clientes/codigos-pais */
    public function codigosPais(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, self::PANTALLA);
        Respuesta::ok(ClientesController::codigosPais());
    }

    /** GET /admin/clientes/{id}: datos, pedidos y movimientos de puntos */
    public function ver(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, self::PANTALLA);
        Respuesta::ok($this->clientes->detalle(AccesoPanel::idDeRuta($peticion, self::NO_EXISTE)));
    }

    /** POST /admin/clientes {nombre, email, codigo_pais, telefono, puntos}: devuelve también la contraseña provisoria */
    public function crear(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, self::PANTALLA);
        Respuesta::creado($this->clientes->crear($peticion->cuerpo()));
    }

    /** PUT /admin/clientes/{id} */
    public function actualizar(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, self::PANTALLA);
        Respuesta::ok($this->clientes->actualizar(AccesoPanel::idDeRuta($peticion, self::NO_EXISTE), $peticion->cuerpo()));
    }

    /** DELETE /admin/clientes/{id} */
    public function eliminar(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, self::PANTALLA);
        $this->clientes->eliminar(AccesoPanel::idDeRuta($peticion, self::NO_EXISTE));
        Respuesta::ok(null);
    }
}
