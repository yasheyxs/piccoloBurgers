<?php
require_once __DIR__ . '/../../../../src/Http/Admin/acceso.php'; // permisos, sesión y CSRF del panel
require_once piccolo_path('src/Core/database.php');

use Piccolo\Http\Admin\PremiosController;
use Piccolo\Http\Api\ApiException;
use Piccolo\Repositories\PremiosRepositorio;

$premios = new PremiosController(new PremiosRepositorio($conexion));
$id = (int) ($_GET['id'] ?? 0);
$errores = [];
$aviso = null;

try {
    $premio = $premios->obtener($id);
} catch (ApiException $error) {
    piccolo_admin_aviso('warning', $error->getMessage());
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $premio = array_intersect_key($_POST, array_flip(['nombre', 'descripcion', 'costo_puntos'])) + $premio;
    try {
        $premios->actualizar($id, $premio);
        piccolo_admin_aviso('success', 'Los cambios se guardaron correctamente.');
        header('Location: index.php');
        exit;
    } catch (ApiException $error) {
        $errores = $error->errores();
        $aviso = ['tipo' => 'danger', 'mensaje' => $error->getMessage()];
    }
}

piccolo_admin_pagina('premios/formulario', [
    'titulo' => 'Editar premio',
    'boton' => 'Guardar cambios',
    'premio' => $premio,
    'errores' => $errores,
    'aviso' => $aviso,
]);
