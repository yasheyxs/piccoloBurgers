<?php
require_once __DIR__ . '/bootstrap.php';

if (!function_exists('piccolo_finalizar_por_error_bd')) {
    function piccolo_finalizar_por_error_bd(string $mensajeLog, string $mensajeUsuario = 'Error de conexión a la base de datos'): void
    {
        error_log($mensajeLog);

        if (PHP_SAPI === 'cli') {
            fwrite(STDERR, $mensajeUsuario . PHP_EOL);
        } else {
            $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
            $requestedWith = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
            $uri = $_SERVER['REQUEST_URI'] ?? '';
            $esperaJson = stripos($accept, 'application/json') !== false
                || strtolower($requestedWith) === 'xmlhttprequest'
                || stripos($uri, '/api/') !== false;

            if (!headers_sent()) {
                http_response_code(500);
            }

            if ($esperaJson) {
                if (!headers_sent()) {
                    header('Content-Type: application/json');
                }
                echo json_encode([
                    "exito" => false,
                    "mensaje" => $mensajeUsuario
                ]);
            } else {
                if (!headers_sent()) {
                    header('Content-Type: text/html; charset=UTF-8');
                }
                echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
                echo '<title>Servicio no disponible</title><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/css/bootstrap.min.css" rel="stylesheet"></head>';
                echo '<body class="bg-light"><main class="container py-5"><div class="mx-auto card border-0 shadow-sm" style="max-width: 560px;">';
                echo '<div class="card-body text-center p-4"><div class="display-6 text-warning mb-3"><i class="fa-solid fa-triangle-exclamation"></i></div>';
                echo '<h1 class="h4 mb-3">No pudimos cargar esta seccion</h1>';
                echo '<p class="text-muted mb-4">Hubo un problema temporal al conectar con la base de datos. Intentalo nuevamente en unos instantes.</p>';
                echo '<a class="btn btn-primary" href="javascript:history.back()">Volver</a></div></div></main></body></html>';
            }
        }

        exit;
    }
}

if (!function_exists('piccolo_conectar_bd')) {
    /**
     * Abre la conexión con los datos de las variables de entorno (MYSQL_HOST, MYSQL_DATABASE,
     * MYSQL_USER, MYSQL_PASSWORD, MYSQL_PORT). Los datos de acceso quedan dentro de esta función:
     * no pisan variables de la página que incluye este archivo ni quedan a mano del resto del código.
     */
    function piccolo_conectar_bd(): PDO
    {
        $datos = [];
        foreach (['MYSQL_HOST', 'MYSQL_DATABASE', 'MYSQL_USER'] as $variable) {
            $valor = piccolo_env($variable);
            if ($valor === null || $valor === '') {
                piccolo_finalizar_por_error_bd(
                    'Configuración de base de datos incompleta. Falta la variable de entorno ' . $variable,
                    'Error de configuración de la base de datos'
                );
            }
            $datos[$variable] = $valor;
        }

        try {
            return new PDO(
                sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $datos['MYSQL_HOST'], piccolo_env('MYSQL_PORT') ?: '3306', $datos['MYSQL_DATABASE']),
                $datos['MYSQL_USER'],
                piccolo_env('MYSQL_PASSWORD') ?? '',
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_PERSISTENT => false,
                ]
            );
        } catch (Exception $error) {
            piccolo_finalizar_por_error_bd(
                'Error al conectar a la base de datos: ' . $error->getMessage(),
                'Error de conexión a la base de datos'
            );
            exit;
        }
    }
}

// Una sola conexión por petición: si ya se abrió (por ejemplo, al revisar la sesión del panel), se reutiliza.
$conexion = $GLOBALS['conexion'] = ($GLOBALS['conexion'] ?? null) instanceof PDO ? $GLOBALS['conexion'] : piccolo_conectar_bd();
