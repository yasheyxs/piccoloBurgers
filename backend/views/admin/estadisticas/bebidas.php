<?php
/**
 * Estadísticas de bebidas en un período: resumen, jornada elegida, gráficos y tabla de jornadas.
 *
 * @var array<string, mixed> $datos  lo que devuelve EstadisticasController::bebidas()
 * @var array{tipo: string, mensaje: string}|null $aviso
 * @var string $url_base
 */
$pesos = static fn(float $monto): string => '$' . number_format($monto, 2, ',', '.');
$fecha = static fn(string $valor): string => date('d/m/Y H:i', strtotime($valor));
$tipo = static fn(string $t): string => $t === 'promo' ? 'Promoción' : 'Jarra';
$resumen = $datos['resumen'];
$sel = $datos['seleccionada'];
$tarjetas = [
    ['Jornadas', $resumen['jornadas'], 'fa-calendar-check', 'primary'],
    ['Jarras vendidas', $resumen['vendidas'], 'fa-glass-water', 'success'],
    ['Pendientes de entregar', $resumen['pendientes'], 'fa-hourglass-half', 'warning'],
    ['Total vendido', $pesos($resumen['monto']), 'fa-sack-dollar', 'success'],
];
?>
<div class="mt-4 mb-5">
  <h2 class="mb-4 text-center"><i class="fa-solid fa-chart-column" aria-hidden="true"></i> Estadísticas de bebidas</h2>

  <?php include __DIR__ . '/../_aviso.php'; ?>
  <?php include __DIR__ . '/_filtro.php'; ?>

  <div class="row g-3 mb-4">
    <?php foreach ($tarjetas as [$titulo, $valor, $icono, $color]): ?>
      <div class="col-12 col-md-3">
        <div class="card shadow border-0 h-100 text-center">
          <div class="card-body">
            <i class="fa-solid <?= $icono ?> fa-2x text-<?= $color ?> mb-2" aria-hidden="true"></i>
            <h5 class="card-title"><?= piccolo_e($titulo) ?></h5>
            <p class="fs-4 fw-bold text-<?= $color ?> mb-0"><?= piccolo_e($valor) ?></p>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <?php if ($sel): ?>
    <div class="card shadow border-0 mb-4">
      <div class="card-header"><i class="fa-solid fa-clipboard-list me-1" aria-hidden="true"></i> Jornada #<?= $sel['id'] ?></div>
      <div class="card-body">
        <div class="row g-3 mb-3">
          <div class="col-md-3"><strong>Apertura</strong><br><?= $fecha($sel['apertura']) ?></div>
          <div class="col-md-2"><strong>Estado</strong><br><?= piccolo_e(ucfirst($sel['estado'])) ?></div>
          <div class="col-md-2"><strong>Vendidas</strong><br><?= $sel['vendidas'] ?></div>
          <div class="col-md-2"><strong>Pendientes</strong><br><?= $sel['pendientes'] ?></div>
          <div class="col-md-3"><strong>Total vendido</strong><br><?= $pesos($sel['monto']) ?></div>
        </div>
        <div class="table-responsive">
          <table class="table table-sm table-bordered align-middle mb-0" data-no-datatable>
            <thead><tr><th>Bebida</th><th>Tipo</th><th>Jarras vendidas</th><th>Entregadas</th><th>Pendientes</th><th>Total</th></tr></thead>
            <tbody>
              <?php foreach ($sel['detalle'] as $fila): ?>
                <tr>
                  <td><?= piccolo_e($fila['bebida']) ?></td>
                  <td><?= $tipo($fila['tipo']) ?></td>
                  <td><?= $fila['vendidas'] ?></td>
                  <td><?= $fila['entregadas'] ?></td>
                  <td><?= $fila['pendientes'] ?></td>
                  <td><?= $pesos($fila['monto']) ?></td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$sel['detalle']): ?><tr><td colspan="6" class="text-center text-muted">Sin ventas de bebidas.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <div class="row g-4 mb-4">
    <div class="col-12 col-lg-6"><div class="card shadow border-0 h-100"><div class="card-header">Jarras por bebida y tipo</div><div class="card-body"><canvas id="graficoMesas" aria-label="Jarras por bebida"></canvas></div></div></div>
    <div class="col-12 col-lg-6"><div class="card shadow border-0 h-100"><div class="card-header">Total vendido por jornada</div><div class="card-body"><canvas id="graficoJornadas" aria-label="Total vendido por jornada"></canvas></div></div></div>
  </div>

  <div class="card shadow border-0">
    <div class="card-header"><i class="fa-solid fa-table me-1" aria-hidden="true"></i> Jornadas del período</div>
    <div class="card-body">
      <div class="table-responsive">
        <table id="tablaJornadas" class="table table-bordered table-hover table-sm align-middle w-100">
          <thead class="table-light"><tr><th>#</th><th>Apertura</th><th>Estado</th><th>Vendidas</th><th>Entregadas</th><th>Pendientes</th><th>Total</th><th>Personas</th></tr></thead>
          <tbody>
            <?php foreach ($datos['jornadas'] as $j): ?>
              <tr>
                <td><?= $j['id'] ?></td>
                <td data-order="<?= piccolo_e($j['apertura']) ?>"><?= $fecha($j['apertura']) ?></td>
                <td><?= piccolo_e(ucfirst($j['estado'])) ?></td>
                <td><?= $j['vendidas'] ?></td>
                <td><?= $j['entregadas'] ?></td>
                <td><?= $j['pendientes'] ?></td>
                <td data-order="<?= $j['monto'] ?>"><?= $pesos($j['monto']) ?></td>
                <td><?= $j['personas'] ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script id="datosEstadisticas" type="application/json"><?= json_encode([
    'tipo' => 'bebidas',
    'porBebida' => array_map(static fn(array $b): array => ['nombre' => $b['bebida'] . ' · ' . $tipo($b['tipo']), 'valor' => $b['vendidas']], $datos['por_bebida']),
    'jornadas' => array_map(static fn(array $j): array => ['id' => $j['id'], 'valor' => $j['monto']], array_reverse($datos['jornadas'])),
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="<?= piccolo_e($url_base) ?>assets/js/graficos.js"></script>
<script src="<?= piccolo_e($url_base) ?>assets/js/pages/estadisticas.js"></script>
