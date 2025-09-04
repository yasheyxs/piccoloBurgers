<?php
include("../../bd.php");

$menu_id = $_GET['menu_id'] ?? null;
if (!$menu_id) {
  echo "Menú no especificado.";
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $cantidades = $_POST['cantidad'] ?? [];
  $eliminar = $_POST['eliminar'] ?? [];

  foreach ($cantidades as $mp_id => $valor) {
    $cantidad = floatval($valor);

    // Si está marcado para eliminar
    if (in_array($mp_id, $eliminar)) {
      $stmtDelete = $conexion->prepare("DELETE FROM tbl_menu_materias_primas WHERE menu_id = ? AND materia_prima_id = ?");
      $stmtDelete->execute([$menu_id, $mp_id]);
      continue;
    }

    // Verificar si ya existe
    $stmtCheck = $conexion->prepare("SELECT COUNT(*) FROM tbl_menu_materias_primas WHERE menu_id = ? AND materia_prima_id = ?");
    $stmtCheck->execute([$menu_id, $mp_id]);
    $existe = $stmtCheck->fetchColumn();

    if ($cantidad > 0) {
      if ($existe) {
        $stmtUpdate = $conexion->prepare("UPDATE tbl_menu_materias_primas SET cantidad = ? WHERE menu_id = ? AND materia_prima_id = ?");
        $stmtUpdate->execute([$cantidad, $menu_id, $mp_id]);
      } else {
        $stmtInsert = $conexion->prepare("INSERT INTO tbl_menu_materias_primas (menu_id, materia_prima_id, cantidad) VALUES (?, ?, ?)");
        $stmtInsert->execute([$menu_id, $mp_id, $cantidad]);
      }
    }
  }

  header("Location: materias_primas.php?menu_id=$menu_id");
  exit;
}

$stmt = $conexion->prepare("SELECT nombre FROM tbl_menu WHERE ID = ?");
$stmt->execute([$menu_id]);
$menu_nombre = $stmt->fetchColumn();

$stmt = $conexion->prepare("SELECT ID, nombre, unidad_medida FROM tbl_materias_primas ORDER BY nombre ASC");
$stmt->execute();
$materias = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conexion->prepare("
  SELECT materia_prima_id, cantidad 
  FROM tbl_menu_materias_primas 
  WHERE menu_id = ?
");
$stmt->execute([$menu_id]);
$asignados = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

include("../../templates/header.php");
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">Asignar insumos al menú: <strong><?= htmlspecialchars($menu_nombre) ?></strong></h4>
  <a class="btn btn-secondary" href="index.php">← Volver al menú</a>
</div>

<form method="POST">
  <div class="table-responsive">
    <table class="table table-bordered table-sm align-middle">
      <thead class="table-light">
        <tr>
          <th>Materia Prima</th>
          <th>Unidad</th>
          <th>Cantidad por unidad vendida</th>
          <th>Eliminar</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($materias as $mp): ?>
          <?php $valor = $asignados[$mp['ID']] ?? ''; ?>
          <tr>
            <td><?= $mp['nombre'] ?></td>
            <td><?= $mp['unidad_medida'] ?></td>
            <td>
              <input type="number" step="0.01" min="0" name="cantidad[<?= $mp['ID'] ?>]" value="<?= $valor ?>" class="form-control form-control-sm">
            </td>
            <td class="text-center">
              <?php if (isset($asignados[$mp['ID']])): ?>
                <input type="checkbox" name="eliminar[]" value="<?= $mp['ID'] ?>">
              <?php else: ?>
                —
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <button type="submit" class="btn btn-success mt-3">Guardar cambios</button>
</form>

<?php include("../../templates/footer.php"); ?>
