<?php
require_once __DIR__ . '/../../../../src/Http/Admin/acceso.php'; // permisos, sesión y CSRF del panel
require_once piccolo_path('src/Core/database.php');

use Piccolo\Http\Admin\ComprasController;
use Piccolo\Http\Api\ApiException;
use Piccolo\Repositories\ComprasRepositorio;
use Piccolo\Repositories\MateriasPrimasRepositorio;
use Piccolo\Repositories\ProveedoresRepositorio;

$compras = new ComprasController(new ComprasRepositorio($conexion), new ProveedoresRepositorio($conexion), new MateriasPrimasRepositorio($conexion));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $compras->eliminar((int) ($_POST['txtID'] ?? 0));
        piccolo_admin_aviso('success', 'Compra eliminada. Lo comprado se descontó del stock.');
    } catch (ApiException $error) {
        piccolo_admin_aviso($error->status() === 404 ? 'warning' : 'danger', $error->getMessage());
    }
    header('Location: index.php');
    exit;
}

piccolo_admin_pagina('compras/lista', [
    'compras' => $compras->listar(),
    'resumen' => $compras->resumen(),
    'aviso' => piccolo_admin_tomar_aviso(),
]);
