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
$publicBaseUrl = piccolo_public_base_url();
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
            <th>Imagen</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($lista_banners as $value) { ?>
            <tr>
              <td><?php echo htmlspecialchars($value['ID'], ENT_QUOTES, 'UTF-8'); ?></td>
              <td><?php echo htmlspecialchars($value['titulo'], ENT_QUOTES, 'UTF-8'); ?></td>
              <td><?php echo htmlspecialchars($value['descripcion'], ENT_QUOTES, 'UTF-8'); ?></td>
              <td><?php echo htmlspecialchars($value['link'], ENT_QUOTES, 'UTF-8'); ?></td>
              <td>
                <?php if (!empty($value['imagen'])) { ?>
                  <img src="<?php echo htmlspecialchars($publicBaseUrl . $value['imagen'], ENT_QUOTES, 'UTF-8'); ?>" alt="Banner" class="img-fluid rounded" style="max-height: 80px; object-fit: cover;">
                <?php } else { ?>
                  <span class="text-muted">Sin imagen</span>
                <?php } ?>
              </td>
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
