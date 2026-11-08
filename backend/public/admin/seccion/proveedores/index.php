<?php
require_once __DIR__ . '/../../../../src/Http/Admin/acceso.php'; // permisos, sesión y CSRF del panel
require_once piccolo_path('src/Core/database.php');

use Piccolo\Http\Admin\ProveedoresController;
use Piccolo\Http\Api\ApiException;
use Piccolo\Repositories\ProveedoresRepositorio;

$proveedores = new ProveedoresController(new ProveedoresRepositorio($conexion));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $proveedores->eliminar((int) ($_POST['txtID'] ?? 0));
        piccolo_admin_aviso('success', 'Proveedor eliminado correctamente.');
    } catch (ApiException $error) {
        piccolo_admin_aviso($error->status() === 404 ? 'warning' : 'danger', $error->getMessage());
    }
    header('Location: index.php');
    exit;
}

piccolo_admin_pagina('proveedores/lista', [
    'proveedores' => $proveedores->listar(),
    'aviso' => piccolo_admin_tomar_aviso(),
]);
