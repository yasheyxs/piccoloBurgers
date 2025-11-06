<?php

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/email_requirement.php';
require_once __DIR__ . '/puntos_config.php';

enforceEmailRequirement();

$conexion = requireConnection($conexion ?? null);

$configuracionPuntos = obtenerConfiguracionPuntos($conexion);
$puntosCliente = obtenerPuntosCliente($conexion);

$minimoPuntosParaCanjear = $configuracionPuntos['minimo_puntos'];
$valorPorPunto = $configuracionPuntos['valor_punto'];
$maximoPorcentajeCanje = $configuracionPuntos['maximo_porcentaje'];

function obtenerPuntosCliente(PDO $conexion): int
{
    if (!isset($_SESSION['cliente']['id'])) {
        return 0;
    }

    $stmt = $conexion->prepare('SELECT puntos FROM tbl_clientes WHERE ID = ?');
    $stmt->execute([$_SESSION['cliente']['id']]);
    $puntos = $stmt->fetchColumn();

    return $puntos !== false ? (int) $puntos : 0;
}
