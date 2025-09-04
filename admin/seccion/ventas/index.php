<?php
include("../../bd.php");

// Validación de fechas
function validarFecha($fecha) {
  return preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) && strtotime($fecha);
}

$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fecha_fin_input = $_GET['fecha_fin'] ?? date('Y-m-d');

if (!validarFecha($fecha_inicio)) $fecha_inicio = date('Y-m-01');
if (!validarFecha($fecha_fin_input)) $fecha_fin_input = date('Y-m-d');

$fecha_fin = $fecha_fin_input . ' 23:59:59';

$total_ventas = 0;
$total_pedidos = 0;
$producto_mas_vendido = [];
$productos_grafico = [];
$ventas_mensuales = [];
$metodos_pago = [];
$tipos_entrega = [];

try {
  // Total de ventas
  $stmt = $conexion->prepare("SELECT SUM(pd.precio * pd.cantidad) AS total_ventas
      FROM tbl_pedidos_detalle pd
      JOIN tbl_pedidos p ON pd.pedido_id = p.ID
      WHERE p.fecha BETWEEN :inicio AND :fin");
  $stmt->bindParam(':inicio', $fecha_inicio);
  $stmt->bindParam(':fin', $fecha_fin);
  $stmt->execute();
  $total_ventas = $stmt->fetch(PDO::FETCH_ASSOC)['total_ventas'] ?? 0;

  // Cantidad de pedidos
  $stmt = $conexion->prepare("SELECT COUNT(*) AS total_pedidos
      FROM tbl_pedidos p
      WHERE p.fecha BETWEEN :inicio AND :fin");
  $stmt->bindParam(':inicio', $fecha_inicio);
  $stmt->bindParam(':fin', $fecha_fin);
  $stmt->execute();
  $total_pedidos = $stmt->fetch(PDO::FETCH_ASSOC)['total_pedidos'] ?? 0;

  // Producto más vendido
  $stmt = $conexion->prepare("SELECT pd.nombre, SUM(pd.cantidad) AS total_vendido
    FROM tbl_pedidos_detalle pd
    JOIN tbl_pedidos p ON pd.pedido_id = p.ID
    WHERE p.fecha BETWEEN :inicio AND :fin
    GROUP BY pd.nombre
    ORDER BY total_vendido DESC
    LIMIT 1");
  $stmt->bindParam(':inicio', $fecha_inicio);
  $stmt->bindParam(':fin', $fecha_fin);
  $stmt->execute();
  $producto_mas_vendido = $stmt->fetch(PDO::FETCH_ASSOC);

  // Productos más vendidos para gráfico torta
  $stmt = $conexion->prepare("SELECT pd.nombre, SUM(pd.cantidad) AS total_vendido
    FROM tbl_pedidos_detalle pd
    JOIN tbl_pedidos p ON pd.pedido_id = p.ID
    WHERE p.fecha BETWEEN :inicio AND :fin
    GROUP BY pd.nombre
    ORDER BY total_vendido DESC
    LIMIT 10");
  $stmt->bindParam(':inicio', $fecha_inicio);
  $stmt->bindParam(':fin', $fecha_fin);
  $stmt->execute();
  $productos_grafico = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // Comparación mensual
  $anio_actual = date('Y');
  $stmt = $conexion->prepare("SELECT MONTH(p.fecha) AS mes, SUM(pd.precio * pd.cantidad) AS total_ventas
      FROM tbl_pedidos_detalle pd
      JOIN tbl_pedidos p ON pd.pedido_id = p.ID
      WHERE YEAR(p.fecha) = :anio
      GROUP BY MONTH(p.fecha)");
  $stmt->bindParam(':anio', $anio_actual);
  $stmt->execute();
  $ventas_mensuales = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // Métodos de pago
  $stmt = $conexion->prepare("SELECT metodo_pago, COUNT(*) AS total
      FROM tbl_pedidos p
      WHERE p.fecha BETWEEN :inicio AND :fin
      GROUP BY metodo_pago");
  $stmt->bindParam(':inicio', $fecha_inicio);
  $stmt->bindParam(':fin', $fecha_fin);
  $stmt->execute();
  $metodos_pago = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // Tipos de entrega
  $stmt = $conexion->prepare("SELECT tipo_entrega, COUNT(*) AS total
      FROM tbl_pedidos p
      WHERE p.fecha BETWEEN :inicio AND :fin
      GROUP BY tipo_entrega");
  $stmt->bindParam(':inicio', $fecha_inicio);
  $stmt->bindParam(':fin', $fecha_fin);
  $stmt->execute();
  $tipos_entrega = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
  $error_msg = "Error al cargar datos: " . htmlspecialchars($e->getMessage());
}

$productos_labels = json_encode(array_column($productos_grafico, 'nombre'));
$productos_data = json_encode(array_column($productos_grafico, 'total_vendido'));

$mes_nombres = [
  1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
  5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
  9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
];

$meses = json_encode(array_map(function($v) use ($mes_nombres) {
  return $mes_nombres[(int)$v['mes']] ?? 'Mes ' . $v['mes'];
}, $ventas_mensuales));

$ventas_data = json_encode(array_column($ventas_mensuales, 'total_ventas'));

include("../../templates/header.php");
?>

<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<div class="container mt-4 mb-5">
  <h2 class="mb-4 text-center"><i class="fa-solid fa-chart-line"></i> Panel de Ventas</h2>

  <?php if (isset($error_msg)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <?= $error_msg ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    </div>
  <?php endif; ?>

  <!-- Filtros -->
  <form method="get" class="row g-2 mb-4 justify-content-center">
    <div class="col-12 col-md-auto">
      <label class="form-label">Desde:</label>
      <input type="date" class="form-control" name="fecha_inicio" value="<?= htmlspecialchars($fecha_inicio) ?>">
    </div>
    <div class="col-12 col-md-auto">
      <label class="form-label">Hasta:</label>
      <input type="date" class="form-control" name="fecha_fin" value="<?= htmlspecialchars($fecha_fin_input) ?>">
    </div>
    <div class="col-12 col-md-auto d-flex align-items-end flex-wrap gap-2">
      <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter"></i> Filtrar</button>
      <a href="?fecha_inicio=<?= date('Y-m-d', strtotime('-7 days')) ?>&fecha_fin=<?= date('Y-m-d') ?>" class="btn btn-outline-secondary">Última semana</a>
      <a href="?fecha_inicio=<?= date('Y-m-01') ?>&fecha_fin=<?= date('Y-m-d') ?>" class="btn btn-outline-secondary">Este mes</a>
      <a href="?fecha_inicio=<?= date('Y-01-01') ?>&fecha_fin=<?= date('Y-m-d') ?>" class="btn btn-outline-secondary">Este año</a>
    </div>
  </form>

  <!-- Métricas -->
  <div class="row mb-4 g-3">
    <div class="col-md-4 col-12">
      <div class="card shadow border-0 h-100">
        <div class="card-body text-center">
          <i class="fa-solid fa-sack-dollar fa-2x text-success mb-2"></i>
          <h5 class="card-title">Total de Ventas</h5>
          <p class="fs-4 fw-bold text-success">$<?= number_format($total_ventas, 2) ?></p>
        </div>
      </div>
    </div>
    <div class="col-md-4 col-12">
      <div class="card shadow border-0 h-100">
        <div class="card-body text-center">
          <i class="fa-solid fa-receipt fa-2x text-info mb-2"></i>
          <h5 class="card-title">Pedidos Totales</h5>
          <p class="fs-4 fw-bold text-info"><?= $total_pedidos ?></p>
        </div>
      </div>
    </div>
        <div class="col-md-4 col-12">
      <div class="card shadow border-0 h-100">
        <div class="card-body text-center">
          <i class="fa-solid fa-crown fa-2x text-warning mb-2"></i>
          <h5 class="card-title">Producto Estrella</h5>
          <p class="fw-bold"><?= htmlspecialchars($producto_mas_vendido['nombre'] ?? 'N/A') ?></p>
          <small class="text-muted">Cantidad: <?= htmlspecialchars($producto_mas_vendido['total_vendido'] ?? 0) ?></small>
        </div>
      </div>
    </div>
  </div>

  <div class="row mb-4 g-3">
    <div class="col-md-6 col-12">
      <div class="card shadow border-0 h-100">
        <div class="card-body text-center">
          <i class="fa-solid fa-credit-card fa-2x text-primary mb-2"></i>
          <h5 class="card-title">Métodos de Pago</h5>
          <?php if (!empty($metodos_pago)): ?>
            <?php foreach ($metodos_pago as $m): ?>
              <p class="mb-1"><strong><?= htmlspecialchars(ucfirst($m['metodo_pago'])) ?>:</strong> <?= htmlspecialchars($m['total']) ?></p>
            <?php endforeach; ?>
          <?php else: ?>
            <p class="text-muted">Sin registros</p>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="col-md-6 col-12">
      <div class="card shadow border-0 h-100">
        <div class="card-body text-center">
          <i class="fa-solid fa-truck fa-2x text-danger mb-2"></i>
          <h5 class="card-title">Tipos de Entrega</h5>
          <?php if (!empty($tipos_entrega)): ?>
            <?php foreach ($tipos_entrega as $t): ?>
              <p class="mb-1"><strong><?= htmlspecialchars(ucfirst($t['tipo_entrega'])) ?>:</strong> <?= htmlspecialchars($t['total']) ?></p>
            <?php endforeach; ?>
          <?php else: ?>
            <p class="text-muted">Sin registros</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Gráficos -->
  <div class="row g-4">
    <div class="col-md-6 col-12">
      <div class="card shadow border-0">
        <div class="card-header"><i class="fa-solid fa-chart-pie"></i> Productos más vendidos</div>
        <div class="card-body">
          <canvas id="productosChart"></canvas>
        </div>
      </div>
    </div>
    <div class="col-md-6 col-12">
      <div class="card shadow border-0">
        <div class="card-header"><i class="fa-solid fa-chart-bar"></i> Ventas mensuales</div>
        <div class="card-body">
          <canvas id="ventasChart"></canvas>
        </div>
      </div>
    </div>
  </div>

  <!-- Exportaciones -->
  <div class="mt-4 text-center">
    <a href="export_pdf.php?fecha_inicio=<?= urlencode($fecha_inicio) ?>&fecha_fin=<?= urlencode($fecha_fin_input) ?>" class="btn btn-danger me-2">
      <i class="fa-solid fa-file-pdf"></i> Exportar PDF
    </a>
    <a href="export_excel.php?fecha_inicio=<?= urlencode($fecha_inicio) ?>&fecha_fin=<?= urlencode($fecha_fin_input) ?>" class="btn btn-success">
      <i class="fa-solid fa-file-excel"></i> Exportar Excel
    </a>
  </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx1 = document.getElementById('productosChart').getContext('2d');
new Chart(ctx1, {
  type: 'pie',
  data: {
    labels: <?= $productos_labels ?>,
    datasets: [{
      data: <?= $productos_data ?>,
      backgroundColor: ['#FF6384','#36A2EB','#FFCE56','#4BC0C0','#9966FF','#FF9F40','#C9CBCF','#8DD17E','#FF6384','#36A2EB']
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { position: 'bottom' } }
  }
});

const ctx2 = document.getElementById('ventasChart').getContext('2d');
new Chart(ctx2, {
  type: 'bar',
  data: {
    labels: <?= $meses ?>,
    datasets: [{
      label: 'Ventas mensuales',
      data: <?= $ventas_data ?>,
      backgroundColor: '#36A2EB'
    }]
  },
  options: {
    responsive: true,
    scales: { y: { beginAtZero: true } }
  }
});
</script>

<?php include("../../templates/footer.php"); ?>
