<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode([
    'exito' => false,
    'mensaje' => 'Método no permitido.'
  ]);
  exit;
}

if (empty($_SESSION['admin_usuario'])) {
  http_response_code(401);
  echo json_encode([
    'exito' => false,
    'mensaje' => 'Sesión no válida.'
  ]);
  exit;
}

require_once __DIR__ . '/bd.php';

try {
  $sentencia = $conexion->prepare('SELECT rol FROM tbl_usuarios WHERE usuario = :usuario LIMIT 1');
  $sentencia->execute([':usuario' => $_SESSION['admin_usuario']]);
  $usuario = $sentencia->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode([
    'exito' => false,
    'mensaje' => 'No se pudo validar la sesión.'
  ]);
  exit;
}

if (!$usuario || ($usuario['rol'] ?? '') !== 'admin') {
  http_response_code(403);
  echo json_encode([
    'exito' => false,
    'mensaje' => 'No contás con permisos suficientes.'
  ]);
  exit;
}

$tokenSesion = $_SESSION['csrf_token'] ?? '';
$tokenRecibido = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
if (!$tokenSesion || !$tokenRecibido || !hash_equals($tokenSesion, $tokenRecibido)) {
  http_response_code(403);
  echo json_encode([
    'exito' => false,
    'mensaje' => 'Token CSRF inválido.'
  ]);
  exit;
}

$rol = $_POST['rol'] ?? '';
$rolesPermitidos = ['admin', 'empleado', 'delivery'];

if (!in_array($rol, $rolesPermitidos, true)) {
  http_response_code(400);
  echo json_encode([
    'exito' => false,
    'mensaje' => 'Rol no permitido.'
  ]);
  exit;
}

$_SESSION['rol'] = $rol;

$etiquetas = [
  'admin' => 'Administrador',
  'empleado' => 'Empleado',
  'delivery' => 'Delivery'
];

echo json_encode([
  'exito' => true,
  'rol' => $rol,
  'vista' => $etiquetas[$rol] ?? ucfirst($rol)
]);