<?php
require_once __DIR__ . '/../../../../src/Http/Admin/acceso.php'; // permisos, sesión y CSRF del panel
include(__DIR__ . "/../../../../src/Core/database.php");

if (isset($_POST['txtID'])) {
    $txtID = $_POST["txtID"] ?? "";

    $sentencia = $conexion->prepare("DELETE FROM tbl_comentarios WHERE ID=:id");
    $sentencia->bindParam(":id", $txtID);
    $sentencia->execute();

    header("Location:index.php");
}

$sentencia = $conexion->prepare("SELECT * FROM `tbl_comentarios`");
$sentencia->execute();
$lista_comentarios = $sentencia->fetchAll(PDO::FETCH_ASSOC);

include(__DIR__ . "/../../../../views/admin/header.php");
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
            <th>Nombre</th>
            <th>Correo</th>
            <th>Mensaje</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($lista_comentarios as $registro) { ?>
            <tr>
              <td><?php echo $registro["nombre"]; ?></td>
              <td><?php echo $registro["correo"]; ?></td>
              <td><?php echo $registro["mensaje"]; ?></td>
              <td>
                <form method="post" action="index.php" class="d-inline" onsubmit="return confirm('¿Seguro que querés borrarlo?')"><input type="hidden" name="txtID" value="<?php echo $registro['ID']; ?>"><button type="submit" class="btn btn-danger btn-sm">Borrar</button></form>
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

<?php include(__DIR__ . "/../../../../views/admin/footer.php"); ?>
