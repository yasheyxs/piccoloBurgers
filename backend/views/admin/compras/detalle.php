<?php
/**
 * Detalle de una compra.
 *
 * @var array<string, mixed> $compra  con sus ítems y el total
 */
$pesos = static fn(float $monto): string => '$' . number_format($monto, 2, ',', '.');
$numero = static fn(float $valor): string => rtrim(rtrim(number_format($valor, 2, ',', '.'), '0'), ',');
?>
<br>
<div class="card">
  <div class="card-header"><strong>Detalles de compra</strong></div>
  <div class="card-body">
    <p><strong>Fecha:</strong> <?= piccolo_e(date('d/m/Y', strtotime($compra['fecha']))) ?></p>
    <p><strong>Proveedor:</strong> <?= piccolo_e($compra['proveedor'] ?? 'Proveedor eliminado') ?>
      <?php if ($compra['telefono']): ?>(<?= piccolo_e($compra['telefono']) ?>)<?php endif; ?></p>

    <div class="table-responsive">
      <table class="table table-bordered table-hover table-sm align-middle w-100" data-no-datatable>
        <thead class="table-light">
          <tr>
            <th>Materia prima</th>
            <th>Cantidad</th>
            <th>Precio unitario</th>
            <th>Subtotal</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($compra['items'] as $item): ?>
            <tr>
              <td><?= piccolo_e($item['materia_prima'] ?? 'Materia prima eliminada') ?></td>
              <td><?= $numero($item['cantidad']) ?> <?= piccolo_e($item['unidad_medida'] ?? '') ?></td>
              <td><?= $pesos($item['precio_unitario']) ?></td>
              <td><?= $pesos($item['subtotal']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr>
            <th colspan="3" class="text-end">Total:</th>
            <th><?= $pesos($compra['total']) ?></th>
          </tr>
        </tfoot>
      </table>
    </div>
    <a class="btn btn-primary" href="index.php" role="button">Volver al historial</a>
  </div>
</div>
