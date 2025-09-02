<?php
include("../../bd.php");

// Eliminar compra (opcional)
if (isset($_GET['txtID'])) {
  $txtID = $_GET["txtID"] ?? "";

  // Eliminar detalles primero
  $sentencia = $conexion->prepare("DELETE FROM tbl_compras_detalle WHERE compra_id = :id");
  $sentencia->bindParam(":id", $txtID);
  $sentencia->execute();

  // Eliminar compra
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

include("../../templates/header.php");
?>

<br>
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
  <div class="card-footer text-muted"></div>
</div>

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.2/js/jquery.dataTables.min.js"></script>
<script>
  $(document).ready(function () {
    if ($.fn.DataTable.isDataTable('#tablaCompras')) {
      $('#tablaCompras').DataTable().clear().destroy();
    }

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
  });
</script>

<?php include("../../templates/footer.php"); ?>
