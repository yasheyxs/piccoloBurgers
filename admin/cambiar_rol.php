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

if (!isset($_SESSION['admin_logueado']) || $_SESSION['admin_logueado'] !== true) {
  http_response_code(401);
  echo json_encode([
    'exito' => false,
    'mensaje' => 'Sesión no válida.'
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