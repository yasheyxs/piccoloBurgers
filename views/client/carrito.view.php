<!doctype html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Carrito - Piccolo Burgers</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <link rel="icon" href="../client/img/favicon.png" type="image/x-icon" />
  <link rel="stylesheet" href="../client/assets/css/carrito.css">
</head>

<body>
  <nav class="navbar navbar-expand-lg navbar-dark sticky-navbar bg-dark">
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
          <li class="nav-item"><a class="nav-link" href="../index.php#testimonio">Testimonio</a></li>

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

          <li class="nav-item">
            <a class="nav-link position-relative" href="../client/carrito.php">
              <i class="fas fa-shopping-cart"></i>
              <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="contador-carrito" style="font-size: 0.7rem;">
                0
              </span>
            </a>
          </li>

          <?php if (isset($_SESSION["cliente"])): ?>
            <li class="nav-item">
              <a href="../client/perfil_cliente.php" class="nav-link" title="<?= htmlspecialchars($_SESSION["cliente"]["nombre"]) ?>">
                <i class="fas fa-user-circle"></i>
              </a>
            </li>
            <li class="nav-item">
              <a href="#" class="nav-link" title="Cerrar sesión" data-bs-toggle="modal" data-bs-target="#logoutModal">
                <i class="fas fa-sign-out-alt"></i>
              </a>

            </li>
          <?php else: ?>
            <li class="nav-item">
              <a href="../client/login_cliente.php" class="btn btn-gold rounded-pill px-4 py-2 ms-2">
                Iniciar sesión / Registrarse
              </a>
            </li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </nav>

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
          <a href="./client/logout_cliente.php" class="btn btn-danger px-4">
            <i class="fas fa-door-open me-1"></i> Cerrar Sesión
          </a>
        </div>
      </div>
    </div>
  </div>

  <div class="container contenido-ajustado">
    <div class="container">
      <h2 class="mb-4 text-center">🛒 Tu Carrito</h2>
      <div id="carrito-contenido" class="row row-cols-2 row-cols-md-4 g-4"></div>
      <div id="btnAgregarMas" class="mt-4"></div>

      <div class="d-flex justify-content-center mt-4">
        <a href="../index.php#menu" class="btn btn-gold-circle" title="Agregar más">
          <i class="fas fa-plus"></i>
        </a>
      </div>

      <div class="text-end mt-4">
        <h4>Total: $<span id="total">0.00</span></h4>

        <?php if (isset($_SESSION["cliente"])): ?>
          <div class="form-check mt-3 d-flex justify-content-end align-items-center gap-2">
            <input class="form-check-input mt-0" type="checkbox" id="usarPuntos">
            <label class="form-check-label mb-0" for="usarPuntos">
              Usar puntos (<?= htmlspecialchars((string) $puntosCliente, ENT_QUOTES, 'UTF-8') ?> disponibles)
            </label>
            <input type="hidden" id="puntosDisponibles" value="<?= htmlspecialchars((string) $puntosCliente, ENT_QUOTES, 'UTF-8') ?>">
          </div>

        <?php endif; ?>
        <div class="d-flex justify-content-end gap-3 mt-4 flex-wrap">
          <form id="formPedido" action="../client/confirmar_pedido.php" method="post" class="m-0">
            <input type="hidden" name="carrito" id="carritoInput">
            <input type="hidden" name="usar_puntos" id="usarPuntosInput" value="0">
            <button type="submit" class="btn btn-gold" id="btnFinalizar">🧾 Finalizar Pedido</button>
          </form>
          <button class="btn btn-outline-danger-rounded" id="btnCancelar">Cancelar Pedido</button>
        </div>
      </div>
    </div>
  </div>

  <footer class="bg-dark text-light text-center py-3 mt-5">
    <p>&copy; 2025 Piccolo Burgers — Developed by: <strong>Jazmin Abigail Gaido - Mariano Jesús Ceballos - Juan Pablo Medina</strong></p>
  </footer>

  <div class="modal fade" id="modalCancelarPedido" tabindex="-1" aria-labelledby="modalCancelarPedidoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content border-0 shadow">
        <div class="modal-header bg-danger text-white">
          <h5 class="modal-title" id="modalCancelarPedidoLabel">¿Cancelar pedido?</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <p class="mb-3">Estás por cancelar tu pedido. Este es el detalle actual:</p>
          <div id="detallePedidoModal" class="mb-3"></div>
          <div class="text-end">
            <button class="btn btn-secondary me-2" data-bs-dismiss="modal">Conservar</button>
            <button class="btn btn-danger" id="btnConfirmarCancelacion">Cancelar</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <?php include __DIR__ . '/../../componentes/whatsapp_button.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../client/assets/js/carrito.js"></script>
</body>

</html>