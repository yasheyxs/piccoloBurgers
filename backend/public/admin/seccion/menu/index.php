<?php
require_once __DIR__ . '/../../../../src/Http/Admin/acceso.php'; // permisos, sesión y CSRF del panel
require_once piccolo_path('src/Core/database.php');
require_once piccolo_path('src/Services/menu_catalogo.php');

use Piccolo\Http\Admin\MenuController;
use Piccolo\Http\Api\ApiException;
use Piccolo\Repositories\MateriasPrimasRepositorio;
use Piccolo\Repositories\MenuRepositorio;

$menu = new MenuController(new MenuRepositorio($conexion), new MateriasPrimasRepositorio($conexion), piccolo_path('public/img/menu'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $menu->eliminar((int) ($_POST['txtID'] ?? 0));
        piccolo_admin_aviso('success', 'Producto eliminado del menú.');
    } catch (ApiException $error) {
        piccolo_admin_aviso($error->status() === 404 ? 'warning' : 'danger', $error->getMessage());
    }
    header('Location: index.php');
    exit;
}

piccolo_admin_pagina('menu/lista', [
    'productos' => $menu->listar(),
    'aviso' => piccolo_admin_tomar_aviso(),
]);
