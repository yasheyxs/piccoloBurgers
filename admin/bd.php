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
$faltantes = [];

// 1. Intentar primero con variables de entorno (Docker)
foreach ($requiredEnv as $variable) {
    $valor = getenv($variable) ?: ($_ENV[$variable] ?? $_SERVER[$variable] ?? null);
    if (is_string($valor)) {
        $valor = trim($valor);
    }
    if ($valor === false || $valor === '' || $valor === null) {
        $faltantes[] = $variable;
        continue;
    }
    $configuracion[$variable] = $valor;
}

// 2. Si faltan, cargar config local o docker
if (!empty($faltantes)) {
    $rutaLocal   = dirname(__DIR__) . '/config/database.php';
    $rutaDocker  = dirname(__DIR__) . '/config/database.docker.php';

    if (getenv('MYSQL_HOST') && is_readable($rutaDocker)) {
        $configuracionLocal = include $rutaDocker;
    } elseif (is_readable($rutaLocal)) {
        $configuracionLocal = include $rutaLocal;
    } else {
        $configuracionLocal = [];
    }

    if (is_array($configuracionLocal)) {
        $mapa = [
            'MYSQL_HOST'     => 'host',
            'MYSQL_DATABASE' => 'database',
            'MYSQL_USER'     => 'user',
            'MYSQL_PASSWORD' => 'password',
        ];
        foreach ($mapa as $variable => $clave) {
            if (!array_key_exists($variable, $configuracion) && isset($configuracionLocal[$clave])) {
                $configuracion[$variable] = trim((string) $configuracionLocal[$clave]);
            }
        }
    }

    $faltantes = array_values(array_diff($requiredEnv, array_keys($configuracion)));
    if (!empty($faltantes)) {
        piccolo_finalizar_por_error_bd(
            'Configuración de base de datos incompleta. Faltan: ' . implode(', ', $faltantes),
            'Error de configuración de la base de datos'
        );
    }
}

// 3. Validar finales
$servidor    = $configuracion['MYSQL_HOST'];
$baseDatos   = $configuracion['MYSQL_DATABASE'];
$usuario     = $configuracion['MYSQL_USER'];
$contrasenia = $configuracion['MYSQL_PASSWORD'];

foreach (['MYSQL_HOST' => $servidor, 'MYSQL_DATABASE' => $baseDatos, 'MYSQL_USER' => $usuario] as $nombre => $valor) {
    if (!is_string($valor) || trim($valor) === '') {
        piccolo_finalizar_por_error_bd(
            'La variable ' . $nombre . ' está vacía o no es válida.',
            'Error de configuración de la base de datos'
        );
    }
}

// 4. Conectar PDO
try {
    $dsn = "mysql:host={$servidor};port=3306;dbname={$baseDatos};charset=utf8mb4";
    $opciones = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_PERSISTENT         => false,
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
        PDO::MYSQL_ATTR_SSL_CA => null,
    ];
    $conexion = new PDO($dsn, $usuario, $contrasenia, $opciones);
} catch (Exception $error) {
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, $error->getMessage() . PHP_EOL);
    } else {
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: application/json');
        }
        echo json_encode([
            "exito"   => false,
            "mensaje" => "Error PDO: " . $error->getMessage()
        ]);
    }
    exit;
}

// Helpers de sesión
function verificarRol($rolPermitido) {
    session_start();
    if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== $rolPermitido) {
        header("Location: ../login.php");
        exit();
    }
}
