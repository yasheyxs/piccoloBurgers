<?php
require_once __DIR__ . '/../../bd.php';
require_once __DIR__ . '/../../../app/Services/pool_schema.php';

verificarRol('admin');
asegurarTablaPoolTurnos($conexion);

function poolStatsValidarFecha(string $fecha): bool
{
    return (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) && strtotime($fecha) !== false;
}

function poolStatsDuracion(?int $minutos): string
{
    if ($minutos === null) {
        return 'En curso';
    }

    $horas = intdiv($minutos, 60);
    $resto = $minutos % 60;
    return $horas > 0 ? $horas . ' h ' . $resto . ' min' : $resto . ' min';
}

$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');

if (!poolStatsValidarFecha($fecha_inicio)) {
    $fecha_inicio = date('Y-m-01');
}

if (!poolStatsValidarFecha($fecha_fin)) {
    $fecha_fin = date('Y-m-d');
}

$jornadas = poolListarJornadas($conexion, $fecha_inicio, $fecha_fin);
$resumen = poolResumenGeneral($jornadas);
$jornadaSeleccionadaId = isset($_GET['jornada_id']) ? (int) $_GET['jornada_id'] : 0;
$jornadaSeleccionada = null;

foreach ($jornadas as $jornada) {
    if ((int) $jornada['id'] === $jornadaSeleccionadaId) {
        $jornadaSeleccionada = $jornada;
        break;
    }
}

if (!$jornadaSeleccionada && !empty($jornadas)) {
    $jornadaSeleccionada = $jornadas[0];
}

$comparativaLabels = json_encode(['Pool Azul', 'Pool Rojo']);
$comparativaVendidas = json_encode([(int) $resumen['vendidas_azul'], (int) $resumen['vendidas_rojo']]);
$comparativaMontos = json_encode([(float) $resumen['monto_vendido_azul'], (float) $resumen['monto_vendido_rojo']]);
$jornadasLabels = json_encode(array_map(static fn(array $jornada): string => '#' . $jornada['id'], $jornadas));
$jornadasVendidas = json_encode(array_map(static fn(array $jornada): int => (int) $jornada['total_vendidas'], $jornadas));

$adminPageIdentifier = 'estadisticas-pool';
include __DIR__ . '/../../templates/header.php';
?>

