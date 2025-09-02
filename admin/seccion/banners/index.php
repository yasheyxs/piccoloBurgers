<?php
include("../../bd.php");

if (isset($_GET['txtID'])) {
  $txtID = $_GET["txtID"] ?? "";

  $sentencia = $conexion->prepare("DELETE FROM tbl_banners WHERE ID=:id");
  $sentencia->bindParam(":id", $txtID);
  $sentencia->execute();

  header("Location:index.php");
}

$sentencia = $conexion->prepare("SELECT * FROM `tbl_banners`");
$sentencia->execute();
$lista_banners = $sentencia->fetchAll(PDO::FETCH_ASSOC);

include("../../templates/header.php");
?>

<style>
  @media (max-width: 576px) {
    .dataTables_wrapper .dataTables_filter,
    .dataTables_wrapper .dataTables_length {
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
    }

    .dataTables_length {
  display: flex;
  flex-direction: row;
  align-items: flex-start;
  gap: 0.25rem;
}

.dataTables_length label {
  font-weight: 500;
}

.dataTables_length select {
  width: auto;
  min-width: 80px;
}


    .dataTables_wrapper .dataTables_filter input,
    .dataTables_wrapper .dataTables_length select {
      width: 100% !important;
    }

    .dataTables_wrapper .dataTables_paginate {
      flex-wrap: wrap;
      justify-content: center;
    }
  }
</style>

<br />
<div class="card">
  <div class="card-header">
    <a class="btn btn-primary" href="crear.php" role="button">Agregar registros</a>
  </div>

  <div class="card-body">
    <div class="table-responsive">
      <table id="miTabla" class="table table-bordered table-hover table-sm align-middle w-100">
        <thead class="table-light">
          <tr>
            <th>ID</th>
            <th>Título</th>
            <th>Descripción</th>
            <th>Enlace</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($lista_banners as $value) { ?>
            <tr>
              <td><?php echo $value['ID']; ?></td>
              <td><?php echo $value['titulo']; ?></td>
              <td><?php echo $value['descripcion']; ?></td>
              <td><?php echo $value['link']; ?></td>
              <td>
                <div class="d-flex flex-wrap gap-1">
                  <a class="btn btn-info btn-sm" href="editar.php?txtID=<?php echo $value['ID']; ?>">Editar</a>
                  <a class="btn btn-danger btn-sm" href="index.php?txtID=<?php echo $value['ID']; ?>">Borrar</a>
                </div>
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
    if ($.fn.DataTable.isDataTable('#miTabla')) {
      $('#miTabla').DataTable().clear().destroy();
    }

    $('#miTabla').DataTable({
      paging: true,
      searching: true,
      info: false,
      lengthChange: true,
      responsive: true,
      fixedHeader: true,
      language: {
  lengthMenu: "Mostrar registros: _MENU_",
  decimal: "",
  emptyTable: "No hay datos disponibles en la tabla",
  info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
  infoEmpty: "Mostrando 0 a 0 de 0 registros",
  infoFiltered: "(filtrado de _MAX_ registros totales)",
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
