<?php
require_once __DIR__ . '/../../src/Http/Admin/acceso.php'; // permisos, sesión y CSRF del panel
require_once piccolo_path('src/Core/database.php');

use Piccolo\Http\Admin\ClientesController;
use Piccolo\Http\Api\ApiException;
use Piccolo\Repositories\ClientesRepositorio;

$clientes = new ClientesController(new ClientesRepositorio($conexion));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $clientes->eliminar((int) ($_POST['cliente_id'] ?? 0));
        piccolo_admin_aviso('success', 'Cliente eliminado. Sus pedidos siguen en el historial de ventas.');
    } catch (ApiException $error) {
        piccolo_admin_aviso('warning', $error->getMessage());
    }
    header('Location: clientes.php');
    exit;
}

piccolo_admin_pagina('clientes/lista', [
    'clientes' => $clientes->listar(),
    'aviso' => piccolo_admin_tomar_aviso(),
]);
