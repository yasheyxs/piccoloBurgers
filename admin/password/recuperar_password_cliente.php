<?php
$mensaje = "";
require_once __DIR__ . '/../../componentes/validar_telefono.php';
?>

<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Recuperar contraseña - Cliente</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <link rel="icon" href="../../public/img/favicon.png" type="image/x-icon" />
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

    .form-text {
      color: var(--text-muted);
    }
  </style>
</head>

<body>
  <nav class="navbar navbar-dark bg-dark">
    <div class="container">
      <a class="navbar-brand" href="../../public/index.php"><i class="fas fa-utensils"></i> Piccolo Burgers</a>
    </div>
  </nav>

  <div class="container mt-5">
    <h2 class="mb-4 text-center">Recuperar contraseña</h2>
    <form method="post" action="./procesar_recuperacion_cliente.php">
      <label for="telefono" class="form-label">Teléfono registrado:</label>
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
        <input type="text" class="form-control" name="telefono" id="telefono" required placeholder="Ej: 3511234567">
      </div>
      <small class="form-text">Ingresá solo números, sin espacios ni guiones.</small>

      <button type="submit" class="btn btn-gold w-100 mt-3">Enviar enlace de recuperación</button>
    </form>

    <div class="mt-3 text-center">
      <a href="../../public/client/login_cliente.php" style="color: var(--main-gold); font-weight: bold;">Volver al login</a>
    </div>

    <div class="mt-4 text-center">
      <?= $mensaje ?>
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
