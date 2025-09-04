<?php
session_start();
include("admin/bd.php");

$mensaje = "";

// Validación de fuerza de contraseña
function validarFuerza($pass) {
  return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $pass);
}

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
  } elseif (!validarFuerza($password)) {
    $mensaje = "<div class='alert alert-danger'>La contraseña debe tener al menos 8 caracteres, una mayúscula, una minúscula, un número y un símbolo.</div>";
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
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Registro Cliente - Piccolo Burgers</title>
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
        <input type="password" class="form-control" name="password" id="password" required>
        <small class="form-text text-muted">
          La contraseña debe tener al menos 8 caracteres, una mayúscula, una minúscula, un número y un símbolo.
        </small>
      </div>

      <div class="mb-3">
        <label for="confirmar" class="form-label">Confirmar contraseña:</label>
        <input type="password" class="form-control" name="confirmar" required>
      </div>

      <button type="submit" class="btn btn-gold w-100">Registrarse</button>
    </form>
  </div>

  <script>
    document.querySelector('input[name="telefono"]').addEventListener("input", function () {
      this.value = this.value.replace(/[^0-9]/g, "");
    });

    document.querySelector('input[name="nombre"]').addEventListener("input", function () {
      this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, "");
    });

    // Validación de fuerza en tiempo real
    document.getElementById("password")?.addEventListener("input", function () {
      const val = this.value;
      const regex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/;
      this.setCustomValidity(regex.test(val) ? "" : "La contraseña no cumple con los requisitos.");
    });
  </script>

</body>
</html>
