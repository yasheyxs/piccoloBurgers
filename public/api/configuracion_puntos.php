<?php

declare(strict_types=1);

require_once __DIR__ . '/../../admin/bd.php';
require_once __DIR__ . '/../../includes/puntos_config.php';

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

try {
    $configuracion = obtenerConfiguracionPuntos($conexion);

    $respuesta = [
        'minimoPuntos' => (int) ($configuracion['minimo_puntos'] ?? 0),
        'valorPunto' => (float) ($configuracion['valor_punto'] ?? 0),
        'maximoPorcentaje' => (float) ($configuracion['maximo_porcentaje'] ?? 0),
        'actualizadoEn' => $configuracion['actualizado_en'] ?? null,
    ];

    echo json_encode([
        'exito' => true,
        'configuracion' => $respuesta,
    ]);
} catch (Throwable $error) {
    http_response_code(500);

    error_log('No se pudo obtener la configuración de puntos: ' . $error->getMessage());

    echo json_encode([
        'exito' => false,
        'mensaje' => 'No se pudo obtener la configuración del sistema de puntos.',
    ]);
}
