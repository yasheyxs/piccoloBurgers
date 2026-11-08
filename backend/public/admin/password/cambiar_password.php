<?php
require_once __DIR__ . '/../../../src/Http/Admin/acceso.php'; // permisos, sesión y CSRF del panel
require_once piccolo_path('src/Core/database.php');

use Piccolo\Http\Admin\ContrasenaPanelController;
use Piccolo\Http\Api\ApiException;

$errores = [];
$aviso = piccolo_admin_tomar_aviso();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        (new ContrasenaPanelController($conexion))->cambiar((int) ($_SESSION['admin_id'] ?? 0), $_POST);
        piccolo_admin_aviso('success', 'Tu contraseña se cambió. La próxima vez entrá con la nueva.');
        header('Location: cambiar_password.php');
        exit;
    } catch (ApiException $error) {
        $errores = $error->errores();
        $aviso = ['tipo' => 'danger', 'mensaje' => $error->getMessage()];
    }
}

piccolo_admin_pagina('contrasena/cambiar', ['errores' => $errores, 'aviso' => $aviso]);
