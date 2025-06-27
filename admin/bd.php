<?php
$servidor = "localhost";
$baseDatos = "piccolodb";
$usuario = "root";
$contrasenia = "";
try {
    $conexion = new PDO("mysql:host=$servidor;dbname=$baseDatos", $usuario, $contrasenia);
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $error) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(["exito" => false, "mensaje" => "Error de conexión a la base de datos"]);
    exit;
}
