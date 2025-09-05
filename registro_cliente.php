<?php
session_start();
include("admin/bd.php");

$mensaje = "";

// Validación de fuerza de contraseña
function validarFuerza($pass)
{
  return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $pass);
}

function validarTelefono($codigo, $numero)
{
  $codigo = preg_replace('/[^\d]/', '', $codigo);
  $numero = preg_replace('/[^\d]/', '', $numero);
  $telefono = '+' . $codigo . $numero;

  // Longitudes esperadas por país
  $longitudes = [
    '54' => [10],       // Argentina
    '598' => [8, 9],    // Uruguay
    '55' => [10, 11],   // Brasil
    '56' => [9],        // Chile
    '595' => [9],       // Paraguay
    '591' => [8],       // Bolivia
    '51' => [9],        // Perú
    '1' => [10],        // USA
    '34' => [9]         // España
  ];

  if (!isset($longitudes[$codigo])) return false;
  if (!in_array(strlen($numero), $longitudes[$codigo])) return false;

  return preg_match('/^\+\d{10,15}$/', $telefono) ? $telefono : false;
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $nombre = trim($_POST["nombre"]);
  $codigo = trim($_POST["codigo_pais"]);
  $numero = trim($_POST["telefono"]);
  $email = trim($_POST["email"] ?? "");
  $password = $_POST["password"];
  $confirmar = $_POST["confirmar"];

  $telefonoCompleto = validarTelefono($codigo, $numero);

  if ($password !== $confirmar) {
    $mensaje = "<div class='alert alert-danger'>Las contraseñas no coinciden.</div>";
  } elseif (empty($nombre) || empty($codigo) || empty($numero) || empty($password)) {
    $mensaje = "<div class='alert alert-danger'>Por favor, completá todos los campos requeridos.</div>";
  } elseif (!validarFuerza($password)) {
    $mensaje = "<div class='alert alert-danger'>La contraseña debe tener al menos 8 caracteres, una mayúscula, una minúscula, un número y un símbolo.</div>";
  } elseif (!$telefonoCompleto) {
    $mensaje = "<div class='alert alert-danger'>El número ingresado no parece válido. Verificá que esté completo y sin espacios.</div>";
  } else {
    try {
      $consulta = $conexion->prepare("SELECT ID FROM tbl_clientes WHERE telefono = ?");
      $consulta->execute([$telefonoCompleto]);

      if ($consulta->rowCount() > 0) {
        $mensaje = "<div class='alert alert-warning'>Ya existe una cuenta registrada con ese teléfono.</div>";
      } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $sentencia = $conexion->prepare("INSERT INTO tbl_clientes (nombre, telefono, email, password) VALUES (?, ?, ?, ?)");
        $sentencia->execute([$nombre, $telefonoCompleto, $email, $hash]);

        $mensaje = "<div class='alert alert-success'>🎉 Registro exitoso. Ahora podés <a href='login_cliente.php'>iniciar sesión</a>.</div>";
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
      background-color: var(--gray-bg);
      color: var(--text-light);
      border: 1px solid #444;
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
        <label for="telefono" class="form-label">Teléfono:</label>
        <div class="d-flex gap-2">
          <select name="codigo_pais" class="form-control" style="max-width: 140px;" required id="codigo_pais">
            <option value="54" selected>🇦🇷 +54</option>
            <option value="598">🇺🇾 +598</option>
            <option value="55">🇧🇷 +55</option>
            <option value="56">🇨🇱 +56</option>
            <option value="595">🇵🇾 +595</option>
            <option value="591">🇧🇴 +591</option>
            <option value="51">🇵🇪 +51</option>
            <option value="1">🇺🇸 +1</option>
            <option value="34">🇪🇸 +34</option>
          </select>
          <input type="text" class="form-control" name="telefono" id="telefono" placeholder="Ej: 3511234567" required>
        </div>
        <small class="form-text text-muted">Ingresá tu número sin el 0 ni el +. Ejemplo: 3511234567</small>
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
    document.querySelector('input[name="telefono"]').addEventListener("input", function() {
      this.value = this.value.replace(/[^\d]/g, "");
    });

    document.querySelector('input[name="nombre"]').addEventListener("input", function() {
      this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, "");
    });

    document.getElementById("password")?.addEventListener("input", function() {
      const val = this.value;
      const regex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/;
      this.setCustomValidity(regex.test(val) ? "" : "La contraseña no cumple con los requisitos.");
    });
  </script>

  <script>
    const longitudes = {
      '54': 10, // Argentina
      '598': 9,
      '55': 11,
      '56': 9,
      '595': 9,
      '591': 8,
      '51': 9,
      '1': 10,
      '34': 9
    };

    const selectPais = document.getElementById('codigo_pais');
    const inputTel = document.getElementById('telefono');

    function actualizarMaxLength() {
      const cod = selectPais.value;
      const max = longitudes[cod] || 15;
      inputTel.maxLength = max;
    }

    inputTel.addEventListener("input", function() {
      this.value = this.value.replace(/[^\d]/g, "");
    });

    selectPais.addEventListener("change", actualizarMaxLength);
    document.addEventListener("DOMContentLoaded", actualizarMaxLength);
  </script>

</body>

</html>