<?php
include("../../bd.php");

if (isset($_GET['txtID'])) {
    $txtID = $_GET["txtID"] ?? "";

    $sentencia = $conexion->prepare("SELECT foto FROM tbl_menu WHERE ID=:id");
    $sentencia->bindParam(":id", $txtID);
    $sentencia->execute();
    $registro_foto = $sentencia->fetch(PDO::FETCH_ASSOC);

    if ($registro_foto && file_exists("../../../images/menu/" . $registro_foto['foto'])) {
        unlink("../../../images/menu/" . $registro_foto['foto']);
    }

    $sentencia = $conexion->prepare("DELETE FROM tbl_menu WHERE ID=:id");
    $sentencia->bindParam(":id", $txtID);
    $sentencia->execute();

    header("Location:index.php");
}

$sentencia = $conexion->prepare("
  SELECT m.*, 
    (SELECT COUNT(*) FROM tbl_menu_materias_primas WHERE menu_id = m.ID) AS insumos_asociados
  FROM tbl_menu m
");
$sentencia->execute();
$lista_menu = $sentencia->fetchAll(PDO::FETCH_ASSOC);

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
          <?php foreach ($lista_menu as $registro): ?>
            <tr>
              <td><?= $registro["ID"] ?></td>
              <td><?= $registro["nombre"] ?></td>
              <td><?= $registro["ingredientes"] ?></td>
              <td><img src="../../../images/menu/<?= $registro['foto'] ?>" width="50" alt="Foto"></td>
              <td>$<?= number_format($registro["precio"], 0) ?></td>
              <td><?= $registro["categoria"] ?></td>
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
