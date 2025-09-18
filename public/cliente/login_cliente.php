<?php
session_start();
require_once __DIR__ . '/../../admin/bd.php';
require_once __DIR__ . '/../../componentes/validar_telefono.php';
require_once __DIR__ . '/../../includes/email_requirement.php';


$mensaje = "";

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

        if (clienteDebeRegistrarEmail()) {
          registrarAvisoEmailObligatorio();
          header("Location: perfil_cliente.php");
          exit;
        }

        limpiarAvisoEmailObligatorio();

        $mensaje = "<div class='alert alert-success'>Inicio de sesión exitoso. ¡Bienvenido/a <strong>{$cliente['nombre']}</strong>!</div>";
        $mensaje .= "<script>setTimeout(() => window.location.href = '../index.php', 1500);</script>";
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
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <link rel="icon" href="../img/favicon.png" type="image/x-icon" />
  <style>
    :root {
      --main-gold: #fac30c;
      --gold-hover: #e0ae00;
      --dark-bg: #1a1a1a;
      --gray-bg: #2c2c2c;
      --text-light: #ffffff;
      --text-muted: #cccccc;
      --font-main: 'Inter', sans-serif;
      --font-title: 'Bebas Neue', cursive;
    }

    * {
      box-sizing: border-box;
    }

    body,
    html {
      height: 100%;
      margin: 0;
      font-family: var(--font-main);
      background-color: var(--dark-bg);
      color: var(--text-light);
    }

    .login-container {
      display: flex;
      height: 100vh;
      width: 100%;
    }

    .login-image {
      flex: 1;
      background: url('../img/HamLoginCliente2.jpg') center/cover no-repeat;
    }

    .login-form-wrapper {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 2rem;
      background-color: var(--dark-bg);

      /* Efecto Glass */
      backdrop-filter: blur(10px);
      /* Desenfoque del fondo */
      -webkit-backdrop-filter: blur(10px);
      /* Compatibilidad con Safari */
    }

    .login-form {
      width: 100%;
      max-width: 400px;
    }

    .login-form h2 {
      font-family: var(--font-title);
      font-size: 2.5rem;
      color: var(--main-gold);
      margin-bottom: 1rem;
      text-align: center;
    }

    .form-control {
      background-color: rgba(44, 44, 44, 0.7);
      /* Fondo con transparencia */
      color: var(--text-light);
      border: 1px solid #444;
      font-size: 1.1rem;
      border-radius: 10px;
    }

    .form-control::placeholder {
      color: var(--text-muted);
    }

    .form-control:focus {
      background-color: rgba(44, 44, 44, 0.7);
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
      width: 100%;
      font-size: 1rem;
    }

    .register-link {
      text-align: center;
      margin-top: 1rem;
      font-size: 0.95rem;
    }

    .register-link a {
      color: var(--main-gold);
      text-decoration: none;
    }

    .register-link a:hover {
      text-decoration: underline;
    }


    .alert {
      margin-top: 1.5rem;
    }

    @media (max-width: 768px) {
      .login-container {
        position: relative;
        flex-direction: column;
        height: 100%;
        background: url('../img/HamLoginClim,jpg') center/cover no-repeat;
        background-size: cover;
        display: flex;
        justify-content: center;
        align-items: center;
      }

      .login-image {
        height: 200px;
      }

      .login-form-wrapper {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        padding: 2rem;
        background-color: rgba(44, 44, 44, 0.8);
        /* Fondo semi-transparente */
        border-radius: 10px;
        max-width: 90%;
        width: 400px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5);
      }
    }

    .extra-links {
      text-align: center;
      margin-top: 2rem;
      font-size: 0.95rem;
    }

    .extra-links a {
      color: var(--main-gold);
      text-decoration: none;
      font-weight: 500;
    }

    .extra-links a:hover {
      text-decoration: underline;
    }

    .extra-links .btn-outline-light {
      color: var(--main-gold);
      border-color: var(--main-gold);
      font-weight: bold;
      padding: 8px 20px;
      border-radius: 30px;
      transition: all 0.3s ease;
    }

    .extra-links .btn-outline-light:hover {
      background-color: var(--main-gold);
      color: #000;
    }
  </style>
</head>

<body>

  <div class="login-container">
    <div class="login-image"></div>

    <div class="login-form-wrapper">
      <form method="post" class="login-form">
        <h2>Iniciar sesión</h2>

        <?= $mensaje ?>

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
        </div>

        <div class="mb-3 mt-3">
          <label for="password" class="form-label">Contraseña:</label>
          <input type="password" class="form-control" name="password" id="password" required>
        </div>

        <button type="submit" class="btn-gold mt-3">Iniciar sesión</button>

        <div class="extra-links">
          ¿No tenés cuenta? <a href="registro_cliente.php">Registrate acá</a><br><br>
          <a href="../../admin/password/recuperar_password_cliente.php?tipo=cliente">¿Olvidaste tu contraseña?</a><br><br> <a href="../index.php" class="btn btn-outline-light">← Volver al inicio</a>

        </div>
      </form>
    </div>
  </div>


  <script>
    const longitudes = {
      '54': 10,
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