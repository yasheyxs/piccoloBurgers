<?php

if (!defined('PICCOLO_ROOT')) {
    define('PICCOLO_ROOT', dirname(__DIR__, 2));
}

if (!defined('PICCOLO_VIEWS_ADMIN')) {
    define('PICCOLO_VIEWS_ADMIN', PICCOLO_ROOT . '/views/admin');
}

if (!function_exists('piccolo_path')) {
    function piccolo_path(string $path = ''): string
    {
        return rtrim(PICCOLO_ROOT . '/' . ltrim($path, '/'), '/');
    }
}

if (!function_exists('piccolo_start_session')) {
    function piccolo_start_session(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}

if (!function_exists('piccolo_leer_env_file')) {
    /** @return array<string, string> variables definidas en un archivo .env */
    function piccolo_leer_env_file(string $path): array
    {
        if (!is_file($path) || !is_readable($path)) {
            return [];
        }

        $variables = [];
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = array_map('trim', explode('=', $line, 2));
            if ($key !== '') {
                $variables[$key] = trim($value, "\"'");
            }
        }

        return $variables;
    }
}

if (!function_exists('piccolo_env')) {
    /**
     * Valor de una variable de configuración.
     * Prioridad: variables reales del entorno (Docker, hosting) > .env.local > .env
     *
     * Las variables de los archivos se guardan en memoria y no con putenv(): en servidores
     * con hilos (como el Apache de XAMPP) putenv() se comparte entre peticiones simultáneas
     * y PHP borra esas variables al terminar cada una, dejando a las demás sin configuración.
     */
    function piccolo_env(string $clave, ?string $porDefecto = null): ?string
    {
        static $archivos = null;

        $real = getenv($clave);
        if ($real !== false) {
            return $real;
        }

        if ($archivos === null) {
            $archivos = piccolo_leer_env_file(PICCOLO_ROOT . '/.env.local') + piccolo_leer_env_file(PICCOLO_ROOT . '/.env');
        }

        return $archivos[$clave] ?? $porDefecto;
    }
}

if (!function_exists('requireConnection')) {
    function requireConnection(PDO $conn = null): PDO
    {
        if ($conn === null) {
            throw new RuntimeException('No se encontro la conexion a la base de datos.');
        }

        return $conn;
    }
}

if (!function_exists('piccolo_detect_scheme')) {
    function piccolo_detect_scheme(): string
    {
        $https = $_SERVER['HTTPS'] ?? '';
        if (!empty($https) && strtolower((string) $https) !== 'off') {
            return 'https';
        }

        $forwardedProto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
        if (!empty($forwardedProto)) {
            $protoParts = explode(',', (string) $forwardedProto);
            if (strtolower(trim($protoParts[0])) === 'https') {
                return 'https';
            }
        }

        if (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_SSL'] ?? '')) === 'on') {
            return 'https';
        }

        return (string) ($_SERVER['SERVER_PORT'] ?? '') === '443' ? 'https' : 'http';
    }
}

if (!function_exists('piccolo_detect_host')) {
    function piccolo_detect_host(): string
    {
        $forwardedHost = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? '';
        if (!empty($forwardedHost)) {
            $hostParts = explode(',', (string) $forwardedHost);
            $primaryHost = trim($hostParts[0]);
            if ($primaryHost !== '') {
                return $primaryHost;
            }
        }

        return (string) ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost');
    }
}

if (!function_exists('piccolo_public_base_path_desde_script')) {
    /**
     * Calcula la URL base de backend/public a partir del script que se esta ejecutando.
     * Funciona igual con XAMPP (/piccoloBurgers/backend/public/) y con Docker (/).
     */
    function piccolo_public_base_path_desde_script(): ?string
    {
        $scriptFile = (string) ($_SERVER['SCRIPT_FILENAME'] ?? '');
        $scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
        $publicDir = realpath(PICCOLO_ROOT . '/public');
        $scriptReal = $scriptFile !== '' ? realpath($scriptFile) : false;

        if ($scriptName === '' || $publicDir === false || $scriptReal === false) {
            return null;
        }

        $publicDir = rtrim(str_replace('\\', '/', $publicDir), '/') . '/';
        $scriptReal = str_replace('\\', '/', $scriptReal);
        if (stripos($scriptReal, $publicDir) !== 0) {
            return null;
        }

        $relativo = substr($scriptReal, strlen($publicDir));
        if (!str_ends_with($scriptName, $relativo)) {
            return null;
        }

        return rtrim(substr($scriptName, 0, -strlen($relativo)), '/') . '/';
    }
}

