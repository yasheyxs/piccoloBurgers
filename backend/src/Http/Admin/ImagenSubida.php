<?php

declare(strict_types=1);

namespace Piccolo\Http\Admin;

use Piccolo\Http\Api\ApiException;

/**
 * Guarda imágenes subidas desde el panel de forma segura:
 *  - solo acepta imágenes reales JPG, PNG o WEBP (se revisa el contenido, no el nombre);
 *  - el archivo se guarda con un nombre generado por el sistema, nunca el original;
 *  - tiene un tamaño máximo.
 */
final class ImagenSubida
{
    private const FORMATOS = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    private const MAXIMO_MB = 5;

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
     *
     * @param array{name?: string, tmp_name?: string, error?: int, size?: int} $archivo
     * @throws ApiException 422 si no es una imagen válida
     */
    public static function guardar(array $archivo, string $carpeta, string $prefijo, string $campo = 'foto', int $anchoMinimo = 0, int $altoMinimo = 0): string
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

        $nombre = $prefijo . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
        if (!move_uploaded_file($temporal, rtrim($carpeta, '/\\') . DIRECTORY_SEPARATOR . $nombre)) {
            throw new \RuntimeException('No se pudo guardar la imagen en ' . $carpeta);
        }

        return $nombre;
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
