<!doctype html>
<html lang="es">

<head>
  <?php
  $pageTitle = 'Carrito - Piccolo Burgers';
  $extraCss = [
    'assets/css/carrito.css',
  ];
  include __DIR__ . '/partials/head.php';
  ?>
</head>

<body>
  <?php
  $navBasePath = './index.php';
  $navHomeLink = './index.php';
  include __DIR__ . '/partials/navbar.php';
  ?>

  <main>
    <div class="container contenido-ajustado">
      <div class="container">
        <h2 class="mb-4 text-center">🛒 Tu Carrito</h2>
        <div id="carrito-contenido" class="row row-cols-2 row-cols-md-4 g-4"></div>
        <div id="btnAgregarMas" class="mt-4"></div>

      <div class="d-flex justify-content-center mt-4">
          <a href="./index.php#menu" class="btn btn-gold-circle" title="Agregar más">
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
            <form id="formPedido" action="confirmar_pedido.php" method="post" class="m-0">
              <input type="hidden" name="carrito" id="carritoInput">
              <input type="hidden" name="usar_puntos" id="usarPuntosInput" value="0">
              <button type="submit" class="btn btn-gold" id="btnFinalizar">🧾 Finalizar Pedido</button>
            </form>
            <button class="btn btn-outline-danger-rounded" id="btnCancelar">Cancelar Pedido</button>
          </div>
        </div>
      </div>
    </div>
        </main>

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

  <?php include("../componentes/whatsapp_button.php"); ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="assets/js/carrito.js"></script>
</body>

</html>