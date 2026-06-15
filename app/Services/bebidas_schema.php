<?php

require_once __DIR__ . '/pool_schema.php';

if (!function_exists('asegurarTablasBebidas')) {
    function asegurarTablasBebidas(PDO $conexion): void
    {
        asegurarTablaPoolTurnos($conexion);

        $conexion->exec(
            "CREATE TABLE IF NOT EXISTS bebidas (
                id INT NOT NULL AUTO_INCREMENT,
                nombre VARCHAR(100) NOT NULL,
                valor_jarra DECIMAL(10,2) NOT NULL DEFAULT 0,
                valor_promo DECIMAL(10,2) NOT NULL DEFAULT 0,
                jarras_por_promo SMALLINT UNSIGNED NOT NULL DEFAULT 3,
                activa TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_bebidas_nombre (nombre),
                INDEX idx_bebidas_activa_nombre (activa, nombre)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
        );

        $conexion->exec(
            "CREATE TABLE IF NOT EXISTS bebida_ventas (
                id INT NOT NULL AUTO_INCREMENT,
                jornada_id INT NOT NULL,
                bebida_id INT NOT NULL,
                bebida_nombre VARCHAR(100) NOT NULL,
                persona VARCHAR(120) NOT NULL,
                tipo ENUM('jarra','promo') NOT NULL,
                cantidad_total SMALLINT UNSIGNED NOT NULL,
                cantidad_entregada SMALLINT UNSIGNED NOT NULL DEFAULT 0,
                unidades_por_item SMALLINT UNSIGNED NOT NULL DEFAULT 1,
                valor_unitario DECIMAL(10,2) NOT NULL,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                INDEX idx_bebida_ventas_jornada_estado (jornada_id, cantidad_entregada, cantidad_total),
                INDEX idx_bebida_ventas_bebida (bebida_id),
                CONSTRAINT fk_bebida_ventas_jornada
                    FOREIGN KEY (jornada_id) REFERENCES pool_jornadas(id)
                    ON DELETE RESTRICT,
                CONSTRAINT fk_bebida_ventas_bebida
                    FOREIGN KEY (bebida_id) REFERENCES bebidas(id)
                    ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
        );

        if (!piccolo_columna_existe($conexion, 'bebidas', 'jarras_por_promo')) {
            $conexion->exec(
                'ALTER TABLE bebidas
                 ADD COLUMN jarras_por_promo SMALLINT UNSIGNED NOT NULL DEFAULT 3 AFTER valor_promo'
            );
        } else {
            $defaultStmt = $conexion->prepare(
                'SELECT COLUMN_DEFAULT
                 FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = :tabla
                   AND COLUMN_NAME = :columna'
            );
            $defaultStmt->execute([
                ':tabla' => 'bebidas',
                ':columna' => 'jarras_por_promo',
            ]);

            if ((string) $defaultStmt->fetchColumn() === '1') {
                $conexion->exec('UPDATE bebidas SET jarras_por_promo = 3 WHERE jarras_por_promo = 1');
                $conexion->exec(
                    'ALTER TABLE bebidas
                     MODIFY COLUMN jarras_por_promo SMALLINT UNSIGNED NOT NULL DEFAULT 3'
                );
            }
        }

        if (!piccolo_columna_existe($conexion, 'bebida_ventas', 'unidades_por_item')) {
            $conexion->exec(
                'ALTER TABLE bebida_ventas
                 ADD COLUMN unidades_por_item SMALLINT UNSIGNED NOT NULL DEFAULT 1 AFTER cantidad_entregada'
            );
        }
    }
}

if (!function_exists('bebidaNormalizarTipo')) {
    function bebidaNormalizarTipo(string $tipo): string
    {
        return $tipo === 'promo' ? 'promo' : 'jarra';
    }
}

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

