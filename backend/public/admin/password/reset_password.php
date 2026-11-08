<?php
/** Crear una contraseña nueva con el enlace que llegó por correo. No requiere sesión. */
require_once __DIR__ . '/../../../src/Http/Admin/acceso.php'; // permisos, sesión y CSRF del panel
require_once piccolo_path('src/Core/database.php');

use Piccolo\Http\Admin\ContrasenaPanelController;
use Piccolo\Http\Api\ApiException;

$contrasenas = new ContrasenaPanelController($conexion);
$token = (string) ($_POST['token'] ?? $_GET['token'] ?? '');
$errores = [];
$aviso = null;
$listo = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!piccolo_csrf_valido()) {
        $aviso = ['tipo' => 'warning', 'mensaje' => 'La página venció. Probá de nuevo.'];
    } else {
        try {
            $contrasenas->restablecer($token, $_POST);
            $listo = true;
        } catch (ApiException $error) {
            $errores = $error->errores();
            $aviso = ['tipo' => 'danger', 'mensaje' => $error->getMessage()];
        }
    }
}

piccolo_admin_pagina_publica('contrasena/restablecer', [
    'token' => $token,
    'usuario' => $listo ? null : $contrasenas->usuarioDelEnlace($token),
    'listo' => $listo,
    'errores' => $errores,
    'aviso' => $aviso,
]);
