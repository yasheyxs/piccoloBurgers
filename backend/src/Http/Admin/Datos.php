<?php

declare(strict_types=1);

namespace Piccolo\Http\Admin;

/**
 * Lectura de valores que llegan de un formulario o de la API, iguales para todas las secciones.
 */
final class Datos
{
    /** Texto sin espacios al principio ni al final ('' si no es texto). */
    public static function texto(mixed $valor): string
    {
        if (!is_scalar($valor)) {
            return '';
        }
        $texto = (string) $valor;
        // Un texto con otra codificación (por ejemplo, Windows-1252) se pasa a UTF-8 en vez de hacer fallar la base
        if (!mb_check_encoding($texto, 'UTF-8')) {
            $texto = mb_convert_encoding($texto, 'UTF-8', 'Windows-1252');
        }

        return trim($texto);
    }

    /** Número con decimales; acepta coma o punto ("2,5" o "2.5"). null si está vacío o no es un número. */
    public static function decimal(mixed $valor): ?float
    {
        if (is_int($valor) || is_float($valor)) {
            return (float) $valor;
        }
        $texto = str_replace(',', '.', self::texto($valor));

        return $texto !== '' && is_numeric($texto) ? (float) $texto : null;
    }

    /** Número entero positivo, o 0 si no lo es. */
    public static function id(mixed $valor): int
    {
        $texto = self::texto($valor);
        return ctype_digit($texto) ? (int) $texto : (is_int($valor) && $valor > 0 ? $valor : 0);
    }

    /** Fecha en formato AAAA-MM-DD válida, o null. */
    public static function fecha(mixed $valor): ?string
    {
        $texto = self::texto($valor);
        $fecha = \DateTimeImmutable::createFromFormat('!Y-m-d', $texto);

        return $fecha !== false && $fecha->format('Y-m-d') === $texto ? $texto : null;
    }
}
