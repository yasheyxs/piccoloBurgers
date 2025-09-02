<?php
include("../../bd.php");

// Eliminar compra
if (isset($_GET['txtID'])) {
  $txtID = $_GET["txtID"] ?? "";

  $sentencia = $conexion->prepare("DELETE FROM tbl_compras_detalle WHERE compra_id = :id");
  $sentencia->bindParam(":id", $txtID);
  $sentencia->execute();

  $sentencia = $conexion->prepare("DELETE FROM tbl_compras WHERE ID = :id");
  $sentencia->bindParam(":id", $txtID);
  $sentencia->execute();

  header("Location:index.php");
}

// Obtener compras con proveedor y total
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

// Métricas: gasto mensual y anual
$sentencia = $conexion->prepare("
  SELECT SUM(cd.cantidad * cd.precio_unitario) AS total_mes
  FROM tbl_compras c
  JOIN tbl_compras_detalle cd ON c.ID = cd.compra_id
  WHERE MONTH(c.fecha) = MONTH(CURRENT_DATE()) AND YEAR(c.fecha) = YEAR(CURRENT_DATE())
");
$sentencia->execute();
$total_mes = $sentencia->fetchColumn();

$sentencia = $conexion->prepare("
  SELECT SUM(cd.cantidad * cd.precio_unitario) AS total_anio
  FROM tbl_compras c
  JOIN tbl_compras_detalle cd ON c.ID = cd.compra_id
  WHERE YEAR(c.fecha) = YEAR(CURRENT_DATE())
");
$sentencia->execute();
$total_anio = $sentencia->fetchColumn();

// Proveedores más solicitados
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

// Materias primas más compradas
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
              <td><?= $compra["ID"] ?></td>
              <td><?= $compra["fecha"] ?></td>
              <td><?= $compra["proveedor"] ?></td>
              <td><?= $compra["telefono"] ?></td>
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
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.datatables.net/1.13.2/js/jquery.dataTables.min.js"></script>
<script>
  $(document).ready(function () {
    $('#tablaCompras').DataTable({
      paging: true,
      searching: true,
      info: false,
      lengthChange: true,
      responsive: true,
      fixedHeader: true,
      language: {
        emptyTable: "No hay compras registradas",
        search: "Buscar:",
        lengthMenu: "Mostrar _MENU_ registros",
        paginate: {
          first: "Primero",
          last: "Último",
          next: "Siguiente",
          previous: "Anterior"
        }
      }
    });

    new Chart(document.getElementById('graficoProveedores'), {
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
        scales: {
          y: { beginAtZero: true }
        }
      }
    });

    new Chart(document.getElementById('graficoMaterias'), {
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
        maintainAspectRatio: false
      }
    });
  });
</script>

<?php include("../../templates/footer.php"); ?>
