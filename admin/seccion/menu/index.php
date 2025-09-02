<?php
include("../../bd.php");

if (isset($_GET['txtID'])) {
    $txtID = $_GET["txtID"] ?? "";

    // Buscar y borrar imagen asociada
    $sentencia = $conexion->prepare("SELECT * FROM `tbl_menu` WHERE ID=:id");
    $sentencia->bindParam(":id", $txtID);
    $sentencia->execute();
    $registro_foto = $sentencia->fetch(PDO::FETCH_LAZY);

    if (isset($registro_foto['foto']) && file_exists("../../../images/menu/" . $registro_foto['foto'])) {
        unlink("../../../images/menu/" . $registro_foto['foto']);
    }

    // Borrar registro en la base de datos
    $sentencia = $conexion->prepare("DELETE FROM tbl_menu WHERE ID=:id");
    $sentencia->bindParam(":id", $txtID);
    $sentencia->execute();

    header("Location:index.php");
}

$sentencia = $conexion->prepare("SELECT * FROM `tbl_menu`");
$sentencia->execute();
$lista_menu = $sentencia->fetchAll(PDO::FETCH_ASSOC);

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

<br>
<div class="card">
  <div class="card-header">
    <a class="btn btn-primary" href="crear.php" role="button">Agregar registros</a>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table id="tablaMenu" class="table table-bordered table-hover table-sm align-middle w-100">
        <thead class="table-light">
          <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Ingredientes</th>
            <th>Foto</th>
            <th>Precio</th>
            <th>Categoría</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($lista_menu as $registro) { ?>
            <tr>
              <td><?php echo $registro["ID"]; ?></td>
              <td><?php echo $registro["nombre"]; ?></td>
              <td><?php echo $registro["ingredientes"]; ?></td>
              <td><img src="../../../images/menu/<?php echo $registro['foto']; ?>" width="50" alt="Foto"></td>
              <td>$<?php echo $registro["precio"]; ?></td>
              <td><?php echo $registro["categoria"]; ?></td>
              <td>
                <a class="btn btn-info btn-sm" href="editar.php?txtID=<?php echo $registro['ID']; ?>">Editar</a>
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
    if ($.fn.DataTable.isDataTable('#tablaMenu')) {
      $('#tablaMenu').DataTable().clear().destroy();
    }

    $('#tablaMenu').DataTable({
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
