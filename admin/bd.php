<?php
$servidor   = getenv('MYSQL_HOST') ?: 'mysql';
$baseDatos  = getenv('MYSQL_DATABASE') ?: 'piccolodb';
$usuario    = getenv('MYSQL_USER') ?: 'piccolo';
$contrasenia= getenv('MYSQL_PASSWORD') ?: 'piccolo_pass';

try {
    $dsn = "mysql:host={$servidor};dbname={$baseDatos};charset=utf8mb4";
    $opciones = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_PERSISTENT         => false,
    ];
    $conexion = new PDO($dsn, $usuario, $contrasenia, $opciones);
} catch (Exception $error) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        "exito"   => false,
        "mensaje" => "Error de conexión a la base de datos"
    ]);
    exit;
}

function verificarRol($rolPermitido) {
    session_start();
    if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== $rolPermitido) {
        header("Location: ../login.php");
        exit();
    }
}
