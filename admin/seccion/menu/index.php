<?php
include("../../bd.php");

// Eliminar menú con validación y manejo de errores
if (isset($_GET['txtID']) && is_numeric($_GET['txtID'])) {
  $txtID = intval($_GET["txtID"]);

  try {
    // Obtener foto
    $sentencia = $conexion->prepare("SELECT foto FROM tbl_menu WHERE ID = :id");
    $sentencia->bindParam(":id", $txtID, PDO::PARAM_INT);
    $sentencia->execute();
    $registro_foto = $sentencia->fetch(PDO::FETCH_ASSOC);

    if ($registro_foto && !empty($registro_foto['foto'])) {
      $ruta_foto = "../../../images/menu/" . $registro_foto['foto'];
      if (file_exists($ruta_foto)) {
        unlink($ruta_foto);
      }
    }

    // Eliminar registro
    $sentencia = $conexion->prepare("DELETE FROM tbl_menu WHERE ID = :id");
    $sentencia->bindParam(":id", $txtID, PDO::PARAM_INT);
    $sentencia->execute();

    header("Location:index.php");
    exit;
  } catch (Exception $e) {
    error_log("Error al eliminar menú: " . $e->getMessage());
    echo "<script>alert('Error al eliminar el menú. Intenta nuevamente.');</script>";
  }
}

// Obtener lista de menú con manejo de errores
$lista_menu = [];
try {
  $sentencia = $conexion->prepare("
    SELECT m.*, 
      (SELECT COUNT(*) FROM tbl_menu_materias_primas WHERE menu_id = m.ID) AS insumos_asociados
    FROM tbl_menu m
  ");
  $sentencia->execute();
  $lista_menu = $sentencia->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
  error_log("Error al obtener lista de menú: " . $e->getMessage());
  echo "<script>alert('Error al cargar los registros de menú.');</script>";
}

include("../../templates/header.php");
?>

<br>
<div class="card">
  <div class="card-header">
    <a class="btn btn-primary" href="crear.php">Agregar registros</a>
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
            <th>Insumos</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php if (count($lista_menu) > 0): ?>
            <?php foreach ($lista_menu as $registro): ?>
              <tr>
                <td><?= htmlspecialchars($registro["ID"]) ?></td>
                <td><?= htmlspecialchars($registro["nombre"]) ?></td>
                <td><?= htmlspecialchars($registro["ingredientes"]) ?></td>
                <td>
                  <?php if (!empty($registro['foto']) && file_exists("../../../images/menu/" . $registro['foto'])): ?>
                    <img src="../../../images/menu/<?= htmlspecialchars($registro['foto']) ?>" width="50" alt="Foto">
                  <?php else: ?>
                    <span class="text-muted">Sin foto</span>
                  <?php endif; ?>
                </td>
                <td>$<?= number_format($registro["precio"], 0) ?></td>
                <td><?= htmlspecialchars($registro["categoria"]) ?></td>
                <td>
                  <?php if ($registro["insumos_asociados"] > 0): ?>
                    <?= $registro["insumos_asociados"] ?> insumos
                  <?php else: ?>
                    <span class="text-danger">Sin insumos</span>
                  <?php endif; ?>
                </td>
                <td class="d-flex flex-wrap gap-1 justify-content-center">
                  <a class="btn btn-secondary btn-sm" href="materias_primas.php?menu_id=<?= $registro['ID'] ?>">Insumos</a>
                  <a class="btn btn-info btn-sm" href="editar.php?txtID=<?= $registro['ID'] ?>">Editar</a>
                  <a class="btn btn-danger btn-sm" href="index.php?txtID=<?= $registro['ID'] ?>" onclick="return confirm('¿Eliminar este menú?')">Borrar</a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="8" class="text-center text-muted">No hay registros de menú disponibles.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script src="https://cdn.datatables.net/1.13.2/js/jquery.dataTables.min.js"></script>
<script>
  $(document).ready(function () {
    $('#tablaMenu').DataTable({
      paging: true,
      searching: true,
      info: false,
      responsive: true,
      fixedHeader: true,
      language: {
        emptyTable: "No hay registros de menú",
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