if (!function_exists('piccolo_admin_base_path')) {
    function piccolo_admin_base_path(): string
    {
        // El panel vive en backend/public/admin, asi que cuelga de la base publica
        $publicBase = piccolo_public_base_path_desde_script();
        if ($publicBase !== null) {
            return $publicBase . 'admin/';
        }

        foreach ([$_SERVER['REQUEST_URI'] ?? '', $_SERVER['SCRIPT_NAME'] ?? ''] as $candidate) {
            $path = parse_url((string) $candidate, PHP_URL_PATH) ?: '';
            if ($path !== '' && str_contains($path, '/admin')) {
                $basePath = preg_replace('#/admin(?:/.*)?$#', '/admin/', $path, 1);
                if (is_string($basePath) && $basePath !== '') {
                    return rtrim($basePath, '/') . '/';
                }
            }
        }

        return '/admin/';
    }
}

if (!function_exists('piccolo_public_base_path')) {
    function piccolo_public_base_path(): string
    {
        $publicBase = piccolo_public_base_path_desde_script();
        if ($publicBase !== null) {
            return $publicBase;
        }

        $scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
        if ($scriptName !== '' && str_contains($scriptName, '/public/')) {
            $basePath = preg_replace('#/public(?:/.*)?$#', '/public/', $scriptName, 1);
            return rtrim((string) $basePath, '/') . '/';
        }

        $adminBasePath = piccolo_admin_base_path();
        if ($adminBasePath !== '/admin/') {
            $publicPath = preg_replace('#/admin/?$#', '/', $adminBasePath, 1);
        } else {
            $publicPath = '/';
        }

        return rtrim((string) ($publicPath ?: '/'), '/') . '/';
    }
}

if (!function_exists('piccolo_admin_base_url')) {
    function piccolo_admin_base_url(): string
    {
        return rtrim(piccolo_detect_scheme() . '://' . piccolo_detect_host() . piccolo_admin_base_path(), '/') . '/';
    }
}

if (!function_exists('piccolo_public_base_url')) {
    function piccolo_public_base_url(): string
    {
        return rtrim(piccolo_detect_scheme() . '://' . piccolo_detect_host() . piccolo_public_base_path(), '/') . '/';
    }
}

if (!function_exists('piccolo_frontend_url')) {
    /**
     * Dirección del sitio de clientes (frontend/).
     * - Si FRONTEND_URL está configurada, se usa la primera dirección.
     * - Con XAMPP se deduce de la ruta del backend (/backend/public/ -> /frontend/public/).
     * - Si no, se asume que el frontend se publica en la misma dirección que el backend (con un proxy).
     */
    function piccolo_frontend_url(string $ruta = ''): string
    {
        $configurada = trim(explode(',', (string) (piccolo_env('FRONTEND_URL') ?? ''))[0]);
        if ($configurada !== '') {
            $base = rtrim($configurada, '/') . '/';
        } else {
            $base = piccolo_public_base_url();
            if (str_contains($base, '/backend/public/')) {
                $base = str_replace('/backend/public/', '/frontend/public/', $base);
            }
        }

        return $base . ltrim($ruta, '/');
    }
}

if (!function_exists('public_url')) {
    function public_url(string $path = ''): string
    {
        return piccolo_public_base_path() . ltrim($path, '/');
    }
}

if (!function_exists('admin_url')) {
    function admin_url(string $path = ''): string
    {
        return piccolo_admin_base_path() . ltrim($path, '/');
    }
}

if (!function_exists('asset_url')) {
    function asset_url(string $path, string $area = 'customer'): string
    {
        $path = ltrim($path, '/');

        if ($area === 'backoffice' || $area === 'admin') {
            return admin_url($path);
        }

        return public_url($path);
    }
}

if (!function_exists('piccolo_render')) {
    function piccolo_render(string $viewPath, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        require $viewPath;
    }
}

if (!function_exists('piccolo_e')) {
    /** Escapa un texto para mostrarlo en HTML sin riesgo de inyección. */
    function piccolo_e(mixed $valor): string
    {
        return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
    }
}
