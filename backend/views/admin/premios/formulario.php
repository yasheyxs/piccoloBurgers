<?php
/**
 * Formulario para crear o editar un premio.
 *
 * @var string $titulo
 * @var string $boton
 * @var array<string, mixed> $premio
 * @var array<string, string> $errores
 * @var array{tipo: string, mensaje: string}|null $aviso
 */
$invalido = static fn(string $campo): string => isset($errores[$campo]) ? ' is-invalid' : '';
$error = static fn(string $campo): string => isset($errores[$campo]) ? '<div class="invalid-feedback">' . piccolo_e($errores[$campo]) . '</div>' : '';
?>
<div class="py-4">
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <h1 class="h3 mb-0"><?= piccolo_e($titulo) ?></h1>
    <a href="index.php" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-arrow-left me-1" aria-hidden="true"></i>Volver a premios</a>
  </div>

  <?php include __DIR__ . '/../_aviso.php'; ?>

  <div class="card shadow-sm">
    <div class="card-body">
      <form method="post" class="row g-3" novalidate>
        <div class="col-md-8">
          <label for="nombre" class="form-label">Nombre</label>
          <input type="text" class="form-control<?= $invalido('nombre') ?>" id="nombre" name="nombre" maxlength="100" value="<?= piccolo_e($premio['nombre'] ?? '') ?>" placeholder="Ej: Papas fritas medianas" required>
          <?= $error('nombre') ?>
        </div>
        <div class="col-md-4">
          <label for="costo_puntos" class="form-label">Costo en puntos</label>
          <input type="number" min="1" step="1" class="form-control<?= $invalido('costo_puntos') ?>" id="costo_puntos" name="costo_puntos" value="<?= piccolo_e($premio['costo_puntos'] ?? '') ?>" required>
          <?= $error('costo_puntos') ?>
        </div>
        <div class="col-12">
          <label for="descripcion" class="form-label">Descripción (opcional)</label>
          <textarea class="form-control<?= $invalido('descripcion') ?>" id="descripcion" name="descripcion" rows="3" maxlength="1000"><?= piccolo_e($premio['descripcion'] ?? '') ?></textarea>
          <?= $error('descripcion') ?>
        </div>
        <div class="col-12 d-flex justify-content-end gap-2">
          <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
          <button type="submit" class="btn btn-success"><?= piccolo_e($boton) ?></button>
        </div>
      </form>
    </div>
  </div>
</div>
