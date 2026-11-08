<?php
require_once __DIR__ . '/../../../../src/Http/Admin/acceso.php'; // permisos, sesión y CSRF del panel
require_once piccolo_path('src/Core/database.php');

use Piccolo\Http\Admin\PuntosController;
use Piccolo\Http\Api\ApiException;

$puntos = new PuntosController($conexion);
$configuracion = $puntos->obtener();
$formulario = $configuracion;
$errores = [];
$aviso = piccolo_admin_tomar_aviso();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $puntos->actualizar($_POST);
        piccolo_admin_aviso('success', 'Los parámetros del sistema de puntos se guardaron. Se aplican desde el próximo pedido.');
        header('Location: index.php');
        exit;
    } catch (ApiException $error) {
        $errores = $error->errores();
        $formulario = array_intersect_key($_POST, $configuracion) + $configuracion;
        $aviso = ['tipo' => 'danger', 'mensaje' => $error->getMessage()];
    }
}

piccolo_admin_pagina('puntos/configuracion', [
    'configuracion' => $configuracion,
    'formulario' => $formulario,
    'errores' => $errores,
    'aviso' => $aviso,
]);
