<?php
/**
 * Receta de un producto: qué materias primas descuenta cada unidad vendida.
 *
 * @var array<string, mixed> $producto
 * @var array<int, array<string, mixed>> $receta  filas actuales (o las enviadas, si hubo errores)
 * @var array<int, array{id: int, nombre: string, unidad_medida: string, cantidad: float}> $materias
 * @var array<string, string> $errores
 * @var array{tipo: string, mensaje: string}|null $aviso
 * @var string $url_base
 */
$invalido = static fn(string $campo): string => isset($errores[$campo]) ? ' is-invalid' : '';
$error = static fn(string $campo): string => '<div class="invalid-feedback">' . piccolo_e($errores[$campo] ?? '') . '</div>';
$porId = array_column($materias, null, 'id');

/** Una fila de la receta. Con $i = '__N__' sirve de molde para las filas que se agregan desde JavaScript. */
$fila = static function ($i, array $item) use ($materias, $porId, $invalido, $error): string {
    $elegida = (int) ($item['materia_prima_id'] ?? 0);
    $stock = $porId[$elegida]['cantidad'] ?? null;
    $cantidad = $item['cantidad'] ?? '';
    $alcanza = $stock !== null && is_numeric($cantidad) ? $stock >= (float) $cantidad : null;
    ob_start(); ?>
    <tr class="fila-receta">
      <td>
        <select class="form-select form-select-sm<?= $invalido("items.$i.materia_prima_id") ?>" name="items[<?= $i ?>][materia_prima_id]" aria-label="Materia prima" required>
          <option value="">Elegir materia prima</option>
          <?php foreach ($materias as $materia): ?>
            <option value="<?= $materia['id'] ?>" <?= $elegida === $materia['id'] ? 'selected' : '' ?>><?= piccolo_e($materia['nombre']) ?> (<?= piccolo_e($materia['unidad_medida']) ?>)</option>
          <?php endforeach; ?>
        </select>
        <?= $error("items.$i.materia_prima_id") ?>
      </td>
      <td style="max-width: 9rem">
        <input type="number" step="0.01" min="0.01" class="form-control form-control-sm text-end<?= $invalido("items.$i.cantidad") ?>"
          name="items[<?= $i ?>][cantidad]" value="<?= piccolo_e($cantidad) ?>" aria-label="Cantidad por unidad vendida" required>
        <?= $error("items.$i.cantidad") ?>
      </td>
      <td class="text-end"><?= $stock !== null ? number_format($stock, 2, ',', '.') : '—' ?></td>
      <td>
        <?php if ($alcanza === true): ?><span class="text-success fw-semibold">OK</span>
        <?php elseif ($alcanza === false): ?><span class="text-danger fw-semibold">No alcanza</span>
        <?php else: ?><span class="text-muted">—</span><?php endif; ?>
      </td>
      <td class="text-center">
        <button type="button" class="btn btn-outline-danger btn-sm quitar-insumo" title="Quitar de la receta" aria-label="Quitar"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
      </td>
    </tr>
    <?php return (string) ob_get_clean();
};
?>
<?php include __DIR__ . '/../_aviso.php'; ?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 mt-3">
  <h4 class="mb-0">Insumos de: <strong><?= piccolo_e($producto['nombre']) ?></strong></h4>
  <a class="btn btn-outline-secondary" href="index.php">← Volver al menú</a>
</div>

<p class="text-muted">Cada vez que se vende una unidad de este producto se descuentan estas cantidades del stock. Si alguna no alcanza, el producto aparece como agotado en el sitio.</p>

<form method="post" novalidate>
  <div class="table-responsive">
    <table class="table table-bordered table-hover table-sm align-middle" data-no-datatable>
      <thead class="table-light">
        <tr>
          <th>Materia prima</th>
          <th>Cantidad por unidad vendida</th>
          <th class="text-end">Stock actual</th>
          <th>Estado</th>
          <th class="text-center" title="Quitar"><i class="fa-solid fa-trash" aria-hidden="true"></i></th>
        </tr>
      </thead>
      <tbody id="receta" data-siguiente="<?= count($receta) ?>">
        <?php foreach (array_values($receta) as $i => $item): ?>
          <?= $fila($i, is_array($item) ? $item : []) ?>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <template id="moldeInsumo"><?= $fila('__N__', []) ?></template>

  <div class="d-flex flex-wrap gap-2">
    <button type="button" class="btn btn-outline-primary" id="agregarInsumo"><i class="fa-solid fa-plus me-2" aria-hidden="true"></i>Agregar insumo</button>
    <button type="submit" class="btn btn-success">Guardar receta</button>
  </div>
</form>

<script src="<?= piccolo_e($url_base) ?>assets/js/pages/receta.js"></script>
