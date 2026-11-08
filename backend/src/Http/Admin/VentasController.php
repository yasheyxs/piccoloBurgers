<?php

declare(strict_types=1);

namespace Piccolo\Http\Admin;

use PDO;
use Piccolo\Http\Api\ApiException;
use Piccolo\Repositories\VentasRepositorio;

/**
 * Reporte de ventas de un período: lo usan la pantalla de ventas, el Excel, el PDF y la API.
 * Suma los pedidos del sitio, las fichas del pool y las bebidas de las jornadas.
 */
final class VentasController
{
    public const MESES = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
        7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
    ];

    public function __construct(private VentasRepositorio $repositorio, private PDO $conexion)
    {
        require_once \piccolo_path('src/Services/pool_schema.php');
        require_once \piccolo_path('src/Services/bebidas_schema.php');
    }

    /**
     * Período pedido: "desde" y "hasta" en AAAA-MM-DD. Sin fechas, va del primer día del mes a hoy.
     *
     * @param array<string, mixed> $filtros
     * @return array{desde: string, hasta: string}
     * @throws ApiException 422 si alguna fecha no es válida o el período está al revés
     */
    public function periodo(array $filtros): array
    {
        $errores = [];
        $periodo = ['desde' => date('Y-m-01'), 'hasta' => date('Y-m-d')];
        foreach ($periodo as $campo => $porDefecto) {
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

    /**
     * @param array<string, mixed> $filtros desde / hasta
     * @return array<string, mixed>
     */
    public function resumen(array $filtros): array
    {
        ['desde' => $desde, 'hasta' => $hasta] = $this->periodo($filtros);

        $pedidos = $this->repositorio->totales($desde, $hasta);
        $fichas = (float) \poolMontoVendidoEntreFechas($this->conexion, $desde, $hasta);
        $bebidas = (float) \bebidaMontoVendidoEntreFechas($this->conexion, $desde, $hasta);
        $productos = $this->repositorio->productos($desde, $hasta);

        return [
            'desde' => $desde,
            'hasta' => $hasta,
            'total' => round($pedidos['monto'] + $fichas + $bebidas, 2),
            'por_origen' => ['pedidos' => $pedidos['monto'], 'fichas' => $fichas, 'bebidas' => $bebidas],
            'cantidad_pedidos' => $pedidos['pedidos'],
            'ticket_promedio' => $pedidos['pedidos'] > 0 ? round($pedidos['monto'] / $pedidos['pedidos'], 2) : 0.0,
            'producto_estrella' => $productos[0] ?? null,
            'productos' => $productos,
            'metodos_pago' => $this->repositorio->agrupados('metodo_pago', $desde, $hasta),
            'tipos_entrega' => $this->repositorio->agrupados('tipo_entrega', $desde, $hasta),
            'mensual' => $this->mensual((int) substr($hasta, 0, 4)),
        ];
    }

    /**
     * Ventas de cada mes del año, separadas por origen.
     *
     * @return array{anio: int, meses: array<int, array{mes: int, nombre: string, pedidos: float, fichas: float, bebidas: float, total: float}>}
     */
    public function mensual(int $anio): array
    {
        $pedidos = $this->repositorio->montoPorMes($anio);
        $fichas = self::porMes(\poolListarJornadas($this->conexion, "$anio-01-01", "$anio-12-31"), 'monto_vendido_total');
        $bebidas = self::porMes(\bebidaListarJornadas($this->conexion, "$anio-01-01", "$anio-12-31"), 'monto_vendido');

        $meses = [];
        foreach (self::MESES as $mes => $nombre) {
            $meses[] = [
                'mes' => $mes,
                'nombre' => $nombre,
                'pedidos' => $pedidos[$mes],
                'fichas' => $fichas[$mes],
                'bebidas' => $bebidas[$mes],
                'total' => round($pedidos[$mes] + $fichas[$mes] + $bebidas[$mes], 2),
            ];
        }

        return ['anio' => $anio, 'meses' => $meses];
    }

    /**
     * @param array<int, array<string, mixed>> $jornadas
     * @return array<int, float>
     */
    private static function porMes(array $jornadas, string $campoMonto): array
    {
        $meses = array_fill(1, 12, 0.0);
        foreach ($jornadas as $jornada) {
            $mes = (int) substr((string) ($jornada['fecha_apertura'] ?? ''), 5, 2);
            if ($mes >= 1 && $mes <= 12) {
                $meses[$mes] += (float) ($jornada[$campoMonto] ?? 0);
            }
        }

        return $meses;
    }
}
