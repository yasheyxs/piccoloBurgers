<?php
// No requiere sesión activa
include("../bd.php");
require_once __DIR__ . '/../../config/mailer.php';
include($_SERVER['DOCUMENT_ROOT'] . "/piccoloBurgers/admin/templates/header_public.php");

$correo = trim($_POST["correo"] ?? "");

echo '<div class="container mt-5"><div class="row justify-content-center"><div class="col-md-6">';

if (empty($correo) || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
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
      <a href='$link' class='btn btn-success'>Hacé clic aquí</a>
    </div>
  ";
} else {
  $correoEnviado = false;

  try {
    $mailer = crearMailer();
    $nombreUsuario = $usuario['usuario'] ?? '';
    $mailer->addAddress($correo, $nombreUsuario);
    $mailer->isHTML(true);
    $mailer->Subject = 'Recuperación de contraseña - Piccolo Burgers';

    $mensajePlano = "Hola $nombreUsuario,\n\n" .
      "Recibimos una solicitud para restablecer tu contraseña del panel administrativo.\n" .
      "Podés crear una nueva contraseña ingresando al siguiente enlace dentro de los próximos 30 minutos:\n$link\n\n" .
      "Si no solicitaste este cambio, ignorá este mensaje.";

    $mensajeHtml = nl2br(htmlspecialchars($mensajePlano, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));

    $mailer->Body = $mensajeHtml . "<br><br><a href='$link' class='btn btn-success'>Restablecer contraseña</a>";
    $mailer->AltBody = $mensajePlano;
    $mailer->send();
    $correoEnviado = true;
  } catch (Throwable $ex) {
    error_log('Error al enviar correo de recuperación: ' . $ex->getMessage());
  }

  if ($correoEnviado) {
    echo "
      <div class='alert alert-success text-center'>
        Te enviamos un correo con las instrucciones para restablecer tu contraseña.<br><br>
        También podés acceder directamente desde aquí:<br>
        <a href='$link' class='btn btn-success mt-2'>Restablecer contraseña</a>
      </div>
    ";
  } else {
    echo "
      <div class='alert alert-warning text-center'>
        Se generó el enlace de recuperación pero no pudimos enviar el correo automáticamente.<br><br>
        Utilizá el siguiente botón para continuar:<br>
        <a href='$link' class='btn btn-success mt-2'>Restablecer contraseña</a>
      </div>
    ";
  }
}

echo '</div></div></div>';
include(__DIR__ . "/../templates/footer.php");
