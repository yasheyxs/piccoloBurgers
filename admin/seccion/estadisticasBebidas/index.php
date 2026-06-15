<?php
require_once __DIR__ . '/../../bd.php';
require_once __DIR__ . '/../../../app/Services/bebidas_schema.php';

verificarRol('admin');
asegurarTablasBebidas($conexion);

[$fechaInicio, $fechaFin] = poolRangoFechasDesdeParametros($_GET);
$jornadas = bebidaListarJornadas($conexion, $fechaInicio, $fechaFin);
$resumen = bebidaResumenGeneral($jornadas);
$jornadaId = isset($_GET['jornada_id']) ? (int) $_GET['jornada_id'] : 0;
$seleccionada = null;
foreach ($jornadas as $jornada) {
    if ((int) $jornada['id'] === $jornadaId) {
        $seleccionada = $jornada;
        break;
    }
}
if (!$seleccionada && $jornadas) {
    $seleccionada = $jornadas[0];
}

$detalleGeneral = [];
foreach ($jornadas as $jornada) {
    foreach ($jornada['detalle'] as $fila) {
        $clave = $fila['bebida_nombre'] . '|' . $fila['tipo'];
        if (!isset($detalleGeneral[$clave])) {
            $detalleGeneral[$clave] = [
                'label' => $fila['bebida_nombre'] . ' - ' . ($fila['tipo'] === 'promo' ? 'Promoción' : 'Jarra'),
                'vendidas' => 0,
                'monto' => 0,
            ];
        }
        $detalleGeneral[$clave]['vendidas'] += $fila['vendidas'];
        $detalleGeneral[$clave]['monto'] += $fila['monto_vendido'];
    }
}

$chartLabels = json_encode(array_column($detalleGeneral, 'label'), JSON_UNESCAPED_UNICODE);
$chartVendidas = json_encode(array_column($detalleGeneral, 'vendidas'));
$jornadaLabels = json_encode(array_map(static fn(array $j): string => '#' . $j['id'], $jornadas));
$jornadaMontos = json_encode(array_map(static fn(array $j): float => (float) $j['monto_vendido'], $jornadas));

$adminPageIdentifier = 'estadisticas-bebidas';
include __DIR__ . '/../../templates/header.php';
?>

