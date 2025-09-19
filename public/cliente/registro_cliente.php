<?php
session_start();
require_once __DIR__ . '/../../admin/bd.php';
require_once __DIR__ . '/../../componentes/validar_telefono.php';
require_once __DIR__ . '/../../componentes/password_utils.php';


$mensaje = "";

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
  } elseif (empty($nombre) || empty($codigo) || empty($numero) || empty($email) || empty($password)) {

    $mensaje = "<div class='alert alert-danger'>Por favor, completá todos los campos requeridos.</div>";
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $mensaje = "<div class='alert alert-danger'>Ingresá un email válido.</div>";
  } elseif (!passwordCumpleRequisitos($password)) {
    $mensaje = "<div class='alert alert-danger'>" . mensajeRequisitosPassword() . "</div>";
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

        $mensaje = "<div class='alert alert-success'>🎉 Registro exitoso. Ahora podés <a href='./login_cliente.php'>iniciar sesión</a>.</div>";
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

    .navbar {
      position: relative;
      z-index: 10;
    }


    .register-container {
      display: flex;
      min-height: 100vh;
      width: 100%;
    }

    .register-image {
      flex: 1;
      background: url('../img/HamLoginCliente.jpg') center/cover no-repeat;
      background-size: cover;
      min-height: 100%;
    }

    .register-form-wrapper {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 2rem;
      background-color: var(--dark-bg);
    }

    .register-form {
      width: 100%;
      max-width: 450px;
    }

    .register-form h2 {
      font-family: var(--font-title);
      font-size: 3rem;
      color: var(--main-gold);
      margin-bottom: 1rem;
      text-align: center;
    }

    .form-control {
      background-color: var(--gray-bg);
      color: var(--text-light);
      border: 1px solid #444;
      font-size: 1.1rem;
      border-radius: 10px;
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
      width: 100%;
      font-size: 1rem;
    }

    .btn-gold:hover {
      background-color: var(--gold-hover);
      transform: scale(1.05);
    }

    .extra-links {
      text-align: center;
      margin-top: 1.5rem;
      font-size: 0.95rem;
    }

    .extra-links a {
      color: var(--main-gold);
      text-decoration: none;
      margin: 0 0.5rem;
    }

    .extra-links a:hover {
      text-decoration: underline;
    }

    .alert {
      margin-top: 1rem;
      font-size: 0.95rem;
    }

    @media (max-width: 768px) {
      .register-container {
        flex-direction: column;
      }

      .register-image {
        height: 200px;
        width: 100%;
      }

      .register-form-wrapper {
        padding: 2rem 1.5rem;
      }

      .register-form {
        max-width: 100%;
      }

      .register-form h2 {
        font-size: 2.5rem;
      }
    }

    @media (max-width: 768px) {
      .register-container {
        flex-direction: column;
        background: url('../img/HamLoginCliente.jpg') center/cover no-repeat;
        background-size: cover;
        min-height: 100vh;
        padding-top: 40px;
        padding-bottom: 2rem;
      }

      .register-image {
        display: none;
      }

      .register-form-wrapper {
        padding: 2rem 1.5rem;
        background: rgba(44, 44, 44, 0.6);
        border-radius: 15px;
        width: 90%;
        max-width: 450px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        margin: auto;
      }

    }
  </style>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
  <div class="register-container">
    <!-- Imagen a la izquierda -->
    <div class="register-image"></div>

    <!-- Formulario a la derecha -->
    <div class="register-form-wrapper">
      <form method="post" class="register-form">
        <h2>Crear cuenta</h2>

        <?= $mensaje ?? '' ?>

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
        </div>

        <div class="mb-3">
          <label for="email" class="form-label">Email:</label>
          <input type="email" class="form-control" name="email" required autocomplete="email">
        </div>

        <div class="mb-3">
          <label for="password" class="form-label">Contraseña:</label>
          <input
            type="password"
            class="form-control"
            name="password"
            id="password"
            required
            pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\\d)(?=.*[\\W_]).{8,}"
            title="<?php echo mensajeRequisitosPassword(); ?>">
          <div class="form-text text-muted"><?php echo mensajeRequisitosPassword(); ?></div>
        </div>

        <div class="mb-3">
          <label for="confirmar" class="form-label">Confirmar contraseña:</label>
          <input type="password" class="form-control" name="confirmar" required>
        </div>

        <button type="submit" class="btn-gold mt-3">Registrarse</button>

        <div class="extra-links">
          ¿Ya tenés cuenta? <a href="./login_cliente.php">Iniciar sesión</a><br><br>
          <a href="../index.php" class="btn btn-outline-light">← Volver a la página principal</a>
        </div>
      </form>
    </div>
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
      this.setCustomValidity(regex.test(val) ? "" : "<?php echo mensajeRequisitosPassword(); ?>");
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