<?php
/**
 * Panel de cocina: pedidos en preparación y en camino, del más viejo al más nuevo.
 * Lo maneja assets/js/pages/cocina.js, que cambia estado y pago por actualizar_estado.php.
 *
 * @var array<int, array<string, mixed>> $pedidos  lo que devuelve PedidosPanelController::cocina()
 * @var string $url_base
 */
?>
<style>
  tr.en-camino { background-color: #f4e3b6 !important; }
  body.admin-dark tr.en-camino { background-color: rgba(191, 148, 57, 0.28) !important; }
  .camion-icono { font-size: 1.2rem; margin-right: 4px; display: inline-block; }
</style>

<br>
<div class="card">
  <div class="card-header bg-dark text-white">
    <h4 class="mb-0">Panel de cocina · pedidos en preparación</h4>
  </div>
  <div class="card-body">
    <?php if (!$pedidos): ?>
      <div class="alert alert-info">No hay pedidos en preparación por el momento.</div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-bordered table-hover table-sm align-middle text-center w-100" id="tabla-pedidos">
          <thead class="table-light">
            <tr><th>Cliente</th><th>Entrega</th><th>Pago</th><th>¿Está pago?</th><th>Dirección</th><th>Productos</th><th>Total</th><th>Nota</th><th>Acciones</th></tr>
          </thead>
          <tbody>
            <?php foreach ($pedidos as $pedido): ?>
              <?php
              $enCamino = $pedido['estado'] === 'En camino';
              $esDelivery = $pedido['tipo_entrega'] === 'Delivery';
              ?>
              <tr data-pedido-id="<?= $pedido['id'] ?>" class="<?= $enCamino ? 'en-camino' : '' ?>">
                <td><?= piccolo_e($pedido['nombre']) ?></td>
                <td><?= piccolo_e($pedido['tipo_entrega']) ?></td>
                <td><?= $pedido['metodo_pago'] !== '' ? piccolo_e($pedido['metodo_pago']) : '-' ?></td>
                <td>
                  <select class="form-select form-select-sm estado-pago" data-id="<?= $pedido['id'] ?>" aria-label="¿Está pago?">
                    <option value="No" <?= $pedido['pagado'] ? '' : 'selected' ?>>No</option>
                    <option value="Si" <?= $pedido['pagado'] ? 'selected' : '' ?>>Sí</option>
                  </select>
                </td>
                <td><?= $esDelivery && $pedido['direccion'] ? piccolo_e($pedido['direccion']) : '-' ?></td>
                <td>
                  <ul class="list-unstyled mb-0">
                    <?php foreach ($pedido['productos'] as $producto): ?>
                      <li><?= (int) $producto['cantidad'] ?> x <?= piccolo_e($producto['nombre']) ?></li>
                    <?php endforeach; ?>
                  </ul>
                </td>
                <td>$<?= number_format($pedido['total'], 2, ',', '.') ?></td>
                <td><?= $pedido['nota'] !== null ? piccolo_e($pedido['nota']) : '-' ?></td>
                <td>
                  <div class="d-flex flex-wrap justify-content-center gap-1 align-items-center">
                    <?php if ($enCamino && $esDelivery): ?>
                      <span class="camion-icono" title="En camino"><i class="fa-solid fa-truck" aria-hidden="true"></i></span>
                    <?php else: ?>
                      <button class="btn btn-success btn-sm btn-estado" data-estado="Listo" data-id="<?= $pedido['id'] ?>">Listo</button>
                    <?php endif; ?>
                    <?php if (!$enCamino && $esDelivery): ?>
                      <button class="btn btn-warning btn-sm btn-estado" data-estado="En camino" data-id="<?= $pedido['id'] ?>">En camino</button>
                    <?php endif; ?>
                    <button class="btn btn-danger btn-sm btn-estado" data-estado="Cancelado" data-id="<?= $pedido['id'] ?>">Cancelar</button>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<script src="<?= piccolo_e($url_base) ?>assets/js/pages/cocina.js"></script>
