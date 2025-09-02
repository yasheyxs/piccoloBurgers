<?php
include("../../bd.php");

// Manejo de fechas
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');

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
    FROM tbl_pedidos
    WHERE fecha BETWEEN :inicio AND :fin");
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
LIMIT 1
");
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

// Datos para Chart.js
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

  <!-- Filtros -->
  <form method="get" class="row g-2 mb-4 justify-content-center">
    <div class="col-12 col-md-auto">
      <label class="form-label">Desde:</label>
      <input type="date" class="form-control" name="fecha_inicio" value="<?= $fecha_inicio ?>">
    </div>
    <div class="col-12 col-md-auto">
      <label class="form-label">Hasta:</label>
      <input type="date" class="form-control" name="fecha_fin" value="<?= $fecha_fin ?>">
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
          <p class="fs-4 fw-bold text-success">$<?= number_format($total_ventas,2) ?></p>
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
          <p class="fw-bold"><?= $producto_mas_vendido['nombre'] ?? 'N/A' ?></p>
          <small class="text-muted">Cantidad: <?= $producto_mas_vendido['total_vendido'] ?? 0 ?></small>
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
    <a href="export_pdf.php?fecha_inicio=<?= $fecha_inicio ?>&fecha_fin=<?= $fecha_fin ?>" class="btn btn-danger me-2"><i class="fa-solid fa-file-pdf"></i> Exportar PDF</a>
    <a href="export_excel.php?fecha_inicio=<?= $fecha_inicio ?>&fecha_fin=<?= $fecha_fin ?>" class="btn btn-success"><i class="fa-solid fa-file-excel"></i> Exportar Excel</a>
  </div>

</div>

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
