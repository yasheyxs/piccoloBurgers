<?php

namespace Piccolo\Http\Api;

/**
 * Permite que el frontend, publicado en otro dominio, use la API enviando la cookie de sesión.
 * Los orígenes permitidos se configuran en la variable FRONTEND_URL (separados por coma).
 */
final class Cors
{
    /**
     * Agrega los encabezados CORS. Devuelve true si la petición era una verificación previa (OPTIONS)
     * y ya fue respondida.
     */
    public static function aplicar(): bool
    {
        $origen = (string) ($_SERVER['HTTP_ORIGIN'] ?? '');

        if ($origen !== '' && in_array(rtrim($origen, '/'), self::origenesPermitidos(), true)) {
            header('Access-Control-Allow-Origin: ' . $origen);
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type');
            header('Access-Control-Max-Age: 600');
            header('Vary: Origin');
        }

        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
            http_response_code(204);
            return true;
        }

        return false;
    }

    /** @return string[] */
    private static function origenesPermitidos(): array
    {
        $valor = (string) (getenv('FRONTEND_URL') ?: '');

        return array_values(array_filter(array_map(
            static fn($origen) => rtrim(trim($origen), '/'),
            explode(',', $valor)
        )));
    }
}
