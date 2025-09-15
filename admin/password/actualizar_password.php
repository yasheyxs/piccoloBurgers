<?php
include("../bd.php");
include($_SERVER['DOCUMENT_ROOT'] . "/piccoloBurgers/admin/templates/header_public.php");

$token     = trim($_POST["token"] ?? "");
$tipo      = $_POST["tipo"] ?? "";
$nueva     = $_POST["nueva"] ?? "";
$confirmar = $_POST["confirmar"] ?? "";

echo '<div class="container mt-5"><div class="row justify-content-center"><div class="col-md-6">';

if (!in_array($tipo, ["usuario", "cliente"])) {
  echo "<div class='alert alert-danger text-center'>Tipo de cuenta inválido.</div>";
  echo "<div class='mt-3 text-center'><a href='recuperar_password_{$tipo}.php' class='btn btn-secondary'>Volver</a></div>";
  include(__DIR__ . "/../templates/footer.php");
  exit();
}

if (empty($nueva) || empty($confirmar)) {
  echo "<div class='alert alert-danger text-center'>Completá ambos campos de contraseña.</div>";
  echo "<div class='mt-3 text-center'><a href='recuperar_password_{$tipo}.php' class='btn btn-secondary'>Volver</a></div>";
  include(__DIR__ . "/../templates/footer.php");
  exit();
}

if ($nueva !== $confirmar) {
  echo "<div class='alert alert-danger text-center'>Las contraseñas no coinciden.</div>";
  echo "<div class='mt-3 text-center'><a href='recuperar_password_{$tipo}.php' class='btn btn-secondary'>Volver</a></div>";
  include(__DIR__ . "/../templates/footer.php");
  exit();
}

$tabla = $tipo === "usuario" ? "tbl_usuarios" : "tbl_clientes";

// Validar token
$stmt = $conexion->prepare("SELECT * FROM $tabla WHERE reset_token = :token AND token_expira > NOW()");
$stmt->bindParam(":token", $token);
$stmt->execute();
$registro = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$registro) {
  echo "<div class='alert alert-danger text-center'>El enlace es inválido o ha expirado. Por favor, solicitá uno nuevo.</div>";
  echo "<div class='mt-3 text-center'><a href='recuperar_password_{$tipo}.php' class='btn btn-warning'>Volver a recuperar contraseña</a></div>";
  include(__DIR__ . "/../templates/footer.php");
  exit();
}

// Encriptar y actualizar
$nuevaEncriptada = password_hash($nueva, PASSWORD_DEFAULT);

$stmt = $conexion->prepare("UPDATE $tabla SET password = :pass, reset_token = NULL, token_expira = NULL WHERE reset_token = :token");
$stmt->bindParam(":pass", $nuevaEncriptada);
$stmt->bindParam(":token", $token);
$stmt->execute();

echo "<div class='alert alert-success text-center'>Contraseña actualizada correctamente. Redirigiendo al login...</div>";
echo "<script>setTimeout(() => window.location.href = '" . ($tipo === "cliente" ? "../../public/client/login_cliente.php" : "../login.php") . "', 3000);</script>";

echo '</div></div></div>';
include(__DIR__ . "/../templates/footer.php");
