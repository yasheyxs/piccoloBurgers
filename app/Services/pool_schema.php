<?php

if (!function_exists('asegurarTablaPoolTurnos')) {
    function asegurarTablaPoolTurnos(PDO $conexion): void
    {
        $conexion->exec(
            "CREATE TABLE IF NOT EXISTS pool_jornadas (
                id INT NOT NULL AUTO_INCREMENT,
                estado ENUM('abierta','cerrada') NOT NULL DEFAULT 'abierta',
                fecha_apertura DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                fecha_cierre DATETIME NULL,
                fichas_vendidas_azul INT UNSIGNED NOT NULL DEFAULT 0,
                fichas_vendidas_rojo INT UNSIGNED NOT NULL DEFAULT 0,
                fichas_consumidas_azul INT UNSIGNED NOT NULL DEFAULT 0,
                fichas_consumidas_rojo INT UNSIGNED NOT NULL DEFAULT 0,
                fichas_pendientes_azul INT UNSIGNED NOT NULL DEFAULT 0,
                fichas_pendientes_rojo INT UNSIGNED NOT NULL DEFAULT 0,
                monto_vendido_azul DECIMAL(12,2) NOT NULL DEFAULT 0,
                monto_vendido_rojo DECIMAL(12,2) NOT NULL DEFAULT 0,
                jugadores_atendidos INT UNSIGNED NOT NULL DEFAULT 0,
                turnos_completados INT UNSIGNED NOT NULL DEFAULT 0,
                duracion_minutos INT UNSIGNED NULL,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                INDEX idx_pool_jornadas_estado (estado),
                INDEX idx_pool_jornadas_apertura (fecha_apertura)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
        );

        $conexion->exec(
            "CREATE TABLE IF NOT EXISTS pool_turnos (
                id INT NOT NULL AUTO_INCREMENT,
                jornada_id INT NULL,
                pool ENUM('azul','rojo') NOT NULL,
                nombre VARCHAR(120) NOT NULL,
                fichas_total TINYINT UNSIGNED NOT NULL,
                fichas_consumidas TINYINT UNSIGNED NOT NULL DEFAULT 0,
                valor_ficha DECIMAL(10,2) NOT NULL DEFAULT 0,
                orden INT NOT NULL DEFAULT 0,
                jornada_fecha DATE NOT NULL,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                INDEX idx_pool_jornada_orden (pool, jornada_fecha, orden),
                INDEX idx_pool_jornada_consumo (pool, jornada_fecha, fichas_consumidas, fichas_total),
                INDEX idx_pool_turnos_jornada_pool (jornada_id, pool, orden),
                CONSTRAINT fk_pool_turnos_jornada
                    FOREIGN KEY (jornada_id) REFERENCES pool_jornadas(id)
                    ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
        );

        $conexion->exec(
            "CREATE TABLE IF NOT EXISTS pool_configuracion (
                id TINYINT UNSIGNED NOT NULL,
                max_fichas_por_registro INT UNSIGNED NOT NULL DEFAULT 3,
                valor_ficha DECIMAL(10,2) NOT NULL DEFAULT 1000,
                actualizado_en TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
        );

        $conexion->exec(
            "INSERT IGNORE INTO pool_configuracion (id, max_fichas_por_registro, valor_ficha)
             VALUES (1, 3, 1000)"
        );

        if (!piccolo_columna_existe($conexion, 'pool_turnos', 'jornada_id')) {
            $conexion->exec('ALTER TABLE pool_turnos ADD COLUMN jornada_id INT NULL AFTER id');
            $conexion->exec('ALTER TABLE pool_turnos ADD INDEX idx_pool_turnos_jornada_pool (jornada_id, pool, orden)');
        }

        if (!piccolo_columna_existe($conexion, 'pool_turnos', 'valor_ficha')) {
            $conexion->exec('ALTER TABLE pool_turnos ADD COLUMN valor_ficha DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER fichas_consumidas');
        }

        if (!piccolo_columna_existe($conexion, 'pool_jornadas', 'monto_vendido_azul')) {
            $conexion->exec('ALTER TABLE pool_jornadas ADD COLUMN monto_vendido_azul DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER fichas_pendientes_rojo');
        }

        if (!piccolo_columna_existe($conexion, 'pool_jornadas', 'monto_vendido_rojo')) {
            $conexion->exec('ALTER TABLE pool_jornadas ADD COLUMN monto_vendido_rojo DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER monto_vendido_azul');
        }

        $conexion->exec(
            "UPDATE pool_turnos
             SET valor_ficha = (SELECT valor_ficha FROM pool_configuracion WHERE id = 1)
             WHERE valor_ficha <= 0"
        );
    }
}

