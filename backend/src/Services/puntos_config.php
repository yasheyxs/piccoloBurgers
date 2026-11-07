<?php

declare(strict_types=1);

/**
 * Obtiene la configuración del sistema de puntos desde la base de datos.
 */
function obtenerConfiguracionPuntos(PDO $conexion): array
{
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
