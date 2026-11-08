<?php
/**
 * Panel de delivery: pedidos en camino. Las tarjetas las arma assets/js/pages/delivery.js con los
 * pedidos que vienen con la página y se actualizan solas cada 4 segundos.
 *
 * @var array<int, array<string, mixed>> $pedidos  lo que devuelve PedidosPanelController::delivery()
 * @var string $url_base
 */
?>
<link rel="stylesheet" href="<?= piccolo_e($url_base) ?>assets/css/delivery.css">

<div class="container mt-4">
  <h3 class="page-title mb-4">Pedidos a entregar</h3>

  <div id="alerta-sin-pedidos" class="alert alert-info<?= $pedidos ? ' d-none' : '' ?>">
    No hay pedidos para entregar por el momento.
  </div>

  <div id="lista-pedidos" data-localidad="<?= piccolo_e(piccolo_negocio()['direccion']['localidad_completa']) ?>"></div>
</div>

<script id="datosDelivery" type="application/json"><?= json_encode(array_reverse($pedidos), JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?></script>
<script src="<?= piccolo_e($url_base) ?>assets/js/pages/delivery.js"></script>