if (!function_exists('poolObtenerConfiguracion')) {
    function poolObtenerConfiguracion(PDO $conexion): array
    {
        $stmt = $conexion->query('SELECT * FROM pool_configuracion WHERE id = 1');
        $configuracion = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$configuracion) {
            $conexion->exec("INSERT INTO pool_configuracion (id, max_fichas_por_registro, valor_ficha) VALUES (1, 3, 1000)");
            $configuracion = [
                'max_fichas_por_registro' => 3,
                'valor_ficha' => 1000,
                'actualizado_en' => null,
            ];
        }

        return [
            'max_fichas_por_registro' => max(1, (int) ($configuracion['max_fichas_por_registro'] ?? 3)),
            'valor_ficha' => max(0.01, (float) ($configuracion['valor_ficha'] ?? 1000)),
            'actualizado_en' => $configuracion['actualizado_en'] ?? null,
        ];
    }
}

if (!function_exists('poolActualizarConfiguracion')) {
    function poolActualizarConfiguracion(PDO $conexion, int $maxFichasPorRegistro, float $valorFicha): void
    {
        $stmt = $conexion->prepare(
            'UPDATE pool_configuracion
             SET max_fichas_por_registro = :max_fichas_por_registro,
                 valor_ficha = :valor_ficha
             WHERE id = 1'
        );
        $stmt->execute([
            ':max_fichas_por_registro' => max(1, $maxFichasPorRegistro),
            ':valor_ficha' => round(max(0.01, $valorFicha), 2),
        ]);
    }
}

if (!function_exists('poolFechaJornadaActual')) {
    function poolFechaJornadaActual(): string
    {
        return date('Y-m-d');
    }
}

if (!function_exists('poolNormalizar')) {
    function poolNormalizar(string $pool): string
    {
        return $pool === 'rojo' ? 'rojo' : 'azul';
    }
}

if (!function_exists('poolFechaDbAFechaApp')) {
    function poolFechaDbAFechaApp(?string $fecha): ?DateTimeImmutable
    {
        if (!$fecha) {
            return null;
        }

        $dbTimezoneId = getenv('DB_TIMEZONE') ?: getenv('APP_TIMEZONE') ?: 'America/Argentina/Buenos_Aires';
        $appTimezoneId = getenv('APP_TIMEZONE') ?: 'America/Argentina/Buenos_Aires';

        try {
            $dbTimezone = new DateTimeZone($dbTimezoneId);
        } catch (Exception $e) {
            $dbTimezone = new DateTimeZone('America/Argentina/Buenos_Aires');
        }

        try {
            $appTimezone = new DateTimeZone($appTimezoneId);
        } catch (Exception $e) {
            $appTimezone = $dbTimezone;
        }

        try {
            return (new DateTimeImmutable($fecha, $dbTimezone))->setTimezone($appTimezone);
        } catch (Exception $e) {
            return null;
        }
    }
}

if (!function_exists('poolJornadaActiva')) {
    function poolJornadaActiva(PDO $conexion): ?array
    {
        $stmt = $conexion->query(
            "SELECT *
             FROM pool_jornadas
             WHERE estado = 'abierta'
             ORDER BY fecha_apertura DESC, id DESC
             LIMIT 1"
        );
        $jornada = $stmt->fetch(PDO::FETCH_ASSOC);
        return $jornada ?: null;
    }
}

