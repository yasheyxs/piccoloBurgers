<?php
require_once __DIR__ . '/../../../../src/Http/Admin/acceso.php'; // permisos, sesión y CSRF del panel
require_once piccolo_path('src/Core/database.php');

use Piccolo\Http\Admin\ProveedoresController;
use Piccolo\Http\Api\ApiException;
use Piccolo\Repositories\ProveedoresRepositorio;

$proveedores = new ProveedoresController(new ProveedoresRepositorio($conexion));
$datos = ['nombre' => '', 'telefono' => '', 'email' => ''];
$errores = [];
$aviso = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos = array_intersect_key($_POST, $datos) + $datos;
    try {
        $proveedores->crear($datos);
        piccolo_admin_aviso('success', 'Proveedor agregado correctamente.');
        header('Location: index.php');
        exit;
    } catch (ApiException $error) {
        $errores = $error->errores();
        $aviso = ['tipo' => 'danger', 'mensaje' => $error->getMessage()];
    }
}

piccolo_admin_pagina('proveedores/formulario', [
    'titulo' => 'Agregar proveedor',
    'boton' => 'Agregar proveedor',
    'proveedor' => $datos,
    'errores' => $errores,
    'aviso' => $aviso,
]);
