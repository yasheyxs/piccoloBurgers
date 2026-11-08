<?php
require_once __DIR__ . '/../../../../src/Http/Admin/acceso.php'; // permisos, sesión y CSRF del panel
require_once piccolo_path('src/Core/database.php');

use Piccolo\Http\Admin\MateriasPrimasController;
use Piccolo\Http\Api\ApiException;
use Piccolo\Repositories\MateriasPrimasRepositorio;
use Piccolo\Repositories\ProveedoresRepositorio;

$materias = new MateriasPrimasController(new MateriasPrimasRepositorio($conexion), new ProveedoresRepositorio($conexion));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $materias->eliminar((int) ($_POST['txtID'] ?? 0));
        piccolo_admin_aviso('success', 'Materia prima eliminada correctamente.');
    } catch (ApiException $error) {
        piccolo_admin_aviso($error->status() === 404 ? 'warning' : 'danger', $error->getMessage());
    }
    header('Location: index.php');
    exit;
}

$soloCriticos = isset($_GET['criticos']);

piccolo_admin_pagina('materias_primas/lista', [
    'materias' => $materias->listar($soloCriticos),
    'soloCriticos' => $soloCriticos,
    'aviso' => piccolo_admin_tomar_aviso(),
]);
