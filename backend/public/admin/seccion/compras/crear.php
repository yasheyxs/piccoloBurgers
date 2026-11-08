<?php
require_once __DIR__ . '/../../../../src/Http/Admin/acceso.php'; // permisos, sesión y CSRF del panel
require_once piccolo_path('src/Core/database.php');

use Piccolo\Http\Admin\ComprasController;
use Piccolo\Http\Admin\MateriasPrimasController;
use Piccolo\Http\Admin\ProveedoresController;
use Piccolo\Http\Api\ApiException;
use Piccolo\Repositories\ComprasRepositorio;
use Piccolo\Repositories\MateriasPrimasRepositorio;
use Piccolo\Repositories\ProveedoresRepositorio;

$proveedores = new ProveedoresRepositorio($conexion);
$materiasPrimas = new MateriasPrimasRepositorio($conexion);
$compras = new ComprasController(new ComprasRepositorio($conexion), $proveedores, $materiasPrimas);

$compra = ['fecha' => date('Y-m-d'), 'proveedor_id' => '', 'items' => []];
$errores = [];
$aviso = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $compra = [
        'fecha' => (string) ($_POST['fecha'] ?? ''),
        'proveedor_id' => (string) ($_POST['proveedor_id'] ?? ''),
        'items' => is_array($_POST['items'] ?? null) ? array_values($_POST['items']) : [],
    ];
    try {
        $compras->registrar($compra);
        piccolo_admin_aviso('success', 'Compra registrada. Lo comprado se sumó al stock.');
        header('Location: index.php');
        exit;
    } catch (ApiException $error) {
        $errores = $error->errores();
        $aviso = ['tipo' => 'danger', 'mensaje' => $error->getMessage()];
    }
}

piccolo_admin_pagina('compras/formulario', [
    'compra' => $compra,
    'proveedores' => (new ProveedoresController($proveedores))->listar(),
    'materias' => (new MateriasPrimasController($materiasPrimas, $proveedores))->listar(),
    'errores' => $errores,
    'aviso' => $aviso,
]);
