<?php
session_start();
include("admin/bd.php");

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = trim($_POST["nombre"]);
    $telefono = trim($_POST["telefono"]);
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"];
    $confirmar = $_POST["confirmar"];

    if ($password !== $confirmar) {
        $mensaje = "<div class='alert alert-danger'>Las contraseñas no coinciden.</div>";
    } elseif (empty($nombre) || empty($telefono) || empty($password)) {
        $mensaje = "<div class='alert alert-danger'>Por favor, completa todos los campos requeridos.</div>";
    } elseif (!isset($_POST['terminos'])) {
        $mensaje = "<div class='alert alert-warning'>Debes aceptar los términos y condiciones.</div>";
    } else {
        try {
            $consulta = $conexion->prepare("SELECT ID FROM tbl_clientes WHERE telefono = ?");
            $consulta->execute([$telefono]);

            if ($consulta->rowCount() > 0) {
                $mensaje = "<div class='alert alert-warning'>Ya existe una cuenta registrada con ese teléfono.</div>";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $sentencia = $conexion->prepare("INSERT INTO tbl_clientes (nombre, telefono, email, password) VALUES (?, ?, ?, ?)");
                $sentencia->execute([$nombre, $telefono, $email, $hash]);

                $mensaje = "<div class='alert alert-success'>🎉 Registro exitoso. Ahora puedes <a href='login_cliente.php'>iniciar sesión</a>.</div>";
            }
        } catch (Exception $e) {
            $mensaje = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
        }
    }
}
?>

<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Registro Cliente - Piccolo Burgers</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Bebas Neue', sans-serif;
      font-size: 1.7rem;
    }
    .form-control {
      font-size: 1.2rem;
    }
    .btn-gold {
      background-color: #fac30c;
      color: #000;
      font-weight: bold;
      border: none;
    }
    .btn-gold:hover {
      background-color: #e0ae00;
    }
  </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand" href="index.php"><i class="fas fa-utensils"></i> Piccolo Burgers</a>
  </div>
</nav>

<div class="container mt-5">
  <h2 class="text-center mb-4">Registro de Cliente</h2>

  <?= $mensaje ?>

  <form method="post">
    <div class="mb-3">
      <label for="nombre" class="form-label">Nombre completo:</label>
      <input type="text" class="form-control" name="nombre" required>
    </div>

    <div class="mb-3">
      <label for="telefono" class="form-label">Teléfono (obligatorio):</label>
      <input type="text" class="form-control" name="telefono" required>
    </div>

    <div class="mb-3">
      <label for="email" class="form-label">Email (opcional):</label>
      <input type="email" class="form-control" name="email">
    </div>

    <div class="mb-3">
      <label for="password" class="form-label">Contraseña:</label>
      <input type="password" class="form-control" name="password" required>
    </div>

    <div class="mb-3">
      <label for="confirmar" class="form-label">Confirmar contraseña:</label>
      <input type="password" class="form-control" name="confirmar" required>
    </div>

    <div class="form-check mb-3">
      <input class="form-check-input" type="checkbox" name="terminos" id="terminos">
      <label class="form-check-label" for="terminos">
        Acepto los términos y condiciones
      </label>
    </div>

    <button type="submit" class="btn btn-gold w-100">Registrarse</button>
  </form>
</div>
</body>
</html>