<div class="container mt-4 mb-5">
  <h1 class="h2 mb-4 text-center"><i class="fa-solid fa-chart-column me-2"></i>Estadísticas de Bebidas</h1>

  <form method="get" class="row g-2 mb-4 justify-content-center">
    <div class="col-12 col-md-auto">
      <label class="form-label">Desde</label>
      <input class="form-control" name="fecha_inicio" type="date" value="<?= htmlspecialchars($fechaInicio, ENT_QUOTES, 'UTF-8'); ?>">
    </div>
    <div class="col-12 col-md-auto">
      <label class="form-label">Hasta</label>
      <input class="form-control" name="fecha_fin" type="date" value="<?= htmlspecialchars($fechaFin, ENT_QUOTES, 'UTF-8'); ?>">
    </div>
    <div class="col-12 col-md-auto">
      <label class="form-label">Jornada</label>
      <select class="form-select" name="jornada_id">
        <option value="0">Última del período</option>
        <?php foreach ($jornadas as $jornada): ?>
          <option value="<?= (int) $jornada['id']; ?>" <?= $seleccionada && $seleccionada['id'] === $jornada['id'] ? 'selected' : ''; ?>>
            #<?= (int) $jornada['id']; ?> - <?= date('d/m/Y H:i', strtotime($jornada['fecha_apertura'])); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-12 col-md-auto d-flex align-items-end">
      <button class="btn btn-primary" type="submit"><i class="fa-solid fa-filter me-1"></i> Filtrar</button>
    </div>
  </form>

  <div class="row g-3 mb-4">
    <?php
    $cards = [
        ['Jornadas', $resumen['total_jornadas'], 'fa-calendar-check', 'primary'],
        ['Jarras vendidas', $resumen['total_vendidas'], 'fa-glass-water', 'success'],
        ['Pendientes', $resumen['total_pendientes'], 'fa-hourglass-half', 'warning'],
        ['Total vendido', '$' . number_format($resumen['monto_vendido_total'], 2, ',', '.'), 'fa-sack-dollar', 'success'],
    ];
    foreach ($cards as [$titulo, $valor, $icono, $color]): ?>
      <div class="col-12 col-md-3">
        <div class="card shadow border-0 h-100 text-center">
          <div class="card-body">
            <i class="fa-solid <?= $icono; ?> fa-2x text-<?= $color; ?> mb-2"></i>
            <h2 class="h6"><?= $titulo; ?></h2>
            <div class="fs-4 fw-bold text-<?= $color; ?>"><?= $valor; ?></div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <?php if ($seleccionada): ?>
    <div class="card shadow border-0 mb-4">
      <div class="card-header"><i class="fa-solid fa-clipboard-list me-1"></i> Jornada #<?= (int) $seleccionada['id']; ?></div>
      <div class="card-body">
        <div class="row g-3 mb-3">
          <div class="col-md-3"><strong>Apertura</strong><br><?= date('d/m/Y H:i', strtotime($seleccionada['fecha_apertura'])); ?></div>
          <div class="col-md-2"><strong>Estado</strong><br><?= htmlspecialchars(ucfirst($seleccionada['estado']), ENT_QUOTES, 'UTF-8'); ?></div>
          <div class="col-md-2"><strong>Vendidas</strong><br><?= (int) $seleccionada['vendidas']; ?></div>
          <div class="col-md-2"><strong>Pendientes</strong><br><?= (int) $seleccionada['pendientes']; ?></div>
          <div class="col-md-3"><strong>Total vendido</strong><br>$<?= number_format($seleccionada['monto_vendido'], 2, ',', '.'); ?></div>
        </div>
        <div class="table-responsive">
          <table class="table table-sm table-bordered align-middle mb-0" data-no-datatable>
            <thead><tr><th>Bebida</th><th>Tipo</th><th>Vendidas</th><th>Entregadas</th><th>Pendientes</th><th>Total</th></tr></thead>
            <tbody>
              <?php foreach ($seleccionada['detalle'] as $fila): ?>
                <tr>
                  <td><?= htmlspecialchars($fila['bebida_nombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?= $fila['tipo'] === 'promo' ? 'Promoción' : 'Jarra'; ?></td>
                  <td><?= (int) $fila['vendidas']; ?></td>
                  <td><?= (int) $fila['entregadas']; ?></td>
                  <td><?= (int) $fila['pendientes']; ?></td>
                  <td>$<?= number_format($fila['monto_vendido'], 2, ',', '.'); ?></td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$seleccionada['detalle']): ?><tr><td colspan="6" class="text-center text-muted">Sin ventas de bebidas.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <div class="row g-4 mb-4">
    <div class="col-12 col-lg-6"><div class="card shadow border-0"><div class="card-header">Jarras por bebida y tipo</div><div class="card-body"><canvas id="bebidasChart"></canvas></div></div></div>
    <div class="col-12 col-lg-6"><div class="card shadow border-0"><div class="card-header">Importe por jornada</div><div class="card-body"><canvas id="jornadasBebidasChart"></canvas></div></div></div>
  </div>

  <div class="card shadow border-0">
    <div class="card-header"><i class="fa-solid fa-table me-1"></i> Jornadas</div>
    <div class="card-body table-responsive">
      <table class="table table-hover align-middle">
        <thead><tr><th>ID</th><th>Apertura</th><th>Estado</th><th>Vendidas</th><th>Entregadas</th><th>Pendientes</th><th>Total</th><th>Personas</th></tr></thead>
        <tbody>
          <?php foreach ($jornadas as $jornada): ?>
            <tr>
              <td><?= (int) $jornada['id']; ?></td>
              <td><?= date('d/m/Y H:i', strtotime($jornada['fecha_apertura'])); ?></td>
              <td><?= htmlspecialchars(ucfirst($jornada['estado']), ENT_QUOTES, 'UTF-8'); ?></td>
              <td><?= (int) $jornada['vendidas']; ?></td>
              <td><?= (int) $jornada['entregadas']; ?></td>
              <td><?= (int) $jornada['pendientes']; ?></td>
              <td>$<?= number_format($jornada['monto_vendido'], 2, ',', '.'); ?></td>
              <td><?= (int) $jornada['personas']; ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
new Chart(document.getElementById('bebidasChart'), {
  type: 'bar',
  data: { labels: <?= $chartLabels; ?>, datasets: [{ label: 'Jarras vendidas', data: <?= $chartVendidas; ?>, backgroundColor: '#0d9488' }] },
  options: { responsive: true, scales: { y: { beginAtZero: true } } }
});
new Chart(document.getElementById('jornadasBebidasChart'), {
  type: 'line',
  data: { labels: <?= $jornadaLabels; ?>, datasets: [{ label: 'Importe vendido', data: <?= $jornadaMontos; ?>, borderColor: '#f59e0b', backgroundColor: 'rgba(245,158,11,.18)', tension: .25 }] },
  options: { responsive: true, scales: { y: { beginAtZero: true } } }
});
</script>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
