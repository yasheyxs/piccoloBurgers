<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Confirmar Pedido - Piccolo Burgers</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <link rel="icon" href="../client/img/favicon.png" type="image/x-icon" />
  <link rel="stylesheet" href="../client/assets/css/confirmar_pedido.css">
</head>

<body>
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
      <a class="navbar-brand" href="../index.php"><i class="fas fa-utensils"></i> PICCOLO BURGERS</a>
      <a class="btn btn-gold ms-auto" href="../client/carrito.php"><i class="fas fa-chevron-left"></i> Volver</a>
    </div>
  </nav>

  <div class="container mt-5">
    <h2 class="mb-4 text-center">Confirmar Pedido</h2>

    <form id="form-pedido" method="post">
      <div class="mb-3">
        <label for="nombre" class="form-label">Nombre completo:</label>
        <input type="text" class="form-control" id="nombre" name="nombre" required
 value="<?php echo htmlspecialchars($cliente['nombre'] ?? '', ENT_QUOTES); ?>">

      </div>
      <div class="mb-3">
        <label for="telefono" class="form-label">Teléfono (obligatorio):</label>
        <input type="text" class="form-control" id="telefono" name="telefono" required
 value="<?php echo htmlspecialchars($cliente['telefono'] ?? '', ENT_QUOTES); ?>">

      </div>
      <div class="mb-3">
        <label for="email" class="form-label">Email (opcional):</label>
        <input type="email" class="form-control" id="email" name="email"
 value="<?php echo htmlspecialchars($cliente['email'] ?? '', ENT_QUOTES); ?>">

      </div>
      <div class="mb-3">
        <label for="nota" class="form-label">Nota para el pedido:</label>
        <textarea class="form-control" id="nota" name="nota" rows="3"></textarea>
      </div>

      <div class="mb-4">
        <label class="form-label d-block mb-3 fw-bold fs-5">Método de pago:</label>

        <input type="radio" class="form-check-input" id="pago_efectivo" name="metodo_pago" value="Efectivo" required>
        <label class="radio-card" for="pago_efectivo">💵 Efectivo</label>

        <input type="radio" class="form-check-input" id="pago_tarjeta" name="metodo_pago" value="Tarjeta">
        <label class="radio-card" for="pago_tarjeta">💳 Tarjeta</label>

        <input type="radio" class="form-check-input" id="pago_mp" name="metodo_pago" value="MercadoPago">
        <label class="radio-card" for="pago_mp">📱 Mercado Pago</label>
      </div>

      <div class="mb-4">
        <label class="form-label d-block mb-3 fw-bold fs-5">Tipo de entrega:</label>

        <input type="radio" class="form-check-input" id="entrega_retiro" name="tipo_entrega" value="Retiro" required>
        <label class="radio-card" for="entrega_retiro">🏪 Retiro en el local</label>

        <input type="radio" class="form-check-input" id="entrega_delivery" name="tipo_entrega" value="Delivery">
        <label class="radio-card" for="entrega_delivery">🏍️ Delivery</label>

        <div class="alert alert-warning mt-3" id="aviso-delivery" style="display: none; font-size: 1rem;">
          🚨 El servicio de delivery tiene un costo adicional de entre <strong>$1000</strong> y <strong>$1500</strong>, dependiendo de la zona.
        </div>
      </div>

      <div class="mb-3" id="grupo-direccion" style="display: none;">
        <label for="direccion" class="form-label">Dirección:</label>
        <input type="text" class="form-control" id="direccion" name="direccion">
      </div>


      <input type="hidden" name="carrito" id="carrito">
      <input type="hidden" name="usar_puntos" id="usar_puntos" value="0">
      <button type="submit" class="btn btn-gold w-100">Enviar Pedido</button>
    </form>

    <div id="mensaje" class="mt-4 text-center"></div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../client/assets/js/confirmar_pedido.js"></script>

  <?php include __DIR__ . '/../../componentes/whatsapp_button.php'; ?>

</body>

</html>
