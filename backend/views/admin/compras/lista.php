<?php
/**
 * Historial de compras, gasto del mes y del año, y gráficos.
 *
 * @var array<int, array<string, mixed>> $compras
 * @var array{gasto_mes: float, gasto_anio: float, proveedores: array, materias_primas: array} $resumen
 * @var array{tipo: string, mensaje: string}|null $aviso
 * @var string $url_base
 */
$pesos = static fn(float $monto): string => '$' . number_format($monto, 2, ',', '.');
?>
<br>
<?php include __DIR__ . '/../_aviso.php'; ?>

<div class="card mb-4">
  <div class="card-header">
    <a class="btn btn-primary" href="crear.php" role="button">Registrar nueva compra</a>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table id="tablaCompras" class="table table-bordered table-hover table-sm align-middle w-100">
        <thead class="table-light">
          <tr>
            <th>Fecha</th>
            <th>Proveedor</th>
            <th>Teléfono</th>
            <th>Total</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($compras as $compra): ?>
            <tr>
              <td data-order="<?= piccolo_e($compra['fecha']) ?>"><?= piccolo_e(date('d/m/Y', strtotime($compra['fecha']))) ?></td>
              <td><?= piccolo_e($compra['proveedor'] ?? 'Proveedor eliminado') ?></td>
              <td><?= piccolo_e($compra['telefono'] ?? '—') ?></td>
              <td data-order="<?= $compra['total'] ?>"><?= $pesos($compra['total']) ?></td>
              <td>
                <a class="btn btn-info btn-sm" href="detalle.php?txtID=<?= $compra['id'] ?>">Ver detalles</a>
                <form method="post" action="index.php" class="d-inline" data-confirmar="¿Eliminar esta compra? Lo comprado se va a descontar del stock.">
                  <input type="hidden" name="txtID" value="<?= $compra['id'] ?>">
                  <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="row mb-4">
  <div class="col-md-6">
    <div class="card text-white bg-success">
      <div class="card-body">
        <h5 class="card-title">Gasto mensual</h5>
        <p class="card-text"><?= $pesos($resumen['gasto_mes']) ?></p>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card text-white bg-primary">
      <div class="card-body">
        <h5 class="card-title">Gasto anual</h5>
        <p class="card-text"><?= $pesos($resumen['gasto_anio']) ?></p>
      </div>
    </div>
  </div>
</div>

<div class="card mb-4">
  <div class="card-header">Compras por proveedor</div>
  <div class="card-body">
    <canvas id="graficoProveedores" height="100"></canvas>
  </div>
</div>

<div class="card mb-4">
  <div class="card-header">Materias primas más compradas</div>
  <div class="card-body d-flex justify-content-center">
    <div style="max-width: 350px; width: 100%;">
      <canvas id="graficoMaterias"></canvas>
    </div>
  </div>
</div>

<script id="datosCompras" type="application/json"><?= json_encode($resumen, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="<?= piccolo_e($url_base) ?>assets/js/graficos.js"></script>
<script src="<?= piccolo_e($url_base) ?>assets/js/pages/compras.js"></script>
