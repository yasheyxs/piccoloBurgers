<?php
include("../../bd.php");

if (isset($_GET['txtID'])) {
    $txtID = $_GET["txtID"] ?? "";

    // Eliminar registro de proveedor
    $sentencia = $conexion->prepare("DELETE FROM tbl_proveedores WHERE ID=:id");
    $sentencia->bindParam(":id", $txtID);
    $sentencia->execute();

    header("Location:index.php");
}

$sentencia = $conexion->prepare("SELECT * FROM tbl_proveedores");
$sentencia->execute();
$lista_proveedores = $sentencia->fetchAll(PDO::FETCH_ASSOC);

include("../../templates/header.php");
?>

<br>
<div class="card">
  <div class="card-header">
    <a class="btn btn-primary" href="crear.php" role="button">Agregar proveedor</a>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table id="tablaProveedores" class="table table-bordered table-hover table-sm align-middle w-100">
        <thead class="table-light">
          <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Teléfono</th>
            <th>Email</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($lista_proveedores as $registro) { ?>
            <tr>
              <td><?php echo $registro["ID"]; ?></td>
              <td><?php echo $registro["nombre"]; ?></td>
              <td><?php echo $registro["telefono"]; ?></td>
              <td><?php echo $registro["email"] ?: '—'; ?></td>
              <td>
                <a class="btn btn-info btn-sm" href="editar.php?txtID=<?php echo $registro['ID']; ?>">Editar</a>
                <a class="btn btn-danger btn-sm" href="index.php?txtID=<?php echo $registro['ID']; ?>" onclick="return confirm('¿Estás seguro de eliminar este proveedor?')">Borrar</a>
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
    if ($.fn.DataTable.isDataTable('#tablaProveedores')) {
      $('#tablaProveedores').DataTable().clear().destroy();
    }

    $('#tablaProveedores').DataTable({
      paging: true,
      searching: true,
      info: false,
      lengthChange: true,
      responsive: true,
      fixedHeader: true,
      language: {
        decimal: "",
        emptyTable: "No hay datos disponibles en la tabla",
        info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
        infoEmpty: "Mostrando 0 a 0 de 0 registros",
        infoFiltered: "(filtrado de _MAX_ registros totales)",
        lengthMenu: "Mostrar registros: _MENU_",
        loadingRecords: "Cargando...",
        processing: "Procesando...",
        search: "Buscar:",
        zeroRecords: "No se encontraron registros coincidentes",
        paginate: {
          first: "Primero",
          last: "Último",
          next: "Siguiente",
          previous: "Anterior"
        },
        aria: {
          sortAscending: ": activar para ordenar la columna ascendente",
          sortDescending: ": activar para ordenar la columna descendente"
        }
      }
    });
  });
</script>

<?php include("../../templates/footer.php"); ?>
