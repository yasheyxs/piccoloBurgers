<?php
require_once __DIR__ . '/../../../../src/Http/Admin/acceso.php'; // permisos, sesión y CSRF del panel
require_once piccolo_path('src/Core/database.php');

use Piccolo\Http\Admin\PremiosController;
use Piccolo\Http\Api\ApiException;
use Piccolo\Repositories\PremiosRepositorio;

$premios = new PremiosController(new PremiosRepositorio($conexion));
$premio = ['nombre' => '', 'descripcion' => '', 'costo_puntos' => ''];
$errores = [];
$aviso = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $premio = array_intersect_key($_POST, $premio) + $premio;
    try {
        $premios->crear($premio);
        piccolo_admin_aviso('success', 'Premio creado. Ya se puede canjear desde la pantalla de canje de cada cliente.');
        header('Location: index.php');
        exit;
    } catch (ApiException $error) {
        $errores = $error->errores();
        $aviso = ['tipo' => 'danger', 'mensaje' => $error->getMessage()];
    }
}

piccolo_admin_pagina('premios/formulario', [
    'titulo' => 'Nuevo premio',
    'boton' => 'Crear premio',
    'premio' => $premio,
    'errores' => $errores,
    'aviso' => $aviso,
]);
