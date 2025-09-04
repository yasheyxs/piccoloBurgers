<?php
include("../../bd.php");

if (isset($_GET['txtID'])) {
    $txtID = $_GET["txtID"] ?? "";
    $sentencia = $conexion->prepare("DELETE FROM tbl_materias_primas WHERE ID=:id");
    $sentencia->bindParam(":id", $txtID);
    $sentencia->execute();
    header("Location:index.php");
}

if (isset($_GET['criticos'])) {
    $sentencia = $conexion->prepare("
        SELECT mp.*, p.nombre AS proveedor 
        FROM tbl_materias_primas mp 
        LEFT JOIN tbl_proveedores p ON mp.proveedor_id = p.ID
        WHERE mp.cantidad <= IFNULL(mp.stock_minimo, 1)
    ");
} else {
    $sentencia = $conexion->prepare("
        SELECT mp.*, p.nombre AS proveedor 
        FROM tbl_materias_primas mp 
        LEFT JOIN tbl_proveedores p ON mp.proveedor_id = p.ID
    ");
}
$sentencia->execute();
$lista_materias = $sentencia->fetchAll(PDO::FETCH_ASSOC);

include("../../templates/header.php");
?>

<style>
  .stock-bajo {
    background-color: #fff3cd !important;
    color: #856404;
    font-weight: bold;
  }

  .stock-cero {
    background-color: #f8d7da !important;
    color: #721c24;
    font-weight: bold;
  }

  .stock-ok {
    background-color: #d4edda !important;
    color: #155724;
  }

  .table td, .table th {
    vertical-align: middle;
  }

  .btn-group-filtros {
    display: flex;
    gap: 0.5rem;
  }

  @media (max-width: 576px) {
    .btn-group-filtros {
      flex-direction: column;
      align-items: stretch;
    }
  }
</style>

<br>
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <a class="btn btn-primary" href="crear.php">Agregar materia prima</a>
    <form method="GET" class="btn-group-filtros">
      <button type="submit" name="criticos" value="1" class="btn btn-warning">Ver insumos críticos</button>
      <?php if (isset($_GET['criticos'])): ?>
        <a href="index.php" class="btn btn-secondary">Mostrar todos</a>
      <?php endif; ?>
    </form>
  </div>

  <div class="card-body">
    <div class="table-responsive">
      <table id="tablaMaterias" class="table table-bordered table-hover table-sm w-100">
        <thead class="table-light">
          <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Unidad</th>
            <th>Cantidad</th>
            <th>Proveedor</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($lista_materias as $registro): ?>
            <?php
              $cantidad = floatval($registro["cantidad"]);
              $stock_minimo = floatval($registro["stock_minimo"] ?? 1);
              $clase = ($cantidad == 0) ? 'stock-cero' : (($cantidad <= $stock_minimo) ? 'stock-bajo' : 'stock-ok');
            ?>
            <tr class="<?= $clase ?>">
              <td><?= $registro["ID"] ?></td>
              <td><?= $registro["nombre"] ?></td>
              <td><?= $registro["unidad_medida"] ?></td>
              <td><?= number_format($cantidad, 2) ?></td>
              <td><?= $registro["proveedor"] ?: '—' ?></td>
              <td>
                <a class="btn btn-info btn-sm" href="editar.php?txtID=<?= $registro['ID'] ?>">Editar</a>
                <a class="btn btn-danger btn-sm" href="index.php?txtID=<?= $registro['ID'] ?>" onclick="return confirm('¿Eliminar esta materia prima?')">Borrar</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script src="https://cdn.datatables.net/1.13.2/js/jquery.dataTables.min.js"></script>
<script>
  $(document).ready(function () {
    $('#tablaMaterias').DataTable({
      paging: true,
      searching: true,
      info: false,
      responsive: true,
      fixedHeader: true,
      language: {
        emptyTable: "No hay materias primas registradas",
        search: "Buscar:",
        paginate: {
          first: "Primero",
          last: "Último",
          next: "Siguiente",
          previous: "Anterior"
        }
      }
    });
  });
</script>

<?php include("../../templates/footer.php"); ?>
