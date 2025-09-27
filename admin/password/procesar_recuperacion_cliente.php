<?php
include("../bd.php");
require_once __DIR__ . '/../../config/mailer.php';
require_once dirname(__DIR__) . '/helpers/url.php';


?>

<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Recuperación - Cliente</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <link rel="icon" href="../../public/img/favicon.png" type="image/x-icon" />
  <style>
    :root {
      --main-gold: #fac30c;
      --gold-hover: #e0ae00;
      --dark-bg: #1a1a1a;
      --text-light: #ffffff;
    }

    body {
      background-color: var(--dark-bg);
      color: var(--text-light);
      font-family: 'Inter', sans-serif;
    }

    .centered-message {
      min-height: 70vh;
      display: flex;
      align-items: center;
      justify-content: center;
      text-align: center;
    }

    .alert {
      font-size: 1.3rem;
      padding: 2rem;
      border-radius: 12px;
    }

    .btn-gold {
      background-color: var(--main-gold);
      color: #000;
      font-weight: bold;
      border: none;
      border-radius: 30px;
      padding: 10px 30px;
      font-size: 1rem;
      transition: all 0.3s ease;
      text-decoration: none;
      display: inline-block;
      margin-top: 1rem;
    }

    .btn-gold:hover {
      background-color: var(--gold-hover);
      transform: scale(1.05);
    }
  </style>
</head>

<body>
  <div class="container centered-message">
    <div class="col-md-8">

<?php
$correo = trim($_POST["correo"] ?? "");

if (empty($correo) || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
  echo "<div class='alert alert-danger'>Por favor, ingresá un correo válido.</div>";
  echo "<div class='mt-3'><a href='../password/recuperar_password_cliente.php' class='btn-gold'>Volver</a></div>";
  exit();
}

$stmt = $conexion->prepare("SELECT * FROM tbl_clientes WHERE email = :correo");
$stmt->bindParam(":correo", $correo);
$stmt->execute();
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

if ($cliente) {
  $token = bin2hex(random_bytes(32));
  $expira = date("Y-m-d H:i:s", strtotime("+30 minutes"));

  $stmt = $conexion->prepare("UPDATE tbl_clientes SET reset_token = :token, token_expira = :expira WHERE email = :correo");
  $stmt->bindParam(":token", $token);
  $stmt->bindParam(":expira", $expira);
  $stmt->bindParam(":correo", $correo);
  $stmt->execute();

$adminBaseUrl = piccolo_admin_base_url();
  $query = http_build_query([
    'token' => $token,
    'tipo'  => 'cliente',
  ]);
  $link = $adminBaseUrl . 'password/reset_password.php?' . $query;
  $linkHtml = htmlspecialchars($link, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');


  $correoEnviado = false;

  try {
    $mailer = crearMailer();
    $nombreCliente = $cliente['nombre'] ?? '';
    $mailer->addAddress($correo, $nombreCliente);
    $mailer->isHTML(true);
    $mailer->Subject = 'Recuperación de contraseña - Piccolo Burgers';

    $mensajePlano = "Hola $nombreCliente,\n\n" .
      "Recibimos una solicitud para restablecer la contraseña de tu cuenta de cliente.\n" .
      "Podés crear una nueva contraseña ingresando al siguiente enlace dentro de los próximos 30 minutos:\n$link\n\n" .
      "Si no solicitaste este cambio, ignorá este mensaje.";

    $mensajeHtml = nl2br(htmlspecialchars($mensajePlano, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));

    $mailer->Body = $mensajeHtml . "<br><br><a href='$linkHtml' class='btn-gold'>Restablecer contraseña</a>";
    $mailer->AltBody = $mensajePlano;
    $mailer->send();
    $correoEnviado = true;
  } catch (Throwable $ex) {
    error_log('Error al enviar correo de recuperación (cliente): ' . $ex->getMessage());
  }

  if ($correoEnviado) {
    echo "
      <div class='alert alert-success'>
        Te enviamos un correo con las instrucciones para restablecer tu contraseña.<br><br>
      </div>
    ";
  } else {
    echo "
      <div class='alert alert-warning'>
        Se generó el enlace de recuperación pero no pudimos enviar el correo automáticamente.<br><br>
        Utilizá el siguiente botón para continuar:<br>
        <a href='$linkHtml' class='btn-gold mt-2'>Restablecer contraseña</a>
      </div>
    ";
  }
} else {
  echo "
    <div class='alert alert-success'>
      Si el correo está registrado, vas a recibir un correo con los pasos para restablecer tu contraseña en los próximos minutos.
    </div>
  ";
}

echo "<div class='mt-3'><a href='../password/recuperar_password_cliente.php' class='btn-gold'>Volver</a></div>";

?>

    </div>
  </div>
</body>
</html>
