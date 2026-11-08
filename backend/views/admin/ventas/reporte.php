<?php
/**
 * Reporte de ventas de un período: totales, productos, métodos de pago, entregas y comparación mensual.
 *
 * @var array<string, mixed> $resumen  lo que devuelve VentasController::resumen()
 * @var array{tipo: string, mensaje: string}|null $aviso
 * @var string $url_base
 */
$pesos = static fn(float $monto): string => '$' . number_format($monto, 2, ',', '.');
$periodo = http_build_query(['desde' => $resumen['desde'], 'hasta' => $resumen['hasta']]);
$hoy = date('Y-m-d');
$atajos = [
    'Última semana' => [date('Y-m-d', strtotime('-6 days')), $hoy],
    'Este mes' => [date('Y-m-01'), $hoy],
    'Este año' => [date('Y-01-01'), $hoy],
];
$estrella = $resumen['producto_estrella'];
$origen = $resumen['por_origen'];
?>
<div class="mt-4 mb-5">
  <h2 class="mb-4 text-center"><i class="fa-solid fa-chart-line" aria-hidden="true"></i> Ventas</h2>

  <?php include __DIR__ . '/../_aviso.php'; ?>

  <form method="get" class="row g-2 mb-4 justify-content-center">
    <div class="col-12 col-md-auto">
      <label class="form-label" for="desde">Desde:</label>
      <input type="date" class="form-control" name="desde" id="desde" value="<?= piccolo_e($resumen['desde']) ?>">
    </div>
    <div class="col-12 col-md-auto">
      <label class="form-label" for="hasta">Hasta:</label>
      <input type="date" class="form-control" name="hasta" id="hasta" value="<?= piccolo_e($resumen['hasta']) ?>">
    </div>
    <div class="col-12 col-md-auto d-flex align-items-end flex-wrap gap-2">
      <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter" aria-hidden="true"></i> Filtrar</button>
      <?php foreach ($atajos as $nombre => [$desde, $hasta]): ?>
        <a href="?<?= piccolo_e(http_build_query(['desde' => $desde, 'hasta' => $hasta])) ?>" class="btn btn-outline-secondary"><?= piccolo_e($nombre) ?></a>
      <?php endforeach; ?>
    </div>
  </form>

  <div class="row mb-4 g-3">
    <div class="col-md-4 col-12">
      <div class="card shadow border-0 h-100">
        <div class="card-body text-center">
          <i class="fa-solid fa-sack-dollar fa-2x text-success mb-2" aria-hidden="true"></i>
          <h5 class="card-title">Total vendido</h5>
          <p class="fs-4 fw-bold text-success mb-1"><?= $pesos($resumen['total']) ?></p>
          <small class="text-muted d-block">
            Pedidos <?= $pesos($origen['pedidos']) ?> · Fichas <?= $pesos($origen['fichas']) ?> · Bebidas <?= $pesos($origen['bebidas']) ?>
          </small>
        </div>
      </div>
    </div>
    <div class="col-md-4 col-12">
      <div class="card shadow border-0 h-100">
        <div class="card-body text-center">
          <i class="fa-solid fa-receipt fa-2x text-info mb-2" aria-hidden="true"></i>
          <h5 class="card-title">Pedidos</h5>
          <p class="fs-4 fw-bold text-info mb-1"><?= (int) $resumen['cantidad_pedidos'] ?></p>
          <small class="text-muted">Ticket promedio: <?= $pesos($resumen['ticket_promedio']) ?> · sin contar cancelados</small>
        </div>
      </div>
    </div>
    <div class="col-md-4 col-12">
      <div class="card shadow border-0 h-100">
        <div class="card-body text-center">
          <i class="fa-solid fa-crown fa-2x text-warning mb-2" aria-hidden="true"></i>
          <h5 class="card-title">Producto estrella</h5>
          <?php if ($estrella): ?>
            <p class="fw-bold mb-1"><?= piccolo_e($estrella['nombre']) ?></p>
            <small class="text-muted"><?= (int) $estrella['cantidad'] ?> vendidos</small>
          <?php else: ?>
            <p class="text-muted">Sin ventas en el período</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="row mb-4 g-3">
    <?php foreach ([['Métodos de pago', 'fa-credit-card text-primary', $resumen['metodos_pago']], ['Tipos de entrega', 'fa-truck text-danger', $resumen['tipos_entrega']]] as [$titulo, $icono, $grupos]): ?>
      <div class="col-md-6 col-12">
        <div class="card shadow border-0 h-100">
          <div class="card-body text-center">
            <i class="fa-solid <?= $icono ?> fa-2x mb-2" aria-hidden="true"></i>
            <h5 class="card-title"><?= piccolo_e($titulo) ?></h5>
            <?php foreach ($grupos as $grupo): ?>
              <p class="mb-1"><strong><?= piccolo_e(ucfirst($grupo['nombre'])) ?>:</strong> <?= (int) $grupo['pedidos'] ?> pedidos (<?= $pesos($grupo['monto']) ?>)</p>
            <?php endforeach; ?>
            <?php if (!$grupos): ?><p class="text-muted">Sin registros</p><?php endif; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="row g-4">
    <div class="col-md-6 col-12">
      <div class="card shadow border-0 h-100">
        <div class="card-header"><i class="fa-solid fa-chart-pie" aria-hidden="true"></i> Productos más vendidos</div>
        <div class="card-body">
          <?php if ($resumen['productos']): ?>
            <canvas id="graficoProductos" aria-label="Productos más vendidos"></canvas>
          <?php else: ?>
            <p class="text-muted text-center my-4">Sin ventas en el período</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <div class="col-md-6 col-12">
      <div class="card shadow border-0 h-100">
        <div class="card-header"><i class="fa-solid fa-chart-bar" aria-hidden="true"></i> Ventas por mes de <?= (int) $resumen['mensual']['anio'] ?></div>
        <div class="card-body">
          <canvas id="graficoMensual" aria-label="Ventas por mes"></canvas>
        </div>
      </div>
    </div>
  </div>

  <div class="mt-4 text-center">
    <a href="export_pdf.php?<?= piccolo_e($periodo) ?>" class="btn btn-danger me-2"><i class="fa-solid fa-file-pdf" aria-hidden="true"></i> Exportar PDF</a>
    <a href="export_excel.php?<?= piccolo_e($periodo) ?>" class="btn btn-success"><i class="fa-solid fa-file-excel" aria-hidden="true"></i> Exportar Excel</a>
  </div>
</div>

<script id="datosVentas" type="application/json"><?= json_encode([
    'productos' => array_slice($resumen['productos'], 0, 10),
    'mensual' => $resumen['mensual']['meses'],
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="<?= piccolo_e($url_base) ?>assets/js/graficos.js"></script>
<script src="<?= piccolo_e($url_base) ?>assets/js/pages/ventas.js"></script>
