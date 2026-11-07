<?php

namespace Piccolo\Http\Api;

/**
 * Respuestas JSON con un formato único para toda la API:
 *   éxito: {"exito": true, "datos": ...}
 *   error: {"exito": false, "mensaje": "...", "errores": {"campo": "..."}}
 */
final class Respuesta
{
    public static function ok($datos = null, int $status = 200): void
    {
        self::enviar(['exito' => true, 'datos' => $datos], $status);
    }

    public static function creado($datos = null): void
    {
        self::ok($datos, 201);
    }

    /**
     * @param array<string, string> $errores
     */
    public static function error(string $mensaje, int $status = 400, array $errores = []): void
    {
        $cuerpo = ['exito' => false, 'mensaje' => $mensaje];
        if ($errores) {
            $cuerpo['errores'] = $errores;
        }

        self::enviar($cuerpo, $status);
    }

    private static function enviar(array $cuerpo, int $status): void
    {
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-store');
        }

        echo json_encode($cuerpo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
    }
}
