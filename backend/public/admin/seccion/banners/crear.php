<?php
require_once __DIR__ . '/../../../../src/Http/Admin/acceso.php'; // permisos, sesión y CSRF del panel
require_once piccolo_path('src/Core/database.php');

use Piccolo\Http\Admin\ContenidoController;
use Piccolo\Http\Api\ApiException;
use Piccolo\Repositories\ContenidoRepositorio;

$contenido = new ContenidoController(new ContenidoRepositorio($conexion), piccolo_path('public/img/banners'));
$banner = ['titulo' => '', 'descripcion' => ''];
$errores = [];
$aviso = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $banner = array_intersect_key($_POST, $banner) + $banner;
    try {
        $contenido->crearBanner($banner, $_FILES['imagen'] ?? null);
        piccolo_admin_aviso('success', 'Banner creado. Es el que se ve ahora en el sitio.');
        header('Location: index.php');
        exit;
    } catch (ApiException $error) {
        $errores = $error->errores();
        $aviso = ['tipo' => 'danger', 'mensaje' => $error->getMessage()];
    }
}

piccolo_admin_pagina('contenido/banner_formulario', [
    'titulo' => 'Nuevo banner', 'boton' => 'Crear banner', 'banner' => $banner, 'errores' => $errores, 'aviso' => $aviso,
]);
