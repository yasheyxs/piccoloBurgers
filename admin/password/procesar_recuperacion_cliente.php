<?php
include("../bd.php");
require_once __DIR__ . '/../../componentes/validar_telefono.php';
?>

<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Recuperación - Cliente</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <link rel="icon" href="../../img/favicon.png" type="image/x-icon" />
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
$codigo = trim($_POST["codigo_pais"] ?? "");
$numero = trim($_POST["telefono"] ?? "");
$telefonoCompleto = validarTelefono($codigo, $numero);

if (empty($telefonoCompleto)) {
  echo "<div class='alert alert-danger'>Por favor, ingresá un número válido.</div>";
  echo "<div class='mt-3'><a href='../password/recuperar_password_cliente.php' class='btn-gold'>Volver</a></div>";
  exit();
}

$stmt = $conexion->prepare("SELECT * FROM tbl_clientes WHERE telefono = :telefono");
$stmt->bindParam(":telefono", $telefonoCompleto);
$stmt->execute();
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

if ($cliente) {
  $token = bin2hex(random_bytes(32));
  $expira = date("Y-m-d H:i:s", strtotime("+30 minutes"));

  $stmt = $conexion->prepare("UPDATE tbl_clientes SET reset_token = :token, token_expira = :expira WHERE telefono = :telefono");
  $stmt->bindParam(":token", $token);
  $stmt->bindParam(":expira", $expira);
  $stmt->bindParam(":telefono", $telefonoCompleto);
  $stmt->execute();

  $host = $_SERVER['HTTP_HOST'];
  $link = "http://$host/piccoloBurgers/admin/password/reset_password.php?token=$token&tipo=cliente";

  echo "
    <div class='alert alert-success'>
      Si el teléfono está registrado, se ha generado un enlace de recuperación.<br>
      <a href='$link' class='btn-gold'>Hacé clic aquí</a>
    </div>
  ";
} else {
  echo "
    <div class='alert alert-warning'>
      No hay una cuenta asociada a este número de teléfono.<br>
      <a href='../password/recuperar_password_cliente.php' class='btn-gold'>Volver</a>
    </div>
  ";
}
?>

    </div>
  </div>
</body>
</html>
