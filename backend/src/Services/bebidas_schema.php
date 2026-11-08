<?php

require_once __DIR__ . '/pool_schema.php';

if (!function_exists('bebidaListar')) {
    function bebidaListar(PDO $conexion, bool $soloActivas = false): array
    {
        $sql = 'SELECT * FROM bebidas';
        if ($soloActivas) {
            $sql .= ' WHERE activa = 1';
        }
        $sql .= ' ORDER BY activa DESC, nombre ASC';

        return array_map(static function (array $bebida): array {
            $bebida['id'] = (int) $bebida['id'];
            $bebida['valor_jarra'] = (float) $bebida['valor_jarra'];
            $bebida['valor_promo'] = (float) $bebida['valor_promo'];
            $bebida['jarras_por_promo'] = max(1, (int) ($bebida['jarras_por_promo'] ?? 3));
            $bebida['activa'] = (bool) $bebida['activa'];
            return $bebida;
        }, $conexion->query($sql)->fetchAll(PDO::FETCH_ASSOC));
    }
}

if (!function_exists('bebidaStatsVivos')) {
    function bebidaStatsVivos(PDO $conexion, int $jornadaId): array
    {
        $stmt = $conexion->prepare(
            'SELECT
                COALESCE(SUM(cantidad_total * unidades_por_item), 0) AS vendidas,
                COALESCE(SUM(cantidad_entregada), 0) AS entregadas,
                COALESCE(SUM(cantidad_total * valor_unitario), 0) AS monto_vendido,
                COUNT(DISTINCT persona) AS personas,
                SUM(CASE WHEN cantidad_entregada < cantidad_total * unidades_por_item THEN 1 ELSE 0 END) AS en_espera
             FROM bebida_ventas
             WHERE jornada_id = :jornada_id'
        );
        $stmt->execute([':jornada_id' => $jornadaId]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $vendidas = (int) ($stats['vendidas'] ?? 0);
        $entregadas = (int) ($stats['entregadas'] ?? 0);

        return [
            'vendidas' => $vendidas,
            'entregadas' => $entregadas,
            'pendientes' => max(0, $vendidas - $entregadas),
            'montoVendido' => (float) ($stats['monto_vendido'] ?? 0),
            'personas' => (int) ($stats['personas'] ?? 0),
            'enEspera' => (int) ($stats['en_espera'] ?? 0),
        ];
    }
}

if (!function_exists('bebidaVentasPendientes')) {
    function bebidaVentasPendientes(PDO $conexion, int $jornadaId): array
    {
        $stmt = $conexion->prepare(
            'SELECT id, bebida_id, bebida_nombre, persona, tipo, cantidad_total,
                    cantidad_entregada, unidades_por_item, valor_unitario, created_at
             FROM bebida_ventas
             WHERE jornada_id = :jornada_id
               AND cantidad_entregada < cantidad_total * unidades_por_item
             ORDER BY created_at ASC, id ASC'
        );
        $stmt->execute([':jornada_id' => $jornadaId]);

        return array_map(static function (array $venta): array {
            $venta['id'] = (int) $venta['id'];
            $venta['bebida_id'] = (int) $venta['bebida_id'];
            $venta['cantidad_total'] = (int) $venta['cantidad_total'];
            $venta['cantidad_entregada'] = (int) $venta['cantidad_entregada'];
            $venta['unidades_por_item'] = max(1, (int) $venta['unidades_por_item']);
            $venta['pendientes'] = max(
                0,
                ($venta['cantidad_total'] * $venta['unidades_por_item']) - $venta['cantidad_entregada']
            );
            $venta['valor_unitario'] = (float) $venta['valor_unitario'];
            return $venta;
        }, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }
}

if (!function_exists('bebidaObtenerEstado')) {
    function bebidaObtenerEstado(PDO $conexion): array
    {
        $jornada = poolJornadaActiva($conexion);
        $jornadaId = $jornada ? (int) $jornada['id'] : 0;

        return [
            'jornada' => $jornada ? [
                'id' => $jornadaId,
                'estado' => $jornada['estado'],
                'fecha_apertura' => $jornada['fecha_apertura'],
            ] : null,
            'bebidas' => bebidaListar($conexion, true),
            'stats' => $jornadaId > 0
                ? bebidaStatsVivos($conexion, $jornadaId)
                : ['vendidas' => 0, 'entregadas' => 0, 'pendientes' => 0, 'montoVendido' => 0, 'personas' => 0, 'enEspera' => 0],
            'ventas' => $jornadaId > 0 ? bebidaVentasPendientes($conexion, $jornadaId) : [],
        ];
    }
}

if (!function_exists('bebidaValorJarraReferencia')) {
    /**
     * Precio de referencia de una jarra: promedio del valor de jarra de las bebidas activas.
     */
    function bebidaValorJarraReferencia(PDO $conexion): float
    {
        static $valor = null;
        if ($valor === null) {
            $valor = (float) $conexion
                ->query('SELECT COALESCE(AVG(valor_jarra), 0) FROM bebidas WHERE activa = 1 AND valor_jarra > 0')
                ->fetchColumn();
        }

        return $valor;
    }
}

if (!function_exists('bebidaResumenJornada')) {
    function bebidaResumenJornada(PDO $conexion, int $jornadaId): array
    {
        $stmt = $conexion->prepare(
            'SELECT
                bebida_id,
                bebida_nombre,
                tipo,
                COALESCE(SUM(cantidad_total * unidades_por_item), 0) AS vendidas,
                COALESCE(SUM(cantidad_entregada), 0) AS entregadas,
                COALESCE(SUM(cantidad_total * valor_unitario), 0) AS monto_vendido,
                COUNT(DISTINCT persona) AS personas
             FROM bebida_ventas
             WHERE jornada_id = :jornada_id
             GROUP BY bebida_id, bebida_nombre, tipo
             ORDER BY bebida_nombre, tipo'
        );
        $stmt->execute([':jornada_id' => $jornadaId]);
        $detalle = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $vendidas = 0;
        $entregadas = 0;
        $monto = 0.0;
        foreach ($detalle as &$fila) {
            $fila['bebida_id'] = (int) $fila['bebida_id'];
            $fila['vendidas'] = (int) $fila['vendidas'];
            $fila['entregadas'] = (int) $fila['entregadas'];
            $fila['pendientes'] = max(0, $fila['vendidas'] - $fila['entregadas']);
            $fila['monto_vendido'] = (float) $fila['monto_vendido'];
            $fila['personas'] = (int) $fila['personas'];
            $vendidas += $fila['vendidas'];
            $entregadas += $fila['entregadas'];
            $monto += $fila['monto_vendido'];
        }
        unset($fila);

        $personasStmt = $conexion->prepare(
            'SELECT COUNT(DISTINCT persona) FROM bebida_ventas WHERE jornada_id = :jornada_id'
        );
        $personasStmt->execute([':jornada_id' => $jornadaId]);
        $ajustes = poolAjustesJornada($conexion, $jornadaId);
        $ajusteVendidas = (int) ($ajustes['ajuste_bebidas_vendidas'] ?? 0);
        $vendidasAjustadas = max(0, $vendidas + $ajusteVendidas);
        // Las jarras agregadas a mano se valorizan al precio promedio de la jornada;
        // si no hubo ventas registradas, se usa el precio de jarra configurado.
        $valorPromedioJarra = $vendidas > 0 ? $monto / $vendidas : bebidaValorJarraReferencia($conexion);
        $montoAjustado = max(0, $monto + ($ajusteVendidas * $valorPromedioJarra));

        return [
            'vendidas' => $vendidasAjustadas,
            'vendidas_sin_ajuste' => $vendidas,
            'ajuste_vendidas' => $ajusteVendidas,
            'entregadas' => $entregadas,
            // El ajuste corrige lo vendido, pero no genera jarras por entregar.
            'pendientes' => max(0, $vendidas - $entregadas),
            'monto_vendido' => $montoAjustado,
            'monto_vendido_sin_ajuste' => $monto,
            'personas' => (int) $personasStmt->fetchColumn(),
            'detalle' => $detalle,
        ];
    }
}

if (!function_exists('bebidaListarJornadas')) {
    function bebidaListarJornadas(PDO $conexion, string $fechaInicio, string $fechaFin): array
    {
        $stmt = $conexion->prepare(
            "SELECT *
             FROM pool_jornadas
             WHERE fecha_apertura BETWEEN :inicio AND :fin
             ORDER BY fecha_apertura DESC, id DESC"
        );
        $stmt->execute([
            ':inicio' => $fechaInicio . ' 00:00:00',
            ':fin' => $fechaFin . ' 23:59:59',
        ]);

        return array_map(static function (array $jornada) use ($conexion): array {
            $resumen = bebidaResumenJornada($conexion, (int) $jornada['id']);
            $jornada['id'] = (int) $jornada['id'];
            $jornada['duracion_minutos'] = $jornada['duracion_minutos'] === null
                ? (int) max(0, (time() - strtotime($jornada['fecha_apertura'])) / 60)
                : (int) $jornada['duracion_minutos'];
            return array_merge($jornada, $resumen);
        }, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }
}

if (!function_exists('bebidaResumenGeneral')) {
    function bebidaResumenGeneral(array $jornadas): array
    {
        $totalJornadas = count($jornadas);
        $vendidas = array_sum(array_column($jornadas, 'vendidas'));
        return [
            'total_jornadas' => $totalJornadas,
            'total_vendidas' => $vendidas,
            'total_entregadas' => array_sum(array_column($jornadas, 'entregadas')),
            'total_pendientes' => array_sum(array_column($jornadas, 'pendientes')),
            'monto_vendido_total' => array_sum(array_column($jornadas, 'monto_vendido')),
            'personas_atendidas' => array_sum(array_column($jornadas, 'personas')),
            'promedio_por_jornada' => $totalJornadas > 0 ? round($vendidas / $totalJornadas, 2) : 0,
        ];
    }
}

if (!function_exists('bebidaMontoVendidoEntreFechas')) {
    function bebidaMontoVendidoEntreFechas(PDO $conexion, string $fechaInicio, string $fechaFin): float
    {
        $jornadas = bebidaListarJornadas($conexion, substr($fechaInicio, 0, 10), substr($fechaFin, 0, 10));
        $resumen = bebidaResumenGeneral($jornadas);

        return (float) ($resumen['monto_vendido_total'] ?? 0);
    }
}