if (!function_exists('poolAbrirJornada')) {
    function poolAbrirJornada(PDO $conexion): array
    {
        $activa = poolJornadaActiva($conexion);
        if ($activa) {
            throw new RuntimeException('Ya existe una jornada activa.');
        }

        $conexion->prepare("INSERT INTO pool_jornadas (estado, fecha_apertura) VALUES ('abierta', NOW())")->execute();
        $id = (int) $conexion->lastInsertId();

        $stmt = $conexion->prepare('SELECT * FROM pool_jornadas WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }
}

if (!function_exists('poolCalcularResumenJornada')) {
    function poolCalcularResumenJornada(PDO $conexion, int $jornadaId): array
    {
        $stmt = $conexion->prepare(
            'SELECT
                pool,
                COALESCE(SUM(fichas_total), 0) AS vendidas,
                COALESCE(SUM(fichas_consumidas), 0) AS consumidas,
                COALESCE(SUM(fichas_total * valor_ficha), 0) AS monto_vendido,
                SUM(CASE WHEN fichas_consumidas >= fichas_total THEN 1 ELSE 0 END) AS completados
             FROM pool_turnos
             WHERE jornada_id = :jornada_id
             GROUP BY pool'
        );
        $stmt->execute([':jornada_id' => $jornadaId]);

        $resumen = [
            'azul' => ['vendidas' => 0, 'consumidas' => 0, 'pendientes' => 0, 'completados' => 0, 'monto_vendido' => 0.0],
            'rojo' => ['vendidas' => 0, 'consumidas' => 0, 'pendientes' => 0, 'completados' => 0, 'monto_vendido' => 0.0],
        ];

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $pool = poolNormalizar((string) $row['pool']);
            $vendidas = (int) ($row['vendidas'] ?? 0);
            $consumidas = (int) ($row['consumidas'] ?? 0);
            $resumen[$pool] = [
                'vendidas' => $vendidas,
                'consumidas' => $consumidas,
                'pendientes' => max(0, $vendidas - $consumidas),
                'completados' => (int) ($row['completados'] ?? 0),
                'monto_vendido' => (float) ($row['monto_vendido'] ?? 0),
            ];
        }

        $jugadoresStmt = $conexion->prepare('SELECT COUNT(DISTINCT nombre) FROM pool_turnos WHERE jornada_id = :jornada_id');
        $jugadoresStmt->execute([':jornada_id' => $jornadaId]);

        return [
            'fichas_vendidas_azul' => $resumen['azul']['vendidas'],
            'fichas_vendidas_rojo' => $resumen['rojo']['vendidas'],
            'fichas_consumidas_azul' => $resumen['azul']['consumidas'],
            'fichas_consumidas_rojo' => $resumen['rojo']['consumidas'],
            'fichas_pendientes_azul' => $resumen['azul']['pendientes'],
            'fichas_pendientes_rojo' => $resumen['rojo']['pendientes'],
            'monto_vendido_azul' => $resumen['azul']['monto_vendido'],
            'monto_vendido_rojo' => $resumen['rojo']['monto_vendido'],
            'jugadores_atendidos' => (int) $jugadoresStmt->fetchColumn(),
            'turnos_completados' => $resumen['azul']['completados'] + $resumen['rojo']['completados'],
        ];
    }
}

if (!function_exists('poolCerrarJornada')) {
    function poolCerrarJornada(PDO $conexion): array
    {
        $activa = poolJornadaActiva($conexion);
        if (!$activa) {
            throw new RuntimeException('No hay una jornada activa para cerrar.');
        }

        $jornadaId = (int) $activa['id'];
        $resumen = poolCalcularResumenJornada($conexion, $jornadaId);

        $stmt = $conexion->prepare(
            "UPDATE pool_jornadas
             SET estado = 'cerrada',
                 fecha_cierre = NOW(),
                 fichas_vendidas_azul = :fichas_vendidas_azul,
                 fichas_vendidas_rojo = :fichas_vendidas_rojo,
                 fichas_consumidas_azul = :fichas_consumidas_azul,
                 fichas_consumidas_rojo = :fichas_consumidas_rojo,
                 fichas_pendientes_azul = :fichas_pendientes_azul,
                 fichas_pendientes_rojo = :fichas_pendientes_rojo,
                 monto_vendido_azul = :monto_vendido_azul,
                 monto_vendido_rojo = :monto_vendido_rojo,
                 jugadores_atendidos = :jugadores_atendidos,
                 turnos_completados = :turnos_completados,
                 duracion_minutos = TIMESTAMPDIFF(MINUTE, fecha_apertura, NOW())
             WHERE id = :id AND estado = 'abierta'"
        );
        $stmt->execute([
            ':fichas_vendidas_azul' => $resumen['fichas_vendidas_azul'],
            ':fichas_vendidas_rojo' => $resumen['fichas_vendidas_rojo'],
            ':fichas_consumidas_azul' => $resumen['fichas_consumidas_azul'],
            ':fichas_consumidas_rojo' => $resumen['fichas_consumidas_rojo'],
            ':fichas_pendientes_azul' => $resumen['fichas_pendientes_azul'],
            ':fichas_pendientes_rojo' => $resumen['fichas_pendientes_rojo'],
            ':monto_vendido_azul' => $resumen['monto_vendido_azul'],
            ':monto_vendido_rojo' => $resumen['monto_vendido_rojo'],
            ':jugadores_atendidos' => $resumen['jugadores_atendidos'],
            ':turnos_completados' => $resumen['turnos_completados'],
            ':id' => $jornadaId,
        ]);

        return poolObtenerJornadaPorId($conexion, $jornadaId) ?? [];
    }
}

if (!function_exists('poolObtenerJornadaPorId')) {
    function poolObtenerJornadaPorId(PDO $conexion, int $jornadaId): ?array
    {
        $stmt = $conexion->prepare('SELECT * FROM pool_jornadas WHERE id = :id');
        $stmt->execute([':id' => $jornadaId]);
        $jornada = $stmt->fetch(PDO::FETCH_ASSOC);
        return $jornada ?: null;
    }
}

if (!function_exists('poolSiguienteOrden')) {
    function poolSiguienteOrden(PDO $conexion, string $pool, int $jornadaId): int
    {
        $stmt = $conexion->prepare(
            'SELECT COALESCE(MAX(orden), 0) + 1 FROM pool_turnos WHERE pool = :pool AND jornada_id = :jornada_id'
        );
        $stmt->execute([
            ':pool' => poolNormalizar($pool),
            ':jornada_id' => $jornadaId,
        ]);

        return (int) $stmt->fetchColumn();
    }
}

if (!function_exists('poolStatsVivos')) {
    function poolStatsVivos(PDO $conexion, int $jornadaId, string $pool): array
    {
        $stmt = $conexion->prepare(
            'SELECT
                COALESCE(SUM(fichas_total), 0) AS vendidas,
                COALESCE(SUM(fichas_consumidas), 0) AS consumidas,
                COALESCE(SUM(fichas_total * valor_ficha), 0) AS monto_vendido,
                SUM(CASE WHEN fichas_consumidas < fichas_total THEN 1 ELSE 0 END) AS en_espera,
                SUM(CASE WHEN fichas_consumidas >= fichas_total THEN 1 ELSE 0 END) AS completados
             FROM pool_turnos
             WHERE pool = :pool AND jornada_id = :jornada_id'
        );
        $stmt->execute([
            ':pool' => $pool,
            ':jornada_id' => $jornadaId,
        ]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $vendidas = (int) ($stats['vendidas'] ?? 0);
        $consumidas = (int) ($stats['consumidas'] ?? 0);

        return [
            'vendidas' => $vendidas,
            'consumidas' => $consumidas,
            'pendientes' => max(0, $vendidas - $consumidas),
            'montoVendido' => (float) ($stats['monto_vendido'] ?? 0),
            'enEspera' => (int) ($stats['en_espera'] ?? 0),
            'completados' => (int) ($stats['completados'] ?? 0),
        ];
    }
}

if (!function_exists('poolTurnosPendientes')) {
    function poolTurnosPendientes(PDO $conexion, int $jornadaId, string $pool): array
    {
        $turnosStmt = $conexion->prepare(
            'SELECT id, pool, nombre, fichas_total, fichas_consumidas, orden, created_at
             FROM pool_turnos
             WHERE pool = :pool
               AND jornada_id = :jornada_id
               AND fichas_consumidas < fichas_total
             ORDER BY orden ASC, id ASC'
        );
        $turnosStmt->execute([
            ':pool' => $pool,
            ':jornada_id' => $jornadaId,
        ]);

        return array_map(static function (array $turno): array {
            $turno['id'] = (int) $turno['id'];
            $turno['fichas_total'] = (int) $turno['fichas_total'];
            $turno['fichas_consumidas'] = (int) $turno['fichas_consumidas'];
            $turno['orden'] = (int) $turno['orden'];
            return $turno;
        }, $turnosStmt->fetchAll(PDO::FETCH_ASSOC));
    }
}

if (!function_exists('poolObtenerEstado')) {
    function poolObtenerEstado(PDO $conexion): array
    {
        $jornada = poolJornadaActiva($conexion);
        $jornadaId = $jornada ? (int) $jornada['id'] : 0;
        $estado = [];

        foreach (['azul', 'rojo'] as $pool) {
            $estado[$pool] = [
                'stats' => $jornadaId > 0
                    ? poolStatsVivos($conexion, $jornadaId, $pool)
                    : ['vendidas' => 0, 'consumidas' => 0, 'pendientes' => 0, 'montoVendido' => 0, 'enEspera' => 0, 'completados' => 0],
                'turnos' => $jornadaId > 0 ? poolTurnosPendientes($conexion, $jornadaId, $pool) : [],
            ];
        }

        return [
            'fecha' => poolFechaJornadaActual(),
            'jornada' => $jornada ? [
                'id' => (int) $jornada['id'],
                'estado' => $jornada['estado'],
                'fecha_apertura' => $jornada['fecha_apertura'],
            ] : null,
            'pools' => $estado,
        ];
    }
}

if (!function_exists('poolListarJornadas')) {
    function poolListarJornadas(PDO $conexion, string $fechaInicio, string $fechaFin): array
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
            if (
                ($jornada['estado'] ?? '') === 'abierta'
                || $jornada['duracion_minutos'] === null
                || (
                    (float) ($jornada['monto_vendido_azul'] ?? 0) <= 0
                    && (float) ($jornada['monto_vendido_rojo'] ?? 0) <= 0
                )
            ) {
                $resumen = poolCalcularResumenJornada($conexion, (int) $jornada['id']);
                $jornada = array_merge($jornada, $resumen);

                $duracionStmt = $conexion->prepare(
                    "SELECT GREATEST(
            0,
            TIMESTAMPDIFF(MINUTE, fecha_apertura, COALESCE(fecha_cierre, NOW()))
        )
        FROM pool_jornadas
        WHERE id = :id"
                );

                $duracionStmt->execute([':id' => (int) $jornada['id']]);
                $jornada['duracion_minutos'] = (int) $duracionStmt->fetchColumn();
            }

            return poolNormalizarJornadaEstadistica($jornada);
        }, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }
}

