<?php
require_once __DIR__ . '/../../src/Http/Admin/acceso.php'; // permisos, sesión y CSRF del panel

use Piccolo\Http\Admin\VentasController;
use Piccolo\Repositories\VentasRepositorio;

$rolReal = (string) ($_SESSION['rol_real'] ?? $_SESSION['rol'] ?? '');
$rol = (string) ($_SESSION['rol'] ?? '');

// El resumen del mes es el mismo cálculo que el reporte de ventas (sin pedidos cancelados)
$resumen = null;
$errorResumen = null;
if ($rol === 'admin') {
    try {
        require_once piccolo_path('src/Core/database.php');
        $resumen = (new VentasController(new VentasRepositorio($conexion), $conexion))->resumen([]);
    } catch (Throwable $error) {
        error_log('No se pudo calcular el resumen de la portada: ' . $error->getMessage());
        $errorResumen = 'No se pudo cargar el resumen de ventas. Probá recargar la página.';
    }
}

piccolo_admin_pagina('inicio', [
    'usuario' => (string) ($_SESSION['admin_usuario'] ?? ''),
    'rol' => $rol,
    'rolReal' => $rolReal,
    'resumen' => $resumen,
    'errorResumen' => $errorResumen,
]);
