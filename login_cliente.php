<?php
session_start();
include("admin/bd.php");

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $telefono = $_POST["telefono"] ?? "";
    $password = $_POST["password"] ?? "";

    if (empty($telefono) || empty($password)) {
        $mensaje = "<div class='alert alert-danger'>Completa todos los campos.</div>";
    } else {
        $consulta = $conexion->prepare("SELECT * FROM tbl_clientes WHERE telefono = ?");
        $consulta->execute([$telefono]);
        $cliente = $consulta->fetch(PDO::FETCH_ASSOC);

        if ($cliente && password_verify($password, $cliente["password"])) {
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
    }
}
?>

<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Login Cliente - Piccolo Burgers</title>
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

<nav class="navbar navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand" href="index.php"><i class="fas fa-utensils"></i> Piccolo Burgers</a>
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
    ¿No tenés cuenta? <a href="registro_cliente.php">Registrate acá</a>
  </div>

  <div class="mt-4 text-center">
    <?= $mensaje ?>
  </div>
</div>

<script>
  // teléfono tenga solo números
  document.getElementById("telefono").addEventListener("input", function () {
    this.value = this.value.replace(/[^0-9]/g, "");
  });
</script>


</body>
</html>
