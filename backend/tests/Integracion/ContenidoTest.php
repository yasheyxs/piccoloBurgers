<?php

declare(strict_types=1);

namespace Piccolo\Tests\Integracion;

use Piccolo\Http\Admin\ContenidoController;
use Piccolo\Http\Api\ApiException;
use Piccolo\Repositories\ContenidoRepositorio;

final class ContenidoTest extends BaseDePruebas
{
    private ContenidoController $contenido;

    protected function setUp(): void
    {
        parent::setUp();
        $this->contenido = new ContenidoController(new ContenidoRepositorio($this->conexion), sys_get_temp_dir());
    }

    public function testElSitioMuestraElBannerMasNuevo(): void
    {
        $this->insertar('tbl_banners', ['titulo' => 'Prueba vieja', 'descripcion' => 'x', 'imagen' => 'img/banners/vieja.jpg', 'link' => '#menu']);
        $nuevo = $this->insertar('tbl_banners', ['titulo' => 'Prueba nueva', 'descripcion' => 'x', 'imagen' => '', 'link' => '#menu']);

        $banners = $this->contenido->banners();
        $this->assertSame([$nuevo, true], [$banners[0]['id'], $banners[0]['en_el_sitio']]);
        $this->assertFalse($banners[1]['en_el_sitio']);
        $this->assertStringEndsWith('img/BannerBG.jpg', $banners[0]['imagen'], 'sin imagen usa la de portada por defecto');
    }

    public function testUnBannerNecesitaTituloDescripcionEImagen(): void
    {
        try {
            $this->contenido->crearBanner(['titulo' => '', 'descripcion' => str_repeat('x', 256)], null);
            $this->fail('Debería rechazar el banner');
        } catch (ApiException $error) {
            $this->assertSame(['titulo', 'descripcion', 'imagen'], array_keys($error->errores()));
        }
    }

    public function testEditarUnBannerSinImagenNuevaConservaLaAnterior(): void
    {
        $id = $this->insertar('tbl_banners', ['titulo' => 'Prueba', 'descripcion' => 'x', 'imagen' => 'img/banners/prueba.jpg', 'link' => '#menu']);

        $banner = $this->contenido->actualizarBanner($id, ['titulo' => 'Prueba editada', 'descripcion' => 'y'], null);

        $this->assertSame(['Prueba editada', 'img/banners/prueba.jpg'], [$banner['titulo'], $banner['imagen_ruta']]);
    }

    public function testTestimonios(): void
    {
        $testimonio = $this->contenido->guardarTestimonio(['opinion' => ' Muy ricas ', 'nombre' => 'Prueba']);
        $this->anotar('tbl_testimonios', $testimonio['id']);
        $this->assertSame('Muy ricas', $testimonio['opinion']);

        $editado = $this->contenido->guardarTestimonio(['opinion' => 'Riquísimas', 'nombre' => 'Prueba'], $testimonio['id']);
        $this->assertSame('Riquísimas', $editado['opinion']);

        try {
            $this->contenido->guardarTestimonio(['opinion' => '', 'nombre' => str_repeat('x', 101)]);
            $this->fail('Debería rechazar el testimonio');
        } catch (ApiException $error) {
            $this->assertSame(['opinion', 'nombre'], array_keys($error->errores()));
        }

        $this->contenido->eliminarTestimonio($testimonio['id']);
        $this->expectException(ApiException::class);
        $this->contenido->testimonio($testimonio['id']);
    }

    public function testLosComentariosLargosSeGuardanConFecha(): void
    {
        $mensaje = str_repeat('Muy buena atención. ', 25); // 500 caracteres
        $id = $this->insertar('tbl_comentarios', ['nombre' => 'Prueba', 'correo' => 'prueba@ejemplo.test', 'mensaje' => $mensaje]);

        $comentario = array_column($this->contenido->comentarios(), null, 'id')[$id];
        $this->assertSame($mensaje, $comentario['mensaje'], 'entra completo (antes se cortaba en 255 o fallaba)');
        $this->assertNotNull($comentario['fecha']);

        $this->contenido->eliminarComentario($id);
        $this->expectException(ApiException::class);
        $this->contenido->eliminarComentario($id);
    }
}
