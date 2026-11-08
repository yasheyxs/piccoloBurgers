<?php

declare(strict_types=1);

namespace Piccolo\Http\Api\Admin;

use Piccolo\Http\Admin\ContenidoController;
use Piccolo\Http\Api\Peticion;
use Piccolo\Http\Api\Respuesta;

/**
 * Rutas /admin/banners, /admin/testimonios y /admin/comentarios. Las reglas están en ContenidoController.
 * Las que llevan imagen se envían como multipart/form-data (con el token en X-CSRF-Token).
 */
final class ContenidoApi
{
    private const BANNERS = 'seccion/banners/index.php';
    private const TESTIMONIOS = 'seccion/testimonios/index.php';
    private const COMENTARIOS = 'seccion/comentarios/index.php';

    public function __construct(private ContenidoController $contenido)
    {
    }

    /** GET /admin/banners */
    public function banners(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, self::BANNERS);
        Respuesta::ok($this->contenido->banners());
    }

    /** GET /admin/banners/{id} */
    public function banner(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, self::BANNERS);
        Respuesta::ok($this->contenido->banner($this->id($peticion, 'El banner no existe.')));
    }

    /** POST /admin/banners (multipart: titulo, descripcion, imagen) */
    public function crearBanner(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, self::BANNERS);
        Respuesta::creado($this->contenido->crearBanner($_POST, $_FILES['imagen'] ?? null));
    }

    /** PUT /admin/banners/{id} {titulo, descripcion} */
    public function actualizarBanner(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, self::BANNERS);
        Respuesta::ok($this->contenido->actualizarBanner($this->id($peticion, 'El banner no existe.'), $peticion->cuerpo(), null));
    }

    /** POST /admin/banners/{id}/imagen (multipart: imagen) */
    public function cambiarImagenBanner(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, self::BANNERS);
        $id = $this->id($peticion, 'El banner no existe.');
        $banner = $this->contenido->banner($id);
        Respuesta::ok($this->contenido->actualizarBanner($id, $banner, $_FILES['imagen'] ?? null));
    }

    /** DELETE /admin/banners/{id} */
    public function eliminarBanner(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, self::BANNERS);
        $this->contenido->eliminarBanner($this->id($peticion, 'El banner no existe.'));
        Respuesta::ok(null);
    }

    /** GET /admin/testimonios */
    public function testimonios(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, self::TESTIMONIOS);
        Respuesta::ok($this->contenido->testimonios());
    }

    /** POST /admin/testimonios {opinion, nombre} */
    public function crearTestimonio(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, self::TESTIMONIOS);
        Respuesta::creado($this->contenido->guardarTestimonio($peticion->cuerpo()));
    }

    /** PUT /admin/testimonios/{id} {opinion, nombre} */
    public function actualizarTestimonio(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, self::TESTIMONIOS);
        Respuesta::ok($this->contenido->guardarTestimonio($peticion->cuerpo(), $this->id($peticion, 'El testimonio no existe.')));
    }

    /** DELETE /admin/testimonios/{id} */
    public function eliminarTestimonio(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, self::TESTIMONIOS);
        $this->contenido->eliminarTestimonio($this->id($peticion, 'El testimonio no existe.'));
        Respuesta::ok(null);
    }

    /** GET /admin/comentarios */
    public function comentarios(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, self::COMENTARIOS);
        Respuesta::ok($this->contenido->comentarios());
    }

    /** DELETE /admin/comentarios/{id} */
    public function eliminarComentario(Peticion $peticion): void
    {
        AccesoPanel::exigir($peticion, self::COMENTARIOS);
        $this->contenido->eliminarComentario($this->id($peticion, 'El comentario no existe.'));
        Respuesta::ok(null);
    }

    private function id(Peticion $peticion, string $noExiste): int
    {
        return AccesoPanel::idDeRuta($peticion, $noExiste);
    }
}
