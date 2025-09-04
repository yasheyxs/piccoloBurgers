<?php
// No requiere sesión activa
include("../bd.php");
include($_SERVER['DOCUMENT_ROOT'] . "/piccoloBurgers/admin/templates/header_public.php");

$correo = trim($_POST["correo"] ?? "");

echo '<div class="container mt-5"><div class="row justify-content-center"><div class="col-md-6">';

if (empty($correo)) {
  echo "<div class='alert alert-danger text-center'>Por favor, ingresá un correo válido.</div>";
  echo "<div class='mt-3 text-center'><a href='recuperar_password_usuario.php' class='btn btn-secondary'>Volver</a></div>";
  include(__DIR__ . "/../templates/footer.php");
  exit();
}

// Buscar usuario por correo
$stmt = $conexion->prepare("SELECT * FROM tbl_usuarios WHERE correo = :correo");
$stmt->bindParam(":correo", $correo);
$stmt->execute();
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if ($usuario) {
  $token = bin2hex(random_bytes(32));
  $expira = date("Y-m-d H:i:s", strtotime("+30 minutes"));

  $stmt = $conexion->prepare("UPDATE tbl_usuarios SET reset_token = :token, token_expira = :expira WHERE correo = :correo");
  $stmt->bindParam(":token", $token);
  $stmt->bindParam(":expira", $expira);
  $stmt->bindParam(":correo", $correo);
  $stmt->execute();

  $host = $_SERVER['HTTP_HOST'];
  $link = "http://$host/piccoloBurgers/admin/password/reset_password.php?token=$token&tipo=usuario";

  echo "
    <div class='alert alert-success text-center'>
      Si el correo está registrado, se ha generado un enlace de recuperación.<br><br>
      <a href='$link' class='btn btn-success'>Haga clic aquí</a>
    </div>
  ";
} else {
  echo "
    <div class='alert alert-success text-center'>
      Si el correo está registrado, se ha generado un enlace de recuperación.
    </div>
  ";
}

echo "<div class='mt-3 text-center'><a href='recuperar_password_usuario.php' class='text-primary'>Volver al formulario</a></div>";
echo '</div></div></div>';

include(__DIR__ . "/../templates/footer.php");
