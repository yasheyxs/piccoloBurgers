<?php
require_once __DIR__ . '/../../../../src/Http/Admin/acceso.php'; // permisos, sesión y CSRF del panel
require_once piccolo_path('src/Core/database.php');

use Piccolo\Http\Admin\ContenidoController;
use Piccolo\Http\Api\ApiException;
use Piccolo\Repositories\ContenidoRepositorio;

$contenido = new ContenidoController(new ContenidoRepositorio($conexion), piccolo_path('public/img/banners'));
$id = (int) ($_GET['id'] ?? 0);
$errores = [];
$aviso = null;

try {
    $banner = $contenido->banner($id);
} catch (ApiException $error) {
    piccolo_admin_aviso('warning', $error->getMessage());
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $banner = array_intersect_key($_POST, ['titulo' => '', 'descripcion' => '']) + $banner;
    try {
        $contenido->actualizarBanner($id, $banner, $_FILES['imagen'] ?? null);
        piccolo_admin_aviso('success', 'Banner modificado correctamente.');
        header('Location: index.php');
        exit;
    } catch (ApiException $error) {
        $errores = $error->errores();
        $aviso = ['tipo' => 'danger', 'mensaje' => $error->getMessage()];
    }
}

piccolo_admin_pagina('contenido/banner_formulario', [
    'titulo' => 'Editar banner', 'boton' => 'Guardar cambios', 'banner' => $banner, 'errores' => $errores, 'aviso' => $aviso,
]);
