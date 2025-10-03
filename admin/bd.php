<?php
if (!function_exists('piccolo_finalizar_por_error_bd')) {
    function piccolo_finalizar_por_error_bd(string $mensajeLog, string $mensajeUsuario = 'Error de conexión a la base de datos'): void
    {
        error_log($mensajeLog);

        if (PHP_SAPI === 'cli') {
            fwrite(STDERR, $mensajeUsuario . PHP_EOL);
        } else {
            if (!headers_sent()) {
                http_response_code(500);
                header('Content-Type: application/json');
            }
            echo json_encode([
                "exito"   => false,
                "mensaje" => $mensajeUsuario
            ]);
        }

        exit;
    }
}

$requiredEnv = ['MYSQL_HOST', 'MYSQL_DATABASE', 'MYSQL_USER', 'MYSQL_PASSWORD'];
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

$servidor    = $configuracion['MYSQL_HOST'];
$baseDatos   = $configuracion['MYSQL_DATABASE'];
$usuario     = $configuracion['MYSQL_USER'];
$contrasenia = $configuracion['MYSQL_PASSWORD'];

try {
    $dsn = "mysql:host={$servidor};dbname={$baseDatos};charset=utf8mb4";
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

function verificarRol($rolPermitido) {
    session_start();
    if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== $rolPermitido) {
        header("Location: ../login.php");
        exit();
    }
}
