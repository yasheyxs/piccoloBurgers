<?php
require_once __DIR__ . '/../../../src/Http/Admin/acceso.php'; // permisos, sesión y CSRF del panel
// Reservas de stock que usa el canje de puntos del panel (clientes_canje.php)
require_once __DIR__ . '/../../../src/Core/bootstrap.php';
require piccolo_path('src/Http/Admin/carrito_actualizar_handler.php');
