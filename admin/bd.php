<?php
require_once __DIR__ . '/../app/Core/bootstrap.php';

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
                || stripos($uri, '/api/') !== false
                || str_ends_with($uri, '.php') && stripos($uri, 'guardar_pedido.php') !== false;

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

$requiredEnv = ['MYSQL_HOST', 'MYSQL_DATABASE', 'MYSQL_USER'];
$configuracion = [];

foreach ($requiredEnv as $variable) {
    $valor = getenv($variable);
    if ($valor === false || $valor === '') {
        piccolo_finalizar_por_error_bd(
            'Configuración de base de datos incompleta. Falta la variable de entorno ' . $variable,
            'Error de configuración de la base de datos'
        );
    }
    $configuracion[$variable] = $valor;
}

$passwordEnv = getenv('MYSQL_PASSWORD');
$configuracion['MYSQL_PASSWORD'] = $passwordEnv === false ? '' : $passwordEnv;

$servidor    = $configuracion['MYSQL_HOST'];
$baseDatos   = $configuracion['MYSQL_DATABASE'];
$usuario     = $configuracion['MYSQL_USER'];
$contrasenia = $configuracion['MYSQL_PASSWORD'];
$puerto      = getenv('MYSQL_PORT') ?: '3306';

try {
    $dsn = "mysql:host={$servidor};port={$puerto};dbname={$baseDatos};charset=utf8mb4";
    $opciones = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_PERSISTENT         => false,
    ];
    $conexion = new PDO($dsn, $usuario, $contrasenia, $opciones);
} catch (Exception $error) {
     piccolo_finalizar_por_error_bd(
        'Error al conectar a la base de datos: ' . $error->getMessage(),
        'Error de conexión a la base de datos'
    );
}

if (!function_exists('piccolo_columna_existe')) {
    /**
     * Verifica si una columna existe en la tabla indicada del esquema actual.
     */
    function piccolo_columna_existe(PDO $conexion, string $tabla, string $columna): bool
    {
        $consulta = $conexion->prepare(
            'SELECT COUNT(*)
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = :tabla
               AND COLUMN_NAME = :columna'
        );

        $consulta->execute([
            ':tabla'   => $tabla,
            ':columna' => $columna,
        ]);

        return (bool) $consulta->fetchColumn();
    }
}

function verificarRol(string $rolPermitido): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $usuarioAutenticado = $_SESSION['admin_usuario'] ?? null;
    $rolSesion = $_SESSION['rol'] ?? null;

    if ($usuarioAutenticado === null || $rolSesion !== $rolPermitido) {
        $loginUrl = piccolo_admin_base_url() . 'login.php';
        header('Location: ' . $loginUrl);
        exit();
    }
}
