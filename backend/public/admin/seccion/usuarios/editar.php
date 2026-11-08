<?php
require_once __DIR__ . '/../../../../src/Http/Admin/acceso.php'; // permisos, sesión y CSRF del panel
require_once piccolo_path('src/Core/database.php');

use Piccolo\Http\Admin\UsuariosController;
use Piccolo\Http\Api\ApiException;
use Piccolo\Repositories\UsuariosRepositorio;

$usuarios = new UsuariosController(new UsuariosRepositorio($conexion));
$id = (int) ($_GET['id'] ?? 0);
$errores = [];
$aviso = null;

try {
    $usuario = $usuarios->obtener($id);
} catch (ApiException $error) {
    piccolo_admin_aviso('warning', $error->getMessage());
    header('Location: index.php');
    exit;
}
$rolAnterior = $usuario['rol'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = array_intersect_key($_POST, array_flip(['usuario', 'correo', 'rol'])) + $usuario;
    try {
        $usuarios->actualizar($id, $_POST, (int) ($_SESSION['admin_id'] ?? 0));
        piccolo_admin_aviso('success', 'Usuario modificado correctamente.');
        header('Location: index.php');
        exit;
    } catch (ApiException $error) {
        $errores = $error->errores();
        $aviso = ['tipo' => 'danger', 'mensaje' => $error->getMessage()];
    }
}

piccolo_admin_pagina('usuarios/formulario', [
    'titulo' => 'Editar usuario',
    'boton' => 'Guardar cambios',
    'nuevo' => false,
    'rolAnterior' => $rolAnterior,
    'usuario' => $usuario,
    'errores' => $errores,
    'aviso' => $aviso,
]);
