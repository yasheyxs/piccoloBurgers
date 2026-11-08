<?php
require_once __DIR__ . '/../../../../src/Http/Admin/acceso.php'; // permisos, sesión y CSRF del panel
require_once piccolo_path('src/Core/database.php');

use Piccolo\Http\Admin\ComprasController;
use Piccolo\Http\Api\ApiException;
use Piccolo\Repositories\ComprasRepositorio;
use Piccolo\Repositories\MateriasPrimasRepositorio;
use Piccolo\Repositories\ProveedoresRepositorio;

$compras = new ComprasController(new ComprasRepositorio($conexion), new ProveedoresRepositorio($conexion), new MateriasPrimasRepositorio($conexion));

try {
    $compra = $compras->obtener((int) ($_GET['txtID'] ?? 0));
} catch (ApiException $error) {
    piccolo_admin_aviso('warning', $error->getMessage());
    header('Location: index.php');
    exit;
}

piccolo_admin_pagina('compras/detalle', ['compra' => $compra]);
