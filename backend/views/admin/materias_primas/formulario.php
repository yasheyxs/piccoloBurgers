<?php
/**
 * Formulario para agregar o editar una materia prima.
 *
 * @var string $titulo
 * @var string $boton
 * @var array<string, mixed> $materia
 * @var array<int, array{id: int, nombre: string}> $proveedores
 * @var array<string, string> $errores
 * @var array{tipo: string, mensaje: string}|null $aviso
 */
$invalido = static fn(string $campo): string => isset($errores[$campo]) ? ' is-invalid' : '';
$error = static fn(string $campo): string => '<div class="invalid-feedback">' . piccolo_e($errores[$campo] ?? '') . '</div>';
$proveedorElegido = (int) ($materia['proveedor_id'] ?? 0);
?>
<br>
<?php include __DIR__ . '/../_aviso.php'; ?>

<div class="card">
  <div class="card-header"><?= piccolo_e($titulo) ?></div>
  <div class="card-body">
    <form method="post" novalidate>
      <?php if (isset($materia['id'])): ?>
        <input type="hidden" name="txtID" value="<?= (int) $materia['id'] ?>">
      <?php endif; ?>

      <div class="mb-3">
        <label for="nombre" class="form-label">Nombre:</label>
        <input type="text" class="form-control<?= $invalido('nombre') ?>" name="nombre" id="nombre" maxlength="50"
          value="<?= piccolo_e($materia['nombre'] ?? '') ?>" placeholder="Ej: Harina, Tomate" required>
        <?= $error('nombre') ?>
      </div>

      <div class="mb-3">
        <label for="unidad_medida" class="form-label">Unidad de medida:</label>
        <input type="text" class="form-control<?= $invalido('unidad_medida') ?>" name="unidad_medida" id="unidad_medida" maxlength="50"
          value="<?= piccolo_e($materia['unidad_medida'] ?? '') ?>" placeholder="Ej: kg, litros" required>
        <?= $error('unidad_medida') ?>
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label for="cantidad" class="form-label">Cantidad en stock:</label>
          <input type="number" step="0.01" min="0" class="form-control<?= $invalido('cantidad') ?>" name="cantidad" id="cantidad"
            value="<?= piccolo_e($materia['cantidad'] ?? '') ?>" placeholder="Ej: 5.00" required>
          <?= $error('cantidad') ?>
        </div>
        <div class="col-md-6 mb-3">
          <label for="stock_minimo" class="form-label">Stock mínimo (opcional):</label>
          <input type="number" step="0.01" min="0" class="form-control<?= $invalido('stock_minimo') ?>" name="stock_minimo" id="stock_minimo"
            value="<?= piccolo_e($materia['stock_minimo'] ?? '') ?>" placeholder="Avisa cuando quede esta cantidad o menos">
          <?= $error('stock_minimo') ?>
        </div>
      </div>

      <div class="mb-3">
        <label for="proveedor_id" class="form-label">Proveedor:</label>
        <select class="form-select<?= $invalido('proveedor_id') ?>" name="proveedor_id" id="proveedor_id">
          <option value="0">Sin proveedor</option>
          <?php foreach ($proveedores as $proveedor): ?>
            <option value="<?= $proveedor['id'] ?>" <?= $proveedorElegido === $proveedor['id'] ? 'selected' : '' ?>><?= piccolo_e($proveedor['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
        <?= $error('proveedor_id') ?>
      </div>

      <button type="submit" class="btn btn-success"><?= piccolo_e($boton) ?></button>
      <a class="btn btn-primary" href="index.php" role="button">Cancelar</a>
    </form>
  </div>
</div>
