<?php
/**
 * Formulario para crear o editar un banner.
 *
 * @var string $titulo
 * @var string $boton
 * @var array<string, mixed> $banner
 * @var array<string, string> $errores
 * @var array{tipo: string, mensaje: string}|null $aviso
 */
use Piccolo\Http\Admin\ContenidoController;

$invalido = static fn(string $campo): string => isset($errores[$campo]) ? ' is-invalid' : '';
$error = static fn(string $campo): string => isset($errores[$campo]) ? '<div class="invalid-feedback">' . piccolo_e($errores[$campo]) . '</div>' : '';
$editando = isset($banner['id']);
?>
<br>
<?php include __DIR__ . '/../_aviso.php'; ?>

<div class="card">
  <div class="card-header"><?= piccolo_e($titulo) ?></div>
  <div class="card-body">
    <form method="post" enctype="multipart/form-data" novalidate>
      <div class="mb-3">
        <label for="titulo" class="form-label">Título:</label>
        <input type="text" class="form-control<?= $invalido('titulo') ?>" name="titulo" id="titulo" maxlength="100" value="<?= piccolo_e($banner['titulo'] ?? '') ?>" placeholder="Ej: Piccolo Burgers" required>
        <?= $error('titulo') ?>
      </div>
      <div class="mb-3">
        <label for="descripcion" class="form-label">Descripción:</label>
        <input type="text" class="form-control<?= $invalido('descripcion') ?>" name="descripcion" id="descripcion" maxlength="255" value="<?= piccolo_e($banner['descripcion'] ?? '') ?>" placeholder="Ej: 100% cargadas de sabor" required>
        <?= $error('descripcion') ?>
      </div>
      <div class="mb-3">
        <label for="imagen" class="form-label">Imagen (JPG, PNG o WEBP, de al menos <?= ContenidoController::BANNER_ANCHO ?> × <?= ContenidoController::BANNER_ALTO ?> píxeles y hasta 5 MB):</label>
        <?php if ($editando): ?>
          <div class="mb-2"><img src="<?= piccolo_e($banner['imagen']) ?>" alt="Imagen actual" width="240" class="rounded"></div>
        <?php endif; ?>
        <input type="file" class="form-control<?= $invalido('imagen') ?>" name="imagen" id="imagen" accept="image/jpeg,image/png,image/webp" <?= $editando ? '' : 'required' ?>>
        <?= $error('imagen') ?>
        <?php if ($editando): ?><div class="form-text">Si no elegís otra, queda la imagen actual.</div><?php endif; ?>
      </div>
      <button type="submit" class="btn btn-success"><?= piccolo_e($boton) ?></button>
      <a class="btn btn-primary" href="index.php" role="button">Cancelar</a>
    </form>
  </div>
</div>