<div class="container mt-4 mb-5">
  <h2 class="mb-4 text-center"><i class="fa-solid fa-chart-column"></i> Estadisticas de Pool</h2>

  <form method="get" class="row g-2 mb-4 justify-content-center">
    <div class="col-12 col-md-auto">
      <label class="form-label">Desde:</label>
      <input type="date" class="form-control" name="fecha_inicio" value="<?= htmlspecialchars($fecha_inicio, ENT_QUOTES, 'UTF-8'); ?>">
    </div>
    <div class="col-12 col-md-auto">
      <label class="form-label">Hasta:</label>
      <input type="date" class="form-control" name="fecha_fin" value="<?= htmlspecialchars($fecha_fin, ENT_QUOTES, 'UTF-8'); ?>">
    </div>
    <div class="col-12 col-md-auto">
      <label class="form-label">Jornada:</label>
      <select class="form-select" name="jornada_id">
        <option value="0">Ultima del periodo</option>
        <?php foreach ($jornadas as $jornada): ?>
          <option value="<?= (int) $jornada['id']; ?>" <?= $jornadaSeleccionada && (int) $jornadaSeleccionada['id'] === (int) $jornada['id'] ? 'selected' : ''; ?>>
            #<?= (int) $jornada['id']; ?> - <?= htmlspecialchars(date('d/m/Y H:i', strtotime($jornada['fecha_apertura'])), ENT_QUOTES, 'UTF-8'); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-12 col-md-auto d-flex align-items-end flex-wrap gap-2">
      <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter"></i> Filtrar</button>
      <a href="?fecha_inicio=<?= date('Y-m-d', strtotime('-7 days')); ?>&fecha_fin=<?= date('Y-m-d'); ?>" class="btn btn-outline-secondary">Ultima semana</a>
      <a href="?fecha_inicio=<?= date('Y-m-01'); ?>&fecha_fin=<?= date('Y-m-d'); ?>" class="btn btn-outline-secondary">Este mes</a>
      <a href="?fecha_inicio=<?= date('Y-01-01'); ?>&fecha_fin=<?= date('Y-m-d'); ?>" class="btn btn-outline-secondary">Este año</a>
    </div>
  </form>

  <div class="row mb-4 g-3">
    <div class="col-md-3 col-12">
      <div class="card shadow border-0 h-100">
        <div class="card-body text-center">
          <i class="fa-solid fa-calendar-check fa-2x text-primary mb-2"></i>
          <h5 class="card-title">Jornadas</h5>
          <p class="fs-4 fw-bold text-primary"><?= (int) $resumen['total_jornadas']; ?></p>
        </div>
      </div>
    </div>
    <div class="col-md-3 col-12">
      <div class="card shadow border-0 h-100">
        <div class="card-body text-center">
          <i class="fa-solid fa-circle-dot fa-2x text-success mb-2"></i>
          <h5 class="card-title">Fichas vendidas</h5>
          <p class="fs-4 fw-bold text-success"><?= (int) $resumen['total_vendidas']; ?></p>
        </div>
      </div>
    </div>
    <div class="col-md-3 col-12">
      <div class="card shadow border-0 h-100">
        <div class="card-body text-center">
          <i class="fa-solid fa-hourglass-half fa-2x text-warning mb-2"></i>
          <h5 class="card-title">Promedio por jornada</h5>
          <p class="fs-4 fw-bold text-warning"><?= number_format((float) $resumen['promedio_fichas_jornada'], 2, ',', '.'); ?></p>
        </div>
      </div>
    </div>
    <div class="col-md-3 col-12">
      <div class="card shadow border-0 h-100">
        <div class="card-body text-center">
          <i class="fa-solid fa-sack-dollar fa-2x text-success mb-2"></i>
          <h5 class="card-title">Valor vendido</h5>
          <p class="fs-4 fw-bold text-success">$<?= number_format((float) $resumen['monto_vendido_total'], 2, ',', '.'); ?></p>
        </div>
      </div>
    </div>
  </div>

  <?php if ($jornadaSeleccionada): ?>
    <div class="card shadow border-0 mb-4">
      <div class="card-header"><i class="fa-solid fa-clipboard-list"></i> Jornada seleccionada</div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-3"><strong>Apertura:</strong><br><?= htmlspecialchars(date('d/m/Y H:i', strtotime($jornadaSeleccionada['fecha_apertura'])), ENT_QUOTES, 'UTF-8'); ?></div>
          <div class="col-md-3"><strong>Cierre:</strong><br><?= $jornadaSeleccionada['fecha_cierre'] ? htmlspecialchars(date('d/m/Y H:i', strtotime($jornadaSeleccionada['fecha_cierre'])), ENT_QUOTES, 'UTF-8') : 'En curso'; ?></div>
          <div class="col-md-2"><strong>Estado:</strong><br><?= htmlspecialchars(ucfirst($jornadaSeleccionada['estado']), ENT_QUOTES, 'UTF-8'); ?></div>
          <div class="col-md-2"><strong>Duracion:</strong><br><?= htmlspecialchars(poolStatsDuracion($jornadaSeleccionada['duracion_minutos']), ENT_QUOTES, 'UTF-8'); ?></div>
          <div class="col-md-2"><strong>Atendidos:</strong><br><?= (int) $jornadaSeleccionada['jugadores_atendidos']; ?></div>
          <div class="col-md-2"><strong>Valor vendido:</strong><br>$<?= number_format((float) $jornadaSeleccionada['monto_vendido_total'], 2, ',', '.'); ?></div>
        </div>
        <hr>
        <div class="table-responsive">
          <table class="table table-sm table-bordered align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Pool</th>
                <th>Vendidas</th>
                <th>Valor vendido</th>
                <th>Pendientes</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>Pool Azul</td>
                <td><?= (int) $jornadaSeleccionada['fichas_vendidas_azul']; ?></td>
                <td>$<?= number_format((float) $jornadaSeleccionada['monto_vendido_azul'], 2, ',', '.'); ?></td>
                <td><?= (int) $jornadaSeleccionada['fichas_pendientes_azul']; ?></td>
              </tr>
              <tr>
                <td>Pool Rojo</td>
                <td><?= (int) $jornadaSeleccionada['fichas_vendidas_rojo']; ?></td>
                <td>$<?= number_format((float) $jornadaSeleccionada['monto_vendido_rojo'], 2, ',', '.'); ?></td>
                <td><?= (int) $jornadaSeleccionada['fichas_pendientes_rojo']; ?></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <div class="row g-4 mb-4">
    <div class="col-md-6 col-12">
      <div class="card shadow border-0">
        <div class="card-header"><i class="fa-solid fa-chart-bar"></i> Comparativa Azul / Rojo</div>
        <div class="card-body"><canvas id="comparativaPoolChart"></canvas></div>
      </div>
    </div>
    <div class="col-md-6 col-12">
      <div class="card shadow border-0">
        <div class="card-header"><i class="fa-solid fa-chart-line"></i> Fichas por jornada</div>
        <div class="card-body"><canvas id="jornadasPoolChart"></canvas></div>
      </div>
    </div>
  </div>

  <div class="card shadow border-0">
    <div class="card-header"><i class="fa-solid fa-table"></i> Jornadas registradas</div>
    <div class="card-body">
      <div class="table-responsive">
        <table id="tablaEstadisticasPool" class="table table-bordered table-hover table-sm align-middle w-100">
          <thead class="table-light">
            <tr>
              <th>ID</th>
              <th>Apertura</th>
              <th>Cierre</th>
              <th>Estado</th>
              <th>Vendidas</th>
              <th>Valor vendido</th>
              <th>Pendientes</th>
              <th>Jugadores</th>
              <th>Completados</th>
              <th>Duracion</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($jornadas as $jornada): ?>
              <tr>
                <td><?= (int) $jornada['id']; ?></td>
                <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($jornada['fecha_apertura'])), ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?= $jornada['fecha_cierre'] ? htmlspecialchars(date('d/m/Y H:i', strtotime($jornada['fecha_cierre'])), ENT_QUOTES, 'UTF-8') : 'En curso'; ?></td>
                <td><?= htmlspecialchars(ucfirst($jornada['estado']), ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?= (int) $jornada['total_vendidas']; ?></td>
                <td>$<?= number_format((float) $jornada['monto_vendido_total'], 2, ',', '.'); ?></td>
                <td><?= (int) $jornada['total_pendientes']; ?></td>
                <td><?= (int) $jornada['jugadores_atendidos']; ?></td>
                <td><?= (int) $jornada['turnos_completados']; ?></td>
                <td><?= htmlspecialchars(poolStatsDuracion($jornada['duracion_minutos']), ENT_QUOTES, 'UTF-8'); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="mt-4 text-center">
    <a href="export_pdf.php?fecha_inicio=<?= urlencode($fecha_inicio); ?>&fecha_fin=<?= urlencode($fecha_fin); ?>" class="btn btn-danger me-2">
      <i class="fa-solid fa-file-pdf"></i> Exportar PDF
    </a>
    <a href="export_excel.php?fecha_inicio=<?= urlencode($fecha_inicio); ?>&fecha_fin=<?= urlencode($fecha_fin); ?>" class="btn btn-success">
      <i class="fa-solid fa-file-excel"></i> Exportar Excel
    </a>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  const poolCharts = [];

  function poolChartTheme() {
    const dark = document.body.classList.contains('admin-dark');
    return {
      text: dark ? '#e8ecf4' : '#212529',
      grid: dark ? 'rgba(232, 236, 244, 0.18)' : 'rgba(33, 37, 41, 0.12)'
    };
  }

  function applyPoolChartTheme(chart) {
    const { text, grid } = poolChartTheme();
    if (chart.options.plugins?.legend?.labels) chart.options.plugins.legend.labels.color = text;
    if (chart.options.scales) {
      Object.values(chart.options.scales).forEach(axis => {
        if (axis.ticks) axis.ticks.color = text;
        if (axis.grid) axis.grid.color = grid;
      });
    }
    chart.update('none');
  }

  function registerPoolChart(chart) {
    poolCharts.push(chart);
    applyPoolChartTheme(chart);
  }

  document.addEventListener('theme:changed', () => poolCharts.forEach(applyPoolChartTheme));

  document.addEventListener('DOMContentLoaded', function () {
    $('#tablaEstadisticasPool').DataTable({
      paging: true,
      searching: true,
      info: false,
      lengthChange: true,
      pageLength: 10,
      language: {
        emptyTable: 'No hay jornadas registradas',
        search: 'Buscar:',
        lengthMenu: 'Mostrar registros _MENU_',
        paginate: { first: 'Primero', last: 'Ultimo', next: 'Siguiente', previous: 'Anterior' }
      }
    });

    registerPoolChart(new Chart(document.getElementById('comparativaPoolChart'), {
      type: 'bar',
      data: {
        labels: <?= $comparativaLabels; ?>,
        datasets: [
          { label: 'Vendidas', data: <?= $comparativaVendidas; ?>, backgroundColor: '#2563eb' },
          { label: 'Valor vendido', data: <?= $comparativaMontos; ?>, backgroundColor: '#f59e0b', yAxisID: 'money' }
        ]
      },
      options: { responsive: true, scales: { y: { beginAtZero: true }, money: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false } } } }
    }));

    registerPoolChart(new Chart(document.getElementById('jornadasPoolChart'), {
      type: 'line',
      data: {
        labels: <?= $jornadasLabels; ?>,
        datasets: [{ label: 'Fichas vendidas', data: <?= $jornadasVendidas; ?>, borderColor: '#2563eb', backgroundColor: 'rgba(37, 99, 235, 0.18)', tension: 0.25 }]
      },
      options: { responsive: true, scales: { y: { beginAtZero: true } } }
    }));
  });
</script>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
