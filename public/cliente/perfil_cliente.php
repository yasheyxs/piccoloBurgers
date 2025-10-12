<?php
require_once __DIR__ . '/../../admin/bd.php';
require_once __DIR__ . '/../../componentes/validar_telefono.php';
require_once __DIR__ . '/../../componentes/password_utils.php';
require_once __DIR__ . '/../../includes/email_requirement.php';

session_start();

if (!isset($_SESSION["cliente"])) {
  header("Location: login_cliente.php");
  exit;
}

enforceEmailRequirement('perfil_cliente.php', true);

$emailObligatorioMensaje = $_SESSION['email_obligatorio_mensaje'] ?? '';

$errores = [];
$mensaje_error = "";
$mensaje_exito = "";
$datos_guardados_exitosamente = false; // Inicializada para evitar warning

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

  if ($nuevo_email === '') {
    $errores[] = "Ingresá un email.";
  } elseif (!filter_var($nuevo_email, FILTER_VALIDATE_EMAIL)) {
    $errores[] = "Email inválido.";
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
    $verificar = $conexion->prepare("SELECT COUNT(*) FROM tbl_clientes WHERE email = ? AND ID != ?");
    $verificar->execute([$nuevo_email, $cliente_id]);

    if ($verificar->fetchColumn() > 0) {
      $errores[] = "El email ya está registrado por otro cliente.";
    }
  }

  if (empty($errores)) {

    $actualizar = $conexion->prepare("UPDATE tbl_clientes SET nombre = ?, telefono = ?, email = ? WHERE ID = ?");
    $actualizar->execute([$nuevo_nombre, $nuevo_telefono, $nuevo_email, $cliente_id]);
    $_SESSION["cliente"]["nombre"] = $nuevo_nombre;
    $_SESSION["cliente"]["telefono"] = $nuevo_telefono;
    $_SESSION["cliente"]["email"] = $nuevo_email;

    limpiarAvisoEmailObligatorio();

    $mensaje_exito = "Datos actualizados correctamente.";
    $datos_guardados_exitosamente = true;
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

  if ($nueva === "") {
    $errores[] = "Ingresá una nueva contraseña.";
  }

  if ($confirmar === "") {
    $errores[] = "Confirmá la nueva contraseña.";
  }

  if ($nueva !== "" && !passwordCumpleRequisitos($nueva)) {
    $errores[] = mensajeRequisitosPassword();
  }

  if ($nueva !== "" && $confirmar !== "" && passwordCumpleRequisitos($nueva) && $nueva !== $confirmar) {
    $errores[] = "Las contraseñas no coinciden.";
  }

  if (empty($errores)) {
    $consulta = $conexion->prepare("SELECT password FROM tbl_clientes WHERE ID = ?");
    $consulta->execute([$cliente_id]);
    $cliente = $consulta->fetch(PDO::FETCH_ASSOC);

    $hashAlmacenado = $cliente["password"] ?? '';

    if (!passwordCoincideConHash($actual, $hashAlmacenado)) {
      $errores[] = "La contraseña actual es incorrecta.";
    } elseif (passwordCoincideConHash($nueva, $hashAlmacenado)) {
      $errores[] = "La nueva contraseña debe ser distinta de la actual.";
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


  <link rel="icon" href="../img/favicon.png" type="image/x-icon" />
  <link rel="stylesheet" href="../assets/css/custom.css">

  <style>
    body {
      height: 100%;
      /* Asegura que el body ocupe toda la altura */
      margin: 0;
      padding: var(--navbar-height) 0 0;
      font-family: var(--font-main);
      color: var(--text-light);
      background: url('../img/HamLoginCliente.jpg') no-repeat center center fixed;
      background-size: cover;
      background-attachment: fixed;
      overflow-x: hidden;
      overflow-y: auto;
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

    .requirement-text {
      color: var(--text-light);
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

  <?php
  $navBasePath = '../index.php';
  $navHomeLink = '../index.php';
  $navCarritoLink = '../carrito.php';
  $navAuthLink = 'login_cliente.php';
  $navProfileLink = 'perfil_cliente.php';
  $navLogoutLink = 'logout_cliente.php';
  include __DIR__ . '/../../views/partials/navbar.php';
  ?>

  <div class="container mt-5 pt-5">
    <?php if ($emailObligatorioMensaje !== ''): ?>
      <div class="alert alert-warning text-dark fw-semibold" role="alert">
        <?= htmlspecialchars($emailObligatorioMensaje); ?>
        <div class="mt-2 fw-normal">
          Si no querés actualizarlo ahora, podés <a href="logout_cliente.php" class="alert-link">cerrar sesión</a>.
        </div>
      </div>
    <?php endif; ?>
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
                value="<?= isset($cliente['email']) ? $cliente['email'] : '' ?>" placeholder="ejemplo@correo.com" required
                autocomplete="email">
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
                  value="<?= isset($cliente['telefono']) ? preg_replace('/^\+\d+/', '', $cliente['telefono']) : '' ?>" required placeholder="Ej: 3511234567"
                  oninput="this.value = this.value.replace(/[^0-9]/g, '');" inputmode="numeric" pattern="[0-9]*">
              </div>
              <small class="form-text requirement-text">Ingresá solo números, sin espacios ni guiones.</small>
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
              <input type="password" class="form-control bg-dark text-light border-secondary" name="password_actual" id="password_actual" required autocomplete="current-password">
            </div>

            <div class="mb-3">
              <label for="password_nueva" class="form-label text-light">Nueva contraseña:</label>
              <input
                type="password"
                class="form-control bg-dark text-light border-secondary"
                name="password_nueva"
                id="password_nueva"
                required
                autocomplete="new-password"
                pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}"
                title="<?php echo mensajeRequisitosPassword(); ?>">
              <div class="form-text requirement-text"><?php echo mensajeRequisitosPassword(); ?></div>
            </div>

            <div class="mb-3">
              <label for="password_confirmar" class="form-label text-light">Confirmar nueva contraseña:</label>
              <input type="password" class="form-control bg-dark text-light border-secondary" name="password_confirmar" id="password_confirmar" required autocomplete="new-password">
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
    <div class="historial-wrapper mt-3 position-relative">
      <button type="button" class="historial-nav historial-nav-left" aria-label="Pedidos anteriores">
        <i class="fas fa-chevron-left"></i>
      </button>
      <div id="historial-pedidos" class="pedidos-scroll"></div>
      <button type="button" class="historial-nav historial-nav-right" aria-label="Pedidos siguientes">
        <i class="fas fa-chevron-right"></i>
      </button>
    </div>
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
    function escapeHtml(texto) {
      const elemento = document.createElement('div');
      elemento.textContent = texto ?? '';
      return elemento.innerHTML;
    }

    function obtenerMetodoPago(pedido) {
      if (!pedido) {
        return 'No especificado';
      }
      const metodo = (pedido.metodo_pago ?? '').toString().trim();
      return metodo !== '' ? metodo : 'No especificado';
    }

    const historialContenedor = document.getElementById('historial-pedidos');
    let pedidosActuales = [];

    (function inicializarEstilosHistorial() {
      const estiloId = 'historial-pedidos-scroll-style';
      if (!document.getElementById(estiloId)) {
        const estilos = document.createElement('style');
        estilos.id = estiloId;
        estilos.textContent = `
          .historial-wrapper {
            position: relative;
            --historial-horizontal-padding: 3.25rem;
          }

          #historial-pedidos {
            display: flex;
            flex-wrap: nowrap;
            gap: 1rem;
            overflow-x: auto;
            padding: 0.75rem var(--historial-horizontal-padding) 1rem;
            margin: 0;
            scroll-behavior: smooth;
            scroll-snap-type: x mandatory;
            scroll-snap-stop: always;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
            scroll-padding: 0 var(--historial-horizontal-padding);
          }

           #historial-pedidos::-webkit-scrollbar {
            display: none;
          }

          #historial-pedidos > .pedido-item {
            flex: 0 0 auto;
            width: min(280px, 82vw);
            scroll-snap-align: start;
          }

          @media (min-width: 768px) {
            #historial-pedidos > .pedido-item {
              width: 260px;
            }
          }

          @media (min-width: 992px) {
            #historial-pedidos > .pedido-item {
              width: 280px;
            }
          }

          .historial-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 2.75rem;
            height: 2.75rem;
            border-radius: 50%;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(0, 0, 0, 0.6);
            color: #ffc107;
            z-index: 2;
            transition: opacity 0.2s ease, transform 0.2s ease;
            cursor: pointer;
            opacity: 0;
            pointer-events: none;
          }

          .historial-wrapper.historial-scroll-activo .historial-nav {
            opacity: 0.85;
            pointer-events: auto;
          }

          .historial-nav-left {
            left: 0.75rem;
          }

          .historial-nav-right {
            right: 0.75rem;
          }

          .historial-nav:disabled {
            opacity: 0.35 !important;
            pointer-events: none;
          }

          .historial-nav i {
            pointer-events: none;
          }

          @media (hover: hover) and (pointer: fine) {
            .historial-wrapper.historial-scroll-activo .historial-nav:hover {
              opacity: 1;
              background-color: rgba(0, 0, 0, 0.8);
            }
          }
        `;
        document.head.appendChild(estilos);
      }
    })();


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
          <strong>${escapeHtml(detalle.nombre)}</strong><br>
          <small>Precio: $${Number(detalle.precio).toFixed(2)} | Cantidad: ${detalle.cantidad} | Subtotal: $${(detalle.precio * detalle.cantidad).toFixed(2)}</small>
        </div>
      `;
      });

      const tipoEntregaTexto = escapeHtml(((pedido.tipo_entrega ?? '').toString().trim()) || 'No especificado');
      const metodoPagoTexto = escapeHtml(obtenerMetodoPago(pedido));
      const notaTexto = pedido.nota ? escapeHtml(pedido.nota).replace(/\n/g, '<br>') : 'Sin nota';

      return `
      <div class="detalle-card bg-dark text-light p-3 border border-secondary rounded">
        <p><strong>Entrega:</strong> ${tipoEntregaTexto}</p>
        <p><strong>Método de pago:</strong> ${metodoPagoTexto}</p>
        <p><strong>Estado:</strong> ${estadoHtml}</p>
        <p><strong>Nota:</strong> ${notaTexto}</p>
        <strong>Productos:</strong>
        <div class="mt-2">${productosHtml}</div>
      </div>
    `;
    }

    const historialWrapper = document.querySelector('.historial-wrapper');
    const botonAnteriorHistorial = document.querySelector('.historial-nav-left');
    const botonSiguienteHistorial = document.querySelector('.historial-nav-right');

    function obtenerPasoScroll() {
      if (!historialContenedor) {
        return 0;
      }
      const primerItem = historialContenedor.querySelector('.pedido-item');
      if (!primerItem) {
        return historialContenedor.clientWidth;
      }
      const estilos = window.getComputedStyle(historialContenedor);
      const gapTexto = estilos.columnGap || estilos.gap || '0';
      const gap = parseFloat(gapTexto) || 0;
      return primerItem.getBoundingClientRect().width + gap;
    }

    function actualizarPaddingCarrusel() {
      if (!historialContenedor) {
        return;
      }

      const primerItem = historialContenedor.querySelector('.pedido-item');
      const anchoFlecha = botonAnteriorHistorial?.offsetWidth ?? 0;
      const paddingBase = anchoFlecha ? anchoFlecha + 12 : 16;

      if (!primerItem) {
        historialContenedor.style.setProperty('--historial-horizontal-padding', `${paddingBase}px`);
        return;
      }

      const wrapperWidth = historialWrapper?.clientWidth ?? historialContenedor.clientWidth;
      const estilos = window.getComputedStyle(historialContenedor);
      const gapTexto = estilos.columnGap || estilos.gap || '0';
      const gap = parseFloat(gapTexto) || 0;
      const cardWidth = primerItem.getBoundingClientRect().width;
      const totalHijos = historialContenedor.children.length;
      const capacidadFila = Math.max(Math.floor((wrapperWidth + gap) / (cardWidth + gap)), 1);
      const itemsConsiderados = Math.min(totalHijos, capacidadFila);
      const anchoContenido = itemsConsiderados * cardWidth + Math.max(itemsConsiderados - 1, 0) * gap;
      const espacioLibre = Math.max(wrapperWidth - anchoContenido, 0);
      const paddingCalculado = Math.max(paddingBase, espacioLibre / 2 + gap / 2);

      historialContenedor.style.setProperty('--historial-horizontal-padding', `${paddingCalculado}px`);
    }


    function actualizarEstadoFlechas() {
      if (!botonAnteriorHistorial || !botonSiguienteHistorial || !historialWrapper) {
        return;
      }
      const maxScrollLeft = historialContenedor.scrollWidth - historialContenedor.clientWidth;
      const scrollLeft = historialContenedor.scrollLeft;

      const hayOverflow = maxScrollLeft > 2;
      historialWrapper.classList.toggle('historial-scroll-activo', hayOverflow);

      if (!hayOverflow) {
        botonAnteriorHistorial.disabled = true;
        botonSiguienteHistorial.disabled = true;
        return;
      }

      botonAnteriorHistorial.disabled = scrollLeft <= 8;
      botonSiguienteHistorial.disabled = scrollLeft >= (maxScrollLeft - 8);
    }

    function aplicarScrollHistorial() {
      requestAnimationFrame(() => {
        actualizarPaddingCarrusel();
        actualizarEstadoFlechas();
      });
    }

    function desplazarHistorial(direccion) {
      const pasoCalculado = obtenerPasoScroll() || historialContenedor.clientWidth;
      const paso = pasoCalculado > 0 ? pasoCalculado : historialContenedor.clientWidth;
      historialContenedor.scrollBy({ left: paso * direccion, behavior: 'smooth' });
    }

    botonAnteriorHistorial?.addEventListener('click', () => desplazarHistorial(-1));
    botonSiguienteHistorial?.addEventListener('click', () => desplazarHistorial(1));
    historialContenedor.addEventListener('scroll', () => requestAnimationFrame(actualizarEstadoFlechas), { passive: true });
    window.addEventListener('resize', aplicarScrollHistorial);
    actualizarPaddingCarrusel();
    actualizarEstadoFlechas();

    async function actualizarHistorial() {
      try {
        const response = await fetch('obtener_pedidos_cliente.php');
        const pedidos = await response.json();

        let htmlPedidos = '';

        if (!Array.isArray(pedidos) || pedidos.length === 0) {
          pedidosActuales = [];
          htmlPedidos = `
            <div class="col-12">
              <div class="alert alert-info text-center">Aún no realizaste ningún pedido.</div>
            </div>`;

        } else {
          const pedidosOrdenados = [...pedidos].sort((a, b) => new Date(a.fecha) - new Date(b.fecha));
          const pedidosConNumeros = pedidosOrdenados.map((pedido, idx) => ({
            ...pedido,
            numeroHistorial: idx + 1
          }));

          const pedidosParaMostrar = [...pedidosConNumeros].sort((a, b) => new Date(b.fecha) - new Date(a.fecha));
          pedidosActuales = pedidosParaMostrar;

          htmlPedidos = pedidosParaMostrar.map((pedido, index) => {
            const pedidoNumero = pedido.numeroHistorial ?? (pedidosParaMostrar.length - index);
            const metodoPagoCard = escapeHtml(obtenerMetodoPago(pedido));

            return `
              <div class="pedido-item">
                <div class="pedido-card glass-card card h-100 p-3 shadow-sm text-center" data-index="${index}" style="border-radius: 15px;">
                  <i class="fas fa-receipt fa-2x mb-3 text-warning"></i>
                   <h5 class="card-title">Pedido #${pedidoNumero}</h5>
                  <p><strong>Fecha:</strong><br>${new Date(pedido.fecha).toLocaleDateString()}</p>
                  <p><strong>Total:</strong> $${Number(pedido.total).toFixed(2)}</p>
                  <p><strong>Pago:</strong> ${metodoPagoCard}</p>

                  <button class="btn btn-outline-warning btn-sm mt-2 ver-detalle-btn" data-index="${index}">Ver detalle</button>
                </div>

              </div>
              `;
          }).join('');
        }

        historialContenedor.innerHTML = htmlPedidos;
        requestAnimationFrame(() => {
          actualizarPaddingCarrusel();
          actualizarEstadoFlechas();
        });
        aplicarScrollHistorial();

        // Actualizar contenido del modal si está abierto
        const modalEl = document.getElementById('modalDetallePedido');
        if (modalEl.classList.contains('show')) {
          const pedido = pedidosActuales[idx];
          if (pedido) {
            const modalBody = modalEl.querySelector('.modal-body');
            modalBody.innerHTML = crearHtmlDetalle(pedido);
          }
          modalBody.innerHTML = crearHtmlDetalle(pedido);
        }

      } catch (e) {
        console.error('Error al actualizar historial:', e);
      }
    }

    historialContenedor.addEventListener('click', (e) => {
      const btn = e.target.closest('.ver-detalle-btn');
      if (!btn) return;

      const idx = parseInt(btn.getAttribute('data-index'), 10);
      const pedido = pedidosActuales[idx];
      if (!pedido) return;

      const modalEl = document.getElementById('modalDetallePedido');
      const modalBody = document.querySelector('#modalDetallePedido .modal-body');
      const modalTitle = document.querySelector('#modalDetallePedidoLabel');
      const pedidoNumero = pedido.numeroHistorial ?? (pedidosActuales.length - idx);

      modalTitle.textContent = `Detalle del Pedido #${pedidoNumero}`;
      modalBody.innerHTML = crearHtmlDetalle(pedido);

      const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
      modalEl.setAttribute('data-index', idx);
      modal.show();
    });

    document.addEventListener('DOMContentLoaded', actualizarHistorial);
    setInterval(actualizarHistorial, 10000);
  </script>


  <?php include __DIR__ . '/../../componentes/whatsapp_button.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

  <?php include __DIR__ . '/../../componentes/carrito_button.php'; ?>

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