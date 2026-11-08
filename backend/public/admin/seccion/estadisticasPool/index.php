<?php
require_once __DIR__ . '/../../../../src/Http/Admin/acceso.php'; // permisos, sesión y CSRF del panel
require_once piccolo_path('src/Core/database.php');

use Piccolo\Http\Admin\EstadisticasController;
use Piccolo\Http\Api\ApiException;

$estadisticas = new EstadisticasController($conexion);
$aviso = null;
try {
    $datos = $estadisticas->pool($_GET);
} catch (ApiException $error) {
    // Fechas mal escritas o al revés: se avisa y se muestra el mes actual
    $aviso = ['tipo' => 'warning', 'mensaje' => implode(' ', $error->errores()) . ' Te mostramos el mes actual.'];
    $datos = $estadisticas->pool([]);
}

piccolo_admin_pagina('estadisticas/pool', ['datos' => $datos, 'aviso' => $aviso]);
