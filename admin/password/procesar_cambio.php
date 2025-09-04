<?php
session_start();
include("../bd.php");

if (!isset($_SESSION["admin_logueado"])) {
  header("Location: ../login.php");
  exit();
}

$usuario = $_SESSION["admin_usuario"];
$actual = $_POST["actual"] ?? "";
$nueva = $_POST["nueva"] ?? "";
$confirmar = $_POST["confirmar"] ?? "";

if ($nueva !== $confirmar) {
  die("Las contraseñas nuevas no coinciden.");
}

// Buscar usuario
$stmt = $conexion->prepare("SELECT * FROM tbl_usuarios WHERE usuario = :usuario");
$stmt->bindParam(":usuario", $usuario);
$stmt->execute();
$registro = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$registro || md5($actual) !== $registro["password"]) {
  die("La contraseña actual es incorrecta.");
}

// Actualizar
$nuevaEncriptada = md5($nueva); // Si migrás a password_hash, te ayudo a actualizar

$stmt = $conexion->prepare("UPDATE tbl_usuarios SET password = :pass WHERE usuario = :usuario");
$stmt->bindParam(":pass", $nuevaEncriptada);
$stmt->bindParam(":usuario", $usuario);
$stmt->execute();

echo "<div class='container mt-5'><div class='alert alert-success'>Contraseña actualizada correctamente.</div></div>";
