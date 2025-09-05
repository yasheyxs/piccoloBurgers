<?php
session_start();
include("admin/bd.php");

$mensaje = "";

function validarTelefono($codigo, $numero)
{
  $codigo = preg_replace('/[^\d]/', '', $codigo);
  $numero = preg_replace('/[^\d]/', '', $numero);
  $telefono = '+' . $codigo . $numero;

  $longitudes = [
    '54' => [10], '598' => [8, 9], '55' => [10, 11], '56' => [9],
    '595' => [9], '591' => [8], '51' => [9], '1' => [10], '34' => [9]
  ];

  if (!isset($longitudes[$codigo])) return false;
  if (!in_array(strlen($numero), $longitudes[$codigo])) return false;

  return preg_match('/^\+\d{10,15}$/', $telefono) ? $telefono : false;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $codigo = trim($_POST["codigo_pais"] ?? "");
  $numero = trim($_POST["telefono"] ?? "");
  $password = $_POST["password"] ?? "";

  $telefonoCompleto = validarTelefono($codigo, $numero);

  if (empty($telefonoCompleto) || empty($password)) {
    $mensaje = "<div class='alert alert-danger'>Completa todos los campos correctamente.</div>";
  } else {
    $consulta = $conexion->prepare("SELECT * FROM tbl_clientes WHERE telefono = ?");
    $consulta->execute([$telefonoCompleto]);
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
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <link rel="icon" href="./img/favicon.png" type="image/x-icon" />
  <style>
    :root {
      --main-gold: #fac30c;
      --gold-hover: #e0ae00;
      --dark-bg: #1a1a1a;
      --gray-bg: #2c2c2c;
      --text-light: #ffffff;
      --text-muted: #b0b0b0;
      --font-main: 'Inter', sans-serif;
      --font-title: 'Bebas Neue', sans-serif;
    }

    body {
      font-family: var(--font-main);
      background-color: var(--dark-bg);
      color: var(--text-light);
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
      font-size: 1rem;
      transition: all 0.3s ease;
    }

    .btn-gold:hover {
      background-color: var(--gold-hover);
      transform: scale(1.05);
    }

    .form-text {
      color: var(--text-muted);
    }
  </style>
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
      <small class="form-text">Ingresá solo números, sin espacios ni guiones.</small>

      <div class="mb-3 mt-3">
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
    const longitudes = {
      '54': 10, '598': 9, '55': 11, '56': 9, '595': 9,
      '591': 8, '51': 9, '1': 10, '34': 9
    };

    const selectPais = document.getElementById('codigo_pais');
    const inputTel = document.getElementById('telefono');

    function actualizarMaxLength() {
      const cod = selectPais.value;
      const max = longitudes[cod] || 15;
      inputTel.maxLength = max;
    }

    inputTel.addEventListener("input", function () {
      this.value = this.value.replace(/[^\d]/g, "");
    });

    selectPais.addEventListener("change", actualizarMaxLength);
    document.addEventListener("DOMContentLoaded", actualizarMaxLength);
  </script>
</body>
</html>
