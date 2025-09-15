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

  <link rel="icon" href="../client/img/favicon.png" type="image/x-icon" />
  <link rel="stylesheet" href="../client/assets/css/perfil_cliente.css">
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
          <li class="nav-item"><a class="nav-link" href="../index.php">Inicio</a></li>
          <li class="nav-item"><a class="nav-link" href="../index.php#menu">Menú</a></li>
          
          <li class="nav-item"><a class="nav-link" href="../index.php#nosotros">Nosotros</a></li>
          <li class="nav-item"><a class="nav-link" href="../index.php#testimonios">Testimonio</a></li>

          <!-- Dropdown compacto -->
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="extrasDropdown" role="button" data-bs-toggle="dropdown">
              Más
            </a>
            <ul class="dropdown-menu dropdown-menu-dark">
              <li><a class="dropdown-item" href="../index.php#puntos">Puntos</a></li>
              <li><a class="dropdown-item" href="../index.php#ubicacion">Ubicación</a></li>
              <li><a class="dropdown-item" href="../index.php#contacto">Contacto</a></li>

            </ul>
          </li>

        <!-- Carrito -->
        <li class="nav-item">
          <a class="nav-link position-relative" href="../client/carrito.php">
            <i class="fas fa-shopping-cart"></i>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="contador-carrito" style="font-size: 0.7rem;">
              0
            </span>
          </a>
        </li>

          <!-- Sesión -->
          <?php if (isset($_SESSION["cliente"])): ?>
            <li class="nav-item">
              <a href="../client/perfil_cliente.php" class="nav-link" title="<?= htmlspecialchars($_SESSION["cliente"]["nombre"]) ?>">
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
              <a href="../client/login_cliente.php" class="btn btn-outline-light ms-2">Iniciar sesión</a>
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
  </div><!-- Modal Editar Cliente -->
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
value="<?= isset($cliente['telefono']) ? preg_replace('/^\+\d+/', '', $cliente['telefono']) : '' ?>" required placeholder="Ej: 3511234567"
      oninput="this.value = this.value.replace(/[^0-9]/g, '');" inputmode="numeric" pattern="[0-9]*">  </div>
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
</div>

  <?php include __DIR__ . '/../../componentes/whatsapp_button.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

  <?php include __DIR__ . '/../../componentes/carrito_button.php'; ?>

  <script>
    window.perfilClienteData = {
      errores: <?= json_encode($errores) ?>,
      mensajeError: <?= json_encode($mensaje_error) ?>,
      mensajeExito: <?= json_encode($mensaje_exito) ?>
    };
  </script>
  <script src="../client/assets/js/perfil_cliente.js"></script>

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
          <a href="../client/logout_cliente.php" class="btn btn-danger px-4">
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

  <?php endif; ?></body>

</html>

