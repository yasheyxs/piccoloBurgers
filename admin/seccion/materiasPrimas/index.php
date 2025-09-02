<?php
include("../../bd.php");

if (isset($_GET['txtID'])) {
    $txtID = $_GET["txtID"] ?? "";

    // Eliminar registro
    $sentencia = $conexion->prepare("DELETE FROM tbl_materias_primas WHERE ID=:id");
    $sentencia->bindParam(":id", $txtID);
    $sentencia->execute();

    header("Location:index.php");
}

// Obtener listado de materias primas
$sentencia = $conexion->prepare("SELECT mp.*, p.nombre AS proveedor 
  FROM tbl_materias_primas mp 
  LEFT JOIN tbl_proveedores p ON mp.proveedor_id = p.ID");
$sentencia->execute();
$lista_materias = $sentencia->fetchAll(PDO::FETCH_ASSOC);

include("../../templates/header.php");
?>

<br>
<div class="card">
  <div class="card-header">
    <a class="btn btn-primary" href="crear.php" role="button">Agregar materia prima</a>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table id="tablaMaterias" class="table table-bordered table-hover table-sm align-middle w-100">
        <thead class="table-light">
          <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Unidad de medida</th>
            <th>Cantidad</th>
            <th>Proveedor</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($lista_materias as $registro) { ?>
            <tr>
              <td><?php echo $registro["ID"]; ?></td>
              <td><?php echo $registro["nombre"]; ?></td>
              <td><?php echo $registro["unidad_medida"]; ?></td>
              <td><?php echo $registro["cantidad"]; ?></td>
              <td><?php echo $registro["proveedor"] ?: '—'; ?></td>
              <td>
                <a class="btn btn-info btn-sm" href="editar.php?txtID=<?php echo $registro['ID']; ?>">Editar</a>
                <a class="btn btn-danger btn-sm" href="index.php?txtID=<?php echo $registro['ID']; ?>" onclick="return confirm('¿Eliminar esta materia prima?')">Borrar</a>
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
    if ($.fn.DataTable.isDataTable('#tablaMaterias')) {
      $('#tablaMaterias').DataTable().clear().destroy();
    }

    $('#tablaMaterias').DataTable({
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