if (!function_exists('poolNormalizarJornadaEstadistica')) {
    function poolNormalizarJornadaEstadistica(array $jornada): array
    {
        $vendidasAzul = (int) ($jornada['fichas_vendidas_azul'] ?? 0);
        $vendidasRojo = (int) ($jornada['fichas_vendidas_rojo'] ?? 0);
        $consumidasAzul = (int) ($jornada['fichas_consumidas_azul'] ?? 0);
        $consumidasRojo = (int) ($jornada['fichas_consumidas_rojo'] ?? 0);
        $pendientesAzul = (int) ($jornada['fichas_pendientes_azul'] ?? 0);
        $pendientesRojo = (int) ($jornada['fichas_pendientes_rojo'] ?? 0);
        $montoAzul = (float) ($jornada['monto_vendido_azul'] ?? 0);
        $montoRojo = (float) ($jornada['monto_vendido_rojo'] ?? 0);

        $jornada['id'] = (int) ($jornada['id'] ?? 0);
        $jornada['fichas_vendidas_azul'] = $vendidasAzul;
        $jornada['fichas_vendidas_rojo'] = $vendidasRojo;
        $jornada['fichas_consumidas_azul'] = $consumidasAzul;
        $jornada['fichas_consumidas_rojo'] = $consumidasRojo;
        $jornada['fichas_pendientes_azul'] = $pendientesAzul;
        $jornada['fichas_pendientes_rojo'] = $pendientesRojo;
        $jornada['monto_vendido_azul'] = $montoAzul;
        $jornada['monto_vendido_rojo'] = $montoRojo;
        $jornada['total_vendidas'] = $vendidasAzul + $vendidasRojo;
        $jornada['total_consumidas'] = $consumidasAzul + $consumidasRojo;
        $jornada['total_pendientes'] = $pendientesAzul + $pendientesRojo;
        $jornada['monto_vendido_total'] = $montoAzul + $montoRojo;
        $jornada['jugadores_atendidos'] = (int) ($jornada['jugadores_atendidos'] ?? 0);
        $jornada['turnos_completados'] = (int) ($jornada['turnos_completados'] ?? 0);
        $jornada['duracion_minutos'] = $jornada['duracion_minutos'] === null ? null : (int) $jornada['duracion_minutos'];

        return $jornada;
    }
}

