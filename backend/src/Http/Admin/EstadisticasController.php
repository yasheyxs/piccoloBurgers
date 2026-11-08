<?php

declare(strict_types=1);

namespace Piccolo\Http\Admin;

use PDO;

/**
 * Estadísticas de las jornadas de pool y bebidas en un período. Las cuentas (con los ajustes que se
 * cargan al cerrar la jornada) están en pool_schema.php y bebidas_schema.php; acá se ordenan para
 * las pantallas, el Excel, el PDF y la API (/api/v1/admin/estadisticas/...).
 */
final class EstadisticasController
{
    public function __construct(private PDO $conexion)
    {
        require_once \piccolo_path('src/Services/pool_schema.php');
        require_once \piccolo_path('src/Services/bebidas_schema.php');
    }

    /**
     * @param array<string, mixed> $filtros desde, hasta y jornada (id de la jornada a detallar; sin ella, la última)
     * @return array<string, mixed>
     */
    public function pool(array $filtros): array
    {
        ['desde' => $desde, 'hasta' => $hasta] = Periodo::leer($filtros);
        $filas = \poolListarJornadas($this->conexion, $desde, $hasta);
        $r = \poolResumenGeneral($filas);
        $jornadas = array_map(static fn(array $j): array => [
            'id' => (int) $j['id'],
            'apertura' => (string) $j['fecha_apertura'],
            'cierre' => $j['fecha_cierre'] !== null ? (string) $j['fecha_cierre'] : null,
            'estado' => (string) $j['estado'],
            'duracion_minutos' => $j['duracion_minutos'],
            'duracion' => \poolFormatearDuracion($j['duracion_minutos']),
            'vendidas' => (int) $j['total_vendidas'],
            'pendientes' => (int) $j['total_pendientes'],
            'monto' => (float) $j['monto_vendido_total'],
            'jugadores' => (int) $j['jugadores_atendidos'],
            'turnos_completados' => (int) $j['turnos_completados'],
            'mesas' => [
                'azul' => ['vendidas' => (int) $j['fichas_vendidas_azul'], 'monto' => (float) $j['monto_vendido_azul'], 'pendientes' => (int) $j['fichas_pendientes_azul']],
                'rojo' => ['vendidas' => (int) $j['fichas_vendidas_rojo'], 'monto' => (float) $j['monto_vendido_rojo'], 'pendientes' => (int) $j['fichas_pendientes_rojo']],
            ],
        ], $filas);

        return [
            'desde' => $desde,
            'hasta' => $hasta,
            'resumen' => [
                'jornadas' => (int) $r['total_jornadas'],
                'vendidas' => (int) $r['total_vendidas'],
                'pendientes' => (int) $r['total_pendientes'],
                'promedio_por_jornada' => (float) $r['promedio_fichas_jornada'],
                'monto' => (float) $r['monto_vendido_total'],
                'jugadores' => (int) $r['jugadores_atendidos'],
                'mesas' => [
                    'azul' => ['vendidas' => (int) $r['vendidas_azul'], 'monto' => (float) $r['monto_vendido_azul']],
                    'rojo' => ['vendidas' => (int) $r['vendidas_rojo'], 'monto' => (float) $r['monto_vendido_rojo']],
                ],
            ],
            'jornadas' => $jornadas,
            'seleccionada' => self::elegir($jornadas, $filtros),
        ];
    }

    /**
     * @param array<string, mixed> $filtros desde, hasta y jornada
     * @return array<string, mixed>
     */
    public function bebidas(array $filtros): array
    {
        ['desde' => $desde, 'hasta' => $hasta] = Periodo::leer($filtros);
        $filas = \bebidaListarJornadas($this->conexion, $desde, $hasta);
        $r = \bebidaResumenGeneral($filas);

        $porBebida = [];
        $jornadas = array_map(static function (array $j) use (&$porBebida): array {
            $detalle = array_map(static fn(array $d): array => [
                'bebida' => (string) $d['bebida_nombre'],
                'tipo' => (string) $d['tipo'],
                'vendidas' => (int) $d['vendidas'],
                'entregadas' => (int) $d['entregadas'],
                'pendientes' => (int) $d['pendientes'],
                'monto' => (float) $d['monto_vendido'],
            ], $j['detalle']);
            foreach ($detalle as $d) {
                $clave = $d['bebida'] . '|' . $d['tipo'];
                $porBebida[$clave] ??= ['bebida' => $d['bebida'], 'tipo' => $d['tipo'], 'vendidas' => 0, 'monto' => 0.0];
                $porBebida[$clave]['vendidas'] += $d['vendidas'];
                $porBebida[$clave]['monto'] += $d['monto'];
            }

            return [
                'id' => (int) $j['id'],
                'apertura' => (string) $j['fecha_apertura'],
                'estado' => (string) $j['estado'],
                'vendidas' => (int) $j['vendidas'],
                'entregadas' => (int) $j['entregadas'],
                'pendientes' => (int) $j['pendientes'],
                'monto' => (float) $j['monto_vendido'],
                'personas' => (int) $j['personas'],
                'detalle' => $detalle,
            ];
        }, $filas);

        return [
            'desde' => $desde,
            'hasta' => $hasta,
            'resumen' => [
                'jornadas' => (int) $r['total_jornadas'],
                'vendidas' => (int) $r['total_vendidas'],
                'entregadas' => (int) $r['total_entregadas'],
                'pendientes' => (int) $r['total_pendientes'],
                'promedio_por_jornada' => (float) $r['promedio_por_jornada'],
                'monto' => (float) $r['monto_vendido_total'],
                'personas' => (int) $r['personas_atendidas'],
            ],
            'por_bebida' => array_values($porBebida),
            'jornadas' => $jornadas,
            'seleccionada' => self::elegir($jornadas, $filtros),
        ];
    }

    /**
     * La jornada pedida si está en el período; si no, la más reciente.
     *
     * @param array<int, array<string, mixed>> $jornadas de la más nueva a la más vieja
     * @return array<string, mixed>|null
     */
    private static function elegir(array $jornadas, array $filtros): ?array
    {
        $id = Datos::id($filtros['jornada'] ?? ($filtros['jornada_id'] ?? 0));
        foreach ($jornadas as $jornada) {
            if ($jornada['id'] === $id) {
                return $jornada;
            }
        }

        return $jornadas[0] ?? null;
    }
}
