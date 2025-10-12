<!doctype html>
<html lang="es">

<head>
  <?php
  $pageTitle = 'Confirmar Pedido - Piccolo Burgers';
  $extraCss = [
    'assets/css/confirmar_pedido.css',
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

  <?php
$codigosPais = [
    '54' => ['etiqueta' => '🇦🇷 +54', 'longitudes' => [10]],
    '598' => ['etiqueta' => '🇺🇾 +598', 'longitudes' => [8, 9]],
    '55' => ['etiqueta' => '🇧🇷 +55', 'longitudes' => [10, 11]],
    '56' => ['etiqueta' => '🇨🇱 +56', 'longitudes' => [9]],
    '595' => ['etiqueta' => '🇵🇾 +595', 'longitudes' => [9]],
    '591' => ['etiqueta' => '🇧🇴 +591', 'longitudes' => [8]],
    '51' => ['etiqueta' => '🇵🇪 +51', 'longitudes' => [9]],
    '1' => ['etiqueta' => '🇺🇸 +1', 'longitudes' => [10]],
    '34' => ['etiqueta' => '🇪🇸 +34', 'longitudes' => [9]],
  ];

  $telefonoCodigo = '54';
  $telefonoNumero = '';
  $telefonoRaw = $cliente['telefono'] ?? '';

  if (is_string($telefonoRaw) && $telefonoRaw !== '') {
    $telefonoCompacto = preg_replace('/[\s()-]/', '', $telefonoRaw);
    $telefonoCompacto = ltrim($telefonoCompacto, '+');
    if (strpos($telefonoCompacto, '00') === 0) {
      $telefonoCompacto = substr($telefonoCompacto, 2);
    }
  
    $telefonoDigitos = preg_replace('/\D/', '', $telefonoCompacto);

    $codigosOrdenados = array_keys($codigosPais);
    usort($codigosOrdenados, fn($a, $b) => strlen($b) <=> strlen($a));

    foreach ($codigosOrdenados as $codigo) {
      $longitudesPermitidas = $codigosPais[$codigo]['longitudes'];
      if (strpos($telefonoDigitos, $codigo) === 0) {
        $numeroPosible = substr($telefonoDigitos, strlen($codigo));
        if (in_array(strlen($numeroPosible), $longitudesPermitidas, true)) {
          $telefonoCodigo = $codigo;
          $telefonoNumero = $numeroPosible;
          break;
        }
      }
    }

  if ($telefonoNumero === '' && preg_match('/^\d{6,}$/', $telefonoDigitos)) {
      $telefonoNumero = $telefonoDigitos;
    }

    if (!isset($codigosPais[$telefonoCodigo])) {
      $telefonoCodigo = '54';
    }
  }
  ?>

  <div class="container mt-5">
    <div class="d-flex justify-content-end mb-3">
      <a class="btn btn-gold" href="carrito.php"><i class="fas fa-chevron-left"></i> Volver</a>
    </div>
    <h2 class="mb-4 text-center">Confirmar Pedido</h2>

    <form id="form-pedido" method="post">
      <div class="mb-3">
        <label for="nombre" class="form-label">Nombre completo:</label>
        <input type="text" class="form-control" id="nombre" name="nombre" required
          value="<?= htmlspecialchars($cliente['nombre'] ?? '', ENT_QUOTES); ?>">
      </div>
      <div class="mb-3">
        <label for="telefono" class="form-label">Teléfono (obligatorio):</label>
        <div class="telefono-group">
          <select class="form-control" id="codigo_pais" name="codigo_pais" required>
            <?php foreach ($codigosPais as $codigo => $info): ?>
              <option value="<?= htmlspecialchars($codigo, ENT_QUOTES); ?>" <?= $codigo === $telefonoCodigo ? 'selected' : ''; ?>>
                <?= htmlspecialchars($info['etiqueta'], ENT_QUOTES); ?>
              </option>
            <?php endforeach; ?>
          </select>
          <input type="tel" class="form-control" id="telefono" name="telefono" required inputmode="numeric"
            placeholder="Ej: 3511234567" value="<?= htmlspecialchars($telefonoNumero, ENT_QUOTES); ?>">
        </div>
        <div class="invalid-feedback" id="telefono-feedback">Ingresá un número de teléfono válido.</div>
      </div>
      <div class="mb-3">
        <label for="email" class="form-label">Email:</label>
        <input type="email" class="form-control" id="email" name="email"
          value="<?= htmlspecialchars($cliente['email'] ?? '', ENT_QUOTES); ?>">
      </div>
      <div class="mb-3">
        <label for="nota" class="form-label">Nota para el pedido:</label>
        <textarea class="form-control" id="nota" name="nota" rows="3"></textarea>
      </div>

      <div class="mb-4">
        <label class="form-label d-block mb-3 fw-bold fs-5">Método de pago:</label>

<input type="radio" class="form-check-input" id="pago_efectivo" name="metodo_pago" value="Efectivo" required checked>        <label class="radio-card" for="pago_efectivo">💵 Efectivo</label>

        <input type="radio" class="form-check-input" id="pago_tarjeta" name="metodo_pago" value="Tarjeta">
        <label class="radio-card" for="pago_tarjeta">💳 Tarjeta</label>

        <input type="radio" class="form-check-input" id="pago_mp" name="metodo_pago" value="MercadoPago">
        <label class="radio-card" for="pago_mp">📱 Mercado Pago</label>
      </div>

      <div class="mb-4">
        <label class="form-label d-block mb-3 fw-bold fs-5">Tipo de entrega:</label>

        <input type="radio" class="form-check-input" id="entrega_retiro" name="tipo_entrega" value="Retiro" required checked
          onchange="mostrarDireccion(this.value)">
        <label class="radio-card" for="entrega_retiro">🏪 Retiro en el local</label>

        <input type="radio" class="form-check-input" id="entrega_delivery" name="tipo_entrega" value="Delivery"
          onchange="mostrarDireccion(this.value)">
        <label class="radio-card" for="entrega_delivery">🏍️ Delivery</label>

        <div class="alert alert-warning mt-3" id="aviso-delivery" style="display: none; font-size: 1rem;">
          🚨 El servicio de delivery tiene un costo adicional de entre <strong>$1000</strong> y <strong>$1500</strong>,
          dependiendo de la zona.
        </div>
      </div>

      <div class="mb-3" id="grupo-direccion" style="display: none;">
        <label for="direccion" class="form-label">Dirección:</label>
        <input type="text" class="form-control" id="direccion" name="direccion">
      </div>

      <div class="mb-3" id="grupo-referencias" style="display: none;">
        <label for="referencias" class="form-label">Referencias (opcional):</label>
        <input type="text" class="form-control" id="referencias" name="referencias" maxlength="255"
          placeholder="Frente al parque, puerta negra, piso 2" value="<?= htmlspecialchars($cliente['referencias'] ?? '', ENT_QUOTES); ?>">
      </div>

      <input type="hidden" name="carrito" id="carrito">
      <input type="hidden" name="usar_puntos" id="usar_puntos" value="0">
      <button type="submit" class="btn btn-gold w-100">Enviar Pedido</button>
    </form>

    <div id="mensaje" class="mt-4 text-center"></div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="assets/js/confirmar_pedido.js"></script>
  <?php include __DIR__ . '/../componentes/whatsapp_button.php'; ?>
</body>

</html>
