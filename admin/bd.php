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

foreach ($requiredEnv as $variable) {
    $valor = getenv($variable) ?: ($_ENV[$variable] ?? $_SERVER[$variable] ?? null);

    if ($valor === false || $valor === '') {
        $faltantes[] = $variable;
        continue;
    }

    $configuracion[$variable] = $valor;
}

if (!empty($faltantes)) {
    $rutaConfiguracionLocal = dirname(__DIR__) . '/config/database.php';

    if (is_readable($rutaConfiguracionLocal)) {
        $configuracionLocal = include $rutaConfiguracionLocal;

        if (is_array($configuracionLocal)) {
            $mapa = [
                'MYSQL_HOST'     => 'host',
                'MYSQL_DATABASE' => 'database',
                'MYSQL_USER'     => 'user',
                'MYSQL_PASSWORD' => 'password',
            ];

            foreach ($mapa as $variable => $clave) {
                if (array_key_exists($variable, $configuracion)) {
                    continue;
                }

                if (isset($configuracionLocal[$clave]) && $configuracionLocal[$clave] !== '') {
                    $configuracion[$variable] = (string) $configuracionLocal[$clave];
                }
            }

            $faltantes = array_values(array_diff($requiredEnv, array_keys($configuracion)));
        } else {
            error_log('El archivo config/database.php debe devolver un array con la configuración de la base de datos.');
        }
    }

    if (!empty($faltantes)) {
        piccolo_finalizar_por_error_bd(
            'Configuración de base de datos incompleta. Faltan las variables: ' . implode(', ', $faltantes),
            'Error de configuración de la base de datos'
        );
    }
}

$servidor    = $configuracion['MYSQL_HOST'];
$baseDatos   = $configuracion['MYSQL_DATABASE'];
$usuario     = $configuracion['MYSQL_USER'];
$contrasenia = $configuracion['MYSQL_PASSWORD'];

try {
    $dsn = "mysql:host={$servidor};port=3306;dbname={$baseDatos};charset=utf8mb4";
    $opciones = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_PERSISTENT         => false,
        // 🔑 Fix TLS error por certificado autofirmado
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
        PDO::MYSQL_ATTR_SSL_CA => null,
    ];
    $conexion = new PDO($dsn, $usuario, $contrasenia, $opciones);
} catch (Exception $error) {
    // Mostrar directamente el error exacto
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



function verificarRol($rolPermitido) {
    session_start();
    if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== $rolPermitido) {
        header("Location: ../login.php");
        exit();
    }
}
