<?php
/**
 * Cambiar la propia contraseña (con la sesión iniciada).
 *
 * @var array<string, string> $errores
 * @var array{tipo: string, mensaje: string}|null $aviso
 */
?>
<div class="container mt-5">
  <div class="row justify-content-center">
    <div class="col-md-6">
      <h4 class="mb-3">Cambiar mi contraseña</h4>
      <?php include __DIR__ . '/../_aviso.php'; ?>
      <form method="post" novalidate>
        <div class="mb-3">
          <label for="actual" class="form-label">Contraseña actual</label>
          <input type="password" class="form-control<?= isset($errores['actual']) ? ' is-invalid' : '' ?>" name="actual" id="actual" required autocomplete="current-password">
          <?php if (isset($errores['actual'])): ?><div class="invalid-feedback"><?= piccolo_e($errores['actual']) ?></div><?php endif; ?>
        </div>
        <?php include __DIR__ . '/_campos.php'; ?>
        <button type="submit" class="btn btn-success">Cambiar contraseña</button>
      </form>
    </div>
  </div>
</div>
