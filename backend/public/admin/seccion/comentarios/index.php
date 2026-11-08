<?php
require_once __DIR__ . '/../../../../src/Http/Admin/acceso.php'; // permisos, sesión y CSRF del panel
require_once piccolo_path('src/Core/database.php');

use Piccolo\Http\Admin\ContenidoController;
use Piccolo\Http\Api\ApiException;
use Piccolo\Repositories\ContenidoRepositorio;

$contenido = new ContenidoController(new ContenidoRepositorio($conexion), piccolo_path('public/img/banners'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $contenido->eliminarComentario((int) ($_POST['id'] ?? 0));
        piccolo_admin_aviso('success', 'Comentario eliminado.');
    } catch (ApiException $error) {
        piccolo_admin_aviso('warning', $error->getMessage());
    }
    header('Location: index.php');
    exit;
}

piccolo_admin_pagina('contenido/comentarios', ['comentarios' => $contenido->comentarios(), 'aviso' => piccolo_admin_tomar_aviso()]);
