<?php

if (!defined('PICCOLO_ROOT')) {
    define('PICCOLO_ROOT', dirname(__DIR__, 2));
}

if (!defined('PICCOLO_FRONTEND_CUSTOMER')) {
    define('PICCOLO_FRONTEND_CUSTOMER', PICCOLO_ROOT . '/frontend/customer');
}

if (!defined('PICCOLO_FRONTEND_BACKOFFICE')) {
    define('PICCOLO_FRONTEND_BACKOFFICE', PICCOLO_ROOT . '/frontend/backoffice');
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

if (!function_exists('piccolo_load_env_file')) {
    function piccolo_load_env_file(string $path, bool $overwrite = false): void
    {
        if (!is_file($path) || !is_readable($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = array_map('trim', explode('=', $line, 2));
            if ($key === '' || (!$overwrite && getenv($key) !== false)) {
                continue;
            }

            $value = trim($value, "\"'");
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

if (!function_exists('piccolo_load_environment')) {
    function piccolo_load_environment(): void
    {
        static $loaded = false;

        if ($loaded) {
            return;
        }

        piccolo_load_env_file(PICCOLO_ROOT . '/.env');
        piccolo_load_env_file(PICCOLO_ROOT . '/.env.local', true);
        $loaded = true;
    }
}

piccolo_load_environment();

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

if (!function_exists('piccolo_admin_base_path')) {
    function piccolo_admin_base_path(): string
    {
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
        $scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
        if ($scriptName !== '' && str_contains($scriptName, '/public/')) {
            $basePath = preg_replace('#/public(?:/.*)?$#', '/public/', $scriptName, 1);
            return rtrim((string) $basePath, '/') . '/';
        }

        $adminBasePath = piccolo_admin_base_path();
        if ($adminBasePath !== '/admin/') {
            $publicPath = preg_replace('#/admin/?$#', '/public/', $adminBasePath, 1);
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
