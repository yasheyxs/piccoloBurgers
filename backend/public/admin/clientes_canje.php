<?php
require_once __DIR__ . '/../../src/Http/Admin/acceso.php'; // permisos, sesión y CSRF del panel
require_once piccolo_path('src/Core/database.php');

use Piccolo\Http\Admin\CanjeController;
use Piccolo\Http\Api\ApiException;
use Piccolo\Repositories\CanjeRepositorio;
use Piccolo\Repositories\ClientesRepositorio;

$canje = new CanjeController(new ClientesRepositorio($conexion), new CanjeRepositorio($conexion), $conexion);
$clienteId = (int) ($_GET['id'] ?? 0);

// Operaciones que manda la pantalla (assets/js/clientes_canje.js) en JSON
if ($_SERVER['REQUEST_METHOD'] === 'POST' && stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
    header('Content-Type: application/json; charset=utf-8');
    $datos = json_decode((string) file_get_contents('php://input'), true);
    $responder = static function (int $status, array $cuerpo): never {
        http_response_code($status);
        echo json_encode($cuerpo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    };

    if (!is_array($datos)) {
        $responder(400, ['exito' => false, 'mensaje' => 'No se pudieron leer los datos enviados.']);
    }
    $token = (string) ($datos['csrf_token'] ?? '');
    if ($token === '' || !hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $token)) {
        $responder(403, ['exito' => false, 'mensaje' => 'El token de seguridad no es válido. Actualizá la página e intentá de nuevo.']);
    }

    $clienteId = (int) ($datos['clienteId'] ?? 0);
    try {
        $resumen = match ($datos['accion'] ?? '') {
            'registrar_descuento' => $canje->registrarVenta($clienteId, $datos, session_id()),
            'canjear_premios' => $canje->canjearPremios($clienteId, $datos),
            default => throw new ApiException('La acción pedida no existe.', 400),
        };
        $responder(200, ['exito' => true, 'mensaje' => $resumen['mensaje'], 'resumen' => $resumen]);
    } catch (ApiException $error) {
        $mensaje = $error->errores() ? implode(' ', $error->errores()) : $error->getMessage();
        $responder($error->status(), ['exito' => false, 'mensaje' => $mensaje]);
    } catch (Throwable $error) {
        error_log('Error en el canje de puntos: ' . $error->getMessage());
        $responder(500, ['exito' => false, 'mensaje' => 'Ocurrió un error inesperado. Intentá de nuevo en unos minutos.']);
    }
}

try {
    $datosCanje = $canje->datos($clienteId);
} catch (ApiException $error) {
    piccolo_admin_aviso('warning', 'No encontramos al cliente que querés ver.');
    header('Location: clientes.php');
    exit;
}

piccolo_admin_pagina('clientes/canje', [
    'canje' => $datosCanje,
    'csrf' => (string) ($_SESSION['csrf_token'] ?? ''),
]);
