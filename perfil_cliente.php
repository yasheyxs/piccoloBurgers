<?php
include("admin/bd.php");
require_once __DIR__ . '/componentes/validar_telefono.php';
session_start();

if (!isset($_SESSION["cliente"])) {
  header("Location: login_cliente.php");
  exit;
}

$errores = [];
$mensaje_error = "";
$mensaje_exito = "";
$datos_guardados_exitosamente = false; // ✅ Inicializada para evitar warning

$cliente = $_SESSION["cliente"];
$cliente_id = $cliente["id"];

// Refrescar datos del cliente
$stmt = $conexion->prepare("SELECT nombre, telefono, email, fecha_registro, puntos FROM tbl_clientes WHERE ID = ?");
$stmt->execute([$cliente_id]);
$datos = $stmt->fetch(PDO::FETCH_ASSOC);

// ACTUALIZAR DATOS PERSONALES
if (isset($_POST["guardar_datos"])) {
  $nuevo_nombre = trim($_POST["nombre"]);
  $codigo = trim($_POST["codigo_pais"] ?? "");
  $numero = trim($_POST["telefono"] ?? "");
  $nuevo_email = trim($_POST["email"]);

  $nuevo_telefono = validarTelefono($codigo, $numero);

  if (!$nuevo_telefono) {
    $errores[] = "Número de teléfono inválido.";
  }

  if ($nuevo_email !== "") {
    if (!filter_var($nuevo_email, FILTER_VALIDATE_EMAIL)) {
      $errores[] = "Email inválido.";
    }
  }

  if (empty($errores)) {
    $verificar = $conexion->prepare("SELECT COUNT(*) FROM tbl_clientes WHERE telefono = ? AND ID != ?");
    $verificar->execute([$nuevo_telefono, $cliente_id]);
    $existe = $verificar->fetchColumn();

    if ($existe > 0) {
      $errores[] = "El número de teléfono ya está registrado por otro cliente.";
    }
  }

  if (empty($errores)) {
    $valor_email = $nuevo_email === "" ? null : $nuevo_email;

    $actualizar = $conexion->prepare("UPDATE tbl_clientes SET nombre = ?, telefono = ?, email = ? WHERE ID = ?");
    $actualizar->execute([$nuevo_nombre, $nuevo_telefono, $valor_email, $cliente_id]);

    $_SESSION["cliente"]["nombre"] = $nuevo_nombre;
    $_SESSION["cliente"]["telefono"] = $nuevo_telefono;
    $_SESSION["cliente"]["email"] = $valor_email;

    $mensaje_exito = "Datos actualizados correctamente.";
    $datos_guardados_exitosamente = true; // ✅ Marcamos éxito
  }
}

// CAMBIAR CONTRASEÑA
if (isset($_POST["guardar_password"])) {
  $actual = trim($_POST["password_actual"] ?? "");
  $nueva = trim($_POST["password_nueva"] ?? "");
  $confirmar = trim($_POST["password_confirmar"] ?? "");

  if ($actual === "") {
    $errores[] = "Ingresá tu contraseña actual.";
  }

  if ($confirmar === "") {
    $errores[] = "Confirmá la nueva contraseña.";
  }

  if ($nueva !== "" && $confirmar !== "" && $nueva !== $confirmar) {
    $errores[] = "Las contraseñas no coinciden.";
  }

  if ($nueva !== "" && strlen($nueva) < 8) {
    $errores[] = "La nueva contraseña debe tener al menos 8 caracteres.";
  }

  if (empty($errores)) {
    $consulta = $conexion->prepare("SELECT password FROM tbl_clientes WHERE ID = ?");
    $consulta->execute([$cliente_id]);
    $cliente = $consulta->fetch(PDO::FETCH_ASSOC);

    $hashAlmacenado = $cliente["password"];
    $esHashModerno = strlen($hashAlmacenado) > 30 && str_starts_with($hashAlmacenado, '$2y$');

    $valido = $esHashModerno
      ? password_verify($actual, $hashAlmacenado)
      : md5($actual) === $hashAlmacenado;

    if (!$valido) {
      $errores[] = "La contraseña actual es incorrecta.";
    } else {
      $nuevoHash = password_hash($nueva, PASSWORD_BCRYPT);
      $update = $conexion->prepare("UPDATE tbl_clientes SET password = ? WHERE ID = ?");
      $update->execute([$nuevoHash, $cliente_id]);

      $mensaje_exito = "Contraseña actualizada correctamente.";
    }
  }
}

