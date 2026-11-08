<?php
require_once __DIR__ . '/../../../../src/Http/Admin/acceso.php'; // permisos, sesión y CSRF del panel
require_once piccolo_path('src/Core/database.php');

use Piccolo\Http\Admin\ContenidoController;
use Piccolo\Http\Api\ApiException;
use Piccolo\Repositories\ContenidoRepositorio;

$contenido = new ContenidoController(new ContenidoRepositorio($conexion), piccolo_path('public/img/banners'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $contenido->eliminarBanner((int) ($_POST['id'] ?? 0));
        piccolo_admin_aviso('success', 'Banner eliminado. El sitio muestra ahora el más nuevo de los que quedan.');
    } catch (ApiException $error) {
        piccolo_admin_aviso('warning', $error->getMessage());
    }
    header('Location: index.php');
    exit;
}

piccolo_admin_pagina('contenido/banners', ['banners' => $contenido->banners(), 'aviso' => piccolo_admin_tomar_aviso()]);
