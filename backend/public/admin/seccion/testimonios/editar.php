<?php
require_once __DIR__ . '/../../../../src/Http/Admin/acceso.php'; // permisos, sesión y CSRF del panel
require_once piccolo_path('src/Core/database.php');

use Piccolo\Http\Admin\ContenidoController;
use Piccolo\Http\Api\ApiException;
use Piccolo\Repositories\ContenidoRepositorio;

$contenido = new ContenidoController(new ContenidoRepositorio($conexion), piccolo_path('public/img/banners'));
$id = (int) ($_GET['id'] ?? 0);
try {
    $testimonio = $contenido->testimonio($id);
} catch (ApiException $error) {
    piccolo_admin_aviso('warning', $error->getMessage());
    header('Location: index.php');
    exit;
}
$errores = [];
$aviso = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $testimonio = array_intersect_key($_POST, ['opinion' => '', 'nombre' => '']) + $testimonio;
    try {
        $contenido->guardarTestimonio($testimonio, $id);
        piccolo_admin_aviso('success', $id > 0 ? 'Testimonio modificado.' : 'Testimonio agregado. Ya se ve en el sitio.');
        header('Location: index.php');
        exit;
    } catch (ApiException $error) {
        $errores = $error->errores();
        $aviso = ['tipo' => 'danger', 'mensaje' => $error->getMessage()];
    }
}

piccolo_admin_pagina('contenido/testimonio_formulario', [
    'titulo' => $id > 0 ? 'Editar testimonio' : 'Nuevo testimonio',
    'boton' => $id > 0 ? 'Guardar cambios' : 'Agregar testimonio',
    'testimonio' => $testimonio, 'errores' => $errores, 'aviso' => $aviso,
]);
