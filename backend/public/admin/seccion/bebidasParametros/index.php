<?php
require_once __DIR__ . '/../../../../src/Http/Admin/acceso.php'; // permisos, sesión y CSRF del panel
require_once piccolo_path('src/Core/database.php');

use Piccolo\Http\Admin\BebidasController;
use Piccolo\Http\Admin\PoolController;
use Piccolo\Http\Api\ApiException;
use Piccolo\Repositories\BebidasRepositorio;
use Piccolo\Repositories\PoolRepositorio;

$bebidas = new BebidasController(new BebidasRepositorio($conexion), new PoolController(new PoolRepositorio($conexion), $conexion), $conexion);
$aviso = piccolo_admin_tomar_aviso();
$errores = [];
$enviada = null; // lo que se envió, si no se pudo guardar (para no perderlo)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    try {
        $bebida = $bebidas->guardarBebida($_POST + ['activa' => false], $id);
        piccolo_admin_aviso('success', $id > 0 ? "«{$bebida['nombre']}» se actualizó." : "«{$bebida['nombre']}» se agregó.");
        header('Location: index.php');
        exit;
    } catch (ApiException $error) {
        $errores = $error->errores();
        $aviso = ['tipo' => 'danger', 'mensaje' => $errores ? implode(' ', $errores) : $error->getMessage()];
        $enviada = array_intersect_key($_POST, array_flip(['nombre', 'valor_jarra', 'valor_promo', 'jarras_por_promo'])) + [
            'id' => $id,
            'activa' => isset($_POST['activa']),
        ];
    }
}

piccolo_admin_pagina('bebidas/catalogo', [
    'bebidas' => $bebidas->catalogo(),
    'enviada' => $enviada,
    'errores' => $errores,
    'aviso' => $aviso,
]);
