<?php
if (!function_exists('piccolo_detect_scheme')) {
    function piccolo_detect_scheme(): string
    {
        $https = $_SERVER['HTTPS'] ?? '';
        if (!empty($https) && strtolower($https) !== 'off') {
            return 'https';
        }

        $forwardedProto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
        if (!empty($forwardedProto)) {
            $protoParts = explode(',', $forwardedProto);
            $primaryProto = strtolower(trim($protoParts[0]));
            if ($primaryProto === 'https') {
                return 'https';
            }
        }

        $forwardedSsl = $_SERVER['HTTP_X_FORWARDED_SSL'] ?? '';
        if (!empty($forwardedSsl) && strtolower($forwardedSsl) === 'on') {
            return 'https';
        }

        $serverPort = $_SERVER['SERVER_PORT'] ?? '';
        if ($serverPort === '443') {
            return 'https';
        }

        return 'http';
    }
}

if (!function_exists('piccolo_detect_host')) {
    function piccolo_detect_host(): string
    {
        $forwardedHost = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? '';
        if (!empty($forwardedHost)) {
            $hostParts = explode(',', $forwardedHost);
            $primaryHost = trim($hostParts[0]);
            if ($primaryHost !== '') {
                return $primaryHost;
            }
        }

        $host = $_SERVER['HTTP_HOST'] ?? '';
        if (!empty($host)) {
            return $host;
        }

        $serverName = $_SERVER['SERVER_NAME'] ?? '';
        if (!empty($serverName)) {
            return $serverName;
        }

        return 'localhost';
    }
}

if (!function_exists('piccolo_admin_base_path')) {
    function piccolo_admin_base_path(): string
    {
        $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
        if (is_string($requestPath) && $requestPath !== '') {
            $basePath = preg_replace('#/admin(?:/.*)?$#', '/admin/', $requestPath, 1);
            if (!empty($basePath)) {
                return rtrim($basePath, '/') . '/';
            }
        }

        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        if (is_string($scriptName) && $scriptName !== '') {
            $basePath = preg_replace('#/admin(?:/.*)?$#', '/admin/', $scriptName, 1);
            if (!empty($basePath)) {
                return rtrim($basePath, '/') . '/';
            }
        }

        return '/admin/';
    }
}

if (!function_exists('piccolo_admin_base_url')) {
    function piccolo_admin_base_url(): string
    {
        $scheme = piccolo_detect_scheme();
        $host = piccolo_detect_host();
        $basePath = piccolo_admin_base_path();

        return rtrim(sprintf('%s://%s%s', $scheme, $host, $basePath), '/') . '/';
    }
}

if (!function_exists('piccolo_public_base_path')) {
    function piccolo_public_base_path(): string
    {
        $adminBasePath = piccolo_admin_base_path();
        $publicPath = preg_replace('#/admin/?$#', '/', $adminBasePath, 1);

        if (is_string($publicPath) && $publicPath !== '') {
            return rtrim($publicPath, '/') . '/';
        }

        return '/';
    }
}

if (!function_exists('piccolo_public_base_url')) {
    function piccolo_public_base_url(): string
    {
        $scheme = piccolo_detect_scheme();
        $host = piccolo_detect_host();
        $basePath = piccolo_public_base_path();

        return rtrim(sprintf('%s://%s%s', $scheme, $host, $basePath), '/') . '/';
    }
}