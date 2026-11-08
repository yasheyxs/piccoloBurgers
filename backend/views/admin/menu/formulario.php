<?php
/**
 * Formulario para agregar o editar un producto del menú.
 *
 * @var string $titulo
 * @var string $boton
 * @var array<string, mixed> $producto
 * @var string[] $categorias
 * @var array<string, string> $errores
 * @var array{tipo: string, mensaje: string}|null $aviso
 */
$invalido = static fn(string $campo): string => isset($errores[$campo]) ? ' is-invalid' : '';
$error = static fn(string $campo): string => '<div class="invalid-feedback">' . piccolo_e($errores[$campo] ?? '') . '</div>';
$visible = filter_var($producto['visible'] ?? true, FILTER_VALIDATE_BOOLEAN);
?>
<br>
<?php include __DIR__ . '/../_aviso.php'; ?>

<div class="card">
  <div class="card-header"><?= piccolo_e($titulo) ?></div>
  <div class="card-body">
    <form method="post" enctype="multipart/form-data" novalidate>
      <?php if (isset($producto['id'])): ?>
        <input type="hidden" name="txtID" value="<?= (int) $producto['id'] ?>">
      <?php endif; ?>

      <div class="mb-3">
        <label for="nombre" class="form-label">Nombre:</label>
        <input type="text" class="form-control<?= $invalido('nombre') ?>" name="nombre" id="nombre" maxlength="255"
          value="<?= piccolo_e($producto['nombre'] ?? '') ?>" placeholder="Ej: Hamburguesa doble" required>
        <?= $error('nombre') ?>
      </div>

      <div class="mb-3">
        <label for="ingredientes" class="form-label">Ingredientes (se muestran en el menú del sitio):</label>
        <input type="text" class="form-control<?= $invalido('ingredientes') ?>" name="ingredientes" id="ingredientes" maxlength="255"
          value="<?= piccolo_e($producto['ingredientes'] ?? '') ?>" placeholder="Ej: Pan, medallón, cheddar, panceta" required>
        <?= $error('ingredientes') ?>
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label for="categoria" class="form-label">Categoría:</label>
          <select class="form-select<?= $invalido('categoria') ?>" name="categoria" id="categoria" required>
            <option value="">Elegir categoría</option>
            <?php foreach ($categorias as $categoria): ?>
              <option value="<?= piccolo_e($categoria) ?>" <?= ($producto['categoria'] ?? '') === $categoria ? 'selected' : '' ?>><?= piccolo_e($categoria) ?></option>
            <?php endforeach; ?>
          </select>
          <?= $error('categoria') ?>
        </div>
        <div class="col-md-6 mb-3">
          <label for="precio" class="form-label">Precio:</label>
          <input type="number" step="0.01" min="0" class="form-control<?= $invalido('precio') ?>" name="precio" id="precio"
            value="<?= piccolo_e($producto['precio'] ?? '') ?>" placeholder="Ej: 1500" required>
          <?= $error('precio') ?>
        </div>
      </div>

      <div class="mb-3">
        <label for="foto" class="form-label">Foto (JPG, PNG o WEBP, hasta 5 MB):</label>
        <?php if (!empty($producto['imagen']) && !empty($producto['foto'])): ?>
          <div class="mb-2"><img src="<?= piccolo_e($producto['imagen']) ?>" alt="Foto actual" width="80" class="rounded"></div>
        <?php endif; ?>
        <input type="file" class="form-control<?= $invalido('foto') ?>" name="foto" id="foto" accept="image/jpeg,image/png,image/webp">
        <?= $error('foto') ?>
      </div>

      <div class="form-check form-switch mb-3">
        <input type="hidden" name="visible" value="0">
        <input class="form-check-input" type="checkbox" role="switch" name="visible" id="visible" value="1" <?= $visible ? 'checked' : '' ?>>
        <label class="form-check-label" for="visible">Mostrar en el menú del sitio</label>
      </div>

      <button type="submit" class="btn btn-success"><?= piccolo_e($boton) ?></button>
      <a class="btn btn-primary" href="index.php" role="button">Cancelar</a>
    </form>
  </div>
</div>
