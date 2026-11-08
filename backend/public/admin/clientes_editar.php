<?php
require_once __DIR__ . '/../../src/Http/Admin/acceso.php'; // permisos, sesión y CSRF del panel
require_once piccolo_path('src/Core/database.php');

use Piccolo\Http\Admin\ClientesController;
use Piccolo\Http\Api\ApiException;
use Piccolo\Repositories\ClientesRepositorio;

$clientes = new ClientesController(new ClientesRepositorio($conexion));
$id = (int) ($_GET['id'] ?? 0);
$errores = [];
$aviso = null;

try {
    $guardado = $clientes->obtener($id);
} catch (ApiException $error) {
    piccolo_admin_aviso('warning', $error->getMessage());
    header('Location: clientes.php');
    exit;
}

// En el formulario el teléfono va sin el código de país
$cliente = ['telefono' => $guardado['numero']] + $guardado;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cliente = array_intersect_key($_POST, array_flip(['nombre', 'email', 'codigo_pais', 'telefono', 'puntos'])) + $cliente;
    try {
        $clientes->actualizar($id, $cliente);
        piccolo_admin_aviso('success', 'Cliente modificado correctamente.');
        header('Location: clientes_detalle.php?id=' . $id);
        exit;
    } catch (ApiException $error) {
        $errores = $error->errores();
        $aviso = ['tipo' => 'danger', 'mensaje' => $error->getMessage()];
    }
}

piccolo_admin_pagina('clientes/formulario', [
    'titulo' => 'Editar cliente',
    'boton' => 'Guardar cambios',
    'nuevo' => false,
    'cliente' => $cliente,
    'codigos' => ClientesController::codigosPais(),
    'errores' => $errores,
    'aviso' => $aviso,
]);