if (!function_exists('bebidaObtener')) {
    function bebidaObtener(PDO $conexion, int $id): ?array
    {
        $stmt = $conexion->prepare('SELECT * FROM bebidas WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $bebida = $stmt->fetch(PDO::FETCH_ASSOC);
        return $bebida ?: null;
    }
}

if (!function_exists('bebidaGuardar')) {
    function bebidaGuardar(
        PDO $conexion,
        int $id,
        string $nombre,
        float $valorJarra,
        float $valorPromo,
        int $jarrasPorPromo,
        bool $activa
    ): int {
        $nombre = trim($nombre);
        if ($nombre === '') {
            throw new RuntimeException('Ingresa el nombre de la bebida.');
        }
        if ($valorJarra <= 0 || $valorPromo <= 0) {
            throw new RuntimeException('Los valores de jarra y promo deben ser mayores a 0.');
        }
        if ($jarrasPorPromo < 1 || $jarrasPorPromo > 99) {
            throw new RuntimeException('La cantidad de jarras por promoción debe estar entre 1 y 99.');
        }

        if ($id > 0) {
            $stmt = $conexion->prepare(
                'UPDATE bebidas
                 SET nombre = :nombre, valor_jarra = :valor_jarra,
                     valor_promo = :valor_promo, jarras_por_promo = :jarras_por_promo,
                     activa = :activa
                 WHERE id = :id'
            );
            $stmt->execute([
                ':nombre' => $nombre,
                ':valor_jarra' => round($valorJarra, 2),
                ':valor_promo' => round($valorPromo, 2),
                ':jarras_por_promo' => $jarrasPorPromo,
                ':activa' => $activa ? 1 : 0,
                ':id' => $id,
            ]);
            return $id;
        }

        $stmt = $conexion->prepare(
            'INSERT INTO bebidas (nombre, valor_jarra, valor_promo, jarras_por_promo, activa)
             VALUES (:nombre, :valor_jarra, :valor_promo, :jarras_por_promo, :activa)'
        );
        $stmt->execute([
            ':nombre' => $nombre,
            ':valor_jarra' => round($valorJarra, 2),
            ':valor_promo' => round($valorPromo, 2),
            ':jarras_por_promo' => $jarrasPorPromo,
            ':activa' => $activa ? 1 : 0,
        ]);

        return (int) $conexion->lastInsertId();
    }
}

if (!function_exists('bebidaRegistrarVenta')) {
    function bebidaRegistrarVenta(
        PDO $conexion,
        int $jornadaId,
        int $bebidaId,
        string $persona,
        string $tipo,
        int $cantidad
    ): void {
        $persona = trim($persona);
        $tipo = bebidaNormalizarTipo($tipo);

        if ($persona === '') {
            throw new RuntimeException('Ingresa una persona o identificación.');
        }
        if ($cantidad < 1 || $cantidad > 99) {
            throw new RuntimeException('Ingresa una cantidad válida entre 1 y 99.');
        }

        $bebida = bebidaObtener($conexion, $bebidaId);
        if (!$bebida || !(bool) $bebida['activa']) {
            throw new RuntimeException('La bebida seleccionada no esta disponible.');
        }

        $valor = $tipo === 'promo' ? (float) $bebida['valor_promo'] : (float) $bebida['valor_jarra'];
        $unidadesPorItem = $tipo === 'promo' ? max(1, (int) $bebida['jarras_por_promo']) : 1;
        $stmt = $conexion->prepare(
            'INSERT INTO bebida_ventas
                (jornada_id, bebida_id, bebida_nombre, persona, tipo, cantidad_total,
                 cantidad_entregada, unidades_por_item, valor_unitario)
             VALUES
                (:jornada_id, :bebida_id, :bebida_nombre, :persona, :tipo, :cantidad,
                 0, :unidades_por_item, :valor_unitario)'
        );
        $stmt->execute([
            ':jornada_id' => $jornadaId,
            ':bebida_id' => $bebidaId,
            ':bebida_nombre' => $bebida['nombre'],
            ':persona' => $persona,
            ':tipo' => $tipo,
            ':cantidad' => $cantidad,
            ':unidades_por_item' => $unidadesPorItem,
            ':valor_unitario' => $valor,
        ]);
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

        return [
            'vendidas' => $vendidas,
            'entregadas' => $entregadas,
            'pendientes' => max(0, $vendidas - $entregadas),
            'monto_vendido' => $monto,
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
        asegurarTablasBebidas($conexion);
        $stmt = $conexion->prepare(
            'SELECT COALESCE(SUM(v.cantidad_total * v.valor_unitario), 0)
             FROM bebida_ventas v
             INNER JOIN pool_jornadas j ON j.id = v.jornada_id
             WHERE j.fecha_apertura BETWEEN :inicio AND :fin'
        );
        $stmt->execute([
            ':inicio' => substr($fechaInicio, 0, 10) . ' 00:00:00',
            ':fin' => substr($fechaFin, 0, 10) . ' 23:59:59',
        ]);
        return (float) $stmt->fetchColumn();
    }
}
