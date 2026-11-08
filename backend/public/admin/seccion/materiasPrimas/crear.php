<?php
require_once __DIR__ . '/../../../../src/Http/Admin/acceso.php'; // permisos, sesión y CSRF del panel
require_once piccolo_path('src/Core/database.php');

use Piccolo\Http\Admin\MateriasPrimasController;
use Piccolo\Http\Admin\ProveedoresController;
use Piccolo\Http\Api\ApiException;
use Piccolo\Repositories\MateriasPrimasRepositorio;
use Piccolo\Repositories\ProveedoresRepositorio;

$materias = new MateriasPrimasController(new MateriasPrimasRepositorio($conexion), new ProveedoresRepositorio($conexion));
$materia = ['nombre' => '', 'unidad_medida' => '', 'cantidad' => '', 'stock_minimo' => '', 'proveedor_id' => 0];
$errores = [];
$aviso = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $materia = array_intersect_key($_POST, $materia) + $materia;
    try {
        $materias->crear($materia);
        piccolo_admin_aviso('success', 'Materia prima agregada correctamente.');
        header('Location: index.php');
        exit;
    } catch (ApiException $error) {
        $errores = $error->errores();
        $aviso = ['tipo' => 'danger', 'mensaje' => $error->getMessage()];
    }
}

piccolo_admin_pagina('materias_primas/formulario', [
    'titulo' => 'Agregar materia prima',
    'boton' => 'Agregar materia prima',
    'materia' => $materia,
    'proveedores' => (new ProveedoresController(new ProveedoresRepositorio($conexion)))->listar(),
    'errores' => $errores,
    'aviso' => $aviso,
]);
