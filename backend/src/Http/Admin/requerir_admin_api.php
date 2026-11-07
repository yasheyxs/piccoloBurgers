<?php

/**
 * Corta la petición con un 401 en JSON si no hay un usuario del panel con sesión iniciada.
 * Se usa en los endpoints internos del panel que responden JSON.
 */

require_once piccolo_path('config/config.php');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!MODO_DESARROLLO && empty($_SESSION['admin_logueado'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['exito' => false, 'mensaje' => 'Tenés que iniciar sesión en el panel.']);
    exit;
}
