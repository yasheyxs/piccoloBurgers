<?php
require_once __DIR__ . '/../../../../src/Http/Admin/acceso.php'; // permisos, sesión y CSRF del panel
require_once piccolo_path('src/Core/database.php');

use Piccolo\Http\Admin\PoolController;
use Piccolo\Repositories\PoolRepositorio;

$pool = new PoolController(new PoolRepositorio($conexion), $conexion);

piccolo_admin_pagina('pool/turnos', [
    'configuracion' => $pool->configuracion(),
    'csrf' => piccolo_csrf_token(),
]);
