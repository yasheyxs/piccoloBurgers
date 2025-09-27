<?php
include("../../bd.php");

// Eliminar materia prima con validación y manejo de errores
if (isset($_GET['txtID']) && is_numeric($_GET['txtID'])) {
  $txtID = intval($_GET["txtID"]);

  try {
    $sentencia = $conexion->prepare("DELETE FROM tbl_materias_primas WHERE ID = :id");
    $sentencia->bindParam(":id", $txtID, PDO::PARAM_INT);
    $sentencia->execute();
    header("Location:index.php");
    exit;
  } catch (Exception $e) {
    error_log("Error al eliminar materia prima: " . $e->getMessage());
    echo "<script>alert('Error al eliminar la materia prima. Intenta nuevamente.');</script>";
  }
}

// Obtener lista de materias primas
try {
  if (isset($_GET['criticos'])) {
    $sentencia = $conexion->prepare("
      SELECT mp.ID, mp.nombre, mp.unidad_medida, mp.cantidad, mp.stock_minimo, p.nombre AS proveedor 
      FROM tbl_materias_primas mp 
      LEFT JOIN tbl_proveedores p ON mp.proveedor_id = p.ID
      WHERE mp.cantidad <= IFNULL(mp.stock_minimo, 1)
    ");
  } else {
    $sentencia = $conexion->prepare("
      SELECT mp.ID, mp.nombre, mp.unidad_medida, mp.cantidad, mp.stock_minimo, p.nombre AS proveedor 
      FROM tbl_materias_primas mp 
      LEFT JOIN tbl_proveedores p ON mp.proveedor_id = p.ID
    ");
  }

  $sentencia->execute();
  $lista_materias = $sentencia->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
  error_log("Error al obtener materias primas: " . $e->getMessage());
  $lista_materias = [];
  echo "<script>alert('Error al cargar la lista de materias primas.');</script>";
}

include("../../templates/header.php");
?>

<style>
  .stock-bajo {
    background-color: #f3e4c2 !important;
    color: #6d5310;
    font-weight: 600;
  }

  .stock-cero {
    background-color: #f4ccd2 !important;
    color: #8a3d47;
    font-weight: 600;
  }

  .stock-ok {
    background-color: #d6ebde !important;
    color: #2f6543;
    font-weight: 500;
  }

  body.admin-dark .stock-bajo {
    background-color: rgba(173, 137, 57, 0.32) !important;
    color: #f3dba2;
  }

  body.admin-dark .stock-cero {
    background-color: rgba(172, 63, 72, 0.33) !important;
    color: #f5b8c1;
  }

  body.admin-dark .stock-ok {
    background-color: rgba(61, 122, 87, 0.32) !important;
    color: #bce6ce;
  }

  .table td, .table th {
    vertical-align: middle;
  }

  .btn-group-filtros {
    display: flex;
    gap: 0.5rem;
  }

  @media (max-width: 576px) {
    .btn-group-filtros {
      flex-direction: column;
      align-items: stretch;
    }
  }
</style>

<br>
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <a class="btn btn-primary" href="crear.php">Agregar materia prima</a>
    <form method="GET" class="btn-group-filtros">
      <button type="submit" name="criticos" value="1" class="btn btn-warning">Ver insumos críticos</button>
      <?php if (isset($_GET['criticos'])): ?>
        <a href="index.php" class="btn btn-secondary">Mostrar todos</a>
      <?php endif; ?>
    </form>
  </div>

  <div class="card-body">
    <div class="table-responsive">
      <table id="tablaMaterias" class="table table-bordered table-hover table-sm w-100">
        <thead class="table-light">
          <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Unidad</th>
            <th>Cantidad</th>
            <th>Proveedor</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php if (count($lista_materias) > 0): ?>
            <?php foreach ($lista_materias as $registro): ?>
              <?php
                $cantidad = floatval($registro["cantidad"]);
                $stock_minimo = floatval($registro["stock_minimo"] ?? 1);
                $clase = ($cantidad == 0) ? 'stock-cero' : (($cantidad <= $stock_minimo) ? 'stock-bajo' : 'stock-ok');
              ?>
              <tr class="<?= $clase ?>">
                <td><?= htmlspecialchars($registro["ID"]) ?></td>
                <td><?= htmlspecialchars($registro["nombre"]) ?></td>
                <td><?= htmlspecialchars($registro["unidad_medida"]) ?></td>
                <td><?= number_format($cantidad, 2) ?></td>
                <td><?= htmlspecialchars($registro["proveedor"] ?? '—') ?></td>
                <td>
                  <a class="btn btn-info btn-sm" href="editar.php?txtID=<?= $registro['ID'] ?>">Editar</a>
                  <a class="btn btn-danger btn-sm" href="index.php?txtID=<?= $registro['ID'] ?>" onclick="return confirm('¿Eliminar esta materia prima?')">Borrar</a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
  <tr>
    <td class="text-center text-muted">—</td>
    <td class="text-center text-muted">—</td>
    <td class="text-center text-muted">—</td>
    <td class="text-center text-muted">—</td>
    <td class="text-center text-muted">—</td>
    <td class="text-center text-muted">No se encontraron materias primas.</td>
  </tr>
<?php endif; ?>

        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- jQuery-->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- CSS de DataTables -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.2/css/jquery.dataTables.min.css">

<script src="https://cdn.datatables.net/1.13.2/js/jquery.dataTables.min.js"></script>
<script>
  $(document).ready(function () {
    $('#tablaMaterias').DataTable({
  paging: true,
  searching: true,
  info: false,
  responsive: true,
  fixedHeader: true,
  pageLength: 10,
  lengthMenu: [10, 25, 50], 
  language: {
    emptyTable: "No hay materias primas registradas",
    search: "Buscar:",
    lengthMenu: "Mostrar registros _MENU_",
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
