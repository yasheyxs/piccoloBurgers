<?php
require_once __DIR__ . '/../../src/Http/Admin/acceso.php'; // permisos, sesión y CSRF del panel

$mensaje = null;
$usuario = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim((string) ($_POST['usuario'] ?? ''));
    if (!piccolo_csrf_valido()) {
        $mensaje = 'La página de ingreso venció. Probá de nuevo.';
    } else {
        require_once piccolo_path('src/Core/database.php');
        require_once piccolo_path('src/Services/Security/login_panel.php');

        $resultado = piccoloPanelAutenticar($conexion, $usuario, (string) ($_POST['password'] ?? ''));
        if ($resultado['usuario'] !== null) {
            piccolo_admin_guardar_sesion($resultado['usuario']);

            // En las pantallas del panel la cookie de sesión no viaja desde otros sitios
            $cookie = session_get_cookie_params();
            setcookie(session_name(), session_id(), [
                'expires' => 0,
                'path' => $cookie['path'],
                'domain' => $cookie['domain'],
                'secure' => piccolo_detect_scheme() === 'https',
                'httponly' => true,
                'samesite' => 'Strict',
            ]);

            header('Location: index.php');
            exit;
        }
        $mensaje = piccoloPanelMensajeRechazo($resultado);
    }
}

piccolo_render(piccolo_path('views/admin/login.php'), [
    'mensaje' => $mensaje,
    'usuario' => $usuario,
    'aviso' => piccolo_admin_tomar_aviso(),
    'csrf' => piccolo_csrf_token(),
    'url_publica' => piccolo_public_base_url(),
]);
