<?php
/**
 * Formulario para registrar una compra con una o más materias primas.
 *
 * @var array{fecha: string, proveedor_id: mixed, items: array<int, array<string, mixed>>} $compra
 * @var array<int, array{id: int, nombre: string, telefono: string}> $proveedores
 * @var array<int, array{id: int, nombre: string, unidad_medida: string}> $materias
 * @var array<string, string> $errores  claves como "fecha", "proveedor_id" o "items.0.cantidad"
 * @var array{tipo: string, mensaje: string}|null $aviso
 * @var string $url_base
 */
$invalido = static fn(string $campo): string => isset($errores[$campo]) ? ' is-invalid' : '';
$error = static fn(string $campo): string => '<div class="invalid-feedback">' . piccolo_e($errores[$campo] ?? '') . '</div>';
$items = $compra['items'] ?: [['materia_prima_id' => '', 'cantidad' => '', 'precio_unitario' => '']];

/** Una fila de materia prima. Con $i = '__N__' sirve de molde para las filas que se agregan desde JavaScript. */
$fila = static function ($i, array $item) use ($materias, $invalido, $error): string {
    ob_start(); ?>
    <div class="row g-2 mb-2 fila-materia">
      <div class="col-md-5">
        <select class="form-select<?= $invalido("items.$i.materia_prima_id") ?>" name="items[<?= $i ?>][materia_prima_id]" aria-label="Materia prima" required>
          <option value="">Seleccionar materia prima</option>
          <?php foreach ($materias as $materia): ?>
            <option value="<?= $materia['id'] ?>" <?= (string) ($item['materia_prima_id'] ?? '') === (string) $materia['id'] ? 'selected' : '' ?>>
              <?= piccolo_e($materia['nombre']) ?> (<?= piccolo_e($materia['unidad_medida']) ?>)
            </option>
          <?php endforeach; ?>
        </select>
        <?= $error("items.$i.materia_prima_id") ?>
      </div>
      <div class="col-md-3">
        <input type="number" step="0.01" min="0.01" class="form-control<?= $invalido("items.$i.cantidad") ?>" name="items[<?= $i ?>][cantidad]"
          value="<?= piccolo_e($item['cantidad'] ?? '') ?>" placeholder="Cantidad" aria-label="Cantidad" required>
        <?= $error("items.$i.cantidad") ?>
      </div>
      <div class="col-md-3">
        <input type="number" step="0.01" min="0.01" class="form-control<?= $invalido("items.$i.precio_unitario") ?>" name="items[<?= $i ?>][precio_unitario]"
          value="<?= piccolo_e($item['precio_unitario'] ?? '') ?>" placeholder="Precio unitario" aria-label="Precio unitario" required>
        <?= $error("items.$i.precio_unitario") ?>
      </div>
      <div class="col-md-1 d-flex align-items-start">
        <button type="button" class="btn btn-danger btn-sm mt-1 quitar-materia" aria-label="Quitar"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
      </div>
    </div>
    <?php return (string) ob_get_clean();
};
?>
<br>
<?php include __DIR__ . '/../_aviso.php'; ?>

<div class="card">
  <div class="card-header">Registrar compra</div>
  <div class="card-body">
    <form method="post" novalidate>
      <div class="row">
        <div class="col-md-4 mb-3">
          <label for="fecha" class="form-label">Fecha de compra:</label>
          <input type="date" class="form-control<?= $invalido('fecha') ?>" name="fecha" id="fecha" max="<?= date('Y-m-d') ?>"
            value="<?= piccolo_e($compra['fecha']) ?>" required>
          <?= $error('fecha') ?>
        </div>
        <div class="col-md-8 mb-3">
          <label for="proveedor_id" class="form-label">Proveedor:</label>
          <select class="form-select<?= $invalido('proveedor_id') ?>" name="proveedor_id" id="proveedor_id" required>
            <option value="">Seleccionar proveedor</option>
            <?php foreach ($proveedores as $proveedor): ?>
              <option value="<?= $proveedor['id'] ?>" <?= (string) $compra['proveedor_id'] === (string) $proveedor['id'] ? 'selected' : '' ?>>
                <?= piccolo_e($proveedor['nombre']) ?> (<?= piccolo_e($proveedor['telefono']) ?>)
              </option>
            <?php endforeach; ?>
          </select>
          <?= $error('proveedor_id') ?>
        </div>
      </div>

      <hr>
      <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
        <h5 class="mb-0 flex-grow-1">Materias primas</h5>
        <button type="button" class="btn btn-secondary" id="agregarMateria">
          <span class="fw-semibold">+</span> Agregar materia prima
        </button>
      </div>
      <?php if (isset($errores['items'])): ?>
        <div class="alert alert-warning py-2"><?= piccolo_e($errores['items']) ?></div>
      <?php endif; ?>

      <div id="materias" data-siguiente="<?= count($items) ?>">
        <?php foreach (array_values($items) as $i => $item): ?>
          <?= $fila($i, is_array($item) ? $item : []) ?>
        <?php endforeach; ?>
      </div>
      <template id="moldeMateria"><?= $fila('__N__', []) ?></template>

      <br>
      <button type="submit" class="btn btn-success">Registrar compra</button>
      <a class="btn btn-primary" href="index.php" role="button">Cancelar</a>
    </form>
  </div>
</div>

<script src="<?= piccolo_e($url_base) ?>assets/js/pages/compra_formulario.js"></script>
