<?php
require_once __DIR__ . '/../../../../src/Http/Admin/acceso.php'; // permisos, sesión y CSRF del panel
require_once piccolo_path('src/Core/database.php');

use Piccolo\Http\Admin\UsuariosController;
use Piccolo\Http\Api\ApiException;
use Piccolo\Repositories\UsuariosRepositorio;

$usuarios = new UsuariosController(new UsuariosRepositorio($conexion));
$actualId = (int) ($_SESSION['admin_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $usuarios->eliminar((int) ($_POST['txtID'] ?? 0), $actualId);
        piccolo_admin_aviso('success', 'Usuario eliminado. Ya no puede entrar al panel.');
    } catch (ApiException $error) {
        piccolo_admin_aviso('warning', $error->getMessage());
    }
    header('Location: index.php');
    exit;
}

piccolo_admin_pagina('usuarios/lista', [
    'usuarios' => $usuarios->listar($actualId),
    'aviso' => piccolo_admin_tomar_aviso(),
]);
