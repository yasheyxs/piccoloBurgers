<?php
require_once __DIR__ . '/../../../../src/Http/Admin/acceso.php'; // permisos, sesión y CSRF del panel
require_once piccolo_path('src/Core/database.php');
require_once piccolo_path('src/Services/menu_catalogo.php');

use Piccolo\Http\Admin\MenuController;
use Piccolo\Http\Api\ApiException;
use Piccolo\Repositories\MateriasPrimasRepositorio;
use Piccolo\Repositories\MenuRepositorio;

$menu = new MenuController(new MenuRepositorio($conexion), new MateriasPrimasRepositorio($conexion), piccolo_path('public/img/menu'));
$producto = ['nombre' => '', 'ingredientes' => '', 'precio' => '', 'categoria' => '', 'visible' => '1'];
$errores = [];
$aviso = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $producto = array_intersect_key($_POST, $producto) + $producto;
    try {
        $creado = $menu->crear($producto, $_FILES['foto'] ?? null);
        piccolo_admin_aviso('success', 'Producto agregado. Ahora cargá los insumos que usa, así el sitio sabe cuándo hay stock.');
        header('Location: materias_primas.php?menu_id=' . $creado['id']);
        exit;
    } catch (ApiException $error) {
        $errores = $error->errores();
        $aviso = ['tipo' => 'danger', 'mensaje' => $error->getMessage()];
    }
}

piccolo_admin_pagina('menu/formulario', [
    'titulo' => 'Agregar producto al menú',
    'boton' => 'Agregar producto',
    'producto' => $producto,
    'categorias' => MenuController::categorias(),
    'errores' => $errores,
    'aviso' => $aviso,
]);
