<?php
require_once __DIR__ . '/../../src/Http/Admin/acceso.php'; // permisos, sesión y CSRF del panel
require_once piccolo_path('src/Core/database.php');

use Piccolo\Http\Admin\ClientesController;
use Piccolo\Http\Api\ApiException;
use Piccolo\Repositories\ClientesRepositorio;

$clientes = new ClientesController(new ClientesRepositorio($conexion));

try {
    $cliente = $clientes->detalle((int) ($_GET['id'] ?? 0));
} catch (ApiException $error) {
    piccolo_admin_aviso('warning', $error->getMessage());
    header('Location: clientes.php');
    exit;
}

piccolo_admin_pagina('clientes/detalle', [
    'cliente' => $cliente,
    'aviso' => piccolo_admin_tomar_aviso(),
]);
