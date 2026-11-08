<?php
require_once __DIR__ . '/../../../../src/Http/Admin/acceso.php'; // permisos, sesión y CSRF del panel
require_once piccolo_path('src/Core/database.php');

use Piccolo\Http\Admin\MateriasPrimasController;
use Piccolo\Http\Admin\ProveedoresController;
use Piccolo\Http\Api\ApiException;
use Piccolo\Repositories\MateriasPrimasRepositorio;
use Piccolo\Repositories\ProveedoresRepositorio;

$materias = new MateriasPrimasController(new MateriasPrimasRepositorio($conexion), new ProveedoresRepositorio($conexion));
$id = (int) ($_POST['txtID'] ?? $_GET['txtID'] ?? 0);
$errores = [];
$aviso = null;

try {
    $materia = $materias->obtener($id);
} catch (ApiException $error) {
    piccolo_admin_aviso('warning', $error->getMessage());
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $campos = ['nombre' => '', 'unidad_medida' => '', 'cantidad' => '', 'stock_minimo' => '', 'proveedor_id' => 0];
    $materia = array_intersect_key($_POST, $campos) + $materia;
    try {
        $materias->actualizar($id, $materia);
        piccolo_admin_aviso('success', 'Materia prima modificada correctamente.');
        header('Location: index.php');
        exit;
    } catch (ApiException $error) {
        $errores = $error->errores();
        $aviso = ['tipo' => 'danger', 'mensaje' => $error->getMessage()];
    }
}

piccolo_admin_pagina('materias_primas/formulario', [
    'titulo' => 'Editar materia prima',
    'boton' => 'Guardar cambios',
    'materia' => $materia,
    'proveedores' => (new ProveedoresController(new ProveedoresRepositorio($conexion)))->listar(),
    'errores' => $errores,
    'aviso' => $aviso,
]);
