<?php
include("../../bd.php");

if (isset($_GET['txtID'])) {
  $txtID = $_GET["txtID"] ?? "";

  if (is_numeric($txtID)) {
    $sentencia = $conexion->prepare("DELETE FROM tbl_banners WHERE ID=:id");
    $sentencia->bindParam(":id", $txtID);
    $sentencia->execute();
  }

  header("Location:index.php");
  exit;
}

try {
  $sentencia = $conexion->prepare("SELECT * FROM tbl_banners ORDER BY ID DESC");
  $sentencia->execute();
  $lista_banners = $sentencia->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
  error_log("Error al obtener banners: " . $e->getMessage());
  $lista_banners = [];
}

include("../../templates/header.php");
$publicBaseUrl = piccolo_public_base_url();
?>

<br />
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <a class="btn btn-primary" href="crear.php" role="button">Agregar registros</a>
    <span class="text-muted">Mostrar registros</span>
  </div>

  <div class="card-body">
    <div class="table-responsive">
      <table id="miTabla" class="table table-bordered table-hover table-sm align-middle w-100">
        <thead class="table-light">
          <tr>
            <th>Título</th>
            <th>Descripción</th>
            <th>Enlace</th>
            <th>Imagen</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php if (count($lista_banners) > 0): ?>
            <?php foreach ($lista_banners as $value): ?>
              <tr>
                <td><?= htmlspecialchars($value['titulo'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($value['descripcion'], ENT_QUOTES, 'UTF-8') ?></td>
                <td>
                  <?php if (!empty($value['link'])): ?>
                    <a href="<?= htmlspecialchars($value['link'], ENT_QUOTES, 'UTF-8') ?>" target="_blank">
                      <?= htmlspecialchars($value['link'], ENT_QUOTES, 'UTF-8') ?>
                    </a>
                  <?php else: ?>
                    <span class="text-muted">Sin enlace</span>
                  <?php endif; ?>
                </td>
                <td class="text-center">
                  <?php if (!empty($value['imagen'])): ?>
                    <img src="<?= htmlspecialchars($publicBaseUrl . $value['imagen'], ENT_QUOTES, 'UTF-8') ?>"
                         alt="Banner"
                         class="img-fluid rounded"
                         style="max-height: 80px; object-fit: cover;">
                  <?php else: ?>
                    <span class="text-muted">Sin imagen</span>
                  <?php endif; ?>
                </td>
                <td class="text-center">
                  <div class="d-flex justify-content-center gap-1">
                    <a class="btn btn-info btn-sm" href="editar.php?txtID=<?= urlencode($value['ID']) ?>">Editar</a>
                    <a class="btn btn-danger btn-sm"
                       href="index.php?txtID=<?= urlencode($value['ID']) ?>"
                       onclick="return confirm('¿Seguro que deseas borrar este banner?');">
                      Borrar
                    </a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="5" class="text-center text-muted">No hay banners cargados.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card-footer text-muted"></div>
</div>

<script src="https://cdn.datatables.net/1.13.2/js/jquery.dataTables.min.js"></script>
<script>
  $(document).ready(function () {
    $('#miTabla').DataTable({
      pageLength: 10,
      lengthChange: true,
      info: false,
      ordering: false,
      language: {
        emptyTable: "No hay registros disponibles",
        lengthMenu: "Mostrar _MENU_ registros",
        search: "Buscar:",
        paginate: {
          first: "Primero",
          last: "Último",
          next: "Siguiente",
          previous: "Anterior"
        }
      }
    });
  });
</script>

<?php include("../../templates/footer.php"); ?>
