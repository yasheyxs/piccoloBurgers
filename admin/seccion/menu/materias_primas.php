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

    if (in_array($mp_id, $eliminar)) {
      $stmtDelete = $conexion->prepare("DELETE FROM tbl_menu_materias_primas WHERE menu_id = ? AND materia_prima_id = ?");
      $stmtDelete->execute([$menu_id, $mp_id]);
      continue;
    }

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

$stmt = $conexion->prepare("
  SELECT mp.ID, mp.nombre, mp.unidad_medida, mp.cantidad AS stock_actual, rel.cantidad AS requerido
  FROM tbl_materias_primas mp
  INNER JOIN tbl_menu_materias_primas rel ON rel.materia_prima_id = mp.ID
  WHERE rel.menu_id = ?
  ORDER BY mp.nombre ASC
");
$stmt->execute([$menu_id]);
$insumos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener materias primas no asignadas aún
$stmt = $conexion->prepare("
  SELECT mp.ID, mp.nombre, mp.unidad_medida, mp.cantidad
  FROM tbl_materias_primas mp
  WHERE mp.ID NOT IN (
    SELECT materia_prima_id FROM tbl_menu_materias_primas WHERE menu_id = ?
  )
  ORDER BY mp.nombre ASC
");
$stmt->execute([$menu_id]);
$materias_disponibles = $stmt->fetchAll(PDO::FETCH_ASSOC);

include("../../templates/header.php");
?>

<style>
  .estado-ok { color: #198754; font-weight: 500; }
  .estado-low { color: #dc3545; font-weight: 500; }
  .table td input[type="number"] { width: 80px; text-align: right; }
  .btn-delete { padding: 0.25rem 0.5rem; font-size: 0.8rem; }
  #buscadorInsumo ul { max-height: 200px; overflow-y: auto; }
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">Insumos para el menú: <strong><?= htmlspecialchars($menu_nombre) ?></strong></h4>
  <a class="btn btn-outline-secondary" href="index.php">← Volver al menú</a>
</div>

<form method="POST">
  <div class="mb-3">
    <button type="button" class="btn btn-outline-primary" onclick="mostrarBuscador()">➕ Agregar insumo</button>
    <div id="buscadorInsumo" class="mt-2" style="display:none;">
      <input type="text" id="inputBuscar" class="form-control" placeholder="Buscar materia prima...">
      <ul id="sugerencias" class="list-group mt-1"></ul>
    </div>
  </div>

  <?php if (count($insumos) === 0): ?>
    <div class="alert alert-warning">Este menú no tiene insumos asignados.</div>
  <?php else: ?>
    <div class="table-responsive">
      <table id="tablaInsumos" class="table table-bordered table-hover table-sm align-middle">
        <thead class="table-light">
          <tr>
            <th>Materia Prima</th>
            <th>Unidad</th>
            <th>Cantidad por unidad</th>
            <th>Stock Actual</th>
            <th>Estado</th>
            <th title="Eliminar insumo">🗑️</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($insumos as $insumo): ?>
            <?php
              $id = $insumo['ID'];
              $requerido = $insumo['requerido'] ?? '';
              $estado = ($requerido > 0 && $insumo['stock_actual'] < $requerido) ? 'estado-low' : 'estado-ok';
              $estado_texto = ($estado === 'estado-low') ? '❌ Bajo' : '✅ OK';
            ?>
            <tr>
              <td><?= $insumo['nombre'] ?></td>
              <td><?= $insumo['unidad_medida'] ?></td>
              <td><input type="number" step="0.01" min="0" name="cantidad[<?= $id ?>]" value="<?= $requerido ?>" class="form-control form-control-sm"></td>
              <td><?= number_format($insumo['stock_actual'], 2) ?></td>
              <td class="<?= $estado ?>"><?= $estado_texto ?></td>
              <td class="text-center">
                <button type="submit" name="eliminar[]" value="<?= $id ?>" class="btn btn-outline-danger btn-sm btn-delete" title="Eliminar insumo">✖</button>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

  <button type="submit" class="btn btn-success mt-3">Guardar cambios</button>
</form>

<script src="https://cdn.datatables.net/1.13.2/js/jquery.dataTables.min.js"></script>
<script>
  $(document).ready(function () {
    $('#tablaInsumos').DataTable({
      pageLength: 10,
      lengthChange: false,
      searching: false,
      info: false,
      ordering: false,
      language: {
        emptyTable: "No hay insumos asignados",
        paginate: {
          first: "Primero",
          last: "Último",
          next: "Siguiente",
          previous: "Anterior"
        }
      }
    });
  });

  const materias = <?= json_encode($materias_disponibles) ?>;
  const tabla = document.querySelector('#tablaInsumos tbody');

  function mostrarBuscador() {
    document.getElementById('buscadorInsumo').style.display = 'block';
    document.getElementById('inputBuscar').focus();
  }

  document.getElementById('inputBuscar').addEventListener('input', function () {
    const query = this.value.toLowerCase();
    const sugerencias = materias.filter(mp => mp.nombre.toLowerCase().includes(query));
    const lista = document.getElementById('sugerencias');
    lista.innerHTML = '';

    sugerencias.forEach(mp => {
      const item = document.createElement('li');
      item.className = 'list-group-item list-group-item-action';
      item.textContent = `${mp.nombre} (${mp.unidad_medida}) - Stock: ${mp.cantidad}`;
      item.onclick = () => agregarFila(mp);
      lista.appendChild(item);
    });
  });

  function agregarFila(mp) {
    const existe = document.querySelector(`input[name="cantidad[${mp.ID}]"]`);
    if (existe) return;

    const fila = document.createElement('tr');
    fila.innerHTML = `
      <td>${mp.nombre}</td>
      <td>${mp.unidad_medida}</td>
      <td><input type="number" step="0.01" min="0" name="cantidad[${mp.ID}]" class="form-control form-control-sm"></td>
      <td>${parseFloat(mp.cantidad).toFixed(2)}</td>
      <td class="text-muted">Nuevo</td>
      <td class="text-center">—</td>
    `;
    tabla.appendChild(fila);
       document.getElementById('inputBuscar').value = '';
    document.getElementById('sugerencias').innerHTML = '';
    document.getElementById('buscadorInsumo').style.display = 'none';
  }
</script>
