<?php
/**
 * "Vista rápida" de la portada: un administrador elige ver el panel como empleado o delivery,
 * para probar lo que ven, y puede volver a su vista cuando quiera. Responde {exito, rol, vista}.
 */
require_once __DIR__ . '/../../src/Http/Admin/acceso.php'; // permisos, sesión y CSRF del panel

header('Content-Type: application/json; charset=utf-8');

$etiquetas = ['admin' => 'Administrador', 'empleado' => 'Empleado', 'delivery' => 'Delivery'];
$responder = static function (int $status, array $cuerpo): never {
    http_response_code($status);
    echo json_encode($cuerpo, JSON_UNESCAPED_UNICODE);
    exit;
};

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $responder(405, ['exito' => false, 'mensaje' => 'Método no permitido.']);
}
// Se mira el rol real, no la vista elegida: así un administrador "viendo como empleado" puede volver
if (($_SESSION['rol_real'] ?? '') !== 'admin') {
    $responder(403, ['exito' => false, 'mensaje' => 'Solo un administrador puede cambiar la vista.']);
}

$rol = (string) ($_POST['rol'] ?? '');
if (!isset($etiquetas[$rol])) {
    $responder(422, ['exito' => false, 'mensaje' => 'Elegí una vista de la lista.']);
}

if ($rol === 'admin') {
    unset($_SESSION['rol_vista']);
} else {
    $_SESSION['rol_vista'] = $rol;
}
$_SESSION['rol'] = piccolo_admin_rol_efectivo();

$responder(200, ['exito' => true, 'rol' => $rol, 'vista' => $etiquetas[$rol]]);
