<?php

if (!isset($conexion)) {
    throw new RuntimeException('No se encontró la conexión a la base de datos.');
}

$puntosCliente = obtenerPuntosCliente($conexion);

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