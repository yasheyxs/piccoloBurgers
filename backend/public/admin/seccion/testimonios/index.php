<?php
require_once __DIR__ . '/../../../../src/Http/Admin/acceso.php'; // permisos, sesión y CSRF del panel
require_once piccolo_path('src/Core/database.php');

use Piccolo\Http\Admin\ContenidoController;
use Piccolo\Http\Api\ApiException;
use Piccolo\Repositories\ContenidoRepositorio;

$contenido = new ContenidoController(new ContenidoRepositorio($conexion), piccolo_path('public/img/banners'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $contenido->eliminarTestimonio((int) ($_POST['id'] ?? 0));
        piccolo_admin_aviso('success', 'Testimonio eliminado.');
    } catch (ApiException $error) {
        piccolo_admin_aviso('warning', $error->getMessage());
    }
    header('Location: index.php');
    exit;
}

piccolo_admin_pagina('contenido/testimonios', ['testimonios' => $contenido->testimonios(), 'aviso' => piccolo_admin_tomar_aviso()]);
