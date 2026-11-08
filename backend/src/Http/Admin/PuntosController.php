<?php

declare(strict_types=1);

namespace Piccolo\Http\Admin;

use PDO;
use Piccolo\Http\Api\ApiException;

/**
 * Reglas del sistema de puntos: mínimo para canjear, valor de cada punto y tope de descuento por compra.
 * La usan la pantalla "Sistema de puntos" y la API (/api/v1/admin/puntos/configuracion).
 */
final class PuntosController
{
    /** Monto de compra que se usa para el ejemplo de la pantalla */
    private const COMPRA_EJEMPLO = 10000;

    public function __construct(private PDO $conexion)
    {
        require_once \piccolo_path('src/Services/puntos_config.php');
        require_once \piccolo_path('src/Services/pedidos.php');
    }

    /**
     * Configuración actual. El porcentaje va de 0 a 100 (en la base se guarda como fracción).
     *
     * @return array{minimo_puntos: int, valor_punto: float, maximo_porcentaje: float, actualizado: ?string, ejemplo: array<string, float|int>}
     */
    public function obtener(): array
    {
        $config = \obtenerConfiguracionPuntos($this->conexion);

        return [
            'minimo_puntos' => (int) $config['minimo_puntos'],
            'valor_punto' => (float) $config['valor_punto'],
            'maximo_porcentaje' => round((float) $config['maximo_porcentaje'] * 100, 2),
            'actualizado' => $config['actualizado_en'] !== null ? (string) $config['actualizado_en'] : null,
            'ejemplo' => self::ejemplo($config),
        ];
    }

    /**
     * @param array<string, mixed> $datos minimo_puntos, valor_punto, maximo_porcentaje (0 a 100; aceptan coma decimal)
     * @return array<string, mixed> la configuración guardada
     */
    public function actualizar(array $datos): array
    {
        $minimo = filter_var(Datos::texto($datos['minimo_puntos'] ?? ''), FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 1000000]]);
        $valor = Datos::decimal($datos['valor_punto'] ?? null);
        $porcentaje = Datos::decimal($datos['maximo_porcentaje'] ?? null);
        $errores = [];

        if ($minimo === false) {
            $errores['minimo_puntos'] = 'El mínimo tiene que ser un número entero, 0 o más.';
        }
        if ($valor === null || $valor <= 0 || $valor > 1000000) {
            $errores['valor_punto'] = 'El valor de cada punto tiene que ser mayor a 0.';
        }
        if ($porcentaje === null || $porcentaje <= 0 || $porcentaje > 100) {
            $errores['maximo_porcentaje'] = 'El porcentaje tiene que estar entre 1 y 100.';
        }
        if ($errores) {
            throw ApiException::validacion($errores);
        }

        \actualizarConfiguracionPuntos($this->conexion, (int) $minimo, round((float) $valor, 2), round((float) $porcentaje / 100, 4));

        return $this->obtener();
    }

    /**
     * Qué pasa con una compra de ejemplo: cuántos puntos se pueden usar como máximo,
     * cuánto descuentan y cuántos puntos tiene que juntar el cliente para llegar al mínimo.
     *
     * @param array<string, mixed> $config
     * @return array{compra: int, maximo_puntos: int, maximo_descuento: float, compras_para_el_minimo: int}
     */
    private static function ejemplo(array $config): array
    {
        $valor = max(0.01, (float) $config['valor_punto']);
        $maximoDescuento = self::COMPRA_EJEMPLO * (float) $config['maximo_porcentaje'];
        $maximoPuntos = (int) floor($maximoDescuento / $valor);
        $puntosPorCompra = max(1, \pedidoPuntosGanados(self::COMPRA_EJEMPLO));

        return [
            'compra' => self::COMPRA_EJEMPLO,
            'maximo_puntos' => $maximoPuntos,
            'maximo_descuento' => round(min($maximoDescuento, $maximoPuntos * $valor), 2),
            'compras_para_el_minimo' => (int) ceil((int) $config['minimo_puntos'] / $puntosPorCompra),
        ];
    }
}