if (!function_exists('poolResumenGeneral')) {
    function poolResumenGeneral(array $jornadas): array
    {
        $totalJornadas = count($jornadas);
        $vendidas = array_sum(array_column($jornadas, 'total_vendidas'));
        $consumidas = array_sum(array_column($jornadas, 'total_consumidas'));
        $pendientes = array_sum(array_column($jornadas, 'total_pendientes'));
        $vendidasAzul = array_sum(array_column($jornadas, 'fichas_vendidas_azul'));
        $vendidasRojo = array_sum(array_column($jornadas, 'fichas_vendidas_rojo'));
        $consumidasAzul = array_sum(array_column($jornadas, 'fichas_consumidas_azul'));
        $consumidasRojo = array_sum(array_column($jornadas, 'fichas_consumidas_rojo'));
        $montoAzul = array_sum(array_column($jornadas, 'monto_vendido_azul'));
        $montoRojo = array_sum(array_column($jornadas, 'monto_vendido_rojo'));

        return [
            'total_jornadas' => $totalJornadas,
            'total_vendidas' => $vendidas,
            'total_consumidas' => $consumidas,
            'total_pendientes' => $pendientes,
            'promedio_fichas_jornada' => $totalJornadas > 0 ? round($vendidas / $totalJornadas, 2) : 0,
            'vendidas_azul' => $vendidasAzul,
            'vendidas_rojo' => $vendidasRojo,
            'consumidas_azul' => $consumidasAzul,
            'consumidas_rojo' => $consumidasRojo,
            'monto_vendido_total' => $montoAzul + $montoRojo,
            'monto_vendido_azul' => $montoAzul,
            'monto_vendido_rojo' => $montoRojo,
            'jugadores_atendidos' => array_sum(array_column($jornadas, 'jugadores_atendidos')),
            'turnos_completados' => array_sum(array_column($jornadas, 'turnos_completados')),
        ];
    }
}

if (!function_exists('poolMontoVendidoEntreFechas')) {
    function poolMontoVendidoEntreFechas(PDO $conexion, string $fechaInicio, string $fechaFin): float
    {
        asegurarTablaPoolTurnos($conexion);
        $jornadas = poolListarJornadas($conexion, substr($fechaInicio, 0, 10), substr($fechaFin, 0, 10));
        $resumen = poolResumenGeneral($jornadas);

        return (float) ($resumen['monto_vendido_total'] ?? 0);
    }
}
