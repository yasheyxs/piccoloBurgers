<?php
session_start();
include("admin/bd.php");

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $telefono = trim($_POST["telefono"] ?? "");
  $password = $_POST["password"] ?? "";

  if (empty($telefono) || empty($password)) {
    $mensaje = "<div class='alert alert-danger'>Completa todos los campos.</div>";
  } else {
    $consulta = $conexion->prepare("SELECT * FROM tbl_clientes WHERE telefono = ?");
    $consulta->execute([$telefono]);
    $cliente = $consulta->fetch(PDO::FETCH_ASSOC);

    if ($cliente) {
      $hashAlmacenado = $cliente["password"];
      $esHashModerno = strlen($hashAlmacenado) > 30 && str_starts_with($hashAlmacenado, '$2y$');

      $valido = $esHashModerno
        ? password_verify($password, $hashAlmacenado)
        : md5($password) === $hashAlmacenado;

      if ($valido) {
        $_SESSION["cliente"] = [
          "id" => $cliente["ID"],
          "nombre" => $cliente["nombre"],
          "telefono" => $cliente["telefono"],
          "email" => $cliente["email"]
        ];
        $mensaje = "<div class='alert alert-success'>Inicio de sesión exitoso. ¡Bienvenido/a <strong>{$cliente['nombre']}</strong>!</div>";
        $mensaje .= "<script>setTimeout(() => window.location.href = 'index.php', 1500);</script>";
      } else {
        $mensaje = "<div class='alert alert-danger'>Teléfono o contraseña incorrectos.</div>";
      }
    } else {
      $mensaje = "<div class='alert alert-danger'>Teléfono o contraseña incorrectos.</div>";
    }
  }
}
?>

<!doctype html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login Cliente - Piccolo Burgers</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <link rel="icon" href="./img/favicon.png" type="image/x-icon" />

  <style>
    :root {
      --main-gold: #fac30c;
      --gold-hover: #e0ae00;
      --dark-bg: #1a1a1a;
      --gray-bg: #2c2c2c;
      --text-light: #ffffff;
      --text-muted: #cccccc;
      --font-main: 'Inter', sans-serif;
      --font-title: 'Bebas Neue', sans-serif;
    }

    body {
      font-family: var(--font-main);
      background-color: var(--dark-bg);
      color: var(--text-light);
      font-size: 1rem;
      line-height: 1.6;
    }

    .form-control {
      font-size: 1.2rem;
    }

    .btn-gold {
      background-color: var(--main-gold);
      color: #000;
      font-weight: bold;
      border: none;
      border-radius: 30px;
      padding: 10px 30px;
      transition: all 0.3s ease;
      font-size: 1rem;
    }

    .btn-gold:hover {
      background-color: var(--gold-hover);
      transform: scale(1.05);
    }

    .form-control {
      background-color: var(--gray-bg);
      color: var(--text-light);
      border: 1px solid #444;
      font-size: 1.2rem;
      border-radius: 8px;
    }

    .form-control::placeholder {
      color: var(--text-muted);
    }

    .form-control:focus {
      background-color: var(--gray-bg);
      color: var(--text-light);
      border-color: var(--main-gold);
      box-shadow: 0 0 0 0.2rem rgba(250, 195, 12, 0.25);
    }
  </style>

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

</head>

<body>

  <nav class="navbar navbar-dark bg-dark">
    <div class="container">
      <a class="navbar-brand" href="index.php"><i class="fas fa-utensils"></i> PICCOLO BURGERS</a>
    </div>
  </nav>

  <div class="container mt-5">
    <h2 class="mb-4 text-center">Iniciar sesión</h2>
    <form method="post">
      <div class="mb-3">
        <label for="telefono" class="form-label">Teléfono:</label>
        <input type="text" class="form-control" name="telefono" id="telefono" required>
      </div>
      <div class="mb-3">
        <label for="password" class="form-label">Contraseña:</label>
        <input type="password" class="form-control" name="password" id="password" required>
      </div>
      <button type="submit" class="btn btn-gold w-100">Iniciar sesión</button>
    </form>

    <div class="mt-3 text-center">
      ¿No tenés cuenta? <a href="registro_cliente.php" class="text-warning">Registrate acá</a>
    </div>

    <div class="mt-2 text-center">
      <a href="admin/password/recuperar_password_cliente.php?tipo=cliente" class="text-warning">¿Olvidaste tu contraseña?</a>
    </div>

    <div class="mt-4 text-center">
      <?= $mensaje ?>
    </div>

    <div class="mt-4 text-center">
      <a href="index.php" class="text-secondary small text-decoration-none" style="opacity: 0.85;">
        <i class="fas fa-arrow-left me-1"></i> Volver al inicio
      </a>
    </div>

  </div>

  <script>
    document.getElementById("telefono").addEventListener("input", function() {
      this.value = this.value.replace(/[^0-9]/g, "");
    });
  </script>

</body>

</html>