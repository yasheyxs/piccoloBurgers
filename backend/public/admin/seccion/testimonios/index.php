<?php
require_once __DIR__ . '/../../../../src/Http/Admin/acceso.php'; // permisos, sesión y CSRF del panel
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include(__DIR__ . "/../../../../src/Core/database.php");

if (isset($_POST["txtID"])) {
    $txtID = $_POST["txtID"] ?? "";
    $sentencia = $conexion->prepare("DELETE FROM tbl_testimonios WHERE ID=:id");
    $sentencia->bindParam(":id", $txtID);
    $sentencia->execute();

    header("Location:index.php");
}

$sentencia = $conexion->prepare("SELECT * FROM `tbl_testimonios`");
$sentencia->execute();
$lista_testimonios = $sentencia->fetchAll(PDO::FETCH_ASSOC);

include(__DIR__ . "/../../../../views/admin/header.php");
?>

<br />
<div class="card">
  <div class="card-header">
    <a class="btn btn-primary" href="crear.php" role="button">Agregar registros</a>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table id="tablaTestimonios" class="table table-bordered table-hover table-sm align-middle w-100">
        <thead class="table-light">
          <tr>
            <th>Opinión</th>
            <th>Nombre</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($lista_testimonios as $value) { ?>
            <tr>
              <td><?php echo $value['opinion']; ?></td>
              <td><?php echo $value['nombre']; ?></td>
              <td>
                <a class="btn btn-info btn-sm" href="editar.php?txtID=<?php echo $value['ID']; ?>">Editar</a>
                <form method="post" action="index.php" class="d-inline" onsubmit="return confirm('¿Seguro que querés borrarlo?')"><input type="hidden" name="txtID" value="<?php echo $value['ID']; ?>"><button type="submit" class="btn btn-danger btn-sm">Borrar</button></form>
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
    initDataTable('#tablaTestimonios');
  });
</script>

<?php include(__DIR__ . "/../../../../views/admin/footer.php"); ?>
