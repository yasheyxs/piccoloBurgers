<?php
require_once __DIR__ . '/../../src/Http/Admin/acceso.php'; // permisos, sesión y CSRF del panel
require_once piccolo_path('src/Core/database.php');

use Piccolo\Http\Admin\PedidosPanelController;
use Piccolo\Repositories\PedidosPanelRepositorio;

$pedidos = new PedidosPanelController(new PedidosPanelRepositorio($conexion), $conexion);

piccolo_admin_pagina('pedidos/cocina', ['pedidos' => $pedidos->cocina()]);
