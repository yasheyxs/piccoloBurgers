<?php

declare(strict_types=1);

namespace Piccolo\Http\Admin;

use Piccolo\Http\Api\ApiException;

/**
 * Período de un reporte ("desde" y "hasta" en AAAA-MM-DD), igual para ventas y estadísticas.
 * Sin fechas va del primer día del mes a hoy.
 */
final class Periodo
{
    /**
     * @param array<string, mixed> $filtros
     * @return array{desde: string, hasta: string}
     * @throws ApiException 422 si alguna fecha no es válida o el período está al revés
     */
    public static function leer(array $filtros): array
    {
        $errores = [];
        $periodo = ['desde' => date('Y-m-01'), 'hasta' => date('Y-m-d')];
        foreach (array_keys($periodo) as $campo) {
            $valor = Datos::texto($filtros[$campo] ?? '');
            if ($valor === '') {
                continue;
            }
            $fecha = Datos::fecha($valor);
            if ($fecha === null) {
                $errores[$campo] = 'Usá una fecha válida (AAAA-MM-DD).';
            } else {
                $periodo[$campo] = $fecha;
            }
        }
        if (!$errores && $periodo['desde'] > $periodo['hasta']) {
            $errores['hasta'] = 'La fecha "hasta" no puede ser anterior a "desde".';
        }
        if ($errores) {
            throw ApiException::validacion($errores, 'Revisá las fechas del reporte.');
        }

        return $periodo;
    }

    /** @return array<string, array{0: string, 1: string}> atajos de la pantalla: nombre => [desde, hasta] */
    public static function atajos(): array
    {
        $hoy = date('Y-m-d');

        return [
            'Última semana' => [date('Y-m-d', strtotime('-6 days')), $hoy],
            'Este mes' => [date('Y-m-01'), $hoy],
            'Este año' => [date('Y-01-01'), $hoy],
        ];
    }
}
