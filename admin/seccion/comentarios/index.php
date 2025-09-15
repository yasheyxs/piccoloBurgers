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

<script>
  document.addEventListener('DOMContentLoaded', function () {
    initDataTable('#tablaComentarios');
  });
</script>

<?php include("../../templates/footer.php"); ?>
