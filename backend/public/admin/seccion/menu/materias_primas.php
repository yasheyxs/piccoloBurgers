<?php
require_once __DIR__ . '/../../../../src/Http/Admin/acceso.php'; // permisos, sesión y CSRF del panel
require_once piccolo_path('src/Core/database.php');
require_once piccolo_path('src/Services/menu_catalogo.php');

// Receta de un producto: qué materias primas descuenta cada unidad vendida

use Piccolo\Http\Admin\MateriasPrimasController;
use Piccolo\Http\Admin\MenuController;
use Piccolo\Http\Api\ApiException;
use Piccolo\Repositories\MateriasPrimasRepositorio;
use Piccolo\Repositories\MenuRepositorio;
use Piccolo\Repositories\ProveedoresRepositorio;

$materiasPrimas = new MateriasPrimasRepositorio($conexion);
$menu = new MenuController(new MenuRepositorio($conexion), $materiasPrimas, piccolo_path('public/img/menu'));
$id = (int) ($_GET['menu_id'] ?? 0);
$errores = [];
$aviso = piccolo_admin_tomar_aviso();

try {
    $producto = $menu->obtener($id);
} catch (ApiException $error) {
    piccolo_admin_aviso('warning', $error->getMessage());
    header('Location: index.php');
    exit;
}
$receta = $producto['receta'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $receta = is_array($_POST['items'] ?? null) ? array_values($_POST['items']) : [];
    try {
        $menu->guardarReceta($id, $receta);
        piccolo_admin_aviso('success', 'Receta guardada.');
        header('Location: materias_primas.php?menu_id=' . $id);
        exit;
    } catch (ApiException $error) {
        $errores = $error->errores();
        $aviso = ['tipo' => 'danger', 'mensaje' => $error->getMessage()];
    }
}

piccolo_admin_pagina('menu/receta', [
    'producto' => $producto,
    'receta' => $receta,
    'materias' => (new MateriasPrimasController($materiasPrimas, new ProveedoresRepositorio($conexion)))->listar(),
    'errores' => $errores,
    'aviso' => $aviso,
]);
