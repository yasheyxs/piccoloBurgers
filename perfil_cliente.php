<?php
include("admin/bd.php");
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

function validarTelefono($codigo, $numero) {
  $codigo = preg_replace('/[^\d]/', '', $codigo);
  $numero = preg_replace('/[^\d]/', '', $numero);
  $telefono = '+' . $codigo . $numero;

  $longitudes = [
    '54' => [10],
    '598' => [8, 9],
    '55' => [10, 11],
    '56' => [9],
    '595' => [9],
    '591' => [8],
    '51' => [9],
    '1' => [10],
    '34' => [9]
  ];

  if (!isset($longitudes[$codigo])) return false;
  if (!in_array(strlen($numero), $longitudes[$codigo])) return false;

  return preg_match('/^\+\d{10,15}$/', $telefono) ? $telefono : false;
}

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
      font-family: var(--font-main);
      background-color: var(--dark-bg);
      color: var(--text-light);
      font-size: 1rem;
      line-height: 1.6;
      padding-top: 70px;
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

    .card-footer::before {
      content: "👤 ";
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

    .card-info::before {
      content: "👤";
      position: absolute;
      top: -0.8rem;
      left: -0.4rem;
      font-size: 4rem;
      color: var(--main-gold);
      filter: drop-shadow(0 0 4px rgba(250, 195, 12, 0.3));
      opacity: 0.1;
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

    .nav-link i.fas.fa-user-circle,
    .nav-link i.fas.fa-sign-out-alt {
      font-size: 1.35rem;
      vertical-align: middle;
    }

    input.form-control:focus,
select.form-select:focus,
textarea.form-control:focus {
  background-color: #111 !important; /* más oscuro */
  color: #fff !important;
  border-color: var(--main-gold) !important;
  box-shadow: 0 0 6px rgba(250, 195, 12, 0.5) !important;
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
          <li class="nav-item"><a class="nav-link" href="./index.php#testimonios">Testimonio</a></li>
          <li class="nav-item"><a class="nav-link" href="./index.php#nosotros">Nosotros</a></li>

          <!-- Dropdown compacto -->
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="extrasDropdown" role="button" data-bs-toggle="dropdown">
              Más
            </a>
            <ul class="dropdown-menu dropdown-menu-dark">
              <li><a class="dropdown-item" href="./index.php#puntos">Puntos</a></li>
              <li><a class="dropdown-item" href="./index.php#ubicacion">Ubicación</a></li>
              <li><a class="dropdown-item" href="./index.php#contacto">Contacto</a></li>
              <li><a class="dropdown-item" href="./index.php#horario">Horarios</a></li>
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

  <div class="container mt-5">
    <h2 class="mb-4 text-center">👤 Información del Cliente</h2>
    <div class="card card-info p-4 mb-5">
      <p><strong>Nombre:</strong> <?= htmlspecialchars($datos["nombre"]) ?></p>
      <p><strong>Teléfono:</strong> <?= htmlspecialchars($datos["telefono"]) ?></p>
      <p><strong>Email:</strong> <?= htmlspecialchars($datos["email"]) ?: "No registrado" ?></p>
      <p><strong>Fecha de Registro:</strong> <?= date("d/m/Y", strtotime($datos["fecha_registro"])) ?></p>
      <p><strong>Puntos disponibles:</strong> <?= $datos["puntos"] ?> ⭐</p>

      <div class="text-end mt-3">
        <button type="button" class="btn btn-gold" data-bs-toggle="modal" data-bs-target="#modalEditarCliente">
          Editar mis datos
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

      const bsToast = new bootstrap.Toast(toastEl, { delay: 2500 });
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
    0% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    50% { transform: translateX(5px); }
    75% { transform: translateX(-5px); }
    100% { transform: translateX(0); }
  }
</style>

<!-- Modal Editar Cliente -->
<div class="modal fade" id="modalEditarCliente" tabindex="-1" aria-labelledby="modalEditarClienteLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content bg-dark text-light border-secondary">
      <div class="modal-header">
        <h5 class="modal-title" id="modalEditarClienteLabel">Editar Cliente</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body">
        <!-- FORMULARIO DATOS -->
        <form method="POST">
          <h5 class="mb-3 text-light">Datos del cliente</h5>

          <div class="mb-3">
            <label for="nombre" class="form-label">Nombre:</label>
            <input type="text" class="form-control" name="nombre" id="nombre"
              value="<?= isset($cliente['nombre']) ? $cliente['nombre'] : '' ?>" required>
          </div>

          <div class="mb-3">
            <label for="email" class="form-label">Email:</label>
            <input type="email" class="form-control" name="email" id="email"
              value="<?= isset($cliente['email']) ? $cliente['email'] : '' ?>" placeholder="Opcional">
          </div>

          <div class="mb-3">
            <label for="telefono_editar" class="form-label">Teléfono:</label>
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

          <button type="submit" name="guardar_datos" class="btn btn-gold w-100">Guardar datos</button>
        </form>

        <hr class="my-4 border-secondary">

        <!-- FORMULARIO CONTRASEÑA -->
        <form method="POST">
          <h5 class="mb-3 text-light">Cambiar contraseña</h5>

          <div class="mb-3">
            <label for="password_actual" class="form-label">Contraseña actual:</label>
            <input type="password" class="form-control" name="password_actual" id="password_actual" required>
          </div>

          <div class="mb-3">
            <label for="password_nueva" class="form-label">Nueva contraseña:</label>
            <input type="password" class="form-control" name="password_nueva" id="password_nueva" placeholder="Mínimo 8 caracteres" required>
          </div>

          <div class="mb-3">
            <label for="password_confirmar" class="form-label">Confirmar nueva contraseña:</label>
            <input type="password" class="form-control" name="password_confirmar" id="password_confirmar" required>
          </div>

          <button type="submit" name="guardar_password" class="btn btn-gold w-100">Cambiar contraseña</button>
        </form>
      </div>
    </div>
  </div>
</div>

  <h2 class="mt-5 mb-4 text-center">📜 Historial de Pedidos</h2>
  <div id="historial-pedidos"></div>
  </div>


  <script>
    async function actualizarHistorial() {
      try {
        const response = await fetch('admin/obtener_pedidos_cliente.php');
        const pedidos = await response.json();

        let htmlPedidos = '';

        if (pedidos.length === 0) {
          htmlPedidos = `<div class="alert alert-info text-center">Aún no realizaste ningún pedido.</div>`;
        } else {
          pedidos.forEach((pedido, index) => {
            let estadoHtml = '';
            switch (pedido.estado) {
              case 'Cancelado':
                estadoHtml = `<span class="text-danger">Cancelado ❌ -  Esperamos poder servirte mejor en el futuro. </span>`;
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

            htmlPedidos += `
          <div class="pedido-resumen p-3 mb-2 rounded shadow-sm"
               data-index="${index}">
            <i class="fas fa-receipt me-2"></i>
            <span><strong>Fecha:</strong> ${new Date(pedido.fecha).toLocaleString()}</span>
            &mdash;
            <span><strong>Total:</strong> $${Number(pedido.total).toFixed(2)}</span>
          </div>

          <div class="pedido-detalle mb-4" id="detalle-${index}" style="display:none;">
            <div class="card shadow p-3" style="background-color: var(--gray-bg);">
              <p><strong>Entrega:</strong> ${pedido.tipo_entrega}</p>
              <p><strong>Método de pago:</strong> ${pedido.metodo_pago}</p>
              <p><strong>Estado:</strong> ${estadoHtml}</p>
              <p><strong>Nota:</strong> ${pedido.nota ? pedido.nota.replace(/\n/g, '<br>') : 'Sin nota'}</p>
              <strong>Productos:</strong>
              <div class="row mt-2">
        `;

            pedido.detalles.forEach(detalle => {
              htmlPedidos += `
            <div class="col-md-6 col-lg-4 mb-3">
              <div class="card h-100">
                <div class="card-body d-flex flex-column">
                  <h5 class="card-title">${detalle.nombre}</h5>
                  <p class="card-text mb-1"><strong>Precio unitario:</strong> $${Number(detalle.precio).toFixed(2)}</p>
                  <p class="card-text mb-1"><strong>Cantidad:</strong> ${detalle.cantidad}</p>
                  <p class="card-text"><strong>Subtotal:</strong> $${(detalle.precio * detalle.cantidad).toFixed(2)}</p>
                </div>
              </div>
            </div>
          `;
            });

            htmlPedidos += `
              </div>
            </div>
          </div>
        `;
          });
        }

        document.getElementById('historial-pedidos').innerHTML = htmlPedidos;

        // Restaurar estado abierto guardado en localStorage
        const abiertos = JSON.parse(localStorage.getItem('pedidosAbiertos') || '[]');

        // Evento click toggle detalle
        document.querySelectorAll('.pedido-resumen').forEach(elem => {
          const idx = elem.getAttribute('data-index');
          const detalle = document.getElementById('detalle-' + idx);

          // Restaurar visual abierto
          if (abiertos.includes(idx)) {
            detalle.style.display = 'block';
            elem.classList.add('activo');
          }

          elem.addEventListener('click', () => {
            if (detalle.style.display === 'none') {
              // Cerrar todos los detalles y quitar clase activo de todos
              document.querySelectorAll('.pedido-detalle').forEach(d => d.style.display = 'none');
              document.querySelectorAll('.pedido-resumen').forEach(e => e.classList.remove('activo'));
              abiertos.length = 0; // limpiar el array de abiertos

              // Abrir el detalle clickeado y marcarlo activo
              detalle.style.display = 'block';
              elem.classList.add('activo');

              // Guardar solo este abierto
              abiertos.push(idx);

              // Scroll suave para centrar el detalle en pantalla
              detalle.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
              });

            } else {
              // Cerrar el detalle si ya estaba abierto
              detalle.style.display = 'none';
              elem.classList.remove('activo');

              // Quitar de abiertos
              const pos = abiertos.indexOf(idx);
              if (pos > -1) {
                abiertos.splice(pos, 1);
              }
            }
            localStorage.setItem('pedidosAbiertos', JSON.stringify(abiertos));
          });
        });


      } catch (e) {
        console.error('Error al actualizar historial:', e);
      }
    }
    // Actualizar cada 10 segundos
    setInterval(actualizarHistorial, 10000);

    // Ejecutar al cargar
    document.addEventListener('DOMContentLoaded', actualizarHistorial);
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

</body>

</html>