<?php
/**
 * Detalle de un cliente: datos, actividad, pedidos y movimientos de puntos.
 *
 * @var array<string, mixed> $cliente  lo que devuelve ClientesController::detalle()
 * @var array{tipo: string, mensaje: string}|null $aviso
 * @var string $url_base
 */
$fecha = static fn(?string $valor): string => $valor ? date('d/m/Y H:i', strtotime($valor)) : '—';
$pesos = static fn(float $monto): string => '$' . number_format($monto, 2, ',', '.');
$puntos = static fn(int $valor): string => number_format($valor, 0, ',', '.');
$mayor = $cliente['pedido_mayor'];
$canjes = array_values(array_filter($cliente['movimientos'], static fn(array $m): bool => $m['tipo'] === 'canje'));
?>
<br>
<?php include __DIR__ . '/../_aviso.php'; ?>

<div class="card mb-4">
  <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
    <h5 class="mb-0"><?= piccolo_e($cliente['nombre']) ?></h5>
    <div class="d-flex gap-2 flex-wrap">
      <a href="clientes_canje.php?id=<?= $cliente['id'] ?>" class="btn btn-outline-success btn-sm"><i class="fa-solid fa-gift me-1" aria-hidden="true"></i>Canjear puntos</a>
      <a href="clientes_editar.php?id=<?= $cliente['id'] ?>" class="btn btn-outline-primary btn-sm"><i class="fa-solid fa-pen me-1" aria-hidden="true"></i>Editar</a>
      <a href="clientes.php" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-arrow-left me-1" aria-hidden="true"></i>Volver al listado</a>
    </div>
  </div>
  <div class="card-body">
    <div class="row g-4">
      <div class="col-md-6">
        <div class="border rounded p-3 h-100">
          <h6 class="text-uppercase text-muted">Datos</h6>
          <dl class="row mb-0">
            <dt class="col-sm-4">Email</dt>
            <dd class="col-sm-8"><?= piccolo_e($cliente['email'] ?? '—') ?></dd>
            <dt class="col-sm-4">Teléfono</dt>
            <dd class="col-sm-8"><?= piccolo_e($cliente['telefono']) ?></dd>
            <dt class="col-sm-4">Registro</dt>
            <dd class="col-sm-8"><?= $fecha($cliente['registrado']) ?></dd>
          </dl>
        </div>
      </div>
      <div class="col-md-6">
        <div class="border rounded p-3 h-100">
          <h6 class="text-uppercase text-muted">Actividad</h6>
          <dl class="row mb-0">
            <dt class="col-sm-5">Pedidos</dt>
            <dd class="col-sm-7"><?= $cliente['cantidad_pedidos'] ?></dd>
            <dt class="col-sm-5">Último pedido</dt>
            <dd class="col-sm-7"><?= $cliente['ultimo_pedido'] ? $fecha($cliente['ultimo_pedido']) : '<span class="text-muted">Sin pedidos</span>' ?></dd>
            <dt class="col-sm-5">Pedido más grande</dt>
            <dd class="col-sm-7">
              <?php if ($mayor): ?>
                <span class="fw-semibold"><?= $pesos($mayor['total']) ?></span>
                <small class="d-block text-muted">#<?= $mayor['id'] ?> · <?= $fecha($mayor['fecha']) ?></small>
              <?php else: ?>
                <span class="text-muted">Sin pedidos</span>
              <?php endif; ?>
            </dd>
            <dt class="col-sm-5">Puntos</dt>
            <dd class="col-sm-7 fw-semibold"><?= $puntos($cliente['puntos']) ?></dd>
          </dl>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="card mb-4">
  <div class="card-header"><h6 class="mb-0">Pedidos</h6></div>
  <div class="card-body">
    <div class="table-responsive">
      <table id="tablaHistorialPedidos" class="table table-sm table-hover table-bordered align-middle w-100">
        <thead class="table-light">
          <tr><th>#</th><th>Fecha</th><th>Total</th><th>Estado</th><th>Método de pago</th><th>Entrega</th></tr>
        </thead>
        <tbody>
          <?php foreach ($cliente['pedidos'] as $pedido): ?>
            <tr>
              <td><?= $pedido['id'] ?></td>
              <td data-order="<?= piccolo_e($pedido['fecha']) ?>"><?= $fecha($pedido['fecha']) ?></td>
              <td data-order="<?= $pedido['total'] ?>"><?= $pesos($pedido['total']) ?></td>
              <td><?= piccolo_e($pedido['estado']) ?></td>
              <td><?= piccolo_e(ucfirst($pedido['metodo_pago'])) ?></td>
              <td><?= piccolo_e(ucfirst($pedido['tipo_entrega'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="card mb-4">
  <div class="card-header"><h6 class="mb-0">Movimientos de puntos</h6></div>
  <div class="card-body">
    <div class="table-responsive">
      <table id="tablaHistorialPuntos" class="table table-sm table-hover table-bordered align-middle w-100">
        <thead class="table-light">
          <tr><th>Fecha</th><th>Tipo</th><th>Descripción</th><th>Puntos</th><th>Saldo</th></tr>
        </thead>
        <tbody>
          <?php foreach ($cliente['movimientos'] as $movimiento): ?>
            <tr>
              <td data-order="<?= piccolo_e($movimiento['fecha'] ?? '') ?>"><?= $fecha($movimiento['fecha']) ?></td>
              <td><?= piccolo_e(ucfirst($movimiento['tipo'])) ?></td>
              <td><?= piccolo_e($movimiento['descripcion']) ?></td>
              <td class="fw-semibold <?= $movimiento['puntos'] >= 0 ? 'text-success' : 'text-danger' ?>"><?= $movimiento['puntos'] >= 0 ? '+' : '' ?><?= $puntos($movimiento['puntos']) ?></td>
              <td><?= $puntos($movimiento['saldo']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header"><h6 class="mb-0">Premios canjeados</h6></div>
  <div class="card-body">
    <?php if (!$canjes): ?>
      <p class="text-center text-muted my-2">Todavía no canjeó premios.</p>
    <?php else: ?>
      <div class="table-responsive">
        <table id="tablaHistorialCanjes" class="table table-sm table-hover table-bordered align-middle w-100">
          <thead class="table-light">
            <tr><th>Fecha</th><th>Detalle</th><th>Puntos usados</th><th>Saldo</th></tr>
          </thead>
          <tbody>
            <?php foreach ($canjes as $canje): ?>
              <tr>
                <td data-order="<?= piccolo_e($canje['fecha'] ?? '') ?>"><?= $fecha($canje['fecha']) ?></td>
                <td><?= piccolo_e($canje['descripcion']) ?></td>
                <td class="fw-semibold text-danger"><?= $puntos(abs($canje['puntos'])) ?> pts</td>
                <td><?= $puntos($canje['saldo']) ?> pts</td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<script src="<?= piccolo_e($url_base) ?>assets/js/pages/clientes.js"></script>
