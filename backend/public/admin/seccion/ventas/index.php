<?php
require_once __DIR__ . '/../../../../src/Http/Admin/acceso.php'; // permisos, sesión y CSRF del panel
require_once piccolo_path('src/Core/database.php');

use Piccolo\Http\Admin\VentasController;
use Piccolo\Http\Api\ApiException;
use Piccolo\Repositories\VentasRepositorio;

$ventas = new VentasController(new VentasRepositorio($conexion), $conexion);

$aviso = null;
try {
    $resumen = $ventas->resumen($_GET);
} catch (ApiException $error) {
    // Fechas mal escritas o al revés: se avisa y se muestra el mes actual
    $aviso = ['tipo' => 'warning', 'mensaje' => implode(' ', $error->errores()) . ' Te mostramos el mes actual.'];
    $resumen = $ventas->resumen([]);
}

piccolo_admin_pagina('ventas/reporte', ['resumen' => $resumen, 'aviso' => $aviso]);
