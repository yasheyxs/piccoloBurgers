<?php

declare(strict_types=1);

namespace Piccolo\Http\Admin;

use Piccolo\Http\Api\ApiException;

/**
 * Guarda imágenes subidas desde el panel de forma segura:
 *  - solo acepta imágenes reales JPG, PNG o WEBP (se revisa el contenido, no el nombre);
 *  - el archivo se guarda con un nombre generado por el sistema, nunca el original;
 *  - tiene un tamaño máximo;
 *  - si PHP tiene la extensión gd, se achica y se guarda en WEBP, que pesa mucho menos.
 */
final class ImagenSubida
{
    private const FORMATOS = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    private const MAXIMO_MB = 5;
    private const CALIDAD_WEBP = 82;

    /**
     * @param array{name?: string, tmp_name?: string, error?: int, size?: int}|null $archivo un elemento de $_FILES
     * @return bool si se eligió un archivo (aunque después resulte inválido)
     */
    public static function fueEnviada(?array $archivo): bool
    {
        return is_array($archivo) && ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    }

    /**
     * Valida y guarda la imagen en $carpeta. Devuelve el nombre del archivo guardado.
     * Con $anchoMaximo, una imagen más ancha se achica a ese ancho (sin deformarla).
     *
     * @param array{name?: string, tmp_name?: string, error?: int, size?: int} $archivo
     * @throws ApiException 422 si no es una imagen válida
     */
    public static function guardar(array $archivo, string $carpeta, string $prefijo, string $campo = 'foto', int $anchoMinimo = 0, int $altoMinimo = 0, int $anchoMaximo = 0): string
    {
        $error = $archivo['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE || ($archivo['size'] ?? 0) > self::MAXIMO_MB * 1024 * 1024) {
            throw ApiException::validacion([$campo => 'La imagen pesa más de ' . self::MAXIMO_MB . ' MB.']);
        }

        $temporal = (string) ($archivo['tmp_name'] ?? '');
        if ($error !== UPLOAD_ERR_OK || $temporal === '' || !is_uploaded_file($temporal)) {
            throw ApiException::validacion([$campo => 'No se pudo recibir la imagen. Probá de nuevo.']);
        }

        $info = @getimagesize($temporal);
        $extension = self::FORMATOS[$info['mime'] ?? ''] ?? null;
        if ($info === false || $extension === null) {
            throw ApiException::validacion([$campo => 'El archivo tiene que ser una imagen JPG, PNG o WEBP.']);
        }
        if ($info[0] < $anchoMinimo || $info[1] < $altoMinimo) {
            throw ApiException::validacion([$campo => "La imagen tiene que medir al menos {$anchoMinimo} × {$altoMinimo} píxeles (esta mide {$info[0]} × {$info[1]})."]);
        }

        if (!is_dir($carpeta) && !mkdir($carpeta, 0775, true) && !is_dir($carpeta)) {
            throw new \RuntimeException("No se pudo crear la carpeta de imágenes $carpeta");
        }

        $base = $prefijo . '_' . bin2hex(random_bytes(8));
        $carpeta = rtrim($carpeta, '/\\') . DIRECTORY_SEPARATOR;

        // Si se puede, se guarda achicada y en WEBP; si no (PHP sin la extensión gd), tal como vino
        $optimizada = self::optimizar($temporal, $info, $anchoMaximo, $carpeta . $base . '.webp');
        if ($optimizada) {
            @unlink($temporal);

            return $base . '.webp';
        }

        $nombre = $base . '.' . $extension;
        if (!move_uploaded_file($temporal, $carpeta . $nombre)) {
            throw new \RuntimeException('No se pudo guardar la imagen en ' . $carpeta);
        }

        return $nombre;
    }

    /**
     * Guarda una copia en WEBP, achicada si es más ancha que $anchoMaximo. Pesa mucho menos que la
     * original y, al volver a dibujarla, se descartan los datos ocultos de la foto (como la ubicación
     * donde se sacó).
     *
     * @param array{0: int, 1: int, mime: string} $info lo que devolvió getimagesize()
     * @return bool false si no se pudo (sin gd o sin soporte de WEBP), y entonces se guarda la original
     */
    private static function optimizar(string $origen, array $info, int $anchoMaximo, string $destino): bool
    {
        if (!function_exists('imagewebp') || !function_exists('imagecreatefromstring')) {
            return false;
        }
        $contenido = file_get_contents($origen);
        $imagen = $contenido !== false ? @imagecreatefromstring($contenido) : false;
        if ($imagen === false) {
            return false;
        }

        [$ancho, $alto] = [$info[0], $info[1]];
        if ($anchoMaximo > 0 && $ancho > $anchoMaximo) {
            $achicada = imagescale($imagen, $anchoMaximo, (int) round($alto * $anchoMaximo / $ancho), IMG_BICUBIC);
            if ($achicada !== false) {
                imagedestroy($imagen);
                $imagen = $achicada;
            }
        }
        // Las transparencias de un PNG se conservan
        imagepalettetotruecolor($imagen);
        imagealphablending($imagen, false);
        imagesavealpha($imagen, true);

        $guardada = imagewebp($imagen, $destino, self::CALIDAD_WEBP);
        imagedestroy($imagen);

        return $guardada && is_file($destino) && filesize($destino) > 0;
    }

    /** Borra una imagen guardada antes, si existe y está dentro de la carpeta indicada. */
    public static function borrar(string $carpeta, string $nombre): void
    {
        $nombre = basename($nombre);
        if ($nombre === '' || $nombre === '.' || $nombre === '..') {
            return;
        }
        $ruta = rtrim($carpeta, '/\\') . DIRECTORY_SEPARATOR . $nombre;
        if (is_file($ruta)) {
            @unlink($ruta);
        }
    }
}
