<?php
require_once __DIR__ . '/../../../../src/Http/Admin/acceso.php'; // permisos, sesión y CSRF del panel
require_once piccolo_path('src/Core/database.php');
require_once piccolo_path('src/Services/menu_catalogo.php');

use Piccolo\Http\Admin\MenuController;
use Piccolo\Http\Api\ApiException;
use Piccolo\Repositories\MateriasPrimasRepositorio;
use Piccolo\Repositories\MenuRepositorio;

$menu = new MenuController(new MenuRepositorio($conexion), new MateriasPrimasRepositorio($conexion), piccolo_path('public/img/menu'));
$id = (int) ($_POST['txtID'] ?? $_GET['txtID'] ?? 0);
$errores = [];
$aviso = null;

try {
    $producto = $menu->obtener($id);
} catch (ApiException $error) {
    piccolo_admin_aviso('warning', $error->getMessage());
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $campos = ['nombre' => '', 'ingredientes' => '', 'precio' => '', 'categoria' => '', 'visible' => '1'];
    $producto = array_intersect_key($_POST, $campos) + $producto;
    try {
        $menu->actualizar($id, $producto, $_FILES['foto'] ?? null);
        piccolo_admin_aviso('success', 'Producto modificado correctamente.');
        header('Location: index.php');
        exit;
    } catch (ApiException $error) {
        $errores = $error->errores();
        $aviso = ['tipo' => 'danger', 'mensaje' => $error->getMessage()];
    }
}

piccolo_admin_pagina('menu/formulario', [
    'titulo' => 'Editar producto del menú',
    'boton' => 'Guardar cambios',
    'producto' => $producto,
    'categorias' => MenuController::categorias(),
    'errores' => $errores,
    'aviso' => $aviso,
]);
