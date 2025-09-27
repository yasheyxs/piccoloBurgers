<?php
include("../../bd.php");

// Eliminar compra con validación y manejo de errores
if (isset($_GET['txtID']) && is_numeric($_GET['txtID'])) {
  $txtID = intval($_GET["txtID"]);

  try {
    $conexion->beginTransaction();

    $sentencia = $conexion->prepare("DELETE FROM tbl_compras_detalle WHERE compra_id = :id");
    $sentencia->bindParam(":id", $txtID, PDO::PARAM_INT);
    $sentencia->execute();

    $sentencia = $conexion->prepare("DELETE FROM tbl_compras WHERE ID = :id");
    $sentencia->bindParam(":id", $txtID, PDO::PARAM_INT);
    $sentencia->execute();

    $conexion->commit();
    header("Location:index.php");
    exit;
  } catch (Exception $e) {
    $conexion->rollBack();
    error_log("Error al eliminar compra: " . $e->getMessage());
    echo "<script>alert('Error al eliminar la compra. Intenta nuevamente.');</script>";
  }
}

// Obtener compras con proveedor y total
try {
  $sentencia = $conexion->prepare("
    SELECT c.ID, c.fecha, p.nombre AS proveedor, p.telefono,
      (SELECT SUM(cd.cantidad * cd.precio_unitario)
       FROM tbl_compras_detalle cd
       WHERE cd.compra_id = c.ID) AS total
    FROM tbl_compras c
    JOIN tbl_proveedores p ON c.proveedor_id = p.ID
    ORDER BY c.fecha DESC
  ");
  $sentencia->execute();
  $lista_compras = $sentencia->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
  error_log("Error al obtener lista de compras: " . $e->getMessage());
  $lista_compras = [];
}

// Métricas: gasto mensual y anual
try {
  $sentencia = $conexion->prepare("
    SELECT SUM(cd.cantidad * cd.precio_unitario) AS total_mes
    FROM tbl_compras c
    JOIN tbl_compras_detalle cd ON c.ID = cd.compra_id
    WHERE MONTH(c.fecha) = MONTH(CURRENT_DATE()) AND YEAR(c.fecha) = YEAR(CURRENT_DATE())
  ");
  $sentencia->execute();
  $total_mes = $sentencia->fetchColumn() ?: 0;
} catch (Exception $e) {
  error_log("Error al calcular gasto mensual: " . $e->getMessage());
  $total_mes = 0;
}

try {
  $sentencia = $conexion->prepare("
    SELECT SUM(cd.cantidad * cd.precio_unitario) AS total_anio
    FROM tbl_compras c
    JOIN tbl_compras_detalle cd ON c.ID = cd.compra_id
    WHERE YEAR(c.fecha) = YEAR(CURRENT_DATE())
  ");
  $sentencia->execute();
  $total_anio = $sentencia->fetchColumn() ?: 0;
} catch (Exception $e) {
  error_log("Error al calcular gasto anual: " . $e->getMessage());
  $total_anio = 0;
}

// Proveedores más solicitados
try {
  $sentencia = $conexion->prepare("
    SELECT p.nombre AS proveedor, SUM(cd.cantidad * cd.precio_unitario) AS total
    FROM tbl_compras c
    JOIN tbl_proveedores p ON c.proveedor_id = p.ID
    JOIN tbl_compras_detalle cd ON c.ID = cd.compra_id
    GROUP BY p.nombre
    ORDER BY total DESC
    LIMIT 5
  ");
  $sentencia->execute();
  $proveedores = $sentencia->fetchAll(PDO::FETCH_ASSOC);
  $labels_prov = json_encode(array_column($proveedores, 'proveedor'));
  $totales_prov = json_encode(array_map('floatval', array_column($proveedores, 'total')));
} catch (Exception $e) {
  error_log("Error al obtener proveedores: " . $e->getMessage());
  $labels_prov = json_encode([]);
  $totales_prov = json_encode([]);
}

// Materias primas más compradas
try {
  $sentencia = $conexion->prepare("
    SELECT mp.nombre AS materia_prima, SUM(cd.cantidad) AS total_cantidad
    FROM tbl_compras_detalle cd
    JOIN tbl_materias_primas mp ON cd.materia_prima_id = mp.ID
    GROUP BY mp.nombre
    ORDER BY total_cantidad DESC
    LIMIT 5
  ");
  $sentencia->execute();
  $materias = $sentencia->fetchAll(PDO::FETCH_ASSOC);
  $labels_mat = json_encode(array_column($materias, 'materia_prima'));
  $cantidades_mat = json_encode(array_map('intval', array_column($materias, 'total_cantidad')));
} catch (Exception $e) {
  error_log("Error al obtener materias primas: " . $e->getMessage());
  $labels_mat = json_encode([]);
  $cantidades_mat = json_encode([]);
}

include("../../templates/header.php");
?>

<!-- Tabla de compras -->
<div class="card">
  <div class="card-header">
    <a class="btn btn-primary" href="crear.php" role="button">Registrar nueva compra</a>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table id="tablaCompras" class="table table-bordered table-hover table-sm align-middle w-100">
        <thead class="table-light">
          <tr>
            <th>ID</th>
            <th>Fecha</th>
            <th>Proveedor</th>
            <th>Teléfono</th>
            <th>Total</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($lista_compras as $compra) { ?>
            <tr>
              <td><?= htmlspecialchars($compra["ID"]) ?></td>
              <td><?= htmlspecialchars($compra["fecha"]) ?></td>
              <td><?= htmlspecialchars($compra["proveedor"]) ?></td>
              <td><?= htmlspecialchars($compra["telefono"]) ?></td>
              <td>$<?= number_format($compra["total"], 2) ?></td>
              <td>
                <a class="btn btn-info btn-sm" href="detalle.php?txtID=<?= $compra['ID'] ?>">Ver detalles</a>
                <a class="btn btn-danger btn-sm" href="index.php?txtID=<?= $compra['ID'] ?>" onclick="return confirm('¿Eliminar esta compra?')">Eliminar</a>
              </td>
            </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Métricas -->
<div class="row mb-4">
  <div class="col-md-6">
    <div class="card text-white bg-success">
      <div class="card-body">
        <h5 class="card-title">Gasto mensual</h5>
        <p class="card-text">$<?= number_format($total_mes, 2) ?></p>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card text-white bg-primary">
      <div class="card-body">
        <h5 class="card-title">Gasto anual</h5>
        <p class="card-text">$<?= number_format($total_anio, 2) ?></p>
      </div>
    </div>
  </div>
</div>

<!-- Gráfico de proveedores -->
<div class="card mb-4">
  <div class="card-header">Compras por proveedor</div>
  <div class="card-body">
    <canvas id="graficoProveedores" height="100"></canvas>
  </div>
</div>

<!-- Gráfico de materias primas -->
<div class="card mb-4">
  <div class="card-header">Materias primas más compradas</div>
  <div class="card-body d-flex justify-content-center">
    <div style="max-width: 350px; width: 100%;">
      <canvas id="graficoMaterias"></canvas>
    </div>
  </div>
</div>

<!-- Scripts -->
<!-- jQuery: debe ir primero -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- DataTables -->
<script src="https://cdn.datatables.net/1.13.2/js/jquery.dataTables.min.js"></script>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
  const chartRegistry = [];

  function getChartThemeColors() {
    const isDark = document.body.classList.contains('admin-dark');
    return {
      text: isDark ? '#e8ecf4' : '#212529',
      grid: isDark ? 'rgba(232, 236, 244, 0.18)' : 'rgba(33, 37, 41, 0.12)'
    };
  }

  function applyChartTheme(chart) {
    const { text, grid } = getChartThemeColors();

    if (chart.options.plugins?.legend?.labels) {
      chart.options.plugins.legend.labels.color = text;
    }

    if (chart.options.plugins?.title) {
      chart.options.plugins.title.color = text;
    }

    if (chart.options.scales) {
      Object.values(chart.options.scales).forEach(axis => {
        if (axis.ticks) {
          axis.ticks.color = text;
        }
        if (axis.grid) {
          axis.grid.color = grid;
        }
      });
    }

    chart.update('none');
  }

  function registerChart(chart) {
    chartRegistry.push(chart);
    applyChartTheme(chart);
  }

  document.addEventListener('theme:changed', function () {
    chartRegistry.forEach(applyChartTheme);
  });

  $(document).ready(function () {
    $('#tablaCompras').DataTable({
      paging: true,
      searching: true,
      info: false,
      lengthChange: true,
      responsive: true,
      fixedHeader: true,
      pageLength: 5,
      lengthMenu: [5, 10, 25, 50],
      language: {
        emptyTable: "No hay compras registradas",
        search: "Buscar:",
        lengthMenu: "Mostrar registros _MENU_",
        paginate: {
          first: "Primero",
          last: "Último",
          next: "Siguiente",
          previous: "Anterior"
        }
      }
    });

    const graficoProveedores = new Chart(document.getElementById('graficoProveedores'), {
      type: 'bar',
      data: {
        labels: <?= $labels_prov ?>,
        datasets: [{
          label: 'Compras por proveedor',
          data: <?= $totales_prov ?>,
          backgroundColor: 'rgba(54, 162, 235, 0.6)'
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            labels: {
              color: '#212529'
            }
          }
        },
        scales: {
          x: {
            ticks: {
              color: '#212529'
            },
            grid: {
              color: 'rgba(33, 37, 41, 0.12)'
            }
          },
          y: {
            beginAtZero: true,
            ticks: {
              color: '#212529'
            },
            grid: {
              color: 'rgba(33, 37, 41, 0.12)'
            }
          }
        }
      }
    });

    const graficoMaterias = new Chart(document.getElementById('graficoMaterias'), {
      type: 'pie',
      data: {
        labels: <?= $labels_mat ?>,
        datasets: [{
          label: 'Materias primas más compradas',
          data: <?= $cantidades_mat ?>,
          backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF']
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              color: '#212529'
            }
          }
        }
      }
    });
    registerChart(graficoProveedores);
    registerChart(graficoMaterias);
  });
</script>

<?php include("../../templates/footer.php"); ?>
