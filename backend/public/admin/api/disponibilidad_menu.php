<?php
require_once __DIR__ . '/../../../src/Http/Admin/acceso.php'; // permisos, sesión y CSRF del panel
// Disponibilidad de productos que usa el canje de puntos del panel (clientes_canje.php)
require_once __DIR__ . '/../../../src/Core/bootstrap.php';
require piccolo_path('src/Http/Admin/disponibilidad_menu_handler.php');
