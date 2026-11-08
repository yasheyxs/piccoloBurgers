<?php

declare(strict_types=1);

namespace Piccolo\Http\Api\Admin;

use Piccolo\Http\Admin\PoolController;
use Piccolo\Http\Api\Peticion;
use Piccolo\Http\Api\Respuesta;

/**
 * Rutas /admin/pool. Las reglas están en PoolController.
 * Las operaciones de la fila responden el estado actualizado, igual que GET /admin/pool.
 */
final class PoolApi
{
    private const PANTALLA = 'seccion/pool/index.php';
    private const PANTALLA_PARAMETROS = 'seccion/poolParametros/index.php';
    private const NO_EXISTE = 'No encontramos ese turno en la jornada activa.';

    public function __construct(private PoolController $pool)
    {
    }

    /** GET /admin/pool: jornada activa y fila de cada mesa (sin montos) */
    public function estado(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, self::PANTALLA);
        Respuesta::ok($this->pool->estado());
    }

    /** POST /admin/pool/datos-sensibles {password}: el estado con fichas vendidas y montos */
    public function sensibles(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, self::PANTALLA);
        Respuesta::ok($this->pool->mostrarSensibles((int) ($_SESSION['admin_id'] ?? 0), $peticion->crudo('password')));
    }

    /** POST /admin/pool/jornada: abre la jornada */
    public function abrir(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, self::PANTALLA);
        $this->pool->abrirJornada();
        Respuesta::creado($this->pool->estado());
    }

    /** GET /admin/pool/jornada/totales: lo vendido, para revisar antes de cerrar */
    public function totales(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, self::PANTALLA);
        Respuesta::ok($this->pool->totalesCierre());
    }

    /** POST /admin/pool/jornada/cierre {fichasVendidas?, bebidasVendidas?} */
    public function cerrar(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, self::PANTALLA);
        $this->pool->cerrarJornada($peticion->cuerpo());
        Respuesta::ok($this->pool->estado());
    }

    /** POST /admin/pool/turnos {pool, nombre, fichas} */
    public function agregar(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, self::PANTALLA);
        $this->pool->agregar($peticion->cuerpo());
        Respuesta::creado($this->pool->estado());
    }

    /** PUT /admin/pool/turnos/{id} {nombre} */
    public function renombrar(Peticion $peticion): void
    {
        $this->operar($peticion, fn(int $id) => $this->pool->renombrar($id, $peticion->cuerpo()));
    }

    /** POST /admin/pool/turnos/{id}/consumir */
    public function consumir(Peticion $peticion): void
    {
        $this->operar($peticion, fn(int $id) => $this->pool->consumir($id));
    }

    /** POST /admin/pool/turnos/{id}/revertir */
    public function revertir(Peticion $peticion): void
    {
        $this->operar($peticion, fn(int $id) => $this->pool->revertir($id));
    }

    /** POST /admin/pool/turnos/{id}/transferir {pool} */
    public function transferir(Peticion $peticion): void
    {
        $this->operar($peticion, fn(int $id) => $this->pool->transferir($id, $peticion->cuerpo()));
    }

    /** POST /admin/pool/turnos/{id}/mover {direccion: arriba|abajo} */
    public function mover(Peticion $peticion): void
    {
        $this->operar($peticion, fn(int $id) => $this->pool->mover($id, $peticion->cuerpo()));
    }

    /** PUT /admin/pool/orden {pool, ids} */
    public function reordenar(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, self::PANTALLA);
        $this->pool->reordenar($peticion->cuerpo());
        Respuesta::ok($this->pool->estado());
    }

    /** GET /admin/pool/configuracion */
    public function verConfiguracion(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, self::PANTALLA_PARAMETROS);
        Respuesta::ok($this->pool->configuracion());
    }

    /** PUT /admin/pool/configuracion {max_fichas_por_registro, valor_ficha} */
    public function guardarConfiguracion(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, self::PANTALLA_PARAMETROS);
        Respuesta::ok($this->pool->guardarConfiguracion($peticion->cuerpo()));
    }

    private function operar(Peticion $peticion, callable $accion): void
    {
        AccesoPanel::exigir($peticion, self::PANTALLA);
        $accion(AccesoPanel::idDeRuta($peticion, self::NO_EXISTE));
        Respuesta::ok($this->pool->estado());
    }
}
