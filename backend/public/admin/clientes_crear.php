<?php
require_once __DIR__ . '/../../src/Http/Admin/acceso.php'; // permisos, sesión y CSRF del panel
require_once piccolo_path('src/Core/database.php');

use Piccolo\Http\Admin\ClientesController;
use Piccolo\Http\Api\ApiException;
use Piccolo\Repositories\ClientesRepositorio;

$clientes = new ClientesController(new ClientesRepositorio($conexion));
$cliente = ['nombre' => '', 'email' => '', 'codigo_pais' => '54', 'telefono' => '', 'puntos' => '0'];
$errores = [];
$aviso = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cliente = array_intersect_key($_POST, $cliente) + $cliente;
    try {
        $creado = $clientes->crear($cliente);
        piccolo_admin_aviso('success', "Cliente creado. Su contraseña provisoria es {$creado['password_provisoria']} (anotala: no se vuelve a mostrar). "
            . 'Puede cambiarla desde su cuenta en el sitio o con "¿Olvidaste tu contraseña?".');
        header('Location: clientes_detalle.php?id=' . $creado['cliente']['id']);
        exit;
    } catch (ApiException $error) {
        $errores = $error->errores();
        $aviso = ['tipo' => 'danger', 'mensaje' => $error->getMessage()];
    }
}

piccolo_admin_pagina('clientes/formulario', [
    'titulo' => 'Crear cliente',
    'boton' => 'Crear cliente',
    'nuevo' => true,
    'cliente' => $cliente,
    'codigos' => ClientesController::codigosPais(),
    'errores' => $errores,
    'aviso' => $aviso,
]);
