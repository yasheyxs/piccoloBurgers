<?php
require_once __DIR__ . '/../../../../src/Http/Admin/acceso.php'; // permisos, sesión y CSRF del panel
require_once piccolo_path('src/Core/database.php');

use Piccolo\Http\Admin\PoolController;
use Piccolo\Http\Api\ApiException;
use Piccolo\Repositories\PoolRepositorio;

$pool = new PoolController(new PoolRepositorio($conexion), $conexion);
$configuracion = $pool->configuracion();
$formulario = $configuracion;
$errores = [];
$aviso = piccolo_admin_tomar_aviso();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pool->guardarConfiguracion($_POST);
        piccolo_admin_aviso('success', 'Parámetros del pool guardados. El valor nuevo se usa en las fichas que se vendan desde ahora.');
        header('Location: index.php');
        exit;
    } catch (ApiException $error) {
        $errores = $error->errores();
        $formulario = array_intersect_key($_POST, $configuracion) + $configuracion;
        $aviso = ['tipo' => 'danger', 'mensaje' => $error->getMessage()];
    }
}

piccolo_admin_pagina('pool/parametros', [
    'configuracion' => $configuracion,
    'formulario' => $formulario,
    'errores' => $errores,
    'aviso' => $aviso,
]);
