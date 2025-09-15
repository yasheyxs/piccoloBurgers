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

<script>
  document.addEventListener('DOMContentLoaded', function () {
    initDataTable('#miTabla');
  });
</script>

<?php include("../../templates/footer.php"); ?>
