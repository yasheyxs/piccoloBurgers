<?php
require_once __DIR__ . '/../../../../src/Http/Admin/acceso.php'; // permisos, sesión y CSRF del panel
require_once piccolo_path('src/Core/database.php');

use Piccolo\Http\Admin\ProveedoresController;
use Piccolo\Http\Api\ApiException;
use Piccolo\Repositories\ProveedoresRepositorio;

$proveedores = new ProveedoresController(new ProveedoresRepositorio($conexion));
$id = (int) ($_POST['txtID'] ?? $_GET['txtID'] ?? 0);
$errores = [];
$aviso = null;

try {
    $proveedor = $proveedores->obtener($id);
} catch (ApiException $error) {
    piccolo_admin_aviso('warning', $error->getMessage());
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $proveedor = array_intersect_key($_POST, ['nombre' => '', 'telefono' => '', 'email' => '']) + $proveedor;
    try {
        $proveedores->actualizar($id, $proveedor);
        piccolo_admin_aviso('success', 'Proveedor modificado correctamente.');
        header('Location: index.php');
        exit;
    } catch (ApiException $error) {
        $errores = $error->errores();
        $aviso = ['tipo' => 'danger', 'mensaje' => $error->getMessage()];
    }
}

piccolo_admin_pagina('proveedores/formulario', [
    'titulo' => 'Editar proveedor',
    'boton' => 'Guardar cambios',
    'proveedor' => $proveedor,
    'errores' => $errores,
    'aviso' => $aviso,
]);
