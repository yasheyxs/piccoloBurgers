<?php

declare(strict_types=1);

/**
 * Gestiona las reservas virtuales de insumos asociadas al carrito del cliente.
 */

function iniciarSesionSiEsNecesario(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function obtenerIdSesionActual(): string
{
    iniciarSesionSiEsNecesario();
    return session_id();
}

function normalizarCantidadSolicitada($valor): int
{
    if (!is_numeric($valor)) {
        return 0;
    }

    $cantidad = (int) floor((float) $valor);
    return $cantidad < 0 ? 0 : $cantidad;
}

function obtenerReservasPorSesion(PDO $conexion, string $sessionId): array
{
    $stmt = $conexion->prepare('SELECT menu_id, cantidad FROM tbl_reservas_virtuales WHERE session_id = ?');
    $stmt->execute([$sessionId]);

    $reservas = [];
    while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $menuId = (int) $fila['menu_id'];
        $reservas[(string) $menuId] = [
            'menu_id' => $menuId,
            'cantidad' => max(0, (int) $fila['cantidad']),
        ];
    }

    return $reservas;
}

function verificarDisponibilidadParaDelta(PDO $conexion, int $menuId, int $delta): void
{
    if ($delta <= 0) {
        return;
    }

    $stmt = $conexion->prepare('
        SELECT 
            mp.materia_prima_id,
            mp.cantidad AS requerido,
            mat.cantidad AS stock_actual,
            (
                SELECT COALESCE(SUM(rv.cantidad * mp2.cantidad), 0)
                FROM tbl_reservas_virtuales rv
                INNER JOIN tbl_menu_materias_primas mp2 ON mp2.menu_id = rv.menu_id
                WHERE mp2.materia_prima_id = mp.materia_prima_id
            ) AS reservado
        FROM tbl_menu_materias_primas mp
        INNER JOIN tbl_materias_primas mat ON mat.ID = mp.materia_prima_id
        WHERE mp.menu_id = ?
        FOR UPDATE
    ');
    $stmt->execute([$menuId]);
    $insumos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($insumos)) {
        throw new RuntimeException('El producto no tiene insumos configurados.');
    }

    foreach ($insumos as $insumo) {
        $requerido = (float) $insumo['requerido'];
        if ($requerido <= 0) {
            throw new RuntimeException('El producto tiene un insumo con cantidad inválida.');
        }

        $stockActual = (float) $insumo['stock_actual'];
        $reservado = (float) $insumo['reservado'];
        $disponible = $stockActual - $reservado;
        $necesario = $requerido * $delta;

        if ($disponible + 1e-8 < $necesario) {
            throw new RuntimeException('Sin stock disponible para este producto.');
        }
    }
}

