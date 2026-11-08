<?php

declare(strict_types=1);

namespace Piccolo\Http\Admin;

use Piccolo\Http\Api\ApiException;
use Piccolo\Http\Api\Formato;
use Piccolo\Repositories\ContenidoRepositorio;

/**
 * Reglas del contenido del sitio: banners (el sitio muestra el más nuevo), testimonios y la
 * bandeja de comentarios del formulario de contacto. La usan las pantallas del panel y la API
 * (/api/v1/admin/banners, /testimonios y /comentarios).
 */
final class ContenidoController
{
    /** Tamaño mínimo de la imagen del banner, para que no se vea pixelada a pantalla completa */
    public const BANNER_ANCHO = 1200;
    public const BANNER_ALTO = 600;

    /** @param string $carpetaBanners carpeta pública donde se guardan las imágenes (public/img/banners) */
    public function __construct(private ContenidoRepositorio $repositorio, private string $carpetaBanners)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function banners(): array
    {
        $banners = array_map([self::class, 'formatoBanner'], $this->repositorio->banners());
        foreach ($banners as $i => &$banner) {
            $banner['en_el_sitio'] = $i === 0;
        }

        return $banners;
    }

    /** @return array<string, mixed> */
    public function banner(int $id): array
    {
        $banner = $this->repositorio->banner($id);
        if ($banner === null) {
            throw ApiException::noEncontrado('El banner no existe o ya fue eliminado.');
        }

        return self::formatoBanner($banner);
    }

    /**
     * El banner nuevo pasa a ser el que se ve en el sitio. La imagen es obligatoria.
     *
     * @param array<string, mixed> $datos titulo, descripcion
     * @param array<string, mixed>|null $imagen elemento de $_FILES
     */
    public function crearBanner(array $datos, ?array $imagen): array
    {
        $validos = $this->validarBanner($datos, ImagenSubida::fueEnviada($imagen) ? null : 'Elegí una imagen para el banner.');
        $validos['imagen'] = 'img/banners/' . $this->guardarImagen((array) $imagen);

        return $this->banner($this->repositorio->crearBanner($validos));
    }

    /** @param array<string, mixed>|null $imagen elemento de $_FILES (opcional: sin imagen se deja la que tenía) */
    public function actualizarBanner(int $id, array $datos, ?array $imagen): array
    {
        $anterior = $this->banner($id);
        $validos = $this->validarBanner($datos);
        $validos['imagen'] = $anterior['imagen_ruta'];
        if (ImagenSubida::fueEnviada($imagen)) {
            $validos['imagen'] = 'img/banners/' . $this->guardarImagen((array) $imagen);
        }

        $this->repositorio->actualizarBanner($id, $validos);
        if ($validos['imagen'] !== $anterior['imagen_ruta']) {
            $this->borrarImagen($anterior['imagen_ruta']);
        }

        return $this->banner($id);
    }

    public function eliminarBanner(int $id): void
    {
        $banner = $this->banner($id);
        $this->repositorio->eliminarBanner($id);
        $this->borrarImagen($banner['imagen_ruta']);
    }

    /** @return array<int, array{id: int, opinion: string, nombre: string}> */
    public function testimonios(): array
    {
        return array_map([self::class, 'formatoTestimonio'], $this->repositorio->testimonios());
    }

    /** @return array{id: int, opinion: string, nombre: string} */
    public function testimonio(int $id): array
    {
        $testimonio = $this->repositorio->testimonio($id);
        if ($testimonio === null) {
            throw ApiException::noEncontrado('El testimonio no existe o ya fue eliminado.');
        }

        return self::formatoTestimonio($testimonio);
    }

    /**
     * Crea (sin $id) o modifica un testimonio.
     *
     * @param array<string, mixed> $datos opinion, nombre
     * @return array{id: int, opinion: string, nombre: string}
     */
    public function guardarTestimonio(array $datos, int $id = 0): array
    {
        if ($id > 0) {
            $this->testimonio($id);
        }
        $opinion = Datos::texto($datos['opinion'] ?? '');
        $nombre = Datos::texto($datos['nombre'] ?? '');
        $errores = [];
        if ($opinion === '' || mb_strlen($opinion) > 255) {
            $errores['opinion'] = 'Escribí la opinión (hasta 255 caracteres).';
        }
        if ($nombre === '' || mb_strlen($nombre) > 100) {
            $errores['nombre'] = 'Escribí quién la dio (hasta 100 caracteres).';
        }
        if ($errores) {
            throw ApiException::validacion($errores);
        }

        return $this->testimonio($this->repositorio->guardarTestimonio($id, ['opinion' => $opinion, 'nombre' => $nombre]));
    }

    public function eliminarTestimonio(int $id): void
    {
        $this->testimonio($id);
        $this->repositorio->eliminarTestimonio($id);
    }

    /** @return array<int, array{id: int, nombre: string, correo: string, mensaje: string, fecha: ?string}> */
    public function comentarios(): array
    {
        return array_map(static fn(array $c): array => [
            'id' => (int) $c['ID'],
            'nombre' => (string) $c['nombre'],
            'correo' => (string) $c['correo'],
            'mensaje' => (string) $c['mensaje'],
            'fecha' => isset($c['fecha']) ? (string) $c['fecha'] : null,
        ], $this->repositorio->comentarios());
    }

    public function eliminarComentario(int $id): void
    {
        if (!$this->repositorio->eliminarComentario($id)) {
            throw ApiException::noEncontrado('El comentario no existe o ya fue eliminado.');
        }
    }

    /** @return array{titulo: string, descripcion: string} */
    private function validarBanner(array $datos, ?string $errorImagen = null): array
    {
        $titulo = Datos::texto($datos['titulo'] ?? '');
        $descripcion = Datos::texto($datos['descripcion'] ?? '');
        $errores = [];
        if ($titulo === '' || mb_strlen($titulo) > 100) {
            $errores['titulo'] = 'Escribí el título (hasta 100 caracteres).';
        }
        if ($descripcion === '' || mb_strlen($descripcion) > 255) {
            $errores['descripcion'] = 'Escribí la descripción (hasta 255 caracteres).';
        }
        if ($errorImagen !== null) {
            $errores['imagen'] = $errorImagen;
        }
        if ($errores) {
            throw ApiException::validacion($errores);
        }

        return ['titulo' => $titulo, 'descripcion' => $descripcion];
    }

    private function guardarImagen(array $archivo): string
    {
        return ImagenSubida::guardar($archivo, $this->carpetaBanners, 'banner', 'imagen', self::BANNER_ANCHO, self::BANNER_ALTO);
    }

    /** Solo se borran imágenes subidas desde el panel (las que viven en img/banners/). */
    private function borrarImagen(string $ruta): void
    {
        if (str_starts_with($ruta, 'img/banners/')) {
            ImagenSubida::borrar($this->carpetaBanners, $ruta);
        }
    }

    /** @return array<string, mixed> */
    private static function formatoBanner(array $fila): array
    {
        $ruta = (string) ($fila['imagen'] ?? '');

        return [
            'id' => (int) $fila['ID'],
            'titulo' => (string) $fila['titulo'],
            'descripcion' => (string) $fila['descripcion'],
            'imagen_ruta' => $ruta,
            'imagen' => Formato::urlPublica($ruta !== '' ? $ruta : 'img/BannerBG.jpg'),
        ];
    }

    /** @return array{id: int, opinion: string, nombre: string} */
    private static function formatoTestimonio(array $fila): array
    {
        return ['id' => (int) $fila['ID'], 'opinion' => (string) $fila['opinion'], 'nombre' => (string) $fila['nombre']];
    }
}
