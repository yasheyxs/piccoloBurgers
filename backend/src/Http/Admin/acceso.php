<?php

/**
 * Control de acceso del panel. Todas las pantallas de public/admin/ lo cargan en su primera línea.
 *
 * Para cada petición:
 *  1. Inicia la sesión del panel con una cookie segura.
 *  2. Si la pantalla no es pública, exige sesión y un rol permitido (config/permisos.php).
 *  3. Cierra la sesión después de un tiempo sin actividad.
 *  4. En cualquier envío de datos (POST, PUT, DELETE) exige el token CSRF.
 *
 * Las respuestas de error son JSON para los endpoints que usa JavaScript y una página o
 * redirección al login para las pantallas.
 */

require_once __DIR__ . '/../../Core/bootstrap.php';
require_once piccolo_path('config/config.php');

const PICCOLO_ADMIN_MINUTOS_INACTIVIDAD = 120;

if (!function_exists('piccolo_admin_pagina_actual')) {
    /** Ruta del script actual relativa a public/admin/, por ejemplo "seccion/menu/index.php". */
    function piccolo_admin_pagina_actual(): string
    {
        $adminDir = realpath(PICCOLO_ROOT . '/public/admin');
        $script = realpath((string) ($_SERVER['SCRIPT_FILENAME'] ?? ''));
        if ($adminDir === false || $script === false) {
            return '';
        }

        $adminDir = rtrim(str_replace('\\', '/', $adminDir), '/') . '/';
        $script = str_replace('\\', '/', $script);

        return stripos($script, $adminDir) === 0 ? substr($script, strlen($adminDir)) : '';
    }
}

if (!function_exists('piccolo_admin_roles_permitidos')) {
    /**
     * Roles que pueden abrir una pantalla, o null si es pública.
     * @return string[]|null
     */
    function piccolo_admin_roles_permitidos(string $pagina): ?array
    {
        static $permisos = null;
        $permisos ??= require piccolo_path('config/permisos.php');

        if (in_array($pagina, $permisos['publicas'], true)) {
            return null;
        }

        if (isset($permisos['roles'][$pagina])) {
            return $permisos['roles'][$pagina];
        }

        // Reglas por carpeta: la más específica gana
        $mejor = null;
        foreach ($permisos['roles'] as $ruta => $roles) {
            if (str_ends_with($ruta, '/') && str_starts_with($pagina, $ruta)
                && ($mejor === null || strlen($ruta) > strlen($mejor))) {
                $mejor = $ruta;
            }
        }

        return $mejor !== null ? $permisos['roles'][$mejor] : ['admin'];
    }
}

if (!function_exists('piccolo_admin_puede')) {
    /** Si el usuario con sesión puede abrir una pantalla. Lo usa también el menú. */
    function piccolo_admin_puede(string $pagina): bool
    {
        $roles = piccolo_admin_roles_permitidos($pagina);
        return $roles === null || in_array($_SESSION['rol'] ?? '', $roles, true);
    }
}

if (!function_exists('piccolo_admin_espera_json')) {
    function piccolo_admin_espera_json(string $pagina): bool
    {
        $accept = (string) ($_SERVER['HTTP_ACCEPT'] ?? '');
        return str_contains($accept, 'application/json')
            || strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest'
            || str_starts_with($pagina, 'api/')
            || str_ends_with($pagina, '/api.php')
            || in_array($pagina, ['actualizar_estado.php', 'obtener_pedidos_delivery.php', 'obtener_pedidos_cliente.php', 'cambiar_rol.php'], true);
    }
}

if (!function_exists('piccolo_admin_rechazar')) {
    function piccolo_admin_rechazar(int $status, string $mensaje, string $pagina): void
    {
        http_response_code($status);

        if (piccolo_admin_espera_json($pagina)) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['exito' => false, 'mensaje' => $mensaje], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($status === 401) {
            header('Location: ' . piccolo_admin_base_url() . 'login.php');
            exit;
        }

        header('Content-Type: text/html; charset=utf-8');
        $inicio = htmlspecialchars(piccolo_admin_base_url(), ENT_QUOTES, 'UTF-8');
        echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<title>Sin permiso</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"></head>'
            . '<body class="bg-dark text-light d-flex align-items-center justify-content-center" style="min-height:100vh">'
            . '<div class="text-center p-4"><h1 class="h4 mb-3">No tenés permiso para entrar a esta sección</h1>'
            . '<p class="text-secondary mb-4">' . htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') . '</p>'
            . '<a class="btn btn-warning" href="' . $inicio . '">Volver al inicio del panel</a></div></body></html>';
        exit;
    }
}

if (!function_exists('piccolo_admin_iniciar_sesion')) {
    function piccolo_admin_iniciar_sesion(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => piccolo_detect_scheme() === 'https',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

if (!function_exists('piccolo_csrf_token')) {
    function piccolo_csrf_token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('piccolo_csrf_valido')) {
    function piccolo_csrf_valido(): bool
    {
        $enviado = (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? ''));
        $esperado = (string) ($_SESSION['csrf_token'] ?? '');
        return $esperado !== '' && $enviado !== '' && hash_equals($esperado, $enviado);
    }
}

(static function (): void {
    piccolo_admin_iniciar_sesion();
    $pagina = piccolo_admin_pagina_actual();
    $roles = piccolo_admin_roles_permitidos($pagina);

    if ($roles === null) {
        piccolo_csrf_token();
        return;
    }

    if (MODO_DESARROLLO) {
        $_SESSION['admin_usuario'] = USUARIO_DESARROLLO;
        $_SESSION['admin_logueado'] = true;
        $_SESSION['rol'] = 'admin';
    }

    if (empty($_SESSION['admin_logueado'])) {
        piccolo_admin_rechazar(401, 'Tenés que iniciar sesión en el panel.', $pagina);
    }

    $ultima = (int) ($_SESSION['admin_ultima_actividad'] ?? time());
    if (time() - $ultima > PICCOLO_ADMIN_MINUTOS_INACTIVIDAD * 60) {
        unset($_SESSION['admin_logueado'], $_SESSION['admin_usuario'], $_SESSION['rol'], $_SESSION['admin_ultima_actividad']);
        piccolo_admin_rechazar(401, 'La sesión se cerró por inactividad.', $pagina);
    }
    $_SESSION['admin_ultima_actividad'] = time();

    if (!in_array($_SESSION['rol'] ?? '', $roles, true)) {
        piccolo_admin_rechazar(403, 'Tu usuario no tiene acceso a esta sección.', $pagina);
    }

    piccolo_csrf_token();
    $metodo = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    if (!in_array($metodo, ['GET', 'HEAD', 'OPTIONS'], true) && !piccolo_csrf_valido()) {
        piccolo_admin_rechazar(403, 'La sesión de este formulario venció. Recargá la página e intentá de nuevo.', $pagina);
    }
})();
