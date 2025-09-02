<?php
include("../../bd.php");

if (isset($_GET['txtID'])) {
    $txtID = $_GET["txtID"] ?? "";

    $sentencia = $conexion->prepare("DELETE FROM tbl_comentarios WHERE ID=:id");
    $sentencia->bindParam(":id", $txtID);
    $sentencia->execute();

    header("Location:index.php");
}

$sentencia = $conexion->prepare("SELECT * FROM `tbl_comentarios`");
$sentencia->execute();
$lista_comentarios = $sentencia->fetchAll(PDO::FETCH_ASSOC);

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

    .dataTables_wrapper .dataTables_filter input,
    .dataTables_wrapper .dataTables_length select {
      width: 100% !important;
    }

    .dataTables_wrapper .dataTables_paginate {
      flex-wrap: wrap;
      justify-content: center;
    }

    .dataTables_length label {
      font-weight: 500;
      display: flex;
      flex-direction: column;
      gap: 0.25rem;
    }
  }

  @media (min-width: 576px) {
    .dataTables_length label {
      display: flex;
      flex-direction: row;
      align-items: center;
      gap: 0.5rem;
    }
  }
</style>

<br />
<div class="card">
  <div class="card-header">
    Bandeja de comentarios
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table id="tablaComentarios" class="table table-bordered table-hover table-sm align-middle w-100">
        <thead class="table-light">
          <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Correo</th>
            <th>Mensaje</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($lista_comentarios as $registro) { ?>
            <tr>
              <td><?php echo $registro["ID"]; ?></td>
              <td><?php echo $registro["nombre"]; ?></td>
              <td><?php echo $registro["correo"]; ?></td>
              <td><?php echo $registro["mensaje"]; ?></td>
              <td>
                <a class="btn btn-danger btn-sm" href="index.php?txtID=<?php echo $registro['ID']; ?>">Borrar</a>
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
    if ($.fn.DataTable.isDataTable('#tablaComentarios')) {
      $('#tablaComentarios').DataTable().clear().destroy();
    }

    $('#tablaComentarios').DataTable({
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
