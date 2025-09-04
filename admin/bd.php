<?php
$servidor = "localhost";
$baseDatos = "piccolodb";
$usuario = "root";
$contrasenia = "";
try {
    $conexion = new PDO("mysql:host=$servidor;dbname=$baseDatos", $usuario, $contrasenia);
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $error) {// Capturar errores de conexión
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(["exito" => false, "mensaje" => "Error de conexión a la base de datos"]);
    exit;
}

function verificarRol($rolPermitido) {
    session_start();
    if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== $rolPermitido) {
        header("Location: ../login.php");
        exit();
    }
}

