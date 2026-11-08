<?php

/**
 * Sesión y permisos del panel: funciones que usan tanto las pantallas (a través de acceso.php)
 * como la API del panel (/api/v1/admin/...). Cargar este archivo no controla nada por sí solo.
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
            || in_array($pagina, ['actualizar_estado.php', 'obtener_pedidos_delivery.php', 'cambiar_rol.php'], true);
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


if (!function_exists('piccolo_admin_sesion_vigente')) {
    /**
     * Si hay un usuario del panel con sesión activa. Cierra la sesión si pasó el tiempo de
     * inactividad y, si sigue vigente, renueva la marca de actividad.
     */
    function piccolo_admin_sesion_vigente(): bool
    {
        if (MODO_DESARROLLO) {
            $_SESSION['admin_usuario'] = USUARIO_DESARROLLO;
            $_SESSION['admin_logueado'] = true;
            $_SESSION['rol_real'] = 'admin';
            $_SESSION['rol'] = piccolo_admin_rol_efectivo();
        }

        if (empty($_SESSION['admin_logueado'])) {
            return false;
        }

        $ultima = (int) ($_SESSION['admin_ultima_actividad'] ?? time());
        if (time() - $ultima > PICCOLO_ADMIN_MINUTOS_INACTIVIDAD * 60) {
            piccolo_admin_cerrar_sesion();
            return false;
        }

        // El usuario puede haber sido borrado o cambiado de rol mientras tenía la sesión abierta
        if (!MODO_DESARROLLO && !piccolo_admin_actualizar_usuario()) {
            piccolo_admin_cerrar_sesion();
            return false;
        }

        $_SESSION['admin_ultima_actividad'] = time();
        return true;
    }
}

if (!function_exists('piccolo_admin_actualizar_usuario')) {
    /**
     * Vuelve a leer de la base el usuario de la sesión y actualiza su nombre y su rol.
     * Devuelve false si ya no existe (por ejemplo, porque lo borraron desde Usuarios).
     */
    function piccolo_admin_actualizar_usuario(): bool
    {
        require_once piccolo_path('src/Core/database.php');
        $conexion = $GLOBALS['conexion'];

        if (!empty($_SESSION['admin_id'])) {
            $stmt = $conexion->prepare('SELECT ID, usuario, rol FROM tbl_usuarios WHERE ID = ?');
            $stmt->execute([(int) $_SESSION['admin_id']]);
        } else {
            // Sesiones iniciadas antes de guardar el id
            $stmt = $conexion->prepare('SELECT ID, usuario, rol FROM tbl_usuarios WHERE usuario = ?');
            $stmt->execute([(string) ($_SESSION['admin_usuario'] ?? '')]);
        }
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$usuario) {
            return false;
        }

        $_SESSION['admin_id'] = (int) $usuario['ID'];
        $_SESSION['admin_usuario'] = $usuario['usuario'];
        $_SESSION['rol_real'] = $usuario['rol'];
        $_SESSION['rol'] = piccolo_admin_rol_efectivo();

        return true;
    }
}

if (!function_exists('piccolo_admin_rol_efectivo')) {
    /**
     * Rol con el que se usa el panel. Un administrador puede elegir ver el panel como empleado o
     * delivery ("vista rápida", para probar lo que ven); el resto siempre usa su propio rol.
     */
    function piccolo_admin_rol_efectivo(): string
    {
        $real = (string) ($_SESSION['rol_real'] ?? '');
        $vista = (string) ($_SESSION['rol_vista'] ?? '');

        return $real === 'admin' && in_array($vista, ['empleado', 'delivery'], true) ? $vista : $real;
    }
}

if (!function_exists('piccolo_admin_guardar_sesion')) {
    /** Deja iniciada la sesión del panel para un usuario de tbl_usuarios. */
    function piccolo_admin_guardar_sesion(array $usuario): void
    {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int) $usuario['ID'];
        $_SESSION['admin_usuario'] = $usuario['usuario'];
        $_SESSION['admin_logueado'] = true;
        $_SESSION['rol_real'] = $usuario['rol'];
        $_SESSION['rol'] = $usuario['rol'];
        $_SESSION['admin_ultima_actividad'] = time();
        unset($_SESSION['rol_vista']);
        unset($_SESSION['csrf_token']);
    }
}

if (!function_exists('piccolo_admin_cerrar_sesion')) {
    function piccolo_admin_cerrar_sesion(): void
    {
        unset($_SESSION['admin_logueado'], $_SESSION['admin_id'], $_SESSION['admin_usuario'], $_SESSION['rol'], $_SESSION['rol_real'], $_SESSION['rol_vista'], $_SESSION['admin_ultima_actividad']);
    }
}

if (!function_exists('piccolo_admin_aviso')) {
    /** Guarda un aviso para mostrar en la próxima pantalla (por ejemplo, después de guardar y redirigir). */
    function piccolo_admin_aviso(string $tipo, string $mensaje): void
    {
        $_SESSION['admin_aviso'] = ['tipo' => $tipo, 'mensaje' => $mensaje];
    }
}

if (!function_exists('piccolo_admin_tomar_aviso')) {
    /** @return array{tipo: string, mensaje: string}|null */
    function piccolo_admin_tomar_aviso(): ?array
    {
        $aviso = $_SESSION['admin_aviso'] ?? null;
        unset($_SESSION['admin_aviso']);

        return is_array($aviso) ? $aviso : null;
    }
}

if (!function_exists('piccolo_admin_pagina')) {
    /**
     * Muestra una pantalla del panel: encabezado y menú, la plantilla indicada y el pie.
     * Las plantillas viven en views/admin/ y solo reciben los datos ya preparados.
     */
    function piccolo_admin_pagina(string $plantilla, array $datos = []): void
    {
        $url_base = piccolo_admin_base_url();
        include piccolo_path('views/admin/header.php');
        piccolo_render(piccolo_path('views/admin/' . $plantilla . '.php'), $datos + ['url_base' => $url_base]);
        include piccolo_path('views/admin/footer.php');
    }
}

if (!function_exists('piccolo_admin_pagina_publica')) {
    /** Como piccolo_admin_pagina(), para las pantallas sin sesión (recuperar contraseña). */
    function piccolo_admin_pagina_publica(string $plantilla, array $datos = []): void
    {
        $url_base = piccolo_admin_base_url();
        include piccolo_path('views/admin/header_public.php');
        piccolo_render(piccolo_path('views/admin/' . $plantilla . '.php'), $datos + ['url_base' => $url_base, 'csrf' => piccolo_csrf_token()]);
        include piccolo_path('views/admin/footer.php');
    }
}
