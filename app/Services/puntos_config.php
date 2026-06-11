<?php

declare(strict_types=1);

/**
 * Crea la tabla de configuración de puntos si no existe y garantiza un registro base.
 */
function asegurarTablaConfiguracionPuntos(PDO $conexion): void
{
    $sqlCrearTabla = <<<'SQL'
        CREATE TABLE IF NOT EXISTS tbl_configuracion_puntos (
            id INT NOT NULL PRIMARY KEY,
            minimo_puntos INT NOT NULL,
            valor_punto DECIMAL(10,2) NOT NULL,
            maximo_porcentaje DECIMAL(5,4) NOT NULL,
            actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    SQL;

    $conexion->exec($sqlCrearTabla);

    $stmt = $conexion->prepare('SELECT COUNT(*) FROM tbl_configuracion_puntos WHERE id = 1');
    $stmt->execute();

    if ((int) $stmt->fetchColumn() === 0) {
        $insert = $conexion->prepare(
            'INSERT INTO tbl_configuracion_puntos (id, minimo_puntos, valor_punto, maximo_porcentaje)
             VALUES (1, :minimo, :valor, :maximo)'
        );
        $insert->execute([
            ':minimo' => 50,
            ':valor'  => 20,
            ':maximo' => 0.25,
        ]);
    }
}

/**
 * Obtiene la configuración del sistema de puntos desde la base de datos.
 */
function obtenerConfiguracionPuntos(PDO $conexion): array
{
    asegurarTablaConfiguracionPuntos($conexion);

    $stmt = $conexion->prepare(
        'SELECT minimo_puntos, valor_punto, maximo_porcentaje, actualizado_en
         FROM tbl_configuracion_puntos WHERE id = 1 LIMIT 1'
    );
    $stmt->execute();
    $config = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($config === false) {
        return [
            'minimo_puntos'      => 50,
            'valor_punto'        => 20.0,
            'maximo_porcentaje'  => 0.25,
            'actualizado_en'     => null,
        ];
    }

    return [
        'minimo_puntos'      => (int) $config['minimo_puntos'],
        'valor_punto'        => (float) $config['valor_punto'],
        'maximo_porcentaje'  => (float) $config['maximo_porcentaje'],
        'actualizado_en'     => $config['actualizado_en'],
    ];
}

/**
 * Actualiza los parámetros del sistema de puntos.
 */
function actualizarConfiguracionPuntos(PDO $conexion, int $minimoPuntos, float $valorPunto, float $maximoPorcentaje): void
{
    asegurarTablaConfiguracionPuntos($conexion);

    $stmt = $conexion->prepare(
        'UPDATE tbl_configuracion_puntos
         SET minimo_puntos = :minimo,
             valor_punto = :valor,
             maximo_porcentaje = :maximo
         WHERE id = 1'
    );

    $stmt->execute([
        ':minimo' => $minimoPuntos,
        ':valor'  => $valorPunto,
        ':maximo' => $maximoPorcentaje,
    ]);
}