function establecerReservaProducto(PDO $conexion, string $sessionId, int $menuId, int $cantidadObjetivo): array
{
    $cantidadObjetivo = max(0, $cantidadObjetivo);

    $conexion->beginTransaction();

    $stmt = $conexion->prepare('SELECT cantidad FROM tbl_reservas_virtuales WHERE session_id = ? AND menu_id = ? FOR UPDATE');
    $stmt->execute([$sessionId, $menuId]);
    $cantidadActual = (int) ($stmt->fetchColumn() ?: 0);

    $delta = $cantidadObjetivo - $cantidadActual;
    if ($delta > 0) {
        verificarDisponibilidadParaDelta($conexion, $menuId, $delta);
    }

    if ($cantidadObjetivo === 0) {
        $stmtEliminar = $conexion->prepare('DELETE FROM tbl_reservas_virtuales WHERE session_id = ? AND menu_id = ?');
        $stmtEliminar->execute([$sessionId, $menuId]);
    } else {
        $stmtUpsert = $conexion->prepare('
            INSERT INTO tbl_reservas_virtuales (session_id, menu_id, cantidad, actualizado_en)
            VALUES (?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE cantidad = VALUES(cantidad), actualizado_en = VALUES(actualizado_en)
        ');
        $stmtUpsert->execute([$sessionId, $menuId, $cantidadObjetivo]);
    }

    $conexion->commit();

    return [
        'reservas' => obtenerReservasPorSesion($conexion, $sessionId),
    ];
}

function actualizarReservaProducto(PDO $conexion, string $sessionId, int $menuId, int $delta): array
{
    if ($delta === 0) {
        return [
            'reservas' => obtenerReservasPorSesion($conexion, $sessionId),
        ];
    }

    $conexion->beginTransaction();

    $stmt = $conexion->prepare('SELECT cantidad FROM tbl_reservas_virtuales WHERE session_id = ? AND menu_id = ? FOR UPDATE');
    $stmt->execute([$sessionId, $menuId]);
    $cantidadActual = (int) ($stmt->fetchColumn() ?: 0);

    if ($delta > 0) {
        verificarDisponibilidadParaDelta($conexion, $menuId, $delta);
    }

    if ($delta < 0) {
        $delta = -min($cantidadActual, abs($delta));
    }

    $cantidadNueva = $cantidadActual + $delta;

    if ($cantidadNueva <= 0) {
        $stmtEliminar = $conexion->prepare('DELETE FROM tbl_reservas_virtuales WHERE session_id = ? AND menu_id = ?');
        $stmtEliminar->execute([$sessionId, $menuId]);
    } else {
        $stmtUpsert = $conexion->prepare('
            INSERT INTO tbl_reservas_virtuales (session_id, menu_id, cantidad, actualizado_en)
            VALUES (?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE cantidad = VALUES(cantidad), actualizado_en = VALUES(actualizado_en)
        ');
        $stmtUpsert->execute([$sessionId, $menuId, $cantidadNueva]);
    }

    $conexion->commit();

    return [
        'reservas' => obtenerReservasPorSesion($conexion, $sessionId),
    ];
}

function sincronizarReservas(PDO $conexion, string $sessionId, array $items): array
{
    $normalizado = [];
    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }
        $menuId = isset($item['id']) ? (int) $item['id'] : (int) ($item['menu_id'] ?? 0);
        if ($menuId <= 0) {
            continue;
        }
        $normalizado[$menuId] = normalizarCantidadSolicitada($item['cantidad'] ?? $item['qty'] ?? 0);
    }

    $conexion->beginTransaction();

    $stmt = $conexion->prepare('SELECT menu_id, cantidad FROM tbl_reservas_virtuales WHERE session_id = ? FOR UPDATE');
    $stmt->execute([$sessionId]);
    $actuales = [];
    while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $actuales[(int) $fila['menu_id']] = (int) $fila['cantidad'];
    }

    foreach ($actuales as $menuId => $cantidadActual) {
        $cantidadObjetivo = $normalizado[$menuId] ?? 0;
        if ($cantidadObjetivo === 0) {
            $stmtEliminar = $conexion->prepare('DELETE FROM tbl_reservas_virtuales WHERE session_id = ? AND menu_id = ?');
            $stmtEliminar->execute([$sessionId, $menuId]);
        }
    }

    foreach ($normalizado as $menuId => $cantidadObjetivo) {
        $cantidadObjetivo = max(0, $cantidadObjetivo);
        $cantidadActual = $actuales[$menuId] ?? 0;
        if ($cantidadObjetivo === $cantidadActual) {
            continue;
        }

        $delta = $cantidadObjetivo - $cantidadActual;
        if ($delta > 0) {
            verificarDisponibilidadParaDelta($conexion, $menuId, $delta);
        }

        if ($cantidadObjetivo === 0) {
            $stmtEliminar = $conexion->prepare('DELETE FROM tbl_reservas_virtuales WHERE session_id = ? AND menu_id = ?');
            $stmtEliminar->execute([$sessionId, $menuId]);
        } else {
            $stmtUpsert = $conexion->prepare('
                INSERT INTO tbl_reservas_virtuales (session_id, menu_id, cantidad, actualizado_en)
                VALUES (?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE cantidad = VALUES(cantidad), actualizado_en = VALUES(actualizado_en)
            ');
            $stmtUpsert->execute([$sessionId, $menuId, $cantidadObjetivo]);
        }
    }

    $conexion->commit();

    return [
        'reservas' => obtenerReservasPorSesion($conexion, $sessionId),
    ];
}

function limpiarReservasSesion(PDO $conexion, string $sessionId): array
{
    $stmt = $conexion->prepare('DELETE FROM tbl_reservas_virtuales WHERE session_id = ?');
    $stmt->execute([$sessionId]);

    return [
        'reservas' => [],
    ];
}

function obtenerDisponibilidadMenu(PDO $conexion, ?array $menuIds = null): array
{
    $parametros = [];
    $filtroMenus = '';

    if (is_array($menuIds) && count($menuIds) > 0) {
        $menuIds = array_values(array_unique(array_map('intval', $menuIds)));
        $placeholders = implode(',', array_fill(0, count($menuIds), '?'));
        $filtroMenus = 'WHERE m.ID IN (' . $placeholders . ')';
        $parametros = $menuIds;
    }

    $sql = '
        WITH reservas_insumo AS (
            SELECT 
                mp.materia_prima_id,
                SUM(rv.cantidad * mp.cantidad) AS reservado
            FROM tbl_reservas_virtuales rv
            INNER JOIN tbl_menu_materias_primas mp ON mp.menu_id = rv.menu_id
            GROUP BY mp.materia_prima_id
        ),
        disponibilidad AS (
            SELECT 
                m.ID AS menu_id,
                MIN(
                    FLOOR(
                        CASE 
                            WHEN mp.cantidad <= 0 THEN 0
                            ELSE GREATEST(mat.cantidad - COALESCE(reservas_insumo.reservado, 0), 0) / mp.cantidad
                        END
                    )
                ) AS unidades_disponibles
            FROM tbl_menu m
            INNER JOIN tbl_menu_materias_primas mp ON mp.menu_id = m.ID
            INNER JOIN tbl_materias_primas mat ON mat.ID = mp.materia_prima_id
            LEFT JOIN reservas_insumo ON reservas_insumo.materia_prima_id = mp.materia_prima_id
            GROUP BY m.ID
        )
        SELECT 
            m.ID AS menu_id,
            COALESCE(disponibilidad.unidades_disponibles, 0) AS unidades_disponibles
        FROM tbl_menu m
        LEFT JOIN disponibilidad ON disponibilidad.menu_id = m.ID
        ' . $filtroMenus;

    $stmt = $conexion->prepare($sql);
    $stmt->execute($parametros);

    $resultado = [];
    while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $menuId = (int) $fila['menu_id'];
        $unidades = max(0, (int) $fila['unidades_disponibles']);
        $resultado[(string) $menuId] = [
            'menu_id' => $menuId,
            'unidades_disponibles' => $unidades,
            'disponible' => $unidades > 0,
        ];
    }

    return $resultado;
}