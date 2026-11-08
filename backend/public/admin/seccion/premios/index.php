<?php
require_once __DIR__ . '/../../../../src/Http/Admin/acceso.php'; // permisos, sesión y CSRF del panel
require_once piccolo_path('src/Core/database.php');

use Piccolo\Http\Admin\PremiosController;
use Piccolo\Http\Api\ApiException;
use Piccolo\Repositories\PremiosRepositorio;

$premios = new PremiosController(new PremiosRepositorio($conexion));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $premios->eliminar((int) ($_POST['txtID'] ?? 0));
        piccolo_admin_aviso('success', 'Premio eliminado. Los canjes que ya se hicieron siguen en el historial de cada cliente.');
    } catch (ApiException $error) {
        piccolo_admin_aviso('warning', $error->getMessage());
    }
    header('Location: index.php');
    exit;
}

piccolo_admin_pagina('premios/lista', [
    'premios' => $premios->listar(),
    'aviso' => piccolo_admin_tomar_aviso(),
]);
