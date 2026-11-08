<?php
/**
 * Estadísticas del pool en un período: resumen, jornada elegida, gráficos y tabla de jornadas.
 *
 * @var array<string, mixed> $datos  lo que devuelve EstadisticasController::pool()
 * @var array{tipo: string, mensaje: string}|null $aviso
 * @var string $url_base
 */
$pesos = static fn(float $monto): string => '$' . number_format($monto, 2, ',', '.');
$fecha = static fn(?string $valor): string => $valor ? date('d/m/Y H:i', strtotime($valor)) : 'En curso';
$resumen = $datos['resumen'];
$sel = $datos['seleccionada'];
$periodo = http_build_query(['desde' => $datos['desde'], 'hasta' => $datos['hasta']]);
$tarjetas = [
    ['Jornadas', $resumen['jornadas'], 'fa-calendar-check', 'primary'],
    ['Fichas vendidas', $resumen['vendidas'], 'fa-circle-dot', 'success'],
    ['Promedio por jornada', number_format($resumen['promedio_por_jornada'], 2, ',', '.'), 'fa-hourglass-half', 'warning'],
    ['Valor vendido', $pesos($resumen['monto']), 'fa-sack-dollar', 'success'],
];
?>
<div class="mt-4 mb-5">
  <h2 class="mb-4 text-center"><i class="fa-solid fa-chart-column" aria-hidden="true"></i> Estadísticas del pool</h2>

  <?php include __DIR__ . '/../_aviso.php'; ?>
  <?php include __DIR__ . '/_filtro.php'; ?>

  <div class="row mb-4 g-3">
    <?php foreach ($tarjetas as [$titulo, $valor, $icono, $color]): ?>
      <div class="col-md-3 col-12">
        <div class="card shadow border-0 h-100">
          <div class="card-body text-center">
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
      <div class="card-header"><i class="fa-solid fa-clipboard-list" aria-hidden="true"></i> Jornada #<?= $sel['id'] ?></div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-3"><strong>Apertura:</strong><br><?= $fecha($sel['apertura']) ?></div>
          <div class="col-md-3"><strong>Cierre:</strong><br><?= $fecha($sel['cierre']) ?></div>
          <div class="col-md-2"><strong>Estado:</strong><br><?= piccolo_e(ucfirst($sel['estado'])) ?></div>
          <div class="col-md-2"><strong>Duración:</strong><br><?= piccolo_e($sel['duracion']) ?></div>
          <div class="col-md-2"><strong>Jugadores:</strong><br><?= $sel['jugadores'] ?></div>
        </div>
        <hr>
        <div class="table-responsive">
          <table class="table table-sm table-bordered align-middle mb-0" data-no-datatable>
            <thead class="table-light"><tr><th>Mesa</th><th>Vendidas</th><th>Valor vendido</th><th>Pendientes</th></tr></thead>
            <tbody>
              <?php foreach (['azul' => 'Pool azul', 'rojo' => 'Pool rojo'] as $mesa => $nombre): ?>
                <tr>
                  <td><?= $nombre ?></td>
                  <td><?= $sel['mesas'][$mesa]['vendidas'] ?></td>
                  <td><?= $pesos($sel['mesas'][$mesa]['monto']) ?></td>
                  <td><?= $sel['mesas'][$mesa]['pendientes'] ?></td>
                </tr>
              <?php endforeach; ?>
              <tr class="fw-semibold"><td>Total</td><td><?= $sel['vendidas'] ?></td><td><?= $pesos($sel['monto']) ?></td><td><?= $sel['pendientes'] ?></td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <div class="row g-4 mb-4">
    <div class="col-md-6 col-12">
      <div class="card shadow border-0 h-100">
        <div class="card-header"><i class="fa-solid fa-chart-bar" aria-hidden="true"></i> Mesa azul y roja</div>
        <div class="card-body"><canvas id="graficoMesas" aria-label="Fichas vendidas por mesa"></canvas></div>
      </div>
    </div>
    <div class="col-md-6 col-12">
      <div class="card shadow border-0 h-100">
        <div class="card-header"><i class="fa-solid fa-chart-line" aria-hidden="true"></i> Fichas por jornada</div>
        <div class="card-body"><canvas id="graficoJornadas" aria-label="Fichas vendidas por jornada"></canvas></div>
      </div>
    </div>
  </div>

  <div class="card shadow border-0">
    <div class="card-header"><i class="fa-solid fa-table" aria-hidden="true"></i> Jornadas del período</div>
    <div class="card-body">
      <div class="table-responsive">
        <table id="tablaJornadas" class="table table-bordered table-hover table-sm align-middle w-100">
          <thead class="table-light">
            <tr><th>#</th><th>Apertura</th><th>Cierre</th><th>Estado</th><th>Vendidas</th><th>Valor vendido</th><th>Pendientes</th><th>Jugadores</th><th>Turnos completos</th><th>Duración</th></tr>
          </thead>
          <tbody>
            <?php foreach ($datos['jornadas'] as $j): ?>
              <tr>
                <td><?= $j['id'] ?></td>
                <td data-order="<?= piccolo_e($j['apertura']) ?>"><?= $fecha($j['apertura']) ?></td>
                <td><?= $fecha($j['cierre']) ?></td>
                <td><?= piccolo_e(ucfirst($j['estado'])) ?></td>
                <td><?= $j['vendidas'] ?></td>
                <td data-order="<?= $j['monto'] ?>"><?= $pesos($j['monto']) ?></td>
                <td><?= $j['pendientes'] ?></td>
                <td><?= $j['jugadores'] ?></td>
                <td><?= $j['turnos_completados'] ?></td>
                <td data-order="<?= (int) $j['duracion_minutos'] ?>"><?= piccolo_e($j['duracion']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="mt-4 text-center">
    <a href="export_pdf.php?<?= piccolo_e($periodo) ?>" class="btn btn-danger me-2"><i class="fa-solid fa-file-pdf" aria-hidden="true"></i> Exportar PDF</a>
    <a href="export_excel.php?<?= piccolo_e($periodo) ?>" class="btn btn-success"><i class="fa-solid fa-file-excel" aria-hidden="true"></i> Exportar Excel</a>
  </div>
</div>

<script id="datosEstadisticas" type="application/json"><?= json_encode([
    'tipo' => 'pool',
    'mesas' => $resumen['mesas'],
    'jornadas' => array_map(static fn(array $j): array => ['id' => $j['id'], 'valor' => $j['vendidas']], array_reverse($datos['jornadas'])),
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="<?= piccolo_e($url_base) ?>assets/js/graficos.js"></script>
<script src="<?= piccolo_e($url_base) ?>assets/js/pages/estadisticas.js"></script>
