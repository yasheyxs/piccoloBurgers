<?php
/**
 * Lista de materias primas con el estado del stock.
 *
 * @var array<int, array<string, mixed>> $materias
 * @var bool $soloCriticos
 * @var array{tipo: string, mensaje: string}|null $aviso
 * @var string $url_base
 */
$claseEstado = ['sin_stock' => 'stock-cero', 'bajo' => 'stock-bajo', 'ok' => 'stock-ok'];
?>
<br>
<?php include __DIR__ . '/../_aviso.php'; ?>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <a class="btn btn-primary" href="crear.php">Agregar materia prima</a>
    <div class="d-flex flex-wrap gap-2">
      <?php if ($soloCriticos): ?>
        <a href="index.php" class="btn btn-secondary">Mostrar todos</a>
      <?php else: ?>
        <a href="index.php?criticos=1" class="btn btn-warning">Ver insumos críticos</a>
      <?php endif; ?>
    </div>
  </div>

  <div class="card-body">
    <div class="table-responsive">
      <table id="tablaMaterias" class="table table-bordered table-hover table-sm align-middle w-100">
        <thead class="table-light">
          <tr>
            <th>Nombre</th>
            <th>Unidad</th>
            <th>Cantidad</th>
            <th>Stock mínimo</th>
            <th>Proveedor</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($materias as $materia): ?>
            <tr class="<?= $claseEstado[$materia['estado_stock']] ?>">
              <td><?= piccolo_e($materia['nombre']) ?></td>
              <td><?= piccolo_e($materia['unidad_medida']) ?></td>
              <td data-order="<?= $materia['cantidad'] ?>"><?= number_format($materia['cantidad'], 2, ',', '.') ?></td>
              <td><?= $materia['stock_minimo'] !== null ? number_format($materia['stock_minimo'], 2, ',', '.') : '—' ?></td>
              <td><?= piccolo_e($materia['proveedor'] ?? '—') ?></td>
              <td>
                <a class="btn btn-info btn-sm" href="editar.php?txtID=<?= $materia['id'] ?>">Editar</a>
                <form method="post" action="index.php" class="d-inline" data-confirmar="¿Eliminar esta materia prima?">
                  <input type="hidden" name="txtID" value="<?= $materia['id'] ?>">
                  <button type="submit" class="btn btn-danger btn-sm">Borrar</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script src="<?= piccolo_e($url_base) ?>assets/js/pages/materias_primas.js"></script>
