<?php
/**
 * Formulario para crear o editar un testimonio.
 *
 * @var string $titulo
 * @var string $boton
 * @var array<string, mixed> $testimonio
 * @var array<string, string> $errores
 * @var array{tipo: string, mensaje: string}|null $aviso
 */
$invalido = static fn(string $campo): string => isset($errores[$campo]) ? ' is-invalid' : '';
$error = static fn(string $campo): string => isset($errores[$campo]) ? '<div class="invalid-feedback">' . piccolo_e($errores[$campo]) . '</div>' : '';
?>
<br>
<?php include __DIR__ . '/../_aviso.php'; ?>

<div class="card">
  <div class="card-header"><?= piccolo_e($titulo) ?></div>
  <div class="card-body">
    <form method="post" novalidate>
      <div class="mb-3">
        <label for="opinion" class="form-label">Opinión:</label>
        <textarea class="form-control<?= $invalido('opinion') ?>" name="opinion" id="opinion" rows="3" maxlength="255" placeholder="Ej: Las mejores hamburguesas de la zona" required><?= piccolo_e($testimonio['opinion'] ?? '') ?></textarea>
        <?= $error('opinion') ?>
      </div>
      <div class="mb-3">
        <label for="nombre" class="form-label">Nombre:</label>
        <input type="text" class="form-control<?= $invalido('nombre') ?>" name="nombre" id="nombre" maxlength="100" value="<?= piccolo_e($testimonio['nombre'] ?? '') ?>" placeholder="Ej: Lucía" required>
        <?= $error('nombre') ?>
      </div>
      <button type="submit" class="btn btn-success"><?= piccolo_e($boton) ?></button>
      <a class="btn btn-primary" href="index.php" role="button">Cancelar</a>
    </form>
  </div>
</div>