// Recarga automática si se guardaron los datos
if ($datos_guardados_exitosamente) {
  header("Location: perfil_cliente.php");
  exit;
}
?>


<!doctype html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Mi Perfil - Piccolo Burgers</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">


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
      height: 100%;
      /* Asegura que el body ocupe toda la altura */
      margin: 0;
      padding: 0;
      font-family: var(--font-main);
      color: var(--text-light);
      background: url('img/HamLoginCliente.jpg') no-repeat center center fixed;
      background-size: cover;
      background-attachment: fixed;
      overflow-x: hidden;
      overflow-y: auto;
    }

    .navbar-brand {
      font-family: var(--font-title);
      text-transform: uppercase;
      letter-spacing: 1px;
      font-size: 1.5rem;
    }

    .navbar {
      background-color: #111;
    }

    .navbar-brand,
    .nav-link {
      font-family: var(--font-main);
      font-size: 1.2rem;
    }

    /* Contenedor con efecto Glass */
    .glass {
      background: rgba(44, 44, 44, 0.7);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      border-radius: 15px;
      padding: 0.5rem 1rem;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5);
      margin: 0 auto;
      flex-grow: 1;
    }

    .pedido-resumen {
      cursor: pointer;
      background-color: var(--gray-bg);
      color: var(--text-light);
      font-weight: 600;
      border-left: 5px solid var(--main-gold);
      transition: background-color 0.3s ease, color 0.3s ease;
      padding: 12px 16px;
    }

    .pedido-resumen:hover {
      background-color: var(--gold-hover);
      color: #000;
      border-left-color: var(--main-gold);
    }

    .pedido-resumen.activo {
      background-color: var(--gold-hover);
      color: #000;
      border-left-color: var(--main-gold);
      box-shadow: 0 0 8px var(--main-gold);
      transition: background-color 0.3s ease, color 0.3s ease, box-shadow 0.3s ease;
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

    .card {
      background-color: var(--gray-bg);
      border-radius: 16px;
      border: none;
      box-shadow: 0 6px 16px rgba(0, 0, 0, 0.25);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      overflow: hidden;
    }

    .card:hover {
      transform: translateY(-4px);
      box-shadow: 0 10px 24px rgba(0, 0, 0, 0.4);
    }

    .card-img-top {
      display: block;
      max-height: 200px;
      width: 100%;
      object-fit: cover;
      transition: transform 0.3s ease;
    }

    .card:hover .card-img-top {
      transform: scale(1.05);
    }

    .card-title {
      font-family: var(--font-title);
      font-size: 1.8rem;
      color: var(--text-light);
    }

    .card-text {
      font-size: 0.9rem;
      color: var(--text-muted);
    }

    .card-footer {
      background-color: transparent;
      color: var(--text-light);
      font-weight: 600;
      font-size: 1rem;
      text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
      border-left: 4px solid var(--main-gold);
      padding-left: 12px;
    }

    .card p,
    .card li,
    .card span,
    .card strong {
      color: var(--text-light);
    }

    .card-info {
      background-color: #343434;
      /* más claro que --gray-bg */
      border-left: 5px solid var(--main-gold);
      position: relative;
      padding-left: 1.5rem !important;
      box-shadow: 0 8px 20px rgba(250, 195, 12, 0.2);
      padding-top: 2.5rem;
    }

    .card-info strong {
      color: var(--main-gold);
    }

    .card-info p:first-child strong {
      font-size: 1.2rem;
      font-weight: 600;
      letter-spacing: 0.5px;
    }

    .alert-info {
      background-color: #2c2c2c;
      color: var(--text-light);
      border: 1px solid var(--gold-hover);
    }

    input.form-control {
      background-color: #1e1e1e;
      color: #cccccc;
      border: 1px solid #444;
    }

    input.form-control::placeholder {
      color: #777;
    }

    .glass-card {
      background: rgba(44, 44, 44, 0.7);
      border-radius: 20px;
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      transition: box-shadow 0.3s ease;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .glass-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 10px 24px rgba(0, 0, 0, 0.4);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .info-label {
      letter-spacing: 0.1em;
    }

    .info-value {
      word-wrap: break-word;
    }

    h2 {
      text-shadow: 0 0 8px rgba(0, 0, 0, 0.7);
    }

    .btn-outline-light {
      border-width: 2px;
      color: white;
      transition: background-color 0.3s, color 0.3s;
    }

    .btn-outline-light:hover {
      background-color: rgba(255, 255, 255, 0.25);
      color: white;
    }

  </style>
</head>
<body>

  <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">

    <div class="container">
      <a class="navbar-brand" href="#"><i class="fas fa-utensils"></i> Piccolo Burgers</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item"><a class="nav-link" href="./index.php">Inicio</a></li>
          <li class="nav-item"><a class="nav-link" href="./index.php#menu">Menú</a></li>
          
          <li class="nav-item"><a class="nav-link" href="./index.php#nosotros">Nosotros</a></li>
          <li class="nav-item"><a class="nav-link" href="./index.php#testimonios">Testimonio</a></li>

          <!-- Dropdown compacto -->
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="extrasDropdown" role="button" data-bs-toggle="dropdown">
              Más
            </a>
            <ul class="dropdown-menu dropdown-menu-dark">
              <li><a class="dropdown-item" href="./index.php#puntos">Puntos</a></li>
              <li><a class="dropdown-item" href="./index.php#ubicacion">Ubicación</a></li>
              <li><a class="dropdown-item" href="./index.php#contacto">Contacto</a></li>

            </ul>
          </li>

        <!-- Carrito -->
        <li class="nav-item">
          <a class="nav-link position-relative" href="carrito.php">
            <i class="fas fa-shopping-cart"></i>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="contador-carrito" style="font-size: 0.7rem;">
              0
            </span>
          </a>
        </li>

          <!-- Sesión -->
          <?php if (isset($_SESSION["cliente"])): ?>
            <li class="nav-item">
              <a href="" class="nav-link" title="<?= htmlspecialchars($_SESSION["cliente"]["nombre"]) ?>">
                <i class="fas fa-user-circle"></i>
              </a>
            </li>
            <li class="nav-item">
              <a href="#" class="nav-link" data-bs-toggle="modal" data-bs-target="#logoutModal" title="Cerrar sesión">
                <i class="fas fa-sign-out-alt"></i>
              </a>
            </li>

          <?php else: ?>
            <li class="nav-item">
              <a href="login_cliente.php" class="btn btn-outline-light ms-2">Iniciar sesión</a>
            </li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </nav>

  <div class="container mt-5 pt-5">
    <h2 class="mb-4 text-center">👤 Información del Cliente</h2>
    <div class="card glass-card p-4 mb-5" style="max-width: 600px; margin: 0 auto; border-radius: 15px;">

      <div class="row mb-3 align-items-center info-item">
  <div class="col-3 text-center">
    <i class="fas fa-user fa-2x text-white"></i>
  </div>
  <div class="col-9">
    <div class="info-label fw-semibold text-uppercase text-white small">Nombre</div>
    <div class="info-value fs-5 text-white"><?= htmlspecialchars($datos["nombre"]) ?></div>
  </div>
</div>
<div class="row mb-3 align-items-center info-item">
  <div class="col-3 text-center">
    <i class="fas fa-phone fa-2x text-white"></i>
  </div>
  <div class="col-9">
    <div class="info-label fw-semibold text-uppercase text-white small">Teléfono</div>
    <div class="info-value fs-5 text-white"><?= htmlspecialchars($datos["telefono"]) ?></div>
  </div>
</div>
<div class="row mb-3 align-items-center info-item">
  <div class="col-3 text-center">
    <i class="fas fa-envelope fa-2x text-white"></i>
  </div>
  <div class="col-9">
    <div class="info-label fw-semibold text-uppercase text-white small">Email</div>
    <div class="info-value fs-5 text-white"><?= htmlspecialchars($datos["email"]) ?></div>
  </div>
</div>
<div class="row mb-3 align-items-center info-item">
  <div class="col-3 text-center">
    <i class="fas fa-calendar-alt fa-2x text-white"></i>
  </div>
  <div class="col-9">
    <div class="info-label fw-semibold text-uppercase text-white small">Fecha de Registro</div>
    <div class="info-value fs-5 text-white"><?= htmlspecialchars($datos["fecha_registro"]) ?></div>
  </div>
</div>
<div class="row mb-3 align-items-center info-item">
  <div class="col-3 text-center">
    <i class="fas fa-star fa-2x text-warning"></i>
  </div>
  <div class="col-9">
    <div class="info-label fw-semibold text-uppercase text-white small">Puntos</div>
    <div class="info-value fs-5 text-white"><?= htmlspecialchars($datos["puntos"]) ?></div>
  </div>
</div>


      <div class="text-center mt-3">
  <button type="button" class="btn btn-outline-light btn-lg fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalEditarCliente" style="border-radius: 50px;">
    ✏️ Editar mis datos
  </button>
</div>

    </div>

  </div>

  <!-- Contenedor de toasts -->
  <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1100">
    <div id="toastMsg" class="toast align-items-center border-0 shadow" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="d-flex">
        <div class="toast-body"></div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
      </div>
    </div>
  </div>

  <script>
    document.addEventListener("DOMContentLoaded", () => {
      const errores = <?= json_encode($errores) ?>;
      const msgError = <?= json_encode($mensaje_error) ?>;
      const msgExito = <?= json_encode($mensaje_exito) ?>;

      function mostrarToast(texto, tipo = "success") {
        const toastEl = document.getElementById("toastMsg");
        const toastBody = toastEl.querySelector(".toast-body");
        toastBody.textContent = texto;

        toastEl.classList.remove("bg-success", "bg-danger");
        toastEl.classList.add(tipo === "error" ? "bg-danger" : "bg-success", "text-white");

        const bsToast = new bootstrap.Toast(toastEl, {
          delay: 2500
        });
        bsToast.show();
      }

      if (msgExito) {
        mostrarToast(msgExito, "success");
      }

      if (msgError) {
        mostrarToast(msgError, "error");
      }

      if (errores.length > 0) {
        const prioridad = errores.find(e => e.toLowerCase().includes("contraseña actual es incorrecta"));
        if (prioridad) {
          mostrarToast(prioridad, "error");

          // Sacudir el campo de contraseña actual
          const campo = document.getElementById("password_actual");
          if (campo) {
            campo.classList.add("shake");
            setTimeout(() => campo.classList.remove("shake"), 500);
          }
        } else {
          mostrarToast(errores[0], "error");
        }
      }
    });
  </script>

  <style>
    .shake {
      animation: shake 0.3s ease-in-out;
    }

    @keyframes shake {
      0% {
        transform: translateX(0);
      }

      25% {
        transform: translateX(-5px);
      }

      50% {
        transform: translateX(5px);
      }

      75% {
        transform: translateX(-5px);
      }

      100% {
        transform: translateX(0);
      }
    }
  </style>

  <!-- Modal Editar Cliente -->
<div class="modal fade" id="modalEditarCliente" tabindex="-1" aria-labelledby="modalEditarClienteLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content glass-card border-0 shadow-lg">
      <div class="modal-header border-0">
        <h5 class="modal-title text-light fw-bold" id="modalEditarClienteLabel">
          <i class="fas fa-user-edit me-2 text-warning"></i>Editar Cliente
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body text-light">
        <!-- FORMULARIO DATOS -->
        <form method="POST">
          <h5 class="mb-3 text-warning">Datos del cliente</h5>

          <div class="mb-3">
            <label for="nombre" class="form-label text-light">Nombre:</label>
            <input type="text" class="form-control bg-dark text-light border-secondary" name="nombre" id="nombre"
              value="<?= isset($cliente['nombre']) ? $cliente['nombre'] : '' ?>" required>
          </div>

          <div class="mb-3">
            <label for="email" class="form-label text-light">Email:</label>
            <input type="email" class="form-control bg-dark text-light border-secondary" name="email" id="email"
              value="<?= isset($cliente['email']) ? $cliente['email'] : '' ?>" placeholder="Opcional">
          </div>

          <div class="mb-3">
  <label for="telefono_editar" class="form-label text-light">Teléfono:</label>
  <div class="input-group">
    <span class="input-group-text bg-dark text-light border-secondary p-0">
      <select name="codigo_pais" class="form-select bg-dark text-light border-0 px-3" id="codigo_pais_editar" required style="min-width: 100px;">
        <option value="54" <?= isset($cliente['telefono']) && strpos($cliente['telefono'], '+54') === 0 ? 'selected' : '' ?>>🇦🇷 +54</option>
        <option value="598" <?= isset($cliente['telefono']) && strpos($cliente['telefono'], '+598') === 0 ? 'selected' : '' ?>>🇺🇾 +598</option>
        <option value="55" <?= isset($cliente['telefono']) && strpos($cliente['telefono'], '+55') === 0 ? 'selected' : '' ?>>🇧🇷 +55</option>
        <option value="56" <?= isset($cliente['telefono']) && strpos($cliente['telefono'], '+56') === 0 ? 'selected' : '' ?>>🇨🇱 +56</option>
        <option value="595" <?= isset($cliente['telefono']) && strpos($cliente['telefono'], '+595') === 0 ? 'selected' : '' ?>>🇵🇾 +595</option>
        <option value="591" <?= isset($cliente['telefono']) && strpos($cliente['telefono'], '+591') === 0 ? 'selected' : '' ?>>🇧🇴 +591</option>
        <option value="51" <?= isset($cliente['telefono']) && strpos($cliente['telefono'], '+51') === 0 ? 'selected' : '' ?>>🇵🇪 +51</option>
        <option value="1" <?= isset($cliente['telefono']) && strpos($cliente['telefono'], '+1') === 0 ? 'selected' : '' ?>>🇺🇸 +1</option>
        <option value="34" <?= isset($cliente['telefono']) && strpos($cliente['telefono'], '+34') === 0 ? 'selected' : '' ?>>🇪🇸 +34</option>
      </select>
    </span>
    <input type="text" class="form-control bg-dark text-light border-secondary" name="telefono" id="telefono_editar"
      value="<?= isset($cliente['telefono']) ? preg_replace('/^\+\d+/', '', $cliente['telefono']) : '' ?>" required placeholder="Ej: 3511234567">
  </div>
  <small class="form-text text-muted">Ingresá solo números, sin espacios ni guiones.</small>
</div>


          <div class="d-grid">
            <button type="submit" name="guardar_datos" class="btn btn-gold">Guardar cambios</button>
          </div>
        </form>

        <hr class="my-4 border-secondary">

        <!-- FORMULARIO CONTRASEÑA -->
        <form method="POST">
          <h5 class="mb-3 text-warning">Cambiar contraseña</h5>

          <div class="mb-3">
            <label for="password_actual" class="form-label text-light">Contraseña actual:</label>
            <input type="password" class="form-control bg-dark text-light border-secondary" name="password_actual" id="password_actual" required>
          </div>

          <div class="mb-3">
            <label for="password_nueva" class="form-label text-light">Nueva contraseña:</label>
            <input type="password" class="form-control bg-dark text-light border-secondary" name="password_nueva" id="password_nueva" placeholder="Mínimo 8 caracteres" required>
          </div>

          <div class="mb-3">
            <label for="password_confirmar" class="form-label text-light">Confirmar nueva contraseña:</label>
            <input type="password" class="form-control bg-dark text-light border-secondary" name="password_confirmar" id="password_confirmar" required>
          </div>

          <div class="d-grid">
            <button type="submit" name="guardar_password" class="btn btn-gold">Cambiar contraseña</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>


  <div class="container mt-5">
  <h2 class="text-center text-white mb-4">
    <i class="fas fa-receipt text-warning me-2"></i>Historial de Pedidos
  </h2>
  <div id="historial-pedidos" class="row justify-content-center"></div>
</div>

<!-- Modal para mostrar detalles -->
<div class="modal fade" id="modalDetallePedido" tabindex="-1" aria-labelledby="modalDetallePedidoLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content glass-card border-0 shadow-lg" style="border-radius: 20px;">
      <div class="modal-header border-0 bg-dark text-light rounded-top px-4 py-3">
        <h5 class="modal-title fw-bold d-flex align-items-center" id="modalDetallePedidoLabel">
          <i class="fas fa-box-open text-warning me-2 fa-lg"></i>
          <span>Detalle del Pedido</span>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body text-light px-4 py-3" style="background-color: rgba(30,30,30,0.85); border-radius: 0 0 20px 20px;">
        <div class="text-center mb-3">
          <i class="fas fa-info-circle fa-2x text-warning"></i>
          <p class="mt-2 text-muted">Aquí verás todos los detalles de tu pedido.</p>
        </div>
        <!-- Aquí se insertan los detalles dinámicamente -->
      </div>
    </div>
  </div>
</div>



<script>
  // Función para construir el HTML del detalle del pedido
  function crearHtmlDetalle(pedido) {
    let estadoHtml = '';
    switch (pedido.estado) {
      case 'Cancelado':
        estadoHtml = `<span class="text-danger">Cancelado ❌ - Esperamos poder servirte mejor en el futuro.</span>`;
        break;
      case 'Listo':
        estadoHtml = `<span class="text-success">Listo ✅</span>`;
        break;
      case 'En preparación':
        estadoHtml = `<span class="text-warning">En preparación ⏳</span>`;
        break;
      case 'En camino':
        estadoHtml = `<span class="text-info">En camino 🚚</span>`;
        break;
      default:
        estadoHtml = pedido.estado;
    }

    let productosHtml = '';
    pedido.detalles.forEach(detalle => {
      productosHtml += `
        <div class="producto-tarjeta mb-2">
          <strong>${detalle.nombre}</strong><br>
          <small>Precio: $${Number(detalle.precio).toFixed(2)} | Cantidad: ${detalle.cantidad} | Subtotal: $${(detalle.precio * detalle.cantidad).toFixed(2)}</small>
        </div>
      `;
    });

    return `
      <div class="detalle-card bg-dark text-light p-3 border border-secondary rounded">
        <p><strong>Entrega:</strong> ${pedido.tipo_entrega}</p>
        <p><strong>Método de pago:</strong> ${pedido.metodo_pago}</p>
        <p><strong>Estado:</strong> ${estadoHtml}</p>
        <p><strong>Nota:</strong> ${pedido.nota ? pedido.nota.replace(/\n/g, '<br>') : 'Sin nota'}</p>
        <strong>Productos:</strong>
        <div class="mt-2">${productosHtml}</div>
      </div>
    `;
  }

  async function actualizarHistorial() {
    try {
      const response = await fetch('admin/obtener_pedidos_cliente.php');
      const pedidos = await response.json();

      const historialContenedor = document.getElementById('historial-pedidos');
      let htmlPedidos = '';

      if (pedidos.length === 0) {
        htmlPedidos = `
          <div class="col-12">
            <div class="alert alert-info text-center">Aún no realizaste ningún pedido.</div>
          </div>`;
      } else {
        pedidos.forEach((pedido, index) => {
const pedidoId = pedido.id ?? (pedidos.length - index);
          htmlPedidos += `
            <div class="col-md-6 col-lg-3 mb-3">
              <div class="pedido-card glass-card card h-100 p-3 shadow-sm text-center" data-index="${index}" style="border-radius: 15px;">
                <i class="fas fa-receipt fa-2x mb-3 text-warning"></i>
                <h5 class="card-title">Pedido #${pedidoId}</h5>
                <p><strong>Fecha:</strong><br>${new Date(pedido.fecha).toLocaleDateString()}</p>
                <p><strong>Total:</strong> $${Number(pedido.total).toFixed(2)}</p>
                <button class="btn btn-outline-warning btn-sm mt-2 ver-detalle-btn" data-index="${index}">Ver detalle</button>
              </div>
            </div>
          `;
        });
      }

      historialContenedor.innerHTML = htmlPedidos;

      // Actualizar contenido del modal si está abierto
      const modalEl = document.getElementById('modalDetallePedido');
      if (modalEl.classList.contains('show')) {
        const idx = modalEl.getAttribute('data-index');
        const pedido = pedidos[idx];
        const modalBody = modalEl.querySelector('.modal-body');
        modalBody.innerHTML = crearHtmlDetalle(pedido);
      }

      // Delegación de eventos para los botones "Ver detalle"
      historialContenedor.addEventListener('click', (e) => {
        const btn = e.target.closest('.ver-detalle-btn');
        if (!btn) return;

        const idx = btn.getAttribute('data-index');
        const pedido = pedidos[idx];

        const modalBody = document.querySelector('#modalDetallePedido .modal-body');
        const modalTitle = document.querySelector('#modalDetallePedidoLabel');
        const pedidoId = pedido.id ?? (parseInt(idx) + 1);

        modalTitle.textContent = `Detalle del Pedido #${pedidoId}`;
        modalBody.innerHTML = crearHtmlDetalle(pedido);

        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modalEl.setAttribute('data-index', idx);
        modal.show();
      });

    } catch (e) {
      console.error('Error al actualizar historial:', e);
    }
  }

  document.addEventListener('DOMContentLoaded', actualizarHistorial);
  setInterval(actualizarHistorial, 10000);
</script>


  <?php include("componentes/whatsapp_button.php"); ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

  <?php include("componentes/carrito_button.php"); ?>

  <!-- Modal de cierre de sesión -->
  <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content bg-dark text-white border-0 shadow-lg">
        <div class="modal-header border-bottom-0">
          <h5 class="modal-title fw-bold" id="logoutModalLabel">
            <i class="fas fa-sign-out-alt me-2 text-warning"></i> ¿Cerrar sesión?
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body text-center">
          <p class="fs-5 mb-0">¿Estás seguro de que querés cerrar sesión?</p>
        </div>
        <div class="modal-footer justify-content-center border-top-0">
          <button type="button" class="btn btn-success px-4" data-bs-dismiss="modal">
            <i></i> Quedarme
          </button>
          <a href="./logout_cliente.php" class="btn btn-danger px-4">
            <i class="fas fa-door-open me-1"></i> Cerrar Sesión
          </a>
        </div>
      </div>
    </div>
  </div>
  <?php if (isset($_SESSION['toast'])): ?>
    <div class="toast-container position-fixed bottom-0 end-0 p-3 z-3">
      <div id="toastContacto" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
          <div class="toast-body">
            <?php echo $_SESSION['toast'];
            unset($_SESSION['toast']); ?>
          </div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Cerrar"></button>
        </div>
      </div>
    </div>

  <?php endif; ?>

  <script>
    const longitudesPorPais = {
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

    document.addEventListener("DOMContentLoaded", () => {
      const selectPais = document.getElementById("codigo_pais_editar");
      const inputTelefono = document.getElementById("telefono_editar");

      function actualizarMaxLength() {
        const codigo = selectPais.value;
        const max = longitudesPorPais[codigo] || 15;
        inputTelefono.setAttribute("maxlength", max);
      }

      selectPais.addEventListener("change", actualizarMaxLength);
      actualizarMaxLength(); // Inicializar al cargar
    });
  </script>

  <script>
  document.addEventListener("DOMContentLoaded", () => {
    const contador = document.getElementById("contador-carrito");
    const carrito = JSON.parse(localStorage.getItem("carrito")) || [];
    contador.textContent = carrito.length;
  });
</script>

  

</body>

</html>