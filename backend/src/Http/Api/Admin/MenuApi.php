<?php

declare(strict_types=1);

namespace Piccolo\Http\Api\Admin;

use Piccolo\Http\Admin\MenuController;
use Piccolo\Http\Api\Peticion;
use Piccolo\Http\Api\Respuesta;

/**
 * Rutas /admin/menu: productos, su foto y su receta. Las reglas están en MenuController.
 */
final class MenuApi
{
    private const PANTALLA = 'seccion/menu/index.php';
    private const NO_EXISTE = 'El producto no existe o ya fue eliminado.';

    public function __construct(private MenuController $menu)
    {
    }

    /** GET /admin/menu */
    public function listar(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, self::PANTALLA);
        Respuesta::ok($this->menu->listar());
    }

    /** GET /admin/menu/categorias */
    public function categorias(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, self::PANTALLA);
        Respuesta::ok(MenuController::categorias());
    }

    /** GET /admin/menu/{id} (incluye la receta) */
    public function ver(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, self::PANTALLA);
        Respuesta::ok($this->menu->obtener(AccesoPanel::idDeRuta($peticion, self::NO_EXISTE)));
    }

    /** POST /admin/menu {nombre, ingredientes, precio, categoria, visible} */
    public function crear(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, self::PANTALLA);
        Respuesta::creado($this->menu->crear($peticion->cuerpo()));
    }

    /** PUT /admin/menu/{id} */
    public function actualizar(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, self::PANTALLA);
        Respuesta::ok($this->menu->actualizar(AccesoPanel::idDeRuta($peticion, self::NO_EXISTE), $peticion->cuerpo()));
    }

    /** POST /admin/menu/{id}/foto (multipart/form-data con el campo "foto") */
    public function foto(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, self::PANTALLA);
        Respuesta::ok($this->menu->cambiarFoto(AccesoPanel::idDeRuta($peticion, self::NO_EXISTE), $_FILES['foto'] ?? null));
    }

    /** DELETE /admin/menu/{id} */
    public function eliminar(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, self::PANTALLA);
        $this->menu->eliminar(AccesoPanel::idDeRuta($peticion, self::NO_EXISTE));
        Respuesta::ok(null);
    }

    /** GET /admin/menu/{id}/receta */
    public function receta(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, self::PANTALLA);
        $id = AccesoPanel::idDeRuta($peticion, self::NO_EXISTE);
        $this->menu->obtener($id);
        Respuesta::ok($this->menu->receta($id));
    }

    /** PUT /admin/menu/{id}/receta {items: [{materia_prima_id, cantidad}]} (reemplaza la receta completa) */
    public function guardarReceta(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, self::PANTALLA);
        $items = $peticion->cuerpo()['items'] ?? [];
        Respuesta::ok($this->menu->guardarReceta(AccesoPanel::idDeRuta($peticion, self::NO_EXISTE), is_array($items) ? $items : []));
    }
}
