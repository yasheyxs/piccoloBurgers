<?php
session_start();
include("../bd.php");
require_once __DIR__ . '/../../componentes/password_utils.php';


if (!isset($_SESSION["admin_logueado"])) {
  header("Location: ../login.php");
  exit();
}

$usuario = $_SESSION["admin_usuario"];
$actual = trim($_POST["actual"] ?? "");
$nueva = trim($_POST["nueva"] ?? "");
$confirmar = trim($_POST["confirmar"] ?? "");

if ($actual === '' || $nueva === '' || $confirmar === '') {
  die("Completá todos los campos obligatorios.");
}

if (!passwordCumpleRequisitos($nueva)) {
  die(mensajeRequisitosPassword());
}

if ($nueva !== $confirmar) {
  die("Las contraseñas nuevas no coinciden.");
}

// Buscar usuario
$stmt = $conexion->prepare("SELECT * FROM tbl_usuarios WHERE usuario = :usuario");
$stmt->bindParam(":usuario", $usuario);
$stmt->execute();
$registro = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$registro) {
  die("No se encontró el usuario solicitado.");
}

if (!passwordCoincideConHash($actual, $registro["password"])) {
    die("La contraseña actual es incorrecta.");
}

if (passwordCoincideConHash($nueva, $registro["password"])) {
  die("La nueva contraseña debe ser distinta de la actual.");
}


// Actualizar
$nuevaEncriptada = password_hash($nueva, PASSWORD_DEFAULT);

$stmt = $conexion->prepare("UPDATE tbl_usuarios SET password = :pass WHERE usuario = :usuario");
$stmt->bindParam(":pass", $nuevaEncriptada);
$stmt->bindParam(":usuario", $usuario);
$stmt->execute();

echo "<div class='container mt-5'><div class='alert alert-success'>Contraseña actualizada correctamente.</div></div>";
