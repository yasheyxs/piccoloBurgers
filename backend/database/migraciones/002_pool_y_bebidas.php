<?php

/**
 * Tablas del pool (jornadas, turnos y configuración) y de la venta de bebidas.
 *
 * Antes se creaban al entrar a esas pantallas. Es PHP y no SQL porque en bases que ya
 * existían agrega solo las columnas que faltan, sin tocar lo demás.
 */

return function (PDO $conexion): void {
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
            ajuste_fichas_vendidas INT NOT NULL DEFAULT 0,
            ajuste_bebidas_vendidas INT NOT NULL DEFAULT 0,
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

    if (!piccolo_columna_existe($conexion, 'pool_jornadas', 'ajuste_fichas_vendidas')) {
        $conexion->exec('ALTER TABLE pool_jornadas ADD COLUMN ajuste_fichas_vendidas INT NOT NULL DEFAULT 0 AFTER monto_vendido_rojo');
    }

    if (!piccolo_columna_existe($conexion, 'pool_jornadas', 'ajuste_bebidas_vendidas')) {
        $conexion->exec('ALTER TABLE pool_jornadas ADD COLUMN ajuste_bebidas_vendidas INT NOT NULL DEFAULT 0 AFTER ajuste_fichas_vendidas');
    }

    $conexion->exec(
        "UPDATE pool_turnos
         SET valor_ficha = (SELECT valor_ficha FROM pool_configuracion WHERE id = 1)
         WHERE valor_ficha <= 0"
    );

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
};
