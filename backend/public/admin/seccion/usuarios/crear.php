<?php
require_once __DIR__ . '/../../../../src/Http/Admin/acceso.php'; // permisos, sesión y CSRF del panel
require_once piccolo_path('src/Core/database.php');

use Piccolo\Http\Admin\UsuariosController;
use Piccolo\Http\Api\ApiException;
use Piccolo\Repositories\UsuariosRepositorio;

$usuarios = new UsuariosController(new UsuariosRepositorio($conexion));
$usuario = ['usuario' => '', 'correo' => '', 'rol' => 'empleado'];
$errores = [];
$aviso = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = array_intersect_key($_POST, $usuario) + $usuario;
    try {
        $usuarios->crear($_POST, (int) ($_SESSION['admin_id'] ?? 0));
        piccolo_admin_aviso('success', 'Usuario creado. Ya puede entrar al panel con su contraseña.');
        header('Location: index.php');
        exit;
    } catch (ApiException $error) {
        $errores = $error->errores();
        $aviso = ['tipo' => 'danger', 'mensaje' => $error->getMessage()];
    }
}

piccolo_admin_pagina('usuarios/formulario', [
    'titulo' => 'Agregar usuario',
    'boton' => 'Crear usuario',
    'nuevo' => true,
    'usuario' => $usuario,
    'errores' => $errores,
    'aviso' => $aviso,
]);
